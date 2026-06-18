<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappTemplateConfig extends Model
{
    protected $table = 'whatsapp_templates_config';

    protected $primaryKey = 'template_key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['template_key', 'ativo'];

    protected $casts = ['ativo' => 'boolean'];

    public static function isAtivo(string $key): bool
    {
        return static::where('template_key', $key)->value('ativo') ?? true;
    }

    public static function setAtivo(string $key, bool $valor): void
    {
        static::updateOrCreate(
            ['template_key' => $key],
            ['ativo' => $valor]
        );
    }
}
