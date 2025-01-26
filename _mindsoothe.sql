-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 21, 2025 at 07:56 PM
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
-- Database: `_mindsoothe`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`, `created_at`) VALUES
(1, 'admin', '$2y$10$XhrKP.0odWvK0LQvJH7lLuS5WB9HuKubnzQadajZ0An7nGHxg.BsW', '2025-01-19 08:16:54');

-- --------------------------------------------------------

--
-- Table structure for table `gracefulthread`
--

CREATE TABLE `gracefulthread` (
  `id` int(10) NOT NULL,
  `user_id` int(10) NOT NULL,
  `content` text NOT NULL,
  `likes` int(10) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gracefulthread`
--

INSERT INTO `gracefulthread` (`id`, `user_id`, `content`, `likes`, `created_at`) VALUES
(1, 9, 'im so happy for today', 0, '2025-01-19 02:09:40');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `sender_type` enum('student','MHP') NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `receiver_type` enum('student','MHP') NOT NULL,
  `message` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('sent','read') DEFAULT 'sent'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mhp`
--

CREATE TABLE `mhp` (
  `id` int(11) NOT NULL,
  `fname` varchar(50) NOT NULL,
  `lname` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `department` varchar(255) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT 'images/blueuser.svg',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mhp`
--

INSERT INTO `mhp` (`id`, `fname`, `lname`, `email`, `department`, `password`, `profile_image`, `created_at`) VALUES
(1, 'Maloi', 'Ricalde', 'maloi.ricalde@usl.edu.ph', 'SABH', '202cb962ac59075b964b07152d234b70', 'images/blueuser.svg', '2025-01-21 16:46:43'),
(2, 'Chloe', 'Realin', '2101644@usl.edu.ph', 'SABH', '202cb962ac59075b964b07152d234b70', 'images/blueuser.svg', '2025-01-21 16:46:43'),
(3, 'AIAI', 'Arceta', 'aiah@usl.edu.ph', 'SACE', '123', 'images/blueuser.svg', '2025-01-21 16:46:43'),
(4, 'Aiah', 'Arceta', 'aiah.arceta@usl.edu.ph', 'SACE', '202cb962ac59075b964b07152d234b70', 'images/blueuser.svg', '2025-01-21 16:46:43'),
(5, 'Bonifer', 'Decena', 'bon@gmail.com', 'SACE', NULL, 'images/blueuser.svg', '2025-01-21 17:09:34'),
(6, 'Daisy', 'Decena', 'dei@gmail.com', 'SHAS', NULL, 'images/blueuser.svg', '2025-01-21 17:11:55'),
(7, 'Jungkook', 'Jeon', '2101644@usl.edu.ph', 'SACE', NULL, 'images/blueuser.svg', '2025-01-21 17:18:31'),
(8, 'try', 'try', 'try@gmail.com', 'SACE', NULL, 'images/blueuser.svg', '2025-01-21 17:21:58'),
(9, 'best', 'best', 'best@gmail.com', 'SACE', NULL, 'images/blueuser.svg', '2025-01-21 17:37:52'),
(10, 'try2', 'try2', 'qqssqx@gmail.com', 'SACE', NULL, 'images/blueuser.svg', '2025-01-21 17:43:48'),
(11, 'Janine', 'Pablo', 'XACFD@GMAIL.COM', 'SACE', NULL, 'images/blueuser.svg', '2025-01-21 17:46:03'),
(12, 'Carlos', 'Joaquin', 'klo@gmail.com', 'SACE', NULL, 'images/blueuser.svg', '2025-01-21 17:56:13'),
(13, 'Janine', 'Pablo', '2101644@usl.edu.ph', 'SACE', '$2y$10$HuDOTvXudw/Zmo9WWKE22ubeBo5CnSB6Xgz1U9YM71pdJOMc2yy7i', 'images/blueuser.svg', '2025-01-21 17:58:13'),
(14, 'Jeon', 'Jungkook', 'jungkook@usl.edu.ph', 'SHAS', '$2y$10$kRY7ujZqn.pfnoqkKPZGVu.fBaROTAxnmAM8i8PIS.qVFyUGuUWaS', 'images/blueuser.svg', '2025-01-21 18:01:30'),
(15, 'Carlos', 'REALIN', '2101644@usl.edu.ph', 'SHAS', '202cb962ac59075b964b07152d234b70', 'images/blueuser.svg', '2025-01-21 18:07:09'),
(16, 'Janine', 'Felipe', 'janine@usl.edu.ph', 'SHAS', '202cb962ac59075b964b07152d234b70', 'images/blueuser.svg', '2025-01-21 18:08:21'),
(17, 'SHYLYN', 'REALIN', 'shy@usl.edu.ph', 'SHAS', '202cb962ac59075b964b07152d234b70', 'images/blueuser.svg', '2025-01-21 18:33:39'),
(18, 'Carlos', 'Pablo', 'klo@usl.edu.ph', 'SABH', '202cb962ac59075b964b07152d234b70', 'images/blueuser.svg', '2025-01-21 18:35:24'),
(19, 'Carlos', 'Pablo', 'pabloja@usl.edu.ph', 'SACE', '202cb962ac59075b964b07152d234b70', 'images/blueuser.svg', '2025-01-21 18:37:31');

