<?php
require_once 'config.php';
require_once 'security_check.php';

// ZAMANLAMA HATASI ÇÖZÜMÜ: Sunucu saatini Türkiye saatine ayarla
date_default_timezone_set('Europe/Istanbul');

$prizes = [
    ['label' => '10 TL', 'type' => 'balance', 'value' => 10, 'color' => '#6b21a8', 'special' => false],
    ['label' => 'Pas', 'type' => 'nothing', 'value' => 0, 'color' => '#4b5563', 'special' => false],
    ['label' => '50 TL', 'type' => 'balance', 'value' => 50, 'color' => '#be185d', 'special' => false],
    ['label' => '1 Gün<br>Premium', 'type' => 'premium', 'value' => 1, 'color' => '#ca8a04', 'special' => false],
    ['label' => '25 TL', 'type' => 'balance', 'value' => 25, 'color' => '#6b21a8', 'special' => false],
    ['label' => '100 TL', 'type' => 'balance', 'value' => 100, 'color' => 'linear-gradient(45deg, #f72585, #b5179e, #7209b7, #560bad, #480ca8, #3a0ca3, #3f37c9, #4361ee, #4895ef, #4cc9f0)', 'special' => true],
    ['label' => 'Pas', 'type' => 'nothing', 'value' => 0, 'color' => '#4b5563', 'special' => false],
    ['label' => '3 Gün<br>Premium', 'type' => 'premium', 'value' => 3, 'color' => 'linear-gradient(45deg, #f94144, #f3722c, #f8961e, #f9c74f, #90be6d, #43aa8b, #577590)', 'special' => true],
];
$prize_count = count($prizes);
$slice_degree = 360 / $prize_count;

// Kullanıcının son çevirme zamanını kontrol et
$can_spin = false;
$seconds_remaining = 0;
$sql_spin = "SELECT last_spin_time FROM wheel_spins WHERE user_id = ?";
if($stmt_spin = $conn->prepare($sql_spin)){
    $stmt_spin->bind_param("i", $user_id);
    $stmt_spin->execute();
    $stmt_spin->bind_result($last_spin_time);
    if($stmt_spin->fetch()){
        $next_spin_time = strtotime($last_spin_time) + (24 * 60 * 60);
        if(time() >= $next_spin_time){ $can_spin = true; } 
        else { $seconds_remaining = $next_spin_time - time(); }
    } else { $can_spin = true; }
    $stmt_spin->close();
}

// Hatanın çözümü: user_balance değişkenini sidebar'dan değil, doğrudan buradan alıyoruz.
$user_balance = 0.00;
$sql_balance = "SELECT balance FROM balances WHERE user_id = ?";
if($stmt_balance = $conn->prepare($sql_balance)){
    $stmt_balance->bind_param("i", $user_id);
    $stmt_balance->execute();
    $stmt_balance->bind_result($user_balance);
    if(!$stmt_balance->fetch()) {
        $user_balance = 0.00;
    }
    $stmt_balance->close();
}

$skip_cooldown_price = 50;
$can_skip = ($user_balance >= $skip_cooldown_price);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Şans Çarkı</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        #wheel-container { position: relative; width: 384px; height: 384px; }
        #wheel {
            position: relative;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 8px solid #374151;
            transition: transform 5s cubic-bezier(0.25, 1, 0.5, 1);
            overflow: hidden;
        }
        #wheel-prizes { list-style: none; margin: 0; padding: 0; position: absolute; width: 100%; height: 100%; border-radius: 50%; }
        #wheel-prizes li {
            overflow: hidden; position: absolute; top: 0; right: 0; width: 50%; height: 50%; transform-origin: 0% 100%;
        }
        #wheel-prizes .text {
            position: absolute; left: -100%; width: 200%; height: 200%; text-align: center;
            transform: skewY(<?php echo 90 - $slice_degree; ?>deg) rotate(<?php echo $slice_degree / 2; ?>deg);
            padding-top: 20px; color: white; font-weight: bold; text-shadow: 1px 1px 2px rgba(0,0,0,0.6);
            line-height: 1.2;
        }
        #marker { position: absolute; top: -4px; left: 50%; transform: translateX(-50%); width: 0; height: 0; border-left: 14px solid transparent; border-right: 14px solid transparent; border-top: 28px solid #facc15; z-index: 10; }
    </style>
