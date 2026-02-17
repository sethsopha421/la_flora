CREATE TABLE cart (
  id int(11) NOT NULL,
  user_id int(11) NOT NULL,
  product_id int(11) NOT NULL,
  quantity int(11) NOT NULL,
  created_at timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE categories (
  id int(11) NOT NULL,
  name varchar(50) NOT NULL,
  description text DEFAULT NULL,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


INSERT INTO categories (id, name, description, created_at, updated_at) VALUES
(8, 'Roses', 'Beautiful roses in various colors', '2026-02-14 05:35:09', '2026-02-14 05:35:09'),
(9, 'Lilies', 'Elegant lily arrangements', '2026-02-14 05:35:09', '2026-02-14 05:35:09'),
(10, 'Tulips', 'Colorful tulips for spring', '2026-02-14 05:35:09', '2026-02-14 05:35:09'),
(11, 'Seasonal', 'Fresh seasonal flowers', '2026-02-14 05:35:09', '2026-02-14 05:35:09'),
(12, 'Bouquets', 'Handcrafted bouquets', '2026-02-14 05:35:09', '2026-02-14 05:35:09'),
(13, 'Exotic', 'Exotic and rare flowers', '2026-02-14 05:35:09', '2026-02-14 05:35:09'),
(14, 'Luxury', 'Premium luxury arrangements', '2026-02-14 05:35:09', '2026-02-14 05:35:09');


CREATE TABLE orders (
  id int(11) NOT NULL,
  user_id int(11) DEFAULT NULL,
  total_amount decimal(10,2) NOT NULL,
  status enum('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  shipping_address text NOT NULL,
  billing_address text NOT NULL,
  payment_method varchar(50) NOT NULL,
  payment_status enum('pending','completed','failed') DEFAULT 'pending',
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE order_items (
  id int(11) NOT NULL,
  order_id int(11) NOT NULL,
  product_id int(11) NOT NULL,
  product_name varchar(255) NOT NULL,
  price decimal(10,2) NOT NULL,
  quantity int(11) NOT NULL,
  subtotal decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE password_resets (
  id int(11) NOT NULL,
  email varchar(255) NOT NULL,
  token varchar(255) NOT NULL,
  expires_at datetime NOT NULL,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  used tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE products (
  id int(11) NOT NULL,
  name varchar(100) NOT NULL,
  description text NOT NULL,
  category_id int(11) DEFAULT NULL,
  price decimal(10,2) NOT NULL,
  stock int(11) DEFAULT 0,
  image varchar(255) DEFAULT NULL,
  featured tinyint(1) DEFAULT 0,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


INSERT INTO products (id, name, description, category_id, price, stock, image, featured, created_at, updated_at) VALUES
(39, 'Lily', 'Colorful assortment of seasonal blooms.', 9, 30.50, 1, 'assets/images/products/1771050513_lily (1).jpg', 0, '2026-02-14 06:16:11', '2026-02-14 07:45:16'),
(46, 'Rose Bouquet', 'stunning bouquet of 12 fresh red roses.', 8, 30.20, 7, 'assets/images/products/1771054952_product26.jpg', 0, '2026-02-14 07:42:32', '2026-02-14 07:42:32'),
(47, 'Wedding ', 'Elegant floral centerpiece.', 12, 31.50, 5, 'assets/images/products/1771055289_Wedding.jpg', 0, '2026-02-14 07:48:09', '2026-02-14 08:05:33'),
(48, 'Lily Arrangement', 'Fresh lilies with greenery.', 9, 29.08, 10, 'assets/images/products/1771055461_lily (2).jpg', 0, '2026-02-14 07:51:01', '2026-02-14 07:51:01'),
(49, 'Tulip Carnival', 'Bright tulips in a vase.', 10, 28.50, 6, 'assets/images/products/1771055687_product22.jpg', 0, '2026-02-14 07:54:47', '2026-02-14 07:54:47'),
(50, 'White Whisper', 'Exotic orchid plant.', 11, 31.50, 6, 'assets/images/products/1771055756_product 1.jpg', 0, '2026-02-14 07:55:56', '2026-02-14 07:55:56'),
(51, 'Red Reverie', 'Rose plant.', 8, 31.09, 9, 'assets/images/products/1771055833_product25.jpg', 0, '2026-02-14 07:57:13', '2026-02-14 07:57:13'),
(52, 'Blush Serenity', 'often fragrant reproductive structure of angiosperms', 12, 29.00, 7, 'assets/images/products/1771056022_product31.jpg', 0, '2026-02-14 08:00:22', '2026-02-14 08:00:22'),
(53, 'Tiny Bells', 'flower for decor home', 12, 32.00, 7, 'assets/images/products/1771056186_product19.jpg', 0, '2026-02-14 08:03:06', '2026-02-14 08:03:06'),
(54, 'Tropical Flower Mix', 'Exotic tropical flowers including birds of paradise and heliconia', 11, 28.90, 6, 'assets/images/products/1771063613_product13).jpg', 0, '2026-02-14 10:06:53', '2026-02-14 10:06:53'),
(55, 'Lily Flower Arrangement', 'Beautiful white lilies with greenery in a modern glass vase', 9, 29.99, 5, 'assets/images/products/1771063783_product16.jpg', 0, '2026-02-14 10:09:44', '2026-02-14 10:09:44'),
(56, 'Mixed Rose Bouquet', 'Assorted colored roses in pink, yellow, and white varieties', 8, 39.99, 10, 'assets/images/products/1771063855_product24.jpg', 0, '2026-02-14 10:10:55', '2026-02-14 10:10:55'),
(57, 'Sunflower Collection', 'Bright and cheerful sunflowers with complementary wildflowers', 11, 29.99, 9, 'assets/images/products/1771063917_product15.jpg', 0, '2026-02-14 10:11:57', '2026-02-14 10:11:57'),
(58, 'Baby Breath Flowers', 'Delicate baby breath flowers perfect for weddings and special occasions', 12, 29.99, 4, 'assets/images/products/1771063992_product18.jpg', 0, '2026-02-14 10:13:12', '2026-02-14 10:13:12'),
(59, 'Autumn Flower Bouquet', 'Warm autumn colors with chrysanthemums, dahlias, and autumn leaves', 12, 29.99, 4, 'assets/images/products/1771064063_product19.jpg', 0, '2026-02-14 10:14:23', '2026-02-14 10:14:23'),
(60, 'Autumn Flower Bouquet', 'Warm autumn colors with chrysanthemums, dahlias, and autumn leaves', 10, 25.90, 5, 'assets/images/products/1771064144_product22.jpg', 0, '2026-02-14 10:15:44', '2026-02-14 10:15:44'),
(61, 'Autumn Flower Bouquet', 'Warm autumn colors with chrysanthemums, dahlias, and autumn leaves', 8, 28.70, 4, 'assets/images/products/1771064326_product27.jpg', 0, '2026-02-14 10:18:21', '2026-02-14 10:18:46'),
(62, 'Autumn Flower Bouquet', 'Warm autumn colors with chrysanthemums, dahlias, and autumn leaves', 10, 25.90, 9, 'assets/images/products/1771064385_product23.jpg', 0, '2026-02-14 10:19:45', '2026-02-14 10:19:45'),
(63, 'Autumn Flower Bouquet', 'Warm autumn colors with chrysanthemums, dahlias, and autumn leaves', 9, 27.70, 6, 'assets/images/products/1771064479_product 6.jpg', 0, '2026-02-14 10:21:19', '2026-02-14 10:21:19'),
(64, 'Autumn Flower Bouquet', 'Warm autumn colors with chrysanthemums, dahlias, and autumn leaves', 10, 26.90, 7, 'assets/images/products/1771064859_product31.jpg', 0, '2026-02-14 10:27:39', '2026-02-14 10:27:39'),


(65, 'Mix Bouquet', 'Warm autumn colors with chrysanthemums, dahlias, and autumn leaves', 12, 34.90, 6, 'assets/images/products/1771064941_product29.jpg', 0, '2026-02-14 10:29:01', '2026-02-14 10:29:01'),
(66, 'Lily Arrangement', 'Sunflower for love', 9, 36.00, 8, 'assets/images/products/1771065014_lily (10).jpg', 0, '2026-02-14 10:30:14', '2026-02-14 10:30:14'),
(67, 'Rose flosun', 'Warm autumn colors with chrysanthemums, dahlias,', 8, 30.90, 5, 'assets/images/products/1771065117_Rose1 (1).jpg', 0, '2026-02-14 10:31:57', '2026-02-14 10:31:57'),
(68, 'Tulip Carnival', 'Warm autumn colors with chrysanthemums, dahlias,', 10, 35.00, 6, 'assets/images/products/1771065196_Tulip (5).jpg', 0, '2026-02-14 10:33:16', '2026-02-14 10:33:16'),
(69, 'Classic Red Roses Bouquet', 'Our signature arrangement of 24 premium long-stem red roses.', 8, 30.80, 5, 'assets/images/products/1771070555_Rose1 (2).jpg', 0, '2026-02-14 12:02:17', '2026-02-14 12:02:35'),
(70, 'Spring Flower Bouquet', 'Celebrate the season of renewal with this stunning spring bouquet featuring fresh tulips.', 12, 31.10, 4, 'assets/images/products/1771070643_lily (14).jpg', 0, '2026-02-14 12:04:03', '2026-02-14 12:04:03'),
(71, 'Flower Arrangement', 'this low-maintenance arrangement blooms for weeks .', 11, 28.90, 10, 'assets/images/products/1771070748_Rose1 (1).jpg', 0, '2026-02-14 12:05:48', '2026-02-14 12:05:48'),
(72, 'White Wedding Flowers', 'Elegant white lilies and roses perfect for wedding ceremonies.', 13, 30.99, 5, 'assets/images/products/1771070796_wed1.jpg', 0, '2026-02-14 12:06:36', '2026-02-14 12:06:36'),
(73, 'Rose flosun', 'Soft pink lilies with delicate accents of baby', 8, 31.80, 2, 'assets/images/products/1771071000_Flowers.jpg', 0, '2026-02-14 12:10:00', '2026-02-14 12:10:00');

CREATE TABLE product_images (
  id int(11) NOT NULL,
  product_id int(11) NOT NULL,
  image_url varchar(500) NOT NULL,
  display_order int(11) DEFAULT 0,
  created_at timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE reviews (
  id int(11) NOT NULL,
  product_id int(11) NOT NULL,
  user_id int(11) NOT NULL,
  rating tinyint(4) NOT NULL,
  comment text DEFAULT NULL,
  created_at timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
  id int(11) NOT NULL,
  name varchar(100) NOT NULL,
  email varchar(100) NOT NULL,
  phone varchar(20) DEFAULT NULL,
  password varchar(255) NOT NULL,
  role enum('user','admin') DEFAULT 'user',
  status enum('active','blocked') DEFAULT 'active',
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


INSERT INTO users (id, name, email, phone, password, role, status, created_at, updated_at) VALUES
(3, 'Administrator', 'admin@laflora.com', NULL, '$2y$10$jBHLvHD0J.n/5DC204PbBeSpoCENwX7AqALrZUfrgZPsqpUb/HvpC', 'admin', 'active', '2026-02-06 05:39:01', '2026-02-06 05:53:24'),
(4, 'Test Customer', 'customer@test.com', NULL, '$2y$10$hashhere', '', 'blocked', '2026-02-06 06:27:15', '2026-02-06 11:36:07'),
(8, 'Hour Lis', 'hourlis@gmail.com', NULL, '$2y$10$aEXJc4P7g1Alv4KoTKix4eNqRgLbnYdGQoStNzxykAxtfJPruzY2m', 'user', 'active', '2026-02-09 05:25:54', '2026-02-09 05:25:54'),
(9, 'sopha', 'sopha0203@gmail.com', NULL, '$2y$10$Cg9pXluIlVgf5v/KBjO.MeT5ossnhC.XhltAQJmMZl79fZ/b3v0G6', 'user', 'active', '2026-02-11 05:17:37', '2026-02-11 05:17:37'),
(10, 'seth sopha', 'sopha2828@gmail.com', NULL, '$2y$10$RLqZnjDQPmQd3qe47vBzv.1hnSUvmGuHUF9MUhF7DuM9.Gh.zuyEq', 'user', 'active', '2026-02-14 06:18:23', '2026-02-14 06:18:23');