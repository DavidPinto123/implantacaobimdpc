<?php

namespace App\Filament\Pages;

use App\Models\PosObra\WhatsappBotMensagem;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class FluxoBotPage extends Page
{
    use HasPageShield;

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.whatsapp.fluxo-bot';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ArrowsRightLeft;

    protected static UnitEnum|string|null $navigationGroup = 'WhatsApp';

    protected static ?string $navigationLabel = 'Mensagens do Bot';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Mensagens do Bot WhatsApp';

    protected Width|string|null $maxContentWidth = Width::Full;

    public string $activeModule = 'lider';

    public ?string $chaveAtiva = null;

    public string $textoEditando = '';

    public array $noAtivo = [];

    public array $customizados = [];

    public function mount(): void
    {
        $this->customizados = WhatsappBotMensagem::pluck('chave')->toArray();
    }

    public function setModule(string $mod): void
    {
        $this->activeModule = $mod;
        $this->chaveAtiva = null;
        $this->noAtivo = [];
    }

    public function selecionarNo(string $chave): void
    {
        $this->chaveAtiva = $chave;
        $this->textoEditando = WhatsappBotMensagem::get($chave);

        foreach ($this->getMensagens() as $nodes) {
            foreach ($nodes as $no) {
                if ($no['key'] === $chave) {
                    $this->noAtivo = $no;

                    return;
                }
            }
        }
    }

    public function salvar(): void
    {
        if (! $this->chaveAtiva) {
            return;
        }

        WhatsappBotMensagem::updateOrCreate(
            ['chave' => $this->chaveAtiva],
            ['texto' => $this->textoEditando],
        );

        $this->customizados = WhatsappBotMensagem::pluck('chave')->toArray();

        Notification::make()
            ->title('Mensagem salva com sucesso')
            ->success()
            ->send();
    }

    public function restaurarPadrao(): void
    {
        if (! $this->chaveAtiva) {
            return;
        }

        WhatsappBotMensagem::where('chave', $this->chaveAtiva)->delete();

        $this->textoEditando = WhatsappBotMensagem::PADROES[$this->chaveAtiva] ?? '';
        $this->customizados = WhatsappBotMensagem::pluck('chave')->toArray();

        Notification::make()
            ->title('Texto restaurado ao padrão')
            ->warning()
            ->send();
    }

    public function getModulos(): array
    {
        return [
            'lider' => ['label' => 'Líder de Obra'],
            'construtora' => ['label' => 'Fornecedor'],
            'gestor' => ['label' => 'Gestor'],
            'sla' => ['label' => 'SLA / Geral'],
        ];
    }

    public function getMensagens(): array
    {
        $texts = WhatsappBotMensagem::whereIn('chave', array_keys(WhatsappBotMensagem::PADROES))
            ->pluck('texto', 'chave')
            ->toArray();

        $result = [];
        foreach ($this->getFluxosJs() as $modKey => $flow) {
            $result[$modKey] = array_map(function (array $node) use ($texts) {
                $text = $texts[$node['key']] ?? (WhatsappBotMensagem::PADROES[$node['key']] ?? '');

                return [
                    'key' => $node['key'],
                    'label' => $node['label'],
                    'fase' => $node['fase'],
                    'tipo' => $node['tipo'],
                    'formato' => $node['formato'] ?? 'texto',
                    'botoes' => $node['botoes'] ?? [],
                    'vars' => $node['vars'],
                    'text' => $text,
                    'custom' => in_array($node['key'], $this->customizados),
                ];
            }, $flow['nodes']);
        }

        return $result;
    }

    private function getFluxosJs(): array
    {
        return [
            'lider' => [
                'nodes' => [
                    ['key' => 'lider.inicio',               'label' => 'Boas-vindas',               'fase' => 'INICIO',                  'tipo' => 'start',   'formato' => 'botoes',  'botoes' => ['Nova Pendência', 'Listar Abertas'],                    'vars' => []],
                    ['key' => 'lider.lista_obras_header',    'label' => 'Selecionar obra',            'fase' => 'AGUARDA_OBRA',            'tipo' => 'normal',  'formato' => 'lista',   'botoes' => [],                                                      'vars' => []],
                    ['key' => 'lider.sem_obras',             'label' => 'Sem obras',                  'fase' => '',                        'tipo' => 'error',   'formato' => 'texto',   'botoes' => [],                                                      'vars' => []],
                    ['key' => 'lider.opcao_invalida',        'label' => 'Opção inválida',             'fase' => '',                        'tipo' => 'error',   'formato' => 'texto',   'botoes' => [],                                                      'vars' => []],
                    ['key' => 'lider.lista_pendencias',      'label' => 'Listar pendências',          'fase' => '',                        'tipo' => 'normal',  'formato' => 'texto',   'botoes' => [],                                                      'vars' => ['{lista}']],
                    ['key' => 'lider.sem_pendencias',        'label' => 'Sem pendências',             'fase' => '',                        'tipo' => 'success', 'formato' => 'texto',   'botoes' => [],                                                      'vars' => []],
                    ['key' => 'lider.descricao',             'label' => 'Pedir descrição',            'fase' => 'AGUARDA_DESCRICAO',       'tipo' => 'normal',  'formato' => 'texto',   'botoes' => [],                                                      'vars' => []],
                    ['key' => 'lider.obra_invalida',         'label' => 'Obra inválida',              'fase' => '',                        'tipo' => 'error',   'formato' => 'texto',   'botoes' => [],                                                      'vars' => []],
                    ['key' => 'lider.local',                 'label' => 'Pedir local',                'fase' => 'AGUARDA_LOCAL',           'tipo' => 'normal',  'formato' => 'texto',   'botoes' => [],                                                      'vars' => []],
                    ['key' => 'lider.urgencia',              'label' => 'Pedir urgência',             'fase' => 'AGUARDA_URGENCIA',        'tipo' => 'normal',  'formato' => 'lista',   'botoes' => [],                                                      'vars' => []],
                    ['key' => 'lider.urgencia_invalida',     'label' => 'Urgência inválida',          'fase' => '',                        'tipo' => 'error',   'formato' => 'texto',   'botoes' => [],                                                      'vars' => []],
                    ['key' => 'lider.impacto',               'label' => 'Pedir impacto operacional',  'fase' => 'AGUARDA_IMPACTO',         'tipo' => 'normal',  'formato' => 'botoes',  'botoes' => ['Sim', 'Não'],                                          'vars' => []],
                    ['key' => 'lider.foto',                  'label' => 'Pedir foto inicial',         'fase' => 'AGUARDA_FOTO_INICIAL',    'tipo' => 'normal',  'formato' => 'texto',   'botoes' => [],                                                      'vars' => []],
                    ['key' => 'lider.pendencia_registrada',  'label' => 'Pendência registrada',       'fase' => 'FIM DO REGISTRO',         'tipo' => 'success', 'formato' => 'texto',   'botoes' => [],                                                      'vars' => ['{codigo}']],
                    ['key' => 'lider.pedir_motivo_rejeicao', 'label' => 'Pedir motivo de rejeição',  'fase' => 'AGUARDA_APROVACAO',       'tipo' => 'normal',  'formato' => 'botoes',  'botoes' => ['✅ Aprovar', '❌ Rejeitar'],                             'vars' => []],
                    ['key' => 'lider.pendencia_aprovada',    'label' => 'Pendência aprovada',         'fase' => '',                        'tipo' => 'success', 'formato' => 'texto',   'botoes' => [],                                                      'vars' => ['{codigo}']],
                    ['key' => 'lider.pendencia_rejeitada',   'label' => 'Pendência rejeitada',        'fase' => 'AGUARDA_MOTIVO_REJEICAO', 'tipo' => 'error',   'formato' => 'texto',   'botoes' => [],                                                      'vars' => ['{codigo}']],
                ],
            ],
            'construtora' => [
                'nodes' => [
                    ['key' => 'fornecedor.aguardando_sistema',    'label' => 'Aguardando instruções',       'fase' => 'default (nova notificação)',  'tipo' => 'start',   'formato' => 'texto',   'botoes' => [],                    'vars' => []],
                    ['key' => 'fornecedor.prazo_registrado',      'label' => 'Prazo registrado',            'fase' => 'AGUARDA_CONFIRMACAO_INICIO',  'tipo' => 'normal',  'formato' => 'botoes',  'botoes' => ['🔧 Iniciar Execução'], 'vars' => []],
                    ['key' => 'fornecedor.execucao_iniciada',     'label' => 'Execução iniciada',           'fase' => 'AGUARDA_EVIDENCIAS',          'tipo' => 'normal',  'formato' => 'botoes',  'botoes' => ['✅ Concluir'],        'vars' => []],
                    ['key' => 'fornecedor.aguarda_iniciar',       'label' => 'Aguarda início',              'fase' => '',                            'tipo' => 'error',   'formato' => 'botoes',  'botoes' => ['🔧 Iniciar Execução'], 'vars' => []],
                    ['key' => 'fornecedor.foto_recebida',         'label' => 'Foto de evidência recebida',  'fase' => 'AGUARDA_EVIDENCIAS',          'tipo' => 'normal',  'formato' => 'botoes',  'botoes' => ['✅ Concluir'],        'vars' => []],
                    ['key' => 'fornecedor.orientacao_evidencias', 'label' => 'Orientação (texto inválido)', 'fase' => '',                            'tipo' => 'error',   'formato' => 'botoes',  'botoes' => ['✅ Concluir'],        'vars' => []],
                    ['key' => 'fornecedor.aprovacao_solicitada',  'label' => 'Aprovação solicitada',        'fase' => 'AGUARDA_RESOLUCAO',           'tipo' => 'success', 'formato' => 'texto',   'botoes' => [],                    'vars' => []],
                ],
            ],
            'gestor' => [
                'nodes' => [
                    ['key' => 'gestor.redirect', 'label' => 'Redirecionar para o painel', 'fase' => 'qualquer mensagem recebida', 'tipo' => 'normal', 'formato' => 'texto', 'botoes' => [], 'vars' => ['{url}']],
                ],
            ],
            'sla' => [
                'nodes' => [
                    ['key' => 'sla.escalamento',    'label' => 'Alerta de SLA vencido',  'fase' => 'automático (agendamento a cada 30min)', 'tipo' => 'normal', 'formato' => 'texto', 'botoes' => [], 'vars' => ['{prefixo}', '{codigo}', '{sigla}', '{urgencia}', '{horas}', '{status}']],
                    ['key' => 'geral.desconhecido', 'label' => 'Número não reconhecido', 'fase' => 'detectarPerfil → DESCONHECIDO',          'tipo' => 'error',  'formato' => 'texto', 'botoes' => [], 'vars' => []],
                ],
            ],
        ];
    }
}
