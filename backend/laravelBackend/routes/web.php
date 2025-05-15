<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Redirect;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/show-image', function () {
    return view('show-image');
});


Route::get('/redis-config', function () {
    try {
        $config = config('database.redis');
        $info = [
            'client' => config('database.redis.client'),
            'default' => config('database.redis.default'),
            'options' => config('database.redis.options'),
        ];
        
        return response()->json($info);
    } catch (\Exception $e) {
        return "Error: " . $e->getMessage();
    }
});


    Route::get('/test-comments/{taskId}', function ($taskId) {
        return view('test-comment', ['taskId' => $taskId]);
    });


Route::get('/api/reset-password/{token}', function ($token) {
    return Redirect::to("http://localhost:5173/reset-password/$token");
});

