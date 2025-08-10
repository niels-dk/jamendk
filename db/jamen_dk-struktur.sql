-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: gehri.iad1-mysql-e2-11a.dreamhost.com
-- Generation Time: Aug 10, 2025 at 01:09 PM
-- Server version: 8.0.28-0ubuntu0.20.04.3
-- PHP Version: 8.1.2-1ubuntu2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `jamen_dk`
--

-- --------------------------------------------------------

--
-- Table structure for table `dream_anchors`
--

CREATE TABLE `dream_anchors` (
  `id` int NOT NULL,
  `dream_id` int DEFAULT NULL,
  `anchor_type` enum('location','brand','person','season') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `value` text COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dream_boards`
--

CREATE TABLE `dream_boards` (
  `id` int NOT NULL,
  `slug` char(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `archived` tinyint(1) NOT NULL DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  `type` varchar(20) COLLATE utf8mb4_general_ci DEFAULT 'dream'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dream_brands`
--

CREATE TABLE `dream_brands` (
  `id` int NOT NULL,
  `dream_id` int NOT NULL,
  `brand` varchar(255) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dream_locations`
--

CREATE TABLE `dream_locations` (
  `id` int NOT NULL,
  `dream_id` int NOT NULL,
  `location` varchar(255) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dream_people`
--

CREATE TABLE `dream_people` (
  `id` int NOT NULL,
  `dream_id` int NOT NULL,
  `person` varchar(255) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dream_seasons`
--

CREATE TABLE `dream_seasons` (
  `id` int NOT NULL,
  `dream_id` int NOT NULL,
  `season` varchar(50) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `visions`
--

CREATE TABLE `visions` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `slug` varchar(32) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` mediumtext,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `archived` tinyint(1) NOT NULL DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vision_anchors`
--

CREATE TABLE `vision_anchors` (
  `id` int NOT NULL,
  `board_id` int NOT NULL,
  `key` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `value` text COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vision_presentation`
--

CREATE TABLE `vision_presentation` (
  `vision_id` int NOT NULL,
  `relations` tinyint NOT NULL DEFAULT '1',
  `goals` tinyint NOT NULL DEFAULT '1',
  `budget` tinyint NOT NULL DEFAULT '1',
  `roles` tinyint NOT NULL DEFAULT '0',
  `contacts` tinyint NOT NULL DEFAULT '1',
  `documents` tinyint NOT NULL DEFAULT '1',
  `workflow` tinyint NOT NULL DEFAULT '1',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `dream_anchors`
--
ALTER TABLE `dream_anchors`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `dream_boards`
--
ALTER TABLE `dream_boards`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `dream_brands`
--
ALTER TABLE `dream_brands`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `dream_locations`
--
ALTER TABLE `dream_locations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `dream_people`
--
ALTER TABLE `dream_people`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `dream_seasons`
--
ALTER TABLE `dream_seasons`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `visions`
--
ALTER TABLE `visions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_archived` (`archived`),
  ADD KEY `idx_deleted` (`deleted_at`);

--
-- Indexes for table `vision_anchors`
--
ALTER TABLE `vision_anchors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `board_id` (`board_id`);

--
-- Indexes for table `vision_presentation`
--
ALTER TABLE `vision_presentation`
  ADD PRIMARY KEY (`vision_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `dream_anchors`
--
ALTER TABLE `dream_anchors`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dream_boards`
--
ALTER TABLE `dream_boards`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dream_brands`
--
ALTER TABLE `dream_brands`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dream_locations`
--
ALTER TABLE `dream_locations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dream_people`
--
ALTER TABLE `dream_people`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dream_seasons`
--
ALTER TABLE `dream_seasons`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `visions`
--
ALTER TABLE `visions`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vision_anchors`
--
ALTER TABLE `vision_anchors`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `vision_anchors`
--
ALTER TABLE `vision_anchors`
  ADD CONSTRAINT `vision_anchors_ibfk_1` FOREIGN KEY (`board_id`) REFERENCES `dream_boards` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
