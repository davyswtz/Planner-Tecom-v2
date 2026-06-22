<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('usuario_permissoes')) {
            return;
        }

        DB::table('usuario_permissoes')
            ->where('permissao', 'conectar_webhook')
            ->update(['permissao' => 'adicionar_webhook']);

        DB::table('usuario_permissoes')
            ->whereNotIn('permissao', ['visualizar_aba_tarefas', 'adicionar_webhook'])
            ->delete();
    }

    public function down(): void
    {
        // Permissões antigas não são restauradas automaticamente.
    }
};
