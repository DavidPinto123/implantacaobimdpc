<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
    'aps' => [
        'client_id' => env('APS_CLIENT_ID'),
        'client_secret' => env('APS_CLIENT_SECRET'),
        'region' => env('APS_REGION', 'US'),
    ],

    'constructin' => [
        'key' => env('CONSTRUCTIN_API_KEY', ''),
        'secret' => env('CONSTRUCTIN_API_SECRET', ''),
    ],

    // Nomes dos templates aprovados no Meta Business Manager
    // Os credentials (phone_number_id + token) ficam na tabela po_whatsapp_config (via /admin/whatsapp-config)
    'whatsapp' => [
        'templates' => [
            'tarefa_atrasada'     => env('WHATSAPP_TEMPLATE_ATRASO',    'tarefa_atrasada'),
            'resumo_atrasos'      => env('WHATSAPP_TEMPLATE_RESUMO_ATRASOS', 'resumo_atrasos'),
            'status_tarefa'       => env('WHATSAPP_TEMPLATE_STATUS',     'status_tarefa'),
            'agenda_semanal'      => env('WHATSAPP_TEMPLATE_AGENDA',     'agenda_semanal'),
            'nova_tarefa'         => env('WHATSAPP_TEMPLATE_NOVA',       'nova_tarefa'),
            'prazo_proximo'       => env('WHATSAPP_TEMPLATE_PRAZO',      'prazo_proximo'),
            'tarefa_comentario'   => env('WHATSAPP_TEMPLATE_COMENTARIO', 'tarefa_comentario'),
            'cronograma_atualizado' => env('WHATSAPP_TEMPLATE_CRONO',   'cronograma_atualizado'),
            'gerente_notificacao' => env('WHATSAPP_TEMPLATE_GERENTE',    'gerente_notificacao'),
        ],
    ],

];