-- --------------------------------------------------------

--
-- Table structure for table `phq9_responses`
--

CREATE TABLE `phq9_responses` (
  `id` int(10) NOT NULL,
  `user_id` int(10) NOT NULL,
  `question_1` varchar(50) NOT NULL,
  `question_2` varchar(50) NOT NULL,
  `question_3` varchar(50) NOT NULL,
  `question_4` varchar(50) NOT NULL,
  `question_5` varchar(50) NOT NULL,
  `question_6` varchar(50) NOT NULL,
  `question_7` varchar(50) NOT NULL,
  `question_8` varchar(50) NOT NULL,
  `question_9` varchar(50) NOT NULL,
  `response_score` int(10) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `response_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `phq9_responses`
--

INSERT INTO `phq9_responses` (`id`, `user_id`, `question_1`, `question_2`, `question_3`, `question_4`, `question_5`, `question_6`, `question_7`, `question_8`, `question_9`, `response_score`, `created_at`, `response_date`) VALUES
(1, 8, 'More than half the days', 'Nearly every day', 'More than half the days', 'Nearly every day', 'More than half the days', 'Several days', 'Nearly every day', 'More than half the days', 'Several days', 19, '2025-01-18 13:44:21', '2025-01-18 21:44:21'),
(2, 9, 'Nearly every day', 'More than half the days', 'Nearly every day', 'Nearly every day', 'More than half the days', 'Several days', 'More than half the days', 'Nearly every day', 'Nearly every day', 22, '2025-01-19 02:07:10', '2025-01-19 10:07:10'),
(3, 11, 'Several days', 'Not at all', 'Several days', 'Not at all', 'Nearly every day', 'Nearly every day', 'Nearly every day', 'More than half the days', 'Several days', 14, '2025-01-20 03:16:00', '2025-01-20 11:16:00');

-- --------------------------------------------------------

--
-- Table structure for table `post_likes`
--

CREATE TABLE `post_likes` (
  `id` int(10) NOT NULL,
  `user_id` int(10) NOT NULL,
  `post_id` int(10) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `post_likes`
--

INSERT INTO `post_likes` (`id`, `user_id`, `post_id`, `created_at`) VALUES
(1, 9, 1, '2025-01-19 02:09:47'),
(3, 1, 1, '2025-01-19 02:10:25'),
(4, 8, 1, '2025-01-20 03:12:12');

-- --------------------------------------------------------

--
-- Table structure for table `time_slots`
--

CREATE TABLE `time_slots` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ;

--
-- Dumping data for table `time_slots`
--

INSERT INTO `time_slots` (`id`, `user_id`, `day_of_week`, `start_time`, `end_time`, `created_at`) VALUES
(5, 1, 'Tuesday', '07:30:00', '08:30:00', '2025-01-19 02:14:58'),
(7, 1, 'Wednesday', '09:04:00', '10:04:00', '2025-01-19 08:04:47'),
(9, 1, 'Monday', '11:42:00', '12:42:00', '2025-01-21 03:42:56'),
(10, 1, 'Tuesday', '11:35:00', '12:33:00', '2025-01-21 15:33:49');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) NOT NULL,
  `Student_id` int(7) NOT NULL,
  `firstName` varchar(50) NOT NULL,
  `lastName` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `password` varchar(50) NOT NULL,
  `profile_image` varchar(255) DEFAULT 'images/blueuser.svg',
  `status` tinyint(1) NOT NULL DEFAULT 0,
  `otp` varchar(6) DEFAULT NULL,
  `Course` varchar(250) NOT NULL,
  `Year` int(10) NOT NULL,
  `Department` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `Student_id`, `firstName`, `lastName`, `email`, `password`, `profile_image`, `status`, `otp`, `Course`, `Year`, `Department`) VALUES
(1, 2102446, 'Janine', 'Pablo', '2102446@usl.edu.ph', '202cb962ac59075b964b07152d234b70', 'images/blueuser.svg', 0, NULL, '', 0, ''),
(2, 2102445, 'Lim', 'mikha', 'mikha@usl.edu.ph', '202cb962ac59075b964b07152d234b70', 'images/blueuser.svg', 0, NULL, '', 0, ''),
(3, 1234567, 'Sevilleja', 'stacey', 'stacey@usl.edu.ph', '202cb962ac59075b964b07152d234b70', 'images/blueuser.svg', 0, NULL, '', 0, ''),
(4, 1234566, 'Robles', 'jhoana', 'jhoanna@usl.edu.ph', '202cb962ac59075b964b07152d234b70', 'images/blueuser.svg', 0, NULL, '', 0, ''),
(5, 2102445, 'Lim', 'mikha', 'mikha@usl.edu.ph', '202cb962ac59075b964b07152d234b70', 'images/blueuser.svg', 0, NULL, '', 0, ''),
(6, 1234567, 'Sevilleja', 'stacey', 'stacey@usl.edu.ph', '202cb962ac59075b964b07152d234b70', 'images/blueuser.svg', 0, NULL, '', 0, ''),
(7, 1234566, 'Robles', 'jhoana', 'jhoanna@usl.edu.ph', '202cb962ac59075b964b07152d234b70', 'images/blueuser.svg', 0, NULL, '', 0, ''),
(8, 2102446, 'Chloe', 'Realin', '2101644@usl.edu.ph', '250cf8b51c773f3f8dc8b4be867a9a02', 'images/blueuser.svg', 0, NULL, '', 0, ''),
(9, 2102446, 'Chloe', 'Realin', '1234567@usl.edu.ph', '202cb962ac59075b964b07152d234b70', 'images/blueuser.svg', 0, NULL, '', 0, ''),
(10, 2102446, 'Chloe', 'Realin', '1234567@usl.edu.ph', '202cb962ac59075b964b07152d234b70', 'images/blueuser.svg', 0, NULL, '', 0, ''),
(11, 2102446, 'Janine', 'Pablo', 'pablo@usl.edu.ph', '202cb962ac59075b964b07152d234b70', 'images/blueuser.svg', 0, NULL, '', 0, '');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `gracefulthread`
--
ALTER TABLE `gracefulthread`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mhp`
--
ALTER TABLE `mhp`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `phq9_responses`
--
ALTER TABLE `phq9_responses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `post_likes`
--
ALTER TABLE `post_likes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `time_slots`
--
ALTER TABLE `time_slots`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `gracefulthread`
--
ALTER TABLE `gracefulthread`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mhp`
--
ALTER TABLE `mhp`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `phq9_responses`
--
ALTER TABLE `phq9_responses`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `post_likes`
--
ALTER TABLE `post_likes`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `time_slots`
--
ALTER TABLE `time_slots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `gracefulthread`
--
ALTER TABLE `gracefulthread`
  ADD CONSTRAINT `gracefulthread_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `phq9_responses`
--
ALTER TABLE `phq9_responses`
  ADD CONSTRAINT `phq9_responses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `time_slots`
--
ALTER TABLE `time_slots`
  ADD CONSTRAINT `time_slots_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
