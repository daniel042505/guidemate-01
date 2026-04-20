-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 20, 2026 at 07:13 AM
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
-- Database: `guidemate-3`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `profile_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `user_id`, `first_name`, `last_name`, `email`, `profile_image`) VALUES
(1, 37, 'bjparan12', '', '', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `admin_messages`
--

CREATE TABLE `admin_messages` (
  `message_id` int(10) UNSIGNED NOT NULL,
  `sender_role` varchar(20) NOT NULL,
  `sender_user_id` int(10) UNSIGNED NOT NULL,
  `recipient_role` varchar(20) NOT NULL,
  `recipient_user_id` int(10) UNSIGNED NOT NULL,
  `message_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_messages`
--

INSERT INTO `admin_messages` (`message_id`, `sender_role`, `sender_user_id`, `recipient_role`, `recipient_user_id`, `message_text`, `created_at`) VALUES
(1, 'tourist', 30, 'admin', 0, 'hey', '2026-04-20 04:15:05'),
(2, 'guide', 32, 'admin', 0, 'admin', '2026-04-20 04:17:59');

-- --------------------------------------------------------

--
-- Table structure for table `booking_messages`
--

CREATE TABLE `booking_messages` (
  `message_id` int(10) UNSIGNED NOT NULL,
  `booking_id` int(10) UNSIGNED NOT NULL,
  `guide_id` int(10) UNSIGNED NOT NULL,
  `tourist_user_id` int(10) UNSIGNED NOT NULL,
  `sender_role` varchar(20) NOT NULL,
  `sender_user_id` int(10) UNSIGNED NOT NULL,
  `message_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `meet_time` datetime DEFAULT NULL,
  `meeting_location` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `booking_messages`
--

