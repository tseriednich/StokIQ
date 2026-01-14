-- StokIQ Projesi - MySQL Şema ve Örnek Veriler (TÜRKÇE KARAKTER DESTEKLİ)

CREATE DATABASE IF NOT EXISTS stokiq_db
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_turkish_ci;

USE stokiq_db;

-- Kategoriler
CREATE TABLE IF NOT EXISTS kategoriler (
  kategori_id INT AUTO_INCREMENT PRIMARY KEY,
  kategori_adi VARCHAR(100) NOT NULL,
  aciklama VARCHAR(255) NULL
) ENGINE=InnoDB;

-- Ürünler
CREATE TABLE IF NOT EXISTS urunler (
  urun_id INT AUTO_INCREMENT PRIMARY KEY,
  barkod VARCHAR(50) NULL,
  urun_adi VARCHAR(150) NOT NULL,
  kategori_id INT NOT NULL,
  alis_fiyat DECIMAL(10,2) NULL,
  satis_fiyat DECIMAL(10,2) NOT NULL,
  stok_miktar INT NOT NULL DEFAULT 0,
  stok_alt_limit INT NOT NULL DEFAULT 0,
  skt DATE NULL,
  birim ENUM('adet','kg','lt') NOT NULL DEFAULT 'adet',
  aktif_mi TINYINT(1) NOT NULL DEFAULT 1,
  CONSTRAINT fk_urun_kategori
    FOREIGN KEY (kategori_id) REFERENCES kategoriler(kategori_id)
      ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Stok hareketleri
CREATE TABLE IF NOT EXISTS stok_hareketleri (
  hareket_id INT AUTO_INCREMENT PRIMARY KEY,
  urun_id INT NOT NULL,
  tip ENUM('GIRIS','CIKIS','IADE','SAYIM') NOT NULL,
  miktar INT NOT NULL,
  tarih DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  aciklama VARCHAR(255) NULL,
  CONSTRAINT fk_hareket_urun
    FOREIGN KEY (urun_id) REFERENCES urunler(urun_id)
      ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Promosyonlar
CREATE TABLE IF NOT EXISTS promosyonlar (
  promosyon_id INT AUTO_INCREMENT PRIMARY KEY,
  urun_id INT NOT NULL,
  indirim_yuzde DECIMAL(5,2) NULL,
  indirim_tutar DECIMAL(10,2) NULL,
  baslangic_tarih DATE NOT NULL,
  bitis_tarih DATE NOT NULL,
  neden ENUM('SKT_YAKLASIYOR','STOK_FAZLA','MANUEL') NOT NULL,
  aktif_mi TINYINT(1) NOT NULL DEFAULT 1,
  CONSTRAINT fk_promosyon_urun
    FOREIGN KEY (urun_id) REFERENCES urunler(urun_id)
      ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Kullanıcılar (opsiyonel, ama ileride genişletmek için)
CREATE TABLE IF NOT EXISTS kullanicilar (
  kullanici_id INT AUTO_INCREMENT PRIMARY KEY,
  kullanici_adi VARCHAR(50) NOT NULL UNIQUE,
  sifre_hash VARCHAR(255) NOT NULL,
  rol ENUM('admin','personel') NOT NULL DEFAULT 'admin',
  aktif_mi TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- Örnek kategoriler (TÜRKÇE KARAKTERLER DÜZELTİLDİ)
INSERT INTO kategoriler (kategori_adi, aciklama) VALUES
('Temel Gıdalar', 'Un, makarna, bakliyat vb.'),
('Süt Ürünleri', 'Süt, yoğurt, peynir vb.'),
('İçecek', 'Su, meyve suyu, gazlı içecekler'),
('Atıştırmalık', 'Çerez, bisküvi vb.'),
('Temizlik', 'Deterjan, sabun vb.');

-- Örnek ürünler (TÜRKÇE KARAKTERLER DÜZELTİLDİ)
INSERT INTO urunler (barkod, urun_adi, kategori_id, alis_fiyat, satis_fiyat, stok_miktar, stok_alt_limit, skt, birim, aktif_mi) VALUES
('1234567890123', 'Beyaz Peynir 500 gr', 2, 45.00, 59.99, 0, 5, DATE_ADD(CURDATE(), INTERVAL 5 DAY), 'adet', 1),
('8809764531209', 'Meyve Suyu 1 L', 3, 10.00, 15.99, 8, 10, DATE_ADD(CURDATE(), INTERVAL 10 DAY), 'adet', 1),
('1940395867432', 'Makarna 500 gr', 1, 5.00, 8.99, 32, 15, DATE_ADD(CURDATE(), INTERVAL 120 DAY), 'adet', 1),
('4548736132658', 'Bisküvi 170 gr', 4, 7.00, 11.99, 12, 10, DATE_ADD(CURDATE(), INTERVAL 60 DAY), 'adet', 1),
('9781234567890', 'Çamaşır Deterjanı 3 Kg', 5, 80.00, 115.00, 20, 5, NULL, 'adet', 1),
('3216549870123', 'Yoğurt 1 Kg', 2, 20.00, 29.99, 25, 10, DATE_ADD(CURDATE(), INTERVAL 6 DAY), 'adet', 1);

-- Örnek stok hareketleri (TÜRKÇE AÇIKLAMALAR)
INSERT INTO stok_hareketleri (urun_id, tip, miktar, aciklama) VALUES
(1, 'GIRIS', 20, 'Yeni stok girişi'),
(1, 'CIKIS', 20, 'Satışlar'),
(2, 'GIRIS', 30, 'Kampanya öncesi yüklü stok'),
(3, 'GIRIS', 50, 'Normal tedarik'),
(4, 'GIRIS', 40, 'Haftalık stok'),
(5, 'GIRIS', 15, 'Depo çıkışı'),
(6, 'GIRIS', 25, 'Yoğurt stok yenileme');

-- Örnek promosyonlar
INSERT INTO promosyonlar (urun_id, indirim_yuzde, indirim_tutar, baslangic_tarih, bitis_tarih, neden, aktif_mi) VALUES
(2, 15.00, NULL, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 7 DAY), 'STOK_FAZLA', 1),
(6, NULL, 5.00, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 5 DAY), 'SKT_YAKLASIYOR', 1),
(1, 20.00, NULL, DATE_ADD(CURDATE(), INTERVAL -10 DAY), DATE_ADD(CURDATE(), INTERVAL 3 DAY), 'MANUEL', 1);