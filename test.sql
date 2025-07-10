-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 10, 2024 at 12:00 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+03:00";

--
-- Database: `sorgu`
--

-- --------------------------------------------------------

--
-- Table structure for table `people`
--

CREATE TABLE `people` (
  `id` int(11) NOT NULL,
  `tc` bigint(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `birth_date` date NOT NULL,
  `birth_place` varchar(100) NOT NULL,
  `mother_id` int(11) DEFAULT NULL,
  `father_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `people` (Tamamen Sahte Veriler)
--

INSERT INTO `people` (`id`, `tc`, `first_name`, `last_name`, `birth_date`, `birth_place`, `mother_id`, `father_id`) VALUES
-- Yılmaz Ailesi - 1. Nesil (Büyükanne/Büyükbaba)
(1, 10000000001, 'Ahmet', 'Yılmaz', '1950-01-15', 'Ankara', NULL, NULL),
(2, 10000000002, 'Fatma', 'Yılmaz', '1952-03-20', 'İstanbul', NULL, NULL),
(3, 20000000001, 'Hasan', 'Kaya', '1951-05-10', 'İzmir', NULL, NULL),
(4, 20000000002, 'Ayşe', 'Kaya', '1953-07-25', 'Bursa', NULL, NULL),

-- Yılmaz Ailesi - 2. Nesil (Anne/Baba)
(5, 30000000001, 'Mehmet', 'Yılmaz', '1975-06-12', 'Ankara', 2, 1),
(6, 30000000002, 'Zeynep', 'Yılmaz', '1978-09-30', 'İzmir', 4, 3),

-- Yılmaz Ailesi - 3. Nesil (Çocuklar)
(7, 40000000001, 'Mustafa', 'Yılmaz', '2000-11-05', 'Ankara', 6, 5),
(8, 40000000002, 'Elif', 'Yılmaz', '2002-02-18', 'Ankara', 6, 5);

--
-- Indexes for table `people`
--
ALTER TABLE `people`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tc` (`tc`),
  ADD KEY `mother_id` (`mother_id`),
  ADD KEY `father_id` (`father_id`);

--
-- AUTO_INCREMENT for table `people`
--
ALTER TABLE `people`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for table `people`
--
ALTER TABLE `people`
  ADD CONSTRAINT `people_ibfk_1` FOREIGN KEY (`mother_id`) REFERENCES `people` (`id`),
  ADD CONSTRAINT `people_ibfk_2` FOREIGN KEY (`father_id`) REFERENCES `people` (`id`);
COMMIT;
