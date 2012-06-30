<?php
/*
	Twitterslurp: Statistics Functions
	Author: John Bafford - http://bafford.com
	Copyright 2009 The Bivings Group
	http://www.bivings.com
*/

/*
	GetLeaderboardGraphData is one hour ahead so that the graph can show the "current" values pushed up against the next hour. (So tweets between 11 - 11:59 are counted for 12:00).
*/
function GetLeaderboardGraphData($searchID, $params)
{
	global $conn;
	
	$tTSM = DB_PREFIX . 'tweetSearchMatches';
	$tTweets = DB_PREFIX . 'tweets';
	
	if($searchID)
		$where = "INNER JOIN $tTSM tsm on tweets.tweetID = tsm.tweetID and tsm.searchID = $searchID";
	
	$total = 0;
	$lastDate = false;
	$arr = array();
	$d = mysql_query("select count(*), date_format(from_unixtime(tweetDate + 3600), '%Y-%m-%d %H') D from $tTweets $where group by D", $conn);
	while(list($count, $date) = mysql_fetch_row($d))
	{
		$total += (int)$count;
		
		$date = strtotime("$date:00 UTC");
		if($date >= GRAPH_DATE_MIN)
		{
			if($params['dataType'] == 'cumulative')
			{
				if(empty($arr) && $date >GRAPH_DATE_MIN)
					$arr[] = array(GRAPH_DATE_MIN, $total);
				
				$arr[] = array($date, $total);
			}
			else if($params['dataType'] == 'hourly')
			{
				if($lastDate)
				{
					while($lastDate < $date - 3600)
						$arr[] = array($lastDate += 3600, 0); //add a 0 value for the missing hour
				}
				
				$lastDate = $date;
				$arr[] = array($date, (int)$count);
			}
		}
	}
	
	return $arr;
}

function GetLeaderboardGraphDataDay($searchID)
{
	global $conn;
	
	$tTSM = DB_PREFIX . 'tweetSearchMatches';
	$tTweets = DB_PREFIX . 'tweets';
	
	if($searchID)
		$where = "INNER JOIN $tTSM tsm on tweets.tweetID = tsm.tweetID and tsm.searchID = $searchID";
	
	$arr = array();
	$d = mysql_query("select count(*), date_format(from_unixtime(tweetDate), '%Y-%m-%d') D from $tTweets $where group by D", $conn);
	while(list($count, $date) = mysql_fetch_row($d))
	{
		$date = strtotime("$date UTC");
		if($date >= GRAPH_DATE_MIN)
			$arr[] = array($date, (int)$count);
	}
	
	return $arr;
}

function GetLeaderboardGraphDataHour($searchID)
{
	global $conn;
	
	$tTSM = DB_PREFIX . 'tweetSearchMatches';
	$tTweets = DB_PREFIX . 'tweets';
	
	if($searchID)
		$where = "INNER JOIN $tTSM tsm on tweets.tweetID = tsm.tweetID and tsm.searchID = $searchID";
	
	$arr = array();
	$d = mysql_query("select count(*), date_format(from_unixtime(tweetDate), '%H') D from $tTweets $where group by D", $conn);
	while(list($count, $date) = mysql_fetch_row($d))
	{
		$arr[] = array(strtotime("1970-01-01 $date:00 UTC"), (int)$count);
	}
	
	return $arr;
}

function GetTopClients()
{
	global $conn;
	
	$tTweets = DB_PREFIX . 'tweets';
	
	$limit = 10;
	
	$num = 0;
	$other = 0;
	$arr = array();
	$d = mysql_query("select count(*) cnt, source from $tTweets group by source order by cnt desc", $conn);
	while(list($cnt, $source) = mysql_fetch_row($d))
	{
		$source = htmlspecialchars_decode($source);
		$source = str_replace('<a href', '<a rel="nofollow" href', $source);
		
		if($num++ >= $limit)
			$other += $cnt;
		else
		{
			$arr[] = array(
				'name' => $source,
				'count' => $cnt,
			);
		}
	}
	
	if($other)
	{
		$otherClients = $num - $limit;
		$arr[] = array(
			'name' => 'Other',
			'count' => "$other ($otherClients clients)",
		);
	}
	
	return $arr;
}

function GetStatConfig()
{
	return array(
		'graph-tweets' => array(
			'fn' => 'GetLeaderboardGraphData',
			'params' => array('dataType' => 'cumulative'),
			
			'config' => array(
				'type' => 'graph',
				
				'dataConfig' => array(
					'lines' => array(
						'show' => true,
						'fill' => true,
					),
				),
				
				'graphConfig' => array(
					'xaxis' => array(
						'mode' => 'time',
						'tickSize' => array(24, 'hour'),
						'minTickSize' => array(1, 'day'),
						'min' => GRAPH_DATE_MIN,
						'max' => GRAPH_DATE_MAX,
					),
					
					'yaxis' => array(
						'min' => 0
					),
					
					'grid' => array(
						'hoverable' => true,
					),
				),
			),
		),
		
		'graph-tweetsHourly' => array(
			'fn' => 'GetLeaderboardGraphData',
			'params' => array('dataType' => 'hourly'),
			
			'config' => array(
				'type' => 'graph',
				
				'dataConfig' => array(
					'lines' => array(
						'show' => true,
						'fill' => true,
					),
				),
				
				'graphConfig' => array(
					'xaxis' => array(
						'mode' => 'time',
						'tickSize' => array(24, 'hour'),
						'minTickSize' => array(1, 'day'),
						'min' => GRAPH_DATE_MIN,
						'max' => GRAPH_DATE_MAX,
					),
					
					'yaxis' => array(
						'min' => 0
					),
					
					'grid' => array(
						'hoverable' => true,
					),
				),
			),
		),
		
		'graph-tweetsDay' => array(
			'fn' => 'GetLeaderboardGraphDataDay',
			'params' => array(),
			
			'config' => array(
				'type' => 'graph',
				
				'dataConfig' => array(
					'bars' => array(
						'show' => true,
						'fill' => true,
						'barWidth' => 22 * 60 * 60 * 1000,
						'align' => 'center',
					),
				),
				
				'graphConfig' => array(
					'xaxis' => array(
						'mode' => 'time',
						'tickSize' => array(24, 'hour'),
						'minTickSize' => array(1, 'day'),
						'min' => GRAPH_DATE_MIN,
						'max' => GRAPH_DATE_MAX,
					),
					
					'yaxis' => array(
						'min' => 0
					),
					
					'grid' => array(
						'hoverable' => true,
					),
				),
			),
		),
		
		'graph-tweetsHour' => array(
			'fn' => 'GetLeaderboardGraphDataHour',
			'params' => array(),
			
			'config' => array(
				'type' => 'graph',
				
				'dataConfig' => array(
					'bars' => array(
						'show' => true,
						'fill' => true,
						'align' => 'center',
						'barWidth' => 45 * 60 * 1000,
					),
				),
				
				'graphConfig' => array(
					'xaxis' => array(
						'mode' => 'time',
						'min' => - 30 * 60,
						'max' => 23.5 * 60 * 60,
						'tickSize' => array(3, 'hour'),
					),
					
					'yaxis' => array(
						'min' => 0
					),
					
					'grid' => array(
						'hoverable' => true,
					),
				),
			),
		),
		
		'table-clients' => array(
			'fn' => 'GetTopClients',
			'params' => array(),
			
			'config' => array(
				'type' => 'table',
				
			),
		),
	);
}