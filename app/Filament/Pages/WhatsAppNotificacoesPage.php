<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Models\WhatsappSubscricao;
use App\Models\WhatsappTemplateConfig;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;

class WhatsAppNotificacoesPage extends Page
{
    protected static ?string $navigationLabel = 'Notificações';

    protected static \UnitEnum|string|null $navigationGroup = 'WhatsApp';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Gestão de Notificações';

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-bell';

    protected string $view = 'filament.pages.whats-app-notificacoes-page';

    // Definição de todos os templates conhecidos
    const TEMPLATES = [
        'agenda_semanal' => [
            'label'    => 'Agenda Semanal',
            'descricao' => 'Resumo semanal de tarefas — agendado toda segunda às 9h',
            'tipo'     => 'broadcast',
            'comando'  => 'whatsapp:agenda-semanal',
        ],
        'tarefa_atrasada' => [
            'label'    => 'Tarefas Atrasadas (individual)',
            'descricao' => 'Notifica cada responsável sobre sua própria tarefa vencida — agendado todo dia às 8h',
            'tipo'     => 'broadcast',
            'comando'  => 'whatsapp:notificar-atrasos',
        ],
        'resumo_atrasos' => [
            'label'    => 'Resumo de Atrasos (gerencial)',
            'descricao' => 'Envia lista consolidada de TODAS as tarefas atrasadas com o profissional responsável',
            'tipo'     => 'broadcast',
            'comando'  => 'whatsapp:resumo-atrasos',
        ],
        'nova_tarefa' => [
            'label'    => 'Nova Tarefa',
            'descricao' => 'Disparado automaticamente ao criar uma tarefa',
            'tipo'     => 'automatico',
        ],
        'status_tarefa' => [
            'label'    => 'Status da Tarefa',
            'descricao' => 'Disparado automaticamente ao alterar o status de uma tarefa',
            'tipo'     => 'automatico',
        ],
        'gerente_notificacao' => [
            'label'    => 'Notificação ao Gerente',
            'descricao' => 'Disparado automaticamente para o Gerente Geral do projeto',
            'tipo'     => 'automatico',
        ],
        'prazo_proximo' => [
            'label'    => 'Prazo Próximo',
            'descricao' => 'Aviso quando o prazo de uma tarefa está se aproximando',
            'tipo'     => 'automatico',
        ],
        'tarefa_comentario' => [
            'label'    => 'Comentário em Tarefa',
            'descricao' => 'Disparado ao adicionar um comentário em uma tarefa',
            'tipo'     => 'automatico',
        ],
        'cronograma_atualizado' => [
            'label'    => 'Cronograma Atualizado',
            'descricao' => 'Disparado ao atualizar o cronograma de um planejamento',
            'tipo'     => 'automatico',
        ],
    ];

    public ?string $painelAberto = null;

    // Envio manual
    public string $envioTemplateKey = 'agenda_semanal';
    public ?int $envioUserId = null;

    public int $renderKey = 0;

    // ── Dados ──────────────────────────────────────────────────────────────

    public function getTemplatesComStatus(): array
    {
        $configs   = WhatsappTemplateConfig::pluck('ativo', 'template_key');
        $contagens = WhatsappSubscricao::selectRaw('template_key, COUNT(*) as total')
            ->groupBy('template_key')
            ->pluck('total', 'template_key');

        return collect(self::TEMPLATES)->map(fn ($def, $key) => array_merge($def, [
            'key'              => $key,
            'ativo'            => (bool) ($configs->get($key) ?? true),
            'total_assinantes' => (int) $contagens->get($key, 0),
            'configurado'      => (bool) config("services.whatsapp.templates.{$key}"),
        ]))->values()->all();
    }

