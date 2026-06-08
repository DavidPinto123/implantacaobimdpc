# Tabelas: Relatórios

## `relatorio_visita_tecnicas`

**Propósito**: Relatórios de visita técnica (RVT) gerados durante a fase de prospecção ou antes de obras. Formulário extenso com 200+ campos cobrindo elétrica, estrutura, hidráulica, arquitetura e fotos. Gera PDF via job assíncrono.
**Model**: `App\Models\RelatorioVisitaTecnica` (SoftDeletes, Observer: `RelatorioVisitaTecnicaObserver`)

### Identificação

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| projeto_id | bigint FK | sim | — | Projeto vinculado |
| marca_id | bigint FK | sim | — | Marca/bandeira da unidade |
| numero_relatorio_vt | string | sim | — | Número gerado automaticamente (formato: VT-001, VT-002…) |
| unidade | string | sim | — | Nome da unidade |
| unidade_relatorio | string | sim | — | Identificação no relatório |
| autor | string | sim | — | Nome do autor do relatório |
| endereco | string | sim | — | Endereço do imóvel |
| responsavel_tecnico | string | sim | — | Responsável técnico pela visita |
| prazo_de_obras | string | sim | — | Prazo estimado de obras |
| link_drive_fotos_e_videos | text | sim | — | Link para pasta de fotos/vídeos no Drive |
| status | string | não | 'Rascunho' | Status: `Rascunho`, `Concluído` |

### Datas e Controle

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| agendado_em | dateTime | sim | — | Data e hora agendada para a visita |
| iniciado_em | dateTime | sim | — | Quando o preenchimento foi iniciado |
| concluido_em | dateTime | sim | — | Quando o relatório foi concluído |
| sicronizado_em | timestamp | sim | — | Última sincronização |
| pdf_path | string | sim | — | Caminho do PDF gerado no storage |
| pdf_generated_at | timestamp | sim | — | Quando o PDF foi gerado |
| pdf_generating_at | timestamp | sim | — | Quando a geração foi iniciada (detecta timeouts) |

### Condições do Imóvel

| Coluna | Tipo | Nullable | Descrição |
|--------|------|----------|-----------|
| condicoes_imovel | text | sim | Descrição geral das condições do imóvel |
| pavimento | json | sim | Pavimentos do imóvel |
| empreendimento | string | sim | Tipo de empreendimento |
| locacao | string | sim | Tipo de locação |
| contato_responsavel | string | sim | Contato do responsável pelo imóvel |
| etapa_contrato | string | sim | Etapa atual do contrato |
| pavimento_outro | string | sim | Pavimento adicional (quando "outro" selecionado) |
| empreendimento_outro | string | sim | Empreendimento adicional |
| comentario_condicoes_imovel | text | sim | Comentário livre sobre as condições |
| validador_ticket_estacionamento | tinyInt | sim | Validador de ticket de estacionamento (booleano) |

### Elétrica / Energia

| Coluna | Tipo | Nullable | Descrição |
|--------|------|----------|-----------|
| entrada_de_energia | string | sim | Tipo de entrada de energia |
| necessario_visita_consultor_energia | tinyInt | sim | É necessário consultor de energia? |
| energia_provisoria | boolean | sim | Há energia provisória? |
| unica_medicao | boolean | sim | Medição única? |
| spda | boolean | sim | Possui SPDA (para-raios)? |
| energia_carga_superior_150 | boolean | sim | Carga superior a 150kVA? |
| cabos_alimentadores_shell | tinyInt | sim | Há cabos alimentadores no shell? |
| metros_cabeamento | decimal | sim | Metros de cabeamento necessários |

### Telefonia / Internet

| Coluna | Tipo | Nullable | Descrição |
|--------|------|----------|-----------|
| telegonia_dg | string | sim | Situação do DG de telefonia |
| descricao_telefonia | text | sim | Descrição da infraestrutura de telefonia |
| distancia_ponto_telefonia | decimal | sim | Distância até o ponto de telefonia (metros) |

### Gás

| Coluna | Tipo | Nullable | Descrição |
|--------|------|----------|-----------|
| rede_gas_disponivel | string | sim | Rede de gás disponível? |
| descricao_rede_gas_disponivel | text | sim | Descrição da rede de gás |
| distancia_rede_gas | decimal | sim | Distância até a rede de gás (metros) |

### Estrutura

