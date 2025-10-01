-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 30, 2025 at 07:32 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `receiver_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `receivers`
--

CREATE TABLE `receivers` (
  `ID` int(9) NOT NULL,
  `lastname` varchar(255) NOT NULL,
  `firstname` varchar(255) NOT NULL,
  `age` int(3) NOT NULL,
  `c&y` varchar(50) NOT NULL,
  `school` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `address` varchar(50) NOT NULL,
  `status` int(1) NOT NULL,
  `date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `receivers`
--

INSERT INTO `receivers` (`ID`, `lastname`, `firstname`, `age`, `c&y`, `school`, `email`, `address`, `status`, `date`) VALUES
(123452789, 'Zorozor', 'Kimberly', 22, 'bisis', 'hindi ako punte school', '', '', 1, NULL),
(123456788, 'Kiroro', 'Kaeya', 11, 'hindi niya alam', 'skol ni mamamo', '', '', 0, NULL),
(123456789, 'Rodriguez', 'Kimberly', 22, 'bobcourse', 'skowl ni agom mo ', '', '', 1, NULL),
(923111189, 'Poligon', 'Koryoo', 22, 'golp course', 'bawdos skowl', '', '', 1, NULL),
(923456789, 'wakin', 'pocholo', 22, 'hendi babago course', 'paano mag aral skol', '', '', 1, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `receivers`
--
ALTER TABLE `receivers`
  ADD PRIMARY KEY (`ID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
