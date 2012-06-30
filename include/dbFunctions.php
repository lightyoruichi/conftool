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

/*
	Use in conjunction with Build(Updated|Insert)String to specify a value
	that shouldn't be quoted.
*/
class DBUnquotedString
{
	public $value;
	
	function __construct($str)
	{
		$this->value = $str;
	}
}

/*
	Build an update query string that can be passed to mysql_query.
	$arr is an associative array of [column] = value
	If arr[key] === false, value is NULL.
*/
function BuildUpdateString($conn, $table, $where, &$arr)
{
	if(count($arr))
	{
		$qs = '';
		
		foreach($arr as $k => $v)
		{
			if($v === false)
				$qs .= "$k = NULL,";
			else if(is_object($v))
				$qs .= "$k = $v->value,";
			else
			{
				$v = mysql_real_escape_string($v, $conn);
				$qs .= "$k = '$v',";
			}
		}
		
		$qs = substr($qs, 0, -1);
		return "update $table set $qs where $where";
	}
	else
		return false;
}

/*
	Build an insert query string that can be passed to mysql_query.
	$arr is an associative array of [column] => value.
	If arr[key] === false, value is NULL.
	If $multi == true, a multiple insert/replace statement is created, using the field list from the first array. Missing elements in subsequent arrays are NULL, extra ones are ignored.
*/
function BuildInsertString($conn, $table, $inArr, $replace = false, $multi = false)
{
	if(count($inArr))
	{
		if(!$multi)
			$inArr = array($inArr);
		
		$vals = array();
		foreach($inArr as $arr)
		{
			if(!isset($varsArr))
				$varsArr = array_keys($arr);
			
			$valArr = array();
			foreach($varsArr as $k)
			{
				$v = $arr[$k];
				
				if($v === false)
					$valArr[] = 'NULL';
				else if(is_object($v))
					$valArr[] = $v->value;
				else
				{
					$v = mysql_real_escape_string($v, $conn);
					
					$valArr[] = "'$v'";
				}
			}
			
			$vals[] = '(' . implode(',', $valArr) . ')';
		}
		
		if($replace)
			$insert = 'replace';
		else
			$insert = 'insert';
		
		$vars = implode(',', $varsArr);
		$vals = implode(',', $vals);
		return "$insert into $table($vars) values $vals";
	}
	else
		return false;
}

/*
	Build an insert query string  with ON DUPLICATE KEY UPDATE that can be passed to mysql_query.
	$arr is an associative array of [column] => value.
	If arr[key] === false, value is NULL.
*/
function _BuildInsertODKUString($conn, $table, $arr)
{
	if(count($arr))
	{
		$vals = array();
		
		$valArr = array();
		foreach($arr as $k => $v)
		{
			if($v === false)
				$v = 'NULL';
			else if(is_object($v))
				$v = $v->value;
			else
			{
				$v = mysql_real_escape_string($v, $conn);
				
				$v = "'$v'";
			}
			
			$valArr[] = "$k = $v";
		}
		
		$insert = 'insert';
		
		$vals = implode(',', $valArr);
		return "$insert into $table set $vals ON DUPLICATE KEY UPDATE $vals";
	}
	else
		return false;
}

function makeSQLInList($conn, $arr, $var, $in = true)
{
	$str = '';
	
	if(!is_array($arr))
		$arr = array($arr);
	
	foreach($arr as $v)
	{
		$v = mysql_real_escape_string($v, $conn);
		$str .= "'$v', ";
	}
	
	$str = substr($str, 0, -2);
	
	if(count($arr) == 1)
	{
		if($in)
			$in = '=';
		else
			$in = '<>';
	}
	else
	{
		if($in)
			$in = 'in';
		else
			$in = 'not in';
	}
	
	return "$var $in ($str)";
}
