CREATE TABLE tweetSearchMatches (
	tweetID bigint unsigned NOT NULL,
	searchID int unsigned NOT NULL,
	UNIQUE KEY tweetID (tweetID,searchID)
) DEFAULT CHARSET=utf8;

CREATE TABLE tweets (
	tweetID bigint unsigned NOT NULL,
	tweetDate int unsigned NOT NULL,
	languageCode char(3) NOT NULL,
	tweet varchar(250) NOT NULL,
	source varchar(250) NOT NULL,
	profileImage varchar(250) NOT NULL,
	fromUser varchar(31) NOT NULL,
	toUser varchar(31) NOT NULL,
	PRIMARY KEY (tweetID),
	KEY fromUser (fromUser)
) DEFAULT CHARSET=utf8;

CREATE TABLE twitterSearches (
	searchID int unsigned NOT NULL AUTO_INCREMENT,
	term varchar(250) NOT NULL,
	searchTime int unsigned NOT NULL,
	lastTweetID bigint unsigned NOT NULL,
	name varchar(250) NOT NULL,
	PRIMARY KEY (searchID),
	UNIQUE KEY name (name)
) DEFAULT CHARSET=utf8;

CREATE TABLE `twitterUsers` (
  `username` varchar(20) NOT NULL,
  `profileImage` varchar(255) NOT NULL,
  PRIMARY KEY  (`username`)
) DEFAULT CHARSET=utf8;

insert into twitterSearches(term, name) values ('twitterslurp', 'twitterslurp');
