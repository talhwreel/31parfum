<?php
require_once 'config.php';
if (session_status() == PHP_SESSION_NONE) { session_start(); }

if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: dashboard.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    header('Content-Type: application/json');
    $response = [];

    // --- GİRİŞ İŞLEMİ ---
    if (isset($_POST['action']) && $_POST['action'] == 'login') {
        $username = trim($_POST["username"]);
        $password = trim($_POST["password"]);

        if (empty($username) || empty($password)) {
            $response = ['status' => 'warning', 'message' => 'Lütfen tüm alanları doldurun.'];
        } else {
            $sql = "SELECT id, username, password, role_id FROM users WHERE username = ?";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("s", $username);
                if ($stmt->execute()) {
                    $stmt->store_result();
                    if ($stmt->num_rows == 1) {
                        $stmt->bind_result($id, $username, $hashed_password, $role_id);
                        if ($stmt->fetch() && password_verify($password, $hashed_password)) {
                            // Ban kontrolü
                            $ban_sql = "SELECT reason, ban_until FROM bans WHERE user_id = ? AND (ban_until IS NULL OR ban_until > NOW())";
                            if ($ban_stmt = $conn->prepare($ban_sql)) {
                                $ban_stmt->bind_param("i", $id);
                                $ban_stmt->execute();
                                $ban_stmt->store_result();
                                if ($ban_stmt->num_rows > 0) {
                                    $ban_stmt->bind_result($reason, $ban_until);
                                    $ban_stmt->fetch();
                                    $_SESSION["banned_username"] = $username;
                                    $response = ['status' => 'banned', 'redirect' => 'banned.php'];
                                } else {
                                    $_SESSION["loggedin"] = true;
                                    $_SESSION["id"] = $id;
                                    $_SESSION["username"] = $username;
                                    $_SESSION["role_id"] = $role_id;
                                    $response = ['status' => 'success', 'redirect' => 'dashboard.php'];
                                }
                                $ban_stmt->close();
                            }
                        } else {
                            $response = ['status' => 'error', 'message' => 'Geçersiz kullanıcı adı veya şifre.'];
                        }
                    } else {
                       $response = ['status' => 'error', 'message' => 'Geçersiz kullanıcı adı veya şifre.'];
                    }
                }
                $stmt->close();
            }
        }
    }

    // --- KAYIT İŞLEMİ ---
    elseif (isset($_POST['action']) && $_POST['action'] == 'register') {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);

        if (empty($username) || empty($email) || empty($password)) {
            $response = ['status' => 'warning', 'message' => 'Tüm alanları doldurmak zorunludur.'];
        } elseif (strlen($password) < 6) {
            $response = ['status' => 'warning', 'message' => 'Şifre en az 6 karakter olmalıdır.'];
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $response = ['status' => 'warning', 'message' => 'Geçersiz e-posta formatı.'];
        } else {
            $sql = "SELECT id FROM users WHERE username = ? OR email = ?";
            if($stmt = $conn->prepare($sql)){
                $stmt->bind_param("ss", $username, $email);
                $stmt->execute();
                $stmt->store_result();
                if($stmt->num_rows > 0){
                    $response = ['status' => 'error', 'message' => 'Bu kullanıcı adı veya e-posta zaten kullanılıyor.'];
                } else {
                    $sql_insert = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
                    if($stmt_insert = $conn->prepare($sql_insert)){
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt_insert->bind_param("sss", $username, $email, $hashed_password);
                        if($stmt_insert->execute()){
                            $response = ['status' => 'success', 'message' => 'Başarıyla kayıt oldunuz! Giriş yapabilirsiniz.'];
                        } else {
                            $response = ['status' => 'error', 'message' => 'Kayıt sırasında bir hata oluştu.'];
                        }
                        $stmt_insert->close();
                    }
                }
                $stmt->close();
            }
        }
    }
    
    echo json_encode($response);
    $conn->close();
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vibe Premium - Giriş & Kayıt</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; overflow: hidden; }
        #intro-screen { transition: opacity 1.5s ease-in-out; cursor: pointer; }
        @keyframes pulseGlow { 0%, 100% { text-shadow: 0 0 15px rgba(216, 180, 254, 0.4); } 50% { text-shadow: 0 0 25px rgba(192, 132, 252, 0.6); } }
        #animated-text { animation: pulseGlow 4s ease-in-out infinite; }
        .typing-cursor::after { content: '_'; display: inline-block; vertical-align: bottom; animation: blink 1s step-start infinite; }
        @keyframes blink { 50% { opacity: 0; } }
        .form-container { transition: opacity 0.5s ease-in-out, transform 0.5s ease-in-out; }
        .form-hidden { opacity: 0; transform: scale(0.95); position: absolute; pointer-events: none; }
        .form-visible { opacity: 1; transform: scale(1); position: relative; pointer-events: auto; }
        .custom-input { border-bottom: 2px solid rgba(255, 255, 255, 0.3); transition: border-color 0.3s ease; }
        .custom-input:focus { border-bottom-color: #a855f7; }
        @keyframes slideInRight { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        @keyframes fadeOut { to { opacity: 0; transform: translateY(20px); } }
        .notification { animation: slideInRight 0.5s ease-out forwards; }
        .notification.fade-out { animation: fadeOut 0.5s ease-in forwards; }
    </style>
</head>
<body class="bg-gray-900 text-white">

    <div id="intro-screen" class="fixed top-0 left-0 w-full h-full bg-gray-900 flex flex-col items-center justify-center z-50">
        <h1 id="animated-text" class="text-3xl md:text-5xl font-extrabold tracking-wider bg-clip-text text-transparent bg-gradient-to-r from-purple-400 to-pink-500"></h1>
        <p id="start-prompt" class="mt-8 text-lg text-white/70 animate-pulse">Deneyimi başlatmak için tıklayın</p>
    </div>
    
    <audio id="typing-audio"> <source src="assets/key.mp3" type="audio/mpeg"> </audio>
    <audio id="background-music" loop> <source src="assets/mus.mp3" type="audio/mpeg"> </audio>
    <audio id="success-audio"> <source src="assets/suc.mp3" type="audio/mpeg"> </audio>
    <audio id="warning-audio"> <source src="assets/war.mp3" type="audio/mpeg"> </audio>
    <audio id="error-audio"> <source src="assets/err.mp3" type="audio/mpeg"> </audio>

    <div id="notification-container" class="fixed bottom-24 right-6 z-50 w-full max-w-xs space-y-3"></div>

    <div id="main-content" class="opacity-0 transition-opacity duration-1000">
        <div class="absolute top-0 left-0 w-full h-full z-[-1] overflow-hidden">
            <video autoplay muted loop class="w-full h-full object-cover filter blur-sm brightness-50 scale-110">
                <source src="assets/vid.mp4" type="video/mp4">
            </video>
        </div>
        
        <button id="music-toggle" class="fixed bottom-6 right-6 w-14 h-14 bg-black/40 backdrop-blur-sm rounded-full flex items-center justify-center text-white/70 hover:bg-black/60 hover:text-white transition-all duration-300 shadow-lg z-50">
            <i id="music-icon" class="fas fa-play text-xl"></i>
        </button>

        <div class="flex items-center justify-center min-h-screen w-full p-4">
            <div class="w-full max-w-md mx-auto bg-black/30 backdrop-blur-xl rounded-2xl shadow-2xl border border-white/10 overflow-hidden">
                
                <div id="login-form-container" class="form-container form-visible">
                    <div class="p-8 md:p-12">
                        <h2 class="text-3xl font-bold text-center mb-2">Hoş Geldiniz</h2>
                        <p class="text-center text-white/60 mb-8">Devam etmek için giriş yapın.</p>
                        <form id="login-form">
                            <input type="hidden" name="action" value="login">
                            <div class="relative mb-6"><div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none"><i class="fas fa-user text-white/40"></i></div><input name="username" type="text" placeholder="Kullanıcı Adı" class="w-full bg-transparent pl-10 p-3 text-white placeholder-white/50 outline-none custom-input"></div>
                            <div class="relative mb-6"><div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none"><i class="fas fa-lock text-white/40"></i></div><input name="password" type="password" placeholder="Şifre" class="w-full bg-transparent pl-10 p-3 text-white placeholder-white/50 outline-none custom-input"></div>
                            <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 rounded-lg transition-all">Giriş Yap</button>
                        </form>
                    </div>
                    <div class="bg-black/20 py-4 text-center"><p class="text-white/60">Hesabın yok mu? <a href="#" id="show-register" data-no-transition class="font-semibold text-purple-400 hover:underline">Hemen Kaydol</a></p></div>
                </div>

                <div id="register-form-container" class="form-container form-hidden">
                    <div class="p-8 md:p-12">
                        <h2 class="text-3xl font-bold text-center mb-2">Hesap Oluştur</h2>
                        <p class="text-center text-white/60 mb-8">Aramıza katıl.</p>
                        <form id="register-form">
                            <input type="hidden" name="action" value="register">
                            <div class="relative mb-6"><div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none"><i class="fas fa-user-astronaut text-white/40"></i></div><input name="username" type="text" placeholder="Kullanıcı Adı" class="w-full bg-transparent pl-10 p-3 text-white placeholder-white/50 outline-none custom-input" required></div>
                            <div class="relative mb-6"><div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none"><i class="fas fa-envelope text-white/40"></i></div><input name="email" type="email" placeholder="E-posta Adresi" class="w-full bg-transparent pl-10 p-3 text-white placeholder-white/50 outline-none custom-input" required></div>
                            <div class="relative mb-6"><div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none"><i class="fas fa-key text-white/40"></i></div><input name="password" type="password" placeholder="Şifre" class="w-full bg-transparent pl-10 p-3 text-white placeholder-white/50 outline-none custom-input" required></div>
                            <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 rounded-lg transition-all">Kayıt Ol</button>
                        </form>
                    </div>
                    <div class="bg-black/20 py-4 text-center"><p class="text-white/60">Zaten bir hesabın var mı? <a href="#" id="show-login" data-no-transition class="font-semibold text-purple-400 hover:underline">Giriş Yap</a></p></div>
                </div>
            </div>
        </div>
    </div>

    <?php require_once 'layout/global_scripts.php'; ?>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const introScreen = document.getElementById('intro-screen');
        const mainContent = document.getElementById('main-content');
        const animatedText = document.getElementById('animated-text');
        const startPrompt = document.getElementById('start-prompt');
        const loginFormContainer = document.getElementById('login-form-container');
        const registerFormContainer = document.getElementById('register-form-container');
        const showRegisterBtn = document.getElementById('show-register');
        const showLoginBtn = document.getElementById('show-login');
        const musicToggle = document.getElementById('music-toggle');
        const musicIcon = document.getElementById('music-icon');
        const typingAudio = document.getElementById('typing-audio');
        const backgroundMusic = document.getElementById('background-music');
        const audioSources = { success: document.getElementById('success-audio'), warning: document.getElementById('warning-audio'), error: document.getElementById('error-audio') };
        const notificationContainer = document.getElementById('notification-container');
        let isMusicPlaying = false;
        let fadeInterval;

        window.showNotification = (message, type = 'success') => {
            if (isMusicPlaying) backgroundMusic.volume = 0.1;
            const icons = { success: 'fa-check-circle', warning: 'fa-exclamation-triangle', error: 'fa-times-circle' };
            const colors = { success: 'bg-green-500/80 border-green-400', warning: 'bg-yellow-500/80 border-yellow-400', error: 'bg-red-500/80 border-red-400' };
            const notification = document.createElement('div');
            notification.className = `notification flex items-center p-4 rounded-lg shadow-lg text-white border-l-4 ${colors[type]} backdrop-blur-sm`;
            notification.innerHTML = `<i class="fas ${icons[type]} mr-3 text-xl"></i><span>${message}</span>`;
            notificationContainer.appendChild(notification);
            if (audioSources[type]) { audioSources[type].currentTime = 0; audioSources[type].play(); }
            setTimeout(() => {
                notification.classList.add('fade-out');
                notification.addEventListener('animationend', () => {
                    notification.remove();
                    if (isMusicPlaying) backgroundMusic.volume = 0.4;
                });
            }, 5000);
        };

        showRegisterBtn.addEventListener('click', (e) => { e.preventDefault(); loginFormContainer.classList.replace('form-visible', 'form-hidden'); registerFormContainer.classList.replace('form-hidden', 'form-visible'); });
        showLoginBtn.addEventListener('click', (e) => { e.preventDefault(); registerFormContainer.classList.replace('form-visible', 'form-hidden'); loginFormContainer.classList.replace('form-hidden', 'form-visible'); });

        const textToType = "Vibe Premium'a Hoş Geldiniz";
        let charIndex = 0;
        function typeWriter() {
            if (charIndex < textToType.length) {
                if (textToType.charAt(charIndex) !== ' ') { typingAudio.currentTime = 0; typingAudio.play(); }
                animatedText.innerHTML += textToType.charAt(charIndex);
                charIndex++;
                setTimeout(typeWriter, 120);
            } else {
                animatedText.classList.remove('typing-cursor');
                setTimeout(() => {
                    introScreen.style.opacity = '0';
                    setTimeout(() => {
                        introScreen.style.display = 'none';
                        mainContent.style.opacity = '1';
                        fadeInMusic();
                    }, 1500);
                }, 1500);
            }
        }

        function startExperience() {
            startPrompt.style.display = 'none';
            typeWriter();
        }
        introScreen.addEventListener('click', startExperience, { once: true });

        const fadeInMusic = () => {
            clearInterval(fadeInterval);
            backgroundMusic.volume = 0;
            backgroundMusic.play().then(() => {
                isMusicPlaying = true;
                musicIcon.classList.replace('fa-play', 'fa-pause');
                fadeInterval = setInterval(() => {
                    if (backgroundMusic.volume < 0.4) { backgroundMusic.volume = Math.min(0.4, backgroundMusic.volume + 0.05); } 
                    else { clearInterval(fadeInterval); }
                }, 100);
            }).catch(e => console.error("Müzik çalınamadı:", e));
        };
        const fadeOutMusic = () => {
            clearInterval(fadeInterval);
            isMusicPlaying = false;
            musicIcon.classList.replace('fa-pause', 'fa-play');
            fadeInterval = setInterval(() => {
                if (backgroundMusic.volume > 0.05) { backgroundMusic.volume -= 0.05; } 
                else { clearInterval(fadeInterval); backgroundMusic.pause(); backgroundMusic.volume = 0.4; }
            }, 100);
        };
        musicToggle.addEventListener('click', () => { isMusicPlaying ? fadeOutMusic() : fadeInMusic(); });

        async function handleFormSubmit(event) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);
            const action = formData.get('action');
            const loadingText = action === 'login' ? 'Giriş yapılıyor...' : 'Hesap oluşturuluyor...';
            showLoading(loadingText);

            try {
                const response = await fetch('auth.php', { method: 'POST', body: formData });
                const result = await response.json();

                if (result.status === 'success') {
                    if (result.redirect) {
                        showLoadingSuccess('Başarılı! Yönlendiriliyorsunuz...', () => {
                            window.location.href = result.redirect;
                        });
                    } else {
                        hideLoading();
                        showNotification(result.message, 'success');
                        form.reset();
                        showLoginBtn.click();
                    }
                } else if (result.status === 'banned') {
                    showLoadingSuccess('Hesap banlı, yönlendiriliyor...', () => {
                        window.location.href = result.redirect;
                    });
                } else {
                    hideLoading();
                    showNotification(result.message, result.status);
                }
            } catch (error) {
                hideLoading();
                showNotification('Bir sunucu hatası oluştu.', 'error');
            }
        }
        document.getElementById('login-form').addEventListener('submit', handleFormSubmit);
        document.getElementById('register-form').addEventListener('submit', handleFormSubmit);
    });
    </script>
</body>
</html>
