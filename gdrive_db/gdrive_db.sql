-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 08, 2025 at 07:38 AM
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
-- Database: `gdrive_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `files`
--

CREATE TABLE `files` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `filepath` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `folder` varchar(255) DEFAULT NULL,
  `deleted` tinyint(1) DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `original_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `files`
--

INSERT INTO `files` (`id`, `user_id`, `filename`, `filepath`, `uploaded_at`, `folder`, `deleted`, `deleted_at`, `original_path`) VALUES
(2, 2, 'page_6_test.png', 'files/2/page_6_test.png', '2025-07-03 08:32:20', '', 0, NULL, NULL),
(3, 2, 'kaggle email.txt', 'files/2/Rifat/kaggle email.txt', '2025-07-03 08:32:59', 'Rifat', 0, NULL, NULL),
(4, 2, 'page_6_test.png', 'files/2/Rifat/page_6_test.png', '2025-07-03 08:45:35', 'Rifat', 0, NULL, NULL),
(5, 2, 'page_71_test.png', 'files/2/page_71_test.png', '2025-07-03 08:50:20', '', 0, NULL, NULL),
(6, 2, 'page_8.jpg', 'files/2/Masum/page_8.jpg', '2025-07-03 08:50:37', 'Masum', 0, NULL, NULL),
(7, 2, 'page_8_test.png', 'files/2/Masum/page_8_test.png', '2025-07-03 08:51:24', 'Masum', 0, NULL, NULL),
(8, 2, 'page_69_test.png', 'files/2/Masum/page_69_test.png', '2025-07-03 08:51:31', 'Masum', 0, NULL, NULL),
(9, 2, 'page_76_test.png', 'files/2/Masum/page_76_test.png', '2025-07-03 08:51:38', 'Masum', 0, NULL, NULL),
(10, 2, 'page_83_renamed.jpg', 'files/2/Masum/page_83_renamed.jpg', '2025-07-03 08:51:45', 'Masum', 0, NULL, NULL),
(12, 3, 'page_8.jpg', 'files/3/page_8.jpg', '2025-07-04 07:43:42', '', 0, NULL, NULL),
(13, 3, 'page_70_test.png', 'files/3/Arefin/page_70_test.png', '2025-07-04 07:44:20', 'Arefin', 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `profile_pic` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `created_at`, `profile_pic`) VALUES
(1, 'Masum Mushfik Rifat', 'masum.mm.rifat@gmail.com', '$2y$10$11KduyQAE6bZQ3tQPk5TGerin3OH40vqYoQol7UWo8EG4o0xVdUIW', '2025-07-03 06:53:26', NULL),
(2, 'Rifat', 'masum@gmail.com', '$2y$10$MgnXgAwk/MWi1DmLsvAtXOgRImmIT3pH9QflYSm8WaXcN6nBjvWy2', '2025-07-03 06:59:13', NULL),
(3, 'Arefin', 'arefin@gmail.com', '$2y$10$ss2FU1G.oOyKhzk/VkUGzOAXKu4Y4MyvTtQgDkENtWisFEqNtfu8e', '2025-07-04 07:42:21', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `files`
--
ALTER TABLE `files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

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
-- AUTO_INCREMENT for table `files`
--
ALTER TABLE `files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `files`
--
ALTER TABLE `files`
  ADD CONSTRAINT `files_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;