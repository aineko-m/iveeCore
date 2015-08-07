CREATE TABLE `trackedCrestUpdates` (
	`name` VARCHAR(255) NOT NULL,
	`lastUpdate` DATETIME NOT NULL,
	PRIMARY KEY (`name`)
)
COMMENT='This table tracks when certain CREST updates were run.'
ENGINE=InnoDB;
