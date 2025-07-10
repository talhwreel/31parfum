<?php
// Oturumu başlat
session_start();

// Veritabanı bağlantı bilgileri
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); // XAMPP için varsayılan kullanıcı adı
define('DB_PASSWORD', ''); // XAMPP için varsayılan şifre boş
define('DB_NAME', 'vibe_premium');

// Veritabanı bağlantısını oluştur
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Bağlantıyı kontrol et
if($conn->connect_error){
    die("Bağlantı hatası: " . $conn->connect_error);
}

// Karakter setini ayarla
$conn->set_charset("utf8mb4");

// Rolleri ve yetkileri tanımla
define('ROLE_PREMIUM', 1);
define('ROLE_ADMIN', 2);
define('ROLE_YONETICI', 3);
?>
