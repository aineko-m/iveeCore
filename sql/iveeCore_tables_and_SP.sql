-- add index invMetaTypes parentTypeID
ALTER TABLE `invMetaTypes`
	ADD INDEX `parentTypeID` (`parentTypeID`);
	
CREATE TABLE IF NOT EXISTS `iveePrices` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `typeID` int(11) NOT NULL,
  `regionID` int(11) NOT NULL,
  `date` date NOT NULL,
  `low` double unsigned DEFAULT NULL,
  `high` double unsigned DEFAULT NULL,
  `avg` double unsigned DEFAULT NULL,
  `vol` double unsigned DEFAULT NULL,
  `tx` float unsigned DEFAULT NULL,
  `sell` double unsigned DEFAULT NULL,
  `buy` double unsigned DEFAULT NULL,
  `supplyIn5` bigint(20) unsigned DEFAULT NULL,
  `demandIn5` bigint(20) unsigned DEFAULT NULL,
  `avgSell5OrderAge` mediumint(8) unsigned DEFAULT NULL,
  `avgBuy5OrderAge` mediumint(8) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `typeID` (`typeID`,`regionID`,`date`)
) ENGINE=InnoDB DEFAULT CHARSET=ascii COMMENT='This table holds the price and history information we collect.\r\nlow, high, avg, vol and tx are considered history data.\r\nsell, buy, supplyIn5, demandIn5, avgSell5OrderAge and avgBuy5OrderAge are considered price (order) data.\r\n\r\nlow, high, avg: Are the indices as returned by Eve\'s market\r\ntx and vol: Are the indices as returned by Eve\'s market\r\n\r\nsell and buy: Realistic price estimates. See EmdrPriceUpdate.php for algorithm.\r\nsupplyIn5, demandIn5, avgSell5OrderAge and avgBuy5OrderAge: Supply/demand and average order ages within 5% of the estimated prices.';

CREATE TABLE `iveeTrackedPrices` (
	`regionID` INT(11) NOT NULL,
	`typeID` INT(11) NOT NULL,
	`newestHistData` INT(10) UNSIGNED NULL DEFAULT NULL,
	`newestPriceData` INT(10) UNSIGNED NULL DEFAULT NULL,
	`lastHistUpdate` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
	`lastPriceUpdate` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
	`avgVol` FLOAT NULL DEFAULT NULL,
	`avgTx` FLOAT NULL DEFAULT NULL,
	PRIMARY KEY (`regionID`, `typeID`),
	UNIQUE INDEX `latestHistUpdate` (`newestHistData`),
	UNIQUE INDEX `latestPriceUpdate` (`newestPriceData`),
	CONSTRAINT `iveeTrackedPrices_ibfk_1` FOREIGN KEY (`newestHistData`) REFERENCES `iveePrices` (`id`) ON UPDATE CASCADE ON DELETE SET NULL,
	CONSTRAINT `iveeTrackedPrices_ibfk_2` FOREIGN KEY (`newestPriceData`) REFERENCES `iveePrices` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
)
COMMENT='This table holds the price and history information we collect.\r\nlow, high, avg, vol and tx are considered history data.\r\nsell, buy, supplyIn5, demandIn5, avgSell5OrderAge and avgBuy5OrderAge are considered price (order) data.\r\n\r\nlow, high, avg: Are the indices as returned by Eve\'s market\r\ntx and vol: Are the indices as returned by Eve\'s market\r\n\r\nsell and buy: Realistic price estimates. See EmdrPriceUpdate.php for algorithm.\r\nsupplyIn5, demandIn5, avgSell5OrderAge and avgBuy5OrderAge: Supply/demand and average order ages within 5% of the estimated prices.'
COLLATE='ascii_general_ci'
ENGINE=InnoDB;


DELIMITER //
CREATE DEFINER=`root`@`%` PROCEDURE `iveeCompleteHistoryUpdate`(IN `IN_typeID` INT, IN `IN_regionID` INT, IN `IN_generatedAt` DATETIME)
	DETERMINISTIC
	MODIFIES SQL DATA
	SQL SECURITY INVOKER
	COMMENT 'This procedure updates iveeTrackedPrices to point to the newest history row in iveePrices and also calculates the week average volume and transaction count for market items'
BEGIN
	DECLARE newestHistId INT;
	DECLARE newestHistDate DATE;
	DECLARE c_avgVol FLOAT;
	DECLARE c_avgTx FLOAT;
	
	# get id of newest history row
	SELECT id, date INTO newestHistId, newestHistDate 
	FROM iveePrices 
	WHERE regionID = IN_regionID 
	AND typeID = IN_typeID 
	AND (avg IS NOT NULL OR vol IS NOT NULL)
	ORDER BY date DESC
	LIMIT 1;
	
	# get average volume and transactions for last week
	SELECT AVG(COALESCE(vol, 0)), AVG(COALESCE(tx, 0)) INTO c_avgVol, c_avgTx 
	FROM iveePrices
	WHERE regionID = IN_regionID 
	AND typeID = IN_typeID
	AND date < newestHistDate 
	AND date > DATE_SUB(newestHistDate, INTERVAL 8 DAY);
	
	# upsert tracked prices
	INSERT INTO iveeTrackedPrices (regionID, typeID, newestHistData, lastHistUpdate, avgVol, avgTx) 
	VALUES (IN_regionID, IN_typeId, newestHistId, IN_generatedAt, c_avgVol, c_avgTx)
	ON DUPLICATE KEY UPDATE newestHistData = newestHistId, lastHistUpdate = IN_generatedAt, avgVol = c_avgVol, avgTx = c_avgTx;
