<div
    x-data="{
        url: @js($url),
        toggleFullscreen() {
            const el = this.$refs.panoContainer;
            if (!el) return;
            if (!document.fullscreenElement) {
                el.requestFullscreen ? el.requestFullscreen() : el.classList.add('amb-pano-fullscreen-fallback');
            } else {
                document.exitFullscreen ? document.exitFullscreen() : el.classList.remove('amb-pano-fullscreen-fallback');
            }
        },
    }"
    x-effect="url = @js($url)"
>
    <template x-if="url">
        <div
            x-ref="panoContainer"
            class="relative w-full overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700"
            style="height: 480px;"
        >
            <iframe :src="url" class="h-full w-full border-0" title="Pré-visualização do Render 360°" allowfullscreen></iframe>

            <button
                type="button"
                x-on:click="toggleFullscreen()"
                class="absolute right-3 top-3 z-10 inline-flex items-center gap-1 rounded-md bg-gray-900/70 px-3 py-1.5 text-xs font-medium text-white hover:bg-gray-900/90"
            >
                Tela cheia
            </button>
        </div>
    </template>

    <template x-if="!url">
        <div class="flex h-32 items-center justify-center rounded-lg border border-dashed border-gray-300 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
            Informe o link do Render 360° para ver a pré-visualização.
        </div>
    </template>
</div>

<style>
    .amb-pano-fullscreen-fallback {
        position: fixed !important;
        inset: 0 !important;
        z-index: 9999 !important;
        height: 100vh !important;
        width: 100vw !important;
        border-radius: 0 !important;
    }
</style>
