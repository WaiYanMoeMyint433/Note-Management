-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 16, 2025 at 11:35 AM
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
-- Database: `nms2`
--

-- --------------------------------------------------------

--
-- Table structure for table `labels`
--

CREATE TABLE `labels` (
  `id` int(11) NOT NULL,
  `label_text` varchar(100) NOT NULL,
  `icon` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `labels`
--

INSERT INTO `labels` (`id`, `label_text`, `icon`) VALUES
(169, 'test', NULL),
(170, 'label test', NULL),
(171, 'so good', NULL),
(172, 'stm test', NULL),
(173, 'success', NULL),
(174, 'image', NULL),
(175, 'fix laptop hp', NULL),
(176, 'edit required', NULL),
(177, 'shared', NULL),
(178, 'image test', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `notes`
--

CREATE TABLE `notes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `creation_time` datetime DEFAULT current_timestamp(),
  `pinned` tinyint(1) DEFAULT 0,
  `password` varchar(255) DEFAULT NULL,
  `shared` tinyint(1) DEFAULT 0,
  `pinned_time` datetime DEFAULT NULL,
  `update_time` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notes`
--

INSERT INTO `notes` (`id`, `user_id`, `title`, `content`, `creation_time`, `pinned`, `password`, `shared`, `pinned_time`, `update_time`) VALUES
(228, 25, 'Hello', '<p>I am Han Gyi. edit test.</p>', '2025-05-14 12:03:28', 0, NULL, 0, NULL, '2025-05-14 12:03:47'),
(229, 26, 'Japan Diary', '<p>my japan trip.</p><p><img src=\"../uploads/68242526302a3.png\"></p><p>it is pretty good.</p>', '2025-05-14 12:08:07', 0, NULL, 0, NULL, '2025-05-14 12:08:07'),
(230, 27, 'soe moe thu', '<p>stm test. succes edit</p>', '2025-05-14 12:25:22', 0, '12345', 0, NULL, '2025-05-14 12:28:06'),
(231, 27, 'second post', '<p>second post,edit success</p><p><img src=\"../uploads/682429779946c.jpg\"></p>', '2025-05-14 12:26:27', 0, NULL, 0, NULL, '2025-05-14 12:27:48'),
(232, 28, 'mdavis', '<p>first test</p><p><img src=\"../uploads/6823d212e50c0.jpg\"></p>', '2025-05-14 06:13:25', 0, '12345', 1, NULL, '2025-05-14 09:20:17'),
(233, 30, 'hi san', '<p>san san </p><p>check here</p><p><img src=\"../uploads/6823feda902b8.jpg\"></p>', '2025-05-14 09:24:40', 0, NULL, 1, NULL, '2025-05-14 09:25:05'),
(234, 31, 'Hello lan', '<p>give me edit access</p><p><br></p><p>lan lan,</p><p>i successfully accept your request</p>', '2025-05-14 09:30:35', 0, NULL, 1, NULL, '2025-05-14 09:57:09'),
(235, 33, 'share testing', '<p>i am testing sharing. now testing saving mode.</p><p><img src=\"../uploads/682447166311f.jpg\"></p>', '2025-05-14 14:32:55', 0, NULL, 1, NULL, '2025-05-14 14:36:30'),
(236, 37, 'testing', '<p>Hello, test done</p><p><img src=\"../uploads/6825c433c9a8a.png\"></p><p>test image again</p><p><img src=\"../uploads/6825c4432a5a2.png\"></p>', '2025-05-15 17:39:09', 1, NULL, 0, '2025-05-16 10:59:29', '2025-05-16 10:59:29');

-- --------------------------------------------------------

--
-- Table structure for table `note_labels`
--

CREATE TABLE `note_labels` (
  `note_id` int(11) NOT NULL,
  `label_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `note_labels`
--

INSERT INTO `note_labels` (`note_id`, `label_id`, `user_id`) VALUES
(228, 169, 25),
(228, 170, 25),
(229, 171, 26),
(230, 172, 27),
(230, 173, 27),
(231, 174, 27),
(233, 175, 30),
(234, 176, 31),
(235, 177, 33),
(236, 178, 37);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset`
--

CREATE TABLE `password_reset` (
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expire_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shared_notes`
--

CREATE TABLE `shared_notes` (
  `note_id` int(11) NOT NULL,
  `shared_email` varchar(255) NOT NULL,
  `permission` enum('read','write') DEFAULT 'read',
  `shared_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shared_notes`
--

INSERT INTO `shared_notes` (`note_id`, `shared_email`, `permission`, `shared_at`) VALUES
(233, 'san@gmail.com', 'read', '2025-05-14 09:25:05'),
(234, 'lanlan@gmail.com', 'write', '2025-05-14 09:31:16'),
(235, 'waiyan@gmail.com', 'write', '2025-05-14 14:33:23');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `activation` int(11) DEFAULT 0,
  `token` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `name`, `password`, `activation`, `token`) VALUES
(23, 'zan@gmail.com', 'zan', '$2y$10$7xvITGUrLCBGgWcLpFTeUuYW/mxesk7PvneL6zapUMmUTHDfwp/Fu', 0, '05a606aa2513abbea4a4dd78b223d22d'),
(24, 'zangi@gmail.com', 'zan gi', '$2y$10$b88coLXmzoIpAixxLSPkkeOUGf1B4cH9l0HQ0q458ew9/QxSD5qdC', 1, NULL),
(25, 'hangyi@gmail.com', 'han gyi', '$2y$10$YkOs6geZ7sdTck1AUKfoTefSnQ1bj67gTO3OZCMysX1A10/5ge/gy', 0, '780b6d5e0f631f3df2fd1cfc1270db58'),
(26, 'hnin@gmail.com', 'hnin', '$2y$10$LP5VXh7Z45gqDy7ztZ0BUOfYNshfZKOFxv3FmKcl16Q94C17VPkYC', 0, '001360a89604a9ce0d07b9f700c9d61f'),
(27, 'stm@gmail.com', 'stm', '$2y$10$.xqBCYngN./7oagMaeJy8ejdyRaYTMgXX2/dLVBVG4YyKROO0BmoG', 0, 'ef6261de0a8f616edc81a5ac349890b5'),
(28, 'mdavis@gmail.com', 'mdavis', '$2y$10$MxY19/NZFF/bko5nJe93E.a5kON7XSji1HVCRovJaIHsji76kjeuO', 0, 'f47f1666e7220b5fcac162b6c4d702ca'),
(29, 'akh@gmail.com', 'akh', '$2y$10$fdZGkyn1e3e8g3Wy5vA3Oe4EXvcZGrFwqOstYJ9rl1DmxJGkZ9p4y', 0, '8dcb9cd7da3056a504d5eba37f5670e5'),
(30, 'lan@gmail.com', 'lan', '$2y$10$X2ytp3.zBl.IcZq4v5TSoOAZJkSL8P5Lloadf4MmGg4AqlKwN5cdO', 0, '8f885a92a485ca9427505dd9e27c4125'),
(31, 'san@gmail.com', 'san', '$2y$10$hVfROy.Nx9VVk//rw1CtQukCu/Rtve/gzdmeSXer3oMURY.SMF8dC', 0, '76a927d3cbbe24f0fd083b81b25eb594'),
(32, 'lanlan@gmail.com', 'lanlan', '$2y$10$WTHdLFYEeEZ2aFW0TAWJRerlQRSlF9NUfjaZy3tORDyHkhnbYwqga', 0, '656199a5d6f418acc4d2da1486c50f78'),
(33, 'haha@gmail.com', 'haha', '$2y$10$1flvxvxINX5l4Isjq7QiwOVmzxHWid7Oll7SoGP.RnYOdJNkk381K', 0, '678e7c8c6798ecfd44d6f5002a729ac9'),
(34, 'waiyan@gmail.com', 'waiyan', '$2y$10$MqGm3L0QGzYiLpm1Lk9Jt.Y/msP8UIodLFly/y4gquQMN2kFvIlfK', 0, '24c1d3a2169dc2024f79a76a2061a5bd'),
(35, 'zanag@gmail.com', 'zan ag', '$2y$10$Snlj1Jt2Qhgp2BGlmxUzhO/DtLXQA/njT3cUOgv5hJ53vPFimU3sy', 0, 'ae094e88499d78ebf0fa26291ca39b62'),
(36, 'mg@gmail.com', 'Mg', '$2y$10$U8hEiKSbpMK4kGxKJSSrlO99SKTMFDGCAjNgH8s3Vzif0fUQHhzJO', 0, 'df54bd9def97fd3cdc325edd41926cc5'),
(37, 'akh2100@gmail.com', 'aung kaung htet', '$2y$10$LHQ36WraW/NR7w980g3o2.mU3ScMv4EPsOVHtmVXvEfVoLsGIsdXG', 1, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `labels`
--
ALTER TABLE `labels`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notes`
--
ALTER TABLE `notes`
  ADD PRIMARY KEY (`id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `note_labels`
--
ALTER TABLE `note_labels`
  ADD PRIMARY KEY (`note_id`,`label_id`),
  ADD KEY `label_id` (`label_id`),
  ADD KEY `note_labels_ibfk_3` (`user_id`);

--
-- Indexes for table `password_reset`
--
ALTER TABLE `password_reset`
  ADD PRIMARY KEY (`user_id`,`token`);

--
-- Indexes for table `shared_notes`
--
ALTER TABLE `shared_notes`
  ADD PRIMARY KEY (`note_id`,`shared_email`),
  ADD KEY `idx_shared_email` (`shared_email`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `labels`
--
ALTER TABLE `labels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=179;

--
-- AUTO_INCREMENT for table `notes`
--
ALTER TABLE `notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=237;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `notes`
--
ALTER TABLE `notes`
  ADD CONSTRAINT `notes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `note_labels`
--
ALTER TABLE `note_labels`
  ADD CONSTRAINT `note_labels_ibfk_1` FOREIGN KEY (`note_id`) REFERENCES `notes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `note_labels_ibfk_2` FOREIGN KEY (`label_id`) REFERENCES `labels` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `note_labels_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `password_reset`
--
ALTER TABLE `password_reset`
  ADD CONSTRAINT `password_reset_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shared_notes`
--
ALTER TABLE `shared_notes`
  ADD CONSTRAINT `shared_notes_ibfk_1` FOREIGN KEY (`note_id`) REFERENCES `notes` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
