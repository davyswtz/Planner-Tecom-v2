<?php

return [
    'storage_key' => 'mensagensTemplates',

    'grupos' => [
        'operacional' => [
            'label' => 'Tarefas operacionais',
            'descricao' => 'Post no canal do Telegram (e paralelo Nicon/Google) ao mudar o status no kanban. OS e atualizações vão como comentários desse post.',
        ],
        'ordem-servico' => [
            'label' => 'Ordens de serviço',
            'descricao' => 'Comentário no post da tarefa pai no Telegram (grupo de discussão do canal) quando a OS muda de status.',
        ],
    ],

    /*
    | Placeholders disponíveis na aba Mensagens.
    | - key: {variavel} no template
    | - label: nome amigável
    | - hint: como aparece no Telegram / dica de uso
    | - exemplo: valor de exemplo na UI
    */
    'placeholders' => [
        ['key' => 'task_code', 'label' => 'Código da tarefa', 'hint' => 'Ex.: GV-ROM-001, VL-TRO-024', 'exemplo' => 'GV-ROM-001'],
        ['key' => 'titulo', 'label' => 'Título', 'hint' => 'Título da tarefa ou da OS', 'exemplo' => 'ROMPIMENTO - CTO-042'],
        ['key' => 'id', 'label' => 'ID interno', 'hint' => 'ID numérico no banco', 'exemplo' => '42'],
        ['key' => 'categoria', 'label' => 'Categoria (chave)', 'hint' => 'Chave técnica: rompimentos, troca-poste…', 'exemplo' => 'rompimentos'],
        ['key' => 'categoria_label', 'label' => 'Categoria (nome)', 'hint' => 'Nome legível da categoria', 'exemplo' => 'Rompimentos'],
        ['key' => 'status', 'label' => 'Status atual', 'hint' => 'Status novo da mudança', 'exemplo' => 'Em andamento'],
        ['key' => 'status_anterior', 'label' => 'Status anterior', 'hint' => 'Status de onde saiu', 'exemplo' => 'Criada'],
        ['key' => 'status_novo', 'label' => 'Status novo', 'hint' => 'Status para onde foi', 'exemplo' => 'Em andamento'],
        ['key' => 'setor', 'label' => 'Setor / CTO', 'hint' => 'Setor ou CTO da tarefa', 'exemplo' => 'CTO-042'],
        ['key' => 'elemento', 'label' => 'Elemento', 'hint' => 'Elemento (manutenção corretiva); fallback do setor', 'exemplo' => 'Poste P-042'],
        ['key' => 'cto', 'label' => 'CTO', 'hint' => 'Alias de setor/CTO', 'exemplo' => 'CTO-042'],
        ['key' => 'regiao', 'label' => 'Região', 'hint' => 'Define o canal Telegram (Goval / Vale do Aço / Teste)', 'exemplo' => 'Goval'],
        ['key' => 'responsavel', 'label' => 'Responsável / técnicos', 'hint' => 'No Telegram vira @Nome do técnico', 'exemplo' => '@João Silva'],
        ['key' => 'prioridade', 'label' => 'Prioridade', 'hint' => 'Alta, Média, Baixa…', 'exemplo' => 'Alta'],
        ['key' => 'prazo', 'label' => 'Prazo', 'hint' => 'Data formatada dd/mm/aaaa', 'exemplo' => '15/07/2026'],
        ['key' => 'clientes_afetados', 'label' => 'Clientes afetados', 'hint' => 'Quantidade de clientes', 'exemplo' => '12'],
        ['key' => 'coordenadas', 'label' => 'Coordenadas', 'hint' => 'No Telegram: só os números, clicáveis no Maps', 'exemplo' => '-18.8512, -41.9495'],
        ['key' => 'localizacao', 'label' => 'Localização', 'hint' => 'Endereço; se vazio, usa coordenadas clicáveis', 'exemplo' => 'Rua Exemplo, 100 — Centro'],
        ['key' => 'localizacao_texto', 'label' => 'Endereço (texto puro)', 'hint' => 'Só o endereço, sem fallback de coordenadas', 'exemplo' => 'Rua Exemplo, 100 — Centro'],
        ['key' => 'descricao', 'label' => 'Descrição', 'hint' => 'Texto livre da tarefa/OS', 'exemplo' => 'Fusão das fibras na CTO'],
        ['key' => 'numero_os', 'label' => 'Número da OS (HubSpot)', 'hint' => 'Número externo da OS', 'exemplo' => '004026203101920778'],
        ['key' => 'ordem_servico', 'label' => 'Ordem de serviço', 'hint' => 'Alias de numero_os', 'exemplo' => 'OS-12345'],
        ['key' => 'nome_cliente', 'label' => 'Nome do cliente', 'hint' => 'Cliente; em atendimento usa o título se vazio', 'exemplo' => 'Maria Souza'],
        ['key' => 'protocolo', 'label' => 'Protocolo', 'hint' => 'Protocolo do atendimento', 'exemplo' => 'PROT-98765'],
        ['key' => 'sub_processo', 'label' => 'Sub-processo', 'hint' => 'Subprocesso vinculado', 'exemplo' => 'Instalação'],
        ['key' => 'data_entrada', 'label' => 'Data de entrada', 'hint' => 'Criação da tarefa (dd/mm/aaaa)', 'exemplo' => '01/06/2026'],
        ['key' => 'data_instalacao', 'label' => 'Data de instalação', 'hint' => 'Data de instalação', 'exemplo' => '10/06/2026'],
        ['key' => 'assinada_por', 'label' => 'Assinada por', 'hint' => 'Quem assinou', 'exemplo' => 'tecnico.silva'],
        ['key' => 'assinada_em', 'label' => 'Assinada em', 'hint' => 'Data/hora da assinatura', 'exemplo' => '10/06/2026 14:30'],
        ['key' => 'criada_em', 'label' => 'Criada em', 'hint' => 'Data/hora de criação', 'exemplo' => '01/06/2026 09:00'],
        ['key' => 'atualizada_em', 'label' => 'Atualizada em', 'hint' => 'Última atualização', 'exemplo' => '29/06/2026 11:45'],
        ['key' => 'duracao_ativa', 'label' => 'Duração ativa', 'hint' => 'Tempo em andamento (ex.: 3h)', 'exemplo' => '3h'],
        ['key' => 'parent_task_id', 'label' => 'ID da tarefa pai', 'hint' => 'Útil em mensagens de OS', 'exemplo' => '42'],
        ['key' => 'parent_task_code', 'label' => 'Código da tarefa pai', 'hint' => 'Código do post pai no canal', 'exemplo' => 'GV-ROM-001'],
        ['key' => 'parent_titulo', 'label' => 'Título da tarefa pai', 'hint' => 'Título do post pai', 'exemplo' => 'ROMPIMENTO - CTO-042'],
        ['key' => 'parent_categoria', 'label' => 'Categoria pai (chave)', 'hint' => 'Chave da categoria do pai', 'exemplo' => 'rompimentos'],
        ['key' => 'parent_categoria_label', 'label' => 'Categoria pai (nome)', 'hint' => 'Nome da categoria do pai', 'exemplo' => 'Rompimentos'],
        ['key' => 'os_tipo', 'label' => 'Tipo da OS', 'hint' => 'Tipo/atividade da OS', 'exemplo' => 'Instalação de CTO'],
        ['key' => 'os_sequencia', 'label' => 'Sequência da OS', 'hint' => 'Posição 1, 2, 3… ou lista curta', 'exemplo' => '2'],
        ['key' => 'os_lista', 'label' => 'Lista de OS', 'hint' => 'Lista numerada de todas as OS do pai', 'exemplo' => "1. Abertura de vala\n2. Instalação de CTO"],
        ['key' => 'is_parent_task', 'label' => 'É tarefa pai', 'hint' => 'Sim ou Não', 'exemplo' => 'Sim'],
        ['key' => 'historico', 'label' => 'Histórico', 'hint' => 'Resumo do histórico de status', 'exemplo' => 'Criada → Em andamento'],
        ['key' => 'os_total', 'label' => 'Total de OS', 'hint' => 'Quantidade de OS vinculadas', 'exemplo' => '4'],
        ['key' => 'os_finalizadas', 'label' => 'OS finalizadas', 'hint' => 'Quantidade de OS finalizadas', 'exemplo' => '3'],
        ['key' => 'os_resumo_tecnicos', 'label' => 'Resumo por técnico', 'hint' => 'Atividades agrupadas por técnico', 'exemplo' => '• João — 2 OS'],
        ['key' => 'os_resumo', 'label' => 'Resumo completo de OS', 'hint' => 'Bloco pronto para colar na finalização', 'exemplo' => 'Resumo de OS…'],
        ['key' => 'etiquetas', 'label' => 'Nomes das etiquetas', 'hint' => 'Lista simples dos nomes', 'exemplo' => 'IPE1504, IPG1106'],
        ['key' => 'etiquetas_localizacao', 'label' => 'Etiquetas + localização', 'hint' => 'Lista com endereço e coordenadas clicáveis', 'exemplo' => '1. 📍 *IPE1504:* Rua…'],
        ['key' => 'etiquetas_coordenadas', 'label' => 'Etiquetas + coordenadas', 'hint' => 'Lista só com coordenadas clicáveis no Telegram', 'exemplo' => '1. 📍 *IPE1504:* -18.85…'],
    ],

    'categorias' => [
        'rompimentos' => [
            'label' => 'Rompimentos',
            'grupo' => 'operacional',
            'statuses' => ['Em andamento', 'Impedimento', 'Finalizada'],
        ],
        'troca-poste' => [
            'label' => 'Troca de poste',
            'grupo' => 'operacional',
            'statuses' => ['Em andamento', 'Impedimento', 'Finalizada'],
        ],
        'troca-etiqueta' => [
            'label' => 'Troca de etiqueta',
            'grupo' => 'operacional',
            'statuses' => ['Pendente', 'Em andamento', 'Impedimento', 'Concluída', 'Finalizada'],
        ],
        'otimizacao-rede' => [
            'label' => 'Otimização de rede',
            'grupo' => 'operacional',
            'statuses' => ['Em andamento', 'Impedimento', 'Finalizada'],
        ],
        'atendimento-cliente' => [
            'label' => 'Atendimento',
            'grupo' => 'operacional',
            'statuses' => ['Em andamento', 'Impedimento', 'Finalizada'],
        ],
        'correcao-atenuacao' => [
            'label' => 'Correção de sinal',
            'grupo' => 'operacional',
            'statuses' => ['Em andamento', 'Impedimento', 'Finalizada'],
        ],
        'manutencao-corretiva' => [
            'label' => 'Manutenção corretiva',
            'grupo' => 'operacional',
            'statuses' => ['Em andamento', 'Impedimento', 'Finalizada'],
        ],
        'certificacao-cemig' => [
            'label' => 'Certificação CEMIG',
            'grupo' => 'operacional',
            'statuses' => ['Pendente', 'Em andamento', 'Validação', 'Precisa de adequação', 'Concluído'],
        ],
        'ordem-servico' => [
            'label' => 'Ordem de serviço',
            'grupo' => 'ordem-servico',
            'descricao' => 'Use {parent_titulo}, {os_tipo}, {responsavel}, {descricao}. No Telegram vira comentário no post da tarefa pai.',
            'statuses' => ['Em andamento', 'Finalizada', 'Aberta'],
        ],
    ],

    'padroes' => [
        'rompimentos' => [
            'Em andamento' => "🚨 *ROMPIMENTO - {cto}*\n\n🗺️ *Endereço:* {localizacao}\n📍 *Localização inicial:* {coordenadas}\n\n🧾 *OS HubSpot:* {numero_os}\n👥 *Clientes afetados:* {clientes_afetados}\n🆔 {task_code}",
            'Impedimento' => "🚨 *Alerta: ROMPIMENTO*\n━━━━━━━━━━━━━━━━━━━━\n💻 *Número da OS:* {numero_os}\n📍 *Setor/CTO:* {setor}\n📌 *Região:* {regiao}\n🔄 *Status:* {status_anterior} → {status_novo}\n⚡ *Prioridade:* {prioridade}\n👥 *Clientes afetados:* {clientes_afetados}\n📍 *Coordenadas:* {coordenadas}\n📍 *Endereço:* {localizacao}\n🔑 *Código:* {task_code}",
            'Finalizada' => "✅ *Alerta: ROMPIMENTO*\n━━━━━━━━━━━━━━━━━━━━\n💻 *Número da OS:* {numero_os}\n📍 *Setor/CTO:* {setor}\n📌 *Região:* {regiao}\n🔄 *Status:* {status_anterior} → {status_novo}\n⚡ *Prioridade:* {prioridade}\n👥 *Clientes afetados:* {clientes_afetados}\n📍 *Coordenadas:* {coordenadas}\n📍 *Endereço:* {localizacao}\n🔑 *Código:* {task_code}\n\n{os_resumo}",
        ],
        'troca-poste' => [
            'Em andamento' => "🔧 *Alerta: TROCA DE POSTE*\n━━━━━━━━━━━━━━━━━━━━\n💻 *Número da OS:* {numero_os}\n📍 *Setor/CTO:* {setor}\n📌 *Região:* {regiao}\n🔄 *Status:* {status_anterior} → {status_novo}\n⚡ *Prioridade:* {prioridade}\n📍 *Coordenadas:* {coordenadas}\n📍 *Endereço:* {localizacao}\n🔑 *Código:* {task_code}",
            'Impedimento' => "🚨 *Alerta: TROCA DE POSTE*\n━━━━━━━━━━━━━━━━━━━━\n💻 *Número da OS:* {numero_os}\n📍 *Setor/CTO:* {setor}\n📌 *Região:* {regiao}\n🔄 *Status:* {status_anterior} → {status_novo}\n⚡ *Prioridade:* {prioridade}\n📍 *Coordenadas:* {coordenadas}\n📍 *Endereço:* {localizacao}\n🔑 *Código:* {task_code}",
            'Finalizada' => "✅ *Alerta: TROCA DE POSTE*\n━━━━━━━━━━━━━━━━━━━━\n💻 *Número da OS:* {numero_os}\n📍 *Setor/CTO:* {setor}\n📌 *Região:* {regiao}\n🔄 *Status:* {status_anterior} → {status_novo}\n⚡ *Prioridade:* {prioridade}\n📍 *Coordenadas:* {coordenadas}\n📍 *Endereço:* {localizacao}\n🔑 *Código:* {task_code}\n\n{os_resumo}",
        ],
        'troca-etiqueta' => [
            'Pendente' => "📋 *Alerta: TROCA DE ETIQUETA*\n━━━━━━━━━━━━━━━━━━━━\n💻 *Número da OS:* {numero_os}\n📌 *Região:* {regiao}\n👤 *Responsável:* {responsavel}\n🔄 *Status:* {status_anterior} → {status_novo}\n\n🏷️ *Etiquetas:*\n{etiquetas_localizacao}\n\n🔑 *Código:* {task_code}",
            'Em andamento' => "🏷️ *TROCA DE ETIQUETA*\n━━━━━━━━━━━━━━━━━━━━\n💻 *Número da OS:* {numero_os}\n📌 *Região:* {regiao}\n👤 *Responsável:* {responsavel}\n🔄 *Status:* {status_anterior} → {status_novo}\n\n🏷️ *Etiquetas:*\n{etiquetas_localizacao}\n\n🔑 *Código:* {task_code}",
            'Impedimento' => "🚨 *Alerta: TROCA DE ETIQUETA*\n━━━━━━━━━━━━━━━━━━━━\n💻 *Número da OS:* {numero_os}\n📌 *Região:* {regiao}\n👤 *Responsável:* {responsavel}\n🔄 *Status:* {status_anterior} → {status_novo}\n\n🏷️ *Etiquetas:*\n{etiquetas_localizacao}\n\n🔑 *Código:* {task_code}",
            'Finalizada' => "✅ *TROCA DE ETIQUETA — Concluída*\n━━━━━━━━━━━━━━━━━━━━\n💻 *Número da OS:* {numero_os}\n📌 *Região:* {regiao}\n👤 *Responsável:* {responsavel}\n🔄 *Status:* {status_anterior} → {status_novo}\n\n🏷️ *Etiquetas:*\n{etiquetas_localizacao}\n\n🔑 *Código:* {task_code}\n\n{os_resumo}",
            'Concluída' => "✅ *TROCA DE ETIQUETA — Concluída*\n━━━━━━━━━━━━━━━━━━━━\n💻 *Número da OS:* {numero_os}\n📌 *Região:* {regiao}\n👤 *Responsável:* {responsavel}\n🔄 *Status:* {status_anterior} → {status_novo}\n\n🏷️ *Etiquetas:*\n{etiquetas_localizacao}\n\n🔑 *Código:* {task_code}\n\n{os_resumo}",
        ],
        'otimizacao-rede' => [
            'Em andamento' => "Otimização de Rede\n🌐 *{titulo}*\n📍 Localização: {localizacao}\n📝 Descrição: {descricao}\n\n⏱️ Duração ativa: {duracao_ativa}\n\n🆔 {task_code}",
            'Impedimento' => "Otimização de Rede\n🌐 *{titulo}*\n📍 Localização: {localizacao}\n📝 Descrição: {descricao}\n\n⏱️ Duração ativa: {duracao_ativa}\n\n🆔 {task_code}",
            'Finalizada' => "Otimização de Rede\n🌐 *{titulo}*\n📍 Localização: {localizacao}\n📝 Descrição: {descricao}\n\n⏱️ Duração ativa: {duracao_ativa}\n\n🆔 {task_code}\n\n{os_resumo}",
        ],
        'atendimento-cliente' => [
            'Em andamento' => "🔧 *Alerta: ATENDIMENTO*\n━━━━━━━━━━━━━━━━━━━━\n💻 *Número da OS:* {numero_os}\n📍 *Setor/CTO:* {setor}\n📌 *Região:* {regiao}\n🔄 *Status:* {status_anterior} → {status_novo}\n👤 *Cliente:* {nome_cliente}\n📅 *Data de entrada:* {data_entrada}\n🔑 *Código:* {task_code}",
            'Impedimento' => "🚨 *Alerta: ATENDIMENTO*\n━━━━━━━━━━━━━━━━━━━━\n💻 *Número da OS:* {numero_os}\n📍 *Setor/CTO:* {setor}\n📌 *Região:* {regiao}\n🔄 *Status:* {status_anterior} → {status_novo}\n👤 *Cliente:* {nome_cliente}\n📅 *Data de entrada:* {data_entrada}\n🔑 *Código:* {task_code}",
            'Finalizada' => "✅ *Alerta: ATENDIMENTO*\n━━━━━━━━━━━━━━━━━━━━\n💻 *Número da OS:* {numero_os}\n📍 *Setor/CTO:* {setor}\n📌 *Região:* {regiao}\n🔄 *Status:* {status_anterior} → {status_novo}\n👤 *Cliente:* {nome_cliente}\n📅 *Data de entrada:* {data_entrada}\n🔑 *Código:* {task_code}\n\n{os_resumo}",
        ],
        'correcao-atenuacao' => [
            'Em andamento' => "🔧 *Alerta: CORREÇÃO DE SINAL*\n━━━━━━━━━━━━━━━━━━━━\n💻 *Número da OS:* {numero_os}\n📍 *Setor/CTO:* {setor}\n📌 *Região:* {regiao}\n🔄 *Status:* {status_anterior} → {status_novo}\n🔑 *Código:* {task_code}",
            'Impedimento' => "🚨 *Alerta: CORREÇÃO DE SINAL*\n━━━━━━━━━━━━━━━━━━━━\n💻 *Número da OS:* {numero_os}\n📍 *Setor/CTO:* {setor}\n📌 *Região:* {regiao}\n🔄 *Status:* {status_anterior} → {status_novo}\n🔑 *Código:* {task_code}",
            'Finalizada' => "✅ *Alerta: CORREÇÃO DE SINAL*\n━━━━━━━━━━━━━━━━━━━━\n💻 *Número da OS:* {numero_os}\n📍 *Setor/CTO:* {setor}\n📌 *Região:* {regiao}\n🔄 *Status:* {status_anterior} → {status_novo}\n🔑 *Código:* {task_code}\n\n{os_resumo}",
        ],
        'manutencao-corretiva' => [
            'Em andamento' => "🔧 *Alerta: MANUTENÇÃO CORRETIVA*\n━━━━━━━━━━━━━━━━━━━━\n💻 *Número da OS:* {numero_os}\n📍 *Elemento:* {elemento}\n📌 *Região:* {regiao}\n🔄 *Status:* {status_anterior} → {status_novo}\n⚡ *Prioridade:* {prioridade}\n👤 *Responsável:* {responsavel}\n📍 *Coordenadas:* {coordenadas}\n📍 *Endereço:* {localizacao}\n🔑 *Código:* {task_code}",
            'Impedimento' => "🚨 *Alerta: MANUTENÇÃO CORRETIVA*\n━━━━━━━━━━━━━━━━━━━━\n💻 *Número da OS:* {numero_os}\n📍 *Elemento:* {elemento}\n📌 *Região:* {regiao}\n🔄 *Status:* {status_anterior} → {status_novo}\n⚡ *Prioridade:* {prioridade}\n👤 *Responsável:* {responsavel}\n📍 *Coordenadas:* {coordenadas}\n📍 *Endereço:* {localizacao}\n🔑 *Código:* {task_code}",
            'Finalizada' => "✅ *Alerta: MANUTENÇÃO CORRETIVA*\n━━━━━━━━━━━━━━━━━━━━\n💻 *Número da OS:* {numero_os}\n📍 *Elemento:* {elemento}\n📌 *Região:* {regiao}\n🔄 *Status:* {status_anterior} → {status_novo}\n⚡ *Prioridade:* {prioridade}\n👤 *Responsável:* {responsavel}\n📍 *Coordenadas:* {coordenadas}\n📍 *Endereço:* {localizacao}\n🔑 *Código:* {task_code}\n\n{os_resumo}",
        ],
        'certificacao-cemig' => [
            'Pendente' => "📋 *Alerta: CERTIFICAÇÃO CEMIG*\n━━━━━━━━━━━━━━━━━━━━\n📌 *Título:* {titulo}\n📌 *Região:* {regiao}\n🔄 *Status:* {status_anterior} → {status_novo}\n🔑 *Código:* {task_code}",
            'Em andamento' => "🔧 *Alerta: CERTIFICAÇÃO CEMIG*\n━━━━━━━━━━━━━━━━━━━━\n📌 *Título:* {titulo}\n📌 *Região:* {regiao}\n🔄 *Status:* {status_anterior} → {status_novo}\n🔑 *Código:* {task_code}",
            'Validação' => "📋 *Alerta: CERTIFICAÇÃO CEMIG*\n━━━━━━━━━━━━━━━━━━━━\n📌 *Título:* {titulo}\n📌 *Região:* {regiao}\n🔄 *Status:* {status_anterior} → {status_novo}\n🔑 *Código:* {task_code}",
            'Precisa de adequação' => "🚨 *Alerta: CERTIFICAÇÃO CEMIG*\n━━━━━━━━━━━━━━━━━━━━\n📌 *Título:* {titulo}\n📌 *Região:* {regiao}\n🔄 *Status:* {status_anterior} → {status_novo}\n🔑 *Código:* {task_code}",
            'Concluído' => "✅ *Alerta: CERTIFICAÇÃO CEMIG*\n━━━━━━━━━━━━━━━━━━━━\n📌 *Título:* {titulo}\n📌 *Região:* {regiao}\n🔄 *Status:* {status_anterior} → {status_novo}\n🔑 *Código:* {task_code}\n\n{os_resumo}",
        ],
        'ordem-servico' => [
            'Aberta' => "📋 *Atualização de Ordem de Serviço*\n\n📌 *Nome da OS:* {titulo}\n🔄 *Status da OS:* {status_novo}",
            'Em andamento' => "📋 *OS em andamento*\n━━━━━━━━━━━━━━━━━━━━\n🔗 *Tarefa pai:* {parent_titulo} ({parent_task_code})\n📂 *Categoria:* {parent_categoria_label}\n📌 *Tipo:* {os_tipo}\n👤 *Técnicos:* {responsavel}\n📝 *Descrição:* {descricao}\n📌 *Região:* {regiao}\n🔑 *Código OS:* {task_code}",
            'Finalizada' => "✅ *OS Finalizada*\n━━━━━━━━━━━━━━━━━━━━\n🔗 *Tarefa pai:* {parent_titulo} ({parent_task_code})\n📌 *Tipo:* {os_tipo}\n👤 *Técnicos:* {responsavel}\n📝 *Descrição:* {descricao}\n🔑 *Código OS:* {task_code}",
        ],
    ],
];
