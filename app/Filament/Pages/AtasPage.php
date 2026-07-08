<?php

namespace App\Filament\Pages;

use App\Mail\AtaEmail;
use App\Models\Ata;
use App\Models\AtaAnexo;
use App\Models\AtaPautaModelo;
use App\Models\Projeto;
use App\Models\User;
use App\Traits\HasMenuPermission;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;

class AtasPage extends Page
{
    use HasMenuPermission;
    use WithFileUploads;

    protected string $view = 'filament.pages.atas-page';

    protected static ?string $navigationLabel = 'Atas';

    protected static \UnitEnum|string|null $navigationGroup = null;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 15;

    protected static ?string $title = 'Atas de Reunião';

    protected static function menuPermission(): string
    {
        return 'View:MenuAtas';
    }

    // ─── Drill-down ──────────────────────────────────────────────────────────
    public ?int $projetoId = null;
    public ?string $projetoNome = null;

    // ─── Modais ──────────────────────────────────────────────────────────────
    public bool $modalFormAberto = false;
    public bool $modalDetalheAberto = false;
    public bool $modalTarefaAberto = false;

    // ─── Tarefa a partir de tema ─────────────────────────────────────────────
    public int $tarefaTemaIndex = -1;
    public string $tarefaTitulo = '';
    public string $tarefaDescricao = '';
    public ?int $tarefaResponsavelId = null;
    public ?int $tarefaCategoriaId = null;
    public string $tarefaInicio = '';
    public string $tarefaPrazo = '';
    public string $tarefaDuracao = '';

    // ─── IDs ativos ──────────────────────────────────────────────────────────
    public ?int $editandoId = null;
    public ?int $detalheId = null;

    // ─── Campos do formulário ────────────────────────────────────────────────
    public ?int $formProjetoId = null;
    public string $formTitulo = '';
    public string $formDataReuniao = '';
    public string $formHoraInicio = '';
    public string $formHoraFim = '';
    public string $formLocal = '';
    public string $formResumo = '';
    public string $formLinkYoutube = '';
    public array $formParticipantes = [];
    public array $formTemas = [];
    public array $formAnexosNovos = [];
    public $uploadBatchGeral = [];
    public $uploadBatchTema = [];
    public ?int $uploadBatchTemaIndex = null;
    public $formTemasAnexos = [];

    // ─── Navegação ───────────────────────────────────────────────────────────

    public function selecionarProjeto(int $id, string $nome): void
    {
        $this->projetoId   = $id;
        $this->projetoNome = $nome;
        $this->fecharTudo();
    }

    public function voltar(): void
    {
        $this->projetoId   = null;
        $this->projetoNome = null;
        $this->fecharTudo();
    }

    // ─── Dados ───────────────────────────────────────────────────────────────

    public function getProjetos(): Collection
    {
        return Projeto::query()
            ->withCount('atas')
            ->withMax('atas', 'data_reuniao')
            ->orderBy('nome')
            ->get();
    }

    public function getAtasDoProjeto(): Collection
    {
        if (! $this->projetoId) {
            return collect();
        }

        return Ata::where('projeto_id', $this->projetoId)
            ->orderByDesc('data_reuniao')
            ->orderByDesc('hora_inicio')
            ->get();
    }

    public function getAtaDetalhe(): ?Ata
    {
        if (! $this->detalheId) {
            return null;
        }

        return Ata::with(['projeto', 'participantes.user', 'temas.anexos', 'criador', 'anexos' => fn ($q) => $q->whereNull('tema_id')])
            ->find($this->detalheId);
    }

    public function getAnexosPorTema(): array
    {
        if (! $this->editandoId) {
            return [];
        }
        $result = [];
        foreach ($this->formTemas as $i => $t) {
            if (empty($t['id'])) {
                continue;
            }
            $result[$i] = AtaAnexo::where('tema_id', (int) $t['id'])->orderBy('ordem')->get();
        }
        return $result;
    }

    public function getProjetosSelect(): array
    {
        return Projeto::orderBy('nome')->pluck('nome', 'id')->toArray();
    }

    public function getUsuariosSelect(): array
    {
        return User::orderBy('name')->pluck('name', 'id')->toArray();
    }

    public function getAnexosEditando(): Collection
    {
        if (! $this->editandoId) {
            return collect();
        }
        return AtaAnexo::where('ata_id', $this->editandoId)->orderBy('ordem')->get();
    }

