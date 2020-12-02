
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


CREATE TABLE `wp_thw_idi_thv` (
  `ID` int NOT NULL,
  `Datum` date NOT NULL,
  `Zeit` time NOT NULL,
  `Ort` varchar(10) NOT NULL,
  `Fuehrungskraft` varchar(50) NOT NULL,
  `Kraftfahrer` varchar(50) NOT NULL,
  `Helfer1` varchar(50) ,
  `Helfer2` varchar(50) ,
  `Helfer3` varchar(50) ,
  `Helfer4` varchar(50) 
) ;

--
-- Daten für Tabelle `wp_thw_idi_thv`
--

INSERT INTO `wp_thw_idi_thv` (`ID`, `Datum`, `Zeit`, `Ort`, `Fuehrungskraft`, `Kraftfahrer`, `Helfer1`, `Helfer2`, `Helfer3`, `Helfer4`) VALUES
(3, '0000-00-00', '00:00:00', '', '', '', '', '', '', ''),
(9, '2020-12-28', '07:00:00', 'A98', 'Matthias Verwold', ' ', '_NutzerEintragen_', '_NutzerEintragen_', '', '');

--
-- Indizes für die Tabelle `wp_thw_idi_thv`
--
ALTER TABLE `wp_thw_idi_thv`
  ADD PRIMARY KEY (`ID`);

ALTER TABLE `wp_thw_idi_thv`
  MODIFY `ID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

COMMIT;

