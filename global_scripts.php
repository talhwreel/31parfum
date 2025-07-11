<!-- Bu dosya, tüm sayfalarda </body> etiketinden hemen önce eklenecek -->

<!-- YENİ: Özel İmleç için HTML Elementleri -->
<div id="custom-cursor-dot" class="custom-cursor"></div>
<div id="custom-cursor-circle" class="custom-cursor"></div>


<!-- Yükleme Ekranı ve Sayfa Geçişi için HTML Elementleri -->
<div id="page-transition-overlay" class="fixed top-0 left-0 w-full h-full bg-gray-900 z-[100] flex items-center justify-center transition-opacity duration-500 ease-in-out pointer-events-none">
    <div class="w-16 h-16 border-4 border-t-purple-500 border-gray-600 rounded-full animate-spin"></div>
</div>

<div id="loading-overlay" class="fixed top-0 left-0 w-full h-full bg-black/70 z-[101] flex-col items-center justify-center transition-opacity duration-300 ease-in-out" style="display: none;">
    <div class="w-16 h-16 border-4 border-t-purple-500 border-gray-600 rounded-full animate-spin"></div>
    <p id="loading-message" class="mt-4 text-white text-lg font-semibold"></p>
</div>


<!-- YENİ: Özel İmleç için CSS Stilleri -->
<style>
    /* Varsayılan imleci gizle */
    body, a, button {
        cursor: none !important;
    }
    .custom-cursor {
        position: fixed;
        top: 0;
        left: 0;
        pointer-events: none;
        z-index: 9999;
        border-radius: 50%;
        transform: translate(-50%, -50%);
        transition: transform 0.2s cubic-bezier(0.19, 1, 0.22, 1), opacity 0.3s;
    }
    /* İçteki nokta */
    #custom-cursor-dot {
        width: 8px;
        height: 8px;
        background-color: #c084fc; /* Mor */
    }
    /* Dıştaki takip eden halka */
    #custom-cursor-circle {
        width: 40px;
        height: 40px;
        border: 2px solid #a855f7; /* Daha koyu mor */
        transition-duration: 0.4s;
    }
    /* Linklerin ve butonların üzerine gelince büyüme efekti */
    body.cursor-hover #custom-cursor-dot {
        opacity: 0;
    }
    body.cursor-hover #custom-cursor-circle {
        transform: translate(-50%, -50%) scale(1.5);
        background-color: rgba(168, 85, 247, 0.2);
    }
</style>


<script>
document.addEventListener('DOMContentLoaded', function() {
    const pageTransitionOverlay = document.getElementById('page-transition-overlay');
    const loadingOverlay = document.getElementById('loading-overlay');
    const loadingMessage = document.getElementById('loading-message');
    
    // YENİ: Özel İmleç Script'i
    const cursorDot = document.getElementById('custom-cursor-dot');
    const cursorCircle = document.getElementById('custom-cursor-circle');

    let dotX = 0, dotY = 0;
    let circleX = 0, circleY = 0;

    window.addEventListener('mousemove', (e) => {
        dotX = e.clientX;
        dotY = e.clientY;
    });

    function animateCursor() {
        // Yumuşak takip efekti için
        circleX += (dotX - circleX) * 0.1;
        circleY += (dotY - circleY) * 0.1;

        cursorDot.style.left = `${dotX}px`;
        cursorDot.style.top = `${dotY}px`;
        cursorCircle.style.left = `${circleX}px`;
        cursorCircle.style.top = `${circleY}px`;

        requestAnimationFrame(animateCursor);
    }
    animateCursor();

    // Link ve butonların üzerine gelince efekt ekle
    document.querySelectorAll('a, button, [role="button"], input[type="submit"]').forEach(el => {
        el.addEventListener('mouseenter', () => document.body.classList.add('cursor-hover'));
        el.addEventListener('mouseleave', () => document.body.classList.remove('cursor-hover'));
    });


    // --- Sayfa Geçiş Animasyonu ---
    window.addEventListener('load', () => {
        pageTransitionOverlay.style.opacity = '0';
        setTimeout(() => {
            pageTransitionOverlay.style.pointerEvents = 'none';
        }, 500);
    });

    document.querySelectorAll('a:not([data-no-transition])').forEach(link => {
        if (link.href && link.href.startsWith(window.location.origin) && !link.href.includes('#') && link.target !== '_blank') {
            link.addEventListener('click', function(e) {
                const url = this.href;
                if (url === window.location.href.split('#')[0]) return;
                e.preventDefault();
                document.body.classList.remove('cursor-hover'); // Geçiş sırasında hover efektini kaldır
                pageTransitionOverlay.style.pointerEvents = 'auto';
                pageTransitionOverlay.style.opacity = '1';
                setTimeout(() => {
                    window.location.href = url;
                }, 500);
            });
        }
    });

    // --- Yükleme Ekranı Fonksiyonları ---
    window.showLoading = function(message = 'İşleniyor...') {
        loadingMessage.innerHTML = message;
        loadingOverlay.querySelector('.animate-spin').style.display = 'block';
        loadingOverlay.style.display = 'flex';
        setTimeout(() => loadingOverlay.style.opacity = '1', 10);
    }

    window.hideLoading = function() {
        loadingOverlay.style.opacity = '0';
        setTimeout(() => loadingOverlay.style.display = 'none', 300);
    }
    
    window.showLoadingSuccess = function(message, onHidden) {
        loadingMessage.innerHTML = `<i class="fas fa-check-circle text-green-400 text-5xl mb-3"></i> <span class="block">${message}</span>`;
        loadingOverlay.querySelector('.animate-spin').style.display = 'none';
        setTimeout(() => {
            hideLoading();
            if (onHidden) {
                setTimeout(onHidden, 300);
            }
        }, 2000);
    }
});
</script>
