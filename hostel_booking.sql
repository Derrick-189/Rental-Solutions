-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 27, 2025 at 12:48 PM
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
-- Database: `hostel_booking`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_messages`
--

CREATE TABLE `admin_messages` (
  `message_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `target_user_type` enum('all','students','landlords') DEFAULT 'all',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `booking_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `hostel_id` int(11) NOT NULL,
  `landlord_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `platform_fee` decimal(10,2) NOT NULL,
  `status` enum('pending','confirmed','cancelled','completed') DEFAULT 'pending',
  `payment_method` enum('mobile_money','credit_card','bank_transfer') NOT NULL,
  `payment_status` enum('pending','paid','failed','refunded') DEFAULT 'pending',
  `transaction_id` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`booking_id`, `student_id`, `hostel_id`, `landlord_id`, `start_date`, `end_date`, `total_amount`, `platform_fee`, `status`, `payment_method`, `payment_status`, `transaction_id`, `created_at`, `updated_at`) VALUES
(1, 4, 3, 3, '2025-09-27', '0000-00-00', 105000.00, 5000.00, 'pending', 'mobile_money', 'pending', NULL, '2025-09-27 08:02:17', '2025-09-27 08:02:17'),
(2, 6, 8, 5, '2025-09-27', '0000-00-00', 1050.00, 50.00, 'pending', 'mobile_money', 'pending', NULL, '2025-09-27 10:03:34', '2025-09-27 10:03:34');

-- --------------------------------------------------------

--
-- Table structure for table `hostels`
--

