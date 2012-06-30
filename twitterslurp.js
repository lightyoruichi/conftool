/*
	Twitterslurp - JavaScript component
	Author: John Bafford - http://bafford.com
	Copyright 2009 The Bivings Group
	http://www.bivings.com
		
	Requires json2.js, jQuery, and jQuery.flot
	
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

function NewAJAXRequest(requestHandler, method, url)
{
	var xmlhttp;
	
	if(window.ActiveXObject)
		xmlhttp = new ActiveXObject('Microsoft.XMLHTTP');
	else if(window.XMLHttpRequest)
		xmlhttp = new XMLHttpRequest();
	
	if(xmlhttp)
	{
		xmlhttp.open(method, url, true);
		
		if(method == 'POST')
			xmlhttp.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded;');
		
		xmlhttp.onreadystatechange = function() { requestHandler(xmlhttp); }
	}
	
	return xmlhttp;
}

function NewStandardAJAXRequest(okHandler, method, url)
{
	var handler = function(xmlhttp)
	{
		if(xmlhttp.readyState == 4)
			if(xmlhttp.status == 200)
				okHandler(xmlhttp.responseText);
	}
	
	return NewAJAXRequest(handler, method, url);
}

//---------------------------------------------------------------------

TwitterSlurp = function TwitterSlurp(init)
{
	this.self = this;
	this.applicationURL = '';
	this.refreshTimeNoUpdates = 60000;
	this.refreshTimeUpdates = 30000;
	this.refreshTimeOnError = 60000;
	this.numErrorsToStop = 3;
	this.defaultNumTweets = 20;
	
	this.numErrors = 0
	this.lastError = null;
	var state = 'stopped';
	var timeoutID = null;
	
	//-----------------------------------------------------------------
	//These functions are standard implementations of the display maintenance functions
	//You can override these as necessary.
	
	this.MakeNewTweet = function(tweet)
	{
		var url = 'http://twitter.com/' + tweet.fromUser;
		
		var out = '<div class="tweet" id="tweet_' + tweet.tweetID + '"><span><span class="avatar"><a href="' + url + '"><img src="' + tweet.profileImage + '" height="48" width="48" /></a></span>';
		
		out += '<span><b><a href="' + url + '">' + tweet.fromUser + '</a></b></span> <span class="tweetmsg">' + tweet.tweet + '</span> <span>(at ' + tweet.tweetDate + ')</span></span></div>';
		
		return out;
	}
	
	this.MakeNewLeaderboardEntry = function(leader)
	{
		return '<div><img src="' + leader.profileImage + '" height="48" width="48"><b>' + leader.fromUser + '</b>: ' + leader.count + '</div>';
	}
	
	this.MakeNewLeaderboardTotalsEntry = function(total)
	{
		return '';
	}
	
	this.RemoveTweet = function(jqueryElts)
	{
		jqueryElts.remove();
	}
	
	this.StateHasChanged = function(oldState, newState)
	{
	}
	
	//-----------------------------------------------------------------
	
	this.removeOldTweets = function removeOldTweets(elt, numToDisplay)
	{
		this.RemoveTweet($(elt).children('.tweet').slice(numToDisplay));
	}
	
	this.setSearchContent = function setSearchContent(searchID, results, tweetType)
	{
		var out = '';
		var cnt = 0;
		
		if(typeof results.tweets != 'undefined')
			for(var tweetID in results.tweets)
			{
				var tweet = results.tweets[tweetID];
				
				out += this.MakeNewTweet(tweet);
				
				cnt++;
			}
		
		if(cnt > 0)
		{
			var elt = $('#' + searchID);
			
			if(tweetType == 'newTweets')
			{
				var maxDisp = elt[0].childElementCount;
				
				var md = elt[0].attributes.getNamedItem('maxDisp');
				if(md && md.value > maxDisp)
					maxDisp = md.value;
				
				if(!maxDisp)
					maxDisp = this.defaultNumTweets;
				
				
				elt.prepend(out);
				this.removeOldTweets(elt[0], maxDisp);
			}
			else if(tweetType == 'oldTweets')
			{
				elt.append(out);
			}
		}
		
		return cnt;
	}
	
	this.MakeNewStatsTableEntry = function MakeNewStatsTableEntry(item)
	{
		return '<li class="leader"><span><b>' + item.name + '</b>: ' + item.count + '</span></li>';
	}
	
	this.graphShowTooltip = function graphShowTooltip(statID, stat, x, y, contents)
	{
		$('<div id="tooltip">' + contents + '</div>').css( {
			'position': 'absolute',
			'display': 'none',
			'top': y + 5,
			'left': x + 5,
			'border': '1px solid #fdd',
			'padding': '2px',
			'background-color': '#fee',
			'opacity': 0.80
		}).appendTo('body').fadeIn(200);
	}
	
	this.setStats = function setStats(stats, newID)
	{
		if(typeof newID.stats == 'undefined')
			newID.stats = {};
		
		var previousPoint = null;
		
		for(var statID in stats)
		{
			newID.stats[statID] = statID;
			
			if(typeof stats[statID] == 'object')
			{
				var stat = stats[statID];
				
				if(stat.type == 'table')
				{
					var out = '';
					for(var name in stat.data)
						out += this.MakeNewStatsTableEntry(stat.data[name]);
					
					$('#' + statID).html(out);
				}
				else if(stat.type == 'graph')
				{
					var dataItem = stat.dataConfig;
					dataItem.data = stat.data;
					
					var graphConfig = stat.graphConfig;
					if(graphConfig.xaxis.mode == 'time')
					{
						for(var i = 0; i < dataItem.data.length; i++)
							dataItem.data[i][0] *= 1000;
						
						graphConfig.xaxis.min *= 1000;
						graphConfig.xaxis.max *= 1000;
					}
					
					var tooltipFn = this.graphShowTooltip;
					$.plot($('#' + statID), [ dataItem ], graphConfig);
					$('#' + statID).bind('plothover', function(event, pos, item) {
						if(item)
						{
							if(previousPoint != item.datapoint)
							{
								previousPoint = item.datapoint;
								
								$('#tooltip').remove();
								
								tooltipFn(statID, stat, item.pageX, item.pageY, item.datapoint[1]);
							}
						}
						else
						{
							$('#tooltip').remove();
							previousPoint = null;
						}
					});
				}
			}
		}
	}

	this.setLeaderboard = function setLeaderboard(leaderboard, newID)
	{
		for(var lbID in leaderboard)
		{
			newID.leaderboard[lbID] = {};
			
			if(typeof leaderboard[lbID] == 'object')
			{
				var lb = leaderboard[lbID];
				if(typeof lb.maxDisp != 'undefined')
					newID.leaderboard[lbID].maxDisp = lb.maxDisp;
				
				var out = '';
				for(var name in lb.users)
					out += this.MakeNewLeaderboardEntry(lb.users[name]);
				
				out += this.MakeNewLeaderboardTotalsEntry(lb.total);
				
				$('#' + lbID).html(out);
			}
		}
	}
	
	this.setSearches = function setSearches(searches, newID, tweetType)
	{
		var gotNew = 0;
		
		for(var searchID in searches)
		{
			var results = searches[searchID];
			
			gotNew += this.setSearchContent(searchID, results, tweetType);
			
			newID.search[searchID] = results.maxID;
		}
		
		return gotNew;
	}
	
	this.setMoreSearches = function setMoreSearches(searches)
	{
		var newID = { 'search': {} };
		
		this.setSearches(searches, newID, 'oldTweets');
	}
	
	this.changeState = function changeState(newState)
	{
		var oldState = state;
		
		state = newState;
		
		this.StateHasChanged(oldState, newState);
	}
	
	this.queueRefresh = function queueRefresh(theQuery, refreshTimeDelay)
	{
		var self = this;
		
		timeoutID = window.setTimeout( function() { self.refresh(theQuery); }, refreshTimeDelay);
		
		this.changeState('waiting');
	}
	
	this.addError = function addError(errMsg, theQuery)
	{
		this.lastError = errMsg;
		this.numErrors++;
		this.changeState('error');
		
		if(this.numErrors >= this.numErrorsToStop)
		{
			this.changeState('stopped');
			return false;
		}
		else
		{
			this.queueRefresh(theQuery, this.refreshTimeOnError);
			return true;
		}
	}
	
	this.RefreshHandler = function RefreshHandler(self, responseText, theQuery)
	{
		var refreshTime;
		var gotNew = 0;
		var response;
		var newID = {
			'search': {},
			'leaderboard': {},
			'stats': {}
		};
		
		self.changeState('running');
		
		try
		{
			response = JSON.parse(responseText);
		}
		catch(err)
		{
			return self.addError(err, theQuery);
		}
		
		if(response.error)
		{
			//Assume this is a temporary error and retry
			return self.addError(response.error, theQuery);
		}
		else
		{
			self.setSearches(response.searches, newID, 'newTweets');
			self.setLeaderboard(response.leaderboard, newID);
			self.setStats(response.stats, newID);
			self.numErrors = 0;
		}
		
		if(typeof response.refreshTime != 'undefined')
			refreshTime = response.refreshTime;
		else if(gotNew == 0)
			refreshTime = self.refreshTimeNoUpdates;
		else
			refreshTime = self.refreshTimeUpdates;
		
		self.queueRefresh(newID, refreshTime);
		
		return true;
	};
	
	this.ShowMoreRefreshHandler = function ShowMoreRefreshHandler(self, responseText)
	{
		var response;
		
		try
		{
			response = JSON.parse(responseText);
		}
		catch(err)
		{
			return false;
		}
		
		self.setMoreSearches(response.searches);
	};
	
	this.makeShowMoreRequest = function makeShowMoreRequest()
	{
		var newID = {
			'search': {}
		};
		
		$('.twitterSearchResults').each(function(index) {
			var tweets = $('#' + this.id + ' .tweet');
			var lastTweet = tweets.eq(tweets.length - 1);
			var minID = lastTweet.attr('id');
			
			minID = minID.replace('tweet_', '');
			
			newID.search[this.id] = {
				'task': 'olderThan',
				'tweetID': minID
			};
		});
		
		return newID;
	}
	
	this.showMore = function showMore()
	{
		var newID = this.makeShowMoreRequest();
		
		var self = this;
		var rh = function(responseText) { self.ShowMoreRefreshHandler(self, responseText); };
		ajax = NewStandardAJAXRequest(rh, 'POST', this.applicationURL);
		ajax.send('newID=' + encodeURIComponent(JSON.stringify(newID)));
	};
	
	this.refresh = function refresh(newID)
	{
		if(!this.applicationURL)
		{
			alert('twitterslurp: you must specify the application url');
			return;
		}
		
		this.changeState('querying');
		
		var self = this;
		var rh = function(responseText) { self.RefreshHandler(self, responseText, newID); };
		ajax = NewStandardAJAXRequest(rh, 'POST', this.applicationURL);
		ajax.send('newID=' + encodeURIComponent(JSON.stringify(newID)));
	};
	
	this.bootstrapContent = function bootstrapContent()
	{
		var newID = {
			'search': {},
			'leaderboard': {},
			'stats': {}
		};
		
		$('.twitterSearchResults').each(function(index) {
			newID.search[this.id] = 0;
		});
		
		$('.twitterLeaderboard').each(function(index) {
			newID.leaderboard[this.id] = {
				maxDisp: this.getAttribute('maxDisp')
			};
		});
		
		$('.twitterStats').each(function(index) {
			newID.stats[this.id] = 0;
		});
	
		return newID;
	};
	
	this.start = function start()
	{
		if(state == 'stopped')
		{
			this.refresh(this.bootstrapContent());
		}
	};
	
	this.stop = function stop()
	{
		if(timeoutID)
		{
			clearTimeout(timeoutID);
			this.timeoutID = null;
		}
		
		this.changeState('stopped');
	}
	
	if(typeof init == 'function')
		init(this);
}