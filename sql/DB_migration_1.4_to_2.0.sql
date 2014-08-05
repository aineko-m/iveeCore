ALTER TABLE `industryActivityProducts`
	ADD INDEX `productTypeID` (`productTypeID`);

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