CREATE TABLE `hostels` (
  `hostel_id` int(11) NOT NULL,
  `landlord_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `address` text NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `price_per_month` decimal(10,2) NOT NULL,
  `rooms_available` int(11) NOT NULL,
  `university_id` int(11) NOT NULL,
  `distance_to_university` decimal(5,2) DEFAULT NULL,
  `amenities` text DEFAULT NULL,
  `rules` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hostels`
--

INSERT INTO `hostels` (`hostel_id`, `landlord_id`, `name`, `description`, `address`, `latitude`, `longitude`, `price_per_month`, `rooms_available`, `university_id`, `distance_to_university`, `amenities`, `rules`, `created_at`, `updated_at`) VALUES
(1, 3, 'Bunker', 'you will find what you need here', '7062 University Rd, Kampala', 0.31360000, 32.58110000, 1000.00, 12, 1, 2.59, '[\"WiFi\",\"Water\",\"Electricity\",\"Security\",\"Parking\"]', 'no parties', '2025-06-14 18:03:44', '2025-06-14 18:03:44'),
(2, 3, 'speed', 'best', '202025', 0.31360000, 32.58110000, 1000.00, 20, 1, 2.59, '[\"WiFi\",\"Water\",\"Electricity\",\"Laundry\"]', 'no rules', '2025-06-17 14:19:32', '2025-06-17 14:19:32'),
(3, 3, 'Cave', 'secure', '7062 University Rd, Kampala', 0.31360000, 32.58110000, 100000.00, 17, 1, 2.59, '[\"WiFi\",\"Water\",\"Electricity\",\"Laundry\"]', 'be honest', '2025-06-17 16:52:23', '2025-09-09 13:37:36'),
(4, 3, 'Arrow', 'closest to campus', '835531', 0.31360000, 32.58110000, 1000.00, 22, 1, 2.59, '[\"WiFi\",\"Water\",\"Electricity\",\"Security\",\"Parking\"]', 'none', '2025-06-17 18:16:20', '2025-06-17 18:16:21'),
(5, 3, 'make', 'hhhhhhhh', 'kabale', 0.31360000, 32.58110000, 1000.00, 16, 4, 19.53, '[\"WiFi\",\"Water\",\"Electricity\",\"Parking\"]', 'no fighting', '2025-06-23 06:30:43', '2025-06-23 06:30:43'),
(6, 3, 'HOPE', 'luffy', 'Kavco', -1.27034143, 29.99394619, 400000.00, 17, 4, 355.86, '[\"Water\",\"Electricity\",\"Security\"]', 'no pets allowed', '2025-09-09 13:08:42', '2025-09-09 13:08:42'),
(7, 3, 'Fortress', 'Fortress offers affordable accommodation suitable for students and budget travelers. \\r\\nIt is located near Kabale town main road making it convenient for accessing local amenities like banks, supermarkets, and restaurants.', 'https://maps.app.goo.gl/2uAD6FmmHMYit65e7', -1.27119200, 29.99217100, 1000.00, 25, 6, 0.29, '[\"WiFi\",\"Water\",\"Electricity\"]', 'ensure to lock your doors to avoid any complications', '2025-09-27 08:46:04', '2025-09-27 08:46:04'),
(8, 5, 'Kensas', 'the hostel of your dreams', '27 Kabale-Katuna-Kigali Rd, Kabale', -1.27065200, 29.99324300, 1000.00, 20, 6, 0.42, '[\"WiFi\",\"Water\",\"Electricity\",\"Parking\"]', 'gate closes at 11:00 pm', '2025-09-27 09:57:06', '2025-09-27 09:57:07');

-- --------------------------------------------------------

--
-- Table structure for table `hostel_images`
--

CREATE TABLE `hostel_images` (
  `image_id` int(11) NOT NULL,
  `hostel_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hostel_images`
--

INSERT INTO `hostel_images` (`image_id`, `hostel_id`, `image_path`, `is_primary`) VALUES
(1, 6, '68c026da3d0b7_1757423322.jpeg', 1),
(2, 5, '68c02a0d4d77a_1757424141.jpeg', 0),
(3, 4, '68c02a653d461_1757424229.webp', 0),
(4, 3, '68c02da02e0ac_1757425056.jpeg', 0),
(5, 2, '68c02db0d5912_1757425072.jpeg', 0),
(6, 1, '68c02dbf89971_1757425087.jpeg', 0),
(7, 7, '68d7a44c83560_1758962764.jpeg', 1),
(8, 8, '68d7b4f316335_1758967027.jpeg', 1);

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `message_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`message_id`, `sender_id`, `receiver_id`, `subject`, `message`, `is_read`, `created_at`) VALUES
(1, 3, 1, 'admin request', 'we need to talk', 1, '2025-06-14 18:38:07'),
(2, 3, 1, 'admin request', 'we need to talk', 1, '2025-06-14 18:38:24'),
(3, 3, 1, 'admin request', 'we need to talk', 1, '2025-06-14 18:38:32'),
(4, 1, 3, 'Re: admin request', 'i will be available soon', 1, '2025-06-17 06:42:49'),
(5, 1, 1, 'Re: Re: admin request', 'yeah', 0, '2025-06-18 11:50:29'),
(7, 4, 1, 'images', 'i don&#039;t see my profile pic', 0, '2025-09-08 06:51:17'),
(8, 4, 1, 'hello', 'these landlords', 0, '2025-09-09 13:50:19'),
(9, 4, 1, 'rooms', 'these rooms are in poor condition', 0, '2025-09-09 13:52:10');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `reset_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`reset_id`, `user_id`, `token`, `expires_at`, `created_at`) VALUES
(3, 8, '72aa9c1f09e20dab4a9418a3492bcb465472408422402a731e69af206baf1248', '2025-09-27 13:28:47', '2025-09-27 10:28:47');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `platform_fee` decimal(10,2) NOT NULL,
  `payment_method` enum('mobile_money','credit_card','bank_transfer') NOT NULL,
  `transaction_id` varchar(100) NOT NULL,
  `status` enum('pending','completed','failed') DEFAULT 'pending',
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `hostel_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`setting_key`, `setting_value`, `updated_at`) VALUES
('currency', 'UGX', '2025-06-16 17:45:04'),
('enable_payments', '1', '2025-06-16 17:45:04'),
('payment_methods', 'mobile_money,credit_card', '2025-06-16 17:45:04'),
('platform_email', 'admin@hostelbookings.com', '2025-06-16 17:45:04'),
('platform_fee_percentage', '2', '2025-09-27 10:10:36'),
('platform_name', 'Crib Hunt', '2025-06-16 18:18:27'),
('smtp_host', 'smtp.example.com', '2025-06-16 17:45:04'),
('smtp_password', '', '2025-06-16 17:45:04'),
('smtp_port', '587', '2025-06-16 17:45:04'),
('smtp_secure', 'tls', '2025-06-16 17:45:04'),
('smtp_username', '', '2025-06-16 17:45:04');

-- --------------------------------------------------------

--
-- Table structure for table `universities`
--

CREATE TABLE `universities` (
  `university_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `location` varchar(100) NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `logo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `universities`
--

INSERT INTO `universities` (`university_id`, `name`, `location`, `latitude`, `longitude`, `logo`) VALUES
(1, 'Makerere University', 'Kampala', 0.33260000, 32.56770000, NULL),
(2, 'Kyambogo University', 'Kampala', 0.34980000, 32.61650000, NULL),
(3, 'MUBS', 'Kampala', 0.31260000, 32.59040000, NULL),
(4, 'Uganda Christian University', 'Mukono', 0.35330000, 32.75220000, NULL),
(5, 'Mbarara University of Science and Technology', 'Mbarara', -0.61360000, 30.65850000, NULL),
(6, 'Kabale University', 'Plot 364 Block 3 Kikungiri Hill, Kabale Municipality Kabale – Kigali Highway, Kabale', -1.27151000, 29.98954000, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `user_type` enum('student','landlord','admin') NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `university` varchar(100) DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `must_reset_password` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password`, `user_type`, `full_name`, `phone`, `university`, `profile_pic`, `verified`, `created_at`, `updated_at`, `must_reset_password`) VALUES
(1, 'Nade', 'admin@example.com', '$2y$10$1pGtNjB3wJp7BlW4YjjqeuMgYEYtyuQa5fbOUx0MrWruKW7zu4eMK', 'admin', 'NADE DERRICK', '0761891599', '', 'user_1_1749984341.jpg', 0, '2025-06-13 16:38:24', '2025-06-15 10:45:41', 0),
(3, 'Dean', 'deanwhinchester@gmail.com', '$2y$10$E5kJiw2HbT/HKk7wXpz2aeeI17Hrm9REBguObENZwaW/7vK0ZBe6C', 'landlord', 'DEAN WHINCHESTER', '0700236424', '', 'user_3_1757319241.png', 1, '2025-06-14 17:48:40', '2025-09-08 08:14:01', 0),
(4, 'Naps', 'napasiokalifani2@gmail.com', '$2y$10$SpxQSal.JImM98aUpvnUuu8IlBsiDIYoiQ/jvkoWMLs2OxmPqH/GG', 'student', 'NAPS  KALIFANI', '0772576741', 'KABALE UNIVERSITY', 'user_4_1757323192.jpg', 0, '2025-09-08 06:39:09', '2025-09-08 09:19:52', 0),
(5, 'Kalifah', 'napskalifah42@gmail.com', '$2y$10$DGj/g.OHWFqu6Cwa5N8eQu37HtqdA5ibdGle9BvSLBErDo4O.ass.', 'landlord', 'NAPS  KALIFANI', '0754649467', '', NULL, 1, '2025-09-27 09:37:11', '2025-09-27 09:42:07', 0),
(6, 'Slade', 'sladephantom60@gmail.com', '$2y$10$m4b0jajkrwz43qaLqqNj4.EWdJdm.9VPRcWTfaa9Tr84Y6FiOoQ6W', 'student', 'SLADE PHANTOM', '0754038069', 'KABALE UNIVERSITY', 'user_6_1758967335.jpg', 0, '2025-09-27 09:59:58', '2025-09-27 10:02:15', 0),
(8, 'DERRICK', '2023akcs830f@kab.ac.ug', '$2y$10$eIgq1j0bppNjQMmnOU6a.Ol0hRUpnA0UFMPWEg8pOWtmSApcttB.q', 'student', 'NAHWERA DERRICK', '0754038069', 'KABALE UNIVERSITY', NULL, 1, '2025-09-27 10:28:47', '2025-09-27 10:28:47', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_messages`
--
ALTER TABLE `admin_messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `hostel_id` (`hostel_id`),
  ADD KEY `landlord_id` (`landlord_id`);

--
-- Indexes for table `hostels`
--
ALTER TABLE `hostels`
  ADD PRIMARY KEY (`hostel_id`),
  ADD KEY `landlord_id` (`landlord_id`),
  ADD KEY `university_id` (`university_id`);

--
-- Indexes for table `hostel_images`
--
ALTER TABLE `hostel_images`
  ADD PRIMARY KEY (`image_id`),
  ADD KEY `hostel_id` (`hostel_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`reset_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `token` (`token`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `hostel_id` (`hostel_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `universities`
--
ALTER TABLE `universities`
  ADD PRIMARY KEY (`university_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_messages`
--
ALTER TABLE `admin_messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `hostels`
--
ALTER TABLE `hostels`
  MODIFY `hostel_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `hostel_images`
--
ALTER TABLE `hostel_images`
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `reset_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `universities`
--
ALTER TABLE `universities`
  MODIFY `university_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_messages`
--
ALTER TABLE `admin_messages`
  ADD CONSTRAINT `admin_messages_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`hostel_id`) REFERENCES `hostels` (`hostel_id`),
  ADD CONSTRAINT `bookings_ibfk_3` FOREIGN KEY (`landlord_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `hostels`
--
ALTER TABLE `hostels`
  ADD CONSTRAINT `hostels_ibfk_1` FOREIGN KEY (`landlord_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `hostels_ibfk_2` FOREIGN KEY (`university_id`) REFERENCES `universities` (`university_id`);

--
-- Constraints for table `hostel_images`
--
ALTER TABLE `hostel_images`
  ADD CONSTRAINT `hostel_images_ibfk_1` FOREIGN KEY (`hostel_id`) REFERENCES `hostels` (`hostel_id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`);

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`),
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`hostel_id`) REFERENCES `hostels` (`hostel_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
