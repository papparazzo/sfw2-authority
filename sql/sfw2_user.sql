
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

CREATE TABLE `sfw2_user` (
  `Id` int(11) UNSIGNED NOT NULL,
  `Active` enum('0','1') COLLATE utf8_unicode_ci NOT NULL DEFAULT '1',
  `Admin` enum('0','1') COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `FirstName` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `LastName` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `Sex` enum('FEMALE','MALE','UNKNOWN') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'UNKNOWN',
  `Birthday` date DEFAULT NULL,
  `Email` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `Phone1` varchar(25) COLLATE utf8_unicode_ci NOT NULL,
  `Phone2` varchar(25) COLLATE utf8_unicode_ci NOT NULL,
  `LoginName` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `Password` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `Retries` tinyint(11) NOT NULL,
  `LastTry` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `ResetExpireDate` datetime NOT NULL,
  `ResetHash` varchar(32) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

ALTER TABLE `sfw2_user`
  ADD PRIMARY KEY (`Id`);

ALTER TABLE `sfw2_user`
  MODIFY `Id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
