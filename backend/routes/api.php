<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AdminForumModerationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DiscussionCommentController;
use App\Http\Controllers\DiscussionController;
use App\Http\Controllers\DiscussionReportController;
use App\Http\Controllers\FaskesCommentController;
use App\Http\Controllers\FaskesController;
use App\Http\Controllers\FaskesFeatureController;
use App\Http\Controllers\FaskesFeedbackController;
use App\Http\Controllers\ForumController;
use App\Http\Controllers\ForumLikeController;
use App\Http\Controllers\FotoFaskesController;
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

// Rute Search & Filter Faskes (Harus didefinisikan sebelum /faskes/{id})
Route::get('/faskes/search', [FaskesController::class, 'searchFaskes']);
Route::get('/faskes/filter-options', [FaskesController::class, 'getFilterOptions']);
// Pencarian faskes untuk tombol mention di judul diskusi
Route::get('/faskes/mention-search', [ForumController::class, 'mentionSearch']);

Route::get('/faskes', [FaskesController::class, 'index']);
Route::get('/faskes/{id}', [FaskesController::class, 'show']);
Route::get('/faskes/{id}/features', [FaskesFeatureController::class, 'getFacilitiesAndSpecialties']);
Route::get('/faskes/{id}/fasilitas', [FaskesFeatureController::class, 'getFacilities']);
Route::get('/faskes/{id}/spesialisasi', [FaskesFeatureController::class, 'getSpecialties']);
Route::get('/faskes/{id}/foto', [FotoFaskesController::class, 'index']); // Foto faskes: thumbnail, banner, galeri
Route::get('/fasilitas', [FaskesFeatureController::class, 'getAllFacilities']);
Route::get('/spesialisasi', [FaskesFeatureController::class, 'getAllSpecialties']);
Route::get('/faskes/{faskes_id}/comments', [FaskesCommentController::class, 'index']);
Route::post('/faskes/{faskes_id}/feedback', [FaskesFeedbackController::class, 'store']);
Route::post('/faskes/{faskes_id}/report', [FaskesFeedbackController::class, 'store']);

// Rute Forum Diskusi (Public)
Route::get('/discussions', [DiscussionController::class, 'index']); // Lihat semua diskusi
Route::get('/discussions/{id}', [DiscussionController::class, 'show']); // Lihat 1 diskusi spesifik
Route::get('/forum/faskes-terdekat', [ForumController::class, 'nearbyFaskes']); // Rekomendasi faskes terdekat (urut like)
Route::get('/forum/diskusi-terdekat', [ForumController::class, 'nearbyDiscussions']); // Diskusi yang membahas faskes di sekitar user

// GEMINI AI
Route::get('/chat-ai/history', [GeminiController::class, 'history']);
Route::post('/chat-ai', [GeminiController::class, 'chat']);

