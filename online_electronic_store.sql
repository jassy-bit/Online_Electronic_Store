-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 06, 2026 at 03:30 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `online_electronic_store`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_activity`
--

CREATE TABLE `admin_activity` (
  `id` int(10) UNSIGNED NOT NULL,
  `admin_id` int(10) UNSIGNED NOT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_activity`
--

INSERT INTO `admin_activity` (`id`, `admin_id`, `action`, `description`, `ip_address`, `created_at`) VALUES
(2, 3, 'Login', 'Admin logged into ElectroDeals.', '127.0.0.1', '2026-09-29 15:09:40');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(10) UNSIGNED NOT NULL,
  `customer_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Audio', NULL, 'active', '2026-09-29 15:27:06', '2026-09-29 15:27:06'),
(2, 'Accessories', NULL, 'active', '2026-09-29 15:27:06', '2026-09-29 15:27:06'),
(3, 'Desktops', NULL, 'active', '2026-09-29 15:27:06', '2026-09-29 15:27:06'),
(4, 'Gaming', NULL, 'active', '2026-09-29 15:27:06', '2026-09-29 15:27:06'),
(5, 'Laptops', NULL, 'active', '2026-09-29 15:27:06', '2026-09-29 15:27:06'),
(6, 'Mobile', NULL, 'active', '2026-09-29 15:27:06', '2026-09-29 15:27:06'),
(7, 'Peripherals', NULL, 'active', '2026-09-29 15:27:06', '2026-09-29 15:27:06'),
(8, 'Smart Home', NULL, 'active', '2026-09-29 15:27:06', '2026-09-29 15:27:06'),
(9, 'Smart Watches', NULL, 'active', '2026-09-29 15:27:06', '2026-09-29 15:27:06'),
(10, 'Tablets', NULL, 'active', '2026-09-29 15:27:06', '2026-09-29 15:27:06');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(10) UNSIGNED NOT NULL,
  `customer_id` int(10) UNSIGNED NOT NULL,
  `order_number` varchar(30) NOT NULL,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `shipping_address` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `status` enum('pending','confirmed','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `subtotal` decimal(12,2) GENERATED ALWAYS AS (`quantity` * `price`) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(10) UNSIGNED NOT NULL,
  `supplier_id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(200) NOT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `model_number` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(12,2) NOT NULL,
  `stock` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `image` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `supplier_id`, `category_id`, `name`, `brand`, `model_number`, `description`, `price`, `stock`, `image`, `status`, `created_at`, `updated_at`) VALUES
(11, 1, 1, 'Sony WH-1000XM5 Wireless Headphones', 'Sony', 'WH1000XM5', 'Premium wireless noise cancelling headphones with high quality sound.', 39999.00, 15, 'Images/sony-wh1000xm5.jpg', 'active', '2026-09-29 15:21:20', '2026-09-29 15:30:30'),
(12, 1, 2, 'Logitech MX Master 3S Mouse', 'Logitech', 'MX-MASTER-3S', 'Wireless ergonomic mouse suitable for office and professional use.', 12999.00, 20, 'Images/logitech-mx-master-3s.jpg', 'active', '2026-09-29 15:21:20', '2026-09-29 15:30:55'),
(13, 1, 3, 'Dell Inspiron 15 Laptop', 'Dell', 'INSPIRON-15', 'Powerful laptop suitable for study, office work and daily tasks.', 85000.00, 10, 'Images/dell-inspiron-15.jpg', 'active', '2026-09-29 15:21:20', '2026-09-29 15:31:30'),
(14, 2, 4, 'ASUS ROG Gaming Laptop', 'ASUS', 'ROG-STRIX-G16', 'High performance gaming laptop with powerful processor and graphics.', 175000.00, 8, 'assets/images/asus-rog-g16.jpg', 'active', '2026-09-29 15:21:20', '2026-09-29 15:21:20'),
(15, 2, 5, 'Lenovo IdeaPad 5', 'Lenovo', 'IDEAPAD-5', 'Reliable laptop designed for students and everyday productivity.', 78000.00, 12, 'assets/images/lenovo-ideapad-5.jpg', 'active', '2026-09-29 15:21:20', '2026-09-29 15:21:20'),
(16, 2, 6, 'Samsung Galaxy S25', 'Samsung', 'GALAXY-S25', 'Modern smartphone with advanced camera and high performance.', 125000.00, 10, 'assets/images/samsung-s25.jpg', 'active', '2026-09-29 15:21:20', '2026-09-29 15:21:20'),
(17, 1, 7, 'Logitech G502 Gaming Mouse', 'Logitech', 'G502-HERO', 'Gaming mouse with programmable buttons and accurate tracking.', 8999.00, 25, 'Images/logitech-g502.jpg', 'active', '2026-09-29 15:21:20', '2026-09-29 15:35:26'),
(18, 1, 8, 'Amazon Echo Dot', 'Amazon', 'ECHO-DOT-5', 'Compact smart speaker with voice assistant functionality.', 6999.00, 18, 'assets/images/echo-dot.jpg', 'active', '2026-09-29 15:21:20', '2026-09-29 15:21:20'),
(19, 2, 9, 'Apple Watch Series 10', 'Apple', 'WATCH-S10', 'Smartwatch with fitness tracking and smart connectivity features.', 85000.00, 7, 'assets/images/apple-watch-10.jpg', 'active', '2026-09-29 15:21:20', '2026-09-29 15:21:20'),
(20, 2, 10, 'Samsung Galaxy Tab S10', 'Samsung', 'GALAXY-TAB-S10', 'High performance tablet suitable for entertainment and productivity.', 95000.00, 9, 'assets/images/galaxy-tab-s10.jpg', 'active', '2026-09-29 15:21:20', '2026-09-29 15:21:20');

