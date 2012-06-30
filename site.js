/*
	Twitterslurp - JavaScript component (site-specific configuration)
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

var ts = new TwitterSlurp(function(self){
	self.applicationURL = 'search.php';
	self.replyHashtag = '#twitterslurp';
	
	
	self.makeRTLink = function(tweet)
	{
		var status = 'RT @' + tweet.fromUser + ': ' + tweet.rawTweet;
		
		var url = 'http://twitter.com/home?status=' + encodeURIComponent(status) + '&in_reply_to_status_id=' + tweet.tweetID + '&in_reply_to=' + tweet.fromUser;
		
		return '<a href="' + url + '" target="_new">';
	}
	
	self.MakeNewTweet = function(tweet, cnt)
	{
		var url = 'http://twitter.com/' + tweet.fromUser;
		
		var out = '<li class="tweet" id="tweet_' + tweet.tweetID + '"><span class="avatar"><a href="' + url + '"><img src="' + tweet.profileImage + '" height="48" width="48" /></a></span>';
		
		out += '<span class="tweetBody"><span><b><a href="http://twitter.com/' + tweet.fromUser + '">' + tweet.fromUser + '</a></b></span> <span class="tweetmsg">' + tweet.tweet + '</span><br /><span class="tweetTime">' + tweet.tweetStrDate + '</span><span style="font-size: 11px;"> <a href="http://twitter.com/home?status=@' + tweet.fromUser + '%20%20' + encodeURIComponent(self.replyHashtag) + '&in_reply_to_status_id=' + tweet.tweetID + '&in_reply_to=' + tweet.fromUser + '" target="_new">(Reply)</a></span> <span style="font-size: 11px;">' + self.makeRTLink(tweet) + '(RT)</a></span></span></li>';
		
		return out;
	}
	
	self.MakeNewLeaderboardEntry = function(leader)
	{
		var url = 'http://twitter.com/' + leader.fromUser;
		
		return '<li class="leader"><span class="avatar"><a href="' + url + '"><img src="' + leader.profileImage + '" height="48" width="48" /></a></span> <span class="tweetBody"><b><a href="' + url + '">' + leader.fromUser + '</a></b>: ' + leader.count + '</span></li>';
	}
	
	self.MakeNewLeaderboardTotalsEntry = function(total)
	{
		return '<li class="leaderTotals">Total Tweets: ' + total.tweets + '<br />Total People: ' + total.users + '</li>';
	}
	
	self.RemoveTweet = function(jqueryElts)
	{
		jqueryElts.remove();
	}
	
	self.StateHasChanged = function(oldState, newState)
	{
		if(this.numErrors)
			$('#tsState').html(newState + ' (' + this.numErrors + ' errors: ' + this.lastError + ')');
		else
			$('#tsState').html(newState);
		
		if(oldState == 'stopped')
		{
			$('#tsStop').show();
			$('#tsStart').hide();
		}
		else if(newState == 'stopped')
		{
			$('#tsStop').hide();
			$('#tsStart').show();
		}
	}
});



$(function() { ts.start(); } );