// Rute Terproteksi (Wajib punya Token / udah Login)
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    // Cek profil user yang lagi login
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::get('/user/profile', [AuthController::class, 'profile']);
    Route::put('/user', [AuthController::class, 'updateProfile']);
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);
    Route::post('/user/profile', [AuthController::class, 'updateProfile']);

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
    Route::post('/discussion-comments/{id}/report', [DiscussionCommentController::class, 'report']);

    // Like & unlike forum (1 user = 1 like, tidak bisa like milik sendiri)
    Route::post('/discussions/{id}/like', [ForumLikeController::class, 'likeDiscussion']);
    Route::delete('/discussions/{id}/like', [ForumLikeController::class, 'unlikeDiscussion']);
    Route::post('/discussion-comments/{id}/like', [ForumLikeController::class, 'likeComment']);
    Route::delete('/discussion-comments/{id}/like', [ForumLikeController::class, 'unlikeComment']);

    // Rute Admin Dashboard Log (Wajib Login + Wajib Admin)
    Route::middleware(AdminMiddleware::class)->group(function () {
        Route::get('/admin/logs', [ActivityLogController::class, 'index']);
        Route::get('/admin/stats', [FaskesController::class, 'getAdminStats']);
        Route::get('/admin/pending-counts', [FaskesController::class, 'getPendingCounts']); // Badge jumlah laporan baru di sidebar admin

        // Admin CRUD Faskes
        Route::post('/faskes', [FaskesController::class, 'store']);
        Route::put('/faskes/{id}', [FaskesController::class, 'update']);
        Route::delete('/faskes/{id}', [FaskesController::class, 'destroy']);

        // Admin CRUD Foto Faskes (otomatis dikonversi ke WebP)
        Route::post('/faskes/{id}/foto', [FotoFaskesController::class, 'store']);
        Route::post('/faskes/{id}/foto/{fotoId}', [FotoFaskesController::class, 'update']); // multipart (ganti file)
        Route::put('/faskes/{id}/foto/{fotoId}', [FotoFaskesController::class, 'update']);  // JSON (keterangan/urutan)
        Route::delete('/faskes/{id}/foto/{fotoId}', [FotoFaskesController::class, 'destroy']);
        Route::post('/faskes/{id}/foto/{fotoId}/jadikan', [FotoFaskesController::class, 'setAs']); // galeri -> thumbnail/banner

        // Admin Master Fasilitas & Spesialisasi
        Route::post('/fasilitas', [FaskesFeatureController::class, 'storeFacility']);
        Route::post('/admin/fasilitas', [FaskesFeatureController::class, 'storeFacility']);
        Route::post('/spesialisasi', [FaskesFeatureController::class, 'storeSpecialty']);
        Route::post('/admin/spesialisasi', [FaskesFeatureController::class, 'storeSpecialty']);

        // 1. Admin Moderasi Laporan Diskusi (Topik)
        Route::get('/admin/discussion-reports', [DiscussionReportController::class, 'indexAdmin']);
        Route::get('/admin/discussion-reports/{id}', [DiscussionReportController::class, 'show']);
        Route::post('/admin/discussions/{id}/takedown', [DiscussionReportController::class, 'takedown']);
        Route::post('/admin/discussion-reports/{id}/takedown', [DiscussionReportController::class, 'takedown']);
        Route::post('/admin/discussions/{id}/dismiss', [DiscussionReportController::class, 'dismiss']);
        Route::post('/admin/discussion-reports/{id}/dismiss', [DiscussionReportController::class, 'dismiss']);
        Route::delete('/admin/discussion-reports/{id}', [DiscussionReportController::class, 'destroy']);

        // 2. Admin Moderasi Laporan Tanggapan / Komentar Diskusi
        Route::get('/admin/discussion-comment-reports', [DiscussionCommentController::class, 'indexAdminReports']);
        Route::get('/admin/discussion-comment-reports/{id}', [DiscussionCommentController::class, 'showReportAdmin']);
        Route::post('/admin/discussion-comments/{id}/takedown', [DiscussionCommentController::class, 'takedownReportAdmin']);
        Route::post('/admin/discussion-comment-reports/{id}/takedown', [DiscussionCommentController::class, 'takedownReportAdmin']);
        Route::post('/admin/discussion-comments/{id}/dismiss', [DiscussionCommentController::class, 'dismissReportAdmin']);
        Route::post('/admin/discussion-comment-reports/{id}/dismiss', [DiscussionCommentController::class, 'dismissReportAdmin']);

        // 3. Admin Moderasi Masukan / Laporan Data Faskes
        Route::get('/admin/faskes-feedbacks', [FaskesFeedbackController::class, 'indexAdmin']);
        Route::get('/admin/faskes-feedbacks/{id}', [FaskesFeedbackController::class, 'show']);
        Route::put('/admin/faskes-feedbacks/{id}/status', [FaskesFeedbackController::class, 'updateStatus']);
        Route::delete('/admin/faskes-feedbacks/{id}', [FaskesFeedbackController::class, 'destroy']);

        // 4. Admin Moderasi Laporan Komentar / Ulasan Faskes
        Route::get('/admin/faskes-comment-reports', [FaskesCommentController::class, 'indexAdminReports']);
        Route::get('/admin/faskes-comment-reports/{id}', [FaskesCommentController::class, 'showReportAdmin']);
        Route::post('/admin/faskes-comment-reports/{id}/takedown', [FaskesCommentController::class, 'takedownReportAdmin']);
        Route::post('/admin/faskes-comment-reports/{id}/dismiss', [FaskesCommentController::class, 'dismissReportAdmin']);

        // 5. Admin Moderasi Forum Komprehensif (Antrean Moderasi, Tren, Terbaru, Dilaporkan, Blokir User)
        Route::get('/admin/forum/moderation', [AdminForumModerationController::class, 'getModerationData']);
        Route::post('/admin/forum/discussions/{id}/takedown', [AdminForumModerationController::class, 'takedownDiscussion']);
        Route::post('/admin/forum/discussions/{id}/dismiss', [AdminForumModerationController::class, 'dismissDiscussion']);
        Route::post('/admin/forum/comments/{id}/takedown', [AdminForumModerationController::class, 'takedownComment']);
        Route::post('/admin/forum/comments/{id}/dismiss', [AdminForumModerationController::class, 'dismissComment']);
        Route::post('/admin/forum/users/{id}/block', [AdminForumModerationController::class, 'blockUser']);
        Route::post('/admin/forum/discussions/{id}/report', [AdminForumModerationController::class, 'reportDiscussion']);
        Route::post('/admin/forum/comments/{id}/report', [AdminForumModerationController::class, 'reportComment']);
    });
});