| Coluna | Tipo | Nullable | Descrição |
|--------|------|----------|-----------|
| tipo_estrutura | string | sim | Tipo da estrutura (concreto, metálica, etc.) |
| tipo_estrutura_outro | string | sim | Tipo adicional |
| necessario_estrutura_auxiliar | boolean | sim | É necessária estrutura auxiliar? |
| descricao_estrutura_auxiliar | longText | sim | Descrição da estrutura auxiliar |
| estrutura_fachada | string | sim | Tipo de estrutura de fachada |
| permitidas_furacoes_laje | boolean | sim | Furação na laje é permitida? |
| sobrecarga_minima_laje | boolean | sim | Laje atende sobrecarga mínima (piso)? |
| sobrecarga_minima_laje_teto | boolean | sim | Laje atende sobrecarga mínima (teto)? |
| comprovacao_sobrecarga_laje | string | sim | Forma de comprovação de sobrecarga (piso) |
| comprovacao_sobrecarga_laje_teto | string | sim | Forma de comprovação de sobrecarga (teto) |

### Cobertura / Isolamento

| Coluna | Tipo | Nullable | Descrição |
|--------|------|----------|-----------|
| cobertura_isolamento | string | sim | Tipo de cobertura/isolamento |
| cobertura_vao_1_5 | boolean | sim | Cobertura tem vão livre ≥ 1,5m? |
| cobertura_vao_1_5_metragem | decimal | sim | Metragem do vão de cobertura |
| cobertura_area_isolamento | decimal | sim | Área de isolamento em m² |

### Alvenaria / Reboco

| Coluna | Tipo | Nullable | Descrição |
|--------|------|----------|-----------|
| alvenaria_periferia_existente | boolean | sim | Alvenaria de periferia existente? |
| reboco_interno_externo_existente | boolean | sim | Reboco interno/externo existente? |
| metros_alvenaria_periferia | decimal | sim | Metros lineares de alvenaria de periferia |
| metros_reboco | decimal | sim | Metros quadrados de reboco |

### Estanqueidade

| Coluna | Tipo | Nullable | Descrição |
|--------|------|----------|-----------|
| estanqueidade | boolean | sim | Há problema de estanqueidade? |
| estanqueidade_outro | longText | sim | Descrição adicional |
| descricao_complementar_estanqueidade | longText | sim | Descrição complementar |

### Hidráulica / Esgoto / Incêndio

| Coluna | Tipo | Nullable | Descrição |
|--------|------|----------|-----------|
| reservatorio_agua_existente | boolean | sim | Reservatório de água existente? |
| reservatorio_incendio_existente | boolean | sim | Reservatório de incêndio existente? |
| reservatorio_agua_litragem | decimal | sim | Capacidade do reservatório de água (litros) |
| reservatorio_incendio_litragem | decimal | sim | Capacidade do reservatório de incêndio (litros) |
| medidor_agua_instalado_ligado | boolean | sim | Medidor de água instalado e ligado? |
| numero_instalacao_agua | string | sim | Número da instalação de água |
| ponto_esgoto_existente_shell | boolean | sim | Ponto de esgoto existe no shell? |
| ponto_esgoto_mais_proximo | decimal | sim | Distância ao ponto de esgoto mais próximo (metros) |
| sistema_incendio_existente | json | sim | Sistemas de incêndio existentes (checkboxes) |

### Área Técnica / Ar Condicionado

| Coluna | Tipo | Nullable | Descrição |
|--------|------|----------|-----------|
| area_tecnica_externa_existente | boolean | sim | Área técnica externa existe? |
| sugestao_area_tecnica_interna | boolean | sim | Sugestão de área técnica interna? |
| prever_acustica_condensadores | boolean | sim | Prever acústica para condensadores? |
| prever_protecao_condensadores | boolean | sim | Prever proteção para condensadores? |

### Arquitetura / Civil

