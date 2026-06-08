<?php

namespace Database\Seeders;

use App\Enums\CategoriaAtualizacaoObra;
use App\Models\AtualizacaoObra;
use App\Models\Obras;
use App\Models\User;
use Illuminate\Database\Seeder;

class FeedObraExemploSeeder extends Seeder
{
    public function run(): void
    {
        $obras = Obras::all();
        $users = User::take(10)->pluck('id')->toArray();

        if (empty($users) || $obras->isEmpty()) {
            $this->command->warn('Nenhuma obra ou usuario encontrado.');

            return;
        }

        foreach ($obras as $obra) {
            $this->gerarAtualizacoesAutomaticas($obra, $users);
            $this->gerarComentariosManuais($obra, $users);
        }

        $this->command->info('Feed de exemplo populado com sucesso!');
    }

    private function gerarAtualizacoesAutomaticas(Obras $obra, array $users): void
    {
        $atualizacoes = [
            [
                'categoria' => CategoriaAtualizacaoObra::STATUS,
                'titulo' => "Status alterado de 'Em Projeto' para 'Em Obra'",
                'campo_alterado' => 'status',
                'valor_anterior' => 'Em Projeto',
                'valor_novo' => 'Em Obra',
                'dias_atras' => 45,
            ],
            [
                'categoria' => CategoriaAtualizacaoObra::PERCENTUAL,
                'titulo' => "Percentual da Obra alterado de '0' para '15%'",
                'campo_alterado' => 'percentual_obra',
                'valor_anterior' => '0',
                'valor_novo' => '15',
                'dias_atras' => 38,
            ],
            [
                'categoria' => CategoriaAtualizacaoObra::CIVIL,
                'titulo' => "Civil alterado de '(vazio)' para 'Fundação concluída'",
                'campo_alterado' => 'civil',
                'valor_anterior' => null,
                'valor_novo' => 'Fundação concluída',
                'dias_atras' => 35,
            ],
            [
                'categoria' => CategoriaAtualizacaoObra::ELETRICA,
                'titulo' => "Elétrica alterado de '(vazio)' para 'Infraestrutura iniciada'",
                'campo_alterado' => 'eletrica',
                'valor_anterior' => null,
                'valor_novo' => 'Infraestrutura iniciada',
                'dias_atras' => 30,
            ],
            [
                'categoria' => CategoriaAtualizacaoObra::HIDRAULICA,
                'titulo' => "Hidráulica alterado de '(vazio)' para 'Tubulação em andamento'",
                'campo_alterado' => 'hidraulica',
                'valor_anterior' => null,
                'valor_novo' => 'Tubulação em andamento',
                'dias_atras' => 28,
            ],
            [
                'categoria' => CategoriaAtualizacaoObra::PERCENTUAL,
                'titulo' => "Percentual da Obra alterado de '15%' para '45%'",
                'campo_alterado' => 'percentual_obra',
                'valor_anterior' => '15',
                'valor_novo' => '45',
                'dias_atras' => 22,
            ],
            [
                'categoria' => CategoriaAtualizacaoObra::CRONOGRAMA,
                'titulo' => "Cronograma Implantação alterado de '(vazio)' para 'Aprovado'",
                'campo_alterado' => 'cronograma_implantacao',
                'valor_anterior' => null,
                'valor_novo' => 'Aprovado',
                'dias_atras' => 18,
            ],
            [
                'categoria' => CategoriaAtualizacaoObra::ENERGIA,
                'titulo' => "Energia alterado de '(vazio)' para 'Solicitação enviada'",
                'campo_alterado' => 'energia',
                'valor_anterior' => null,
                'valor_novo' => 'Solicitação enviada',
                'dias_atras' => 15,
            ],
            [
                'categoria' => CategoriaAtualizacaoObra::CLIMATIZACAO,
                'titulo' => "Instalação Ar Condicionado alterado de '(vazio)' para 'Equipamento recebido'",
                'campo_alterado' => 'instalacao_ar_condicionado',
                'valor_anterior' => null,
                'valor_novo' => 'Equipamento recebido',
                'dias_atras' => 12,
            ],
            [
                'categoria' => CategoriaAtualizacaoObra::PERCENTUAL,
                'titulo' => "Percentual da Obra alterado de '45%' para '78%'",
                'campo_alterado' => 'percentual_obra',
                'valor_anterior' => '45',
                'valor_novo' => '78',
                'dias_atras' => 8,
            ],
            [
                'categoria' => CategoriaAtualizacaoObra::IMPLANTACAO,
                'titulo' => "Início Implantação alterado de '(vazio)' para '15/03/2026'",
                'campo_alterado' => 'inicio_imp',
                'valor_anterior' => null,
                'valor_novo' => '2026-03-15',
                'dias_atras' => 5,
            ],
            [
                'categoria' => CategoriaAtualizacaoObra::STATUS,
                'titulo' => "Status alterado de 'Em Obra' para 'Inaugurada'",
                'campo_alterado' => 'status',
                'valor_anterior' => 'Em Obra',
                'valor_novo' => 'Inaugurada',
                'dias_atras' => 2,
            ],
        ];

        foreach ($atualizacoes as $dados) {
            AtualizacaoObra::create([
                'obra_id' => $obra->id,
                'usuario_id' => $users[array_rand($users)],
                'categoria' => $dados['categoria'],
                'titulo' => $dados['titulo'],
                'campo_alterado' => $dados['campo_alterado'],
                'valor_anterior' => $dados['valor_anterior'],
                'valor_novo' => $dados['valor_novo'],
                'automatico' => true,
                'created_at' => now()->subDays($dados['dias_atras']),
                'updated_at' => now()->subDays($dados['dias_atras']),
            ]);
        }
    }

