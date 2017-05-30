-- phpMyAdmin SQL Dump
-- version 4.6.6
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 30, 2017 at 08:51 AM
-- Server version: 5.6.35
-- PHP Version: 5.6.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cp4776_webforum`
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
(3, 'Events'),
(4, 'Cool stuff');

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `postId` int(11) NOT NULL,
  `threadId` int(11) NOT NULL,
  `userId` int(11) NOT NULL,
  `date` datetime NOT NULL,
  `body` longtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`postId`, `threadId`, `userId`, `date`, `body`) VALUES
(24, 12, 4, '2017-05-29 13:04:41', 'It\'s definitely not for everyone, but I recommend listening to some Merzbow'),
(25, 12, 4, '2017-05-29 13:05:10', 'How do you do?'),
(26, 13, 4, '2017-05-29 13:05:42', 'Try one and see for yourself'),
(27, 14, 4, '2017-05-29 13:08:20', 'Drew McDowall is also performing'),
(28, 15, 4, '2017-05-29 13:10:24', 'sadsafdsafsda'),
(29, 12, 5, '2017-05-29 17:16:41', 'dude what you really need is to listen to your colon through a garden hose'),
(30, 16, 5, '2017-05-29 17:18:59', 'I\'m looking for a used turntable in the 100-200$ price range anyone know of any good models that fit that description? '),
(32, 16, 3, '2017-05-29 22:41:37', 'no'),
(33, 18, 6, '2017-05-29 22:41:43', 'is in 3 days '),
(34, 18, 3, '2017-05-29 22:42:01', 'Where tho?'),
(36, 18, 4, '2017-05-30 09:30:16', 'Hi'),
(37, 19, 4, '2017-05-30 09:30:40', 'right?'),
(38, 12, 3, '2017-05-30 09:35:38', 'no'),
(39, 12, 8, '2017-05-30 09:40:10', 'this sucks'),
(40, 20, 8, '2017-05-30 10:02:58', 'zero cool');

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
(12, 1, 'I like noise music', '2017-05-29', 0, 0),
(13, 2, 'MS20m is the ultimate MS20', '2017-05-29', 0, 0),
(14, 3, 'Pharmakon in Montreal this Friday', '2017-05-29', 0, 0),
(15, 1, 'Wowo owoiwj', '2017-05-29', 0, 0),
(16, 2, 'I\'m looking for a turntable', '2017-05-29', 0, 0),
(18, 1, 'Death in June', '2017-05-29', 0, 0),
(19, 4, 'I LOVE cool stuff', '2017-05-30', 0, 0),
(20, 4, 'more cool', '2017-05-30', 0, 0);

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
(3, 'lalala', 'abC123'),
(4, 'slugbait', 'abC123'),
(5, 'poop', 'Poop12'),
(6, 'blabla', 'Blabla1'),
(8, 'ZMAN', 'abC123');

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
  MODIFY `boardId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `postId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;
--
-- AUTO_INCREMENT for table `threads`
--
ALTER TABLE `threads`
  MODIFY `threadId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;
--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `userId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;
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
