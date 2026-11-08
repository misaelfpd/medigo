<?php

use App\Http\Controllers\BerandaController;
use App\Http\Controllers\FaskesDetailController;
use App\Http\Controllers\MapController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/faskes', [BerandaController::class, 'index'])->name('faskes.index');
Route::get('/faskes/load-more', [BerandaController::class, 'loadMore'])->name('faskes.loadMore');
Route::get('/faskes/{id}', [FaskesDetailController::class, 'show'])->name('faskes.detail');
Route::get('/faskes/{id}/comments', [FaskesDetailController::class, 'getComments'])->name('faskes.comments');
Route::post('/faskes/{id}/comments', [FaskesDetailController::class, 'storeComment'])->name('faskes.comments.store');
Route::put('/faskes/{id}/comments/{commentId}', [FaskesDetailController::class, 'updateComment'])->name('faskes.comments.update');
Route::delete('/faskes/{id}/comments/{commentId}', [FaskesDetailController::class, 'destroyComment'])->name('faskes.comments.destroy');
Route::post('/faskes/{id}/comments/{commentId}/report', [FaskesDetailController::class, 'reportComment'])->name('faskes.comments.report');
Route::post('/faskes/{id}/feedback', [FaskesDetailController::class, 'storeFeedback'])->name('faskes.feedback.store');
Route::delete('/reviews/{commentId}', [FaskesDetailController::class, 'destroyReview'])->name('reviews.destroy');
Route::get('/map', [MapController::class, 'index'])->name('map.index');
