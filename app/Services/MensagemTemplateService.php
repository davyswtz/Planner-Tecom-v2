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
                    'statuses' => $meta['statuses'] ?? [],
                    'padroes' => $padroes[$key] ?? [],
                ];
            })
            ->values()
            ->all();
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

    public function dadosExemplo(): array
    {
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
        ];
    }

    public function normalizarCategoria(?string $categoria): string
    {
        $categoria = strtolower(trim((string) $categoria));

        return match ($categoria) {
            'rompimento', 'rompimentos' => 'rompimentos',
            'troca-poste', 'troca de poste' => 'troca-poste',
            'otimizacao-rede', 'otimizacao de rede', 'otimização de rede' => 'otimizacao-rede',
            'atendimento-cliente', 'atendimento ao cliente' => 'atendimento-cliente',
            'correcao-atenuacao', 'correção de atenuação' => 'correcao-atenuacao',
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
            'is_parent_task' => $this->formatarBooleano($task['is_parent_task'] ?? null),
            'historico' => trim((string) ($task['historico'] ?? '')) ?: '—',
            'enviado_por' => trim((string) ($enviadoPor ?? '')) ?: '—',
        ];
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
