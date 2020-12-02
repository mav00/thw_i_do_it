
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


CREATE TABLE `wp_thw_idi_configuration` (
  `idiTable` varchar(50) NOT NULL,
  `idiFields` text NOT NULL,
  `idiHeaders` text NOT NULL,
  `idiWhere` text NOT NULL,
  `idiSelectableFields` text NOT NULL,
  `idiAdminRole` varchar(50) NOT NULL
);

INSERT INTO `wp_thw_idi_configuration` (`idiTable`, `idiFields`, `idiHeaders`, `idiWhere`, `idiSelectableFields`, `idiAdminRole`) VALUES
('thv', 'Datum,Zeit,Ort,Fuehrungskraft,Kraftfahrer,Helfer1,Helfer2,Helfer3,Helfer4', 'Datum,Uhrzeit,Ort,Führungskraft,Kraftfahrer,Helfer 1,Helfer 2,Helfer 3, Helfer 4', 'WHERE Datum >= CURDATE()', 'Fuehrungskraft,Kraftfahrer,Helfer1,Helfer2,Helfer3,Helfer4', 'administrator');

ALTER TABLE `wp_thw_idi_configuration`
  ADD PRIMARY KEY (`idiTable`);
COMMIT;

