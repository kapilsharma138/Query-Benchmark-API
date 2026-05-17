<?php

use Illuminate\Support\Facades\Route;
use Modules\Reports\Http\Controllers\ReportsController;

Route::get('/slow',[ReportsController::class, 'slow']);
Route::get('/optimized',[ReportsController::class, 'optimized']);
Route::get('/compare',[ReportsController::class, 'compare']);
Route::get('/slowusepaginate',[ReportsController::class, 'slowusepaginate']);
Route::get('/optimizedusepaginate',[ReportsController::class, 'optimizedusepaginate']);
Route::get('/compareusepaginate',[ReportsController::class, 'compareusepaginate']);

Route::get('/slowwithexplain',[ReportsController::class, 'slowwithexplain']);
Route::get('/optimizedwithexplain',[ReportsController::class, 'optimizedwithexplain']);
Route::get('/comparewithexplain',[ReportsController::class, 'comparewithexplain']);

Route::get('/cached-with-explain', [ReportsController::class, 'cachedWithExplain']);