END//
DELIMITER ;


DELIMITER //
CREATE DEFINER=`root`@`%` PROCEDURE `iveeCompletePriceUpdate`(IN `IN_typeID` INT, IN `IN_regionID` INT, IN `IN_generatedAt` DATETIME)
    MODIFIES SQL DATA
    DETERMINISTIC
    SQL SECURITY INVOKER
    COMMENT 'This procedure updates iveeTrackedPrices to point to the newest price row in iveePrices'
BEGIN
	DECLARE newestPriceId INT;
	
	# get the newest id
	SELECT id INTO newestPriceId 
	FROM iveePrices 
	WHERE regionID = IN_regionID 
	AND typeID = IN_typeID 
	AND (sell IS NOT NULL OR buy IS NOT NULL)
	ORDER BY date DESC
	LIMIT 1;
	
	# upsert new data
	INSERT INTO iveeTrackedPrices (regionID, typeID, newestPriceData, lastPriceUpdate) VALUES (IN_regionID, IN_typeId, newestPriceId, IN_generatedAt)
	ON DUPLICATE KEY UPDATE newestPriceData = newestPriceId, lastPriceUpdate = IN_generatedAt;
END//
DELIMITER ;

CREATE TABLE `iveeCrestPrices` (
	`typeID` INT(11) NOT NULL,
	`date` DATE NOT NULL,
	`averagePrice` DOUBLE UNSIGNED NULL DEFAULT NULL,
	`adjustedPrice` DOUBLE UNSIGNED NOT NULL,
	PRIMARY KEY (`typeID`, `date`)
)
COLLATE='ascii_general_ci'
ENGINE=InnoDB;

CREATE TABLE `iveeFacilities` (
	`facilityID` INT(11) NOT NULL,
	`owner` INT(11) NOT NULL,
	`tax` FLOAT NULL DEFAULT NULL,
	PRIMARY KEY (`facilityID`),
	INDEX `owner` (`owner`)
)
COLLATE='ascii_general_ci'
ENGINE=InnoDB;

CREATE TABLE `iveeIndustrySystems` (
	`systemID` INT(11) NOT NULL,
	`date` DATE NOT NULL,
	`manufacturingIndex` FLOAT UNSIGNED NOT NULL,
	`teResearchIndex` FLOAT UNSIGNED NOT NULL,
	`meResearchIndex` FLOAT UNSIGNED NOT NULL,
	`copyIndex` FLOAT UNSIGNED NOT NULL,
	`reverseIndex` FLOAT UNSIGNED NOT NULL,
	`inventionIndex` FLOAT UNSIGNED NOT NULL,
	PRIMARY KEY (`systemID`, `date`)
)
COLLATE='ascii_general_ci'
ENGINE=InnoDB;

CREATE TABLE `iveeSpecialities` (
	`specialityID` SMALLINT(5) UNSIGNED NOT NULL,
	`specialityName` VARCHAR(50) NOT NULL,
	PRIMARY KEY (`specialityID`)
)
COLLATE='ascii_general_ci'
ENGINE=InnoDB;

CREATE TABLE `iveeSpecialityGroups` (
	`specialityID` SMALLINT(5) UNSIGNED NOT NULL,
	`groupID` INT(11) NOT NULL,
	INDEX `specialtyID` (`specialityID`),
	INDEX `groupID` (`groupID`)
)
COLLATE='ascii_general_ci'
ENGINE=InnoDB;

CREATE TABLE `iveeTeams` (
	`teamID` INT(11) NOT NULL,
	`solarSystemID` INT(11) NOT NULL,
	`expiryTime` DATETIME NOT NULL,
	`creationTime` DATETIME NOT NULL,
	`activityID` TINYINT(4) NOT NULL,
	`teamName` VARCHAR(50) NOT NULL,
	`costModifier` TINYINT(4) NOT NULL,
	`specID` SMALLINT(5) UNSIGNED NOT NULL,
	`w0BonusID` TINYINT(4) UNSIGNED NOT NULL,
	`w0BonusValue` FLOAT NOT NULL,
	`w0SpecID` SMALLINT(5) UNSIGNED NOT NULL,
	`w1BonusID` TINYINT(4) UNSIGNED NOT NULL,
	`w1BonusValue` FLOAT NOT NULL,
	`w1SpecID` SMALLINT(5) UNSIGNED NOT NULL,
	`w2BonusID` TINYINT(4) UNSIGNED NOT NULL,
	`w2BonusValue` FLOAT NOT NULL,
	`w2SpecID` SMALLINT(5) UNSIGNED NOT NULL,
	`w3BonusID` TINYINT(4) UNSIGNED NOT NULL,
	`w3BonusValue` FLOAT NOT NULL,
	`w3SpecID` SMALLINT(5) UNSIGNED NOT NULL,
	PRIMARY KEY (`teamID`),
	INDEX `solarSystemID` (`solarSystemID`, `expiryTime`),
	INDEX `w0SpecID` (`w0SpecID`),
	INDEX `w1SpecID` (`w1SpecID`),
	INDEX `w2SpecID` (`w2SpecID`),
	INDEX `w3SpecID` (`w3SpecID`),
	INDEX `activityID` (`activityID`)
)
COMMENT='BonusID = 0 : time bonus, = 1 : material bonus'
COLLATE='ascii_general_ci'
ENGINE=InnoDB;
