<?php

use Jevo\JRelations\Http\Controllers\ModuleController;
use Jevo\JRelations\Http\Controllers\RelationsController;
use Illuminate\Support\Facades\Route;

Route::name('jRelations.')->group(function (): void {
    Route::get('/', [ModuleController::class, 'index'])->name('index');
    Route::post('/types', [ModuleController::class, 'storeType'])->name('types.store');
    Route::post('/types/{type}/delete', [ModuleController::class, 'deleteType'])->name('types.delete');

    Route::get('/resources/search', [RelationsController::class, 'search'])->name('resources.search');
    Route::get('/resources/{resource}/relations', [RelationsController::class, 'index'])->name('resources.relations');
    Route::post('/resources/{resource}/relations/{type}', [RelationsController::class, 'sync'])->name('resources.relations.sync');
});
