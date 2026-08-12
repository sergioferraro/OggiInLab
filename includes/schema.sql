--
-- Schema di base per OggiInLab
-- Solo struttura tabelle e dati di riferimento essenziali
-- I dati personali (docenti, appuntamenti, etc.) devono essere importati separatamente
--

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- Database
CREATE DATABASE IF NOT EXISTS `nzschool` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `nzschool`;

-- --------------------------------------------------------
-- Tabella: admin
-- --------------------------------------------------------

CREATE TABLE `admin` (
  `id` int NOT NULL,
  `nomeCompleto` varchar(27) COLLATE utf8mb4_general_ci NOT NULL,
  `adminEmail` varchar(120) COLLATE utf8mb4_general_ci NOT NULL,
  `userName` varchar(27) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(256) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updationDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_super_admin` tinyint(1) DEFAULT '0',
  `lastLogin` timestamp NULL DEFAULT NULL,
  `isActive` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1=attivo, 0=disattivato'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Utente amministratore di default
-- Password: 'admin' (hash generato con password_hash('admin', PASSWORD_DEFAULT))
-- MODIFICARE LA PASSWORD DOPO IL PRIMO ACCESSO
INSERT INTO `admin` (`id`, `nomeCompleto`, `adminEmail`, `userName`, `password`, `updationDate`, `is_super_admin`, `lastLogin`, `isActive`) VALUES
(1, 'Amministratore', 'admin@esempio.it', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-01-01 00:00:00', 1, NULL, 1);

-- --------------------------------------------------------
-- Tabella: appuntamento
-- --------------------------------------------------------

CREATE TABLE `appuntamento` (
  `idCorso` int NOT NULL,
  `data` date NOT NULL,
  `oraInizio` time NOT NULL,
  `oraFine` time NOT NULL,
  `luogo` int DEFAULT NULL,
  `isDeleted` tinyint(1) NOT NULL DEFAULT '0',
  `lastModified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `idAppuntamento` int NOT NULL,
  `descrizione` varchar(256) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `autore` int DEFAULT NULL,
  `creationDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Tabella: assistente
-- --------------------------------------------------------

CREATE TABLE `assistente` (
  `idAssistente` int NOT NULL,
  `nome` varchar(56) COLLATE utf8mb4_general_ci NOT NULL,
  `cognome` varchar(56) COLLATE utf8mb4_general_ci NOT NULL,
  `inServizio` tinyint NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Tabella: aula
-- --------------------------------------------------------

CREATE TABLE `aula` (
  `idAula` int NOT NULL,
  `nAula` varchar(64) COLLATE utf8mb4_general_ci NOT NULL,
  `nPosti` int NOT NULL,
  `computer` tinyint(1) DEFAULT '0',
  `richiedeAt` tinyint(1) DEFAULT '0',
  `lim` tinyint(1) DEFAULT '0',
  `pcDocente` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Aula di default necessaria per il funzionamento
INSERT INTO `aula` (`idAula`, `nAula`, `nPosti`, `computer`, `richiedeAt`, `lim`, `pcDocente`) VALUES
(18, 'aula generica', 27, 0, 0, 0, 0);

-- --------------------------------------------------------
-- Tabella: calendario
-- --------------------------------------------------------

CREATE TABLE `calendario` (
  `idCalendario` int NOT NULL,
  `annoScolastico` varchar(9) COLLATE utf8mb4_general_ci NOT NULL,
  `giorno` date NOT NULL,
  `nomeChiusura` varchar(64) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Tabella: comments
-- --------------------------------------------------------

CREATE TABLE `comments` (
  `id` int NOT NULL,
  `post_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `content` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Tabella: docente
-- --------------------------------------------------------

CREATE TABLE `docente` (
  `idDocente` int NOT NULL,
  `cognome` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `nome` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `isDeleted` tinyint DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Tabella: fasce
-- Fasce orarie del laboratorio (obbligatorie)
-- --------------------------------------------------------

CREATE TABLE `fasce` (
  `id` int NOT NULL,
  `inizio` time NOT NULL,
  `fine` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `fasce` (`id`, `inizio`, `fine`) VALUES
(1, '08:10:00', '09:10:00'),
(2, '09:10:00', '10:10:00'),
(3, '10:10:00', '11:10:00'),
(4, '11:10:00', '12:10:00'),
(5, '12:10:00', '13:10:00'),
(6, '13:10:00', '14:10:00'),
(7, '14:10:00', '15:10:00'),
(8, '15:00:00', '16:00:00');

-- --------------------------------------------------------
-- Tabella: likes
-- --------------------------------------------------------

CREATE TABLE `likes` (
  `id` int NOT NULL,
  `post_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Tabella: orario_settimana
-- --------------------------------------------------------

CREATE TABLE `orario_settimana` (
  `idOrarioSettimana` int NOT NULL,
  `idProgetto` int NOT NULL,
  `idAula` int NOT NULL,
  `idDocente` int DEFAULT NULL,
  `classe` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `giorno` enum('Lunedì','Martedì','Mercoledì','Giovedì','Venerdì','Sabato') COLLATE utf8mb4_general_ci NOT NULL,
  `ora_inizio` time NOT NULL,
  `ora_fine` time NOT NULL,
  `autore` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Tabella: posts
-- --------------------------------------------------------

CREATE TABLE `posts` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `content` text COLLATE utf8mb4_general_ci,
  `image_url` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Tabella: prestito
-- --------------------------------------------------------

CREATE TABLE `prestito` (
  `id` int NOT NULL,
  `id_admin` int NOT NULL,
  `beneficiario` varchar(100) NOT NULL,
  `classe` varchar(20) DEFAULT NULL,
  `data_prestito` date NOT NULL,
  `data_consegna_prevista` date NOT NULL,
  `data_consegna_effettiva` date DEFAULT NULL,
  `descrizione_bene` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------
-- Tabella: progetto
-- Progetti di base necessari per il funzionamento
-- --------------------------------------------------------

CREATE TABLE `progetto` (
  `idProgetto` int NOT NULL,
  `nomeProgetto` varchar(256) COLLATE utf8mb4_general_ci NOT NULL,
  `idTutor` int DEFAULT NULL,
  `idEsperto` int DEFAULT NULL,
  `descProgetto` text COLLATE utf8mb4_general_ci,
  `cnp` varchar(56) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cup` varchar(56) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `startDate` date DEFAULT NULL,
  `endDate` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `progetto` (`idProgetto`, `nomeProgetto`, `idTutor`, `idEsperto`, `descProgetto`, `cnp`, `cup`, `startDate`, `endDate`) VALUES
(1, 'prenotazione', NULL, NULL, 'prenotaaulagiornaliero', NULL, NULL, NULL, NULL),
(2, 'orario', NULL, NULL, 'orario delle lezioni', NULL, NULL, NULL, NULL),
(3, 'Manutenzione', NULL, NULL, 'Il laboratorio non è accessibile', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------
-- Tabella: servizi
-- --------------------------------------------------------

CREATE TABLE `servizi` (
  `idServizio` int NOT NULL,
  `idAssistente` int NOT NULL,
  `serviziData` date NOT NULL,
  `serviziOraInizio` time NOT NULL,
  `serviziOraFine` time NOT NULL,
  `serviziDescrizione` varchar(256) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `serviziLuogo` int NOT NULL,
  `serviziProj` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Tabella: utente
-- --------------------------------------------------------

CREATE TABLE `utente` (
  `id` int NOT NULL,
  `nomeCompleto` varchar(27) COLLATE utf8mb4_general_ci NOT NULL,
  `adminEmail` varchar(120) COLLATE utf8mb4_general_ci NOT NULL,
  `userName` varchar(27) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(256) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updationDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_super_admin` tinyint(1) DEFAULT '0',
  `lastLogin` timestamp NULL DEFAULT NULL,
  `isActive` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Indici per le tabelle
-- --------------------------------------------------------

ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `userName` (`userName`);

ALTER TABLE `appuntamento`
  ADD PRIMARY KEY (`idAppuntamento`),
  ADD KEY `idCorso` (`idCorso`),
  ADD KEY `luogo` (`luogo`),
  ADD KEY `autore` (`autore`);

ALTER TABLE `aula`
  ADD PRIMARY KEY (`idAula`);

ALTER TABLE `calendario`
  ADD PRIMARY KEY (`idCalendario`);

ALTER TABLE `docente`
  ADD PRIMARY KEY (`idDocente`);

ALTER TABLE `fasce`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `orario_settimana`
  ADD PRIMARY KEY (`idOrarioSettimana`);

ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `prestito`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `progetto`
  ADD PRIMARY KEY (`idProgetto`);

ALTER TABLE `servizi`
  ADD PRIMARY KEY (`idServizio`);

ALTER TABLE `utente`
  ADD PRIMARY KEY (`id`);

-- --------------------------------------------------------
-- AUTO_INCREMENT per le tabelle
-- --------------------------------------------------------

ALTER TABLE `admin`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `appuntamento`
  MODIFY `idAppuntamento` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `aula`
  MODIFY `idAula` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

ALTER TABLE `calendario`
  MODIFY `idCalendario` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `docente`
  MODIFY `idDocente` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `fasce`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

ALTER TABLE `orario_settimana`
  MODIFY `idOrarioSettimana` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `posts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `prestito`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `progetto`
  MODIFY `idProgetto` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE `servizi`
  MODIFY `idServizio` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `utente`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
