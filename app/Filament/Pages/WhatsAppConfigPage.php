<?php

namespace App\Filament\Pages;

use App\Models\PosObra\WhatsappConfig;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class WhatsAppConfigPage extends Page
{
    use HasPageShield;

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.whats-app-config-page';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ChatBubbleLeftRight;

    protected static UnitEnum|string|null $navigationGroup = 'WhatsApp';

    protected static ?string $navigationLabel = 'Configuração';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Configuração WhatsApp';

    public ?string $phone_number_id = null;

    public ?string $token = null;

    public ?string $verify_token = null;

    public bool $ativo = false;

    public string $webhook_url = '';

    public function mount(): void
    {
        $this->webhook_url = route('whatsapp.webhook.receive');

        $config = WhatsappConfig::instancia();

        if ($config) {
            $this->phone_number_id = $config->phone_number_id;
            $this->verify_token = $config->verify_token;
            $this->ativo = (bool) $config->ativo;
            // token não é preenchido por segurança (encrypted + hidden)
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Credenciais Meta / WhatsApp Cloud API')->schema([
                TextInput::make('phone_number_id')
                    ->label('Phone Number ID')
                    ->required()
                    ->maxLength(100)
                    ->helperText('Encontrado no painel Meta for Developers → WhatsApp → Getting Started'),

                TextInput::make('token')
                    ->label('Token de Acesso Permanente')
                    ->password()
                    ->revealable()
                    ->maxLength(500)
                    ->helperText('Deixe em branco para manter o token atual'),

                TextInput::make('verify_token')
                    ->label('Verify Token (Webhook)')
                    ->required()
                    ->maxLength(100)
                    ->helperText('String secreta configurada no webhook Meta — deve coincidir com o endpoint'),

                Toggle::make('ativo')
                    ->label('Integração ativa')
                    ->helperText('Desative para suspender o envio de mensagens sem remover a configuração'),
            ])->columns(2),

            Section::make('URL do Webhook')->schema([
                TextInput::make('webhook_url')
                    ->label('URL do Webhook')
                    ->readOnly()
                    ->copyable('URL copiada!')
                    ->helperText('Configure esta URL no painel Meta for Developers → Webhooks → Callback URL'),
            ]),
        ]);
    }

    public function salvar(): void
    {
        $data = $this->form->getState();

        $config = WhatsappConfig::instancia();

        $payload = [
            'phone_number_id' => $data['phone_number_id'],
            'verify_token' => $data['verify_token'],
            'ativo' => $data['ativo'],
        ];

        if (filled($data['token'])) {
            $payload['token'] = $data['token'];
        }

        if ($config) {
            $config->update($payload);
        } else {
            WhatsappConfig::create($payload);
        }

        Notification::make()
            ->title('Configuração salva com sucesso')
            ->success()
            ->send();
    }
}
