<?php

namespace App\Services;
use App\Models\OpTask;
use App\Services\GoogleChatService;
use Illuminate\Support\Facades\Http;

class TrocaDePosteService{
    public function __construct(
        private OpTaskService $opTaskService,
        private GoogleChatService $googleChatService
    ){}


    public function getTrocaDePoste(int $limit = 10, int $offset = 0, string $status = null, string $regiao = null, string $tecnico = null, string $taskCode = null, string $dataInicio = null, string $dataFim = null){
        $query = OpTask::tarefasPai('troca-poste')
        ->orderBy('updated_at', 'desc')
        ->when($status, fn($q) => $q->where('status', $status))
        ->when($regiao, fn($q) => $q->where('regiao', $regiao))
        ->when($tecnico, fn($q) => $q->where('responsavel', 'like', "%{$tecnico}%"))
        ->when($taskCode, fn($q) => $q->where('taskCode', $taskCode))
        ->when($dataInicio, fn($q) => $q->whereDate('criadaEm', '>=', $dataInicio))
        ->when($dataFim, fn($q) => $q->whereDate('criadaEm', '<=', $dataFim));

        if($status === 'Finalizada'){
            return $query->limit(1000)->offset($offset)->get();
        }

        return $query->limit($limit)->offset($offset)->get();
    }

    public function showTrocaDePoste(OpTask $opTask): OpTask{
        $dados['categoria'] = 'troca-poste';
        return $opTask;
    }

    public function createTrocaDePoste(array $dados): OpTask{
        $dados['categoria'] = 'troca-poste';
        $dados['taskCode'] = $this->opTaskService->gerarTaskCode($dados);
        $task = OpTask::create($dados);
        return $task;
    }

    public function buscarEndereco(string $coordenada): string{
        $partes = explode(',', $coordenada);
        $lat = $partes[0];
        $lng = $partes[1];

        $response = Http::withHeaders([
            'User-Agent' => 'Planner/1.0'
        ])->get('https://nominatim.openstreetmap.org/reverse', [
            'lat'    => $lat,
            'lon'    => $lng,
            'format' => 'json'
        ]);

        return $response->json('display_name') ?? 'Endereço não encontrado';
    }

    public function updateTrocaDePoste(OpTask $trocadeposte, array $dados): OpTask{
        $statusAnterior = $trocadeposte->status;
        if (isset($dados['status']) && $dados['status'] === 'Finalizada') {
            $osPendentes = OpTask::where('parent_task_id', $trocadeposte->id)
                ->where('status', '!=', 'Finalizada')
                ->count();
            if ($osPendentes > 0) {
                abort(422, 'Finalize todas as OS antes de finalizar a troca de poste');
            }
        }
        $trocadeposte->update($dados);
        if (isset($dados['status']) && $dados['status'] !== $statusAnterior) {
            $tarefaAtualizada = $trocadeposte->fresh();
            $mensagem = $this->googleChatService->montarMensagemTrocaDePoste(
                $tarefaAtualizada->toArray(),
                $statusAnterior,
                $dados['status']
            );
            $googleChatService = $this->googleChatService;
            app()->terminating(function () use ($tarefaAtualizada, $mensagem, $googleChatService) {
                $googleChatService->enviarNotificacao($tarefaAtualizada, $mensagem);
            });
        }
        return $trocadeposte->fresh();
    }

    public function deleteTrocaDePoste(OpTask $trocadeposte): void {
        OpTask::where('parent_task_id', $trocadeposte->id)
            ->where('categoria', 'ordem-servico')
            ->delete();
        $trocadeposte->delete();
    }

}
