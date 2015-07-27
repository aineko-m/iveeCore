CREATE TABLE `marketPrices` (
        `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        `typeID` INT(11) NOT NULL,
        `regionID` INT(11) NOT NULL,
        `date` DATE NOT NULL,
        `sell` DOUBLE UNSIGNED NULL DEFAULT NULL,
        `buy` DOUBLE UNSIGNED NULL DEFAULT NULL,
        `supplyIn5` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
        `demandIn5` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
        `avgSell5OrderAge` MEDIUMINT(8) UNSIGNED NULL DEFAULT NULL,
        `avgBuy5OrderAge` MEDIUMINT(8) UNSIGNED NULL DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE INDEX `typeID` (`typeID`, `regionID`, `date`)
)
COMMENT='This table holds the price information we collect.\r\n\r\nsell and buy: Realistic price estimates. See PriceEstimator.php for algorithm.\r\nsupplyIn5, demandIn5: Supply/demand within 5% of the estimated prices.\r\navgSell5OrderAge and avgBuy5OrderAge: Order ages within 5% of the estimated prices.'
COLLATE='ascii_general_ci'
ENGINE=InnoDB;

CREATE TABLE `marketHistory` (
        `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        `typeID` INT(11) NOT NULL,
        `regionID` INT(11) NOT NULL,
        `date` DATE NOT NULL,
        `low` DOUBLE UNSIGNED NULL DEFAULT NULL,
        `high` DOUBLE UNSIGNED NULL DEFAULT NULL,
        `avg` DOUBLE UNSIGNED NULL DEFAULT NULL,
        `vol` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
        `tx` MEDIUMINT(8) UNSIGNED NULL DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE INDEX `typeID` (`typeID`, `regionID`, `date`)
)
COMMENT='This table holds the market history information we collect.'
COLLATE='ascii_general_ci'
ENGINE=InnoDB;

CREATE TABLE `trackedMarketData` (
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
        CONSTRAINT `trackedMarketData_ibfk_1` FOREIGN KEY (`newestHistData`) REFERENCES `marketHistory` (`id`) ON UPDATE CASCADE ON DELETE SET NULL,
        CONSTRAINT `trackedMarketData_ibfk_2` FOREIGN KEY (`newestPriceData`) REFERENCES `marketPrices` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
)
COMMENT='This table points to the most recent rows of data for both history and prices, also storing the timestamp of their respective last update attempts, as well as a weekly average for volume and number of transactions.'
COLLATE='ascii_general_ci'
ENGINE=InnoDB;

DELIMITER //
CREATE PROCEDURE `completeHistoryUpdate`(IN `IN_typeID` INT, IN `IN_regionID` INT, IN `IN_generatedAt` DATETIME)
    LANGUAGE SQL
    DETERMINISTIC
    MODIFIES SQL DATA
    SQL SECURITY INVOKER
    COMMENT 'This procedure updates trackedMarketData to point to the newest history row in marketHistory and also calculates the week average volume and transaction count for market items'
BEGIN
    DECLARE newestHistId INT;
    DECLARE newestHistDate DATE;
    DECLARE c_avgVol FLOAT;
    DECLARE c_avgTx FLOAT;

    # get id of newest history row
    SELECT id, date INTO newestHistId, newestHistDate
    FROM marketHistory
    WHERE regionID = IN_regionID
    AND typeID = IN_typeID
    ORDER BY date DESC
    LIMIT 1;

    # get average volume and transactions for last week
    SELECT AVG(COALESCE(vol, 0)), AVG(COALESCE(tx, 0)) INTO c_avgVol, c_avgTx
    FROM marketHistory
    WHERE regionID = IN_regionID
    AND typeID = IN_typeID
    AND date < newestHistDate
    AND date > DATE_SUB(newestHistDate, INTERVAL 8 DAY);

    # upsert trackedMarketData
    INSERT INTO trackedMarketData (regionID, typeID, newestHistData, lastHistUpdate, avgVol, avgTx)
    VALUES (IN_regionID, IN_typeId, newestHistId, IN_generatedAt, c_avgVol, c_avgTx)
    ON DUPLICATE KEY UPDATE newestHistData = newestHistId, lastHistUpdate = IN_generatedAt, avgVol = c_avgVol, avgTx = c_avgTx;
END
//
DELIMITER ;

DELIMITER //
CREATE PROCEDURE `completePriceUpdate`(IN `IN_typeID` INT, IN `IN_regionID` INT, IN `IN_generatedAt` DATETIME)
    LANGUAGE SQL
    DETERMINISTIC
    MODIFIES SQL DATA
    SQL SECURITY INVOKER
    COMMENT 'This procedure updates tracketMarketData to point to the newest row in marketPrices'
BEGIN
    DECLARE newestPriceId INT;

    # get the newest id
    SELECT id INTO newestPriceId
    FROM marketPrices
    WHERE regionID = IN_regionID
    AND typeID = IN_typeID
    ORDER BY date DESC
    LIMIT 1;

    # upsert new data
    INSERT INTO trackedMarketData (regionID, typeID, newestPriceData, lastPriceUpdate) VALUES (IN_regionID, IN_typeId, newestPriceId, IN_generatedAt)
	ON DUPLICATE KEY UPDATE newestPriceData = newestPriceId, lastPriceUpdate = IN_generatedAt;
END
//
DELIMITER ;

CREATE TABLE `globalPrices` (
    `typeID` INT(11) NOT NULL,
    `date` DATE NOT NULL,
    `averagePrice` DOUBLE UNSIGNED NULL DEFAULT NULL,
    `adjustedPrice` DOUBLE UNSIGNED NOT NULL,
    PRIMARY KEY (`typeID`, `date`)
)
COMMENT='This table holds the global average and adjusted prices for items that are fetched from CREST, over time'
COLLATE='ascii_general_ci'
ENGINE=InnoDB;

CREATE TABLE `systemIndustryIndices` (
        `systemID` INT(11) NOT NULL,
        `date` DATE NOT NULL,
        `manufacturingIndex` FLOAT UNSIGNED NOT NULL,
        `teResearchIndex` FLOAT UNSIGNED NOT NULL,
        `meResearchIndex` FLOAT UNSIGNED NOT NULL,
        `copyIndex` FLOAT UNSIGNED NOT NULL,
        `inventionIndex` FLOAT UNSIGNED NOT NULL,
        PRIMARY KEY (`systemID`, `date`),
        INDEX `date` (`date`)
)
COMMENT='This table stores the solar system industry indices over time'
COLLATE='ascii_general_ci'
ENGINE=InnoDB;

CREATE TABLE `outposts` (
        `facilityID` INT(11) NOT NULL,
        `solarSystemID` INT(11) NULL DEFAULT NULL,
        `ownerID` INT(11) NOT NULL,
        `stationTypeID` INT(11) NOT NULL,
        `stationName` VARCHAR(50) NOT NULL,
        PRIMARY KEY (`facilityID`),
        INDEX `owner` (`ownerID`),
        INDEX `solarSystemID` (`solarSystemID`),
        INDEX `stationTypeID` (`stationTypeID`)
)
COMMENT='This table holds the information about outposts fetched from the CREST facilities endpoint (non-conquerable stations are not included)'
COLLATE='ascii_general_ci'
ENGINE=InnoDB;