</head>
<body class="bg-gray-900 text-white overflow-hidden">
    <?php require_once 'layout/sidebar.php'; ?>
    <audio id="bg-music" loop><source src="assets/cark.mp3" type="audio/mpeg"></audio>
    <audio id="spin-sound"><source src="assets/carkcevir.mp3" type="audio/mpeg"></audio>
    <audio id="result-sound"><source src="assets/carksonuc.mp3" type="audio/mpeg"></audio>
    <audio id="skip-sound"><source src="assets/suc.mp3" type="audio/mpeg"></audio>

    <main class="ml-64 p-8 flex flex-col items-center justify-center h-screen">
        <h1 class="text-5xl font-bold mb-8 text-purple-400 drop-shadow-lg">Şans Çarkı</h1>
        <div id="wheel-container">
            <div id="marker"></div>
            <div id="wheel">
                <ul id="wheel-prizes">
                    <?php foreach($prizes as $index => $prize): ?>
                        <li style="transform: rotate(<?php echo $index * $slice_degree; ?>deg) skewY(<?php echo $slice_degree - 90; ?>deg); background: <?php echo $prize['color']; ?>;">
                            <div class="text"><?php echo $prize['label']; ?></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <div id="controls" class="mt-8 text-center">
            <?php if($can_spin): ?>
                <button id="spin-button" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-4 px-12 rounded-lg text-2xl transition-transform hover:scale-105">ÇEVİR</button>
            <?php else: ?>
                <div id="cooldown-timer" class="bg-gray-800 p-4 rounded-lg">
                    <p class="text-gray-400">Tekrar çevirmek için beklemen gereken süre:</p>
                    <div id="timer" class="text-4xl font-bold text-yellow-400 my-2"></div>
                    <button id="skip-button" data-no-transition <?php echo !$can_skip ? 'disabled' : ''; ?> class="w-full mt-2 <?php echo $can_skip ? 'bg-green-600 hover:bg-green-700' : 'bg-gray-600 cursor-not-allowed'; ?> text-white font-bold py-2 rounded-lg">
                        Süreyi Atla (<?php echo $skip_cooldown_price; ?> TL)
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <div id="prize-modal" class="fixed top-0 left-0 w-full h-full bg-black/70 z-50 flex items-center justify-center opacity-0 pointer-events-none transition-opacity duration-300">
        <div class="bg-gray-800 p-8 rounded-lg shadow-2xl text-center border-2 border-purple-500 transform scale-90 transition-transform duration-300">
            <h2 class="text-3xl font-bold mb-4 text-yellow-400">Tebrikler!</h2>
            <p id="prize-text" class="text-xl text-white"></p>
            <button id="close-modal" class="mt-6 bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-6 rounded-lg">Kapat</button>
        </div>
    </div>

    <?php require_once 'layout/global_scripts.php'; ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const wheel = document.getElementById('wheel');
            const spinButton = document.getElementById('spin-button');
            const skipButton = document.getElementById('skip-button');
            const prizeModal = document.getElementById('prize-modal');
            const prizeModalContent = prizeModal.querySelector('div');
            const prizeText = document.getElementById('prize-text');
            const closeModal = document.getElementById('close-modal');
            const bgMusic = document.getElementById('bg-music');
            const spinSound = document.getElementById('spin-sound');
            const resultSound = document.getElementById('result-sound');
            const skipSound = document.getElementById('skip-sound');
            
            bgMusic.volume = 0.2;
            bgMusic.play().catch(e => {});

            let currentRotation = 0;
            let isSpinning = false;

            const handleSpin = async (action) => {
                if (isSpinning) return;
                isSpinning = true;
                spinButton?.setAttribute('disabled', 'disabled');
                skipButton?.setAttribute('disabled', 'disabled');
                
                if (action === 'spin') {
                    spinSound.currentTime = 0;
                    spinSound.play();
                } else {
                    showLoading('İşleniyor...');
                }

                try {
                    const formData = new FormData();
                    formData.append('action', action);
                    const response = await fetch('process_wheel.php', { method: 'POST', body: formData });
                    const result = await response.json();

                    if(result.status === 'success'){
                        const prizeIndex = result.prize_index;
                        const prize = result.prize_label.replace('<br>', ' ');
                        const sliceDegree = <?php echo $slice_degree; ?>;
                        const randomOffset = (Math.random() * (sliceDegree * 0.8)) + (sliceDegree * 0.1);
                        const totalRotation = (360 * 5) + (360 - (prizeIndex * sliceDegree) - randomOffset);
                        
                        currentRotation += totalRotation;
                        wheel.style.transform = `rotate(${currentRotation}deg)`;

                        setTimeout(() => {
                            resultSound.play();
                            prizeText.innerHTML = `Kazandın: <span class="font-bold text-yellow-300">${prize}</span>`;
                            prizeModal.style.opacity = '1';
                            prizeModal.style.pointerEvents = 'auto';
                            setTimeout(() => prizeModalContent.style.transform = 'scale(1)', 10);
                            isSpinning = false;
                        }, 5500);
                    } else if (result.status === 'success_skip') {
                        skipSound.play();
                        showLoadingSuccess('Süre sıfırlandı!', () => window.location.reload());
                    } else {
                        hideLoading();
                        alert(result.message || 'Bilinmeyen bir hata oluştu.');
                        isSpinning = false;
                        spinButton?.removeAttribute('disabled');
                        skipButton?.removeAttribute('disabled');
                    }
                } catch (error) {
                    hideLoading();
                    alert('Bir hata oluştu. Lütfen konsolu kontrol edin.');
                    console.error(error);
                    isSpinning = false;
                }
            };

            spinButton?.addEventListener('click', () => handleSpin('spin'));
            skipButton?.addEventListener('click', () => handleSpin('skip_cooldown'));
            closeModal.addEventListener('click', () => window.location.reload());

            const timerEl = document.getElementById('timer');
            let seconds = <?php echo $seconds_remaining; ?>;
            if (timerEl && seconds > 0) {
                const interval = setInterval(() => {
                    seconds--;
                    const h = Math.floor(seconds / 3600).toString().padStart(2, '0');
                    const m = Math.floor((seconds % 3600) / 60).toString().padStart(2, '0');
                    const s = (seconds % 60).toString().padStart(2, '0');
                    timerEl.textContent = `${h}:${m}:${s}`;
                    if (seconds <= 0) {
                        clearInterval(interval);
                        window.location.reload();
                    }
                }, 1000);
            }
        });
    </script>
</body>
</html>
