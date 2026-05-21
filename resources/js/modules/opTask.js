import { getUrl, postUrl, putUrl, destroyUrl } from '../api/client.js';

async function listarTarefas(){
    return getUrl('op-tasks');
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