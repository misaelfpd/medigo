<?php

use App\Http\Controllers\BerandaController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\FaskesDetailController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DiscussionController;
use App\Http\Controllers\ChatWebController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminFaskesController;
use App\Http\Controllers\Admin\AdminDiscussionReportController;
use App\Http\Controllers\Admin\AdminForumController;
use App\Http\Controllers\Admin\AdminReportController;
use Illuminate\Support\Facades\Route;

// Public User Routes
Route::get('/', function () {
    return view('landing');
});
Route::get('/search', [SearchController::class, 'index'])->name('search.index');
Route::get('/search/data', [SearchController::class, 'searchData'])->name('search.data');
Route::get('/faskes/search', function () {
    return redirect()->route('search.index', request()->query());
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

// User Authentication Routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.post');
Route::match(['get', 'post'], '/logout', [AuthController::class, 'logout'])->name('logout');

// AI Chat
Route::get('/chat', [ChatWebController::class, 'index'])->name('chat');
Route::post('/chat', [ChatWebController::class, 'sendChat']);
// User Profile & Community Routes
Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
Route::get('/profile/comments', [ProfileController::class, 'getComments'])->name('profile.comments');
Route::get('/discussions', [DiscussionController::class, 'index'])->name('discussions');
Route::get('/discussions/create', [DiscussionController::class, 'create'])->name('discussions.create');
Route::post('/discussions', [DiscussionController::class, 'store'])->name('discussions.store');
Route::get('/discussions/{id_diskusi}', [DiscussionController::class, 'show'])->name('discussions.show');
Route::post('/discussions/{id_diskusi}/comments', [DiscussionController::class, 'reply'])->name('discussions.reply');
Route::delete('/discussion-comments/{id_tanggapan}', [DiscussionController::class, 'destroyComment'])->name('discussion-comments.destroy');
Route::delete('/discussions/{id_diskusi}', [DiscussionController::class, 'destroyDiscussion'])->name('discussions.destroy');
Route::post('/forum/report/{type}/{id}', [DiscussionController::class, 'report'])->name('forum.report');

// Admin Auth Routes
Route::get('/admin/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'login'])->name('admin.login.post');
Route::match(['get', 'post'], '/admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

// Admin Dashboard & Faskes Management Routes
Route::prefix('admin')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index']);
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    
    Route::get('/faskes', [AdminFaskesController::class, 'index'])->name('admin.faskes');
    Route::get('/faskes/create', [AdminFaskesController::class, 'create'])->name('admin.faskes.create');
    Route::get('/faskes/{id}/json', [AdminFaskesController::class, 'getFaskesJson'])->name('admin.faskes.json');
    Route::post('/faskes', [AdminFaskesController::class, 'store'])->name('admin.faskes.store');
    Route::put('/faskes/{id}', [AdminFaskesController::class, 'update'])->name('admin.faskes.update');
    Route::delete('/faskes/{id}', [AdminFaskesController::class, 'destroy'])->name('admin.faskes.destroy');
    Route::post('/fasilitas', [AdminFaskesController::class, 'storeFasilitas'])->name('admin.fasilitas.store');
    Route::post('/spesialisasi', [AdminFaskesController::class, 'storeSpesialisasi'])->name('admin.spesialisasi.store');
    
    // Rute Moderasi Forum Admin
    Route::get('/forum', [AdminForumController::class, 'index'])->name('admin.forum');
    Route::post('/forum/discussions/{id}/takedown', [AdminForumController::class, 'takedownDiscussion'])->name('admin.forum.discussions.takedown');
    Route::post('/forum/discussions/{id}/dismiss', [AdminForumController::class, 'dismissDiscussionReport'])->name('admin.forum.discussions.dismiss');
    Route::post('/forum/comments/{id}/takedown', [AdminForumController::class, 'takedownComment'])->name('admin.forum.comments.takedown');
    Route::post('/forum/comments/{id}/dismiss', [AdminForumController::class, 'dismissCommentReport'])->name('admin.forum.comments.dismiss');
    Route::post('/forum/users/{id}/block', [AdminForumController::class, 'blockUser'])->name('admin.forum.users.block');
    Route::post('/forum/discussions/{id}/report', [AdminForumController::class, 'adminReportDiscussion'])->name('admin.forum.discussions.report');
    Route::post('/forum/comments/{id}/report', [AdminForumController::class, 'adminReportComment'])->name('admin.forum.comments.report');

    Route::get('/discussion-reports', [AdminDiscussionReportController::class, 'index'])->name('admin.discussion-reports');
    Route::post('/discussions/{id}/takedown', [AdminDiscussionReportController::class, 'takedown'])->name('admin.discussions.takedown');
    // Rute menghapus komentar diskusi
    Route::delete('/discussion-comments/{id}', [App\Http\Controllers\DiscussionController::class, 'destroyComment']);

    // Rute Laporan Komentar & Masukan Data Faskes
    Route::get('/comment-reports', [AdminReportController::class, 'indexCommentReports'])->name('admin.comment-reports');
    Route::post('/comment-reports/{id}/takedown', [AdminReportController::class, 'takedownComment'])->name('admin.comment-reports.takedown');
    Route::post('/comment-reports/{id}/dismiss', [AdminReportController::class, 'dismissCommentReport'])->name('admin.comment-reports.dismiss');
    
    Route::get('/feedback-faskes', [AdminReportController::class, 'indexFeedbacks'])->name('admin.faskes-feedbacks');
    Route::put('/feedback-faskes/{id}/status', [AdminReportController::class, 'updateFeedbackStatus'])->name('admin.faskes-feedbacks.status');
});
