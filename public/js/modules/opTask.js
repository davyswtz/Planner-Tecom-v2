import { getUrl, postUrl, putUrl, destroyUrl } from '../api/client.js';

async function listarTarefas(opts = {}){
    const params = new URLSearchParams();
    if (opts.limit) params.set('limit', String(opts.limit));
    if (opts.categoria) params.set('categoria', opts.categoria);
    if (opts.minhas) params.set('minhas', '1');
    const qs = params.toString();
    return getUrl('op-tasks' + (qs ? '?' + qs : ''));
}

async function criarTarefa(dados){
    return postUrl('op-tasks', dados);
}

async function atualizarTarefa(id, dados){
    return putUrl('op-tasks/' + id, dados);
}

async function deletarTarefa(id){
    return destroyUrl('op-tasks/' + id);
}

export { listarTarefas, criarTarefa, atualizarTarefa, deletarTarefa };