<?php
// Sorgu veritabanı için bağlantı bilgileri
define('SORGU_DB_SERVER', 'localhost');
define('SORGU_DB_USERNAME', 'root');
define('SORGU_DB_PASSWORD', '');
define('SORGU_DB_NAME', 'sorgu');

// Sorgu veritabanı bağlantısını oluştur
$sorgu_conn = new mysqli(SORGU_DB_SERVER, SORGU_DB_USERNAME, SORGU_DB_PASSWORD, SORGU_DB_NAME);

// Bağlantıyı kontrol et
if($sorgu_conn->connect_error){
    // Hata mesajını ekrana basmak yerine loglamak daha güvenli olabilir.
    // Şimdilik basit bir die() kullanıyoruz.
    die("Sorgu veritabanı bağlantı hatası: " . $sorgu_conn->connect_error);
}

// Karakter setini ayarla
$sorgu_conn->set_charset("utf8mb4");
?>
