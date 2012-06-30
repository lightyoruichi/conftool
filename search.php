<?php
/*
	Twitterslurp: PHP Search Component
	Author: John Bafford - http://bafford.com
	Copyright 2009 The Bivings Group
	http://www.bivings.com
	
	Permission is hereby granted, free of charge, to any person
	obtaining a copy of this software and associated documentation
	files (the "Software"), to deal in the Software without
	restriction, including without limitation the rights to use,
	copy, modify, merge, publish, distribute, sublicense, and/or sell
	copies of the Software, and to permit persons to whom the
	Software is furnished to do so, subject to the following
	conditions:

	The above copyright notice and this permission notice shall be
	included in all copies or substantial portions of the Software.

	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
	EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
	OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
	NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
	HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
	WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
	FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
	OTHER DEALINGS IN THE SOFTWARE.
*/

require_once('dbCommon.php');
require_once('statsFunctions.inc');

function SetTimeZone()
{
	global $conn;
	
	date_default_timezone_set(TS_TZ);
	
	$date = date_create();
	$offset = $date->getOffset();
	
	$hours = abs($offset) / 3600;
	$mim = abs($offset) % 60;
	
	$hours = str_pad($hours, 2, '0', STR_PAD_LEFT);
	$min = str_pad($min, 2, '0', STR_PAD_LEFT);
	
	if($offset >= 0)
		$hours = '+' . $hours;
	else
		$hours = '-' . $hours;
	
	$offset = "$hours:$min";
	
	mysql_query("set time_zone = '$offset'", $conn);
}

//---------------------------------------------
/*
	Cache Functions.
	
	We require APC for caching. If it's not installed, then no caching.
*/

function TSCacheGet($cacheID)
{
	if(function_exists('apc_fetch'))
	{
		$data = apc_fetch('slurpGraph_data' . $cacheID);
		if($data)
			return unserialize($data);
	}
	
	return false;
}

function TSCacheSet($cacheID, $data)
{
	if(function_exists('apc_store'))
		apc_store('slurpGraph_data' . $cacheID, serialize($data), TS_CACHE_TTL);
}

//---------------------------------------------

function url_to_link($text) 
{ 
	$text =
	preg_replace('!(^|([^\'"]\s*))' .
	'([hf][tps]{2,4}:\/\/[^\s<>"\'()]{4,})!mi',
	'$2<a href="$3" rel="nofollow">$3</a>', $text);
	
	$text =
	preg_replace('!<a href="([^"]+)[\.:,\]]">!',
	'<a href="$1" rel="nofollow">', $text);
	
	$text = preg_replace('!([\.:,\]])</a>!', '</a>$1', $text);
	
	return $text;
}

function ProcessTweetText($str)
{
	$str = url_to_link($str);
	
	$str = preg_replace('/(#\w+)/', '<span class="hashtag">$1</span>', $str);
	
	$str = preg_replace('/@(\w+)/', '<a href="http://twitter.com/$1" class="atuser">@$1</a>', $str);
	
	return $str;
}

function GetSearchResponse($searchID, $params)
{
	global $conn;
	
	if(is_scalar($params))
	{
		$params = array(
			'task' => 'getNewer',
			'tweetID' => $params,
		);
	}
	else
		$params = (array)$params;
	
	$tweetID = mysql_real_escape_string($params['tweetID'], $conn);
	
	switch($params['task'])
	{
		case 'getNewer':
			$arr = array(
				'maxID' => $params['tweetID'],
			);
			
			$criteria = "TSM.tweetID > '$tweetID'";
			break;
		
		case 'olderThan':
			$arr = array(
				'maxID' => 0,
			);
			
			$criteria = "TSM.tweetID < '$tweetID'";
			break;
		
		default:
			return false;
	}
	
	$maxDisp = 20;
	$tweets = array();
	
	$tTSM = DB_PREFIX . 'tweetSearchMatches';
	$tTweets = DB_PREFIX . 'tweets';
	$d = mysql_query("select * from $tTSM TSM inner join $tTweets T using(tweetID) where TSM.searchID = $searchID and $criteria order by TSM.tweetID desc limit $maxDisp", $conn);
	while($o = mysql_fetch_object($d))
	{
		$o->tweetStrDate = date('F j, Y g:i a', $o->tweetDate) . ' ' . TS_TZ_ABREV;
#		$o->tweetDate = getdate($o->tweetDate);
		
		$o->rawTweet = $o->tweet;
		$o->tweet = ProcessTweetText($o->tweet);
		
		//We don't return the tweetID as the key because Chrome randomly sorts the array then.
		$tweets[] = $o;
		
		//if($arr['maxID'] < $o->tweetID)
		if(bccomp($arr['maxID'], $o->tweetID) == -1)
			$arr['maxID'] = $o->tweetID;
	}
	
	if($tweets)
		$arr['tweets'] = $tweets;
	
	return $arr;
}

function GetStatsData($type, $config, $searchID)
{
	$cacheID = '_' . $type . '_' . $searchID;
	
	$data = TSCacheGet($cacheID);
	if(!$data)
	{
		$fn = $config['fn'];
		$data = $fn($searchID, $config['params']);
		
		TSCacheSet($cacheID, $data);
	}
	
	return $data;	
}