    private function gerarComentariosManuais(Obras $obra, array $users): void
    {
        $user1 = $users[0];
        $user2 = $users[1] ?? $users[0];
        $user3 = $users[2] ?? $users[0];

        $nomeUser2 = User::find($user2)?->name ?? 'admin';
        $nomeUser3 = User::find($user3)?->name ?? 'admin';

        // Comentário 1 — com resposta
        $c1 = AtualizacaoObra::create([
            'obra_id' => $obra->id,
            'usuario_id' => $user1,
            'categoria' => CategoriaAtualizacaoObra::COMENTARIO,
            'titulo' => 'Comentário',
            'conteudo' => "Pessoal, a fundação ficou excelente. Parabéns à equipe de campo! @{$nomeUser2} pode agendar a próxima vistoria?",
            'mencoes' => [$user2],
            'automatico' => false,
            'created_at' => now()->subDays(34),
            'updated_at' => now()->subDays(34),
        ]);

        AtualizacaoObra::create([
            'obra_id' => $obra->id,
            'usuario_id' => $user2,
            'parent_id' => $c1->id,
            'categoria' => CategoriaAtualizacaoObra::COMENTARIO,
            'titulo' => 'Resposta',
            'conteudo' => 'Já agendei para quinta-feira. Vou levar o checklist atualizado.',
            'automatico' => false,
            'created_at' => now()->subDays(33),
            'updated_at' => now()->subDays(33),
        ]);

        AtualizacaoObra::create([
            'obra_id' => $obra->id,
            'usuario_id' => $user1,
            'parent_id' => $c1->id,
            'categoria' => CategoriaAtualizacaoObra::COMENTARIO,
            'titulo' => 'Resposta',
            'conteudo' => 'Perfeito, obrigado!',
            'automatico' => false,
            'created_at' => now()->subDays(33),
            'updated_at' => now()->subDays(33),
        ]);

        // Comentário 2 — fixado
        AtualizacaoObra::create([
            'obra_id' => $obra->id,
            'usuario_id' => $user3,
            'categoria' => CategoriaAtualizacaoObra::COMENTARIO,
            'titulo' => 'Comentário',
            'conteudo' => 'ATENÇÃO: a concessionária informou que a ligação de energia será feita somente após regularização do padrão. Prazo estimado: 10 dias úteis.',
            'automatico' => false,
            'fixado' => true,
            'created_at' => now()->subDays(14),
            'updated_at' => now()->subDays(14),
        ]);

        // Comentário 3 — com menção e resposta
        $c3 = AtualizacaoObra::create([
            'obra_id' => $obra->id,
            'usuario_id' => $user2,
            'categoria' => CategoriaAtualizacaoObra::COMENTARIO,
            'titulo' => 'Comentário',
            'conteudo' => "Alguém sabe se o elevador já foi liberado? @{$nomeUser3} você tem essa informação?",
            'mencoes' => [$user3],
            'automatico' => false,
            'created_at' => now()->subDays(6),
            'updated_at' => now()->subDays(6),
        ]);

        AtualizacaoObra::create([
            'obra_id' => $obra->id,
            'usuario_id' => $user3,
            'parent_id' => $c3->id,
            'categoria' => CategoriaAtualizacaoObra::COMENTARIO,
            'titulo' => 'Resposta',
            'conteudo' => 'Sim, liberaram ontem. O técnico vem na segunda para a instalação final.',
            'automatico' => false,
            'created_at' => now()->subDays(5),
            'updated_at' => now()->subDays(5),
        ]);

        // Comentário 4 — recente
        AtualizacaoObra::create([
            'obra_id' => $obra->id,
            'usuario_id' => $user1,
            'categoria' => CategoriaAtualizacaoObra::COMENTARIO,
            'titulo' => 'Comentário',
            'conteudo' => 'Inauguração confirmada! Tudo certo para o dia 30. Equipe de marketing já foi notificada.',
            'automatico' => false,
            'created_at' => now()->subDays(1),
            'updated_at' => now()->subDays(1),
        ]);
    }
}
