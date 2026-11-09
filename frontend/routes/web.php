<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminFaskesController;
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

// Admin Auth Routes
Route::get('/admin/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'login'])->name('admin.login.post');
Route::match(['get', 'post'], '/admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

// Admin Dashboard & Faskes Management Routes
Route::prefix('admin')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index']);
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    
    Route::get('/faskes', [AdminFaskesController::class, 'index'])->name('admin.faskes');
    Route::post('/faskes', [AdminFaskesController::class, 'store'])->name('admin.faskes.store');
    Route::put('/faskes/{id}', [AdminFaskesController::class, 'update'])->name('admin.faskes.update');
    Route::delete('/faskes/{id}', [AdminFaskesController::class, 'destroy'])->name('admin.faskes.destroy');
});