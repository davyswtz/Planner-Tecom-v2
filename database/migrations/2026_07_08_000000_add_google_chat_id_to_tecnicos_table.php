<?php

use App\Models\Tecnico;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<int, array{nome: string, google_chat_id: string, regiao: string}> */
    private const MAPEAMENTO_GOOGLE_CHAT = [
        // TIME_GOVAL
        ['nome' => 'Diogo', 'google_chat_id' => '108550026877105275192', 'regiao' => 'Goval'],
        ['nome' => 'Leyzon', 'google_chat_id' => '106401946499967744380', 'regiao' => 'Goval'],
        ['nome' => 'Tiago', 'google_chat_id' => '101380783980574935265', 'regiao' => 'Goval'],
        ['nome' => 'Matheus Leite', 'google_chat_id' => '108878826481798176302', 'regiao' => 'Goval'],
        ['nome' => 'Lucas', 'google_chat_id' => '104890974179693995001', 'regiao' => 'Goval'],
        ['nome' => 'Isak', 'google_chat_id' => '108767000765958552234', 'regiao' => 'Goval'],
        ['nome' => 'Guilherme', 'google_chat_id' => '110674011987336259927', 'regiao' => 'Goval'],
        ['nome' => 'Gabriel Cantão', 'google_chat_id' => '108676605328960824173', 'regiao' => 'Goval'],
        // TIME_ACO
        ['nome' => 'Carlos', 'google_chat_id' => '116570300630830665670', 'regiao' => 'Vale do Aço'],
        ['nome' => 'Wallison', 'google_chat_id' => '108816543518917361378', 'regiao' => 'Vale do Aço'],
        ['nome' => 'Messias', 'google_chat_id' => '107729755364477461933', 'regiao' => 'Vale do Aço'],
        ['nome' => 'Roberto Kallyl', 'google_chat_id' => '116868701156027259229', 'regiao' => 'Vale do Aço'],
        ['nome' => 'Kallyl', 'google_chat_id' => '116868701156027259229', 'regiao' => 'Vale do Aço'],
        ['nome' => 'Arrhenius', 'google_chat_id' => '104672635607071026724', 'regiao' => 'Vale do Aço'],
        ['nome' => 'Thales', 'google_chat_id' => '114670511005082185491', 'regiao' => 'Vale do Aço'],
        ['nome' => 'Weignon', 'google_chat_id' => '102567325876582077098', 'regiao' => 'Vale do Aço'],
        ['nome' => 'Eduardo', 'google_chat_id' => '113773984468601459304', 'regiao' => 'Vale do Aço'],
        ['nome' => 'Hugo', 'google_chat_id' => '112666079684600011906', 'regiao' => 'Vale do Aço'],
        ['nome' => 'Reginaldo', 'google_chat_id' => '106260606388411799911', 'regiao' => 'Vale do Aço'],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('tecnicos')) {
            return;
        }

        if (! Schema::hasColumn('tecnicos', 'google_chat_id')) {
            Schema::table('tecnicos', function (Blueprint $table) {
                $table->string('google_chat_id', 32)->nullable()->after('regiao');
                $table->index('google_chat_id');
            });
        }

        foreach (self::MAPEAMENTO_GOOGLE_CHAT as $item) {
            $tecnico = Tecnico::query()
                ->whereRaw('LOWER(nome) = ?', [mb_strtolower($item['nome'])])
                ->first();

            if ($tecnico) {
                $tecnico->update(['google_chat_id' => $item['google_chat_id']]);

                continue;
            }

            Tecnico::query()->create([
                'nome' => $item['nome'],
                'username' => null,
                'regiao' => $item['regiao'],
                'google_chat_id' => $item['google_chat_id'],
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('tecnicos') || ! Schema::hasColumn('tecnicos', 'google_chat_id')) {
            return;
        }

        Schema::table('tecnicos', function (Blueprint $table) {
            $table->dropIndex(['google_chat_id']);
            $table->dropColumn('google_chat_id');
        });
    }
};
