<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Resumo Semanal de Planejamentos</title>
<style>
  body { margin: 0; padding: 0; background: #f4f4f5; font-family: Arial, sans-serif; color: #18181b; }
  .wrap { max-width: 680px; margin: 32px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 6px rgba(0,0,0,.08); }
  .header { background: #1e3a5f; padding: 32px 40px; }
  .header h1 { margin: 0; color: #fff; font-size: 20px; font-weight: 700; letter-spacing: .3px; }
  .header p { margin: 6px 0 0; color: #93c5fd; font-size: 13px; }
  .body { padding: 32px 40px; }
  .greeting { font-size: 15px; margin-bottom: 24px; }
  .section-title { font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: .8px; color: #6b7280; margin: 28px 0 10px; border-bottom: 1px solid #e5e7eb; padding-bottom: 6px; }
  table { width: 100%; border-collapse: collapse; font-size: 13px; }
  th { text-align: left; background: #f9fafb; padding: 8px 10px; color: #374151; font-weight: 600; border-bottom: 2px solid #e5e7eb; }
  td { padding: 8px 10px; border-bottom: 1px solid #f3f4f6; vertical-align: top; }
  tr:last-child td { border-bottom: none; }
  .badge { display: inline-block; padding: 2px 8px; border-radius: 20px; font-size: 11px; font-weight: 600; }
  .badge-green { background: #d1fae5; color: #065f46; }
  .badge-blue  { background: #dbeafe; color: #1e40af; }
  .badge-gray  { background: #f3f4f6; color: #6b7280; }
  .empty { color: #9ca3af; font-style: italic; font-size: 13px; padding: 12px 0; }
  .footer { background: #f9fafb; padding: 20px 40px; border-top: 1px solid #e5e7eb; font-size: 12px; color: #9ca3af; }
  .footer a { color: #6b7280; }
</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <h1>Resumo Semanal de Planejamentos</h1>
    <p>{{ $labelSemanaAtual }}</p>
  </div>

  <div class="body">
    <p class="greeting">Olá, <strong>{{ $gerente->name }}</strong>.</p>
    <p style="font-size:14px;color:#4b5563;margin-top:0;">
      Segue o resumo das atividades dos seus planejamentos para a semana.
    </p>

    {{-- SEMANA ANTERIOR --}}
    <div class="section-title">Semana anterior &mdash; {{ $labelSemanaAnterior }}<br>
      <span style="font-weight:400;color:#9ca3af;font-size:11px;text-transform:none;">Itens concluídos</span>
    </div>

    @if(count($semanaAnterior) > 0)
    <table>
      <thead>
        <tr>
          <th>Planejamento</th>
          <th>Fase</th>
          <th>Item</th>
          <th>Responsável(is)</th>
          <th>Concluído em</th>
        </tr>
      </thead>
      <tbody>
        @foreach($semanaAnterior as $row)
        <tr>
          <td>{{ $row['projeto'] }}</td>
          <td>{{ $row['fase'] }}</td>
          <td>{{ $row['item'] }}</td>
          <td>{{ $row['responsaveis'] }}</td>
          <td><span class="badge badge-green">{{ $row['data'] }}</span></td>
        </tr>
        @endforeach
      </tbody>
    </table>
    @else
    <p class="empty">Nenhum item foi concluído na semana anterior.</p>
    @endif

    {{-- SEMANA ATUAL --}}
    <div class="section-title">Semana atual &mdash; {{ $labelSemanaAtual }}<br>
      <span style="font-weight:400;color:#9ca3af;font-size:11px;text-transform:none;">Itens previstos</span>
    </div>

    @if(count($semanaAtual) > 0)
    <table>
      <thead>
        <tr>
          <th>Planejamento</th>
          <th>Fase</th>
          <th>Item</th>
          <th>Responsável(is)</th>
          <th>Previsão</th>
        </tr>
      </thead>
      <tbody>
        @foreach($semanaAtual as $row)
        <tr>
          <td>{{ $row['projeto'] }}</td>
          <td>{{ $row['fase'] }}</td>
          <td>{{ $row['item'] }}</td>
          <td>{{ $row['responsaveis'] }}</td>
          <td><span class="badge badge-blue">{{ $row['data'] }}</span></td>
        </tr>
        @endforeach
      </tbody>
    </table>
    @else
    <p class="empty">Nenhum item previsto para esta semana.</p>
    @endif

    <p style="font-size:13px;color:#6b7280;margin-top:32px;">
      Para ver detalhes completos, acesse o sistema de planejamento.
    </p>
  </div>

  <div class="footer">
    Este e-mail é enviado automaticamente toda segunda-feira.
    Não responda a este e-mail.
  </div>
</div>
</body>
</html>
