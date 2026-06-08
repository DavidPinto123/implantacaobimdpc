<x-filament-panels::page>
    <div
        x-data="relatorioFotograficoAutosave($wire)"
        x-init="init()"
        x-cloak
        x-on:input.debounce.700ms="touch($event)"
        x-on:change="touch($event)"
        x-on:draft-autosaved.window="onSaved()"
        x-on:draft-autosave-error.window="onError()"
    >
        <div :inert="uploadLocked">
            {{ $this->form }}
        </div>

        <div class="mt-6 flex justify-start gap-6">
            <x-filament::button
                color="gray"
                type="button"
                wire:click="saveDraft"
                x-bind:disabled="uploadLocked"
            >
                Salvar rascunho
            </x-filament::button>

            <x-filament::button
                color="primary"
                type="button"
                wire:click="finalizar"
                x-bind:disabled="uploadLocked"
            >
                Finalizar
            </x-filament::button>
        </div>
    </div>

    <x-filament-actions::modals />

    <script>
        function relatorioFotograficoAutosave($wire) {
            return {
                timer: null,
                dirty: false,
                autosaving: false,
                observer: null,
                watchedRoots: new Set(),

                uploadLocked: false,
                uploadOverlayEl: null,
                lastUploadActivityAt: null,
                bootedAt: null,

                init() {
                    console.log('[LIVEWIRE] iniciado (relatório fotográfico)')

                    this.bootedAt = Date.now()
                    this.createUploadOverlay()
                    this.syncOverlay()

                    this.observer = new MutationObserver(() => {
                        this.lastUploadActivityAt = Date.now()

                        if (this.dirty && this.uploadLocked) {
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

                createUploadOverlay() {
                    if (document.getElementById('rf-upload-overlay')) {
                        this.uploadOverlayEl = document.getElementById('rf-upload-overlay')
                        return
                    }

                    const wrapper = document.createElement('div')
                    wrapper.id = 'rf-upload-overlay'
                    wrapper.style.position = 'fixed'
                    wrapper.style.inset = '0'
                    wrapper.style.zIndex = '999999'
                    wrapper.style.display = 'none'
                    wrapper.style.alignItems = 'center'
                    wrapper.style.justifyContent = 'center'
                    wrapper.style.padding = '16px'

                    wrapper.innerHTML = `
                        <div style="
                            position:absolute;
                            inset:0;
                            background:rgba(17,24,39,.45);
                            backdrop-filter:blur(3px);
                            -webkit-backdrop-filter:blur(3px);
                        "></div>

                        <div style="
                            position:relative;
                            width:100%;
                            max-width:420px;
                            border-radius:18px;
                            background:#ffffff;
                            box-shadow:0 25px 50px -12px rgba(0,0,0,.35);
                            border:1px solid rgba(0,0,0,.08);
                            padding:24px;
                            font-family:inherit;
                        ">
                            <div style="display:flex; gap:16px; align-items:flex-start;">
                                <div style="
                                    width:48px;
                                    height:48px;
                                    border-radius:9999px;
                                    display:flex;
                                    align-items:center;
                                    justify-content:center;
                                    background:#fef3c7;
                                    color:#d97706;
                                    flex-shrink:0;
                                ">
                                    <svg xmlns="http://www.w3.org/2000/svg" style="width:24px;height:24px;animation:rf-spin 1s linear infinite;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v4m0 8v4m8-8h-4M8 12H4m13.657-5.657l-2.828 2.828M9.172 14.828l-2.829 2.829m0-11.314l2.829 2.828m8.485 8.486l-2.828-2.829" />
                                    </svg>
                                </div>

                                <div style="min-width:0; width:100%;">
                                    <div style="font-size:16px; font-weight:700; color:#111827;">
                                        Enviando arquivos
                                    </div>

                                    <div style="margin-top:8px; font-size:14px; line-height:1.6; color:#4b5563;">
                                        Aguarde o término do upload para continuar preenchendo o formulário.
                                    </div>

                                    <div id="rf-upload-progress-text" style="margin-top:10px; font-size:14px; font-weight:600; color:#111827;">
                                        0 de 0 arquivos enviados
                                    </div>

                                    <div style="
                                        margin-top:12px;
                                        width:100%;
                                        height:8px;
                                        background:#e5e7eb;
                                        border-radius:9999px;
                                        overflow:hidden;
                                    ">
                                        <div
                                            id="rf-upload-progress-bar"
                                            style="
                                                width:0%;
                                                height:100%;
                                                background:#f59e0b;
                                                border-radius:9999px;
                                                transition:width .25s ease;
                                            "
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `

                    if (!document.getElementById('rf-upload-overlay-style')) {
                        const style = document.createElement('style')
                        style.id = 'rf-upload-overlay-style'
                        style.textContent = `
                            @keyframes rf-spin {
                                from { transform: rotate(0deg); }
                                to { transform: rotate(360deg); }
                            }
                        `
                        document.head.appendChild(style)
                    }

                    document.body.appendChild(wrapper)
                    this.uploadOverlayEl = wrapper
                },

                syncOverlay() {
                    if (!this.uploadOverlayEl) {
                        return
                    }

                    this.updateOverlayProgress()
                    this.uploadOverlayEl.style.display = this.uploadLocked ? 'flex' : 'none'
                    document.body.style.overflow = this.uploadLocked ? 'hidden' : ''
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
                        this.uploadLocked = true
                        this.lastUploadActivityAt = Date.now()
                        this.syncOverlay()
                        console.log('[UI] uploadLocked =', this.uploadLocked)
                        this.queueSave(900)
                        return
                    }

                    if (this.uploadLocked) {
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
                        console.log('[UPLOAD] root registrado', this.watchedRoots.size)
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

                getUploadStats() {
                    let total = 0
                    let completed = 0

                    this.watchedRoots.forEach((root) => {
                        if (!document.body.contains(root)) {
                            return
                        }

                        const items = [...root.querySelectorAll('.filepond--item[data-filepond-item-state]')]

                        items.forEach((item) => {
                            total++

                            const state = (item.getAttribute('data-filepond-item-state') || '').toLowerCase()
                            const statusTextResult = this.isPendingByStatusText(item)

                            const isCompleted =
                                statusTextResult === false ||
                                this.isTerminalState(state)

                            if (isCompleted) {
                                completed++
                            }
                        })
                    })

                    return { total, completed }
                },

                updateOverlayProgress() {
                    if (!this.uploadOverlayEl) {
                        return
                    }

                    const textEl = this.uploadOverlayEl.querySelector('#rf-upload-progress-text')
                    const barEl = this.uploadOverlayEl.querySelector('#rf-upload-progress-bar')

                    if (!textEl || !barEl) {
                        return
                    }

                    const { total, completed } = this.getUploadStats()
                    const percent = total > 0 ? Math.round((completed / total) * 100) : 0

                    textEl.textContent = `${completed} de ${total} arquivos enviados`
                    barEl.style.width = `${percent}%`
                },

                hasPendingUploads() {
                    if (!this.watchedRoots.size) {
                        this.updateOverlayProgress()
                        return false
                    }

                    const { total, completed } = this.getUploadStats()

                    if (total > 0 && completed >= total) {
                        this.watchedRoots.clear()
                        this.updateOverlayProgress()
                        return false
                    }

                    const staleForMs = this.lastUploadActivityAt
                        ? (Date.now() - this.lastUploadActivityAt)
                        : 0

                    if (staleForMs > 5000) {
                        if (total > 0 && completed >= Math.max(1, total - 1)) {
                            console.warn('[AUTOSAVE] destravando por timeout de segurança')
                            this.watchedRoots.clear()
                            this.updateOverlayProgress()
                            return false
                        }
                    }

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

                    completedRoots.forEach((root) => this.watchedRoots.delete(root))

                    this.updateOverlayProgress()

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

                    const stats = this.getUploadStats()

                    if (stats.total > 0 && stats.completed >= stats.total) {
                        this.uploadLocked = false
                        this.syncOverlay()
                    }

                    if (this.hasPendingUploads()) {
                        this.uploadLocked = true
                        this.syncOverlay()
                        console.log('[AUTOSAVE] aguardando uploads terminarem...')
                        this.queueSave(1000)
                        return
                    }

                    this.uploadLocked = false
                    this.syncOverlay()

                    this.autosaving = true
                    console.log('[AUTOSAVE] salvando rascunho...')
                    $wire.autoSaveDraft()
                },

                onSaved() {
                    this.autosaving = false
                    this.dirty = false
                    this.uploadLocked = false
                    this.watchedRoots.clear()
                    this.lastUploadActivityAt = null
                    this.syncOverlay()
                    console.log('[AUTOSAVE] rascunho salvo com sucesso')
                },

                onError() {
                    this.autosaving = false
                    this.dirty = true
                    this.uploadLocked = false
                    this.watchedRoots.clear()
                    this.lastUploadActivityAt = null
                    this.syncOverlay()
                    console.log('[AUTOSAVE] erro ao salvar')
                }
            }
        }
    </script>
</x-filament-panels::page>
