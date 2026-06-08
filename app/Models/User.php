<?php

namespace App\Models;

use App\Models\PosObra\Pendencia;
use App\Notifications\UserEmailVerificationNotification;
use App\Notifications\UserPasswordResetNotification;
use Database\Factories\UserFactory;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Auth\Passwords\CanResetPassword as CanResetPasswordTrait;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\HasDatabaseNotifications;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements CanResetPasswordContract, FilamentUser, HasAvatar, MustVerifyEmailContract
{
    use CanResetPasswordTrait;
    use HasDatabaseNotifications;

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    use HasRoles;
    use MustVerifyEmailTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'pais_id',
        'estado_id',
        'cidade_id',
        'is_active',
        'is_fornecedor',
        'is_lider_obra',
        'construtoras_id',
        'foto_perfil',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_fornecedor' => 'boolean',
        'is_lider_obra' => 'boolean',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function projetos()
    {
        return $this->belongsToMany(Projeto::class, 'projeto_user');
    }

    public function relatoriosCriados()
    {
        return $this->hasMany(RelatorioFotografico::class, 'autor_id');
    }

    public function relatoriosComoGestor()
    {
        return $this->hasMany(RelatorioFotografico::class, 'gestor_id');
    }

    public function pais()
    {

        return $this->belongsTo(Pais::class);
    }

    public function estado()
    {

        return $this->belongsTo(Estado::class);
    }

    public function cidade()
    {

        return $this->belongsTo(Cidade::class);
    }

    public function setores()
    {
        return $this->belongsToMany(Setor::class, 'setor_user');
    }

    public function construtora()
    {
        return $this->belongsTo(Construtora::class, 'construtoras_id');
    }

    public function tarefasTemporarias()
    {
        return $this->belongsToMany(Task::class, 'task_user')
            ->withTimestamps();
    }

    public function tarefasComoResponsavelPrincipal(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    /*
    public function construtoras()
    {
      return $this->belongsTo(\App\Models\Construtora::class, 'construtoras_id');
    }
    */
    // Somente usuários ativos entram no painel Filament
    public function canAccessPanel(Panel $panel): bool
    {
        return (bool) $this->is_active;
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new UserPasswordResetNotification($token));
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new UserEmailVerificationNotification);
    }

    public function getFilamentAvatarUrl(): ?string
    {
        if (filled($this->foto_perfil)) {
            /** @var FilesystemAdapter $disk */
            $disk = Storage::disk((string) config('filesystems.media_disk', 'r2'));

            return $disk->url($this->foto_perfil);
        }

        // Retornando null, o Filament usa ui-avatars automaticamente
        return null;
    }

    public function isCoordenador(): bool
    {
        return $this->hasAnyRole(['Coordenador', 'Gestor', 'Diretor']);
    }

    /**
     * IDs de Gestores que compartilham pelo menos um setor com este usuário (geralmente um Coordenador).
     *
     * @return array<int, int>
     */
    public function gestoresSubordinadosIds(): array
    {
        $setorIds = $this->setores()->pluck('setores.id')->all();

        if (empty($setorIds)) {
            return [];
        }

        return User::query()
            ->role('Gestor')
            ->whereHas('setores', fn ($q) => $q->whereIn('setores.id', $setorIds))
            ->pluck('id')
            ->all();
    }

    /**
     * IDs de usuários visíveis na Agenda Geral para este usuário,
     * considerando a hierarquia role+setor.
     *
     * - super_admin / Diretor: null (sem filtro, vê tudo)
     * - Coordenador: gestores do(s) mesmo(s) setor(es) + ele mesmo
     * - Outros: apenas o próprio id
     *
     * @return array<int, int>|null
     */
    public function agendaUsuariosVisiveisIds(): ?array
    {
        if ($this->hasAnyRole(['super_admin', 'Diretor'])) {
            return null;
        }

        if ($this->hasRole('Coordenador')) {
            return array_values(array_unique(array_merge(
                [$this->id],
                $this->gestoresSubordinadosIds(),
            )));
        }

        return [$this->id];
    }

    public function primeiroSetor(): ?Setor
    {
        return $this->setores()->first();
    }

    /**
     * O Coordenador pode gerenciar tipos de evento do seu setor.
     */
    public function podeGerenciarTiposAgenda(): bool
    {
        return $this->hasRole('Coordenador') && $this->primeiroSetor() !== null;
    }

    /**
     * IDs dos setores cujos tipos de evento o usuário pode visualizar/usar.
     *
     * - super_admin / Diretor: null (vê todos os tipos cadastrados)
     * - Coordenador / Gestor / demais: apenas os setores aos quais pertence
     *
     * @return array<int, int>|null
     */
    public function setoresVisiveisIds(): ?array
    {
        if ($this->hasAnyRole(['super_admin', 'Diretor'])) {
            return null;
        }

        return $this->setores()->pluck('setores.id')->all();
    }

    public function pendenciasComoGestor()
    {
        return $this->hasMany(Pendencia::class, 'gestor_id');
    }

    public function pendenciasComoLider()
    {
        return $this->hasMany(Pendencia::class, 'lider_obra_id');
    }

    public function obrasComoLider()
    {
        return $this->belongsToMany(Obras::class, 'obra_user', 'user_id', 'obra_id');
    }

    public function agendaEventParticipations()
    {
        return $this->belongsToMany(AgendaEvent::class, 'agenda_event_participant', 'user_id', 'agenda_event_id')
            ->withPivot('status', 'responded_at')
            ->withTimestamps();
    }
    /*
    public function canAccessPanel(Panel $panel): bool {

      if ($panel->getId() === 'admin') {
        return $this->hasVerifiedEmail();
      }

      return $this->hasVerifiedEmail();

    }
      */
}
