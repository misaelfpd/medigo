/*
 * MediGO - tombol suka (like/unlike) untuk diskusi & komentar forum.
 * Tombol: <button class="mg-like-btn" data-like-type="discussion|comment" data-like-id=".."
 *          data-liked="0|1" data-own="0|1" data-login="0|1"><span data-like-count>..</span></button>
 * Aturan backend: 1 user hanya bisa suka 1x, dan tidak bisa menyukai milik sendiri.
 */
(function () {
    function toast(message, type) {
        if (typeof window.showGlobalToast === 'function') {
            window.showGlobalToast(message, type || 'success');
        } else {
            window.alert(message);
        }
    }

    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function setState(btn, liked, count) {
        btn.dataset.liked = liked ? '1' : '0';
        btn.classList.toggle('is-liked', liked);
        btn.setAttribute('aria-pressed', liked ? 'true' : 'false');
        var icon = btn.querySelector('i');
        if (icon) {
            icon.classList.toggle('bi-hand-thumbs-up-fill', liked);
            icon.classList.toggle('bi-hand-thumbs-up', !liked);
        }
        var counter = btn.querySelector('[data-like-count]');
        if (counter && typeof count === 'number') {
            counter.textContent = count;
        }
    }

    document.addEventListener('click', function (event) {
        var btn = event.target.closest('.mg-like-btn');
        if (!btn || btn.disabled) return;

        if (btn.dataset.login !== '1') {
            toast('Silakan masuk terlebih dahulu untuk menyukai.', 'error');
            return;
        }
        if (btn.dataset.own === '1') {
            toast('Anda tidak bisa menyukai tulisan sendiri.', 'error');
            return;
        }

        var wasLiked = btn.dataset.liked === '1';
        var counter = btn.querySelector('[data-like-count]');
        var before = counter ? parseInt(counter.textContent, 10) || 0 : 0;

        btn.disabled = true;
        // Tampilkan perubahan langsung, lalu sesuaikan dengan jawaban server
        setState(btn, !wasLiked, Math.max(0, before + (wasLiked ? -1 : 1)));

        fetch('/forum/like/' + encodeURIComponent(btn.dataset.likeType) + '/' + encodeURIComponent(btn.dataset.likeId), {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken()
            },
            body: JSON.stringify({ action: wasLiked ? 'unlike' : 'like' })
        })
            .then(function (response) {
                return response.json().catch(function () { return {}; }).then(function (body) {
                    return { ok: response.ok, status: response.status, body: body };
                });
            })
            .then(function (res) {
                var data = (res.body && res.body.data) || res.body || {};
                if (res.ok) {
                    setState(btn, !!data.liked, typeof data.likes_count === 'number' ? data.likes_count : undefined);
                } else if (res.status === 409 && typeof data.likes_count === 'number') {
                    // Status sudah sesuai di server (misal sudah disukai di tab lain)
                    setState(btn, !!data.liked, data.likes_count);
                } else {
                    setState(btn, wasLiked, before);
                    toast((res.body && res.body.message) || 'Gagal memproses suka.', 'error');
                }
            })
            .catch(function () {
                setState(btn, wasLiked, before);
                toast('Server tidak dapat dihubungi.', 'error');
            })
            .finally(function () {
                btn.disabled = false;
            });
    });
})();
