-- phpMyAdmin SQL Dump
-- version 4.6.5.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3333
-- Generation Time: May 26, 2017 at 07:54 PM
-- Server version: 10.1.21-MariaDB
-- PHP Version: 5.6.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `phpwebforum`
--

-- --------------------------------------------------------

--
-- Table structure for table `boards`
--

CREATE TABLE `boards` (
  `boardId` int(11) NOT NULL,
  `title` varchar(250) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;

--
-- Dumping data for table `boards`
--

INSERT INTO `boards` (`boardId`, `title`) VALUES
(1, 'Musictalk'),
(2, 'Geartech'),
(3, 'Events');

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `postId` int(11) NOT NULL,
  `threadId` int(11) NOT NULL,
  `userId` int(11) NOT NULL,
  `date` date NOT NULL,
  `title` varchar(100) DEFAULT NULL,
  `body` longtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`postId`, `threadId`, `userId`, `date`, `title`, `body`) VALUES
(1, 1, 3, '0000-00-00', 'Hello!', 'My first post'),
(2, 2, 3, '0000-00-00', 'I LOVE microkorgs!!!', 'Not.'),
(3, 3, 3, '0000-00-00', 'I saw Sunn O)))', 'Got tinnitus as a result :D'),
(4, 4, 3, '0000-00-00', 'woW ME', 'Enter body here...'),
(5, 5, 2, '0000-00-00', 'Empty body', ''),
(6, 6, 2, '0000-00-00', 'I like noise music', 'In other words: It is screeching, pounding dissonance. It is not music.'),
(7, 7, 3, '0000-00-00', 'Hello Chen', 'I am here!');

-- --------------------------------------------------------

--
-- Table structure for table `threads`
--

CREATE TABLE `threads` (
  `threadId` int(11) NOT NULL,
  `boardId` int(11) NOT NULL,
  `title` varchar(250) NOT NULL,
  `date` date NOT NULL,
  `replies` int(250) NOT NULL,
  `views` int(250) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;

--
-- Dumping data for table `threads`
--

INSERT INTO `threads` (`threadId`, `boardId`, `title`, `date`, `replies`, `views`) VALUES
(1, 1, 'Hello!', '0000-00-00', 0, 0),
(2, 2, 'I LOVE microkorgs!!!', '0000-00-00', 0, 0),
(3, 3, 'I saw Sunn O)))', '0000-00-00', 0, 0),
(4, 3, 'woW ME', '0000-00-00', 0, 0),
(5, 1, 'Empty body', '0000-00-00', 0, 0),
(6, 1, 'I like noise music', '0000-00-00', 0, 0),
(7, 1, 'Hello Chen', '0000-00-00', 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `userId` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`userId`, `username`, `password`) VALUES
(1, 'jerry', 'abC123'),
(2, 'mstinis', 'abC123'),
(3, 'lalala', 'abC123');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `boards`
--
ALTER TABLE `boards`
  ADD PRIMARY KEY (`boardId`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`postId`),
  ADD KEY `threadId` (`threadId`),
  ADD KEY `userId` (`userId`);

--
-- Indexes for table `threads`
--
ALTER TABLE `threads`
  ADD PRIMARY KEY (`threadId`),
  ADD KEY `boardId` (`boardId`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`userId`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `boards`
--
ALTER TABLE `boards`
  MODIFY `boardId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `postId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;
--
-- AUTO_INCREMENT for table `threads`
--
ALTER TABLE `threads`
  MODIFY `threadId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;
--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `userId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
--
-- Constraints for dumped tables
--

--
-- Constraints for table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`threadId`) REFERENCES `threads` (`threadId`),
  ADD CONSTRAINT `posts_ibfk_2` FOREIGN KEY (`userId`) REFERENCES `users` (`userId`);

--
-- Constraints for table `threads`
--
ALTER TABLE `threads`
  ADD CONSTRAINT `threads_ibfk_1` FOREIGN KEY (`boardId`) REFERENCES `boards` (`boardId`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
