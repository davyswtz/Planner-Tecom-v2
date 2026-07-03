<?php

namespace App\Services;

use App\Models\AppConfig;

class MensagemTemplateService
{
    public function catalogo(): array
    {
        $categorias = config('mensagens.categorias', []);
        $padroes = config('mensagens.padroes', []);

        return collect($categorias)
            ->map(function (array $meta, string $key) use ($padroes) {
                return [
                    'key' => $key,
                    'label' => $meta['label'] ?? $key,
                    'grupo' => $meta['grupo'] ?? 'operacional',
                    'descricao' => $meta['descricao'] ?? null,
                    'statuses' => $meta['statuses'] ?? [],
                    'padroes' => $padroes[$key] ?? [],
                ];
            })
            ->values()
            ->all();
    }

    /** @return array<string, array{label: string, descricao?: string}> */
    public function grupos(): array
    {
        return config('mensagens.grupos', []);
    }

    public function placeholders(): array
    {
        return config('mensagens.placeholders', []);
    }

    /** @return array<string, array<string, string>> */
    public function listarSalvos(): array
    {
        return AppConfig::getJson(config('mensagens.storage_key', 'mensagensTemplates'), []);
    }

    /** @return array<string, array<string, string>> */
    public function listarEfetivos(): array
    {
        $salvos = $this->listarSalvos();
        $efetivos = [];

        foreach (config('mensagens.categorias', []) as $categoria => $meta) {
            foreach ($meta['statuses'] ?? [] as $status) {
                $custom = trim($salvos[$categoria][$status] ?? '');
                $padrao = trim(config("mensagens.padroes.{$categoria}.{$status}", ''));

                $efetivos[$categoria][$status] = $custom !== '' ? $custom : $padrao;
            }
        }

        return $efetivos;
    }

    public function obterTemplate(string $categoria, string $status): ?string
    {
        $categoria = $this->normalizarCategoria($categoria);
        $status = $this->normalizarStatus($status);
        $status = $this->ajustarStatusTemplate($categoria, $status);

        if ($categoria === '' || $status === '') {
            return null;
        }

        $salvos = $this->listarSalvos();
        $custom = trim($salvos[$categoria][$status] ?? '');

        if ($custom !== '') {
            return $custom;
        }

        $padrao = trim(config("mensagens.padroes.{$categoria}.{$status}", ''));

        return $padrao !== '' ? $padrao : null;
    }

    /** @param array<string, array<string, string>> $templates */
    public function salvar(array $templates): void
    {
        $validados = [];
        $categorias = config('mensagens.categorias', []);

        foreach ($categorias as $categoria => $meta) {
            $statuses = $meta['statuses'] ?? [];
            $entrada = $templates[$categoria] ?? [];

            if (! is_array($entrada)) {
                continue;
            }

            foreach ($statuses as $status) {
                if (! array_key_exists($status, $entrada)) {
                    continue;
                }

                $texto = trim((string) $entrada[$status]);
                $padrao = trim(config("mensagens.padroes.{$categoria}.{$status}", ''));

                if ($texto !== '' && $texto !== $padrao) {
                    $validados[$categoria][$status] = $texto;
                }
            }
        }

        AppConfig::setJson(config('mensagens.storage_key', 'mensagensTemplates'), $validados);
    }

    public function renderizar(
        string $template,
        array $task,
        ?string $statusAnterior = null,
        ?string $statusNovo = null,
        ?string $enviadoPor = null,
    ): string {
        $mapa = $this->montarMapaPlaceholders($task, $statusAnterior, $statusNovo, $enviadoPor);

        return preg_replace_callback(
            '/\{([a-z0-9_]+)\}/i',
            fn (array $matches) => $mapa[$matches[1]] ?? $matches[0],
            $template
        ) ?? $template;
    }

    public function dadosExemplo(?string $categoria = null): array
    {
        $categoria = $this->normalizarCategoria($categoria ?? '');

        if ($categoria === 'troca-etiqueta') {
            return $this->dadosExemploTrocaEtiqueta();
        }

        if ($categoria === 'ordem-servico') {
            return $this->dadosExemploOrdemServico();
        }

        return $this->dadosExemploPadrao();
    }

    private function dadosExemploOrdemServico(): array
    {
        return [
            'id' => 108,
            'taskCode' => 'GV-OS-003',
            'titulo' => 'OS — Instalação de CTO',
            'categoria' => 'ordem-servico',
            'status' => 'Em andamento',
            'regiao' => 'Goval',
            'responsavel' => 'joao.silva, maria.santos',
            'descricao' => 'Instalar CTO na caixa 042 e realizar fusão das fibras.',
            'parent_task_id' => 42,
            'parent_task_code' => 'GV-ROM-001',
            'parent_titulo' => 'ROMPIMENTO - CTO-042',
            'parent_categoria' => 'rompimentos',
            'parent_categoria_label' => 'Rompimentos',
            'os_tipo' => 'Instalação de CTO',
            'is_parent_task' => false,
            'criadaEm' => '2026-07-01 09:00:00',
            'updated_at' => '2026-07-02 11:30:00',
        ];
    }

