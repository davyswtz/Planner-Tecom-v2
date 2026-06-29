<?php

return [
    'storage_key' => 'mensagensTemplates',

    'placeholders' => [
        ['key' => 'id', 'label' => 'ID interno'],
        ['key' => 'task_code', 'label' => 'Código da tarefa'],
        ['key' => 'titulo', 'label' => 'Título'],
        ['key' => 'categoria', 'label' => 'Categoria (chave)'],
        ['key' => 'categoria_label', 'label' => 'Categoria (nome)'],
        ['key' => 'status', 'label' => 'Status atual'],
        ['key' => 'status_anterior', 'label' => 'Status anterior'],
        ['key' => 'status_novo', 'label' => 'Status novo'],
        ['key' => 'setor', 'label' => 'Setor / CTO'],
        ['key' => 'cto', 'label' => 'CTO'],
        ['key' => 'regiao', 'label' => 'Região'],
        ['key' => 'responsavel', 'label' => 'Responsável'],
        ['key' => 'prioridade', 'label' => 'Prioridade'],
        ['key' => 'prazo', 'label' => 'Prazo'],
        ['key' => 'clientes_afetados', 'label' => 'Clientes afetados'],
        ['key' => 'coordenadas', 'label' => 'Coordenadas'],
        ['key' => 'localizacao', 'label' => 'Endereço (com fallback)'],
        ['key' => 'localizacao_texto', 'label' => 'Endereço'],
        ['key' => 'descricao', 'label' => 'Descrição'],
        ['key' => 'numero_os', 'label' => 'Número da OS'],
        ['key' => 'ordem_servico', 'label' => 'Ordem de serviço'],
        ['key' => 'nome_cliente', 'label' => 'Nome do cliente'],
        ['key' => 'protocolo', 'label' => 'Protocolo'],
        ['key' => 'sub_processo', 'label' => 'Sub-processo'],
        ['key' => 'data_entrada', 'label' => 'Data de entrada'],
        ['key' => 'data_instalacao', 'label' => 'Data de instalação'],
        ['key' => 'assinada_por', 'label' => 'Assinada por'],
        ['key' => 'assinada_em', 'label' => 'Assinada em'],
        ['key' => 'criada_em', 'label' => 'Criada em'],
        ['key' => 'atualizada_em', 'label' => 'Atualizada em'],
        ['key' => 'duracao_ativa', 'label' => 'Duração ativa (minutos)'],
        ['key' => 'parent_task_id', 'label' => 'ID da tarefa pai'],
        ['key' => 'is_parent_task', 'label' => 'É tarefa pai'],
        ['key' => 'historico', 'label' => 'Histórico'],
        ['key' => 'enviado_por', 'label' => 'Enviado por'],
    ],

    'categorias' => [
        'rompimentos' => [
            'label' => 'Rompimentos',
            'statuses' => ['Criada', 'Em andamento', 'Impedimento', 'Finalizada'],
        ],
        'troca-poste' => [
            'label' => 'Troca de poste',
            'statuses' => ['Criada', 'Em andamento', 'Impedimento', 'Finalizada'],
        ],
        'otimizacao-rede' => [
            'label' => 'Otimização de rede',
            'statuses' => ['Criada', 'Em andamento', 'Impedimento', 'Finalizada'],
        ],
        'atendimento-cliente' => [
            'label' => 'Atendimento',
            'statuses' => ['Criada', 'Em andamento', 'Impedimento', 'Finalizada'],
        ],
        'correcao-atenuacao' => [
            'label' => 'Correção de sinal',
            'statuses' => ['Criada', 'Em andamento', 'Impedimento', 'Finalizada'],
        ],
        'certificacao-cemig' => [
            'label' => 'Certificação CEMIG',
            'statuses' => ['Pendente', 'Em andamento', 'Validação', 'Precisa de adequação', 'Concluído'],
        ],
        'ordem-servico' => [
            'label' => 'Ordem de serviço',
            'statuses' => ['Aberta', 'Em andamento', 'Finalizada'],
        ],
    ],

    'padroes' => [
        'rompimentos' => [
            'Criada' => "📋 *Alerta: ROMPIMENTO*\n━━━━━━━━━━━━━━━━━━━━\n💻 *Número da OS:* {numero_os}\n📍 *Setor/CTO:* {setor}\n📌 *Região:* {regiao}\n🔄 *Status:* {status_anterior} → {status_novo}\n⚡ *Prioridade:* {prioridade}\n👥 *Clientes afetados:* {clientes_afetados}\n📍 *Coordenadas:* {coordenadas}\n📍 *Endereço:* {localizacao}\n🔑 *Código:* {task_code}",
            'Em andamento' => "🚨 *ROMPIMENTO - {cto}*\n\n🗺️ *Endereço:* {localizacao}\n📍 *Localização inicial:* {coordenadas}\n\n🧾 *OS HubSpot:* {numero_os}\n👥 *Clientes afetados:* {clientes_afetados}\n🆔 {task_code}",
            'Impedimento' => "🚨 *Alerta: ROMPIMENTO*\n━━━━━━━━━━━━━━━━━━━━\n💻 *Número da OS:* {numero_os}\n📍 *Setor/CTO:* {setor}\n📌 *Região:* {regiao}\n🔄 *Status:* {status_anterior} → {status_novo}\n⚡ *Prioridade:* {prioridade}\n👥 *Clientes afetados:* {clientes_afetados}\n📍 *Coordenadas:* {coordenadas}\n📍 *Endereço:* {localizacao}\n🔑 *Código:* {task_code}",
            'Finalizada' => "✅ *Alerta: ROMPIMENTO*\n━━━━━━━━━━━━━━━━━━━━\n💻 *Número da OS:* {numero_os}\n📍 *Setor/CTO:* {setor}\n📌 *Região:* {regiao}\n🔄 *Status:* {status_anterior} → {status_novo}\n⚡ *Prioridade:* {prioridade}\n👥 *Clientes afetados:* {clientes_afetados}\n📍 *Coordenadas:* {coordenadas}\n📍 *Endereço:* {localizacao}\n🔑 *Código:* {task_code}",
        ],
        'troca-poste' => [
            'Criada' => "📋 *Alerta: TROCA DE POSTE*\n━━━━━━━━━━━━━━━━━━━━\n💻 *Número da OS:* {numero_os}\n📍 *Setor/CTO:* {setor}\n📌 *Região:* {regiao}\n🔄 *Status:* {status_anterior} → {status_novo}\n⚡ *Prioridade:* {prioridade}\n📍 *Coordenadas:* {coordenadas}\n📍 *Endereço:* {localizacao}\n🔑 *Código:* {task_code}",
            'Em andamento' => "🔧 *Alerta: TROCA DE POSTE*\n━━━━━━━━━━━━━━━━━━━━\n💻 *Número da OS:* {numero_os}\n📍 *Setor/CTO:* {setor}\n📌 *Região:* {regiao}\n🔄 *Status:* {status_anterior} → {status_novo}\n⚡ *Prioridade:* {prioridade}\n📍 *Coordenadas:* {coordenadas}\n📍 *Endereço:* {localizacao}\n🔑 *Código:* {task_code}",
            'Impedimento' => "🚨 *Alerta: TROCA DE POSTE*\n━━━━━━━━━━━━━━━━━━━━\n💻 *Número da OS:* {numero_os}\n📍 *Setor/CTO:* {setor}\n📌 *Região:* {regiao}\n🔄 *Status:* {status_anterior} → {status_novo}\n⚡ *Prioridade:* {prioridade}\n📍 *Coordenadas:* {coordenadas}\n📍 *Endereço:* {localizacao}\n🔑 *Código:* {task_code}",
            'Finalizada' => "✅ *Alerta: TROCA DE POSTE*\n━━━━━━━━━━━━━━━━━━━━\n💻 *Número da OS:* {numero_os}\n📍 *Setor/CTO:* {setor}\n📌 *Região:* {regiao}\n🔄 *Status:* {status_anterior} → {status_novo}\n⚡ *Prioridade:* {prioridade}\n📍 *Coordenadas:* {coordenadas}\n📍 *Endereço:* {localizacao}\n🔑 *Código:* {task_code}",
        ],
        'otimizacao-rede' => [
            'Criada' => "Otimização de Rede\n🌐 *{titulo}*\n📍 Localização: {localizacao}\n📝 Descrição: {descricao}\n\n👤 Enviado por: {enviado_por}\n\n🆔 {task_code}",
            'Em andamento' => "Otimização de Rede\n🌐 *{titulo}*\n📍 Localização: {localizacao}\n📝 Descrição: {descricao}\n\n👤 Enviado por: {enviado_por}\n\n🆔 {task_code}",
            'Impedimento' => "Otimização de Rede\n🌐 *{titulo}*\n📍 Localização: {localizacao}\n📝 Descrição: {descricao}\n\n👤 Enviado por: {enviado_por}\n\n🆔 {task_code}",
            'Finalizada' => "Otimização de Rede\n🌐 *{titulo}*\n📍 Localização: {localizacao}\n📝 Descrição: {descricao}\n\n👤 Enviado por: {enviado_por}\n\n🆔 {task_code}",
        ],
        'atendimento-cliente' => [
            'Criada' => "📋 *Alerta: ATENDIMENTO*\n━━━━━━━━━━━━━━━━━━━━\n💻 *Número da OS:* {numero_os}\n📍 *Setor/CTO:* {setor}\n📌 *Região:* {regiao}\n🔄 *Status:* {status_anterior} → {status_novo}\n👤 *Cliente:* {nome_cliente}\n📋 *Protocolo:* {protocolo}\n🔑 *Código:* {task_code}",
            'Em andamento' => "🔧 *Alerta: ATENDIMENTO*\n━━━━━━━━━━━━━━━━━━━━\n💻 *Número da OS:* {numero_os}\n📍 *Setor/CTO:* {setor}\n📌 *Região:* {regiao}\n🔄 *Status:* {status_anterior} → {status_novo}\n👤 *Cliente:* {nome_cliente}\n📋 *Protocolo:* {protocolo}\n🔑 *Código:* {task_code}",
            'Impedimento' => "🚨 *Alerta: ATENDIMENTO*\n━━━━━━━━━━━━━━━━━━━━\n💻 *Número da OS:* {numero_os}\n📍 *Setor/CTO:* {setor}\n📌 *Região:* {regiao}\n🔄 *Status:* {status_anterior} → {status_novo}\n👤 *Cliente:* {nome_cliente}\n📋 *Protocolo:* {protocolo}\n🔑 *Código:* {task_code}",
            'Finalizada' => "✅ *Alerta: ATENDIMENTO*\n━━━━━━━━━━━━━━━━━━━━\n💻 *Número da OS:* {numero_os}\n📍 *Setor/CTO:* {setor}\n📌 *Região:* {regiao}\n🔄 *Status:* {status_anterior} → {status_novo}\n👤 *Cliente:* {nome_cliente}\n📋 *Protocolo:* {protocolo}\n🔑 *Código:* {task_code}",
        ],
        'correcao-atenuacao' => [
            'Criada' => "📋 *Alerta: CORREÇÃO DE SINAL*\n━━━━━━━━━━━━━━━━━━━━\n💻 *Número da OS:* {numero_os}\n📍 *Setor/CTO:* {setor}\n📌 *Região:* {regiao}\n🔄 *Status:* {status_anterior} → {status_novo}\n🔑 *Código:* {task_code}",
            'Em andamento' => "🔧 *Alerta: CORREÇÃO DE SINAL*\n━━━━━━━━━━━━━━━━━━━━\n💻 *Número da OS:* {numero_os}\n📍 *Setor/CTO:* {setor}\n📌 *Região:* {regiao}\n🔄 *Status:* {status_anterior} → {status_novo}\n🔑 *Código:* {task_code}",
            'Impedimento' => "🚨 *Alerta: CORREÇÃO DE SINAL*\n━━━━━━━━━━━━━━━━━━━━\n💻 *Número da OS:* {numero_os}\n📍 *Setor/CTO:* {setor}\n📌 *Região:* {regiao}\n🔄 *Status:* {status_anterior} → {status_novo}\n🔑 *Código:* {task_code}",
            'Finalizada' => "✅ *Alerta: CORREÇÃO DE SINAL*\n━━━━━━━━━━━━━━━━━━━━\n💻 *Número da OS:* {numero_os}\n📍 *Setor/CTO:* {setor}\n📌 *Região:* {regiao}\n🔄 *Status:* {status_anterior} → {status_novo}\n🔑 *Código:* {task_code}",
        ],
        'certificacao-cemig' => [
            'Pendente' => "📋 *Alerta: CERTIFICAÇÃO CEMIG*\n━━━━━━━━━━━━━━━━━━━━\n📌 *Título:* {titulo}\n📌 *Região:* {regiao}\n🔄 *Status:* {status_anterior} → {status_novo}\n🔑 *Código:* {task_code}",
            'Em andamento' => "🔧 *Alerta: CERTIFICAÇÃO CEMIG*\n━━━━━━━━━━━━━━━━━━━━\n📌 *Título:* {titulo}\n📌 *Região:* {regiao}\n🔄 *Status:* {status_anterior} → {status_novo}\n🔑 *Código:* {task_code}",
            'Validação' => "📋 *Alerta: CERTIFICAÇÃO CEMIG*\n━━━━━━━━━━━━━━━━━━━━\n📌 *Título:* {titulo}\n📌 *Região:* {regiao}\n🔄 *Status:* {status_anterior} → {status_novo}\n🔑 *Código:* {task_code}",
            'Precisa de adequação' => "🚨 *Alerta: CERTIFICAÇÃO CEMIG*\n━━━━━━━━━━━━━━━━━━━━\n📌 *Título:* {titulo}\n📌 *Região:* {regiao}\n🔄 *Status:* {status_anterior} → {status_novo}\n🔑 *Código:* {task_code}",
            'Concluído' => "✅ *Alerta: CERTIFICAÇÃO CEMIG*\n━━━━━━━━━━━━━━━━━━━━\n📌 *Título:* {titulo}\n📌 *Região:* {regiao}\n🔄 *Status:* {status_anterior} → {status_novo}\n🔑 *Código:* {task_code}",
        ],
        'ordem-servico' => [
            'Aberta' => "📋 *Atualização de Ordem de Serviço*\n\n📌 *Nome da OS:* {titulo}\n🔄 *Status da OS:* {status_novo}",
            'Em andamento' => "📋 *Atualização de Ordem de Serviço*\n\n📌 *Nome da OS:* {titulo}\n🔄 *Status da OS:* {status_novo}\n📝 *Descrição:* {descricao}",
            'Finalizada' => "✅ *OS Finalizada*\n\n📌 *Nome da OS:* {titulo}\n✅ *Status da OS:* {status_novo}",
        ],
    ],
];
