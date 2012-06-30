#!/usr/local/php/bin/php
<?php
/*
	Twitterslurp - Twitter Search Daemon component
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

ini_set('include_path', dirname(__FILE__) . '/include:' . ini_get('include_path'
));
require_once('dbFunctions.php');
require_once('dbCommon.php');

function MainLoop()
{
	global $Interrupt;
	
	while(1)
	{
		switch($Interrupt)
		{
			case 'terminate':
				echo "Received terminate request.\n";
				return;
			
			case 'init':
			case 'rehash':
			case 'usr1':
				echo "Rehash...\n";
				break;
		}
		
		$Interrupt = false;
		
		$sleepDuration = TS_TWITTER_QUERY_FREQ;
		
		if(1)
		{
			passthru(dirname(__FILE__) . '/twitterSearch.php');
						
			echo "Requesting sleep until $nextUpdate ($sleepDuration)\n";
		}
		
		if($sleepDuration > 0)
			sleep($sleepDuration);
	}
}

//----------------------------------------------------------------------

function sig_handler($signo)
{
	global $Interrupt;
	
	switch($signo)
	{
		case SIGINT:
		case SIGTERM:
			$Interrupt = 'terminate';
			break;
		
		case SIGHUP:
			$Interrupt = 'rehash';
			break;
		
		case SIGUSR1:
			$Interrupt = 'usr1';
			break;
	}
	
	$pid = posix_getpid();
	echo "[$pid] Signal: $signo: $Interrupt\n";
}

//----------------------------------------------------------------------

$Interrupt = 'init';

if(function_exists('pcntl_signal'))
{
	//requires the pcntl module
	
	pcntl_signal(SIGTERM, 'sig_handler');
	pcntl_signal(SIGHUP, 'sig_handler');
	pcntl_signal(SIGINT, 'sig_handler');
	pcntl_signal(SIGUSR1, 'sig_handler');
}

MainLoop();