-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1:3306
-- Üretim Zamanı: 05 Oca 2026, 12:50:36
-- Sunucu sürümü: 8.4.7
-- PHP Sürümü: 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `stokiq_db`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `kategoriler`
--

DROP TABLE IF EXISTS `kategoriler`;
CREATE TABLE IF NOT EXISTS `kategoriler` (
  `kategori_id` int NOT NULL AUTO_INCREMENT,
  `kategori_adi` varchar(100) COLLATE utf8mb4_turkish_ci NOT NULL,
  `aciklama` varchar(255) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  PRIMARY KEY (`kategori_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Tablo döküm verisi `kategoriler`
--

INSERT INTO `kategoriler` (`kategori_id`, `kategori_adi`, `aciklama`) VALUES
(1, 'Temel Gıdalar', 'Un, makarna, bakliyat vb.'),
(2, 'Süt Ürünleri', 'Süt, yoğurt, peynir vb.'),
(3, 'İçecek', 'Su, meyve suyu, gazlı içecekler'),
(4, 'Atıştırmalık', 'Çerez, bisküvi vb.'),
(5, 'Temizlik', 'Deterjan, sabun vb.'),
(6, 'Temel Gıdalar', 'Un, makarna, bakliyat vb.'),
(7, 'Süt Ürünleri', 'Süt, yoğurt, peynir vb.'),
(8, 'İçecek', 'Su, meyve suyu, gazlı içecekler'),
(9, 'Atıştırmalık', 'Çerez, bisküvi vb.'),
(10, 'Temizlik', 'Deterjan, sabun vb.');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `kullanicilar`
--

DROP TABLE IF EXISTS `kullanicilar`;
CREATE TABLE IF NOT EXISTS `kullanicilar` (
  `kullanici_id` int NOT NULL AUTO_INCREMENT,
  `kullanici_adi` varchar(50) COLLATE utf8mb4_turkish_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `sifre_hash` varchar(255) COLLATE utf8mb4_turkish_ci NOT NULL,
  `rol` enum('admin','personel') COLLATE utf8mb4_turkish_ci NOT NULL DEFAULT 'admin',
  `aktif_mi` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`kullanici_id`),
  UNIQUE KEY `kullanici_adi` (`kullanici_adi`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Tablo döküm verisi `kullanicilar`
--

INSERT INTO `kullanicilar` (`kullanici_id`, `kullanici_adi`, `email`, `sifre_hash`, `rol`, `aktif_mi`) VALUES
(1, 'admin', NULL, '$2y$10$.WDlr7YDcBH9kwRhUz3BvO.yjyswoS4/WEw9ZGd2HWlXyga1fMz7G', 'admin', 1),
(2, 'personel', NULL, '$2y$10$wW55.2k7.k.6H6.6.6H6.6H6.6H6.6H6.6H6.6H6.6H6.6H6.6', 'personel', 1);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `promosyonlar`
--

DROP TABLE IF EXISTS `promosyonlar`;
CREATE TABLE IF NOT EXISTS `promosyonlar` (
  `promosyon_id` int NOT NULL AUTO_INCREMENT,
  `urun_id` int NOT NULL,
  `indirim_yuzde` decimal(5,2) DEFAULT NULL,
  `indirim_tutar` decimal(10,2) DEFAULT NULL,
  `baslangic_tarih` date NOT NULL,
  `bitis_tarih` date NOT NULL,
  `neden` enum('SKT_YAKLASIYOR','STOK_FAZLA','MANUEL') COLLATE utf8mb4_turkish_ci NOT NULL,
  `aktif_mi` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`promosyon_id`),
  KEY `fk_promosyon_urun` (`urun_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Tablo döküm verisi `promosyonlar`
--

INSERT INTO `promosyonlar` (`promosyon_id`, `urun_id`, `indirim_yuzde`, `indirim_tutar`, `baslangic_tarih`, `bitis_tarih`, `neden`, `aktif_mi`) VALUES
(1, 2, 15.00, NULL, '2025-12-15', '2025-12-22', 'STOK_FAZLA', 1),
(3, 1, 20.00, NULL, '2025-12-05', '2025-12-18', 'MANUEL', 1),
(4, 2, 15.00, NULL, '2025-12-16', '2025-12-23', 'STOK_FAZLA', 1),
(6, 1, 20.00, NULL, '2025-12-06', '2025-12-19', 'MANUEL', 1);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `stok_hareketleri`
--

DROP TABLE IF EXISTS `stok_hareketleri`;
CREATE TABLE IF NOT EXISTS `stok_hareketleri` (
  `hareket_id` int NOT NULL AUTO_INCREMENT,
  `urun_id` int NOT NULL,
  `tip` enum('GIRIS','CIKIS','IADE','SAYIM') COLLATE utf8mb4_turkish_ci NOT NULL,
  `miktar` int NOT NULL,
  `tarih` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `aciklama` varchar(255) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  PRIMARY KEY (`hareket_id`),
  KEY `fk_hareket_urun` (`urun_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Tablo döküm verisi `stok_hareketleri`
--

INSERT INTO `stok_hareketleri` (`hareket_id`, `urun_id`, `tip`, `miktar`, `tarih`, `aciklama`) VALUES
(1, 1, 'GIRIS', 20, '2025-12-15 17:54:26', 'Yeni stok girisi'),
(2, 1, 'CIKIS', 20, '2025-12-15 17:54:26', 'Satislar'),
(3, 2, 'GIRIS', 30, '2025-12-15 17:54:26', 'Kampanya oncesi yuklu stok'),
(4, 3, 'GIRIS', 50, '2025-12-15 17:54:26', 'Normal tedarik'),
(5, 4, 'GIRIS', 40, '2025-12-15 17:54:26', 'Haftalik stok'),
(8, 1, 'GIRIS', 20, '2025-12-16 06:31:56', 'Yeni stok girişi'),
(9, 1, 'CIKIS', 20, '2025-12-16 06:31:56', 'Satışlar'),
(10, 2, 'GIRIS', 30, '2025-12-16 06:31:56', 'Kampanya öncesi yüklü stok'),
(11, 3, 'GIRIS', 50, '2025-12-16 06:31:56', 'Normal tedarik'),
(12, 4, 'GIRIS', 40, '2025-12-16 06:31:56', 'Haftalık stok');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `urunler`
--

DROP TABLE IF EXISTS `urunler`;
CREATE TABLE IF NOT EXISTS `urunler` (
  `urun_id` int NOT NULL AUTO_INCREMENT,
  `barkod` varchar(50) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `urun_adi` varchar(150) COLLATE utf8mb4_turkish_ci NOT NULL,
  `kategori_id` int NOT NULL,
  `alis_fiyat` decimal(10,2) DEFAULT NULL,
  `satis_fiyat` decimal(10,2) NOT NULL,
  `stok_miktar` int NOT NULL DEFAULT '0',
  `stok_alt_limit` int NOT NULL DEFAULT '0',
  `skt` date DEFAULT NULL,
  `birim` enum('adet','kg','lt') COLLATE utf8mb4_turkish_ci NOT NULL DEFAULT 'adet',
  `aktif_mi` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`urun_id`),
  KEY `fk_urun_kategori` (`kategori_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Tablo döküm verisi `urunler`
--

INSERT INTO `urunler` (`urun_id`, `barkod`, `urun_adi`, `kategori_id`, `alis_fiyat`, `satis_fiyat`, `stok_miktar`, `stok_alt_limit`, `skt`, `birim`, `aktif_mi`) VALUES
(1, '1234567890123', 'Beyaz Peynir 500 gr', 2, 45.00, 59.99, 0, 5, '2025-12-20', 'adet', 1),
(2, '8809764531209', 'Meyve Suyu 1 L', 3, 10.00, 15.99, 8, 10, '2025-12-25', 'adet', 1),
(3, '1940395867432', 'Makarna 500 gr', 1, 5.00, 8.99, 32, 15, '2026-04-14', 'adet', 1),
(4, '4548736132658', 'Biskuvi 170 gr', 4, 7.00, 11.99, 12, 10, '2026-02-13', 'adet', 1),
(7, '1234567890123', 'Beyaz Peynir 500 gr', 2, 45.00, 59.99, 0, 5, '2025-12-21', 'adet', 1),
(8, '8809764531209', 'Meyve Suyu 1 L', 3, 10.00, 15.99, 8, 10, '2025-12-26', 'adet', 1),
(9, '1940395867432', 'Makarna 500 gr', 1, 5.00, 8.99, 32, 15, '2026-04-15', 'adet', 1),
(10, '4548736132658', 'Bisküvi 170 gr', 4, 7.00, 11.99, 12, 10, '2026-02-14', 'adet', 1),
(11, '9781234567890', 'Çamaşır Deterjanı 3 Kg', 5, 80.00, 115.00, 20, 5, NULL, 'adet', 1),
(12, '3216549870123', 'Yoğurt 1 Kg', 2, 20.00, 29.99, 25, 10, '2025-12-22', 'adet', 1);

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `promosyonlar`
--
ALTER TABLE `promosyonlar`
  ADD CONSTRAINT `fk_promosyon_urun` FOREIGN KEY (`urun_id`) REFERENCES `urunler` (`urun_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Tablo kısıtlamaları `stok_hareketleri`
--
ALTER TABLE `stok_hareketleri`
  ADD CONSTRAINT `fk_hareket_urun` FOREIGN KEY (`urun_id`) REFERENCES `urunler` (`urun_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Tablo kısıtlamaları `urunler`
--
ALTER TABLE `urunler`
  ADD CONSTRAINT `fk_urun_kategori` FOREIGN KEY (`kategori_id`) REFERENCES `kategoriler` (`kategori_id`) ON DELETE RESTRICT ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
