-- add unique invBlueprintTypes productTypeID
ALTER TABLE `invBlueprintTypes`
	ADD UNIQUE INDEX `productTypeID` (`productTypeID`);
	
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
) ENGINE=InnoDB DEFAULT CHARSET=ascii COMMENT='This table holds the price and history information we collect.\r\nlow, high, avg, vol and tx are considered history data.\r\nsell, buy, supplyIn5, demandIn5, avgSell5OrderAge and avgBuy5OrderAge are considered price (order) data.\r\n\r\nlow, high, avg: Are the indices as returned by Eve''s market\r\ntx and vol: Are the indices as returned by Eve''s market, with one important difference: The latest stored value is an average over the 7 days before. This is a performance hack. The data returned by Eve''s maket for the current day is incomplete anyway, therefore of little value.\r\n\r\nsell and buy: Realistic price estimates. See emdr.php for algorithm.\r\nsupplyIn5, demandIn5, avgSell5OrderAge and avgBuy5OrderAge: Supply/demand and average order ages within 5% of the estimated prices. See emdr.php for algorithm.';

CREATE TABLE IF NOT EXISTS `iveeTrackedPrices` (
  `regionID` int(11) NOT NULL,
  `typeID` int(11) NOT NULL,
  `newestHistData` int(10) unsigned DEFAULT NULL,
  `newestPriceData` int(10) unsigned DEFAULT NULL,
  `lastHistUpdate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `lastPriceUpdate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`regionID`,`typeID`),
  UNIQUE KEY `latestHistUpdate` (`newestHistData`),
  UNIQUE KEY `latestPriceUpdate` (`newestPriceData`),
  CONSTRAINT `iveeTrackedPrices_ibfk_1` FOREIGN KEY (`newestHistData`) REFERENCES `iveePrices` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `iveeTrackedPrices_ibfk_2` FOREIGN KEY (`newestPriceData`) REFERENCES `iveePrices` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=ascii COMMENT='This table holds foreign keys to the latest price and history rows in iveePrices for each regionID/typeID combination, as well as update timestamps.\r\n\r\nnewestHistData and newestPriceData: Foreign Keys to iveePrices.\r\nlastHistUpdate and lastPriceUpdate: Timestamps for last data received ("generatedAt")';


DELIMITER //
CREATE DEFINER=`root`@`%` PROCEDURE `iveeCompleteHistoryUpdate`(IN `IN_typeID` INT, IN `IN_regionID` INT, IN `IN_generatedAt` DATETIME)
    MODIFIES SQL DATA
    DETERMINISTIC
    SQL SECURITY INVOKER
    COMMENT 'This procedure updates iveeTrackedPrices to point to the newest history data in iveePrices. It should be called after history data has been updated or inserted into iveePrices. It also calculates the averaged vol and tx values.'
BEGIN
	DECLARE newestHistId INT;
	DECLARE newestHistDate DATE;
	DECLARE avgVol DOUBLE;
	DECLARE avgTx FLOAT;
	
	# get the newest id
	SELECT id, date INTO newestHistId, newestHistDate 
	FROM iveePrices 
	WHERE regionID = IN_regionID 
	AND typeID = IN_typeID 
	AND (avg IS NOT NULL OR vol IS NOT NULL)
	ORDER BY date DESC
	LIMIT 1;
	
	# get average volume and transactions for last week
	SELECT AVG(COALESCE(vol, 0)), AVG(COALESCE(tx, 0)) INTO avgVol, avgTx 
	FROM iveePrices
	WHERE regionID = IN_regionID 
	AND typeID = IN_typeID
	AND date < newestHistDate 
	AND date > DATE_SUB(newestHistDate, INTERVAL 8 DAY);
	
	# update average volume and transactions on latest history row
	UPDATE iveePrices 
	SET vol = avgVol, tx = avgTx
	WHERE id = newestHistId;
	
	# upsert tracked prices
	INSERT INTO iveeTrackedPrices (regionID, typeID, newestHistData, lastHistUpdate) VALUES (IN_regionID, IN_typeId, newestHistId, IN_generatedAt)
	ON DUPLICATE KEY UPDATE newestHistData = newestHistId, lastHistUpdate = IN_generatedAt;
END//
DELIMITER ;


DELIMITER //
CREATE DEFINER=`root`@`%` PROCEDURE `iveeCompletePriceUpdate`(IN `IN_typeID` INT, IN `IN_regionID` INT, IN `IN_generatedAt` DATETIME)
    MODIFIES SQL DATA
    DETERMINISTIC
    SQL SECURITY INVOKER
    COMMENT 'This procedure updates iveeTrackedPrices to point to the newest price data in iveePrices. It should be called after price data has been updated or inserted into iveePrices.'
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


DELIMITER //
CREATE DEFINER=`root`@`%` PROCEDURE `iveeGetRequirements`(IN `IN_blueprintTypeID` INT)
    READS SQL DATA
    DETERMINISTIC
    SQL SECURITY INVOKER
    COMMENT 'Returns all requirements for all activities of a blueprint.'
