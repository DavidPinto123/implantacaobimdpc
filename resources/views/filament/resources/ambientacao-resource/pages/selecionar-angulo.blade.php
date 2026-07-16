<x-filament::page>
    <link rel="stylesheet" href="{{ asset('vendor/photo-sphere-viewer/photo-sphere-viewer.css') }}">

    <div
        x-data="{
            yaw: 0,
            pitch: 0,
            fov: 75,
            legenda: '',
            capturando: false,
        }"
        x-on:angulo-updated.window="yaw = $event.detail.yaw; pitch = $event.detail.pitch"
        class="space-y-4"
    >
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Arraste dentro da imagem para escolher o ângulo. Ajuste o campo de visão e clique em "Capturar recorte" para gerar uma imagem estática dessa vista.
        </p>

        <div id="angulo-picker-container" class="w-full overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700" style="height: 520px;"></div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <label class="text-sm">
                <span class="mb-1 block font-medium text-gray-700 dark:text-gray-300">Campo de visão (FOV)</span>
                <input type="range" min="30" max="100" step="1" x-model.number="fov" class="w-full">
                <span x-text="fov + '°'" class="text-xs text-gray-500"></span>
            </label>

            <div class="text-sm sm:col-span-2">
                <span class="mb-1 block font-medium text-gray-700 dark:text-gray-300">Ângulo atual</span>
                <span x-text="'Yaw: ' + yaw.toFixed(1) + '° · Pitch: ' + pitch.toFixed(1) + '°'" class="text-xs text-gray-500"></span>
            </div>
        </div>

        <label class="block text-sm">
            <span class="mb-1 block font-medium text-gray-700 dark:text-gray-300">Legenda (opcional)</span>
            <textarea x-model="legenda" rows="2" class="fi-input block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900"></textarea>
        </label>

        <div class="flex justify-end">
            <button
                type="button"
                x-on:click="capturando = true; $wire.capturar(yaw, pitch, fov, legenda)"
                x-bind:disabled="capturando"
                class="fi-btn fi-btn-size-md fi-color-primary inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
            >
                <span x-show="!capturando">Capturar recorte</span>
                <span x-show="capturando">Gerando recorte...</span>
            </button>
        </div>
    </div>

    <script type="importmap">
        {
            "imports": {
                "three": "{{ asset('vendor/photo-sphere-viewer/three.module.js') }}"
            }
        }
    </script>

    <script type="module">
        import { Viewer } from "{{ asset('vendor/photo-sphere-viewer/photo-sphere-viewer.module.js') }}";

        const viewer = new Viewer({
            container: document.getElementById('angulo-picker-container'),
            panorama: @js($this->panoUrl()),
            navbar: false,
            defaultZoomLvl: 30,
        });

        viewer.addEventListener('position-updated', ({ position }) => {
            window.dispatchEvent(new CustomEvent('angulo-updated', {
                detail: {
                    yaw: position.yaw * 180 / Math.PI,
                    pitch: position.pitch * 180 / Math.PI,
                },
            }));
        });
    </script>
</x-filament::page>
