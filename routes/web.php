<?php

use App\Http\Controllers\Api\OpTaskAnexoController;
use Illuminate\Support\Facades\Route;

Route::get('/chat-img/{token}', [OpTaskAnexoController::class, 'imagemChat'])
    ->where('token', '[\w.\-]+')
    ->name('chat.anexo.imagem');

Route::get('/', function () {
    return view('login');
});

Route::get('/login', function () {
    return view('login');
});

Route::get('/dashboard', function () {
    return view('dashboard');
});

Route::get('/tarefas', function () {
    return view('tarefas');
});

Route::get('/rompimento', function () {
    return view('rompimento');
});

Route::get('/troca-de-poste', function () {
    return view('troca-de-poste');
});

Route::get('/troca-de-etiqueta', function () {
    return view('troca-de-etiqueta');
});

Route::get('/otimizacao-de-rede', function () {
    return view('otimizacao-de-rede');
});

Route::get('/atendimento', function () {
    return view('atendimento');
});

Route::get('/correcao-de-sinal', function () {
    return view('correcao-de-sinal');
});

Route::get('/manutencao-corretiva', function () {
    return view('manutencao-corretiva');
});

Route::get('/certificacao-cemig', function () {
    return view('certificacao-cemig');
});

Route::get('/ordem-de-servico', function () {
    return view('ordem-de-servico');
});

Route::get('/correcao-de-dados', function () {
    return view('correcao-de-dados');
});

Route::get('/usuarios', function () {
    return view('usuarios');
});

Route::get('/configuracoes', function () {
    return view('configuracoes');
});

Route::get('/mensagens', function () {
    return view('mensagens');
});

Route::get('/buscar-caixa', function () {
    return view('buscar-caixa', [
        'cidadesNicon' => config('services.nicon.cidades', []),
    ]);
});
