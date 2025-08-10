-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: gehri.iad1-mysql-e2-11a.dreamhost.com
-- Generation Time: Aug 10, 2025 at 01:10 PM
-- Server version: 8.0.28-0ubuntu0.20.04.3
-- PHP Version: 8.1.2-1ubuntu2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `jamen_dk`
--

--
-- Dumping data for table `dream_boards`
--

INSERT INTO `dream_boards` (`id`, `slug`, `user_id`, `title`, `description`, `created_at`, `updated_at`, `archived`, `deleted_at`, `type`) VALUES
(1, '27da0d2cc0', 1, 'test 1', '<div>making this test sdf</div>', '2025-08-03 09:11:15', '2025-08-04 15:28:34', 0, '2025-08-04 15:28:34', 'dream'),
(2, '6f9eefc5cb', 1, 'test 2', 'test 2 srg gr \r\ndfg srg rg\r\n\r\n\r\ndrdg r g', '2025-08-03 09:34:17', '2025-08-04 15:29:12', 0, '2025-08-04 15:29:12', 'dream'),
(3, 'NfrTW2Kr', 1, 'test 3', '<div>sfgdfrfgh dfg efg<br><strong>Jdujdbddk</strong></div>', '2025-08-03 10:07:35', '2025-08-04 15:13:34', 1, '2025-08-04 15:13:34', 'dream'),
(4, 'bbCwmols', 1, 'eee', 'eeee', '2025-08-04 00:23:40', '2025-08-04 15:13:28', 1, '2025-08-04 15:13:28', 'dream'),
(5, 'FBJhpoAj', 1, 'øå', 'dføåøåæ\r\n\r\nø', '2025-08-04 00:32:52', '2025-08-04 15:13:24', 1, '2025-08-04 15:13:24', 'dream'),
(6, 'zHjfSkK2', 1, 'added location edit 3', '<ol><li>edit <em>dadfasdedit </em><strong><em><del>ipiposjg</del></em></strong></li><li>sfdgg</li></ol><div><br>sgsdgf</div><ul><li>ssdfsf</li><li>sfsf</li><li><br></li></ul>', '2025-08-04 00:49:10', '2025-08-06 21:59:02', 0, NULL, 'dream'),
(7, 'x5YINRRj', 1, 'jpinonsdfg 90ndfgnj', '<div><strong>njnåøaa </strong>\'po jfgjpoa<br><br>fgk afgip¨<em><del>jert2w\'mp\'sr\'g</del></em>¨ RROGIERGN\"UU2joioinzd\"okåaefagn\'¨g</div><ul><li>sdvsdv</li><li>svsv</li></ul><div><br></div><ol><li>sdgsgs</li><li>sdfgfsg&nbsp;</li><li>sdfsg4tdfg</li><li>44ewtdrg</li><li>df1ad</li></ol><div><br></div><div><br></div>', '2025-08-04 01:50:05', '2025-08-04 15:13:15', 1, '2025-08-04 15:13:15', 'dream'),
(8, '2H4G5NeD', 1, 'Ggg', '<div>kbwj&nbsp;<strong>ssrgerkk</strong></div>', '2025-08-04 05:25:09', '2025-08-04 15:13:07', 1, '2025-08-04 15:13:07', 'dream'),
(9, 'SAAqjGuw', 1, 'n', '', '2025-08-04 08:19:23', '2025-08-04 13:16:18', 0, '2025-08-04 13:16:18', 'dream'),
(10, 'zXOqWStY', 1, 'n 1', '<div>2q</div>', '2025-08-04 08:19:28', '2025-08-04 13:16:40', 1, '2025-08-04 13:16:40', 'dream'),
(11, 'dN1O57Hn', 1, 'Toyota Land crusier trail blaster', '<div>Go on a trip with the new Toyota Land Crusier.<br>Make it a story to show case the car, the usage, everyday plus\'s, how to.<br><br>Going around Europa and down to Africa.<br>Along the way invite friends to join the trip for X days and fly them out and home.<br><br>Get their stories, there take on the experince with the car.<br><br></div>', '2025-08-04 14:12:04', '2025-08-08 12:59:02', 0, NULL, 'dream'),
(12, 'bpcub2fb', 1, 'Amazon Rainforest filming', '<div>Coming back to the Amazon Rainforest to tell the story of change.<br>Change in the nature because of man-made or evalution.<br><br></div><ul><li>What was</li><li>What is left</li><li>What to come</li></ul><div><br></div>', '2025-08-04 15:05:49', '2025-08-04 15:59:20', 0, NULL, 'dream'),
(13, 'J0Wb49gw', 1, 'Journey Back To Brazil', '<div>Doing some sort of filming of the going back to Brazil in a fun way to show that you are in Denmark and now you are in Brazil and the steps to get there<br><br>Maybe make many different stories filmed about it.</div>', '2025-08-04 15:12:40', '2025-08-05 03:55:58', 0, NULL, 'dream'),
(14, 'X6tH7V3D', 1, 'Journey Back To Brazil', '<div>Doing some sort of filming of the going back to Brazil in a fun way to show that you are in Denmark and now you are in Brazil and the steps to get there</div>', '2025-08-05 01:55:49', '2025-08-05 02:56:57', 0, '2025-08-05 02:56:57', 'dream'),
(15, 'IyafWynl', 1, 'Journey Back To Brazil cop', '<div>Doing some sort of filming of the going back to Brazil in a fun way to show that you are in Denmark and now you are in Brazil and the steps to get there</div>', '2025-08-05 01:59:16', '2025-08-05 02:57:19', 1, '2025-08-05 02:57:19', 'dream'),
(16, 'xbc7nLho', 1, 'new dream', '<div>Brand new dream</div>', '2025-08-05 02:33:21', '2025-08-05 02:34:00', 0, '2025-08-05 02:34:00', 'dream'),
(17, 'Q47LcOc7', 1, 'new dream', '<div>Brand new dream</div>', '2025-08-05 02:33:22', '2025-08-05 02:33:55', 0, '2025-08-05 02:33:55', 'dream'),
(18, 'NgZXD4da', 1, 'new 2', '<div>new 2</div>', '2025-08-05 02:34:29', '2025-08-05 03:48:52', 1, NULL, 'dream'),
(19, 'xfc5NCbn', 1, 'new 2', '<div>new 2</div>', '2025-08-05 02:34:29', '2025-08-05 02:56:40', 0, '2025-08-05 02:56:40', 'dream'),
(20, 'ITAcqqdB', 1, 'new 3', '<div>new 3</div>', '2025-08-05 02:35:02', '2025-08-05 02:56:34', 0, '2025-08-05 02:56:34', 'dream'),
(21, 'HWXS3UII', 1, 'new 3', '<div>new 3</div>', '2025-08-05 02:35:03', '2025-08-05 02:56:29', 0, '2025-08-05 02:56:29', 'dream'),
(22, 'wLvAZ0YI', 1, 'new 4', '<div>new 4 wefsdvs ¨<br><br>sd f<br><br>s f<br><br></div>', '2025-08-05 02:38:58', '2025-08-05 03:48:50', 1, NULL, 'dream'),
(23, 'VU6zenRA', 1, 'Offline dream', '<div>Yes this was created offline</div>', '2025-08-05 03:57:14', '2025-08-08 15:57:53', 1, NULL, 'dream'),
(24, 'Lc3j9DpK', 1, 'T1', '', '2025-08-05 04:21:44', '2025-08-05 07:31:52', 0, '2025-08-05 07:31:52', 'dream'),
(25, 'fHRmIDg6', 1, 'Off t', '', '2025-08-05 05:08:20', '2025-08-05 05:08:28', 0, '2025-08-05 05:08:28', 'dream'),
(26, 'CsXdWzZr', 1, 'Off 5', '', '2025-08-05 06:02:28', '2025-08-05 07:31:49', 0, '2025-08-05 07:31:49', 'dream'),
(27, 'a6N9jlL0', 1, 'Off 6', '', '2025-08-05 07:31:25', '2025-08-05 07:31:44', 0, '2025-08-05 07:31:44', 'dream'),
(28, 'IPJEAldZ', 1, 'Off 6', '', '2025-08-05 07:33:44', '2025-08-05 07:33:50', 0, '2025-08-05 07:33:50', 'dream'),
(29, 'IgaxI3Bz', 1, 'off 7', '7', '2025-08-05 14:50:49', '2025-08-05 14:59:02', 0, '2025-08-05 14:59:02', 'dream'),
(30, 'bw5s53Hi', 1, 'off 8', '8', '2025-08-05 15:00:45', '2025-08-05 15:08:41', 0, '2025-08-05 15:08:41', 'dream'),
(31, 'j0Uo9FmL', 1, 'off 9', '9', '2025-08-05 15:06:15', '2025-08-05 15:08:35', 0, '2025-08-05 15:08:35', 'dream'),
(32, 'InQbgi0P', 1, 'off 10', '10', '2025-08-05 15:08:02', '2025-08-05 15:08:30', 0, '2025-08-05 15:08:30', 'dream'),
(33, 'jcuHYqTf', 1, 'off 1', '<div>1</div>', '2025-08-05 15:09:22', '2025-08-05 15:09:34', 0, '2025-08-05 15:09:34', 'dream'),
(34, 'vJUiSUg3', 1, 'off 1', '1', '2025-08-05 15:11:28', '2025-08-08 15:57:50', 1, NULL, 'dream'),
(35, 'U4avwcRe', 1, 'off 2', '<div>2</div>', '2025-08-05 15:11:29', '2025-08-08 15:57:47', 1, NULL, 'dream'),
(36, 'DXBs2kxB', 1, 'test', '', '2025-08-07 00:13:26', '2025-08-08 15:57:44', 1, NULL, 'dream'),
(37, 'AXZ2Sf9Y', 1, 'test', '', '2025-08-07 00:16:03', '2025-08-08 15:57:42', 1, NULL, 'dream'),
(38, '6JAhgScC', 1, 'tesd', '', '2025-08-07 00:19:31', '2025-08-08 15:57:39', 1, NULL, 'dream'),
(39, 'eBBz4vJF', 1, 'ef', '', '2025-08-07 00:37:52', '2025-08-08 15:57:37', 1, NULL, 'dream'),
(40, 'IhSiCiiC', 1, 'ef', '', '2025-08-07 00:38:07', '2025-08-08 15:57:33', 1, NULL, 'dream'),
(41, 'yMms0fLy', 1, 'sdf', 's sdsdf s', '2025-08-07 00:41:40', '2025-08-08 15:57:31', 1, NULL, 'dream'),
(42, '2sw1YVKA', 1, 'wewewef', 'sdsdf fdhjxj', '2025-08-07 00:43:23', '2025-08-07 00:48:58', 1, '2025-08-07 00:48:58', 'dream'),
(43, 'XpH51yEu', 1, 'Mobile', 'Mobile beskrivelse', '2025-08-07 00:51:38', '2025-08-08 15:57:30', 1, NULL, 'dream'),
(44, 'jfrydvtH', 1, 'sdg', '', '2025-08-07 01:17:29', '2025-08-08 15:57:28', 1, NULL, 'dream'),
(45, 'Ye6vmr79', 1, 'Off 8', '', '2025-08-07 01:18:21', '2025-08-08 15:57:26', 1, NULL, 'dream'),
(46, 'I4ghhfU9', 1, 'Off 9 ppj', '9', '2025-08-07 02:44:06', '2025-08-08 15:57:22', 1, NULL, 'dream'),
(47, 'Q0BpT7aX', 1, 'Off 10 pp', '10\r\nFgkkf\r\n\r\nFuguujjd', '2025-08-07 02:44:06', '2025-08-08 08:08:35', 1, NULL, 'dream'),
(48, 'hvrMBZBV', 1, 'Off 11', '11', '2025-08-07 03:17:11', '2025-08-08 08:08:32', 1, NULL, 'dream'),
(49, 'Ml2VKzqz', 1, 'Off 12', '12', '2025-08-07 03:17:11', '2025-08-08 08:08:24', 1, NULL, 'dream'),
(50, 'x7zqlhAZ', 1, 'Off 6', '6', '2025-08-07 03:18:13', '2025-08-07 03:18:46', 0, '2025-08-07 03:18:46', 'dream'),
(51, 'pF2rrQW6', 1, 'Off 6', '6', '2025-08-07 03:18:13', '2025-08-07 03:18:41', 0, '2025-08-07 03:18:41', 'dream'),
(52, 'NS10UyE4', 1, 'Off 6', '6', '2025-08-07 03:18:13', '2025-08-07 03:18:38', 0, '2025-08-07 03:18:38', 'dream'),
(53, 'YUXKmnvf', 1, 'Off 14', '14', '2025-08-07 03:19:59', '2025-08-08 08:06:01', 1, NULL, 'dream'),
(54, 'IWyi6XYD', 1, 'aasdfsdfwewwwwwww 11', 'sdfaaae 11', '2025-08-08 15:50:11', '2025-08-08 15:51:47', 0, NULL, 'dream'),
(55, 'n2hZVdZa', 1, 'Night offline', 'Jjj', '2025-08-08 15:59:41', '2025-08-09 13:25:57', 1, NULL, 'dream'),
(56, 'JWUMAxLt', 1, 'On new trips, interview people', 'Going anywhere on a trip, just to eat or drink.\r\nInterview people who are maybe selling or cooking.\r\nIt could be the tour guide, the vaiter, sales person, Uber driver, motorbike driver.\r\nRandom people on the street', '2025-08-09 14:11:35', '2025-08-10 12:59:48', 0, NULL, 'dream'),
(57, '68zKHrRD', 1, 'Hhhhh', '', '2025-08-10 06:11:47', '2025-08-10 06:11:47', 0, NULL, 'dream');

