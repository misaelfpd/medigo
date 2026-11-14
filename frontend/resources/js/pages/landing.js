/**
 * MediGO - Modular Script Halaman Landing (landing.blade.php)
 * Mengatur:
 * 1. Navbar sticky state (shadow saat discroll)
 * 2. Tombol 'Lihat Faskes' di navbar yang muncul saat user scroll melewati hero
 * 3. Smooth scroll untuk navigasi indikator hero
 */

export function initLandingPage() {
    const topbar = document.getElementById('mgLandingTopbar');
    const faskesBtn = document.getElementById('mgLandingNavFaskesBtn');
    const heroActions = document.getElementById('landingHeroActions');
    const heroScrollBtn = document.querySelector('.mg-landing-hero__scroll');

    if (!topbar) return;

    // 1. Logika Scroll Detection (Navbar Sticky & Tombol 'Lihat Faskes')
    let ticking = false;

    function updateNavbarOnScroll() {
        const scrollY = window.scrollY || window.pageYOffset || 0;

        // Sticky shadow elevation pada topbar
        if (scrollY > 10) {
            topbar.classList.add('is-scrolled');
        } else {
            topbar.classList.remove('is-scrolled');
        }

        // Tampilkan tombol "Lihat Faskes" di navbar jika user sudah scroll ke bawah
        // Menggunakan posisi elemen tombol hero jika ada, atau fallback threshold 120px
        let shouldShowBtn = scrollY > 120;
        if (heroActions) {
            const rect = heroActions.getBoundingClientRect();
            // Tombol di navbar muncul saat tombol hero mulai meninggalkan layar
            shouldShowBtn = rect.top < 40;
        }

        if (faskesBtn) {
            if (shouldShowBtn) {
                faskesBtn.classList.add('is-visible');
            } else {
                faskesBtn.classList.remove('is-visible');
            }
        }

        ticking = false;
    }

    // Scroll listener dengan requestAnimationFrame untuk performa 60fps
    window.addEventListener('scroll', () => {
        if (!ticking) {
            window.requestAnimationFrame(updateNavbarOnScroll);
            ticking = true;
        }
    }, { passive: true });

    // Panggil sekali di awal untuk inisialisasi state jika halaman dibuka sudah dalam posisi scroll
    updateNavbarOnScroll();

    // 2. Smooth Scroll untuk tombol indikator ke bawah
    if (heroScrollBtn) {
        heroScrollBtn.addEventListener('click', (e) => {
            const targetId = heroScrollBtn.getAttribute('href');
            if (targetId && targetId.startsWith('#')) {
                const targetEl = document.querySelector(targetId);
                if (targetEl) {
                    e.preventDefault();
                    const topbarHeight = topbar.offsetHeight || 64;
                    const targetPosition = targetEl.getBoundingClientRect().top + window.pageYOffset - topbarHeight;
                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                }
            }
        });
    }
}

// Inisialisasi otomatis ketika DOM selesai dimuat
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initLandingPage);
} else {
    initLandingPage();
}