-- --------------------------------------------------------

--
-- Table structure for table `product_views`
--

CREATE TABLE `product_views` (
  `id` int(10) UNSIGNED NOT NULL,
  `customer_id` int(10) UNSIGNED DEFAULT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `viewed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(10) UNSIGNED NOT NULL,
  `customer_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `rating` tinyint(3) UNSIGNED NOT NULL,
  `review_text` text NOT NULL,
  `sentiment` enum('positive','negative','neutral') DEFAULT NULL,
  `sentiment_score` decimal(6,4) DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL,
  `sale_amount` decimal(12,2) NOT NULL,
  `sale_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `company_name` varchar(150) NOT NULL,
  `contact_person` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(150) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `user_id`, `company_name`, `contact_person`, `phone`, `email`, `address`, `status`, `created_at`, `updated_at`) VALUES
(1, 2, 'ElectroDeals Supplier', 'Supplier', '9855056039', 'supplier112@gmail.com', 'Kathmandu', 'active', '2026-09-29 14:48:06', '2026-09-29 14:48:06');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `role` enum('admin','supplier','customer') NOT NULL DEFAULT 'customer',
  `status` enum('active','inactive','blocked') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `address`, `role`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Jasmine Bhagat', 'jasminebhagat111@gmail.com', '$2y$10$e2orZxzoD7HgRLakG8bZk.dh5F8FTvrJtreubz2WDixyplb12vHnq', '9814234729', 'Bharatpur', 'customer', 'active', '2026-09-29 13:32:24', '2026-09-29 13:32:24'),
(2, 'Supplier', 'supplier112@gmail.com', '$2y$10$QACIX/ef4YtzxEOegkMpkO.tvnj/NPk8/nMN/Vh6K48JmFXF1CiRO', '9855056039', 'Kathmandu', 'supplier', 'active', '2026-09-29 13:44:12', '2026-09-29 14:46:47'),
(3, 'Admin', 'admin111@gmail.com', '$2y$10$B21DcS.WTkNboz9slxYhEezklTSy.Z4ycvZ2IH1c3/g9fjnlhKItm', '9819255700', 'Hetauda', 'admin', 'active', '2026-09-29 14:49:51', '2026-09-29 14:51:15');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_activity`
--
ALTER TABLE `admin_activity`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_activity_admin` (`admin_id`),
  ADD KEY `idx_activity_date` (`created_at`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_customer_product` (`customer_id`,`product_id`),
  ADD KEY `fk_cart_product` (`product_id`),
  ADD KEY `idx_cart_customer` (`customer_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `idx_order_customer` (`customer_id`),
  ADD KEY `idx_order_status` (`status`),
  ADD KEY `idx_order_date` (`created_at`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_item_order` (`order_id`),
  ADD KEY `idx_order_item_product` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product_category` (`category_id`),
  ADD KEY `idx_product_supplier` (`supplier_id`),
  ADD KEY `idx_product_status` (`status`),
  ADD KEY `idx_product_name` (`name`);

--
-- Indexes for table `product_views`
--
ALTER TABLE `product_views`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_view_customer` (`customer_id`),
  ADD KEY `idx_view_product` (`product_id`),
  ADD KEY `idx_view_date` (`viewed_at`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_review_customer` (`customer_id`),
  ADD KEY `idx_review_product` (`product_id`),
  ADD KEY `idx_review_sentiment` (`sentiment`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sales_product` (`product_id`),
  ADD KEY `idx_sales_date` (`sale_date`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `idx_supplier_status` (`status`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_role` (`role`),
  ADD KEY `idx_users_status` (`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_activity`
--
ALTER TABLE `admin_activity`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `product_views`
--
ALTER TABLE `product_views`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_activity`
--
ALTER TABLE `admin_activity`
  ADD CONSTRAINT `fk_activity_admin` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `fk_cart_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cart_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_order_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_order_item_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_order_item_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_product_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `fk_product_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`);

--
-- Constraints for table `product_views`
--
ALTER TABLE `product_views`
  ADD CONSTRAINT `fk_view_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_view_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `fk_review_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_review_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `fk_sale_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD CONSTRAINT `fk_supplier_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