BEGIN

# procedure adapted from http://wiki.eve-id.net/Denormalizing_itm/rtr
DROP TEMPORARY TABLE IF EXISTS reqtemp;
CREATE TEMPORARY TABLE reqtemp ENGINE = MEMORY 
SELECT
	rtr.typeID as blueprintTypeID,
	rtr.activityID,
	rtr.requiredTypeID,
	ig.categoryID,
	(rtr.quantity + IFNULL(itm.quantity, 0)) quantity,
	rtr.damagePerJob,
	itm.quantity as baseMaterial
FROM invBlueprintTypes AS b
INNER JOIN ramTypeRequirements AS rtr
	ON rtr.typeID = b.blueprintTypeID
	AND rtr.activityID IN (1,3,4,5,7,8)
LEFT OUTER JOIN invTypeMaterials AS itm
	ON itm.typeID = b.productTypeID
	AND itm.materialTypeID = rtr.requiredTypeID
JOIN invTypes AS it ON it.typeID = rtr.requiredTypeID
JOIN invGroups AS ig ON it.groupID = ig.groupID
WHERE rtr.quantity > 0
AND rtr.typeID = IN_blueprintTypeID;

DROP TEMPORARY TABLE IF EXISTS reqtemp2; 
CREATE TEMPORARY TABLE reqtemp2 ENGINE = MEMORY # need second temp table as workaround for MySQL limitation: Can't reference the same temp table more than once
SELECT
	b.blueprintTypeID,
	1 activityID,                  
	itm.materialTypeID requiredTypeID, 
	ig.categoryID,
	(itm.quantity - IFNULL(sub.quantity * sub.recycledQuantity, 0)) quantity,                  
	1 damagePerJob,                  
	(itm.quantity - IFNULL(sub.quantity * sub.recycledQuantity, 0)) as baseMaterial                   
FROM invBlueprintTypes AS b
INNER JOIN invTypeMaterials AS itm
	ON itm.typeID = b.productTypeID
LEFT OUTER JOIN reqtemp m
	ON b.blueprintTypeID = m.blueprintTypeID
	AND m.requiredTypeID = itm.materialTypeID
LEFT OUTER JOIN (
	SELECT srtr.typeID AS blueprintTypeID,  
   	sitm.materialTypeID AS recycledTypeID, 
		srtr.quantity AS recycledQuantity,
		sitm.quantity
	FROM ramTypeRequirements AS srtr
	INNER JOIN invTypeMaterials AS sitm
		ON srtr.requiredTypeID = sitm.typeID
	WHERE srtr.recycle = 1
   AND srtr.activityID = 1
) AS sub
	ON sub.blueprintTypeID = b.blueprintTypeID
	AND sub.recycledTypeID = itm.materialTypeID
JOIN invTypes AS it ON it.typeID = itm.materialTypeID
JOIN invGroups AS ig ON it.groupID = ig.groupID
WHERE m.blueprintTypeID IS NULL
AND (itm.quantity - IFNULL(sub.quantity * sub.recycledQuantity, 0) ) > 0
AND b.blueprintTypeID = IN_blueprintTypeID;

INSERT INTO reqtemp SELECT * FROM reqtemp2;
DROP TEMPORARY TABLE reqtemp2;

# update interface damage to 0
UPDATE `reqtemp` SET `damagePerJob` = 0 WHERE `requiredTypeID` IN (SELECT typeID FROM `invTypes` WHERE `groupID` = 716);

# insert invention skills
INSERT INTO reqtemp
SELECT DISTINCT 
	bt.blueprintTypeID, 
	8 activityID, 
	COALESCE(dta.valueInt, dta.valueFloat) requiredTypeID,
   ig.categoryID,
	sl.level quantity, 
	0 damagePerJob, 
	NULL baseMaterial
FROM ramTypeRequirements r, invBlueprintTypes bt, dgmTypeAttributes dta
LEFT JOIN (
	select r.requiredTypeID, COALESCE(dta.valueInt, dta.valueFloat) level, bt.blueprintTypeID
	FROM ramTypeRequirements r, invBlueprintTypes bt, dgmTypeAttributes dta
	WHERE r.typeID = bt.blueprintTypeID
	AND r.activityID = 8
	AND dta.typeID = r.requiredTypeID
	AND dta.attributeID = 277) as sl
ON sl.requiredTypeID = dta.typeID
JOIN invTypes AS it ON it.typeID = COALESCE(dta.valueInt, dta.valueFloat)
JOIN invGroups AS ig ON it.groupID = ig.groupID
WHERE r.typeID = bt.blueprintTypeID
AND bt.blueprintTypeID = sl.blueprintTypeID
AND bt.blueprintTypeID = IN_blueprintTypeID
AND r.activityID = 8
AND dta.typeID = r.requiredTypeID
AND dta.attributeID = 182;

SELECT * FROM reqtemp;
DROP TEMPORARY TABLE reqtemp;

END//
DELIMITER ;