    private function dadosExemploPadrao(): array
    {
        $resumoOs = app(OsResumoChatService::class)->formatarBloco([
            'os_total' => 4,
            'os_finalizadas' => 4,
            'por_tecnico' => [
                'joao.silva' => 2,
                'maria.santos' => 2,
            ],
        ]);

        return [
            'id' => 42,
            'taskCode' => 'GV-ROM-001',
            'titulo' => 'Manutenção na CTO Centro',
            'categoria' => 'rompimentos',
            'status' => 'Em andamento',
            'setor' => 'CTO-042',
            'cto' => 'CTO-042',
            'regiao' => 'Goval',
            'responsavel' => 'joao.silva',
            'prioridade' => 'Alta',
            'prazo' => '2026-07-15',
            'clientesAfetados' => '12',
            'coordenadas' => '-18.8512, -41.9495',
            'localizacao_texto' => 'Rua Exemplo, 100 — Centro',
            'descricao' => 'Descrição de exemplo da tarefa.',
            'numero_os' => 'OS-12345',
            'ordem_servico' => 'OS-12345',
            'nome_cliente' => 'Maria Souza',
            'protocolo' => 'PROT-98765',
            'sub_processo' => 'Instalação',
            'data_entrada' => '2026-06-01',
            'data_instalacao' => '2026-06-10',
            'assinada_por' => 'tecnico.silva',
            'assinada_em' => '2026-06-10 14:30:00',
            'criadaEm' => '2026-06-01 09:00:00',
            'updated_at' => '2026-06-29 11:45:00',
            'active_duration_minutes' => 180,
            'parent_task_id' => null,
            'is_parent_task' => true,
            'historico' => 'Criada → Em andamento',
            'os_total' => '4',
            'os_finalizadas' => '4',
            'os_resumo_tecnicos' => "• joao.silva — 2 OS\n• maria.santos — 2 OS",
            'os_resumo' => $resumoOs,
        ];
    }

    private function dadosExemploTrocaEtiqueta(): array
    {
        $etiquetasExemplo = [
            ['nome' => 'IPE1504', 'coordenadas' => '-18.8512, -41.9495', 'endereco' => 'Rua Exemplo, 100 — Centro'],
            ['nome' => 'IPG1106', 'coordenadas' => '-18.8520, -41.9501', 'endereco' => 'Av. Brasil, 250 — Goval'],
            ['nome' => 'IPE2201', 'coordenadas' => '-18.8535, -41.9510', 'endereco' => 'Rua das Flores, 45 — Jardim'],
        ];

        return [
            'id' => 42,
            'taskCode' => 'GV-ETQ-001',
            'titulo' => 'IPE1504, IPG1106, IPE2201',
            'categoria' => 'troca-etiqueta',
            'status' => 'Em andamento',
            'setor' => 'CTO-042',
            'cto' => 'CTO-042',
            'regiao' => 'Goval',
            'responsavel' => 'joao.silva',
            'prioridade' => '—',
            'prazo' => '2026-07-15',
            'clientesAfetados' => '0',
            'coordenadas' => '-18.8512, -41.9495 | -18.8520, -41.9501 | -18.8535, -41.9510',
            'localizacao_texto' => 'Rua Exemplo, 100 — Centro | Av. Brasil, 250 — Goval | Rua das Flores, 45 — Jardim',
            'descricao' => TrocaEtiquetaParser::montarDescricao($etiquetasExemplo),
            'numero_os' => 'OS-12345',
            'ordem_servico' => 'OS-12345',
            'nome_cliente' => 'Maria Souza',
            'protocolo' => 'PROT-98765',
            'sub_processo' => 'Instalação',
            'data_entrada' => '2026-06-01',
            'data_instalacao' => '2026-06-10',
            'assinada_por' => 'tecnico.silva',
            'assinada_em' => '2026-06-10 14:30:00',
            'criadaEm' => '2026-06-01 09:00:00',
            'updated_at' => '2026-06-29 11:45:00',
            'active_duration_minutes' => 180,
            'parent_task_id' => null,
            'is_parent_task' => true,
            'historico' => 'Criada → Em andamento',
        ];
    }