    // ─── Modal: Detalhe ──────────────────────────────────────────────────────

    public function abrirDetalhe(int $id): void
    {
        $this->detalheId          = $id;
        $this->modalDetalheAberto = true;
    }

    public function fecharDetalhe(): void
    {
        $this->detalheId          = null;
        $this->modalDetalheAberto = false;
    }

    // ─── Modal: Formulário ───────────────────────────────────────────────────

    public function novaAta(): void
    {
        $this->editandoId        = null;
        $this->formProjetoId     = $this->projetoId;
        $this->formTitulo        = '';
        $this->formDataReuniao   = now()->format('Y-m-d');
        $this->formHoraInicio    = '';
        $this->formHoraFim       = '';
        $this->formLocal         = '';
        $this->formResumo        = '';
        $this->formLinkYoutube   = '';
        $this->formParticipantes = [];
        $this->formTemas         = [];
        $this->formAnexosNovos      = [];
        $this->uploadBatchGeral     = [];
        $this->uploadBatchTema      = [];
        $this->uploadBatchTemaIndex = null;
        $this->formTemasAnexos      = [];
        $this->resetValidation();
        $this->modalFormAberto   = true;
    }

    public function editarAta(int $id): void
    {
        $ata = Ata::with(['participantes', 'temas'])->findOrFail($id);

        $this->editandoId      = $id;
        $this->formProjetoId   = $ata->projeto_id;
        $this->formTitulo      = $ata->titulo;
        $this->formDataReuniao = $ata->data_reuniao?->format('Y-m-d') ?? '';
        $this->formHoraInicio  = substr($ata->hora_inicio ?? '', 0, 5);
        $this->formHoraFim     = substr($ata->hora_fim ?? '', 0, 5);
        $this->formLocal       = $ata->local ?? '';
        $this->formResumo      = $ata->resumo ?? '';
        $this->formLinkYoutube = $ata->link_youtube ?? '';
        $this->formParticipantes = $ata->participantes
            ->map(fn ($p) => [
                'user_id' => $p->user_id ? (string) $p->user_id : '',
                'nome'    => $p->nome,
                'empresa' => $p->empresa ?? '',
                'cargo'   => $p->cargo ?? '',
                'email'   => $p->email ?? '',
            ])
            ->toArray();
        $this->formTemas = $ata->temas
            ->map(fn ($t) => ['id' => $t->id, 'titulo' => $t->titulo, 'descricao' => $t->descricao ?? ''])
            ->values()
            ->toArray();
        $this->formAnexosNovos      = [];
        $this->uploadBatchGeral     = [];
        $this->uploadBatchTema      = [];
        $this->uploadBatchTemaIndex = null;
        $this->formTemasAnexos      = [];

        $this->resetValidation();
        $this->modalFormAberto    = true;
        $this->modalDetalheAberto = false;
    }

    public function fecharModal(): void
    {
        $this->modalFormAberto      = false;
        $this->editandoId           = null;
        $this->formAnexosNovos      = [];
        $this->uploadBatchGeral     = [];
        $this->uploadBatchTema      = [];
        $this->uploadBatchTemaIndex = null;
        $this->formTemasAnexos      = [];
    }

    // ─── Participantes ───────────────────────────────────────────────────────

    public function adicionarParticipante(): void
    {
        $this->formParticipantes[] = ['user_id' => '', 'nome' => '', 'empresa' => '', 'cargo' => '', 'email' => ''];
    }

    public function removerParticipante(int $index): void
    {
        array_splice($this->formParticipantes, $index, 1);
        $this->formParticipantes = array_values($this->formParticipantes);
    }

    public function selecionarUsuario(int $index, string $userId): void
    {
        if ($userId === '') {
            $this->formParticipantes[$index]['user_id'] = '';
            return;
        }

        $user = User::find((int) $userId);
        if ($user) {
            $this->formParticipantes[$index]['user_id'] = (string) $user->id;
            $this->formParticipantes[$index]['nome']    = $user->name;
            $this->formParticipantes[$index]['email']   = $user->email;
        }
    }

    // ─── Temas ───────────────────────────────────────────────────────────────

    public function adicionarTema(): void
    {
        $this->formTemas[] = ['id' => null, 'titulo' => '', 'descricao' => ''];
    }

