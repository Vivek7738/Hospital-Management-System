-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 25, 2024 at 06:20 PM
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
-- Database: `hospital_pro`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL,
  `doctor_name` varchar(100) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `patient_id`, `date`, `time`, `doctor_name`, `note`) VALUES
(3, 4, '2024-07-24', '17:30:00', 'rekha', 'I m feeling like fever'),
(7, 4, '2024-08-01', '13:00:00', 'rekha', 'Stomach ache');

-- --------------------------------------------------------

--
-- Table structure for table `clinical_documentation`
--

CREATE TABLE `clinical_documentation` (
  `id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `treatment_details` text NOT NULL,
  `clinical_notes` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `shared_with_doctors` varchar(255) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clinical_documentation`
--

INSERT INTO `clinical_documentation` (`id`, `doctor_id`, `patient_id`, `treatment_details`, `clinical_notes`, `created_at`, `shared_with_doctors`) VALUES
(1, 2, 3, 'dolo given', 'for fever', '2024-07-19 15:14:58', 'all');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `feedback` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`id`, `patient_id`, `feedback`, `created_at`) VALUES
(1, 4, 'Facility is good', '2024-07-25 14:16:11');

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `date` date NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','paid') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `patient_id`, `doctor_id`, `amount`, `date`, `description`, `status`, `created_at`) VALUES
(3, 3, 2, 2000.00, '2024-07-19', 'for lab result', 'pending', '2024-07-19 15:30:41'),
(4, 4, 2, 1400.00, '2024-07-16', 'For Blood test', 'paid', '2024-07-23 14:47:55');

-- --------------------------------------------------------

--
-- Table structure for table `medical_orders`
--

CREATE TABLE `medical_orders` (
  `id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `medicine_name` varchar(255) NOT NULL,
  `dose` varchar(255) NOT NULL,
  `frequency` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medical_orders`
--

INSERT INTO `medical_orders` (`id`, `doctor_id`, `patient_id`, `created_at`, `medicine_name`, `dose`, `frequency`) VALUES
(3, 2, 3, '2024-07-20 15:02:06', 'Paracetamol', '500mg', 'Twice a day'),
(5, 2, 20, '2024-07-20 15:03:02', 'Aspirin', '300mg', 'Thrice a day'),
(6, 2, 23, '2024-07-20 15:03:26', 'Diclofenac', '75mg', 'Once a day'),
(7, 2, 21, '2024-07-20 15:04:04', 'Hydrocortisone', '1% cream', 'Apply Twice a day'),
(8, 2, 4, '2024-07-23 14:07:43', 'Dolo 650', '100mg', 'Twice a day');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `sender_id`, `recipient_id`, `message`, `created_at`) VALUES
(1, 2, 3, 'hii meet me at 4pm', '2024-07-20 13:17:36'),
(3, 2, 3, 'hello', '2024-07-20 14:34:55'),
(4, 2, 3, 'hello', '2024-07-20 14:35:28'),
(5, 2, 3, 'hello', '2024-07-20 14:36:10'),
(6, 2, 4, 'Hii patient', '2024-07-22 14:49:22'),
(7, 2, 4, 'How are you', '2024-07-22 14:49:31'),
(8, 4, 2, 'yes doctor', '2024-07-22 15:23:58'),
(9, 4, 2, 'how are you', '2024-07-22 15:26:23'),
(10, 4, 2, 'what about my status', '2024-07-22 15:28:51'),
(11, 2, 4, 'its good', '2024-07-22 15:29:23'),
(12, 4, 2, 'yes doctor', '2024-07-22 15:32:48'),
(13, 2, 4, 'all good?', '2024-07-22 16:04:59'),
(14, 2, 4, 'all good?', '2024-07-22 16:07:52'),
(15, 2, 4, 'all good?', '2024-07-22 16:10:05'),
(16, 2, 4, 'come for meet', '2024-07-22 16:10:16'),
(17, 4, 2, 'yes sure', '2024-07-22 16:12:40'),
(18, 2, 4, 'you have appointment on 1 august', '2024-07-24 16:15:31');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `recipient_group` enum('doctor','patient','all') NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pdf_files`
--

CREATE TABLE `pdf_files` (
  `id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `file_path` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pdf_files`
--

INSERT INTO `pdf_files` (`id`, `doctor_id`, `patient_id`, `file_name`, `uploaded_at`, `file_path`) VALUES
(1, 2, 4, 'blood_test.pdf', '2024-07-19 15:10:31', '../Pdf/blood_test.pdf'),
(2, 5, 23, 'CBC.pdf', '2024-07-19 15:58:52', '../Pdf/CBC.pdf'),
(3, 2, 4, 'CBC.pdf', '2024-07-24 16:15:06', '../Pdf/CBC.pdf');

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `availability` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedules`
--

INSERT INTO `schedules` (`id`, `doctor_id`, `availability`) VALUES
(1, 2, 'Mon-Fri 9am to 5pm'),
(2, 5, 'Mon-Fri 10am to 8pm');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `otp` varchar(6) DEFAULT NULL,
  `otp_expiry` datetime DEFAULT NULL,
  `role` enum('admin','doctor','patient') NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `reset_token` varchar(100) DEFAULT NULL,
  `reset_time` datetime DEFAULT NULL,
  `security_question` varchar(255) DEFAULT NULL,
  `security_answer` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `otp`, `otp_expiry`, `role`, `created_at`, `reset_token`, `reset_time`, `security_question`, `security_answer`) VALUES