    public function normalizarCategoria(?string $categoria): string
    {
        $categoria = strtolower(trim((string) $categoria));

        return match ($categoria) {
            'rompimento', 'rompimentos' => 'rompimentos',
            'troca-poste', 'troca de poste' => 'troca-poste',
            'troca-etiqueta', 'troca de etiqueta' => 'troca-etiqueta',
            'otimizacao-rede', 'otimizacao de rede', 'otimização de rede' => 'otimizacao-rede',
            'atendimento-cliente', 'atendimento ao cliente' => 'atendimento-cliente',
            'correcao-atenuacao', 'correção de atenuação', 'correcao-de-sinal', 'correção de sinal' => 'correcao-atenuacao',
            'manutencao-corretiva', 'manutenção corretiva' => 'manutencao-corretiva',
            'certificacao-cemig', 'certificação cemig' => 'certificacao-cemig',
            'ordem-servico' => 'ordem-servico',
            default => str_replace(' ', '-', $categoria),
        };
    }

    public function normalizarStatus(?string $status): string
    {
        $status = trim((string) $status);

        return match (strtolower(str_replace('_', ' ', $status))) {
            'criada' => 'Criada',
            'em andamento', 'em_andamento' => 'Em andamento',
            'impedimento' => 'Impedimento',
            'finalizada', 'finalizar', 'concluída', 'concluida' => 'Finalizada',
            'aberta' => 'Aberta',
            'pendente', 'backlog' => 'Pendente',
            'validação', 'validacao' => 'Validação',
            'precisa de adequação', 'precisa de adequacao' => 'Precisa de adequação',
            'concluído', 'concluido', 'finalizado' => 'Concluído',
            default => $status,
        };
    }

