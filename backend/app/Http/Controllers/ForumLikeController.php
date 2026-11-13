<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Like & unlike untuk diskusi dan komentar forum.
 * Aturan: wajib login, satu user hanya bisa like 1x per diskusi/komentar,
 * dan tidak bisa like diskusi/komentar milik sendiri.
 */
class ForumLikeController extends Controller
{
    // POST /api/discussions/{id}/like
    public function likeDiscussion(Request $request, $id): JsonResponse
    {
        $discussion = DB::table('diskusi')->where('id_diskusi', $id)->where('status_diskusi', 'active')->first();
        if (! $discussion) {
            return response()->json(['status' => 'error', 'message' => 'Diskusi tidak ditemukan atau telah dihapus.'], 404);
        }

        $userId = (int) $request->user()->id_user;
        if ((int) $discussion->id_user === $userId) {
            return response()->json(['status' => 'error', 'message' => 'Kamu tidak bisa menyukai diskusi milikmu sendiri.'], 403);
        }

        // insertOrIgnore + unique index: aman walau tombol like ditekan dua kali bersamaan
        $inserted = DB::table('like_diskusi')->insertOrIgnore([
            'id_diskusi' => $id,
            'id_user' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->respond(
            $inserted > 0,
            'Diskusi disukai.',
            'Kamu sudah menyukai diskusi ini.',
            true,
            self::countDiscussionLikes($id)
        );
    }

    // DELETE /api/discussions/{id}/like
    public function unlikeDiscussion(Request $request, $id): JsonResponse
    {
        if (! DB::table('diskusi')->where('id_diskusi', $id)->where('status_diskusi', 'active')->exists()) {
            return response()->json(['status' => 'error', 'message' => 'Diskusi tidak ditemukan atau telah dihapus.'], 404);
        }

        $deleted = DB::table('like_diskusi')
            ->where('id_diskusi', $id)
            ->where('id_user', $request->user()->id_user)
            ->delete();

        return $this->respond(
            $deleted > 0,
            'Batal menyukai diskusi.',
            'Kamu belum menyukai diskusi ini.',
            false,
            self::countDiscussionLikes($id)
        );
    }

    // POST /api/discussion-comments/{id}/like
    public function likeComment(Request $request, $id): JsonResponse
    {
        $comment = $this->activeComment($id);
        if (! $comment) {
            return response()->json(['status' => 'error', 'message' => 'Komentar tidak ditemukan atau diskusinya telah dihapus.'], 404);
        }

        $userId = (int) $request->user()->id_user;
        if ((int) $comment->id_user === $userId) {
            return response()->json(['status' => 'error', 'message' => 'Kamu tidak bisa menyukai komentarmu sendiri.'], 403);
        }

        $inserted = DB::table('like_tanggapan')->insertOrIgnore([
            'id_tanggapan' => $id,
            'id_user' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->respond(
            $inserted > 0,
            'Komentar disukai.',
            'Kamu sudah menyukai komentar ini.',
            true,
            self::countCommentLikes($id)
        );
    }

    // DELETE /api/discussion-comments/{id}/like
    public function unlikeComment(Request $request, $id): JsonResponse
    {
        if (! $this->activeComment($id)) {
            return response()->json(['status' => 'error', 'message' => 'Komentar tidak ditemukan atau diskusinya telah dihapus.'], 404);
        }

        $deleted = DB::table('like_tanggapan')
            ->where('id_tanggapan', $id)
            ->where('id_user', $request->user()->id_user)
            ->delete();

        return $this->respond(
            $deleted > 0,
            'Batal menyukai komentar.',
            'Kamu belum menyukai komentar ini.',
            false,
            self::countCommentLikes($id)
        );
    }

    private function activeComment($id): ?object
    {
        return DB::table('tanggapan_diskusi')
            ->join('diskusi', 'tanggapan_diskusi.id_diskusi', '=', 'diskusi.id_diskusi')
            ->where('tanggapan_diskusi.id_tanggapan', $id)
            ->where('diskusi.status_diskusi', 'active')
            ->select('tanggapan_diskusi.*')
            ->first();
    }

    /**
     * $changed false berarti user sudah like (saat like) atau belum like (saat unlike) -> 409.
     */
    private function respond(bool $changed, string $okMessage, string $conflictMessage, bool $liked, int $count): JsonResponse
    {
        return response()->json([
            'status' => $changed ? 'success' : 'error',
            'message' => $changed ? $okMessage : $conflictMessage,
            'data' => [
                'liked' => $liked,
                'likes_count' => $count,
            ],
        ], $changed ? 200 : 409);
    }

    public static function countDiscussionLikes($id): int
    {
        return DB::table('like_diskusi')->where('id_diskusi', $id)->count();
    }

    public static function countCommentLikes($id): int
    {
        return DB::table('like_tanggapan')->where('id_tanggapan', $id)->count();
    }
}
