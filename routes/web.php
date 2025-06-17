<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
});

Route::get('/task', function () {
    return view('task.task');
});

Route::post('/task/solution');
