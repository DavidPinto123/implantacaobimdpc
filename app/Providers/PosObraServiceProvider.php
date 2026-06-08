<?php

namespace App\Providers;

use App\Events\PosObra\ExecucaoIniciada;
use App\Events\PosObra\FinalizacaoSolicitada;
use App\Events\PosObra\PendenciaAprovada;
use App\Events\PosObra\PendenciaRegistrada;
use App\Events\PosObra\PendenciaRejeitada;
use App\Events\PosObra\PrazoInformado;
use App\Events\PosObra\SlaVencido;
use App\Listeners\PosObra\NotificarConstrutoraNovasPendencias;
use App\Listeners\PosObra\NotificarFinalizacaoSolicitada;
use App\Listeners\PosObra\NotificarLiderExecucaoIniciada;
use App\Listeners\PosObra\NotificarLiderPrazoInformado;
use App\Listeners\PosObra\NotificarPendenciaAprovada;
use App\Listeners\PosObra\NotificarPendenciaRejeitada;
use App\Listeners\PosObra\NotificarSlaVencido;
use App\Models\PosObra\Pendencia;
use App\Observers\PosObra\PendenciaObserver;
use App\Services\PosObra\PendenciaService;
use App\Services\PosObra\WhatsAppBotService;
use App\Services\PosObra\WhatsAppService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class PosObraServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WhatsAppService::class);
        $this->app->singleton(PendenciaService::class);
        $this->app->singleton(WhatsAppBotService::class);
    }

    public function boot(): void
    {
        Pendencia::observe(PendenciaObserver::class);

        Event::listen(PendenciaRegistrada::class, NotificarConstrutoraNovasPendencias::class);
        Event::listen(PrazoInformado::class, NotificarLiderPrazoInformado::class);
        Event::listen(ExecucaoIniciada::class, NotificarLiderExecucaoIniciada::class);
        Event::listen(FinalizacaoSolicitada::class, NotificarFinalizacaoSolicitada::class);
        Event::listen(PendenciaAprovada::class, NotificarPendenciaAprovada::class);
        Event::listen(PendenciaRejeitada::class, NotificarPendenciaRejeitada::class);
        Event::listen(SlaVencido::class, NotificarSlaVencido::class);
    }
}
