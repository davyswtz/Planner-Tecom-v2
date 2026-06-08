<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('login');
});

Route::get('/login', function () {
    return view('login');
});

Route::get('/dashboard', function () {
    return view('dashboard');
});

Route::get('/rompimento', function () {
    return view('rompimento');
});

Route::get('/troca-de-poste', function () {
    return view('troca-de-poste');
});

Route::get('/otimizacao-de-rede', function () {
    return view('otimizacao-de-rede');
});