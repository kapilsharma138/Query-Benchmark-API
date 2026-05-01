<?php

use Illuminate\Support\Facades\Route;
use Modules\Reports\Http\Controllers\ReportsController;

Route::get('/compare',[ReportsController::class, 'compare']);

Route::get('/slowwithexplain',[ReportsController::class, 'slowwithexplain']);
Route::get('/optimizedwithexplain',[ReportsController::class, 'optimizedwithexplain']);
Route::get('/comparewithexplain',[ReportsController::class, 'comparewithexplain']);

Route::get('/cached-with-explain', [ReportsController::class, 'cachedWithExplain']);