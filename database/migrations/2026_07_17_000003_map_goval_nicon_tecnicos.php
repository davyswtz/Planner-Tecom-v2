<?php

use App\Models\Tecnico;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /** @var array<int, array{nome: string, nicon_user_id: int, nicon_mention_name: string}> */
    private const MAPEAMENTO_GOVAL = [
        ['nome' => 'Gabriel Cantão', 'nicon_user_id' => 1432, 'nicon_mention_name' => 'EP GABRIEL CANTÃO VIANA'],
        ['nome' => 'Guilherme', 'nicon_user_id' => 1437, 'nicon_mention_name' => 'EP GUILHERME DOS SANTOS MENDES'],
        ['nome' => 'Leyzon', 'nicon_user_id' => 1474, 'nicon_mention_name' => 'EP LEYZON MAKSUEL RAMOS BRANDÃO'],
        ['nome' => 'Matheus Leite', 'nicon_user_id' => 1503, 'nicon_mention_name' => 'EP MATHEUS LEITE DE OLIVEIRA'],
        ['nome' => 'Tiago', 'nicon_user_id' => 1567, 'nicon_mention_name' => 'EP TIAGO BRANDAO BRAGA'],
        ['nome' => 'Lucas', 'nicon_user_id' => 1479, 'nicon_mention_name' => 'LUCAS SILVA'],
    ];

    public function up(): void
    {
        foreach (self::MAPEAMENTO_GOVAL as $item) {
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
        foreach (self::MAPEAMENTO_GOVAL as $item) {
            Tecnico::query()
                ->whereRaw('LOWER(nome) = ?', [mb_strtolower($item['nome'])])
                ->update([
                    'nicon_user_id' => null,
                    'nicon_mention_name' => null,
                ]);
        }
    }
};
