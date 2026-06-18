<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappSubscricao extends Model
{
    protected $table = 'whatsapp_subscricoes';

    protected $fillable = ['user_id', 'template_key'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function assinadosPor(string $key): \Illuminate\Support\Collection
    {
        return static::where('template_key', $key)->pluck('user_id');
    }
}
