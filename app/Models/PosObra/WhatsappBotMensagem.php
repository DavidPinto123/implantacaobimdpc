<?php

namespace App\Models\PosObra;

use Illuminate\Database\Eloquent\Model;

class WhatsappBotMensagem extends Model
{
    protected $table = 'po_whatsapp_bot_mensagens';

    protected $fillable = ['chave', 'texto'];

    const PADROES = [
        // Fluxo Líder
        'lider.inicio' => 'Olá! 👷 O que deseja fazer?',
        'lider.opcao_invalida' => 'Selecione uma opção do menu.',
        'lider.sem_obras' => 'Nenhuma obra ativa encontrada. Entre em contato com o gestor.',
        'lider.lista_obras_header' => 'Selecione a obra:',
        'lider.obra_invalida' => 'Obra não encontrada. Selecione da lista.',
        'lider.descricao' => 'Descreva o problema:',
        'lider.local' => 'Qual o local específico do problema?',
        'lider.urgencia' => 'Qual o nível de urgência?',
        'lider.urgencia_invalida' => 'Selecione uma urgência da lista.',
        'lider.impacto' => 'Há impacto operacional?',
        'lider.foto' => 'Envie uma foto do problema (ou responda "pular" para registrar sem foto).',
        'lider.pendencia_registrada' => "✅ Pendência *{codigo}* registrada!\nO fornecedor será notificado.",
        'lider.sem_pendencias' => 'Você não tem pendências abertas. ✅',
        'lider.lista_pendencias' => "Suas pendências abertas:\n{lista}",
        'lider.pedir_motivo_rejeicao' => 'Qual o motivo da rejeição?',
        'lider.pendencia_aprovada' => '✅ Pendência *{codigo}* aprovada e concluída!',
        'lider.pendencia_rejeitada' => '❌ Pendência *{codigo}* rejeitada. O fornecedor será notificado.',

        // Fluxo Construtora
        'fornecedor.aguardando_sistema' => 'Aguardando instruções do sistema.',
        'fornecedor.prazo_registrado' => 'Prazo registrado.',
        'fornecedor.aguarda_iniciar' => 'Clique no botão abaixo quando começar a execução.',
        'fornecedor.execucao_iniciada' => 'Execução registrada! Envie as fotos das evidências.',
        'fornecedor.foto_recebida' => 'Foto recebida. Envie mais fotos ou clique em Concluir.',
        'fornecedor.aprovacao_solicitada' => 'Solicitação de conclusão enviada ao líder.',
        'fornecedor.orientacao_evidencias' => 'Envie uma foto ou clique em Concluir para solicitar aprovação.',

        // Fluxo Gestor
        'gestor.redirect' => 'Acesse o painel web para gerenciar pendências: {url}',

        // Geral
        'geral.desconhecido' => 'Número não reconhecido no sistema.',

        // SLA (automático)
        'sla.escalamento' => "{prefixo} *SLA vencido*\nPendência: *{codigo}*\nObra: {sigla}\nUrgência: {urgencia}\nAtraso: {horas}h\nStatus: {status}",
    ];

    public static function get(string $chave): string
    {
        return static::where('chave', $chave)->value('texto') ?? (static::PADROES[$chave] ?? '');
    }

    public static function formatar(string $chave, array $vars = []): string
    {
        $texto = static::get($chave);

        foreach ($vars as $key => $value) {
            $texto = str_replace("{{$key}}", $value, $texto);
        }

        return $texto;
    }
}