    public function removerTema(int $index): void
    {
        array_splice($this->formTemas, $index, 1);
        $this->formTemas = array_values($this->formTemas);
    }

    // ─── Upload hooks (acumulam sem sobrescrever) ─────────────────────────────

    public function updatedUploadBatchGeral(): void
    {
        $incoming = is_array($this->uploadBatchGeral) ? $this->uploadBatchGeral : [$this->uploadBatchGeral];
        $this->formAnexosNovos = array_values(array_filter(array_merge($this->formAnexosNovos, $incoming)));
        $this->uploadBatchGeral = [];
    }

    public function updatedUploadBatchTema(): void
    {
        if ($this->uploadBatchTemaIndex === null) {
            return;
        }
        $idx      = $this->uploadBatchTemaIndex;
        $incoming = is_array($this->uploadBatchTema) ? $this->uploadBatchTema : [$this->uploadBatchTema];
        $existing = isset($this->formTemasAnexos[$idx]) && is_array($this->formTemasAnexos[$idx])
            ? $this->formTemasAnexos[$idx]
            : [];
        $this->formTemasAnexos[$idx] = array_values(array_filter(array_merge($existing, $incoming)));
        $this->uploadBatchTema       = [];
    }

    public function setUploadTemaIndex(int $index): void
    {
        $this->uploadBatchTemaIndex = $index;
    }

    // ─── Anexos ──────────────────────────────────────────────────────────────

    public function removerAnexo(int $id): void
    {
        $anexo = AtaAnexo::findOrFail($id);
        Storage::disk('public')->delete($anexo->caminho);
        $anexo->delete();
    }

    // ─── CRUD ────────────────────────────────────────────────────────────────

