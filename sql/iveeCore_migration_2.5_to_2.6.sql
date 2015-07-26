-- !! ATTENTION !!
-- Before running this schema migration make sure you stop all write activity to the iveeCore tables or the migration
-- might fail due to foreign key errors. The migration itself might take a long time depending on how large the DB and
-- how fast the storage backing the DB is.

-- split iveePrices into marketHistory and marketPrices
-- first create new table for history
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

-- import relevant rows of history, this may take a while
INSERT INTO marketHistory
SELECT id, typeID, regionID, date, low, high, avg, vol, tx
FROM iveePrices
WHERE low IS NOT NULL OR high IS NOT NULL OR avg IS NOT NULL OR vol IS NOT NULL OR tx IS NOT NULL;

-- temporarily remove foreign keys
ALTER TABLE `iveeTrackedPrices`
    DROP FOREIGN KEY `iveeTrackedPrices_ibfk_1`,
    DROP FOREIGN KEY `iveeTrackedPrices_ibfk_2`;

-- remove non-price rows from iveePrices, this may take a while
DELETE FROM iveePrices
WHERE sell IS NULL AND buy IS NULL;

-- remove history columns from prices table, this may take a while
ALTER TABLE `iveePrices`
    DROP COLUMN `low`,
    DROP COLUMN `high`,
    DROP COLUMN `avg`,
    DROP COLUMN `vol`,
    DROP COLUMN `tx`;

-- alter description and rename table
ALTER TABLE `iveePrices`
	COMMENT='This table holds the price information we collect.\r\n\r\nsell and buy: Realistic price estimates. See PriceEstimator.php for algorithm.\r\nsupplyIn5, demandIn5: Supply/demand within 5% of the estimated prices.\r\navgSell5OrderAge and avgBuy5OrderAge: Order ages within 5% of the estimated prices.';
RENAME TABLE `iveePrices` TO `marketPrices`;

-- re-add foreign keys
ALTER TABLE `iveeTrackedPrices`
    ADD CONSTRAINT `iveeTrackedPrices_ibfk_1` FOREIGN KEY (`newestHistData`) REFERENCES `marketHistory` (`id`) 
    ON UPDATE CASCADE ON DELETE SET NULL,
    ADD CONSTRAINT `iveeTrackedPrices_ibfk_2` FOREIGN KEY (`newestPriceData`) REFERENCES `marketPrices` (`id`) 
    ON UPDATE CASCADE ON DELETE SET NULL;

-- drop facilities table
DROP TABLE `iveeFacilities`;

-- make column name consistent
ALTER TABLE `iveeOutposts`
    CHANGE COLUMN `owner` `ownerID` INT(11) NOT NULL AFTER `solarSystemID`;
ALTER TABLE `iveeOutposts`
	COMMENT='This table holds the information about outposts fetched from the CREST facilities endpoint (non-conquerable stations are not included)';
RENAME TABLE `iveeOutposts` TO `outposts`;

-- alter description and rename table
ALTER TABLE `iveeTrackedPrices`
    COMMENT='This table points to the most recent rows of data for both history and prices, also storing the timestamp of their respective last update attempts, as well as a weekly average for volume and number of transactions.';
RENAME TABLE `iveeTrackedPrices` TO `trackedMarketData`;

-- drop reverse engineering indices from system indices table and rename
ALTER TABLE `iveeIndustrySystems`
    DROP COLUMN `reverseIndex`;
RENAME TABLE `iveeIndustrySystems` TO `systemIndustryIndices`;
ALTER TABLE `systemIndustryIndices`
    COMMENT='This table stores the solar system industry indices over time';

-- add comment and rename CREST prices table
ALTER TABLE `iveeCrestPrices`
    COMMENT='This table holds the global average and adjusted prices for items that are fetched from CREST, over time';
RENAME TABLE `iveeCrestPrices` TO `globalPrices`;

-- adapt stored procedures
DROP PROCEDURE IF EXISTS `iveeCompleteHistoryUpdate`;
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

DROP PROCEDURE IF EXISTS `iveeCompletePriceUpdate`;
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