--
-- Dumping data for table `dream_brands`
--

INSERT INTO `dream_brands` (`id`, `dream_id`, `brand`) VALUES
(41, 6, 'toyota'),
(42, 6, 'Canon'),
(43, 8, 'Nike'),
(44, 7, 'wesvd'),
(45, 7, '333'),
(46, 9, 'canon'),
(49, 10, 'canon'),
(57, 16, 'bad'),
(58, 17, 'bad'),
(59, 20, '3'),
(60, 21, '3'),
(62, 22, '4'),
(66, 18, 'Bjfb'),
(67, 13, 'KLM'),
(71, 43, 'Goog'),
(77, 53, 'sfwsf'),
(78, 11, 'Toyota');

--
-- Dumping data for table `dream_locations`
--

INSERT INTO `dream_locations` (`id`, `dream_id`, `location`) VALUES
(64, 6, 'Norway'),
(65, 6, 'sweden'),
(66, 6, 'Denmark'),
(67, 6, 'aasd'),
(68, 6, 'Jhddjjd'),
(71, 8, 'Hvjk'),
(72, 7, 'sdg'),
(73, 7, '3ssg'),
(90, 12, 'Amazon'),
(95, 14, 'Denmark'),
(96, 14, 'Brazil'),
(101, 15, 'Denmark'),
(102, 15, 'Brazil'),
(105, 16, 'bed'),
(106, 17, 'bed'),
(108, 19, 'new 2'),
(109, 20, 'new 3'),
(110, 21, 'new 3'),
(112, 22, 'new 4'),
(113, 22, 'sd'),
(120, 18, 'new 2'),
(121, 18, 'H'),
(122, 13, 'Denmark'),
(123, 13, 'Brazil'),
(126, 33, 'loc 1'),
(127, 41, 'DK'),
(128, 41, 'BR'),
(131, 43, 'Dk'),
(145, 53, 'qerwe'),
(146, 53, 'sdaf2'),
(147, 53, 'asdf'),
(148, 11, 'Europa'),
(149, 11, 'Africa'),
(150, 54, '1'),
(151, 54, '1'),
(152, 55, 'd'),
(159, 56, 'anywhere');

