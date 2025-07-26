<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LeadController;
use App\Http\Middleware\ApiTokenMiddleware;

Route::middleware([ApiTokenMiddleware::class])->group(function () {
    Route::post('/leads', [LeadController::class, 'store']);
    Route::get('/leads', [LeadController::class, 'index']);
    Route::get('/leads/{id}', [LeadController::class, 'show']);
});