(1, 'Vivek', 'vivekdubey5960@gmail.com', '$2y$10$siu4hHskkRosDpeTIFmAd.p3hI92MZnoL00FB.T14J3k1BvAEQdyW', NULL, NULL, 'admin', '2024-07-14 00:41:22', 'dd1bf7a84823d5e78669195febf4c88084a17931c7fc3181cea9e5348932ae057cefadd5aad9b9ddf0ed4750a5adb8e0eb9d', '2024-07-14 21:09:46', NULL, NULL),
(2, 'rekha', 'dubeyrekha1979@gmail.com', '$2y$10$llKICfmkG7KgIM.XvsD9Y.zx7btQgnzl5FZNqhc/TmCOwagAfFARG', NULL, NULL, 'doctor', '2024-07-14 04:30:50', NULL, NULL, NULL, NULL),
(3, 'xyz', 'xyz@gmail.com', '$2y$10$pDnAstnYmfba20rsM/RWD./yDHEydTctNhykTt2DgAmlE2zLS83rG', NULL, NULL, 'patient', '2024-07-14 16:42:23', NULL, NULL, NULL, NULL),
(4, 'tycs', 'tycs@gmail.com', '$2y$10$Q6PnlK5mMI6OfH293CMc5eZUj9MD0o194i5fDet1euFsbCeDTCZwy', NULL, NULL, 'patient', '2024-07-14 20:56:57', NULL, NULL, NULL, NULL),
(5, 'rohan', 'dubeyrohan0100@gmail.com', '$2y$10$xnG8PIP7WOlCRC.0qiebM.nSvSGkN5EIL18DqlLlmNwm.Wy9olSWa', NULL, NULL, 'doctor', '2024-07-14 21:15:39', NULL, NULL, NULL, NULL),
(20, 'hello', 'wanalah434@reebsd.com', '$2y$10$YVHuEEKT883RdwUxTwuQxu4etQQt6wmAfeAVRRLoRl4koEhWF7MJ6', NULL, NULL, 'patient', '2024-07-16 18:28:09', NULL, NULL, NULL, NULL),
(21, 'des', 'sovaxac113@reebsd.com', '$2y$10$p2VGKOZyZyCOsu/39aUZmuZHEX4dc.2GNb7EbxpYeV/lqqGjjC9L2', NULL, NULL, 'patient', '2024-07-16 18:40:05', NULL, NULL, NULL, NULL),
(23, 'abc', 'facehel247@ikangou.com', '$2y$10$Wxxzb4G4mjZR4z9hL1Mnbuf8g4OfqOVvKZfl2BdF5IErVCfGpvnPq', NULL, NULL, 'patient', '2024-07-16 18:50:26', NULL, NULL, NULL, NULL),
(24, 'uvw', 'reholaw883@ikangou.com', '$2y$10$6fjyNnkt.IGd7UiBD6k2AOQVeEcuH7b5hUWDxuMvEP6xZ8/MsMqNe', NULL, NULL, 'patient', '2024-07-16 18:55:12', NULL, NULL, NULL, NULL),
(25, 'pqr', 'hello@gmail.com', '$2y$10$kf.Pk3AyQLzVFId5dT1rRe16PCtSA6DNgilVN2EpQplSGfKv.Kfwu', NULL, NULL, 'patient', '2024-07-16 19:07:21', NULL, NULL, NULL, NULL),
(28, 'kokila', 'kokila@gmail.com', '$2y$10$2KufRIR78f4mWp4ixmNn1ORGBTbJsYhXuRgi1JvKjcSdnt0TWPLAm', NULL, NULL, 'patient', '2024-07-19 20:42:10', NULL, NULL, NULL, NULL),
(30, 'kon', 'kon@gmail.com', '$2y$10$fDV511Mhtm5a5pGDvu7ucewiPE4TpgMDmoUT6fQs8FL8RWU3ij4vO', NULL, NULL, 'patient', '2024-07-20 20:57:26', NULL, NULL, NULL, NULL),
(32, 'tech', 'techthings2022@gmail.com', '$2y$10$lAnJ.NYSHUZQxVyB4LiWRuHpZdJ5BsqT8pS2xOvYDmEEw8fyDVAp6', NULL, NULL, 'doctor', '2024-07-25 21:43:02', NULL, NULL, 'food', 'rice');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `clinical_documentation`
--
ALTER TABLE `clinical_documentation`
  ADD PRIMARY KEY (`id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- Indexes for table `medical_orders`
--
ALTER TABLE `medical_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `recipient_id` (`recipient_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pdf_files`
--
ALTER TABLE `pdf_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `clinical_documentation`
--
ALTER TABLE `clinical_documentation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `medical_orders`
--
ALTER TABLE `medical_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pdf_files`
--
ALTER TABLE `pdf_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `clinical_documentation`
--
ALTER TABLE `clinical_documentation`
  ADD CONSTRAINT `clinical_documentation_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `clinical_documentation_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `invoices_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `medical_orders`
--
ALTER TABLE `medical_orders`
  ADD CONSTRAINT `medical_orders_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `medical_orders_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `pdf_files`
--
ALTER TABLE `pdf_files`
  ADD CONSTRAINT `pdf_files_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `pdf_files_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `schedules`
--
ALTER TABLE `schedules`
  ADD CONSTRAINT `schedules_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
