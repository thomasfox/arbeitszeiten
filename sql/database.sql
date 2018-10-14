CREATE TABLE `arbeitsgruppe` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8;

CREATE TABLE `familie` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `sollstunden` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;

CREATE TABLE `arbeitsgruppe_familie` (
  `arbeitsgruppe_id` int(11) NOT NULL,
  `familie_id` int(11) NOT NULL,
  PRIMARY KEY (`familie_id`,`arbeitsgruppe_id`),
  KEY `af_arbeitsgruppe_idx` (`arbeitsgruppe_id`),
  CONSTRAINT `af_arbeitsgruppe` FOREIGN KEY (`arbeitsgruppe_id`) REFERENCES `arbeitsgruppe` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `af_familie` FOREIGN KEY (`familie_id`) REFERENCES `familie` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `arbeitsauftrag` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `beschreibung` varchar(45) NOT NULL,
  `workdate` date DEFAULT NULL,
  `arbeitsgruppe_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `arbeitsauftrag_arbeitsgruppe_idx` (`arbeitsgruppe_id`),
  CONSTRAINT `arbeitsauftrag_arbeitsgruppe` FOREIGN KEY (`arbeitsgruppe_id`) REFERENCES `arbeitsgruppe` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8;

CREATE TABLE `arbeitszeit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `stunden` float NOT NULL,
  `familie_id` int(11) NOT NULL,
  `arbeitsauftrag_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `as_familie_arbeitsauftrag_uq` (`familie_id`,`arbeitsauftrag_id`),
  KEY `arbeitsstunden_familie_idx` (`familie_id`),
  KEY `arbeitszeit_arbeitsauftrag_idx` (`arbeitsauftrag_id`),
  CONSTRAINT `arbeitszeit_arbeitsauftrag` FOREIGN KEY (`arbeitsauftrag_id`) REFERENCES `arbeitsauftrag` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `arbeitszeit_familie` FOREIGN KEY (`familie_id`) REFERENCES `familie` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8;