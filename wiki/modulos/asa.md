# Módulo: ASA (Assessments)

Módulo de assessments/análises técnicas de projetos. ASA pode referir-se a "Análise de Superfície e Ambientes" ou similar.

## Models

### Asa
- **Arquivo**: `app/Models/Asa.php`
- Cabeçalho de um assessment

### AsaItem
- **Arquivo**: `app/Models/AsaItem.php`
- Itens individuais do assessment

### AsEscopo
- **Arquivo**: `app/Models/AsEscopo.php`
- Escopos do assessment
- Relacionamento: `HasMany: faixasArea`

### AsFaixaArea
- **Arquivo**: `app/Models/AsFaixaArea.php`
- Faixas de área para cálculo

## Service

### AsaService
- **Arquivo**: `app/Services/AsaService.php`
- Lógica de negócio dos assessments
- Cálculos de área, escopo e valores

## Filament Resources

### AsasResource
- **Pasta**: `app/Filament/Resources/AsasResource/`
- CRUD de assessments
- Contém: Pages, Schemas, Tables, Widgets próprios

### AsEscopjResource
- **Arquivo**: `app/Filament/Resources/AsEscopjResource.php`
- Gestão de escopos
- RelationManager: `FaixasAreaRelationManager`

### AsFaixaAreasResource
- **Arquivo**: `app/Filament/Resources/AsFaixaAreasResource.php`
- Gestão de faixas de área

## Seeder

### AsEscopoSeeder
- Popula escopos padrão do sistema

## Addendum / Aditivos

Relacionado ao módulo ASA:

### ElaboracaoAditivo
- **Arquivo**: `app/Models/ElaboracaoAditivo.php`
- Elaboração de aditivos contratuais

### ElaboracaoAditivoItem
- **Arquivo**: `app/Models/ElaboracaoAditivoItem.php`
- Itens do aditivo

### ElaboracaoAdditivosResource
- **Pasta**: `app/Filament/Resources/ElaboracaoAdditivosResource/`
- CRUD de aditivos

### Export
- `ElaboracaoAditivoPlanilhaExport` — exporta aditivos para planilha Excel
