-- add index invTypeReactions typeID
ALTER TABLE `invTypeReactions`
	ADD INDEX `typeID` (`typeID`);
