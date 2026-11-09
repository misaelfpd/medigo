<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FaskesController;
use App\Http\Controllers\DiscussionController;
use App\Http\Controllers\DiscussionCommentController;
use App\Http\Controllers\FaskesCommentController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\DiscussionReportController;
use App\Http\Controllers\FaskesFeedbackController;
use App\Http\Middleware\AdminMiddleware;

// Rute Publik
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Server-to-server only: the frontend exchanges a logged-in user's email for a
// token. Requires the shared X-Service-Key header (see AuthController).
Route::post('/auth/token-by-email', [AuthController::class, 'tokenByEmail']);

Route::get('/faskes', [FaskesController::class, 'index']);
Route::get('/faskes/{id}', [FaskesController::class, 'show']);
Route::get('/faskes/{faskes_id}/comments', [FaskesCommentController::class, 'index']);
Route::post('/faskes', [FaskesController::class, 'store']);
Route::put('/faskes/{id}', [FaskesController::class, 'update']);
Route::delete('/faskes/{id}', [FaskesController::class, 'destroy']);
Route::get('/admin/stats', [FaskesController::class, 'getAdminStats']);
Route::post('/faskes/{faskes_id}/feedback', [FaskesFeedbackController::class, 'store']);

// Rute Forum Diskusi (Public)
Route::get('/discussions', [DiscussionController::class, 'index']); // Lihat semua diskusi
Route::get('/discussions/{id}', [DiscussionController::class, 'show']); // Lihat 1 diskusi spesifik

// Rute Terproteksi (Wajib punya Token / udah Login)
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    // Cek profil user yang lagi login
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/user/discussions', [DiscussionController::class, 'myDiscussions']);
    Route::get('/user/comments', [DiscussionController::class, 'myComments']);

    // Rute Kasih Rating & Review ke Faskes (CRUD)
    Route::post('/faskes/{faskes_id}/comments', [FaskesCommentController::class, 'store']);
    Route::put('/comments/{id}', [FaskesCommentController::class, 'update']);
    Route::delete('/comments/{id}', [FaskesCommentController::class, 'destroy']);
    Route::get('/user/reviews', [FaskesCommentController::class, 'getUserReviews']);

    // Rute Forum Diskusi (Protected)
    Route::post('/discussions', [DiscussionController::class, 'store']); // Bikin diskusi baru
    Route::delete('/discussions/{id}', [DiscussionController::class, 'destroy']); // Hapus diskusi sendiri

    // Rute Tambah Komentar di Diskusi
    Route::post('/discussions/{discussion_id}/comments', [DiscussionCommentController::class, 'store']);
    // Route untuk melaporkan komentar
    Route::post('/comments/{id}/report', [DiscussionCommentController::class, 'report']);
    Route::delete('/discussion-comments/{id}', [DiscussionCommentController::class, 'destroy']);
    // Rute Report Diskusi & Komentar Faskes
    Route::post('/discussions/{id}/report', [DiscussionReportController::class, 'store']);
    Route::post('/faskes-comments/{id}/report', [FaskesCommentController::class, 'report']);

    // GEMINI AI
    Route::post('/chat-ai', [App\Http\Controllers\GeminiController::class, 'chat']);

    // Rute Admin Dashboard Log (Wajib Login + Wajib Admin)
    Route::middleware(AdminMiddleware::class)->group(function () {
        Route::get('/admin/logs', [ActivityLogController::class, 'index']);
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

Route::post('/tes-ai', function (Request $request, App\Services\GeminiClient $gemini) {
    if (!$gemini->isConfigured()) {
        return response()->json(['status' => 'Belum dikonfigurasi', 'message' => 'Set GEMINI_API_KEY di .env lalu php artisan config:clear'], 503);
    }

    $request->validate([
        'prompt' => 'required|string|max:1000',
    ]);

    $started = microtime(true);

    try {
        [$model, $data] = $gemini->generate([
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [['text' => $request->query('prompt', $request->input('prompt'))]],
                ]
            ],
        ]);
    } catch (RuntimeException $e) {
        return response()->json([
            'status' => 'Gagal Nembus Google',
            'models_tried' => config('services.gemini.models'),
            'error_detail' => $e->getMessage(),
        ], 502);
    }

    return response()->json([
        'status' => 'Sukses',
        'model' => $model,
        'latency_ms' => (int) round((microtime(true) - $started) * 1000),
        'reply' => collect($data['candidates'][0]['content']['parts'] ?? [])
            ->reject(fn($part) => $part['thought'] ?? false)
            ->pluck('text')->filter()->implode(''),
        'finish_reason' => $data['candidates'][0]['finishReason'] ?? null,
    ]);
});