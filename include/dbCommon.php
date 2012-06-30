<?php
/*
	Twitterslurp - Database Functions
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

require_once('config.php');

function dbConnect($database = DBNAME, $user = DBUSER, $pass = DBPASS, $hostname = DBHOST)
{
	$conn = @mysql_connect($hostname, $user, $pass);
	
	if($conn)
		mysql_select_db($database, $conn);

	return $conn;
}

function GetSearches()
{
	global $conn;
	
	$arr = array();
	
	$tTS = DB_PREFIX . 'twitterSearches';
	$d = mysql_query("select * from $tTS", $conn);
	while($o = mysql_fetch_object($d))
		$arr[$o->searchID] = $o;
	
	return $arr;
}

function GetSearchData()
{
	static $sd;
	
	if(empty($sd))
	{
		$sd['searches'] = GetSearches();
		
		$sm = array();
		foreach($sd['searches'] as $s)
			$sm[$s->name] = $s->searchID;
		
		$sd['map'] = $sm;
	}
	
	return $sd;
}

