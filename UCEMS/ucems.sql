-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 10, 2025 at 04:36 PM
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
-- Database: `ucems`
--

-- --------------------------------------------------------

--
-- Table structure for table `business_info`
--

CREATE TABLE `business_info` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `business_name` varchar(255) NOT NULL,
  `business_description` text NOT NULL,
  `course_name` varchar(255) NOT NULL,
  `subject_code_name` varchar(255) NOT NULL,
  `study_level` enum('Diploma','Degree') NOT NULL,
  `class_section` varchar(50) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `facebook_link` varchar(255) DEFAULT NULL,
  `instagram_link` varchar(255) DEFAULT NULL,
  `twitter_link` varchar(255) DEFAULT NULL,
  `business_category` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `business_info`
--

INSERT INTO `business_info` (`id`, `username`, `business_name`, `business_description`, `course_name`, `subject_code_name`, `study_level`, `class_section`, `updated_at`, `facebook_link`, `instagram_link`, `twitter_link`, `business_category`) VALUES
(2, 'testuser', 'THRIFTLABMY', 'Selling thrift clothes', 'Bachelor of Information Technology (Honours) in Cyber Security (CT206)', 'UCS3103 - Digital Entrepreneurship', 'Degree', '02', '2025-04-10 01:48:23', '', '', '', 'Apparel, Health & Beauty'),
(3, 'AM2307013985', 'THRIFTLABMY', 'Selling thrift clothes', 'Bachelor of Information Technology (Honours) in Cyber Security (CT206)', 'UCS3103 - Digital Entrepreneurship', 'Degree', '01', '2025-04-10 02:40:38', '', '', '', 'Apparel, Health & Beauty'),
(4, 'AM230701389', 'THRIFTLABMY', 'Selling thrift clothes', 'Bachelor of Information Technology (Honours) in Cyber Security (CT206)', 'UCS3103 - Digital Entrepreneurship', 'Degree', '01', '2025-04-08 08:21:47', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `lecturer_username` varchar(255) NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`id`, `username`, `lecturer_username`, `comment`, `created_at`) VALUES
(1, 'testuser', 'Hannah', 'Good sales', '2025-04-05 14:21:22'),
(2, 'testuser', 'Hannah', 'Update your current sales', '2025-04-05 18:36:15'),
(3, 'testuser', 'Hannah', 'Submit your SSM', '2025-04-06 06:00:37'),
(4, 'AM2307013985', 'Syasya', 'Good sales', '2025-04-08 03:49:57'),
(5, 'AM2307013985', 'Syasya', 'Good Sales', '2025-04-10 02:50:30');

-- --------------------------------------------------------

--
-- Table structure for table `files`
--

CREATE TABLE `files` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `upload_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `files`
--

INSERT INTO `files` (`id`, `username`, `file_name`, `file_path`, `upload_time`) VALUES
(3, 'testuser', 'FYP ACKNOWLEDMENT NOTE - MUHD SHUKRI.pdf', '../user/uploads/FYP ACKNOWLEDMENT NOTE - MUHD SHUKRI.pdf', '2025-04-06 05:58:31'),
(5, 'AM230701389', 'FYP ACKNOWLEDMENT NOTE - MUHD SHUKRI.pdf', '../user/uploads/FYP ACKNOWLEDMENT NOTE - MUHD SHUKRI.pdf', '2025-04-08 08:27:50');

-- --------------------------------------------------------

--
-- Table structure for table `lecturers`
--

CREATE TABLE `lecturers` (
  `username` varchar(80) NOT NULL,
  `password` varchar(80) NOT NULL,
  `name` varchar(80) NOT NULL,
  `email` varchar(80) NOT NULL,
  `phone` bigint(10) NOT NULL,
  `userimage` varchar(800) NOT NULL,
  `image1` varchar(800) NOT NULL,
  `slice1` int(1) NOT NULL,
  `image2` varchar(800) NOT NULL,
  `slice2` int(1) NOT NULL,
  `image3` varchar(800) NOT NULL,
  `slice3` int(1) NOT NULL,
  `token` varchar(255) DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL,
  `token_status` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lecturers`
--