| Coluna | Tipo | Nullable | Descrição |
|--------|------|----------|-----------|
| piso_acabamento_polido | boolean | sim | Piso com acabamento polido? |
| piso_area_intervencao | decimal | sim | Área de intervenção no piso (m²) |
| necessario_pelicula_fachada | boolean | sim | Película na fachada necessária? |
| pelicula_fachada_area | decimal | sim | Área de película na fachada (m²) |
| prever_marquise | boolean | sim | Prever marquise? |
| necessario_porta_enrolar | boolean | sim | Porta de enrolar necessária? |
| necessario_porta_enrolar_descricao | longText | sim | Descrição da porta de enrolar |
| porta_enrolar_area_necessaria | decimal | sim | Área necessária para porta de enrolar (m²) |
| caixilhos_vidros_existentes | boolean | sim | Caixilhos e vidros existentes? |
| caixilhos_vidros_area | decimal | sim | Área de caixilhos e vidros (m²) |
| prever_impermeabilizacao | boolean | sim | Prever impermeabilização? |
| impermeabilizacao_area_necessaria | decimal | sim | Área necessária de impermeabilização (m²) |
| planta_demarcacao_area | boolean | sim | Planta de demarcação disponível? |
| link_planta_demarcacao_area | string | sim | Link para a planta |

### Contrato BTS

| Coluna | Tipo | Nullable | Descrição |
|--------|------|----------|-----------|
| contrato_bts | json | sim | Dados do contrato BTS |
| prazo_bts | date | sim | Prazo do BTS |
| prazo_desocupacao | date | sim | Prazo de desocupação |
| descricao_prazo_obras | longText | sim | Descrição do prazo de obras no contrato |

### Observações e Capa

| Coluna | Tipo | Nullable | Descrição |
|--------|------|----------|-----------|
| fotos_gerais | json | sim | Fotos gerais do local |
| observacoes_gerais | longText | sim | Observações gerais finais |
| foto_capa | string | sim | Foto de capa do relatório (path no storage) |
| pontos_atencao | longText | sim | Pontos de atenção para o projeto |

### Fotos por seção (33 campos JSON)

Cada campo armazena um array de caminhos de arquivos no storage:

`foto_entrada_energia`, `foto_telefonia`, `foto_rede_gas`, `foto_estrutura`, `foto_cobertura`, `foto_alvenaria`, `foto_estanqueidade`, `foto_reservatorio_agua`, `foto_reservatorio_incendio`, `foto_sistema_incendio`, `foto_area_tecnica`, `foto_piso`, `foto_fachada`, `foto_porta_enrolar`, `foto_caixilhos`, `foto_impermeabilizacao`, `foto_planta_demarcacao`, `foto_spda`, e outros campos de foto de seções específicas.

### Chaves estrangeiras

| Coluna | Referencia | On Delete |
|--------|-----------|-----------|
| projeto_id | projetos.id | cascade |
| marca_id | marcas.id | set null |

---

## `relatorio_fotograficos`

**Propósito**: Relatórios fotográficos das obras. Fluxo: autor cria e preenche → gestor revisa e conclui → PDF gerado e enviado por e-mail. Fotos armazenadas no R2 são convertidas para base64 no PDF.
**Model**: `App\Models\RelatorioFotografico` (SoftDeletes)

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| projeto_id | bigint FK | sim | — | Projeto vinculado |
| autor_id | bigint FK | não | — | Usuário que criou o relatório |
| gestor_id | bigint FK | sim | — | Gestor responsável pela revisão/aprovação |
| status | string | sim | 'rascunho' | Status atual: `rascunho`, `em_revisao`, `concluido` |
| status_relatorio | string | não | 'rascunho' | Campo alternativo de status (pode ser redundante com `status`) |
| sigla | string | sim | — | Sigla da unidade |
| endereco | string | sim | — | Endereço da unidade |
| tipo_unidade | string | sim | — | Tipo da unidade (shopping, rua, etc.) |
| data_posse | date | sim | — | Data de posse do imóvel |
| entregas_contratuais | json | sim | — | Checklist de entregas contratuais (array de itens com status marcado/desmarcado) |
| fotos | json | sim | — | Array de fotos com metadata (path, legenda, seção) |
| deleted_at | timestamp | sim | — | SoftDelete |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

### Chaves estrangeiras

| Coluna | Referencia | On Delete |
|--------|-----------|-----------|
| projeto_id | projetos.id | cascade |
| autor_id | users.id | cascade |
| gestor_id | users.id | cascade |

### Notas

- PDF só é gerado e enviado quando `status = 'concluido'`
- Imagens do R2 são convertidas para base64 pelo `RelatorioFotograficoPdfService` antes de embutir no PDF
- `entregas_contratuais`: lista de entregas que devem ser verificadas na entrega da obra (ex: manual do proprietário, AVCB)
