<?php
namespace App\Services;
use App\Models\OpTask;
class OpTaskService
{
    public function getOpTasks(int $limit = 40, string $orderBy = 'updated_at', string $order = 'desc')
    {
        return OpTask::orderBy($orderBy, $order)->limit($limit)->get();
    }

    public function createOpTask(array $dados): OpTask{
        $dados['taskCode'] = $this->gerarTaskCode($dados);
        return OpTask::create($dados);
    }

    public function showOpTask(OpTask $opTask){
        return $opTasks;
    }

    public function updateOpTask(OpTask $opTask, array $dados){
        return $opTask;
    }

    public function deleteOpTask(OpTask $opTask){
        $opTask->delete();
        return $opTask;
    }

    private array $regioes = [
        'Goval' => 'GV',
        'goval' => 'GV',
        'Vale do Aço' => 'VA',
        'vale do aco' => 'VA',
        'Caratinga' => 'CA',
        'caratinga' => 'CA',
    ];

    private array $categorias = [
        'rompimentos'           => 'ROM',
        'troca-poste'           => 'TRO',
        'troca de poste'        => 'TRO',
        'otimizacao-rede'       => 'OTM',
        'otimização de rede'    => 'OTM',
        'certificacao-cemig'    => 'CER',
        'certificação cemig'    => 'CER',
        'atendimento-cliente'   => 'ATE',
        'atendimento ao cliente'=> 'ATE',
        'manutencao-corretiva'  => 'MAN',
        'manutenção corretiva'  => 'MAN',
        'correcao-atenuacao'    => 'COR',
        'correção de atenuação' => 'COR',
        'troca-etiqueta'        => 'ETQ',
        'troca de etiqueta'     => 'ETQ',
        'qualidade-potencia'    => 'QUA',
        'qualidade de potencia' => 'QUA',
        'sem-categoria'         => 'GEN',
    ];



    private function gerarTaskCode(array $dados): string
{
    $regiao = strtolower(trim($dados['regiao'] ?? ''));
    $siglaRegiao = $this->regioes[$regiao] ?? 'XX';
    $categoria = strtolower(trim($dados['categoria'] ?? ''));
    $siglaCategoria = $this->categorias[$categoria] ?? 'GV';
    $prefixo = $siglaRegiao . '-' . $siglaCategoria;
    $ultimo = OpTask::where('taskCode', 'like', $prefixo . '-%')
        ->orderBy('id', 'desc')
        ->value('taskCode');
    if ($ultimo) {
        $numero = (int) substr($ultimo, strrpos($ultimo, '-') + 1);
        $numero++;
    } else {
        $numero = 1;
    }
    return $prefixo . '-' . str_pad($numero, 3, '0', STR_PAD_LEFT);
}
}
