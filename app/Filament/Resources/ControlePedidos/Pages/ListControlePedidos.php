<?php

namespace App\Filament\Resources\ControlePedidos\Pages;

use App\Filament\Resources\ControlePedidos\ControlePedidoResource;
use App\Imports\ControlePedidosBaseImport;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Maatwebsite\Excel\Facades\Excel;

class ListControlePedidos extends ListRecords
{
    protected static string $resource = ControlePedidoResource::class;

    protected function getHeaderActions(): array
    {
        return [

            Actions\CreateAction::make()
                ->label('Criar Pedido'),

            Action::make('importarBase')
                ->label('Importar Planilha Base')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')

                ->form([
                    Placeholder::make('modelo')
                        ->label('')
                        ->content(function (): HtmlString {
                            /** @var FilesystemAdapter $disk */
                            $disk = Storage::disk((string) config('filesystems.media_disk', 'r2'));

                            return new HtmlString('
                                <div class="rounded-lg border border-gray-200 dark:border-gray-700
                                            p-4 bg-gray-50/50 dark:bg-gray-800/40">

                                    <div class="flex items-center justify-between">

                                        <div>
                                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                Modelo de importação
                                            </p>

                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                Utilize a planilha padrão para evitar erros na importação.
                                            </p>
                                        </div>

                                        <a href="'.$disk->url('controle-pedidos/modelo_controle_pedidos.xlsx').'"
                                        target="_blank"
                                        class="inline-flex items-center gap-2 px-3 py-2
                                                text-sm font-medium rounded-md
                                                bg-white dark:bg-gray-900
                                                border border-gray-300 dark:border-gray-600
                                                hover:bg-gray-100 dark:hover:bg-gray-700
                                                transition">

                                            📥 Baixar modelo
                                        </a>

                                    </div>
                                </div>
                            ');
                        }),

                    FileUpload::make('arquivo')
                        ->label('Arquivo Excel')
                        ->disk((string) config('filesystems.media_disk', 'r2'))
                        ->directory('imports')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                        ])
                        ->required(),
                ])

                ->action(function (array $data) {
                    /** @var FilesystemAdapter $disk */
                    $disk = Storage::disk((string) config('filesystems.media_disk', 'r2'));
                    $stream = $disk->readStream($data['arquivo']);

                    if (! is_resource($stream)) {
                        throw new \RuntimeException('Não foi possível ler o arquivo importado.');
                    }

                    $temporaryDirectory = storage_path('app/tmp');

                    if (! is_dir($temporaryDirectory)) {
                        mkdir($temporaryDirectory, 0775, true);
                    }

                    $extension = pathinfo((string) $data['arquivo'], PATHINFO_EXTENSION) ?: 'xlsx';
                    $temporaryBasePath = tempnam($temporaryDirectory, 'controle-pedidos-');

                    if ($temporaryBasePath === false) {
                        fclose($stream);

                        throw new \RuntimeException('Não foi possível preparar o arquivo temporário para importação.');
                    }

                    $temporaryPath = $temporaryBasePath.'.'.$extension;
                    rename($temporaryBasePath, $temporaryPath);

                    try {
                        $destination = fopen($temporaryPath, 'wb');

                        if (! is_resource($destination)) {
                            throw new \RuntimeException('Não foi possível gravar o arquivo temporário para importação.');
                        }

                        stream_copy_to_stream($stream, $destination);
                        fclose($destination);

                        Excel::import(new ControlePedidosBaseImport, $temporaryPath);
                    } finally {
                        fclose($stream);

                        if (file_exists($temporaryPath)) {
                            unlink($temporaryPath);
                        }
                    }
                })
                ->modalHeading('Importar Planilha Base')
                ->modalSubmitActionLabel('Importar')
                ->successNotificationTitle('Planilha importada com sucesso!'),
        ];
    }
}