    public function salvar(): void
    {
        $this->validate([
            'formProjetoId'              => 'required|exists:projetos,id',
            'formTitulo'                 => 'required|string|max:255',
            'formDataReuniao'            => 'required|date',
            'formHoraInicio'             => 'nullable|date_format:H:i',
            'formHoraFim'                => 'nullable|date_format:H:i',
            'formLocal'                  => 'nullable|string|max:255',
            'formResumo'                 => 'nullable|string',
            'formLinkYoutube'            => 'nullable|url|max:500',
            'formParticipantes.*.nome'   => 'required|string|max:255',
            'formTemas.*.titulo'         => 'required|string|max:255',
            'formAnexosNovos.*'          => 'nullable|file|max:10240|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx',
        ], [
            'formProjetoId.required'            => 'Selecione um projeto.',
            'formTitulo.required'               => 'Informe o tema da reunião.',
            'formDataReuniao.required'          => 'Informe a data da reunião.',
            'formHoraInicio.date_format'        => 'Formato inválido. Use HH:MM.',
            'formHoraFim.date_format'           => 'Formato inválido. Use HH:MM.',
            'formLinkYoutube.url'               => 'Informe uma URL válida.',
            'formParticipantes.*.nome.required' => 'Informe o nome do participante.',
            'formTemas.*.titulo.required'       => 'Informe o título do tema.',
            'formAnexosNovos.*.max'             => 'Cada arquivo pode ter no máximo 10 MB.',
        ]);

        $dados = [
            'projeto_id'   => $this->formProjetoId,
            'titulo'       => $this->formTitulo,
            'data_reuniao' => $this->formDataReuniao,
            'hora_inicio'  => $this->formHoraInicio ?: null,
            'hora_fim'     => $this->formHoraFim ?: null,
            'local'        => $this->formLocal ?: null,
            'resumo'       => $this->formResumo ?: null,
            'link_youtube' => $this->formLinkYoutube ?: null,
        ];

        if ($this->editandoId) {
            $ata = Ata::findOrFail($this->editandoId);
            $ata->update($dados);
        } else {
            $dados['criado_por'] = auth()->id();
            $ata = Ata::create($dados);
        }

        // Participantes
        $ata->participantes()->delete();
        foreach ($this->formParticipantes as $p) {
            if (trim($p['nome'] ?? '')) {
                $ata->participantes()->create([
                    'user_id' => ($p['user_id'] ?? '') !== '' ? (int) $p['user_id'] : null,
                    'nome'    => $p['nome'],
                    'empresa' => $p['empresa'] ?: null,
                    'cargo'   => $p['cargo'] ?: null,
                    'email'   => $p['email'] ?: null,
                ]);
            }
        }

        // Temas — update-in-place para preservar anexos dos temas
        $temaIdPorIndex = [];
        if ($this->editandoId) {
            $idsNoForm = collect($this->formTemas)->pluck('id')->filter()->map(fn ($id) => (int) $id)->toArray();
            $ata->temas()->whereNotIn('id', $idsNoForm)->delete();

            foreach ($this->formTemas as $i => $t) {
                if (! trim($t['titulo'] ?? '')) {
                    continue;
                }
                $temaId = isset($t['id']) && $t['id'] ? (int) $t['id'] : null;
                if ($temaId) {
                    $tema = $ata->temas()->find($temaId);
                    if ($tema) {
                        $tema->update(['titulo' => $t['titulo'], 'descricao' => $t['descricao'] ?: null, 'ordem' => $i]);
                        $temaIdPorIndex[$i] = $temaId;
                    }
                } else {
                    $tema = $ata->temas()->create(['titulo' => $t['titulo'], 'descricao' => $t['descricao'] ?: null, 'ordem' => $i]);
                    $temaIdPorIndex[$i] = $tema->id;
                }
            }
        } else {
            foreach ($this->formTemas as $i => $t) {
                if (! trim($t['titulo'] ?? '')) {
                    continue;
                }
                $tema = $ata->temas()->create(['titulo' => $t['titulo'], 'descricao' => $t['descricao'] ?: null, 'ordem' => $i]);
                $temaIdPorIndex[$i] = $tema->id;
            }
        }

        // Anexos gerais novos (sem tema)
        $ordemBase = $ata->anexos()->whereNull('tema_id')->count();
        foreach ($this->formAnexosNovos as $i => $file) {
            if (! $file) {
                continue;
            }
            $path = $file->store("atas/{$ata->id}", 'public');
            AtaAnexo::create([
                'ata_id'        => $ata->id,
                'tema_id'       => null,
                'nome_original' => $file->getClientOriginalName(),
                'caminho'       => $path,
                'mime_type'     => $file->getMimeType(),
                'tamanho'       => $file->getSize(),
                'ordem'         => $ordemBase + $i,
            ]);
        }

        // Anexos por tema
        foreach ($this->formTemasAnexos as $temaIndex => $files) {
            if (! isset($temaIdPorIndex[$temaIndex]) || ! $files) {
                continue;
            }
            $temaId    = $temaIdPorIndex[$temaIndex];
            $ordemTema = AtaAnexo::where('tema_id', $temaId)->count();
            foreach ((array) $files as $j => $file) {
                if (! $file) {
                    continue;
                }
                $path = $file->store("atas/{$ata->id}", 'public');
                AtaAnexo::create([
                    'ata_id'        => $ata->id,
                    'tema_id'       => $temaId,
                    'nome_original' => $file->getClientOriginalName(),
                    'caminho'       => $path,
                    'mime_type'     => $file->getMimeType(),
                    'tamanho'       => $file->getSize(),
                    'ordem'         => $ordemTema + $j,
                ]);
            }
        }

        $this->registrarPauta($this->formTitulo);
        $this->fecharModal();

        Notification::make()
            ->title($this->editandoId ? 'Ata atualizada com sucesso' : 'Ata criada com sucesso')
            ->success()
            ->send();
    }

    public function deletarAta(int $id): void
    {
        $ata = Ata::with('anexos')->findOrFail($id);

        foreach ($ata->anexos as $anexo) {
            Storage::disk('public')->delete($anexo->caminho);
        }

        $ata->delete();

        Notification::make()->title('Ata removida')->success()->send();
    }

    // ─── PDF ─────────────────────────────────────────────────────────────────

    public function gerarPdf(int $id)
    {
        $ata = Ata::with(['projeto', 'participantes.user', 'temas', 'criador', 'anexos'])
            ->findOrFail($id);

        $pdf = Pdf::loadView('pdf.ata', ['ata' => $ata])
            ->setPaper('a4', 'portrait')
            ->setOption('isPhpEnabled', true);

        $filename = 'ata-' . $ata->data_reuniao->format('Y-m-d') . '-' . Str::slug($ata->titulo) . '.pdf';

        return response()->streamDownload(
            fn () => print($pdf->output()),
            $filename,
            ['Content-Type' => 'application/pdf']
        );
    }

    // ─── Email ───────────────────────────────────────────────────────────────

