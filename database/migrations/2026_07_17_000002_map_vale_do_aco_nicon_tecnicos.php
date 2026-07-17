<?php

use App\Models\Tecnico;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /** @var array<int, array{nome: string, nicon_user_id: int, nicon_mention_name: string}> */
    private const MAPEAMENTO_VALE_DO_ACO = [
        ['nome' => 'Arrhenius', 'nicon_user_id' => 1377, 'nicon_mention_name' => 'EP ARRHENIUS LUCAS COSTA MENEZES'],
        ['nome' => 'Carlos', 'nicon_user_id' => 1390, 'nicon_mention_name' => 'CARLOS HENRIQUE DA SILVA MENDES'],
        ['nome' => 'Eduardo', 'nicon_user_id' => 1405, 'nicon_mention_name' => 'EP EDUARDO HENRIQUE LAUDISLAL VIEIRA'],
        ['nome' => 'Hugo', 'nicon_user_id' => 1445, 'nicon_mention_name' => 'EP HUGO HENRIQUE DA SILVA SOUZA'],
        ['nome' => 'Messias', 'nicon_user_id' => 1510, 'nicon_mention_name' => 'EP MESSIAS VITOR ASSIS CRUZ'],
        ['nome' => 'Reginaldo', 'nicon_user_id' => 1537, 'nicon_mention_name' => 'EP REGINALDO DA SILVA DIAS'],
        ['nome' => 'Kallyl', 'nicon_user_id' => 1541, 'nicon_mention_name' => 'EP ROBERTO KALLYL CARDOZO DE OLIVEIRA'],
        ['nome' => 'Roberto Kallyl', 'nicon_user_id' => 1541, 'nicon_mention_name' => 'EP ROBERTO KALLYL CARDOZO DE OLIVEIRA'],
        ['nome' => 'Weignon', 'nicon_user_id' => 1578, 'nicon_mention_name' => 'EP WEIGNON RODRIGUES FRAGA'],
    ];

    public function up(): void
    {
        foreach (self::MAPEAMENTO_VALE_DO_ACO as $item) {
            Tecnico::query()
                ->whereRaw('LOWER(nome) = ?', [mb_strtolower($item['nome'])])
                ->update([
                    'nicon_user_id' => $item['nicon_user_id'],
                    'nicon_mention_name' => $item['nicon_mention_name'],
                ]);
        }
    }

    public function down(): void
    {
        foreach (self::MAPEAMENTO_VALE_DO_ACO as $item) {
            // Arrhenius permanece pelo migration anterior; demais limpam
            if (mb_strtolower($item['nome']) === 'arrhenius') {
                continue;
            }
            Tecnico::query()
                ->whereRaw('LOWER(nome) = ?', [mb_strtolower($item['nome'])])
                ->update([
                    'nicon_user_id' => null,
                    'nicon_mention_name' => null,
                ]);
        }
    }
};