    /** @return array<string, string> */
    private function montarMapaPlaceholders(
        array $task,
        ?string $statusAnterior,
        ?string $statusNovo,
        ?string $enviadoPor,
    ): array {
        $numeroOs = trim((string) ($task['numero_os'] ?? ''));
        $ordemServico = trim((string) ($task['ordem_servico'] ?? ''));
        if ($numeroOs === '') {
            $numeroOs = $ordemServico;
        }
        if ($ordemServico === '') {
            $ordemServico = $numeroOs;
        }

        $setor = trim((string) ($task['setor'] ?? $task['cto'] ?? ''));
        $localizacaoTexto = trim((string) ($task['localizacao_texto'] ?? ''));
        $coordenadas = trim((string) ($task['coordenadas'] ?? ''));
        $localizacao = $localizacaoTexto !== '' ? $localizacaoTexto : $coordenadas;

        $clientes = trim((string) ($task['clientesAfetados'] ?? ''));
        if ($clientes === '') {
            $clientes = '0';
        }

        $categoria = $this->normalizarCategoria($task['categoria'] ?? '');
        $categoriaLabel = trim((string) (config("mensagens.categorias.{$categoria}.label", '')));
        if ($categoriaLabel === '') {
            $categoriaLabel = trim((string) ($task['categoria'] ?? '')) ?: '—';
        }

        $statusAtual = trim((string) ($statusNovo ?? $task['status'] ?? '')) ?: '—';

        $tituloOs = trim((string) ($task['titulo'] ?? ''));
        $osTipo = trim((string) ($task['os_tipo'] ?? ''));
        if ($osTipo === '' && preg_match('/^OS\s*[—\-]\s*(.+)$/iu', $tituloOs, $m)) {
            $osTipo = trim($m[1]);
        }
        if ($osTipo === '') {
            $osTipo = $tituloOs !== '' ? $tituloOs : '—';
        }

        $parentCategoria = $this->normalizarCategoria($task['parent_categoria'] ?? '');
        $parentCategoriaLabel = trim((string) ($task['parent_categoria_label'] ?? ''));
        if ($parentCategoriaLabel === '' && $parentCategoria !== '') {
            $parentCategoriaLabel = trim((string) (config("mensagens.categorias.{$parentCategoria}.label", '')));
        }
        if ($parentCategoriaLabel === '') {
            $parentCategoriaLabel = trim((string) ($task['parent_categoria'] ?? '')) ?: '—';
        }

        $etiquetasItens = TrocaEtiquetaParser::parseItensParaMensagem(
            $task['titulo'] ?? '',
            $task['descricao'] ?? '',
            $task['coordenadas'] ?? '',
            $task['localizacao_texto'] ?? '',
        );

        return [
            'id' => trim((string) ($task['id'] ?? '')) ?: '—',
            'task_code' => trim((string) ($task['taskCode'] ?? $task['id'] ?? '')) ?: '—',
            'titulo' => trim((string) ($task['titulo'] ?? '')) ?: '—',
            'categoria' => $categoria !== '' ? $categoria : '—',
            'categoria_label' => $categoriaLabel,
            'status' => $statusAtual,
            'status_anterior' => trim((string) ($statusAnterior ?? '')) ?: '—',
            'status_novo' => $statusAtual,
            'setor' => $setor !== '' ? $setor : '—',
            'cto' => $setor !== '' ? $setor : '—',
            'regiao' => trim((string) ($task['regiao'] ?? '')) ?: '—',
            'responsavel' => trim((string) ($task['responsavel'] ?? '')) ?: '—',
            'prioridade' => trim((string) ($task['prioridade'] ?? '')) ?: '—',
            'prazo' => $this->formatarValorData($task['prazo'] ?? null),
            'clientes_afetados' => $clientes,
            'coordenadas' => $coordenadas !== '' ? $coordenadas : '—',
            'localizacao' => $localizacao !== '' ? $localizacao : '—',
            'localizacao_texto' => $localizacaoTexto !== '' ? $localizacaoTexto : '—',
            'descricao' => trim((string) ($task['descricao'] ?? '')) ?: '—',
            'numero_os' => $numeroOs !== '' ? $numeroOs : '—',
            'ordem_servico' => $ordemServico !== '' ? $ordemServico : '—',
            'nome_cliente' => trim((string) ($task['nome_cliente'] ?? '')) ?: '—',
            'protocolo' => trim((string) ($task['protocolo'] ?? '')) ?: '—',
            'sub_processo' => trim((string) ($task['sub_processo'] ?? '')) ?: '—',
            'data_entrada' => $this->formatarValorData($task['data_entrada'] ?? null),
            'data_instalacao' => $this->formatarValorData($task['data_instalacao'] ?? null),
            'assinada_por' => trim((string) ($task['assinada_por'] ?? '')) ?: '—',
            'assinada_em' => $this->formatarValorData($task['assinada_em'] ?? null, true),
            'criada_em' => $this->formatarValorData($task['criadaEm'] ?? $task['created_at'] ?? null, true),
            'atualizada_em' => $this->formatarValorData($task['updated_at'] ?? null, true),
            'duracao_ativa' => isset($task['active_duration_minutes']) && $task['active_duration_minutes'] !== ''
                ? (string) $task['active_duration_minutes']
                : '—',
            'parent_task_id' => isset($task['parent_task_id']) && $task['parent_task_id'] !== null && $task['parent_task_id'] !== ''
                ? (string) $task['parent_task_id']
                : '—',
            'parent_task_code' => trim((string) ($task['parent_task_code'] ?? '')) ?: '—',
            'parent_titulo' => trim((string) ($task['parent_titulo'] ?? '')) ?: '—',
            'parent_categoria' => $parentCategoria !== '' ? $parentCategoria : '—',
            'parent_categoria_label' => $parentCategoriaLabel,
            'os_tipo' => $osTipo,
            'is_parent_task' => $this->formatarBooleano($task['is_parent_task'] ?? null),
            'historico' => app(OpTaskHistoricoService::class)->resumoParaTemplate($task['historico'] ?? null),
            'enviado_por' => trim((string) ($enviadoPor ?? '')) ?: '—',
            'etiquetas' => TrocaEtiquetaParser::formatarNomes($etiquetasItens),
            'etiquetas_localizacao' => TrocaEtiquetaParser::formatarLocalizacaoLista($etiquetasItens),
            'etiquetas_coordenadas' => TrocaEtiquetaParser::formatarCoordenadasLista($etiquetasItens),
            'os_total' => trim((string) ($task['os_total'] ?? '')) ?: '—',
            'os_finalizadas' => trim((string) ($task['os_finalizadas'] ?? '')) ?: '—',
            'os_resumo_tecnicos' => trim((string) ($task['os_resumo_tecnicos'] ?? '')) ?: '—',
            'os_resumo' => trim((string) ($task['os_resumo'] ?? '')) ?: '—',
        ];
    }

    private function ajustarStatusTemplate(string $categoria, string $status): string
    {
        if ($categoria === 'certificacao-cemig' && $status === 'Finalizada') {
            return 'Concluído';
        }

        if ($categoria === 'troca-etiqueta' && $status === 'Concluída') {
            return 'Finalizada';
        }

        return $status;
    }

    private function formatarValorData(mixed $valor, bool $comHora = false): string
    {
        if ($valor === null || $valor === '') {
            return '—';
        }

        if ($valor instanceof \DateTimeInterface) {
            return $valor->format($comHora ? 'd/m/Y H:i' : 'd/m/Y');
        }

        $texto = trim((string) $valor);
        if ($texto === '') {
            return '—';
        }

        try {
            $data = new \DateTimeImmutable($texto);

            return $data->format($comHora ? 'd/m/Y H:i' : 'd/m/Y');
        } catch (\Throwable) {
            return $texto;
        }
    }

    private function formatarBooleano(mixed $valor): string
    {
        if ($valor === null || $valor === '') {
            return '—';
        }

        return filter_var($valor, FILTER_VALIDATE_BOOLEAN) ? 'Sim' : 'Não';
    }
}
