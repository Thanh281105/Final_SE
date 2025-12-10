-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 10, 2025 at 04:53 AM
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
-- Database: `bluebirdhotel`
--

-- --------------------------------------------------------

--
-- Table structure for table `branches`
--

CREATE TABLE `branches` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `address` text DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `branches`
--

INSERT INTO `branches` (`id`, `name`, `address`, `status`, `created_at`) VALUES
(1, 'Ho Chi Minh City ', '123 Hotel Street, District 1, Ho Chi Minh City', 'Active', '2025-12-09 18:22:39'),
(2, 'Ha Noi ', '456 Hotel Road, Ba Dinh, Ha Noi', 'Active', '2025-12-09 18:22:39');

-- --------------------------------------------------------

--
-- Table structure for table `emp_login`
--

CREATE TABLE `emp_login` (
  `empid` int(100) NOT NULL,
  `Emp_Email` varchar(50) NOT NULL,
  `Emp_Password` varchar(50) NOT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expiry` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `emp_login`
--

INSERT INTO `emp_login` (`empid`, `Emp_Email`, `Emp_Password`, `reset_token`, `reset_expiry`) VALUES
(1, 'Admin@gmail.com', '1234', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `id` int(30) NOT NULL,
  `Name` varchar(30) NOT NULL,
  `Email` varchar(30) NOT NULL,
  `RoomType` varchar(30) NOT NULL,
  `Bed` varchar(30) NOT NULL,
  `NoofRoom` int(30) NOT NULL,
  `cin` date NOT NULL,
  `cout` date NOT NULL,
  `noofdays` int(30) NOT NULL,
  `roomtotal` decimal(10,2) NOT NULL,
  `bedtotal` decimal(10,2) NOT NULL,
  `meal` varchar(30) NOT NULL,
  `mealtotal` decimal(10,2) NOT NULL,
  `finaltotal` decimal(10,2) NOT NULL,
  `status` enum('Pending','Paid') DEFAULT 'Pending',
  `qr_url` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment`
--

INSERT INTO `payment` (`id`, `Name`, `Email`, `RoomType`, `Bed`, `NoofRoom`, `cin`, `cout`, `noofdays`, `roomtotal`, `bedtotal`, `meal`, `mealtotal`, `finaltotal`, `status`, `qr_url`) VALUES
(91, 'Pham Nguyen Anh', 'phamnguyenanhsva@gmail.com', 'Single Room', 'Double', 1, '2025-12-11', '2025-12-14', 3, 3000000.00, 60000.00, 'Breakfast', 120000.00, 3180000.00, 'Paid', NULL),
(92, 'Pham Nguyen Anh', 'phamnguyenanhsva@gmail.com', 'Single Room', 'Double', 2, '2025-12-12', '2025-12-14', 2, 4000000.00, 80000.00, 'Room only', 0.00, 4080000.00, 'Paid', NULL),
(93, 'Pham Nguyen Anh', 'phamnguyenanhsva@gmail.com', 'Single Room', 'Single', 1, '2025-12-10', '2025-12-13', 3, 3000000.00, 30000.00, 'Room only', 0.00, 3030000.00, 'Paid', NULL),
(94, 'Pham Nguyen Anh', 'phamnguyenanhsva@gmail.com', 'Single Room', 'Single', 1, '2025-12-11', '2025-12-13', 2, 2000000.00, 20000.00, 'Room only', 0.00, 2020000.00, 'Paid', NULL),
(95, 'Pham Nguyen Anh', 'phamnguyenanhsva@gmail.com', 'Single Room', 'Double', 1, '2025-12-11', '2025-12-13', 2, 2000000.00, 40000.00, 'Room only', 0.00, 2040000.00, 'Paid', NULL),
(96, 'Pham Nguyen Anh', 'phamnguyenanhsva@gmail.com', 'Superior Room', 'Single', 1, '2025-12-10', '2025-12-12', 2, 6000000.00, 60000.00, 'Room only', 0.00, 6060000.00, 'Paid', NULL),
(97, 'Pham Nguyen Anh', 'phamnguyenanhsva@gmail.com', 'Single Room', 'Double', 2, '2025-12-10', '2025-12-12', 2, 4000000.00, 80000.00, 'Room only', 0.00, 4080000.00, 'Paid', NULL),
(98, 'Pham Nguyen Anh', 'phamnguyenanhsva@gmail.com', 'Superior Room', 'Single', 1, '2025-12-11', '2025-12-13', 2, 6000000.00, 60000.00, 'Room only', 0.00, 6060000.00, 'Paid', NULL),
(100, 'Pham Nguyen Anh', 'phamnguyenanhsva@gmail.com', 'Single Room', 'Double', 2, '2025-12-10', '2025-12-13', 3, 6000000.00, 120000.00, 'Breakfast', 240000.00, 6360000.00, 'Pending', NULL),
(101, 'Pham Nguyen Anh', 'phamnguyenanhsva@gmail.com', 'Single Room', 'Double', 3, '2025-12-19', '2025-12-21', 2, 6000000.00, 120000.00, 'Half Board', 360000.00, 6480000.00, '', NULL),
(103, 'Pham Nguyen Anh', 'phamnguyenanhsva@gmail.com', 'Single Room', 'Double', 1, '2025-12-17', '2025-12-19', 2, 2000000.00, 40000.00, 'Room only', 0.00, 2040000.00, 'Pending', NULL),
(104, 'pna', '523h0117@student.tdtu.edu.vn', 'Superior Room', 'Double', 1, '2025-12-12', '2025-12-14', 2, 6000000.00, 120000.00, 'Breakfast', 240000.00, 6360000.00, 'Paid', 'https://api.qrserver.com/v1/create-qr-code/?data=BookingID%3A104%7CAmount%3A6360000%7CBranch%3AHa+Noi+&size=200x200'),
(105, 'pna', '523h0117@student.tdtu.edu.vn', 'Superior Room', 'Single', 1, '2025-12-11', '2025-12-13', 2, 6000000.00, 60000.00, 'Half Board', 180000.00, 6240000.00, '', 'https://api.qrserver.com/v1/create-qr-code/?data=BookingID%3A105%7CAmount%3A6240000%7CBranch%3AHo+Chi+Minh+City+&size=200x200'),
(107, 'Thanh', 'thanhhoangnvbg@gmail.com', 'Single Room', 'Double', 1, '2025-12-18', '2025-12-20', 2, 2000.00, 40.00, 'Breakfast', 80.00, 2120.00, 'Pending', 'https://api.qrserver.com/v1/create-qr-code/?data=BookingID%3A107%7CAmount%3A6180000%7CBranch%3AHo+Chi+Minh+City+&size=200x200');

-- --------------------------------------------------------

--
-- Table structure for table `room`
--

CREATE TABLE `room` (
  `id` int(30) NOT NULL,
  `type` varchar(50) NOT NULL,
  `bedding` varchar(50) NOT NULL,
  `Country` varchar(50) DEFAULT 'Main Branch',
  `room_number` varchar(10) NOT NULL DEFAULT '1',
  `floor` int(11) NOT NULL DEFAULT 1,
  `status` enum('Available','Occupied','Reserved') DEFAULT 'Available',
  `current_booking_id` int(11) DEFAULT NULL,
  `reserved_until` datetime DEFAULT NULL,
  `reserved_booking_id` int(11) DEFAULT NULL
) ;

--
-- Dumping data for table `room`
--

INSERT INTO `room` (`id`, `type`, `bedding`, `Country`, `room_number`, `floor`, `status`, `current_booking_id`, `reserved_until`, `reserved_booking_id`) VALUES
(11, 'Guest House', 'Single', 'Ha Noi', '1', 1, 'Available', NULL, NULL, NULL),
(13, 'Guest House', 'Triple', 'Ha Noi', '3', 1, 'Available', NULL, NULL, NULL),
(16, 'Superior Room', 'Double', 'Ha Noi', '1', 2, 'Occupied', 104, NULL, NULL),
(20, 'Single Room', 'Single', 'Ha Noi', '1', 3, 'Available', NULL, NULL, NULL),
(22, 'Superior Room', 'Single', 'Ha Noi', '2', 2, 'Available', NULL, NULL, NULL),
(23, 'Deluxe Room', 'Single', 'Ha Noi', '1', 4, 'Available', NULL, NULL, NULL),
(24, 'Deluxe Room', 'Triple', 'Ha Noi', '2', 4, 'Available', NULL, NULL, NULL),
(30, 'Deluxe Room', 'Single', 'Ha Noi', '3', 4, 'Available', NULL, NULL, NULL),
(32, 'Single Room', 'Single', 'Ho Chi Minh city', '1', 1, 'Available', NULL, NULL, NULL),
(33, 'Single Room', 'Double', 'Ho Chi Minh city', '2', 1, 'Reserved', NULL, '2025-12-10 20:19:24', 103),
(36, 'Single Room', 'Double', 'Ho Chi Minh city', '3', 1, 'Available', NULL, NULL, NULL),
(37, 'Superior Room', 'Single', 'Ha Noi', '4', 4, 'Available', NULL, NULL, NULL),
(38, 'Superior Room', 'Single', 'Ho Chi Minh city', '1', 2, 'Reserved', NULL, '2025-12-11 04:31:31', 107),
(39, 'Single Room', 'Double', 'Ho Chi Minh city', '4', 1, 'Available', NULL, NULL, NULL),
(40, 'Single Room', 'Double', 'Ho Chi Minh city', '5', 1, 'Available', NULL, NULL, NULL),
(41, 'Single Room', 'Double', 'Ho Chi Minh city', '2', 2, 'Available', NULL, NULL, NULL),
(42, 'Deluxe Room', 'Double', 'Ho Chi Minh City ', '4', 3, 'Available', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `roombook`
--

CREATE TABLE `roombook` (
  `id` int(10) NOT NULL,
  `Name` varchar(50) NOT NULL,
  `Email` varchar(50) NOT NULL,
  `Country` varchar(30) NOT NULL,
  `Phone` varchar(30) NOT NULL,
  `RoomType` varchar(30) NOT NULL,
  `Bed` varchar(30) NOT NULL,
  `Meal` varchar(30) NOT NULL,
  `NoofRoom` int(11) NOT NULL DEFAULT 1,
  `cin` date NOT NULL,
  `cout` date NOT NULL,
  `nodays` int(50) NOT NULL,
  `stat` varchar(30) NOT NULL DEFAULT 'Not Confirmed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roombook`
--

INSERT INTO `roombook` (`id`, `Name`, `Email`, `Country`, `Phone`, `RoomType`, `Bed`, `Meal`, `NoofRoom`, `cin`, `cout`, `nodays`, `stat`) VALUES
(91, 'Pham Nguyen Anh', 'phamnguyenanhsva@gmail.com', 'Ho Chi Minh city', '0936765762', 'Single Room', 'Double', 'Breakfast', 1, '2025-12-11', '2025-12-14', 3, 'Confirmed'),
(92, 'Pham Nguyen Anh', 'phamnguyenanhsva@gmail.com', 'Ho Chi Minh city', '0936765762', 'Single Room', 'Double', 'Room only', 2, '2025-12-12', '2025-12-14', 2, 'Confirmed'),
(93, 'Pham Nguyen Anh', 'phamnguyenanhsva@gmail.com', 'Ho Chi Minh city', '0936765762', 'Single Room', 'Single', 'Room only', 1, '2025-12-10', '2025-12-13', 3, 'Confirmed'),
(94, 'Pham Nguyen Anh', 'phamnguyenanhsva@gmail.com', 'Ho Chi Minh city', '0936765762', 'Single Room', 'Single', 'Room only', 1, '2025-12-11', '2025-12-13', 2, 'Not Confirmed'),
(95, 'Pham Nguyen Anh', 'phamnguyenanhsva@gmail.com', 'Ho Chi Minh city', '0936765762', 'Single Room', 'Double', 'Room only', 1, '2025-12-11', '2025-12-13', 2, 'Confirmed'),
(97, 'Pham Nguyen Anh', 'phamnguyenanhsva@gmail.com', 'Ho Chi Minh city', '0936765762', 'Single Room', 'Double', 'Room only', 2, '2025-12-10', '2025-12-12', 2, 'Confirmed'),
(98, 'Pham Nguyen Anh', 'phamnguyenanhsva@gmail.com', 'Ho Chi Minh city', '0936765762', 'Superior Room', 'Single', 'Room only', 1, '2025-12-11', '2025-12-13', 2, 'Confirmed'),
(99, 'Pham Nguyen Anh', 'phamnguyenanhsva@gmail.com', 'Ho Chi Minh city', '0936765762', 'Single Room', 'Double', 'Breakfast', 2, '2025-12-11', '2025-12-13', 2, 'Rejected'),
(100, 'Pham Nguyen Anh', 'phamnguyenanhsva@gmail.com', 'Ho Chi Minh city', '0936765762', 'Single Room', 'Double', 'Breakfast', 2, '2025-12-10', '2025-12-13', 3, 'Not Confirmed'),
(101, 'Pham Nguyen Anh', 'phamnguyenanhsva@gmail.com', 'Ho Chi Minh city', '0936765762', 'Single Room', 'Double', 'Half Board', 3, '2025-12-19', '2025-12-21', 2, 'Cancelled'),
(102, 'Pham Nguyen Anh', 'phamnguyenanhsva@gmail.com', 'Ha Noi ', '0936765762', 'Deluxe Room', 'Double', 'Room only', 1, '2025-12-11', '2025-12-13', 2, 'Cancelled'),
(103, 'Pham Nguyen Anh', 'phamnguyenanhsva@gmail.com', 'Ho Chi Minh City ', '0936765762', 'Single Room', 'Double', 'Room only', 1, '2025-12-17', '2025-12-19', 2, 'Confirmed'),
(104, 'pna', '523h0117@student.tdtu.edu.vn', 'Ha Noi ', '0987654321', 'Superior Room', 'Double', 'Breakfast', 1, '2025-12-12', '2025-12-14', 2, 'Confirmed'),
(105, 'pna', '523h0117@student.tdtu.edu.vn', 'Ho Chi Minh City ', '0987654321', 'Superior Room', 'Single', 'Half Board', 1, '2025-12-11', '2025-12-13', 2, 'Cancelled'),
(106, 'pna', '523h0117@student.tdtu.edu.vn', 'Ho Chi Minh City ', '0987654321', 'Deluxe Room', 'Double', 'Full Board', 1, '2025-12-11', '2025-12-13', 2, 'Cancelled'),
(107, 'Thanh', 'thanhhoangnvbg@gmail.com', '', '0123456789', 'Single Room', 'Double', 'Breakfast', 1, '2025-12-18', '2025-12-20', 2, 'Confirmed');

-- --------------------------------------------------------

--
-- Table structure for table `signup`
--

CREATE TABLE `signup` (
  `UserID` int(100) NOT NULL,
  `Username` varchar(50) NOT NULL,
  `Email` varchar(50) NOT NULL,
  `Password` varchar(50) NOT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expiry` datetime DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `phone` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `signup`
--

INSERT INTO `signup` (`UserID`, `Username`, `Email`, `Password`, `reset_token`, `reset_expiry`, `avatar`, `phone`) VALUES
(1, 'Tushar Pankhaniya', 'tusharpankhaniya2202@gmail.com', '123', NULL, NULL, NULL, ''),
(9, 'Pham Nguyen Anh', 'phamnguyenanhsva@gmail.com', 'oah2317', 'fd358d39303fb7b57b088b14a194aaa06436dfd3349f25593df0106753e81457', '2025-12-09 13:41:57', 'image/avatar_692760adbce023.45364591.jpg', '0936765762'),
(10, 'pna', '523h0117@student.tdtu.edu.vn', '12345', NULL, NULL, NULL, '0987654321'),
(11, 'Thanh', 'thanhhoangnvbg@gmail.com', '12345', NULL, NULL, NULL, '0123456789');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `id` int(30) NOT NULL,
  `name` varchar(30) NOT NULL,
  `work` varchar(30) NOT NULL,
  `Country` varchar(50) DEFAULT 'Main Branch'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`id`, `name`, `work`, `Country`) VALUES
(1, 'Tushar pankhaniya', 'Manager', 'Ho Chi Minh city'),
(3, 'rohit patel', 'Cook', 'Ho Chi Minh city'),
(4, 'Dipak', 'Cook', 'Ho Chi Minh city'),
(5, 'tirth', 'Helper', 'Ho Chi Minh city'),
(6, 'mohan', 'Helper', 'Ha Noi'),
(7, 'shyam', 'cleaner', 'Ha Noi'),
(8, 'rohan', 'weighter', 'Ha Noi'),
(9, 'hiren', 'weighter', 'Ha Noi'),
(10, 'nikunj', 'weighter', 'Ha Noi'),
(11, 'rekha', 'Cook', 'Ha Noi'),
(12, 'ád', 'Manager', 'Ha Noi');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `branches`
--
ALTER TABLE `branches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `emp_login`
--
ALTER TABLE `emp_login`
  ADD PRIMARY KEY (`empid`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `room`
--
ALTER TABLE `room`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_room_location` (`Country`,`floor`,`room_number`);

--
-- Indexes for table `roombook`
--
ALTER TABLE `roombook`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `signup`
--
ALTER TABLE `signup`
  ADD PRIMARY KEY (`UserID`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `branches`
--
ALTER TABLE `branches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `emp_login`
--
ALTER TABLE `emp_login`
  MODIFY `empid` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `room`
--
ALTER TABLE `room`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roombook`
--
ALTER TABLE `roombook`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=108;

--
-- AUTO_INCREMENT for table `signup`
--
ALTER TABLE `signup`
  MODIFY `UserID` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
