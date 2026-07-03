<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->limparTabela('op_tasks', fn ($query) => $query->where('categoria', 'ordem-servico'));

        if (Schema::hasTable('os_tecnicos')) {
            $this->limparTabela('os_tecnicos');
        }
    }

    public function down(): void
    {
        // Irreversível: o prefixo antigo não deve ser restaurado.
    }

    private function limparTabela(string $tabela, ?callable $filtro = null): void
    {
        $query = DB::table($tabela)->where(function ($q) {
            $q->where('titulo', 'like', 'OS — %')
                ->orWhere('titulo', 'like', 'OS - %')
                ->orWhere('titulo', 'like', 'OS – %');
        });

        if ($filtro) {
            $filtro($query);
        }

        foreach ($query->get(['id', 'titulo']) as $registro) {
            $titulo = trim((string) preg_replace('/^OS\s*[—\-–]\s*/u', '', (string) $registro->titulo));

            if ($titulo === '' || $titulo === $registro->titulo) {
                continue;
            }

            DB::table($tabela)->where('id', $registro->id)->update(['titulo' => $titulo]);
        }
    }
};