function GetStats($searchFor)
{
	$statConfig = GetStatConfig();
	
	$searchData = GetSearchData();
	$sm = $searchData['map'];
	
	$arr = array();
	if($searchFor->stats)
		foreach($searchFor->stats as $type => $typeData)
		{
			$searchID = 0;
			if(preg_match('/^([^_]*)(_(.*))?/', $type, $pm))
			{
				$searchName = $pm[3];
				if($searchName)
					$searchID = $sm[$searchName];
			}
			
			$config = $statConfig[$type];
			if(!$config)
				continue;
			
			$data = GetStatsData($type, $config, $searchID);
			if(!$data)
				continue;
			
			$name = $type;
			if($searchName)
				$name .= '_' . $searchName;
			
			$arr[$name] = $config['config'];
			$arr[$name]['data'] = $data;
		}
	
	return $arr;
}

function GetLeaderboard($searchFor)
{
	global $conn;
	
	$searchData = GetSearchData();
	$sm = $searchData['map'];
	
	$arr = array();
	
	$tTweets = DB_PREFIX . 'tweets';
	$tTU = DB_PREFIX . 'twitterUsers';
	$tTSM = DB_PREFIX . 'tweetSearchMatches';
	foreach((array)$searchFor->leaderboard as $name => $leaderboard)
	{
		if(empty($leaderboard->maxDisp))
			$numDisp = TS_LEADERBOARD_STDDISP;
		else
			$numDisp = $leaderboard->maxDisp;
		
		if((!TS_LEADERBOARD_CANSHOWALL && $numDisp < 1) || $numDisp > TS_LEADERBOARD_MAXDISP)
			$numDisp = TS_LEADERBOARD_MAXDISP;
		
		if($numDisp > 0)
			$limit = "limit $numDisp";
		else
			$limit = '';
		
		if(preg_match('/^leaderboard(.*)/', $name, $pm))
		{
			$searchName = $pm[1];
			if($searchName)
			{
				$searchName = substr($searchName, 1);
				$searchID = $sm[$searchName];
			}
			else
				$searchID = 0;
			
			if($searchID)
				$where = "INNER JOIN $tTSM tsm on tweets.tweetID = tsm.tweetID and tsm.searchID = $searchID";
			
			$lb = array(
				'maxDisp' => $numDisp,
				'users' => array(),
			);
			
			$d = mysql_query("select fromUser, count, TU.profileImage from (select fromUser, count(*) count from $tTweets $where group by fromUser order by count desc $limit) TW left join $tTU TU on TW.fromUser=TU.username", $conn);
			while($o = mysql_fetch_object($d))
				$lb['users'][$o->fromUser] = $o;
			
			$d = mysql_query("select count(*) tweets, count(distinct fromUser) users from $tTweets $where", $conn);
			$lb['total'] = mysql_fetch_object($d);
			
			$arr[$name] = $lb;
		}
	}
	
	return $arr;
}

function GetNewSearches($searchFor)
{
	$searchData = GetSearchData();
	$searches = $searchData['searches'];
	$sm = $searchData['map'];
	
	$searchRequest = array();
	
	if(is_object($searchFor->search))
		foreach($searchFor->search as $searchKey => $newID)
		{
			if(is_numeric($searchKey))
			{
				if(isset($searches[$searchKey]))
					$searchRequest[$searchKey] = $newID;
			}
			else
			{
				if(preg_match('/^search_(.*)/', $searchKey, $arr))
					if(isset($sm[$arr[1]]))
						$searchRequest[$sm[$arr[1]]] = $newID;
			}
		}
	
	$arr = array();
	foreach($searchRequest as $searchID => $minID)
		$arr['search_' . $searches[$searchID]->name] = GetSearchResponse($searchID, $minID);
	
	return $arr;
}

function DoSearch()
{
	global $conn;
	
	$anyUpdates = false;
	$searchFor = false;
	$response = array(
		'searches' => array(),
		'leaderboard' => array(),
		'stats' => array(),
		'refreshTime' => TS_REFRESH_NOCONTENT,
	);
	
	if(!empty($_POST['newID']))
	{
		$theSearch = $_POST['newID'];
		if(get_magic_quotes_gpc())
			$theSearch = stripslashes($theSearch);
		
		$searchFor = json_decode($theSearch);
		if($searchFor)
		{
			$conn = dbConnect();
			if(!$conn)
			{
				return array(
					'error' => 'Database error',
				);
			}
			
			SetTimeZone();
			
			$searches = GetNewSearches($searchFor);
			foreach($searches as $sr)
				if($sr['tweets'])
				{
					$anyUpdates = true;
					break;
				}
			
			if($anyUpdates || !$searches)
			{
				$leaderboard = GetLeaderboard($searchFor);
				$stats = GetStats($searchFor);
			}
			else
			{
				$leaderboard = array();
				foreach(array_keys((array)$searchFor->leaderboard) as $k)
					$leaderboard[$k] = 0;
				
				$stats = array();
				foreach(array_keys((array)$searchFor->stats) as $k)
					$stats[$k] = 0;
			}
			
			if($anyUpdates)
				$refreshTime = TS_REFRESH_CONTENT;
			else
				$refreshTime = TS_REFRESH_NOCONTENT;
			
			$response['searches'] = $searches;
			$response['leaderboard'] = $leaderboard;
			$response['stats'] = $stats;
			$response['refreshTime'] = $refreshTime;
		}
	}
	
	return $response;
}

echo json_encode(DoSearch());
