--
-- Database: `telegram`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `azioni`
--

CREATE TABLE `azioni` (
  `ID_TG` int(11) NOT NULL,
  `AZIONE` text NOT NULL,
  `STATO` text NOT NULL,
  `TS` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `azioni`
--
ALTER TABLE `azioni`
  ADD PRIMARY KEY (`ID_TG`),
  ADD UNIQUE KEY `ID_TG` (`ID_TG`);

--
-- Struttura della tabella `feedback`
--

CREATE TABLE `feedback` (
  `ID` int(11) NOT NULL,
  `ID_TG` int(11) NOT NULL,
  `VERSO` text COLLATE utf8_unicode_ci NOT NULL,
  `TESTO` text COLLATE utf8_unicode_ci NOT NULL,
  `RISPOSTA` int(11) NOT NULL DEFAULT '0',
  `NOTE` text COLLATE utf8_unicode_ci NOT NULL,
  `TS` int(11) NOT NULL,
  `VISTO` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `ID_2` (`ID`),
  ADD KEY `ID` (`ID`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `feedback`
--
ALTER TABLE `feedback`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- Struttura della tabella `messaggi`
--

CREATE TABLE `messaggi` (
  `ID_MESSAGGIO` int(11) NOT NULL,
  `CORRISPONDENTE` int(11) NOT NULL,
  `TESTO` text NOT NULL,
  `TIPO` text NOT NULL,
  `TS` int(11) NOT NULL,
  `NOTE` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `messaggi`
--
ALTER TABLE `messaggi`
  ADD PRIMARY KEY (`ID_MESSAGGIO`),
  ADD UNIQUE KEY `ID_MESSAGGIO` (`ID_MESSAGGIO`);