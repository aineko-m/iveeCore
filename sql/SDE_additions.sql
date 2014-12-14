-- add index invMetaTypes parentTypeID
ALTER TABLE `invMetaTypes`
	ADD INDEX `parentTypeID` (`parentTypeID`);

-- add index invTypeReactions typeID
ALTER TABLE `invTypeReactions`
	ADD INDEX `typeID` (`typeID`);

-- add missing compatibility data for blueprint activities in assembly lines
INSERT INTO `ramAssemblyLineTypeDetailPerCategory` (`assemblyLineTypeID`, `categoryID`, `timeMultiplier`, `materialMultiplier`, `costMultiplier`)
VALUES (110, 9, 1, 1, 0.81),
(111, 9, 1, 1, 0.81),
(107, 9, 1, 1, 0.9),
(147, 9, 1, 1, 1),
(109, 9, 1, 1, 0.9),
(108, 9, 1, 1, 0.9),
(148, 9, 1, 1, 1),
(112, 9, 1, 1, 0.81),
(113, 9, 1, 1, 0.729),
(75, 9, 1, 1, 0.81),
(76, 9, 1, 1, 0.729),
(77, 9, 1, 1, 0.729),
(134, 9, 1, 1, 0.729),
(138, 9, 1, 1, 0.9),
(78, 9, 1, 1, 0.729),
(146, 9, 1, 1, 1),
(115, 9, 1, 1, 0.729),
(114, 9, 1, 1, 0.729),
(74, 9, 1, 1, 0.81),
(81, 9, 1, 1, 0.9),
(82, 9, 1, 1, 0.9),
(131, 9, 1, 1, 0.81),
(149, 9, 1, 1, 1),
(130, 9, 1, 1, 0.81),
(128, 9, 1, 1, 0.9),
(129, 9, 1, 1, 0.81),
(168, 9, 1, 1, 1),
(127, 9, 1, 1, 0.9),
(7, 9, 1, 1, 1),
(169, 9, 1, 1, 1),
(132, 9, 1, 1, 0.729),
(133, 9, 1, 1, 0.729),
(92, 9, 1, 1, 0.729),
(83, 9, 1, 1, 0.9),
(84, 9, 1, 1, 0.81),
(85, 9, 1, 1, 0.81),
(86, 9, 1, 1, 0.81),
(87, 9, 1, 1, 0.729),
(88, 9, 1, 1, 0.729),
(89, 9, 1, 1, 0.729),
(90, 9, 1, 1, 0.9),
(91, 9, 1, 1, 0.81),
(5, 9, 1, 1, 1),
(73, 9, 1, 1, 0.81),
(72, 9, 1, 1, 0.9),
(34, 9, 1, 1, 1),
(69, 9, 1, 1, 0.9),
(8, 9, 1, 1, 1),
(32, 9, 1, 1, 1),
(33, 9, 1, 1, 1),
(30, 9, 1, 1, 1),
(29, 9, 1, 1, 1),
(28, 9, 1, 1, 1),
(71, 9, 1, 1, 0.9);
