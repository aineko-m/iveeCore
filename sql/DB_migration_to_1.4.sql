-- extend iveeTrackedPrices with two columns
ALTER TABLE `iveeTrackedPrices`
        COMMENT='This table holds the price and history information we collect.\r\nlow, high, avg, vol and tx are considered history data.\r\nsell, buy, supplyIn5, demandIn5, avgSell5OrderAge and avgBuy5OrderAge are considered price (order) data.\r\n\r\nlow, high, avg: Are the indices as returned by Eve\'s market\r\ntx and vol: Are the indices as returned by Eve\'s market\r\n\r\nsell and buy: Realistic price estimates. See EmdrPriceUpdate.php for algorithm.\r\nsupplyIn5, demandIn5, avgSell5OrderAge and avgBuy5OrderAge: Supply/demand and average order ages within 5% of the estimated prices.',
	ADD COLUMN `avgVol` FLOAT NULL DEFAULT NULL AFTER `lastPriceUpdate`,
	ADD COLUMN `avgTx` FLOAT NULL DEFAULT NULL AFTER `avgVol`;

-- fill the new columns with existing data
UPDATE iveeTrackedPrices as itp
JOIN iveePrices as ip ON ip.id = itp.newestHistData
SET itp.avgVol=ip.avg, itp.avgTx=ip.tx;

-- modify vol and tx to integers
ALTER TABLE `iveePrices`
	COMMENT='This table holds the price and history information we collect.\r\nlow, high, avg, vol and tx are considered history data.\r\nsell, buy, supplyIn5, demandIn5, avgSell5OrderAge and avgBuy5OrderAge are considered price (order) data.\r\n\r\nlow, high, avg: Are the indices as returned by Eve\'s market\r\ntx and vol: Are the indices as returned by Eve\'s market\r\n\r\nsell and buy: Realistic price estimates. See EmdrPriceUpdate.php for algorithm.\r\nsupplyIn5, demandIn5, avgSell5OrderAge and avgBuy5OrderAge: Supply/demand and average order ages within 5% of the estimated prices.',
	CHANGE COLUMN `vol` `vol` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `avg`,
	CHANGE COLUMN `tx` `tx` MEDIUMINT UNSIGNED NULL DEFAULT NULL AFTER `vol`;

-- modify stored procedures
DROP PROCEDURE IF EXISTS `iveeCompleteHistoryUpdate`;
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
