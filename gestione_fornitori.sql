-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Creato il: Mar 03, 2026 alle 20:06
-- Versione del server: 10.11.14-MariaDB-0ubuntu0.24.04.1
-- Versione PHP: 8.3.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `gestione_fornitori`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `Account`
--

CREATE TABLE `Account` (
  `aid` int(11) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `ruolo` enum('FORNITORE','ADMIN') NOT NULL,
  `fid` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `Account`
--

INSERT INTO `Account` (`aid`, `email`, `password_hash`, `ruolo`, `fid`) VALUES
(1, 'ACME@mail.com', '$2y$10$EZ8StDKjHw0b9uamwtHPLegb.UUJ.nXpDGNeeLSgrozHWEG6OkS.a', 'FORNITORE', 1),
(3, 'admin@admin.com', '$2y$10$bOSXaSTZZr.gbzShWlojG..BsUQbBH80H4aUmi/dkodE6hM8kA2Eq', 'ADMIN', NULL);

-- --------------------------------------------------------

--
-- Struttura della tabella `AccountSession`
--

CREATE TABLE `AccountSession` (
  `sid` bigint(20) NOT NULL,
  `aid` int(11) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `revoked_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `AccountSession`
--

INSERT INTO `AccountSession` (`sid`, `aid`, `token_hash`, `expires_at`, `revoked_at`, `created_at`) VALUES
(1, 1, '9a3b3c1a5b07b188b9cac0e388b99ff24068a3e60745beea089ea5a03f612873', '2026-03-02 16:43:20', NULL, '2026-03-02 08:43:20'),
(2, 1, 'af64b189a2138a75118ad839874bb2ca010fc61aebfc87927e2ed02fe91866d4', '2026-03-03 23:42:53', NULL, '2026-03-03 15:42:53'),
(3, 1, '2175f76d5a72695f9ce62a495631a195807efdc175cd591d94947fb80c1a31c1', '2026-03-03 23:43:40', NULL, '2026-03-03 15:43:40'),
(4, 1, '938a1f4a1ca0d2fc5b439fa50bb9c92010815d710f9b1350ee2f83ac0ac0bc69', '2026-03-03 23:48:14', NULL, '2026-03-03 15:48:14'),
(5, 1, '453a08d6f86d16c8d3a8a27dffb66b4db734f68353ca208ab8961cc3a11b2a37', '2026-03-03 23:49:50', '2026-03-03 16:29:16', '2026-03-03 15:49:50'),
(6, 1, '811c878fdc3ce3a19ee9198c122e608411bc02e0a4450fd74d7e92e1cc73460f', '2026-03-04 00:33:14', '2026-03-03 16:33:21', '2026-03-03 16:33:14'),
(7, 1, 'e7e92a4a3333147462620c33e2ccc1ccda476f653d7e9ad6557b835db51fc6c0', '2026-03-04 00:34:09', '2026-03-03 16:34:55', '2026-03-03 16:34:09'),
(8, 1, '8365599da515eefc2e2674a29f240ddde931f2ded372303b396b8496c066d12e', '2026-03-04 00:36:16', '2026-03-03 16:36:20', '2026-03-03 16:36:16'),
(9, 1, '03659d0c07df16f9d70bde87f1f30e7333d889781bae5d8f0fde4529dbdb321d', '2026-03-04 00:37:28', '2026-03-03 16:37:57', '2026-03-03 16:37:28'),
(12, 3, 'b9e84f9387d0ee38233c5562224f8fc96f00efe42536b0b9889e923cb5c510ba', '2026-03-04 00:42:52', NULL, '2026-03-03 16:42:52'),
(13, 3, '8bb5c012da5350d4e928e3001cd5bd668ef733be53fae1923914784586bc8da7', '2026-03-04 00:43:28', '2026-03-03 17:46:50', '2026-03-03 16:43:28'),
(14, 1, '9974c19aa1017e1b500ed318f629cfca08f31884ca764679844569b1b36cf075', '2026-03-04 01:47:01', '2026-03-03 17:47:23', '2026-03-03 17:47:01'),
(15, 3, '192b375c3b006036ea2a596e10863cdb0330db966f28734e885f2bbc39ea0a26', '2026-03-04 01:47:36', '2026-03-03 18:33:54', '2026-03-03 17:47:36'),
(17, 3, '345df4ea386cb0ee6030c5ef15a003b1df9592ae6a268846ce9e4aa13f1f6adf', '2026-03-04 02:29:53', NULL, '2026-03-03 18:29:53'),
(18, 3, 'f0983dabbbe8f5f67604e469a2f46d828f9961ffd2114dcce20f3e4357a3597b', '2026-03-04 02:30:07', NULL, '2026-03-03 18:30:07'),
(19, 3, '638ca621e83046f2a66f6c1f4b113dcf2361260c18c46a2449da3e7262753433', '2026-03-04 02:31:32', '2026-03-03 18:31:42', '2026-03-03 18:31:32'),
(20, 3, '26efc45555fc1fa627792e992847495b30314e94fec4a5818dac2fa1bffe174a', '2026-03-04 02:31:49', NULL, '2026-03-03 18:31:49'),
(21, 3, 'e4b3d2e0ad0994d5270121897c1f8058896ff9571c404b07ae86c77ba6262910', '2026-03-04 02:34:01', '2026-03-03 20:03:03', '2026-03-03 18:34:01'),
(23, 1, 'c55e6e58cf3ba4c634b893af0e2c526f88c154729bab7a7a5a9fdd3eaf2dc894', '2026-03-04 03:34:26', NULL, '2026-03-03 19:34:26');

-- --------------------------------------------------------

--
-- Struttura della tabella `Catalogo`
--

CREATE TABLE `Catalogo` (
  `fid` int(11) NOT NULL,
  `pid` int(11) NOT NULL,
  `costo` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `Catalogo`
--

INSERT INTO `Catalogo` (`fid`, `pid`, `costo`) VALUES
(1, 3, 12.19),
(1, 4, 12.92),
(1, 5, 13.65),
(1, 6, 14.38),
(1, 7, 15.11),
(1, 8, 15.84),
(1, 9, 16.57),
(1, 10, 17.30),
(1, 11, 18.03),
(1, 12, 18.76),
(1, 13, 19.49),
(1, 14, 20.22),
(1, 15, 20.95),
(1, 16, 21.68),
(1, 17, 22.41),
(1, 18, 23.14),
(1, 19, 23.87),
(1, 20, 24.60),
(1, 21, 25.33),
(1, 22, 26.06),
(1, 23, 26.79),
(1, 24, 27.52),
(1, 25, 28.25),
(1, 26, 28.98),
(1, 27, 29.71),
(1, 28, 30.44),
(1, 29, 31.17),
(1, 30, 31.90),
(1, 31, 32.63),
(1, 32, 33.36),
(1, 33, 34.09),
(1, 34, 34.82),
(1, 35, 35.55),
(1, 36, 36.28),
(35, 3, 12.77),
(35, 4, 13.36),
(35, 5, 13.95),
(35, 6, 14.54),
(35, 7, 15.13),
(35, 8, 15.72),
(35, 9, 16.31),
(35, 10, 16.90),
(35, 11, 17.49),
(35, 12, 18.08),
(35, 13, 18.67),
(35, 14, 19.26),
(35, 15, 19.85),
(35, 16, 20.44),
(35, 17, 21.03),
(35, 18, 21.62),
(35, 19, 22.21),
(35, 20, 22.80),
(35, 21, 23.39),
(35, 22, 23.98),
(35, 23, 24.57),
(35, 24, 25.16),
(35, 25, 25.75),
(35, 26, 26.34),
(36, 3, 9.77),
(36, 4, 10.36),
(36, 5, 10.95),
(36, 6, 11.54),
(36, 7, 12.13),
(36, 8, 12.72),
(36, 9, 13.31),
(36, 10, 13.90),
(36, 11, 14.49),
(36, 12, 15.08),
(36, 13, 15.67),
(36, 14, 16.26),
(36, 15, 16.85),
(36, 16, 17.44),
(36, 17, 18.03),
(36, 18, 18.62),
(36, 19, 19.21),
(36, 20, 19.80),
(36, 21, 20.39),
(36, 22, 20.98),
(36, 23, 21.57),
(36, 24, 22.16),
(36, 25, 22.75),
(36, 26, 23.34),
(37, 3, 10.77),
(37, 4, 11.36),
(37, 5, 11.95),
(37, 6, 12.54),
(37, 7, 13.13),
(37, 8, 13.72),
(37, 9, 14.31),
(37, 10, 14.90),
(37, 11, 15.49),
(37, 12, 16.08),
(37, 13, 16.67),
(37, 14, 17.26),
(37, 15, 17.85),
(37, 16, 18.44),
(37, 17, 19.03),
(37, 18, 19.62),
(37, 19, 20.21),
(37, 20, 20.80),
(37, 21, 21.39),
(37, 22, 21.98),
(37, 23, 22.57),
(37, 24, 23.16),
(37, 25, 23.75),
(37, 26, 24.34),
(38, 3, 11.77),
(38, 4, 12.36),
(38, 5, 12.95),
(38, 6, 13.54),
(38, 7, 14.13),
(38, 8, 14.72),
(38, 9, 15.31),
(38, 10, 15.90),
(38, 11, 16.49),
(38, 12, 17.08),
(38, 13, 17.67),
(38, 14, 18.26),
(38, 15, 18.85),
(38, 16, 19.44),
(38, 17, 20.03),
(38, 18, 20.62),
(38, 19, 21.21),
(38, 20, 21.80),
(38, 21, 22.39),
(38, 22, 22.98),
(38, 23, 23.57),
(38, 24, 24.16),
(38, 25, 24.75),
(38, 26, 25.34),
(39, 3, 12.77),
(39, 4, 13.36),
(39, 5, 13.95),
(39, 6, 14.54),
(39, 7, 15.13),
(39, 8, 15.72),
(39, 9, 16.31),
(39, 10, 16.90),
(39, 11, 17.49),
(39, 12, 18.08),
(39, 13, 18.67),
(39, 14, 19.26),
(39, 15, 19.85),
(39, 16, 20.44),
(39, 17, 21.03),
(39, 18, 21.62),
(39, 19, 22.21),
(39, 20, 22.80),
(39, 21, 23.39),
(39, 22, 23.98),
(39, 23, 24.57),
(39, 24, 25.16),
(39, 25, 25.75),
(39, 26, 26.34),
(40, 3, 9.77),
(40, 4, 10.36),
(40, 5, 10.95),
(40, 6, 11.54),
(40, 7, 12.13),
(40, 8, 12.72),
(40, 9, 13.31),
(40, 10, 13.90),
(40, 11, 14.49),
(40, 12, 15.08),
(40, 13, 15.67),
(40, 14, 16.26),
(40, 15, 16.85),
(40, 16, 17.44),
(40, 17, 18.03),
(40, 18, 18.62),
(40, 19, 19.21),
(40, 20, 19.80),
(40, 21, 20.39),
(40, 22, 20.98),
(40, 23, 21.57),
(40, 24, 22.16),
(40, 25, 22.75),
(40, 26, 23.34),
(41, 3, 10.77),
(41, 4, 11.36),
(41, 5, 11.95),
(41, 6, 12.54),
(41, 7, 13.13),
(41, 8, 13.72),
(41, 9, 14.31),
(41, 10, 14.90),
(41, 11, 15.49),
(41, 12, 16.08),
(41, 13, 16.67),
(41, 14, 17.26),
(41, 15, 17.85),
(41, 16, 18.44),
(41, 17, 19.03),
(41, 18, 19.62),
(41, 19, 20.21),
(41, 20, 20.80),
(41, 21, 21.39),
(41, 22, 21.98),
(41, 23, 22.57),
(41, 24, 23.16),
(41, 25, 23.75),
(41, 26, 24.34),
(42, 3, 11.77),
(42, 4, 12.36),
(42, 5, 12.95),
(42, 6, 13.54),
(42, 7, 14.13),
(42, 8, 14.72),
(42, 9, 15.31),
(42, 10, 15.90),
(42, 11, 16.49),
(42, 12, 17.08),
(42, 13, 17.67),
(42, 14, 18.26),
(42, 15, 18.85),
(42, 16, 19.44),
(42, 17, 20.03),
(42, 18, 20.62),
(42, 19, 21.21),
(42, 20, 21.80),
(42, 21, 22.39),
(42, 22, 22.98),
(42, 23, 23.57),
(42, 24, 24.16),
(42, 25, 24.75),
(42, 26, 25.34),
(43, 3, 12.77),
(43, 4, 13.36),
(43, 5, 13.95),
(43, 6, 14.54),
(43, 7, 15.13),
(43, 8, 15.72),
(43, 9, 16.31),
(43, 10, 16.90),
(43, 11, 17.49),
(43, 12, 18.08),
(43, 13, 18.67),
(43, 14, 19.26),
(43, 15, 19.85),
(43, 16, 20.44),
(43, 17, 21.03),
(43, 18, 21.62),
(43, 19, 22.21),
(43, 20, 22.80),
(43, 21, 23.39),
(43, 22, 23.98),
(43, 23, 24.57),
(43, 24, 25.16),
(43, 25, 25.75),
(43, 26, 26.34),
(44, 3, 9.77),
(44, 4, 10.36),
(44, 5, 10.95),
(44, 6, 11.54),
(44, 7, 12.13),
(44, 8, 12.72),
(44, 9, 13.31),
(44, 10, 13.90),
(44, 11, 14.49),
(44, 12, 15.08),
(44, 13, 15.67),
(44, 14, 16.26),
(44, 15, 16.85),
(44, 16, 17.44),
(44, 17, 18.03),
(44, 18, 18.62),
(44, 19, 19.21),
(44, 20, 19.80),
(44, 21, 20.39),
(44, 22, 20.98),
(44, 23, 21.57),
(44, 24, 22.16),
(44, 25, 22.75),
(44, 26, 23.34),
(45, 3, 10.77),
(45, 4, 11.36),
(45, 5, 11.95),
(45, 6, 12.54),
(45, 7, 13.13),
(45, 8, 13.72),
(45, 9, 14.31),
(45, 10, 14.90),
(45, 11, 15.49),
(45, 12, 16.08),
(45, 13, 16.67),
(45, 14, 17.26),
(45, 15, 17.85),
(45, 16, 18.44),
(45, 17, 19.03),
(45, 18, 19.62),
(45, 19, 20.21),
(45, 20, 20.80),
(45, 21, 21.39),
(45, 22, 21.98),
(45, 23, 22.57),
(45, 24, 23.16),
(45, 25, 23.75),
(45, 26, 24.34),
(46, 3, 11.77),
(46, 4, 12.36),
(46, 5, 12.95),
(46, 6, 13.54),
(46, 7, 14.13),
(46, 8, 14.72),
(46, 9, 15.31),
(46, 10, 15.90),
(46, 11, 16.49),
(46, 12, 17.08),
(46, 13, 17.67),
(46, 14, 18.26),
(46, 15, 18.85),
(46, 16, 19.44),
(46, 17, 20.03),
(46, 18, 20.62),
(46, 19, 21.21),
(46, 20, 21.80),
(46, 21, 22.39),
(46, 22, 22.98),
(46, 23, 23.57),
(46, 24, 24.16),
(46, 25, 24.75),
(46, 26, 25.34),
(47, 3, 10.71),
(47, 4, 11.28),
(47, 5, 11.85),
(47, 6, 12.42),
(47, 7, 12.99),
(47, 8, 13.56),
(47, 9, 14.13),
(47, 10, 14.70),
(47, 11, 15.27),
(47, 12, 15.84),
(47, 13, 16.41),
(47, 14, 16.98),
(47, 15, 17.55),
(47, 16, 18.12),
(47, 17, 18.69),
(47, 18, 19.26),
(47, 19, 19.83),
(47, 20, 20.40),
(48, 3, 11.71),
(48, 4, 12.28),
(48, 5, 12.85),
(48, 6, 13.42),
(48, 7, 13.99),
(48, 8, 14.56),
(48, 9, 15.13),
(48, 10, 15.70),
(48, 11, 16.27),
(48, 12, 16.84),
(48, 13, 17.41),
(48, 14, 17.98),
(48, 15, 18.55),
(48, 16, 19.12),
(48, 17, 19.69),
(48, 18, 20.26),
(48, 19, 20.83),
(48, 20, 21.40),
(49, 15, 14.10),
(49, 16, 14.64),
(49, 17, 15.18),
(49, 18, 15.72),
(49, 19, 16.26),
(49, 20, 16.80),
(50, 3, 8.71),
(50, 4, 9.28),
(50, 5, 9.85),
(50, 6, 10.42),
(50, 7, 10.99),
(50, 8, 11.56),
(50, 9, 12.13),
(50, 10, 12.70),
(50, 11, 13.27),
(50, 12, 13.84),
(50, 13, 14.41),
(50, 14, 14.98),
(50, 15, 15.55),
(50, 16, 16.12),
(50, 17, 16.69),
(50, 18, 17.26),
(50, 19, 17.83),
(50, 20, 18.40),
(51, 3, 10.86),
(51, 4, 11.48),
(51, 5, 12.10),
(51, 6, 12.72),
(51, 7, 13.34),
(51, 8, 13.96),
(51, 9, 14.58),
(51, 10, 15.20),
(51, 11, 15.82),
(51, 12, 16.44),
(51, 13, 17.06),
(51, 14, 17.68),
(52, 3, 11.86),
(52, 4, 12.48),
(52, 5, 13.10),
(52, 6, 13.72),
(52, 7, 14.34),
(52, 8, 14.96),
(52, 9, 15.58),
(52, 10, 16.20),
(52, 11, 16.82),
(52, 12, 17.44),
(52, 13, 18.06),
(52, 14, 18.68),
(53, 3, 11.71),
(53, 4, 12.28),
(53, 5, 12.85),
(53, 6, 13.42),
(53, 7, 13.99),
(53, 8, 14.56),
(53, 9, 15.13),
(53, 10, 15.70),
(53, 11, 16.27),
(53, 12, 16.84),
(53, 13, 17.41),
(53, 14, 17.98),
(53, 15, 18.55),
(53, 16, 19.12),
(53, 17, 19.69),
(53, 18, 20.26),
(53, 19, 20.83),
(53, 20, 21.40);

-- --------------------------------------------------------

--
-- Struttura della tabella `Fornitori`
--

CREATE TABLE `Fornitori` (
  `fid` int(11) NOT NULL,
  `fnome` varchar(100) NOT NULL,
  `indirizzo` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `Fornitori`
--

INSERT INTO `Fornitori` (`fid`, `fnome`, `indirizzo`) VALUES
(1, 'ACME', 'Via Roma 10, Milano'),
(35, 'Officine Lombarde', 'Via Breda 24, Milano'),
(36, 'Meccanica Veneta', 'Via dell Industria 18, Padova'),
(37, 'Tecnoforge Italia', 'Via Galileo 52, Torino'),
(38, 'Componenti Emiliani', 'Via Emilia Ovest 110, Modena'),
(39, 'Saldature Toscane', 'Via Pratese 9, Firenze'),
(40, 'Ferramenta Adriatica', 'Via del Porto 41, Ancona'),
(41, 'Bulloneria Partenopea', 'Via Vespucci 13, Napoli'),
(42, 'Ricambi Liguri', 'Via XX Settembre 77, Genova'),
(43, 'Industria Umbra Pezzi', 'Via dei Mestieri 5, Perugia'),
(44, 'Acciai e Viti Roma', 'Via Tiburtina 312, Roma'),
(45, 'Forniture Alpine', 'Via Brennero 66, Bolzano'),
(46, 'ColorMet Srl', 'Via dei Verniciatori 20, Bologna'),
(47, 'Precisione Padana', 'Via Po 8, Parma'),
(48, 'Pezzi e Componenti Sud', 'Via Marina 44, Bari'),
(49, 'Nautica Ferri Srl', 'Via del Molo 2, Livorno'),
(50, 'GreenMetal Components', 'Via delle Querce 14, Trento'),
(51, 'RossoFerro Spa', 'Via del Maglio 3, Brescia'),
(52, 'Viteria Centrale', 'Via Cavour 91, Verona'),
(53, 'Linea Bulloni Italia', 'Via Manzoni 27, Reggio Emilia');

-- --------------------------------------------------------

--
-- Struttura della tabella `Pezzi`
--

CREATE TABLE `Pezzi` (
  `pid` int(11) NOT NULL,
  `pnome` varchar(100) NOT NULL,
  `colore` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `Pezzi`
--

INSERT INTO `Pezzi` (`pid`, `pnome`, `colore`) VALUES
(3, 'Bullone M8 zincato', 'Rosso'),
(4, 'Vite testa esagonale M10', 'Rosso'),
(5, 'Rondella larga inox 12mm', 'Rosso'),
(6, 'Staffa angolare 40x40', 'Rosso'),
(7, 'Piastra di fissaggio 80x50', 'Rosso'),
(8, 'Tappo filettato 1/2', 'Rosso'),
(9, 'Giunto rapido 12mm', 'Rosso'),
(10, 'Dado autobloccante M12', 'Rosso'),
(11, 'Distanziale alluminio 20mm', 'Rosso'),
(12, 'Fascetta metallica 25mm', 'Rosso'),
(13, 'Boccola bronzo 18mm', 'Rosso'),
(14, 'Perno temprato 6x40', 'Rosso'),
(15, 'Bullone M6 zincato', 'Verde'),
(16, 'Vite torx T25', 'Verde'),
(17, 'Rondella elastica 10mm', 'Verde'),
(18, 'Piastrina di rinforzo 30x60', 'Verde'),
(19, 'Raccordo a gomito 3/8', 'Verde'),
(20, 'Valvola a sfera mini 1/4', 'Verde'),
(21, 'Guarnizione NBR 22mm', 'Nero'),
(22, 'Anello seeger interno 28', 'Nero'),
(23, 'Copiglia inox 3mm', 'Blu'),
(24, 'Rivetto cieco 4x12', 'Blu'),
(25, 'Tubo distanziale 15mm', 'Giallo'),
(26, 'Molla compressione 35mm', 'Giallo'),
(27, 'Kit fissaggio motore A1', 'Nero'),
(28, 'Kit fissaggio motore A2', 'Nero'),
(29, 'Kit fissaggio motore A3', 'Nero'),
(30, 'Kit fissaggio motore A4', 'Nero'),
(31, 'Kit fissaggio motore A5', 'Nero'),
(32, 'Supporto antivibrante H1', 'Blu'),
(33, 'Supporto antivibrante H2', 'Blu'),
(34, 'Supporto antivibrante H3', 'Blu'),
(35, 'Flangia speciale X1', 'Giallo'),
(36, 'Flangia speciale X2', 'Giallo'),
(66, 'console.log(\"matita\")', 'nero');

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `Account`
--
ALTER TABLE `Account`
  ADD PRIMARY KEY (`aid`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_account_fornitore` (`fid`);

--
-- Indici per le tabelle `AccountSession`
--
ALTER TABLE `AccountSession`
  ADD PRIMARY KEY (`sid`),
  ADD UNIQUE KEY `token_hash` (`token_hash`),
  ADD KEY `idx_account_session_aid` (`aid`),
  ADD KEY `idx_account_session_expires` (`expires_at`);

--
-- Indici per le tabelle `Catalogo`
--
ALTER TABLE `Catalogo`
  ADD PRIMARY KEY (`fid`,`pid`),
  ADD KEY `fk_catalogo_pezzo` (`pid`);

--
-- Indici per le tabelle `Fornitori`
--
ALTER TABLE `Fornitori`
  ADD PRIMARY KEY (`fid`);

--
-- Indici per le tabelle `Pezzi`
--
ALTER TABLE `Pezzi`
  ADD PRIMARY KEY (`pid`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `Account`
--
ALTER TABLE `Account`
  MODIFY `aid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT per la tabella `AccountSession`
--
ALTER TABLE `AccountSession`
  MODIFY `sid` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT per la tabella `Fornitori`
--
ALTER TABLE `Fornitori`
  MODIFY `fid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT per la tabella `Pezzi`
--
ALTER TABLE `Pezzi`
  MODIFY `pid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `Account`
--
ALTER TABLE `Account`
  ADD CONSTRAINT `fk_account_fornitore` FOREIGN KEY (`fid`) REFERENCES `Fornitori` (`fid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `AccountSession`
--
ALTER TABLE `AccountSession`
  ADD CONSTRAINT `fk_account_session_account` FOREIGN KEY (`aid`) REFERENCES `Account` (`aid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `Catalogo`
--
ALTER TABLE `Catalogo`
  ADD CONSTRAINT `fk_catalogo_fornitore` FOREIGN KEY (`fid`) REFERENCES `Fornitori` (`fid`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_catalogo_pezzo` FOREIGN KEY (`pid`) REFERENCES `Pezzi` (`pid`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
