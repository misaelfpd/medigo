<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DiscussionCommentController;
use App\Http\Controllers\DiscussionController;
use App\Http\Controllers\DiscussionReportController;
use App\Http\Controllers\FaskesCommentController;
use App\Http\Controllers\FaskesController;
use App\Http\Controllers\FaskesFeedbackController;
use App\Http\Controllers\GeminiController;
use App\Http\Middleware\AdminMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Rute Publik
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Server-to-server only: the frontend exchanges a logged-in user's email for a
// token. Requires the shared X-Service-Key header (see AuthController).
Route::post('/auth/token-by-email', [AuthController::class, 'tokenByEmail']);

Route::get('/faskes', [FaskesController::class, 'index']);
Route::get('/faskes/{id}', [FaskesController::class, 'show']);
Route::get('/faskes/{faskes_id}/comments', [FaskesCommentController::class, 'index']);
Route::post('/faskes/{faskes_id}/feedback', [FaskesFeedbackController::class, 'store']);

// Rute Forum Diskusi (Public)
Route::get('/discussions', [DiscussionController::class, 'index']); // Lihat semua diskusi
Route::get('/discussions/{id}', [DiscussionController::class, 'show']); // Lihat 1 diskusi spesifik

// Rute Search
Route::get('/faskes/search', [FaskesController::class, 'searchFaskes']);

// Rute Terproteksi (Wajib punya Token / udah Login)
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    // Cek profil user yang lagi login
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/user/discussions', [DiscussionController::class, 'myDiscussions']);
    Route::get('/user/comments', [DiscussionController::class, 'myComments']);

    // ==========================================
    // 1. RUTE ULASAN FASILITAS KESEHATAN (TABEL `komentar`)
    // ==========================================
    Route::post('/faskes/{faskes_id}/comments', [FaskesCommentController::class, 'store']); // Tambah ulasan baru / rating ke faskes
    Route::put('/comments/{id}', [FaskesCommentController::class, 'update']);                // Ubah ulasan faskes milik sendiri
    Route::delete('/comments/{id}', [FaskesCommentController::class, 'destroy']);            // Hapus ulasan faskes milik sendiri / admin
    Route::get('/user/reviews', [FaskesCommentController::class, 'getUserReviews']);         // Ambil daftar ulasan faskes milik user yang login
    Route::post('/faskes-comments/{id}/report', [FaskesCommentController::class, 'report']); // Laporkan ulasan faskes yang melanggar aturan

    // ==========================================
    // 2. RUTE FORUM DISKUSI & BALASAN DISKUSI
    // ==========================================
    Route::post('/discussions', [DiscussionController::class, 'store']);                                   // Buat topik diskusi baru
    Route::delete('/discussions/{id}', [DiscussionController::class, 'destroy']);                          // Hapus (soft delete) diskusi sendiri / admin
    Route::post('/discussions/{id}/report', [DiscussionReportController::class, 'store']);                 // Laporkan topik diskusi yang melanggar aturan
    Route::post('/discussions/{discussion_id}/comments', [DiscussionCommentController::class, 'store']);   // Tambah balasan/komentar di topik diskusi
    Route::delete('/discussion-comments/{id}', [DiscussionCommentController::class, 'destroy']);          // Hapus balasan diskusi sendiri / admin
    // Catatan integrasi frontend: '/comments/{id}/report' digunakan untuk melaporkan balasan DISKUSI (tabel tanggapan_diskusi)
    Route::post('/comments/{id}/report', [DiscussionCommentController::class, 'report']);

    // GEMINI AI
    Route::post('/chat-ai', [GeminiController::class, 'chat']);

    // Rute Admin Dashboard Log (Wajib Login + Wajib Admin)
    Route::middleware(AdminMiddleware::class)->group(function () {
        Route::get('/admin/logs', [ActivityLogController::class, 'index']);
        Route::get('/admin/stats', [FaskesController::class, 'getAdminStats']);

        // Admin CRUD Faskes
        Route::post('/faskes', [FaskesController::class, 'store']);
        Route::put('/faskes/{id}', [FaskesController::class, 'update']);
        Route::delete('/faskes/{id}', [FaskesController::class, 'destroy']);

        Route::get('/admin/discussion-reports', [DiscussionReportController::class, 'indexAdmin']);
        Route::post('/admin/discussions/{id}/takedown', [DiscussionReportController::class, 'takedown']);

        // Admin Faskes Feedback & Comment Moderation
        Route::get('/admin/faskes-feedbacks', [FaskesFeedbackController::class, 'indexAdmin']);
        Route::put('/admin/faskes-feedbacks/{id}/status', [FaskesFeedbackController::class, 'updateStatus']);
        Route::get('/admin/faskes-comment-reports', [FaskesCommentController::class, 'indexAdminReports']);
        Route::post('/admin/faskes-comment-reports/{id}/takedown', [FaskesCommentController::class, 'takedownReportAdmin']);
        Route::post('/admin/faskes-comment-reports/{id}/dismiss', [FaskesCommentController::class, 'dismissReportAdmin']);
    });
});
