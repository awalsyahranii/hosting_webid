-- ==============================================================
-- Migration Table: portfolios
-- Database: hosting_web
-- ==============================================================

CREATE TABLE IF NOT EXISTS `portfolios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `portfolio_id` VARCHAR(100) NOT NULL UNIQUE,
  `domain` VARCHAR(255) NOT NULL,
  `url` VARCHAR(255) NOT NULL,
  `category` VARCHAR(100) NOT NULL DEFAULT 'Bisnis & Jasa',
  `category_tag` VARCHAR(100) NOT NULL DEFAULT 'PRODUKSI & JASA',
  `promo_tag` VARCHAR(100) NOT NULL DEFAULT 'Hemat 50%',
  `gradient` VARCHAR(255) NOT NULL DEFAULT 'linear-gradient(135deg, #064e3b 0%, #047857 50%, #065f46 100%)',
  `banner_title` VARCHAR(255) NOT NULL,
  `banner_desc` TEXT,
  `rating` VARCHAR(50) NOT NULL DEFAULT '4.9/5 - 42 Ulasan',
  `original_price` VARCHAR(50) NOT NULL DEFAULT 'Rp 1.500.000',
  `discount_badge` VARCHAR(50) NOT NULL DEFAULT 'Hemat 50%',
  `price` VARCHAR(50) NOT NULL DEFAULT 'Mulai Rp 750.000',
  `order_price` VARCHAR(50) NOT NULL DEFAULT 'Pesan Rp 750rb',
  `package_title` VARCHAR(255) NOT NULL,
  `feat1` VARCHAR(255) NOT NULL DEFAULT 'Tampilan Bisa Request/Custom',
  `feat2` VARCHAR(255) NOT NULL DEFAULT 'Termasuk Domain + Cloud Server',
  `image` TEXT,
  `use_screenshot` TINYINT(1) NOT NULL DEFAULT 1,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================
-- 4 Data Real Portfolio Klien
-- ==============================================================

INSERT INTO `portfolios` (`id`, `portfolio_id`, `domain`, `url`, `category`, `category_tag`, `promo_tag`, `gradient`, `banner_title`, `banner_desc`, `rating`, `original_price`, `discount_badge`, `price`, `order_price`, `package_title`, `feat1`, `feat2`, `image`, `use_screenshot`, `status`)
VALUES
(1, 'portfolio-tukangaluminiumkaca-my-id', 'tukangaluminiumkaca.my.id', 'https://www.tukangaluminiumkaca.my.id/', 'Bisnis & Jasa', 'PRODUKSI & JASA', 'Hemat 50%', 'linear-gradient(135deg, #064e3b 0%, #047857 50%, #065f46 100%)', 'Jasa Aluminium & Kaca Tempered', 'Kusen aluminium, pintu sliding, partisi kantor & etalase kaca custom', '4.9/5 - 42 Ulasan', 'Rp 1.500.000', 'Save 50%', 'Mulai Rp 750.000', 'Pesan Rp 750rb', 'Jasa Pembuatan Website Usaha Aluminium', 'Tampilan Bisa Request/Custom', 'Termasuk Domain .my.id + Cloud Server', 'https://image.thum.io/get/width/800/crop/600/https://www.tukangaluminiumkaca.my.id/', 1, 'active'),
(2, 'portfolio-terbaruindo-com', 'terbaruindo.com', 'https://terbaruindo.com/', 'Media & Berita', 'NEWS FLASH', 'SEO & AdSense Ready', 'linear-gradient(135deg, #1e1b4b 0%, #2563eb 50%, #1e40af 100%)', 'Portal Berita & Media Online Multi-Kategori', 'Support slot iklan, trending tags, dan amp speed', '5.0/5 - 58 Ulasan', 'Rp 1.500.000', 'Save 50%', 'Mulai Rp 750.000', 'Pesan Rp 750rb', 'Jasa Pembuatan Website Media Berita', 'Tampilan Bisa Request/Custom', 'Autoshare Medsos & Optimasi SEO', 'https://image.thum.io/get/width/800/crop/600/https://terbaruindo.com/', 1, 'active'),
(3, 'portfolio-aionjakarta-id', 'aionjakarta.id', 'https://aionjakarta.id/', 'Otomotif', 'FLEET & DEALER', 'Fast Booking', 'linear-gradient(135deg, #7c2d12 0%, #c2410c 50%, #ea580c 100%)', 'Dealer Resmi Mobil Listrik GAC AION', 'List varian unit, simulasi cicilan & form booking test drive', '4.9/5 - 36 Ulasan', 'Rp 1.500.000', 'Hemat 50%', 'Mulai Rp 750.000', 'Pesan Rp 750rb', 'Jasa Pembuatan Website Dealer Mobil', 'Tampilan Bisa Request/Custom', 'Form Reservasi Langsung ke WhatsApp', 'https://image.thum.io/get/width/800/crop/600/https://aionjakarta.id/', 1, 'active'),
(4, 'portfolio-hunianjabodetabek-com', 'hunianjabodetabek.com', 'https://hunianjabodetabek.com/', 'Properti', 'PROPERTI & HUNIAN', 'Hemat 50%', 'linear-gradient(135deg, #881337 0%, #be123c 50%, #9f1239 100%)', 'Listing Hunian, Rumah & Apartemen Baru', 'Katalog properti, galeri virtual tour & kalkulator simulasi KPR', '5.0/5 - 51 Ulasan', 'Rp 1.500.000', 'Save 50%', 'Mulai Rp 750.000', 'Pesan Rp 750rb', 'Jasa Pembuatan Website Properti', 'Tampilan Bisa Request/Custom', 'Integrasi Google Maps & Brosur PDF', 'https://image.thum.io/get/width/800/crop/600/https://hunianjabodetabek.com/', 1, 'active')
ON DUPLICATE KEY UPDATE
  `domain` = VALUES(`domain`),
  `url` = VALUES(`url`),
  `category` = VALUES(`category`),
  `category_tag` = VALUES(`category_tag`),
  `promo_tag` = VALUES(`promo_tag`),
  `gradient` = VALUES(`gradient`),
  `banner_title` = VALUES(`banner_title`),
  `banner_desc` = VALUES(`banner_desc`),
  `rating` = VALUES(`rating`),
  `original_price` = VALUES(`original_price`),
  `discount_badge` = VALUES(`discount_badge`),
  `price` = VALUES(`price`),
  `order_price` = VALUES(`order_price`),
  `package_title` = VALUES(`package_title`),
  `feat1` = VALUES(`feat1`),
  `feat2` = VALUES(`feat2`),
  `image` = VALUES(`image`),
  `use_screenshot` = VALUES(`use_screenshot`),
  `status` = VALUES(`status`);
