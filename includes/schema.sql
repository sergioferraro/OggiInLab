-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Creato il: Mag 16, 2025 alle 20:44
-- Versione del server: 10.4.32-MariaDB
-- Versione PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `nzschool`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `nomeCompleto` varchar(27) NOT NULL,
  `adminEmail` varchar(120) NOT NULL,
  `userName` varchar(27) NOT NULL,
  `password` varchar(256) DEFAULT NULL,
  `updationDate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_super_admin` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Struttura della tabella `appuntamento`
--

CREATE TABLE `appuntamento` (
  `idCorso` int(11) NOT NULL,
  `data` date NOT NULL,
  `oraInizio` time NOT NULL,
  `oraFine` time NOT NULL,
  `luogo` int(11) DEFAULT NULL,
  `isDeleted` tinyint(1) NOT NULL DEFAULT 0,
  `lastModified` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `idAppuntamento` int(11) NOT NULL,
  `descrizione` varchar(256) DEFAULT NULL,
  `autore` int(11) DEFAULT NULL,
  `creationDate` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


--
-- Struttura della tabella `aula`
--

CREATE TABLE `aula` (
  `idAula` int(11) NOT NULL,
  `nAula` varchar(64) NOT NULL,
  `nPosti` int(11) NOT NULL,
  `computer` tinyint(1) DEFAULT 0,
  `richiedeAt` tinyint(1) DEFAULT 0,
  `lim` tinyint(1) DEFAULT 0,
  `pcDocente` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `aula` (`idAula`, `nAula`, `nPosti`, `computer`, `richiedeAt`, `lim`, `pcDocente`) VALUES
(1, 'aula generica', 27, 0, 0, 0, 0);

--
-- Struttura della tabella `calendario`
--

CREATE TABLE `calendario` (
  `idCalendario` int(11) NOT NULL,
  `annoScolastico` varchar(9) NOT NULL,
  `giorno` date NOT NULL,
  `nomeChiusura` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


--
-- Struttura della tabella `comments`
--

CREATE TABLE `comments` (
  `id` int(11) NOT NULL,
  `post_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


--
-- Struttura della tabella `docente`
--

CREATE TABLE `docente` (
  `idDocente` int(11) NOT NULL,
  `cognome` varchar(32) NOT NULL,
  `nome` varchar(32) NOT NULL,
  `isDeleted` tinyint(4) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


--
-- Struttura della tabella `likes`
--

CREATE TABLE `likes` (
  `id` int(11) NOT NULL,
  `post_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


--
-- Struttura della tabella `orario_settimana`
--

CREATE TABLE `orario_settimana` (
  `idOrarioSettimana` int(11) NOT NULL,
  `idProgetto` int(11) NOT NULL,
  `idAula` int(11) NOT NULL,
  `idDocente` int(11) DEFAULT NULL,
  `classe` varchar(50) NOT NULL,
  `giorno` enum('Lunedì','Martedì','Mercoledì','Giovedì','Venerdì','Sabato') NOT NULL,
  `ora_inizio` time NOT NULL,
  `ora_fine` time NOT NULL,
  `autore` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



--
-- Struttura della tabella `posts`
--

CREATE TABLE `posts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Struttura della tabella `progetto`
--

CREATE TABLE `progetto` (
  `idProgetto` int(11) NOT NULL,
  `nomeProgetto` varchar(256) NOT NULL,
  `idTutor` int(11) DEFAULT NULL,
  `idEsperto` int(11) DEFAULT NULL,
  `descProgetto` text DEFAULT NULL,
  `cnp` varchar(56) DEFAULT NULL,
  `cup` varchar(56) DEFAULT NULL,
  `startDate` date DEFAULT NULL,
  `endDate` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `progetto` (`idProgetto`, `nomeProgetto`, `idTutor`, `idEsperto`, `descProgetto`, `cnp`, `cup`, `startDate`, `endDate`) VALUES
(1, 'prenotazione', NULL, NULL, 'prenotaaulagiornaliero', NULL, NULL, '2025-04-23', NULL),
(2, 'orario', NULL, NULL, 'orario delle lezioni', NULL, NULL, '2024-09-09', '2025-06-07');
--
-- Struttura della tabella `servizi`
--

CREATE TABLE `servizi` (
  `idServizio` int(11) NOT NULL,
  `idAssistente` int(11) NOT NULL,
  `serviziData` date NOT NULL,
  `serviziOraInizio` time NOT NULL,
  `serviziOraFine` time NOT NULL,
  `serviziDescrizione` varchar(256) DEFAULT NULL,
  `serviziLuogo` int(11) NOT NULL,
  `serviziProj` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Struttura della tabella `fasce`
--

CREATE TABLE `fasce` (
  `id` int NOT NULL,
  `inizio` time NOT NULL,
  `fine` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `fasce`
--

INSERT INTO `fasce` (`id`, `inizio`, `fine`) VALUES
(1, '08:10:00', '09:10:00'),
(2, '09:10:00', '10:10:00'),
(3, '10:10:00', '11:10:00'),
(4, '11:10:00', '12:10:00'),
(5, '12:10:00', '13:10:00'),
(6, '13:10:00', '14:10:00'),
(7, '14:10:00', '15:10:00'),
(8, '15:00:00', '16:00:00');

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `fasce`
--
ALTER TABLE `fasce`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `fasce`
--
ALTER TABLE `fasce`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;
COMMIT;


--
-- Indici per le tabelle `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `adminEmail` (`adminEmail`),
  ADD UNIQUE KEY `userName` (`userName`);

--
-- Indici per le tabelle `appuntamento`
--
ALTER TABLE `appuntamento`
  ADD PRIMARY KEY (`idCorso`,`idAppuntamento`),
  ADD UNIQUE KEY `dataLuogoOraIsDeleted` (`data`,`luogo`,`oraInizio`,`isDeleted`),
  ADD KEY `luogoIndex` (`luogo`),
  ADD KEY `idAppuntamentoIndex` (`idAppuntamento`),
  ADD KEY `appuntamento_ibfk_3` (`autore`);

--
-- Indici per le tabelle `aula`
--
ALTER TABLE `aula`
  ADD PRIMARY KEY (`idAula`),
  ADD UNIQUE KEY `nAulaUnico` (`nAula`);

--
-- Indici per le tabelle `calendario`
--
ALTER TABLE `calendario`
  ADD PRIMARY KEY (`idCalendario`);

--
-- Indici per le tabelle `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `fk_post_id` (`post_id`);

--
-- Indici per le tabelle `docente`
--
ALTER TABLE `docente`
  ADD PRIMARY KEY (`idDocente`);

--
-- Indici per le tabelle `likes`
--
ALTER TABLE `likes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `fk_post_id2` (`post_id`);

--
-- Indici per le tabelle `orario_settimana`
--
ALTER TABLE `orario_settimana`
  ADD PRIMARY KEY (`idOrarioSettimana`),
  ADD KEY `idProgetto` (`idProgetto`),
  ADD KEY `idAula` (`idAula`),
  ADD KEY `idDocente` (`idDocente`),
  ADD KEY `orario_settimana_ibfk_4` (`autore`);

--
-- Indici per le tabelle `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indici per le tabelle `progetto`
--
ALTER TABLE `progetto`
  ADD PRIMARY KEY (`idProgetto`),
  ADD KEY `tutorIndex` (`idTutor`),
  ADD KEY `espertoIndex` (`idEsperto`);

--
-- Indici per le tabelle `servizi`
--
ALTER TABLE `servizi`
  ADD PRIMARY KEY (`idServizio`),
  ADD KEY `idAssistente` (`idAssistente`),
  ADD KEY `serviziLuogo` (`serviziLuogo`),
  ADD KEY `serviziProj` (`serviziProj`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT per la tabella `appuntamento`
--
ALTER TABLE `appuntamento`
  MODIFY `idAppuntamento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=237;

--
-- AUTO_INCREMENT per la tabella `aula`
--
ALTER TABLE `aula`
  MODIFY `idAula` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT per la tabella `calendario`
--
ALTER TABLE `calendario`
  MODIFY `idCalendario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT per la tabella `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT per la tabella `docente`
--
ALTER TABLE `docente`
  MODIFY `idDocente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=116;

--
-- AUTO_INCREMENT per la tabella `likes`
--
ALTER TABLE `likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT per la tabella `orario_settimana`
--
ALTER TABLE `orario_settimana`
  MODIFY `idOrarioSettimana` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=189;

--
-- AUTO_INCREMENT per la tabella `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT per la tabella `progetto`
--
ALTER TABLE `progetto`
  MODIFY `idProgetto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT per la tabella `servizi`
--
ALTER TABLE `servizi`
  MODIFY `idServizio` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`),
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `admin` (`id`),
  ADD CONSTRAINT `fk_post_id` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `likes`
--
ALTER TABLE `likes`
  ADD CONSTRAINT `fk_post_id2` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `likes_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`),
  ADD CONSTRAINT `likes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `admin` (`id`);

--
-- Limiti per la tabella `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `admin` (`id`);

--
-- Limiti per la tabella `progetto`
--
ALTER TABLE `progetto`
  ADD CONSTRAINT `progetto_ibfk_1` FOREIGN KEY (`idTutor`) REFERENCES `docente` (`idDocente`),
  ADD CONSTRAINT `progetto_ibfk_2` FOREIGN KEY (`idEsperto`) REFERENCES `docente` (`idDocente`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