    public function getUsuariosParaSubscricao(string $templateKey): \Illuminate\Support\Collection
    {
        $inscritos = WhatsappSubscricao::where('template_key', $templateKey)
            ->pluck('user_id')
            ->toArray();

        return User::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'phone'])
            ->map(fn ($u) => [
                'id'       => $u->id,
                'name'     => $u->name,
                'phone'    => $u->phone,
                'inscrito' => in_array($u->id, $inscritos),
            ]);
    }

    public function getUsuariosSelect(): array
    {
        return User::where('is_active', true)
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    // ── Ações ──────────────────────────────────────────────────────────────

    public function toggleAtivo(string $key): void
    {
        $atual = WhatsappTemplateConfig::isAtivo($key);
        WhatsappTemplateConfig::setAtivo($key, ! $atual);
        $this->renderKey++;

        Notification::make()
            ->title('Template ' . (! $atual ? 'ativado' : 'pausado'))
            ->success()
            ->send();
    }

    public function toggleSubscricao(int $userId, string $key): void
    {
        $existente = WhatsappSubscricao::where('user_id', $userId)
            ->where('template_key', $key)
            ->first();

        if ($existente) {
            $existente->delete();
        } else {
            WhatsappSubscricao::create(['user_id' => $userId, 'template_key' => $key]);
        }

        $this->renderKey++;
    }

    public function selecionarTodos(string $key): void
    {
        $comTelefone = User::where('is_active', true)
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->pluck('id');

        foreach ($comTelefone as $userId) {
            WhatsappSubscricao::firstOrCreate([
                'user_id'      => $userId,
                'template_key' => $key,
            ]);
        }

        $this->renderKey++;
        Notification::make()->title('Todos os usuários com telefone adicionados')->success()->send();
    }

    public function removerTodos(string $key): void
    {
        WhatsappSubscricao::where('template_key', $key)->delete();
        $this->renderKey++;
        Notification::make()->title('Todos os assinantes removidos')->warning()->send();
    }

    public function abrirPainel(string $key): void
    {
        $this->painelAberto = $this->painelAberto === $key ? null : $key;
    }

    public function enviarParaTodos(string $key): void
    {
        $def = self::TEMPLATES[$key] ?? null;
        if (! $def || $def['tipo'] !== 'broadcast') {
            Notification::make()->title('Template inválido')->danger()->send();
            return;
        }

        Artisan::call($def['comando']);
        $output = trim(Artisan::output());

        Notification::make()
            ->title('Enviado — ' . $def['label'])
            ->body($output ?: 'Mensagens enfileiradas para todos os assinantes.')
            ->success()
            ->send();

        $this->renderKey++;
    }

    public function adicionarPorPerfil(string $key, string $roleName): void
    {
        if (! $roleName) {
            return;
        }

        $usuarios = User::role($roleName)
            ->where('is_active', true)
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->pluck('id');

        $adicionados = 0;
        foreach ($usuarios as $userId) {
            $entry = WhatsappSubscricao::firstOrCreate([
                'user_id'      => $userId,
                'template_key' => $key,
            ]);
            if ($entry->wasRecentlyCreated) {
                $adicionados++;
            }
        }

        $this->renderKey++;
        Notification::make()
            ->title("{$adicionados} usuário(s) do perfil "{$roleName}" adicionado(s)")
            ->success()
            ->send();
    }

    public function getRoles(): array
    {
        return \Spatie\Permission\Models\Role::orderBy('name')->pluck('name')->toArray();
    }

    public function enviarManual(): void
    {
        if (! $this->envioTemplateKey) {
            Notification::make()->title('Selecione o template')->warning()->send();
            return;
        }

        $def = self::TEMPLATES[$this->envioTemplateKey] ?? null;
        if (! $def || $def['tipo'] !== 'broadcast') {
            Notification::make()->title('Só é possível envio manual de templates Broadcast')->warning()->send();
            return;
        }

        $comando = $def['comando'];
        $args    = [];

        if ($this->envioUserId) {
            $user = User::find($this->envioUserId);
            if (! $user) {
                Notification::make()->title('Usuário não encontrado')->danger()->send();
                return;
            }
            $args['--user'] = $user->email;
        }
        // sem usuário → envia para todos os assinantes (ou todos com telefone se sem assinantes)

        Artisan::call($comando, $args);
        $output = trim(Artisan::output());

        Notification::make()
            ->title('Envio concluído')
            ->body($output ?: 'Mensagens enfileiradas.')
            ->success()
            ->send();

        $this->renderKey++;
    }
}