INSERT INTO `lecturers` (`username`, `password`, `name`, `email`, `phone`, `userimage`, `image1`, `slice1`, `image2`, `slice2`, `image3`, `slice3`, `token`, `token_expiry`, `token_status`) VALUES
('Hannah', '$2y$10$HpCDvF8zguAH4790dJTI/ORitKe45nG7xZqiMEtFu4CWWHQC09Ljq', 'Hannah Juon', 'hannah270@uptm.edu.my', 139806543, '../lecturers/images/lecturers/Hannah.png', 'http://localhost/UCEMS/images/pw/image1.jpg', 0, 'http://localhost/UCEMS/images/pw/image2.jpg', 0, 'http://localhost/UCEMS/images/pw/image3.jpg', 0, NULL, NULL, 0),
('Jennie', '$2y$10$HjcaxnSXUoJI6GZ.KdriYOZX7J36n6XmgWsBHxfZiK2cAclqxf7/m', 'Kim Jennie', 'jennie19@uptm.edu.my', 175402391, '../user/images/user/default.png', 'http://localhost/UCEMS/images/pw/image1.jpg', 3, 'http://localhost/UCEMS/images/pw/image2.jpg', 3, 'http://localhost/UCEMS/images/pw/image3.jpg', 3, NULL, NULL, 0),
('Syasya', '$2y$10$mJ2PGAkfKfGv05w1oEZOkOjLgYWfwTMb6MntcDyTh0iXBJOAvvAWW', 'Nurul Syasya', 'syasya23@uptm.edu.my', 178969023, '../lecturers/images/lecturers/Syasya.jpeg', 'http://localhost/UCEMS/images/pw/image1.jpg', 0, 'http://localhost/UCEMS/images/pw/image2.jpg', 0, 'http://localhost/UCEMS/images/pw/image3.jpg', 0, NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `lecturer_courses`
--

CREATE TABLE `lecturer_courses` (
  `id` int(11) NOT NULL,
  `lecturer_username` varchar(255) NOT NULL,
  `course_name` varchar(255) NOT NULL,
  `subject_code_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lecturer_courses`
--

INSERT INTO `lecturer_courses` (`id`, `lecturer_username`, `course_name`, `subject_code_name`) VALUES
(2, 'Hannah', 'Bachelor of Information Technology (Honours) in Business Computing (CT203)', 'UCS3103 - Digital Entrepreneurship'),
(1, 'Hannah', 'Bachelor of Information Technology (Honours) in Cyber Security (CT206)', 'UCS3103 - Digital Entrepreneurship'),
(3, 'Syasya', 'Bachelor of Information Technology (Honours) in Cyber Security (CT206)', 'UCS3103 - Digital Entrepreneurship'),
(4, 'Syasya1', 'Bachelor of Information Technology (Honours) in Cyber Security (CT206)', 'UCS3103 - Digital Entrepreneurship');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `status` varchar(50) NOT NULL,
  `image_type` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `username`, `image`, `name`, `quantity`, `price`, `status`, `image_type`) VALUES
(8, 'testuser', '../user/images/products/3.png', 'Creation Hoodie', 4, 35.00, 'Available', NULL),
(9, 'testuser', '../user/images/products/1.png', 'Ahead Hoodie ', 8, 25.00, 'Available', NULL),
(10, 'AM2307013985', '../user/images/products/1.png', 'Ahead Hoodie ', 2, 25.00, 'Available', NULL),
(11, 'AM230701389', '../user/images/products/1.png', 'A1 - AHEAD HOODIE', 2, 45.00, 'Available', NULL),
(12, 'AM2307013985', '../user/images/products/5.png', 'Black Hoodie', 4, 55.00, 'Available', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `type` enum('Sale','Expense') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `date` date DEFAULT curdate(),
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `payment_method` varchar(50) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity_sold` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `username`, `type`, `amount`, `date`, `description`, `created_at`, `payment_method`, `product_id`, `quantity_sold`) VALUES
(8, 'testuser', 'Sale', 45.00, '2025-04-04', 'Creation Hoodie ', '2025-04-04 06:05:32', NULL, NULL, NULL),
(14, 'testuser', 'Sale', 35.00, '2025-04-06', 'Ahead Hoodie', '2025-04-05 18:33:06', NULL, NULL, NULL),
(17, 'testuser', 'Expense', 25.00, '2025-04-06', 'Paper Bag 10pcs', '2025-04-06 05:18:27', NULL, NULL, NULL),
(22, 'testuser', 'Sale', 35.00, '2025-04-08', 'Instagram', '2025-04-08 14:47:39', 'E-Wallet', NULL, NULL),
(24, 'testuser', 'Sale', 25.00, '2025-04-09', 'Ahmad buy 1', '2025-04-08 16:17:37', 'Online Banking', 9, 1),
(26, 'testuser', 'Sale', 25.00, '2025-04-10', 'Danial buy 1', '2025-04-10 01:54:58', 'QR Payment', 9, 1),
(27, 'testuser', 'Sale', 25.00, '2025-04-10', 'Hannah buy 1', '2025-04-10 02:00:04', 'Cash', 9, 1),
(28, 'AM2307013985', 'Sale', 55.00, '2025-04-10', 'Ahmad buy 1', '2025-04-10 13:52:29', 'QR Payment', 12, 1);

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int(15) NOT NULL,
  `username` varchar(80) NOT NULL,
  `password` varchar(80) NOT NULL,
  `name` varchar(80) NOT NULL,
  `email` varchar(80) NOT NULL,
  `phone` bigint(10) NOT NULL,
  `userimage` varchar(800) NOT NULL,
  `image1` varchar(800) NOT NULL,
  `slice1` int(1) NOT NULL,
  `image2` varchar(800) NOT NULL,
  `slice2` int(1) NOT NULL,
  `image3` varchar(800) NOT NULL,
  `slice3` int(1) NOT NULL,
  `token` varchar(255) DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL,
  `token_status` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `username`, `password`, `name`, `email`, `phone`, `userimage`, `image1`, `slice1`, `image2`, `slice2`, `image3`, `slice3`, `token`, `token_expiry`, `token_status`) VALUES
(0, 'AM2307013985', '$2y$10$q7.jUWyTxQyzX2vIdUTspuZ.N/2SpWD1GJMYb8kMLzky6AcGgehZq', 'Muhd Shukri', 'kl2307013985@student.uptm.edu.my', 176411091, '../user/images/user/default.png', 'http://localhost/UCEMS/images/pw/image1.jpg', 0, 'http://localhost/UCEMS/images/pw/image2.jpg', 0, 'http://localhost/UCEMS/images/pw/image3.jpg', 0, NULL, NULL, 1),
(0, 'testuser', '$2y$10$iuwR2RrPM5aSpvIK0LjtOeg70PxamngWdkBfVVwrMo2t3xJa0ndEO', 'James Smith', 'james43@student.uptm.edu.my', 176549082, '../user/images/user/testuser.png', 'http://localhost/UCEMS/images/pw/image1.jpg', 0, 'http://localhost/UCEMS/images/pw/image2.jpg', 0, 'http://localhost/UCEMS/images/pw/image3.jpg', 0, NULL, NULL, 0),
(0, 'Zafran', '$2y$10$S/TCcF5yuJnhueSVmPMN4e9VQzVjw1BQNAjRSrRlhjl.smWpInvpa', 'Muhd Zafran', 'kl2307013984@student.uptm.edu.my', 108498304, '../user/images/user/default.png', 'http://localhost/UCEMS/images/pw/image1.jpg', 0, 'http://localhost/UCEMS/images/pw/image2.jpg', 0, 'http://localhost/UCEMS/images/pw/image3.jpg', 0, NULL, NULL, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `business_info`
--
ALTER TABLE `business_info`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `files`
--
ALTER TABLE `files`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lecturers`
--
ALTER TABLE `lecturers`
  ADD PRIMARY KEY (`username`,`email`,`phone`);

--
-- Indexes for table `lecturer_courses`
--
ALTER TABLE `lecturer_courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `lecturer_username` (`lecturer_username`,`course_name`,`subject_code_name`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`username`,`email`,`phone`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `business_info`
--
ALTER TABLE `business_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `files`
--
ALTER TABLE `files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `lecturer_courses`
--
ALTER TABLE `lecturer_courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
