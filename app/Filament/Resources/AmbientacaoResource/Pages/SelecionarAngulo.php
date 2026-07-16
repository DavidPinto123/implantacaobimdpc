<?php

namespace App\Filament\Resources\AmbientacaoResource\Pages;

use App\Filament\Resources\AmbientacaoResource;
use App\Models\Ambientacao;
use App\Services\EquirectangularCropper;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Storage;

class SelecionarAngulo extends Page
{
    protected static string $resource = AmbientacaoResource::class;

    public string $view = 'filament.resources.ambientacao-resource.pages.selecionar-angulo';

    public Ambientacao $ambientacao;

    public function mount(Ambientacao $record): void
    {
        abort_unless(auth()->user()?->can('update', $record), 403);
        abort_unless(filled($record->pano_equirretangular), 404);

        $this->ambientacao = $record;
    }

    public function getTitle(): string
    {
        return 'Selecionar ângulo — '.$this->ambientacao->ambiente;
    }

    public function panoUrl(): string
    {
        $disk = (string) config('filesystems.media_disk', 'r2');

        return Storage::disk($disk)->url($this->ambientacao->pano_equirretangular);
    }

    public function capturar(float $yaw, float $pitch, float $fov, ?string $legenda = null): void
    {
        $disk = (string) config('filesystems.media_disk', 'r2');

        $sourcePath = Storage::disk($disk)->path($this->ambientacao->pano_equirretangular);

        $binary = app(EquirectangularCropper::class)->crop($sourcePath, $yaw, $pitch, $fov);

        $filename = "ambientacoes/{$this->ambientacao->id}/imagens/recorte-".now()->timestamp.'.jpg';

        Storage::disk($disk)->put($filename, $binary, 'public');

        $this->ambientacao->imagens()->create([
            'arquivo' => $filename,
            'legenda' => $legenda,
            'origem' => 'recorte_360',
            'yaw' => $yaw,
            'pitch' => $pitch,
            'fov' => $fov,
            'uploaded_by' => auth()->id(),
        ]);

        Notification::make()
            ->title('Recorte capturado com sucesso')
            ->success()
            ->send();

        $this->redirect(AmbientacaoResource::getUrl('edit', ['record' => $this->ambientacao]));
    }
}
