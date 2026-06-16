<?php

namespace App\Providers;

use App\Models\Asa;
use App\Models\AsaItem;
use App\Models\AutorizacaoServico;
use App\Models\ControleNotaFiscal;
use App\Models\ControleNotaFiscalAuxiliar;
use App\Models\ControleNotaFiscalItem;
use App\Models\ControleNotaFiscalNota;
use App\Models\ControleNotaFiscalNotaBaixa;
use App\Models\ControlePedido;
use App\Models\CronogramaFase;
use App\Models\Etapa;
use App\Models\PosObra\ConfiguracaoSla;
use App\Models\PosObra\DisciplinaConfig;
use App\Models\PosObra\Pendencia;
use App\Models\Projeto;
use App\Models\Task;
use App\Observers\ControleNotaFiscalItemObserver;
use App\Observers\FiscalObraSoftDeleteGuardObserver;
use App\Observers\TaskObserver;
use App\Policies\ControlePedidoPolicy;
use App\Policies\CronogramaFasePolicy;
use App\Policies\PosObra\ConfiguracaoSlaPolicy;
use App\Policies\PosObra\DisciplinaConfigPolicy;
use App\Policies\PosObra\PendenciaPolicy;
use App\Support\Livewire\R2GenerateSignedUploadUrl;
use Facades\Livewire\Features\SupportFileUploads\GenerateSignedUploadUrl;
use Filament\Auth\Notifications\ResetPassword as FilamentResetPasswordNotification;
use Filament\Auth\Notifications\VerifyEmail as FilamentVerifyEmailNotification;
use Filament\Facades\Filament;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('livewire.temporary_file_upload.disk') === 'r2') {
            GenerateSignedUploadUrl::swap(new R2GenerateSignedUploadUrl);
        }

        Gate::policy(ControlePedido::class, ControlePedidoPolicy::class);
        Gate::policy(Pendencia::class, PendenciaPolicy::class);
        Gate::policy(DisciplinaConfig::class, DisciplinaConfigPolicy::class);
        Gate::policy(ConfiguracaoSla::class, ConfiguracaoSlaPolicy::class);
        Gate::policy(CronogramaFase::class, CronogramaFasePolicy::class);

        ControleNotaFiscal::observe(FiscalObraSoftDeleteGuardObserver::class);
        ControleNotaFiscalItem::observe([
            ControleNotaFiscalItemObserver::class,
            FiscalObraSoftDeleteGuardObserver::class,
        ]);
        ControleNotaFiscalAuxiliar::observe(FiscalObraSoftDeleteGuardObserver::class);
        ControleNotaFiscalNota::observe(FiscalObraSoftDeleteGuardObserver::class);
        ControleNotaFiscalNotaBaixa::observe(FiscalObraSoftDeleteGuardObserver::class);
        AutorizacaoServico::observe(FiscalObraSoftDeleteGuardObserver::class);
        Asa::observe(FiscalObraSoftDeleteGuardObserver::class);
        AsaItem::observe(FiscalObraSoftDeleteGuardObserver::class);
        Task::observe(TaskObserver::class);

        FilamentResetPasswordNotification::toMailUsing(function ($notifiable, string $token): MailMessage {
            return (new MailMessage)
                ->subject('Redefina sua senha - DPC')
                ->view('emails.user-password-reset', [
                    'name' => $notifiable->name,
                    'resetUrl' => Filament::getResetPasswordUrl($token, $notifiable),
                ]);
        });

        FilamentVerifyEmailNotification::createUrlUsing(function ($notifiable): string {
            return Filament::getVerifyEmailUrl($notifiable);
        });

        FilamentVerifyEmailNotification::toMailUsing(function ($notifiable, string $verificationUrl): MailMessage {
            return (new MailMessage)
                ->subject('Verifique seu e-mail - DPC')
                ->view('emails.user-email-verification', [
                    'name' => $notifiable->name,
                    'verificationUrl' => $verificationUrl,
                ]);
        });

    }

    /**
     * Bootstrap any application services.
     */
    /*
    public function boot(): void
    {
        Filament::serving(function () {
            if (Route::is('filament.*.resources.projetos.index')) { // ajuste o nome da rota do recurso projeto
                Filament::registerRenderHook(
                    'panels::topbar.start',
                    function () {
                        $fases = Etapa::pluck('nome', 'id');
                        $counts = Projeto::query()
                            ->join('etapa_projeto', 'projetos.id', '=', 'etapa_projeto.projeto_id')
                            ->selectRaw('etapa_projeto.etapa_id, count(*) as total')
                            ->groupBy('etapa_projeto.etapa_id')
                            ->pluck('total', 'etapa_projeto.etapa_id');

                        return view('filament.topbar-etapas', compact('fases', 'counts'));
                    }
                );
            }
        });
    }
    */
}
