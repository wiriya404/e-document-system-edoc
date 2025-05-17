-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Feb 17, 2025 at 11:10 AM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `edoc`
--

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `dep_id` int NOT NULL,
  `dep_name` varchar(254) NOT NULL,
  `dep_img` varchar(254) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`dep_id`, `dep_name`, `dep_img`) VALUES
(1, 'Information', 'dep_67a97eb5c1cda6.73292190.jpg'),
(2, 'electric', 'dep_67a97ebd3177d8.03276245.gif'),
(3, 'engineer', 'dep_67846c1e02c4c6.51931178.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `doc_id` int NOT NULL,
  `doc_name` varchar(254) NOT NULL,
  `doc_date` date NOT NULL,
  `doc_dep` int DEFAULT NULL,
  `doc_user` int DEFAULT NULL,
  `doc_file` varchar(254) NOT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `who_sent` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `documents`
--

INSERT INTO `documents` (`doc_id`, `doc_name`, `doc_date`, `doc_dep`, `doc_user`, `doc_file`, `original_name`, `who_sent`) VALUES
(108, 'send to user1', '2025-02-13', NULL, NULL, '67ad999945b432.25733612.mp4', 'มึงคิดว่ามึงเฟี้ยวหรอวะ.mp4', 3),
(109, 'send to all departments', '2025-02-13', NULL, NULL, '67ad9c50e533e4.73315738.pdf', '13_แบบเสนอ.pdf', 3);

-- --------------------------------------------------------

--
-- Table structure for table `document_recipients`
--

CREATE TABLE `document_recipients` (
  `id` int NOT NULL,
  `doc_id` int NOT NULL,
  `dep_id` int DEFAULT NULL,
  `us_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `document_recipients`
--

INSERT INTO `document_recipients` (`id`, `doc_id`, `dep_id`, `us_id`) VALUES
(53, 108, NULL, 2),
(54, 109, 1, NULL),
(55, 109, 2, NULL),
(56, 109, 3, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `readchk`
--

CREATE TABLE `readchk` (
  `readchk_id` int NOT NULL,
  `doc_id` int NOT NULL,
  `read_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `us_dep` int NOT NULL,
  `us_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `readchk`
--

INSERT INTO `readchk` (`readchk_id`, `doc_id`, `read_date`, `us_dep`, `us_id`) VALUES
(68, 108, '2025-02-13 14:06:12', 1, 2),
(69, 109, '2025-02-13 14:18:18', 1, 2),
(70, 109, '2025-02-13 14:19:14', 1, 4);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `us_id` int NOT NULL,
  `us_name` varchar(255) NOT NULL,
  `us_pwd` varchar(255) NOT NULL,
  `us_dep` varchar(100) DEFAULT NULL,
  `us_role` varchar(50) DEFAULT NULL,
  `us_stat` enum('active','inactive') DEFAULT 'active',
  `imgprofile` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`us_id`, `us_name`, `us_pwd`, `us_dep`, `us_role`, `us_stat`, `imgprofile`) VALUES
(2, 'user1', '$2y$10$exuNV2ltfY5yW9nKa48xCOhBKRm6SR.GmKHcNOi6dOlw/1D5MQ/7.', '1', 'user', 'active', 'img_67a88347dd1bf2.11622697.webp'),
(3, 'admin', '$2y$10$0Cm8VP7wxkDvW4T5hWJKn.mVoEjWNY6GIT3.dv4A4159wnGj8/84e', '2', 'admin', 'active', 'img_6783a126a44146.30414159.png'),
(4, 'user2', '$2y$10$M5b3/f8oSy/fGBgHzqAyyuAT0vJnUZgFzO4LxHXoMMubhbcArfwom', '1', 'user', 'active', 'img_6783a12f85b959.85926803.png'),
(6, 'user3', '$2y$10$rUkDKKHblHFfqe6DJgyDOuSFLUFU2GYUDz7g/FYG578NxpjdwOfLm', '3', 'user', 'active', 'img_678396a6806558.35317152.png'),
(7, 'wiriya', '$2y$10$yVnw4QddRiTodLQ3BEnbZui6Dpuj6gX8JMTMtvCZRIXFWsow/LY/2', '1', 'user', 'active', 'img_67876eed6faf94.94416484.png'),
(8, 'แป้ง', '$2y$10$O9K9kpkiDdpV4kZVPzYbOOGvHDCMwWB0JnQtei9xQZvqynYOLrsre', '2', 'user', 'inactive', 'img_67a9853a0e54f2.93850473.jpg');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`dep_id`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`doc_id`);

--
-- Indexes for table `document_recipients`
--
ALTER TABLE `document_recipients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `doc_id` (`doc_id`),
  ADD KEY `dep_id` (`dep_id`),
  ADD KEY `user_id` (`us_id`);

--
-- Indexes for table `readchk`
--
ALTER TABLE `readchk`
  ADD PRIMARY KEY (`readchk_id`),
  ADD KEY `doc_id` (`doc_id`),
  ADD KEY `us_dep` (`us_dep`),
  ADD KEY `us_id` (`us_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`us_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `dep_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `doc_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=110;

--
-- AUTO_INCREMENT for table `document_recipients`
--
ALTER TABLE `document_recipients`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `readchk`
--
ALTER TABLE `readchk`
  MODIFY `readchk_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `us_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `document_recipients`
--
ALTER TABLE `document_recipients`
  ADD CONSTRAINT `document_recipients_ibfk_1` FOREIGN KEY (`doc_id`) REFERENCES `documents` (`doc_id`),
  ADD CONSTRAINT `document_recipients_ibfk_2` FOREIGN KEY (`dep_id`) REFERENCES `departments` (`dep_id`),
  ADD CONSTRAINT `document_recipients_ibfk_3` FOREIGN KEY (`us_id`) REFERENCES `users` (`us_id`);

--
-- Constraints for table `readchk`
--
ALTER TABLE `readchk`
  ADD CONSTRAINT `readchk_ibfk_1` FOREIGN KEY (`doc_id`) REFERENCES `documents` (`doc_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `readchk_ibfk_2` FOREIGN KEY (`us_dep`) REFERENCES `departments` (`dep_id`),
  ADD CONSTRAINT `readchk_ibfk_3` FOREIGN KEY (`us_id`) REFERENCES `users` (`us_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
