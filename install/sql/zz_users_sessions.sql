CREATE TABLE IF NOT EXISTS `zz_users_sessions` (
  `userID` int(11) NOT NULL,
  `sessionHash` varchar(192) NOT NULL,
  `dateCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `validTill` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `userAgent` varchar(256) NOT NULL,
  `ip` varchar(16) NOT NULL,
  UNIQUE KEY `sessionHash` (`sessionHash`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;