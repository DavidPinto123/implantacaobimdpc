<?php

namespace App\Models\PosObra;

use Illuminate\Database\Eloquent\Model;

class WhatsappConfig extends Model
{
    protected $table = 'po_whatsapp_config';

    protected $fillable = [
        'phone_number_id',
        'token',
        'verify_token',
        'ativo',
    ];

    protected $hidden = [
        'token',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'token' => 'encrypted',
    ];

    public static function instancia(): ?static
    {
        return static::first();
    }
}
