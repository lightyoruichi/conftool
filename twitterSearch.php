#!/usr/local/php/bin/php
<?php
/*
	Twitterslurp - Twitter Search component
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

ini_set('include_path', dirname(__FILE__) . '/include:' . ini_get('include_path'));
require_once('dbFunctions.php');
require_once('dbCommon.php');


//---------------------------------------------

function DoTwitterSearch($search, $extraTerms = array())
{
	$query = array(
		'q' => $search->term,
		'since_id' => $search->lastTweetID,
		'rpp' => 100,
	);
	
	if($extraTerms)
		$query += $extraTerms;
	
	$query = http_build_query($query);
	
	$url = "http://search.twitter.com/search.json?$query";
	
	$response = file_get_contents($url);
	if($response)
	{
		$response = json_decode($response);
		
		if(!empty($response->next_page))
		{
			$more = DoTwitterSearch($search, array('page' => $response->page + 1, 'max_id' => $response->max_id));
			
			if(!$more) //we failed getting data from Twitter. Bail out.
				return false;
			
			$response->results = array_merge($response->results, $more->results);
		}
		
		return $response;
	}
	
	return false;
}

function UpdateSearchResults($search, $results)
{
	global $conn;
	
	foreach($results->results as $r)
	{
		$arr = array(
			'tweetID' => $r->id,
			'tweetDate' => strtotime($r->created_at),
			'languageCode' => $r->iso_language_code,
			'tweet' => $r->text,
			'source' => $r->source,
			'profileImage' => $r->profile_image_url,
			'fromUser' => $r->from_user,
		);
		if(!empty($r->to_user))
			$arr['toUser'] = $r->to_user;
		
		$qs = BuildInsertString($conn, DB_PREFIX . 'tweets', $arr);
		$ok = mysql_query($qs, $conn);
		
		$arr = array(
			'tweetID' => $r->id,
			'searchID' => $search->searchID,
		);
		$qs = BuildInsertString($conn, DB_PREFIX . 'tweetSearchMatches', $arr);
		$ok = mysql_query($qs, $conn);
		
		$arr = array(
			'username' => $r->from_user,
			'profileImage' => $r->profile_image_url,
		);
		$qs = _BuildInsertODKUString($conn, DB_PREFIX . 'twitterUsers', $arr);
		$ok = mysql_query($qs, $conn);
		
		echo "Added tweet $search->name $r->id $r->from_user\n";
	}
	
	$arr = array(
		'searchTime' => $search->searchtime = time(),
	);
	
	if($results->max_id != -1)
		$arr['lastTweetID'] = $search->lastTweetID = $results->max_id;

	
	$qs = BuildUpdateString($conn, DB_PREFIX . 'twitterSearches', "searchID = $search->searchID", $arr);
	mysql_query($qs, $conn);
}

function DoSearch($search)
{
	$results = DoTwitterSearch($search);
	
	if($results)
		UpdateSearchResults($search, $results);
	else
		echo "Search failed.\n";
}

function DoSearches($searches)
{
	$date = date('Y-m-d H:i:s');
	
	echo "\nRunning twitter searches at $date\n";
	
	foreach($searches as $search)
	{
		DoSearch($search);
	}
}

//----------------------------------------------------------------------

$conn = dbConnect();


$searches = GetSearches();

DoSearches($searches);
