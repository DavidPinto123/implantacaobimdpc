<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappTaskContext extends Model
{
    protected $table = 'whatsapp_task_contexts';

    protected $fillable = ['phone', 'task_id', 'task_title', 'expires_at', 'replied_at'];

    protected $casts = [
        'expires_at'  => 'datetime',
        'replied_at'  => 'datetime',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public static function ativo(string $phone): ?self
    {
        return static::where('phone', $phone)
            ->where('expires_at', '>', now())
            ->whereNull('replied_at')
            ->latest()
            ->first();
    }

    public function marcarRespondido(): void
    {
        $this->update(['replied_at' => now()]);
    }
}