    public function enviarPorEmail(int $id): void
    {
        $ata = Ata::with(['projeto', 'participantes.user', 'temas', 'criador', 'anexos'])
            ->findOrFail($id);

        $emails = $ata->participantes
            ->map(fn ($p) => $p->email ?: $p->user?->email)
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        if (empty($emails)) {
            Notification::make()
                ->title('Nenhum participante com email cadastrado')
                ->warning()
                ->send();
            return;
        }

        foreach ($emails as $email) {
            Mail::to($email)->send(new AtaEmail($ata));
        }

        Notification::make()
            ->title('Ata enviada para ' . count($emails) . ' participante(s)')
            ->success()
            ->send();
    }

    // ─── Pautas modelo ───────────────────────────────────────────────────────

    public function getPautaModelos(): array
    {
        return AtaPautaModelo::orderByDesc('uso')->orderBy('titulo')->pluck('titulo')->toArray();
    }

    private function registrarPauta(string $titulo): void
    {
        $titulo = trim($titulo);
        if ($titulo === '') {
            return;
        }
        $modelo = AtaPautaModelo::firstOrCreate(['titulo' => $titulo], ['uso' => 0]);
        $modelo->increment('uso');
    }

    // ─── Horários ────────────────────────────────────────────────────────────

    public function getHorarios(): array
    {
        $horarios = [];
        for ($i = 6 * 2; $i <= 22 * 2; $i++) {
            $h = intdiv($i, 2);
            $m = ($i % 2) * 30;
            $horarios[] = sprintf('%02d:%02d', $h, $m);
        }
        return $horarios;
    }

    // ─── Tarefa a partir de tema ─────────────────────────────────────────────

    public function getCategoriasSelect(): array
    {
        return \App\Models\TaskCategory::orderBy('name')->pluck('name', 'id')->toArray();
    }

    public function abrirModalTarefa(int $index): void
    {
        $this->tarefaTemaIndex     = $index;
        $this->tarefaTitulo        = $this->formTemas[$index]['titulo'] ?? '';
        $this->tarefaDescricao     = $this->formTemas[$index]['descricao'] ?? '';
        $this->tarefaResponsavelId = null;
        $this->tarefaCategoriaId   = \App\Models\TaskCategory::first()?->id;
        $this->tarefaInicio        = '';
        $this->tarefaPrazo         = '';
        $this->tarefaDuracao       = '';
        $this->modalTarefaAberto   = true;
    }

    public function fecharModalTarefa(): void
    {
        $this->modalTarefaAberto = false;
    }

    public function criarTarefaDeTema(): void
    {
        $this->validate([
            'tarefaTitulo'     => 'required|string|max:255',
            'tarefaCategoriaId' => 'required|exists:task_categories,id',
            'tarefaDuracao'    => 'nullable|integer|min:1|max:9999',
        ], [
            'tarefaTitulo.required'      => 'Informe o nome da tarefa.',
            'tarefaCategoriaId.required' => 'Selecione a categoria.',
            'tarefaDuracao.integer'      => 'Duração deve ser em dias (número inteiro).',
        ]);

        // Calcular termino_programado: prazo manual ou início + duração
        $terminoProgramado = $this->tarefaPrazo ?: null;
        if (! $terminoProgramado && $this->tarefaInicio && $this->tarefaDuracao) {
            $terminoProgramado = \Carbon\Carbon::parse($this->tarefaInicio)
                ->addDays((int) $this->tarefaDuracao)
                ->format('Y-m-d');
        }

        \App\Models\Task::create([
            'title'              => $this->tarefaTitulo,
            'description'        => $this->tarefaDescricao ?: null,
            'task_category_id'   => $this->tarefaCategoriaId,
            'projeto_id'         => $this->formProjetoId,
            'assigned_to'        => $this->tarefaResponsavelId ?: null,
            'inicio'             => $this->tarefaInicio ?: null,
            'termino_programado' => $terminoProgramado,
            'status'             => 'pendente',
            'created_by'         => auth()->id(),
        ]);

        $this->modalTarefaAberto = false;

        Notification::make()
            ->title('Tarefa criada com sucesso')
            ->success()
            ->send();
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function fecharTudo(): void
    {
        $this->modalFormAberto    = false;
        $this->modalDetalheAberto = false;
        $this->modalTarefaAberto  = false;
        $this->editandoId         = null;
        $this->detalheId          = null;
    }
}
