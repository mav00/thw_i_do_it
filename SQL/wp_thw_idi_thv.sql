SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE TABLE `wp_thw_idi_thv` (
  `ID` int NOT NULL,
  `Datum` date NOT NULL,
  `Zeit` time NOT NULL,
  `Ort` varchar(10) NOT NULL,
  `Fuehrungskraft` int NOT NULL DEFAULT '-1',
  `Kraftfahrer` int NOT NULL DEFAULT '-1',
  `Helfer1` int NOT NULL DEFAULT '-1',
  `Helfer2` int NOT NULL DEFAULT '-1',
  `Helfer3` int NOT NULL DEFAULT '-1',
  `Helfer4` int NOT NULL DEFAULT '-1'
);


INSERT INTO `wp_thw_idi_thv` (`ID`, `Datum`, `Zeit`, `Ort`, `Fuehrungskraft`, `Kraftfahrer`, `Helfer1`, `Helfer2`, `Helfer3`, `Helfer4`) VALUES
(18, '0000-00-00', '00:00:00', '', -1, -1, -1, -1, -1, -1),
(19, '2020-12-28', '07:30:00', 'A97', 1, -1, -1, -1, -1, -1),
(22, '2020-12-28', '07:30:00', 'A12', -1, -1, 1, -1, -1, -1);

ALTER TABLE `wp_thw_idi_thv`
  ADD PRIMARY KEY (`ID`);
ALTER TABLE `wp_thw_idi_thv`
  MODIFY `ID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;
COMMIT;

