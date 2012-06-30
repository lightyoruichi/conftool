<?php
/*
	Twitterslurp: Configuration
	Author: John Bafford - http://bafford.com
	Copyright 2009 The Bivings Group
	http://www.bivings.com
*/


//Database
define('DBNAME', 'conftool');
define('DBUSER', 'socialmedia192');
define('DBPASS', 'socialmedia192');
define('DBHOST', 'mysql.lightyoruichi.com');
define('DB_PREFIX', '');


//Target Time Zone
define('TS_TZ', 'Asia/Kuala_Lumpur');
define('TS_TZ_ABREV', 'ET');


//Update frequency
define('TS_REFRESH_NOCONTENT', 60000); //msec
define('TS_REFRESH_CONTENT', 30000); //msec
define('TS_TWITTER_QUERY_FREQ', 20); //seconds


//Leaderboard configuration
define('TS_LEADERBOARD_MAXDISP', 20);
define('TS_LEADERBOARD_STDDISP', 10);
define('TS_LEADERBOARD_CANSHOWALL', false);


//Stats configuration
define('TS_CACHE_TTL', 300); //seconds
define('GRAPH_DATE_MIN', strtotime('today - 7 days 12:00 am UTC'));
define('GRAPH_DATE_MAX', strtotime('today + 1 day 12:00 am UTC'));