INSERT INTO `booking_messages` (`message_id`, `booking_id`, `guide_id`, `tourist_user_id`, `sender_role`, `sender_user_id`, `message_text`, `created_at`, `meet_time`, `meeting_location`) VALUES
(1, 6, 16, 30, 'guide', 32, '', '2026-04-20 03:58:48', NULL, NULL),
(2, 6, 16, 30, 'guide', 32, 'aw', '2026-04-20 03:58:55', NULL, NULL),
(3, 6, 16, 30, 'guide', 32, '', '2026-04-20 03:59:00', NULL, NULL),
(4, 6, 16, 30, 'guide', 32, '', '2026-04-20 03:59:11', NULL, NULL),
(5, 6, 16, 30, 'guide', 32, '', '2026-04-20 03:59:43', NULL, NULL),
(6, 6, 16, 30, 'tourist', 30, 'par', '2026-04-20 03:59:58', NULL, NULL),
(7, 6, 16, 30, 'guide', 32, '', '2026-04-20 04:00:18', '2026-04-20 16:00:00', 'Magellan\'s Cross'),
(8, 6, 16, 30, 'guide', 32, '', '2026-04-20 04:00:25', '2026-04-20 12:06:00', 'TOPS'),
(9, 6, 16, 30, 'guide', 32, '', '2026-04-20 04:01:25', '2026-04-20 12:06:00', 'Magellan\'s Cross'),
(10, 6, 16, 30, 'guide', 32, '', '2026-04-20 04:03:09', '2026-04-20 12:08:00', 'Magellan\'s Cross'),
(11, 6, 16, 30, 'guide', 32, '', '2026-04-20 04:03:18', '2026-04-24 12:08:00', 'Sirao Garden'),
(12, 6, 16, 30, 'guide', 32, '', '2026-04-20 04:05:27', '2026-04-30 12:05:00', 'TOPS'),
(13, 6, 16, 30, 'tourist', 30, 'aw', '2026-04-20 04:05:42', NULL, NULL),
(14, 6, 16, 30, 'guide', 32, '', '2026-04-20 04:06:02', '2026-04-20 12:11:00', 'Magellan\'s Cross'),
(15, 7, 16, 30, 'guide', 32, '', '2026-04-20 04:19:14', '2026-04-24 12:19:00', 'Sirao Garden'),
(16, 10, 16, 30, 'guide', 32, '', '2026-04-20 04:54:31', '2026-04-20 12:55:00', 'Magellan\'s Cross'),
(17, 10, 16, 30, 'guide', 32, '', '2026-04-20 04:54:33', NULL, 'Magellan\'s Cross'),
(18, 10, 16, 30, 'tourist', 30, 'okay', '2026-04-20 04:54:51', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `destinations`
--

CREATE TABLE `destinations` (
  `destination_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `contact_info` varchar(150) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `visit_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `image` varchar(255) DEFAULT NULL,
  `rating` decimal(2,1) DEFAULT 4.5,
  `review_count` int(11) DEFAULT 0,
  `price` varchar(30) DEFAULT NULL,
  `facilities_services` text DEFAULT NULL,
  `contact_information` varchar(255) DEFAULT NULL,
  `categorization` varchar(100) DEFAULT NULL,
  `is_most_visited` tinyint(1) NOT NULL DEFAULT 0,
  `is_available` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `destinations`
--

INSERT INTO `destinations` (`destination_id`, `name`, `description`, `address`, `latitude`, `longitude`, `contact_info`, `category_id`, `visit_count`, `created_at`, `image`, `rating`, `review_count`, `price`, `facilities_services`, `contact_information`, `categorization`, `is_most_visited`, `is_available`) VALUES
(6, 'Fort San Pedro', 'the oldest and smallest triangular bastion fort in the country, serving as the nucleus of the first Spanish settlement and a key military defense structure built by Miguel López de Legazpi.', 'Fort San Pedro, A. Pigafetta Street, San Roque, Cebu City, Central Visayas, 6000, Philippines', 10.29272830, 123.90589110, NULL, NULL, 0, '2026-03-06 17:41:32', 'photos/spot_1772818892_FortSanPedro.jpg', 4.5, 300, 'STUDENT - 50 REGULAR - 100', NULL, NULL, NULL, 0, 1),
(7, 'Sirao Garden', 'With its stunning celosia flowers and charming replicas of Amsterdam\'s iconic structures, Sirao Garden is a floral paradise that captivates the senses.', 'Sirao Garden, Sirao, Cebu City, Central Visayas, 6000, Philippines', 10.40704290, 123.86654740, NULL, NULL, 0, '2026-03-06 17:46:54', 'photos/spot_1772819214_SiraoGarden.jpeg', 5.0, 300, '200', NULL, NULL, NULL, 0, 1),
(8, 'TOPS', 'The Top, as it was officially called, opened in 1985, three years before Lito was elected governor of Cebu Province.', 'Cebu Tops Road, Upper Busay, Malubog, Cebu City, Central Visayas, 6000, Philippines', 10.37116190, 123.87059480, NULL, NULL, 0, '2026-03-06 17:49:18', 'photos/spot_1772819358_TOPS.jpeg', 5.0, 300, '300', NULL, NULL, NULL, 0, 1),
(9, 'Magellan\'s Cross', 'Magellan\'s Cross is a significant historical landmark located in the heart of Cebu City, Philippines. This iconic cross was planted by the Portuguese explorer Ferdinand Magellan in 1521, marking the arrival of Christianity in the Philippines.', 'Magellan\'s Cross Cebu, Cebu City Tourist Police Unit, Señor Santo Niño, Cebu City, Central Visayas, 6000, Philippines', 10.29351500, 123.90192320, NULL, NULL, 0, '2026-03-07 13:18:39', 'photos/spot_1772889519_MagellansCross.jpg', 5.0, 0, '500', '', '', 'Historical Site', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `destination_photos`
--

CREATE TABLE `destination_photos` (
  `photo_id` int(11) NOT NULL,
  `destination_id` int(11) NOT NULL,
  `photo_url` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `favorite_destinations`
--

CREATE TABLE `favorite_destinations` (
  `favorite_id` int(11) NOT NULL,
  `tourist_id` int(11) NOT NULL,
  `destination_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `guide_bookings`
--

CREATE TABLE `guide_bookings` (
  `booking_id` int(10) UNSIGNED NOT NULL,
  `tourist_user_id` int(10) UNSIGNED NOT NULL,
  `guide_id` int(10) UNSIGNED NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `meet_time` datetime DEFAULT NULL,
  `meeting_location` varchar(255) DEFAULT NULL,
  `tourist_message` text DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `guide_bookings`
--

INSERT INTO `guide_bookings` (`booking_id`, `tourist_user_id`, `guide_id`, `status`, `created_at`, `meet_time`, `meeting_location`, `tourist_message`, `approved_at`) VALUES
(1, 12, 2, 'Completed', '2026-03-06 16:55:51', NULL, NULL, NULL, '2026-03-07 00:56:29'),
(2, 12, 12, 'Completed', '2026-03-07 13:25:22', NULL, NULL, NULL, '2026-03-07 21:25:42'),
(3, 28, 12, 'Completed', '2026-03-07 13:36:02', NULL, NULL, NULL, '2026-03-07 21:36:30'),
(4, 29, 14, 'Completed', '2026-03-07 14:03:01', NULL, NULL, NULL, '2026-03-07 22:03:19'),
(5, 29, 12, 'Completed', '2026-03-07 14:05:22', NULL, NULL, NULL, '2026-03-07 22:05:41'),
(6, 30, 16, 'Completed', '2026-04-20 03:58:03', '2026-04-20 12:11:00', 'Magellan\'s Cross', NULL, '2026-04-20 11:58:28'),
(7, 30, 16, 'Completed', '2026-04-20 04:18:19', '2026-04-24 12:19:00', 'Sirao Garden', NULL, '2026-04-20 12:19:08'),
(8, 30, 16, 'Completed', '2026-04-20 04:36:29', NULL, NULL, NULL, '2026-04-20 12:38:10'),
(9, 30, 16, 'Completed', '2026-04-20 04:38:52', NULL, NULL, NULL, '2026-04-20 12:40:49'),
(10, 30, 16, 'Completed', '2026-04-20 04:42:02', '2026-04-20 12:55:00', 'Magellan\'s Cross', NULL, '2026-04-20 12:54:25');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL,
  `tourist_id` int(11) NOT NULL,
  `guide_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL,
  `comment` text DEFAULT NULL,
  `status` enum('visible','hidden','reported') DEFAULT 'visible',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`review_id`, `tourist_id`, `guide_id`, `rating`, `comment`, `status`, `created_at`) VALUES
(4, 9, 16, 4, 'Type: location\nLocation: Magellan\'s Cross\nReview: awdasd', 'visible', '2026-04-20 04:08:03');

-- --------------------------------------------------------

--
-- Table structure for table `review_replies`
--

CREATE TABLE `review_replies` (
  `reply_id` int(11) NOT NULL,
  `review_id` int(11) NOT NULL,
  `guide_id` int(11) NOT NULL,
  `reply_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tourists`
--

CREATE TABLE `tourists` (
  `tourist_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `profile_image_updated_at` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tourists`
--

INSERT INTO `tourists` (`tourist_id`, `user_id`, `first_name`, `last_name`, `email`, `phone_number`, `profile_image`, `profile_image_updated_at`) VALUES
(1, 1, 'Justine Ian', 'Valen', 'justineianvalen@gmail.com', '09387627671', 'photos/profile_1_1772425092.jpg', NULL),
(2, 6, 'Justine Ian', 'Valen', 'justine12@gmail.com', '09451578965', 'photos/profile_6_1772426342.jfif', NULL),
(3, 12, 'Jose', 'Rizal', 'rizal@gmail.com', '09456789123', 'photos/profile_12_1772781066.jpg', NULL),
(4, 15, 'Christian', 'Yongzon', 'yongzong@gmail.com', '09231456874', NULL, NULL),
(5, 16, 'Jayem', 'Rosalita', 'jayemrosalita23@gmail.com', '09332584561', NULL, NULL),
(6, 24, 'Jay', 'Cabatuan', 'jayjay@gmail.com', '09333698521', NULL, NULL),
(7, 28, 'Ian', 'Jakosalem', 'valen@gmail.com', '09106083345', 'photos/profile_28_1772890545.jpg', NULL),
(8, 29, 'Mark', 'Wakandezo', 'markmark@gmail.com', '09987412563', 'photos/profile_29_1772892163.jpg', NULL),
(9, 30, 'dan', 'dan', 'dandan@gmail.com', '09876787652', 'photos/tourist_profile_30_1776657414.jpg', '2026-04-20'),
(10, 31, 'noya', 'noya', 'noya@gmail.com', '09873654561', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tourist_favorite_destinations`
--

CREATE TABLE `tourist_favorite_destinations` (
  `favorite_id` int(10) UNSIGNED NOT NULL,
  `tourist_user_id` int(11) NOT NULL,
  `destination_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tour_guides`
--

CREATE TABLE `tour_guides` (
  `guide_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `experience_years` int(11) DEFAULT 0,
  `specialization` text DEFAULT NULL,
  `service_areas` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Pending',
  `suspended_until` date DEFAULT NULL,
  `profile_image_updated_at` date DEFAULT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `cover_image_updated_at` date DEFAULT NULL,
  `suspension_count` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tour_guides`
--

INSERT INTO `tour_guides` (`guide_id`, `user_id`, `first_name`, `last_name`, `email`, `phone_number`, `profile_image`, `experience_years`, `specialization`, `service_areas`, `status`, `suspended_until`, `profile_image_updated_at`, `cover_image`, `cover_image_updated_at`, `suspension_count`) VALUES
(1, 5, 'Justine Ian', 'Valen', 'justine12@gmail.com', '09451578965', 'photos/profile_5_1772426203.jpg', 0, NULL, NULL, 'Active', NULL, NULL, 'photos/cover_sample1.jpg', '2026-04-20', 0),
(2, 7, 'lloyd', 'Noya', 'noya123@gmail.com', '09434567894', 'photos/profile_7_1772776621.jpg', 3, 'Tours', 'Naga Cebu', 'Active', NULL, '2026-03-06', 'photos/cover_sample2.jpg', '2026-04-20', 0),
(3, 11, 'lloyd', 'Noya', 'noya123@gmail.com', '09434567894', 'photos/profile_11_1772727659.jpg', 0, NULL, NULL, 'Active', NULL, NULL, 'photos/cover_sample3.jpg', '2026-04-20', 0),
(4, 13, 'Desk', 'top', 'desk@gmail.com', '09154786932', 'photos/profile_13_1772788939.jpeg', 0, NULL, NULL, 'Active', NULL, '2026-03-06', 'photos/cover_sample4.jpg', '2026-04-20', 0),
(5, 14, 'John', 'Doe', 'johndoe212@gmail.com', '09257894561', NULL, 0, NULL, NULL, 'Active', NULL, NULL, 'photos/cover_sample1.jpg', '2026-04-20', 0),
(6, 17, 'Jayem', 'Rosalita', 'jayemrosalita23@gmail.com', '09332584561', NULL, 0, NULL, NULL, 'Active', NULL, NULL, 'photos/cover_sample2.jpg', '2026-04-20', 0),
(7, 18, 'Christian', 'Yongzon', 'chano@gmail.com', '09124567895', NULL, 0, NULL, NULL, 'Active', NULL, NULL, 'photos/cover_sample3.jpg', '2026-04-20', 0),
(8, 19, 'Sanders', 'Batonabakal', 'sanders@gmail.com', '09336547895', NULL, 0, NULL, NULL, 'Active', NULL, NULL, 'photos/cover_sample4.jpg', '2026-04-20', 0),
(9, 20, 'Braxx', 'Doex', 'braxx@gmail.com', '09106104533', NULL, 0, NULL, NULL, 'Active', NULL, NULL, 'photos/cover_sample1.jpg', '2026-04-20', 0),
(10, 21, 'Rhem', 'Al-Jus', 'Rhem@gmail.com', '09337536504', NULL, 0, NULL, NULL, 'Active', NULL, NULL, 'photos/cover_sample2.jpg', '2026-04-20', 0),
(11, 22, 'Jasmine', 'Flores', 'jas@gmail.com', '09636547854', NULL, 0, NULL, NULL, 'Active', NULL, NULL, NULL, NULL, 0),
(12, 23, 'Benjohn', 'Paran', 'paran@gmail.com', '09756547588', 'photos/profile_23_1772888767.jpg', 1, 'Tours', 'Cebu City', 'Active', NULL, '2026-03-07', NULL, NULL, 0),
(13, 25, 'Rimuru', 'Tempest', 'tempest@gmail.com', '09354561235', 'photos/profile_25_1772888714.jpg', 0, NULL, NULL, 'Active', NULL, '2026-03-07', NULL, NULL, 0),
(14, 26, 'Christian', 'Yongzon', 'yongzong@gmail.com', '09456589741', 'photos/profile_26_1772888933.jpg', 0, NULL, NULL, 'Active', NULL, '2026-03-07', NULL, NULL, 0),
(15, 27, 'John Lloyd', 'Noya', 'noynoy@gmail.com', '09122854789', 'photos/profile_27_1772889274.jpg', 0, NULL, NULL, 'Active', NULL, '2026-03-07', NULL, NULL, 0),
(16, 32, 'noya', 'noya', 'benjohn060810@gmail.com', '09876787652', 'photos/profile_32_1776658123.jpg', 5, '', 'cebu', 'Active', NULL, '2026-04-20', 'photos/cover_32_1776656849.jpg', '2026-04-20', 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('tourist','guide','admin') NOT NULL,
  `status` varchar(20) DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `status`, `created_at`) VALUES
(1, 'daniel', '$2y$10$0NgQt4cQ2Sa7VPb33jvL5.AluWjnj/9x8Fo7sC0yC2uwcewcGnHWm', 'tourist', 'Active', '2026-03-02 04:17:47'),
(5, 'jus1', '$2y$10$0L0fWuzLGLml7kOXLNobq.4m7OnVZYsY7FnTCuuumgrjC5IaDch42', '', 'Active', '2026-03-02 04:36:33'),
(6, 'jus12', '$2y$10$yLQ1zpYd1NVExb9D0y1xkOlC/3Rc65/A8nNdNCiPkHen3tyCmL8iG', 'tourist', 'Active', '2026-03-02 04:38:54'),
(7, 'noya1233', '$2y$10$Hfp5dg6IVIzg2ax.r.RGaOsPLLXaolurAJkbX/xeC4SfR/F7u.Wau', 'guide', 'Active', '2026-03-02 08:04:56'),
(8, 'Maria C.', '$2y$10$oAt7.HPBYziFUvo7l5BF9eMlVCKpNjUdzJiAkXiiBE6Rs5y1D6lY6', 'admin', 'Active', '2026-03-05 15:54:30'),
(9, 'Christian', '$2y$10$4tdVqy7MSPEpq/mlnNlmAelj3t49rc0RxrFXnj0HAO/alors40sN.', 'admin', 'Active', '2026-03-05 16:05:55'),
(11, 'noya12334', '$2y$10$PTpMTX7XaI.jnrFeo7894e7XAIMC520JYZ.M7Wrf9NtSdYCc1tjNW', 'guide', 'Active', '2026-03-05 16:20:48'),
(12, 'jose123', '$2y$10$hdfFBK2ij4GuzDpGmX.eZe4Sf4Wvo7SzZtwnolFUWYt2/immpnfvO', 'tourist', 'Active', '2026-03-06 07:10:55'),
(13, 'desktop12', '$2y$10$li44p3Cwk9SztJ1hqTyJj.6yiAd2G9AqkJwhQiR/DU3xjPRAbRcK.', 'guide', 'Active', '2026-03-06 09:22:12'),
(14, 'johndow', '$2y$10$.qeG3eWXEJLpCvIVCx0.H.BuIYxNw.i2ZyN8H3tz3C7urkDfAUgqS', 'guide', 'Active', '2026-03-06 17:53:10'),
(15, 'yongzong', '$2y$10$5Pwu3qbeCJTDZlvov/CaX.JuqssufwAT/dwdHnD67fIsDLSwep39y', 'tourist', 'Active', '2026-03-06 17:53:50'),
(16, 'rosalinda', '$2y$10$0y1l0DdGtPTeUldvV/.X3O5SVJQT.C00SGdKOORDk0yZdWfnmIVwy', 'tourist', 'Active', '2026-03-06 17:54:27'),
(17, 'rosalinda1', '$2y$10$QtiGSdEE3p3xd5xt1s6QN.LEVvl5OQlunIbEleCV1SP.WUEQpjmJe', 'guide', 'Active', '2026-03-06 17:55:28'),
(18, 'yongzong1', '$2y$10$YrXwH.8ngMHw6mZ5RL/tNOmp.3DspHBkzSQhSz9S/soZxpjew46Ve', 'guide', 'Active', '2026-03-06 17:56:29'),
(19, 'bato123', '$2y$10$x/1cY2xsMLqoTxdXTZZyQOUJPR9OGjaBIvPANGdymsFpQ0DdXzory', 'guide', 'Active', '2026-03-06 17:57:48'),
(20, 'brazx', '$2y$10$mNEz7YatkQpgoGAEqabrbutKX0oSjMKaeEJZWksLo3VI.3fsZYye6', 'guide', 'Active', '2026-03-07 12:59:49'),
(21, 'rhem', '$2y$10$7FH2PMSUOytb.EkXsPVKV.N4xYWNLihUxgZFSgoWfbKxkW.aOkYie', 'guide', 'Active', '2026-03-07 13:00:45'),
(22, 'jasjas', '$2y$10$MBnqpDq8V5fbkduRZ8zGm.SbqxgOzCf8pFz1bTvyQeQyRHIZ1kqxa', 'guide', 'Active', '2026-03-07 13:01:52'),
(23, 'bjparan', '$2y$10$Hg3.ZjlgYqr6qxm0Gl.RiO5kHMR7ohL1DaLmK1NYCjYJ4tjUbF3gK', 'guide', 'Active', '2026-03-07 13:03:35'),
(24, 'jay123', '$2y$10$ZU.gHNtDEl4gpzaLIbMEpOS70xNrNQ1khjZQUEoYlWvBAN3BAbmva', 'tourist', 'Active', '2026-03-07 13:04:02'),
(25, 'rimuru', '$2y$10$DodQgI1JvvQYU83U9jKSnuySyT7DeXVikAKpJDIiU5Y7QMzq7TGwK', 'guide', 'Active', '2026-03-07 13:04:39'),
(26, 'yoyong', '$2y$10$QOPP41lVT7omR4/VR7nvkOsnfqEW9ia3S1P7VIt9hReI0STW56fQm', 'guide', 'Active', '2026-03-07 13:08:25'),
(27, 'noynoynoy', '$2y$10$OTufKJzFCOz2c.79YSmYluQBEUsju/.1.cX2ZT3u1Q605vfN5V2l6', 'guide', 'Active', '2026-03-07 13:14:02'),
(28, 'valenlen', '$2y$10$8NBVF3zvezJDpjRkQHbf2OqGhv5OSuveyQeYaTZmlUMZh0s6eWsX6', 'tourist', 'Active', '2026-03-07 13:34:57'),
(29, 'marmar', '$2y$10$MfahDSVXh4bRP3mSkOghBOlvIi/mqwWNPrW5QrlQHlaMjghNki/jW', 'tourist', 'Active', '2026-03-07 14:02:37'),
(30, 'dandan', '$2y$10$2kXDKMV5ti.cxLd4D2buyexpFSfZbr.i1oaqSE1BM3chZTp2BH1P2', 'tourist', 'Active', '2026-04-20 03:42:32'),
(31, 'noya', '$2y$10$MOEPmsJNtgkYvGwJDIM8bOjXi6VinjmnVRNnWaAZx3qNRt9AXd9wu', 'tourist', 'Active', '2026-04-20 03:45:35'),
(32, 'noya1', '$2y$10$KtBC.XWtcsMNGVBtl5/tCu7iQxFKk5aQrPH36zQbwM/tPsxvHrhV2', 'guide', 'Active', '2026-04-20 03:46:15'),
(33, 'awsaws', 'awsaws123', '', 'Active', '2026-04-20 03:50:01'),
(34, '', '', 'admin', 'Active', '2026-04-20 03:50:01'),
(35, 'awsaws', 'awsaws123', 'admin', 'Active', '2026-04-20 03:50:18'),
(36, 'dandandan', '$2y$10$AHHf5CjqqYcIl7Up3htcTeRL0LDHFbIFtCbG5IrhbVWpV5bZzhCii', 'admin', 'Active', '2026-04-20 03:55:05'),
(37, 'bjparan12', '$2y$10$6Pz7ds4NOt3zqECHaUtG7OwgpWjINJDw4Gq8Z8bt5OwzS5zZngWvq', 'admin', 'Active', '2026-04-20 03:56:03');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `admin_messages`
--
ALTER TABLE `admin_messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `idx_admin_messages_sender` (`sender_role`,`sender_user_id`),
  ADD KEY `idx_admin_messages_recipient` (`recipient_role`,`recipient_user_id`),
  ADD KEY `idx_admin_messages_created_at` (`created_at`);

--
-- Indexes for table `booking_messages`
--
ALTER TABLE `booking_messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `idx_booking_messages_booking` (`booking_id`),
  ADD KEY `idx_booking_messages_sender` (`sender_user_id`),
  ADD KEY `idx_booking_messages_guide` (`guide_id`),
  ADD KEY `idx_booking_messages_tourist` (`tourist_user_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indexes for table `destinations`
--
ALTER TABLE `destinations`
  ADD PRIMARY KEY (`destination_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `destination_photos`
--
ALTER TABLE `destination_photos`
  ADD PRIMARY KEY (`photo_id`),
  ADD KEY `destination_id` (`destination_id`);

--
-- Indexes for table `favorite_destinations`
--
ALTER TABLE `favorite_destinations`
  ADD PRIMARY KEY (`favorite_id`),
  ADD UNIQUE KEY `tourist_id` (`tourist_id`,`destination_id`),
  ADD KEY `destination_id` (`destination_id`);

--
-- Indexes for table `guide_bookings`
--
ALTER TABLE `guide_bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `idx_guide_bookings_status` (`status`),
  ADD KEY `idx_guide_bookings_guide` (`guide_id`),
  ADD KEY `idx_guide_bookings_tourist` (`tourist_user_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `tourist_id` (`tourist_id`),
  ADD KEY `guide_id` (`guide_id`);

--
-- Indexes for table `review_replies`
--
ALTER TABLE `review_replies`
  ADD PRIMARY KEY (`reply_id`),
  ADD KEY `review_id` (`review_id`),
  ADD KEY `guide_id` (`guide_id`);

--
-- Indexes for table `tourists`
--
ALTER TABLE `tourists`
  ADD PRIMARY KEY (`tourist_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `tourist_favorite_destinations`
--
ALTER TABLE `tourist_favorite_destinations`
  ADD PRIMARY KEY (`favorite_id`),
  ADD UNIQUE KEY `uniq_tourist_destination` (`tourist_user_id`,`destination_id`),
  ADD KEY `idx_tourist_user_id` (`tourist_user_id`),
  ADD KEY `idx_destination_id` (`destination_id`);

--
-- Indexes for table `tour_guides`
--
ALTER TABLE `tour_guides`
  ADD PRIMARY KEY (`guide_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `admin_messages`
--
ALTER TABLE `admin_messages`
  MODIFY `message_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `booking_messages`
--
ALTER TABLE `booking_messages`
  MODIFY `message_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `destinations`
--
ALTER TABLE `destinations`
  MODIFY `destination_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `destination_photos`
--
ALTER TABLE `destination_photos`
  MODIFY `photo_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `favorite_destinations`
--
ALTER TABLE `favorite_destinations`
  MODIFY `favorite_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `guide_bookings`
--
ALTER TABLE `guide_bookings`
  MODIFY `booking_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `review_replies`
--
ALTER TABLE `review_replies`
  MODIFY `reply_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tourists`
--
ALTER TABLE `tourists`
  MODIFY `tourist_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `tourist_favorite_destinations`
--
ALTER TABLE `tourist_favorite_destinations`
  MODIFY `favorite_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tour_guides`
--
ALTER TABLE `tour_guides`
  MODIFY `guide_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admins`
--
ALTER TABLE `admins`
  ADD CONSTRAINT `admins_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `destinations`
--
ALTER TABLE `destinations`
  ADD CONSTRAINT `destinations_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`);

--
-- Constraints for table `destination_photos`
--
ALTER TABLE `destination_photos`
  ADD CONSTRAINT `destination_photos_ibfk_1` FOREIGN KEY (`destination_id`) REFERENCES `destinations` (`destination_id`) ON DELETE CASCADE;

--
-- Constraints for table `favorite_destinations`
--
ALTER TABLE `favorite_destinations`
  ADD CONSTRAINT `favorite_destinations_ibfk_1` FOREIGN KEY (`tourist_id`) REFERENCES `tourists` (`tourist_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `favorite_destinations_ibfk_2` FOREIGN KEY (`destination_id`) REFERENCES `destinations` (`destination_id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`tourist_id`) REFERENCES `tourists` (`tourist_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`guide_id`) REFERENCES `tour_guides` (`guide_id`) ON DELETE CASCADE;

--
-- Constraints for table `review_replies`
--
ALTER TABLE `review_replies`
  ADD CONSTRAINT `review_replies_ibfk_1` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`review_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `review_replies_ibfk_2` FOREIGN KEY (`guide_id`) REFERENCES `tour_guides` (`guide_id`) ON DELETE CASCADE;

--
-- Constraints for table `tourists`
--
ALTER TABLE `tourists`
  ADD CONSTRAINT `tourists_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tour_guides`
--
ALTER TABLE `tour_guides`
  ADD CONSTRAINT `tour_guides_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