--
-- Dumping data for table `dream_people`
--

INSERT INTO `dream_people` (`id`, `dream_id`, `person`) VALUES
(22, 6, 'marry'),
(23, 7, 'gr'),
(25, 10, 'matt'),
(26, 20, 'new 3'),
(27, 21, 'new 3'),
(29, 22, '4'),
(31, 43, 'Selv'),
(37, 53, 'sdsf'),
(44, 56, 'everyone');

--
-- Dumping data for table `dream_seasons`
--

INSERT INTO `dream_seasons` (`id`, `dream_id`, `season`) VALUES
(21, 6, 'vinter'),
(23, 8, 'Summer'),
(24, 7, 'summer'),
(25, 7, 'winter'),
(31, 20, '3'),
(32, 21, '3'),
(34, 22, '4'),
(36, 41, 'summer'),
(40, 43, 'Summer'),
(41, 43, 'Vinter'),
(52, 53, 'sdsf'),
(53, 53, 'wseefwsf'),
(54, 11, 'Summer'),
(56, 54, 'waeaefae'),
(63, 56, 'any');

--
-- Dumping data for table `visions`
--

INSERT INTO `visions` (`id`, `user_id`, `slug`, `title`, `description`, `created_at`, `updated_at`, `archived`, `deleted_at`) VALUES
(1, 1, '7YWBOANp', 'vision 1', '', '2025-08-09 13:41:26', NULL, 0, NULL),
(2, 1, 'nJt19DHI', '', '', '2025-08-10 02:58:48', NULL, 0, NULL),
(3, 1, 'Mp5ETYWV', 'Vision 2', '', '2025-08-10 03:49:58', NULL, 0, NULL),
(4, 1, 'thReHSZP', 'faese', '<div>seffe</div>', '2025-08-10 05:11:44', NULL, 0, NULL),
(5, 1, '0LgfIZYl', 'S', '', '2025-08-10 05:13:11', NULL, 0, NULL),
(6, 1, 'DlZmcRPW', 'df', '', '2025-08-10 05:41:00', NULL, 0, NULL),
(7, 1, '2fr9sw5i', 'Fh gt', '', '2025-08-10 10:18:53', NULL, 0, NULL),
(8, 1, '4RZvHOs3', 'Fh gt', '', '2025-08-10 10:20:11', NULL, 0, NULL);

--
-- Dumping data for table `vision_anchors`
--

INSERT INTO `vision_anchors` (`id`, `board_id`, `key`, `value`) VALUES
(1, 1, 'key', 'value'),
(2, 1, 'a', '1'),
(3, 1, 'b', '2'),
(4, 3, 'locations', 'Brazil'),
(5, 7, 'brands', 'Jui'),
(6, 8, 'brands', 'Jui');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
