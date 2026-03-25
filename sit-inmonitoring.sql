-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 25, 2026 at 10:51 AM
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
-- Database: `sit-inmonitoring`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `adminID` varchar(50) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`adminID`, `Name`, `password`) VALUES
('admin001', 'CCS Admin', 'admin123');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` varchar(50) NOT NULL,
  `text` text NOT NULL,
  `date` date NOT NULL,
  `createdAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` varchar(50) NOT NULL,
  `idNumber` varchar(50) NOT NULL,
  `studentName` varchar(200) NOT NULL,
  `text` text NOT NULL,
  `date` date NOT NULL,
  `createdAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `id` varchar(50) NOT NULL,
  `idNumber` varchar(50) NOT NULL,
  `studentName` varchar(200) NOT NULL,
  `lab` varchar(50) NOT NULL,
  `date` date NOT NULL,
  `time` varchar(20) NOT NULL,
  `purpose` varchar(255) NOT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `createdAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sitins`
--

CREATE TABLE `sitins` (
  `sitId` varchar(50) NOT NULL,
  `idNumber` varchar(50) NOT NULL,
  `studentName` varchar(200) NOT NULL,
  `purpose` varchar(100) NOT NULL,
  `lab` varchar(50) NOT NULL,
  `date` date NOT NULL,
  `timeIn` varchar(20) NOT NULL,
  `timeOut` varchar(20) DEFAULT NULL,
  `status` enum('Active','Completed','Cancelled') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `IdNumber` varchar(50) NOT NULL,
  `firstName` varchar(100) NOT NULL,
  `lastName` varchar(100) NOT NULL,
  `middleName` varchar(100) DEFAULT '',
  `yearLevel` varchar(20) NOT NULL,
  `Course` varchar(50) NOT NULL,
  `email` varchar(150) NOT NULL,
  `Address` varchar(255) DEFAULT '',
  `password` varchar(255) NOT NULL,
  `photo` longtext NOT NULL,
  `remainingCredits` int(11) DEFAULT 30,
  `banned` tinyint(1) DEFAULT 0,
  `createdAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`IdNumber`, `firstName`, `lastName`, `middleName`, `yearLevel`, `Course`, `email`, `Address`, `password`, `photo`, `remainingCredits`, `banned`, `createdAt`) VALUES
('2026-00001', 'jerson', 'villegas', 'amiana', '1st Year', 'BSHM', 'jetro@email.com', '123 address city country', '$2y$10$eLpbRo0gtv9xezRMsHnMt.4onSnGUAUb9h5iVhX2Qpr3J3Bro7Cyu', '', 30, 0, '2026-03-25 04:15:25');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`adminID`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idNumber` (`idNumber`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idNumber` (`idNumber`);

--
-- Indexes for table `sitins`
--
ALTER TABLE `sitins`
  ADD PRIMARY KEY (`sitId`),
  ADD KEY `idNumber` (`idNumber`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`IdNumber`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`idNumber`) REFERENCES `students` (`IdNumber`) ON DELETE CASCADE;

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`idNumber`) REFERENCES `students` (`IdNumber`) ON DELETE CASCADE;

--
-- Constraints for table `sitins`
--
ALTER TABLE `sitins`
  ADD CONSTRAINT `sitins_ibfk_1` FOREIGN KEY (`idNumber`) REFERENCES `students` (`IdNumber`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
