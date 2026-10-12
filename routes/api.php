<?php

use App\Http\Controllers\Api\C2ReportController;
use Illuminate\Support\Facades\Route;

Route::post('/v1/report', [C2ReportController::class, 'store']);