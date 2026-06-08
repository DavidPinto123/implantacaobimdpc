<x-filament::page>

    {{-- CSS do Autodesk Viewer (OBRIGATÓRIO para botões aparecerem!) --}}
    <link
        rel="stylesheet"
        href="https://developer.api.autodesk.com/modelderivative/v2/viewers/7.*/style.min.css"
        type="text/css"
    />

    <div class="space-y-4">
        <div class="rounded-xl border bg-white shadow overflow-hidden">
            <div id="forgeViewer" class="w-full" style="height: calc(100vh - 220px);"></div>
        </div>
    </div>

    {{-- JS do Viewer --}}
    <script src="https://developer.api.autodesk.com/modelderivative/v2/viewers/7.*/viewer3D.js"></script>

    <script>
        const modelUrn = "{{ $this->modelUrn }}";
        let viewer = null;

        async function getAccessToken(callback) {
            try {
                const response = await fetch("{{ route('aps.token') }}");
                const json = await response.json();

                if (!json.access_token) {
                    console.error('Token APS inválido:', json);
                    return;
                }

                callback(json.access_token, json.expires_in);
            } catch (e) {
                console.error('Erro ao buscar token APS:', e);
            }
        }

        const options = {
            env: 'AutodeskProduction',
            getAccessToken,
        };

        function initViewer() {
            if (!modelUrn) {
                console.error('Nenhum URN encontrado.');
                return;
            }

            const htmlDiv = document.getElementById('forgeViewer');
            viewer = new Autodesk.Viewing.GuiViewer3D(htmlDiv);

            const startedCode = viewer.start();
            if (startedCode > 0) {
                console.error('Erro ao iniciar viewer:', startedCode);
                return;
            }

            Autodesk.Viewing.Document.load(
                "urn:" + modelUrn,
                (doc) => {
                    const defaultModel = doc.getRoot().getDefaultGeometry();

                    if (!defaultModel) {
                        console.error("Nenhuma geometria padrão.");
                        return;
                    }

                    viewer.loadDocumentNode(doc, defaultModel, {}, () => {
                        viewer.addEventListener(
                            Autodesk.Viewing.GEOMETRY_LOADED_EVENT,
                            () => {
                                viewer.fitToView();
                                viewer.resize();

                                // 🔥 Mostra toolbar
                                viewer.getToolbar(true);

                                // 🔥 Ativa extensões
                                const extensions = [
                                    'Autodesk.Measure',
                                    'Autodesk.Section',
                                    'Autodesk.Explode',
                                    'Autodesk.ModelStructure',
                                    'Autodesk.FullScreen',
                                    'Autodesk.Viewing.MarkupsCore',
                                    'Autodesk.Viewing.MarkupsGui',
                                ];

                                extensions.forEach(ext => {
                                    viewer.loadExtension(ext).catch(err =>
                                        console.warn('Extensão falhou', ext, err)
                                    );
                                });

                            },
                            { once: true }
                        );
                    });
                },
                (errorCode) => {
                    console.error("Erro ao carregar documento:", errorCode);
                }
            );
        }

        document.addEventListener("DOMContentLoaded", () => {
            Autodesk.Viewing.Initializer(options, initViewer);
        });

        window.addEventListener("resize", () => viewer?.resize());
    </script>

    <style>
        #forgeViewer { position: relative; }

        /* Ajuste do ViewCube no Filament */
        #forgeViewer .viewcubeDiv {
            position: absolute !important;
            top: 10px !important;
            right: 10px !important;
        }
    </style>

</x-filament::page>
