<x-filament-panels::page>
    <div
        x-data="asaAutosave($wire)"
        x-init="init()"
        x-cloak
        x-on:input.debounce.700ms="touch($event)"
        x-on:change="touch($event)"
        x-on:draft-autosaved.window="onSaved()"
        x-on:draft-autosave-error.window="onError()"
    >
        {{ $this->form }}

        <div class="mt-6 flex justify-start gap-6">
            <x-filament::button color="warning" wire:click="save">
                Salvar alterações
            </x-filament::button>

            <x-filament::button color="gray" tag="a" :href="$this->getResource()::getUrl('index')">
                Cancelar
            </x-filament::button>
        </div>
    </div>

    <x-filament-actions::modals />

    <script>
        function asaAutosave($wire) {
            return {
                timer: null,
                dirty: false,
                autosaving: false,
                observer: null,
                watchedRoots: new Set(),
                lastUploadActivityAt: null,
                bootedAt: null,

                init() {
                    this.bootedAt = Date.now()

                    // FileUpload não expõe um evento estável do Filament para o momento
                    // exato em que todos os arquivos já deixaram de ser temporários.
                    // Por isso observamos o DOM do FilePond e só liberamos o autosave
                    // quando o upload tiver assentado por completo.
                    this.observer = new MutationObserver(() => {
                        this.lastUploadActivityAt = Date.now()

                        if (this.dirty) {
                            this.queueSave(900)
                        }
                    })

                    this.observer.observe(document.body, {
                        subtree: true,
                        childList: true,
                        attributes: true,
                        attributeFilter: ['data-filepond-item-state', 'class'],
                    })
                },

                touch(event) {
                    if (Date.now() - this.bootedAt < 1200) {
                        return
                    }

                    if (event && event.isTrusted === false) {
                        return
                    }

                    this.dirty = true

                    if (this.isFileUploadEvent(event)) {
                        this.registerFileUploadRoot(event)
                        this.queueSave(900)
                        return
                    }

                    this.queueSave(1500)
                },

                isFileUploadEvent(event) {
                    const target = event?.target

                    return !!(
                        target?.type === 'file' ||
                        target?.closest('.fi-fo-file-upload') ||
                        target?.closest('.filepond--root')
                    )
                },

                registerFileUploadRoot(event) {
                    const target = event?.target
                    const root = target?.closest('.filepond--root') || target?.closest('.fi-fo-file-upload')

                    if (root) {
                        this.watchedRoots.add(root)
                        this.lastUploadActivityAt = Date.now()
                    }
                },

                isTerminalState(state) {
                    const normalized = (state || '').toLowerCase()

                    return [
                        'processing-complete',
                        'processing-error',
                        'load-complete',
                        'load-error',
                        'idle',
                        'local',
                        'limbo',
                        'complete',
                    ].some((terminal) => normalized.includes(terminal))
                },

                isPendingByStatusText(item) {
                    const text = (item.textContent || '').toLowerCase()

                    if (text.includes('clique para cancelar')) {
                        return true
                    }

                    if (text.includes('envio finalizado')) {
                        return false
                    }

                    if (text.includes('clique para desfazer')) {
                        return false
                    }

                    return null
                },

                rootHasPendingUploads(root) {
                    const items = [...root.querySelectorAll('.filepond--item[data-filepond-item-state]')]

                    if (!items.length) {
                        return false
                    }

                    return items.some((item) => {
                        const state = (item.getAttribute('data-filepond-item-state') || '').toLowerCase()
                        const statusTextResult = this.isPendingByStatusText(item)

                        if (statusTextResult !== null) {
                            return statusTextResult
                        }

                        const hasExplicitPendingState =
                            state.includes('processing') ||
                            state.includes('busy') ||
                            state.includes('loading')

                        if (hasExplicitPendingState && !this.isTerminalState(state)) {
                            return true
                        }

                        return false
                    })
                },

                hasPendingUploads() {
                    if (!this.watchedRoots.size) {
                        return false
                    }

                    // Depois que o FilePond para de mutar por um curto intervalo, já
                    // tratamos as raízes concluídas como estáveis e permitimos o save.
                    const staleForMs = this.lastUploadActivityAt
                        ? (Date.now() - this.lastUploadActivityAt)
                        : 0

                    let hasPending = false
                    const completedRoots = []

                    this.watchedRoots.forEach((root) => {
                        if (!document.body.contains(root)) {
                            completedRoots.push(root)
                            return
                        }

                        if (this.rootHasPendingUploads(root)) {
                            hasPending = true
                            return
                        }

                        completedRoots.push(root)
                    })

                    if (!hasPending && staleForMs > 250) {
                        completedRoots.forEach((root) => this.watchedRoots.delete(root))
                        return false
                    }

                    if (staleForMs > 5000) {
                        this.watchedRoots.clear()
                        return false
                    }

                    completedRoots.forEach((root) => this.watchedRoots.delete(root))

                    return hasPending
                },

                queueSave(delay = 1500) {
                    clearTimeout(this.timer)
                    this.timer = setTimeout(() => this.trySave(), delay)
                },

                trySave() {
                    if (!this.dirty || this.autosaving) {
                        return
                    }

                    // Uploads ainda em trânsito não podem cair no autosave, porque o
                    // backend precisa chamar saveUploadedFiles() somente no estado final.
                    if (this.hasPendingUploads()) {
                        this.queueSave(1000)
                        return
                    }

                    this.autosaving = true
                    $wire.autoSaveCurrentState()
                },

                onSaved() {
                    this.autosaving = false
                    this.dirty = false
                    this.watchedRoots.clear()
                    this.lastUploadActivityAt = null
                },

                onError() {
                    this.autosaving = false
                    this.dirty = true
                    this.watchedRoots.clear()
                    this.lastUploadActivityAt = null
                }
            }
        }
    </script>
</x-filament-panels::page>
