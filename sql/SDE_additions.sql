-- add index invMetaTypes parentTypeID
ALTER TABLE `invMetaTypes`
	ADD INDEX `parentTypeID` (`parentTypeID`);

-- add index invTypeReactions typeID
ALTER TABLE `invTypeReactions`
	ADD INDEX `typeID` (`typeID`);
