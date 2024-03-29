﻿<!DOCTYPE HTML>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta http-equiv="Content-Language" content="en-us" />
<title><?php echo $pagesetup['title'];?></title>
<link rel="shortcut icon" href="/assets/images/favicon.ico" /> 
<?php foreach($pagesetup['styles'] as $file => $type) { echo HTML::style($file, array('media' => $type)), "\n"; }?>
<?php foreach($pagesetup['scripts'] as $file) { echo HTML::script($file, NULL, TRUE), "\n"; }?>
<script type="text/javascript"> 
 
function errorshow(html) {
	$('#notice').html(html);
	$('#notice').slideDown(1000);
}
function errorclear() {
	$('#notice').slideUp(500);
	$('#notice').html('');
}

$(document).ready(function() {
<?php // Error display
if (isset($error))  {
	 if (is_array($error)) {
		 $error = implode('<br>',$error);
	 }?>
	$('#notice').html('<?php echo $error; ?>');
	$('#notice').slideDown(1000);
<?php } //endif ?>
});

 
//convert seconds to minutes:seconds
function stom(time){
  seconds = time%60;
  minutes = parseInt(time/60);
  if (seconds < 10) { 
    seconds = "0" + seconds.toString(); //add zero to single digit numbers
  } else {
    seconds = seconds.toString();
  }
  return minutes.toString() + ':' + seconds;
}
 
/*** vote handling stuff ***/
 
function voteHandler(event) {
  tgt = $(event.target);
  votelink = event.target.href;
  if (tgt.hasClass('voted')) {
    //clicking again to clear vote
    votelink = votelink.slice(0, votelink.length-1); //get rid of vote at end
    $.get(votelink + '0'); //vote 0 to clear vote
    tgt.removeClass("voted");
  } else {
    $.get(votelink);
    tgt.parent().children().removeClass("voted");
    tgt.addClass("voted");
  }
  event.preventDefault();
}
 
function addVoteHandlers () {
  $("a[href*='/vote?']").not('.handled').click(voteHandler).addClass('handled');
}

/*** progress bar stuff ***/
 
//both of these are in seconds
var songLength = <?php echo $now_playing['length']; ?>;
var songPosition = <?php echo $now_playing['position']; ?>;
//percentage
var songProgress = <?php echo $now_playing['progress']; ?>; 
 
function setSongLength(length) {
  songLength = length;
  $('#songLength').html(stom(length));
  
}
 
//set numeric song position
function setSongPosition(pos) {
  pos = Math.round(Math.min(pos, songLength));
  if (songPosition != pos) {
    $('#songPosition').html(stom(pos));
    songPosition = pos;
    setSongProgress((pos/songLength)*100);
  }
  ajax_args['position'] = pos;
}
 
//set progressbar percentage
function setSongProgress(percentage) {
  $("#progbar").css("width", percentage.toString() + '%');
  //alert(percentage.toString() * '%');
  songProgress = percentage;
}
 
//Increment song position every second
//TODO: make this more accurate using clock to avoid inaccuracies of delyed calls
function secondLoop() {
  setSongPosition(songPosition+1);
  setTimeout('secondLoop()', 1000);
}

/*** Clutterbar song info stuff ***/

//Set metadata to appear in clutterbar
function setClutterMetadata(metadata) {
  elem = $("div#metadata");
  old = elem.html();
  if (metadata != old) {
    elem.html(metadata);
  }
}
 
/**
* Sets up the quick search box properly
**/
function setupSearch() {
  searchbox = $('#id_query');
  searchbox.css('color', 'grey');
  searchbox.attr('value', "quick search"); //override browser modifications
  searchbox.focus(function () {
    if (searchbox.attr('value') == "quick search") {
      searchbox.attr('value', '');
      searchbox.css('color', 'black');
    }
  });
}

/**
* Set up comment box properly
**/
function setupComment() {
  $('#commentform').submit(commentSubmit);
  commentbox = $('#commentbox');
  commentbox.removeAttr("disabled") //enable it
  commentbox.css('color', 'grey');
  message = "Comment on this track here. Bad things will happen to people who use this for general chat!";
  commentbox.attr('value', message);
  
  commentbox.focus(function () {
    if (commentbox.attr('value') == message) {
      //clear it and set text to normal colour when in focus
      commentbox.attr('value', '');
      commentbox.css('color', 'black');
    }
  });
  
  commentbox.blur(function () {
    if (commentbox.attr('value') == '') {
      //clear it and set text to normal colour when in focus
      commentbox.attr('value', message);
      commentbox.css('color', 'grey');
    }
  });
}

 
/**
* Hide message box
**/
function hideMessages() {
  $("p#messages").fadeOut("slow");
}

/**
* Add comment to comment box
**/
function addComment(comment, time, commenter, title) {
  elem = $("div#commentsbox");
  elem.html("<p class='comment' title='title'>" + "@" + time + " &lt;" + commenter + "&gt; " + comment + "\n" + "</p>" + elem.html());
}

/**
* Clear the comment box
**/
function clearComments() {
  $("div#commentsbox").html("");
}

/**
* Submit a comment entered in the comment box
**/
function commentSubmit() {
  commentbox = $('#commentbox');
  comment = commentbox.val();
  args = {};
  for (var i in ajax_args){
    args[i] = ajax_args[i];
  }
  
  args['comment'] = comment;
  $.getJSON("/ajax", args, eventDispatcher);
  
  commentbox.val("");
  return false; //avoid default behaviour
}
  
/**
* Score stuff
**/

function setScore(score) {
  if (score == 0) {
    score = "none";
  }
  $('#avgscore').html(score);
  // update playlist if necessary
  $('.playing .score').html(score);
}

function setUserVote(score) {
  $("#votebuttonslow > .selectedvote[data-vote!="+score+"]").removeClass("selectedvote");
  $("#votebuttonslow > *[data-vote="+score+"]").addClass("selectedvote");
  //update playlist if necessary
  $(".playing .votes a[data-vote!="+score+"]").removeClass("voted");
  $(".playing .votes a[data-vote="+score+"]").addClass("voted");
}

function setupVotes() {
  $("#votebuttonslow > a").click(function () {
    args = {};
    for (var i in ajax_args) {
      args[i] = ajax_args[i];
    }
    button = $(this);
    if (button.hasClass("selectedvote")) {
      args['vote'] = 0;
    } else {
      args['vote'] = button.attr("data-vote");
    }
    $.getJSON("/api", args, eventDispatcher);
    
  });
}
 
/*** event handlers for clutterbar ***/
 
function songLengthHandler(e, length) {
  setSongLength(length);
}
 
function songPositionHandler(e, position) {
  setSongPosition(position);
}

function linkedMetadataHandler(e, metadata) {
  setClutterMetadata(metadata);
}

function commentHandler(e, comment) {
  addComment(comment.body, comment.time, comment.commenter, comment.html_title);
  ajax_args['last_comment'] = comment.id;
}

function clearCommentsHandler(e, blank) {
  clearComments();
  ajax_args['last_comment'] = 0;
}

function scoreHandler(e, score) {
  setScore(score);
}

function userVoteHandler(e, score) {
  setUserVote(score);
}
  
 
  
/*** event loop stuff ***/
 
//delay between ajax calls
DELAY = 15000; 
 
//how long messages should show for
MESSAGES_DELAY = 5000;
 
//associative array storing args for ajax calls
//TODO: replace with array of functions to be called to obtain args

 
 
//Called when list of events is recieved from server: triggers the correct javascript events with the correct arguments
function eventDispatcher(data) {
  for (i=0; i<data.length; i++) {
    $('body').trigger(data[i][0], [data[i][1]]);
  }
  addVoteHandlers();
}
 
 
//Main AJAX loop: handles all periodic AJAX calls
function ajaxLoop() {
  $.getJSON("/ajax", ajax_args, eventDispatcher);
  setTimeout('ajaxLoop()', DELAY);
}
 
//for stored ajax args
var ajax_args = {};
var now_playing = <?php echo $now_playing['song_id']; ?>;
$(document).ready(function(){

  //song position stuff
  setSongLength(songLength);
  setSongPosition(songPosition);
  
  //comment box stuff
  setupComment();
  setupVotes();
  
  //add vote handlers to voting buttons
  addVoteHandlers();
  
  //ajax events stuff
  ajax_args['position'] = songPosition;
  ajax_args['last_comment'] = <?php if ( ! empty($comments)) { $x = end($comments); echo $x['id']; } else { echo '0'; } ?>;
  $('body').bind('songPosition', songPositionHandler);
  $('body').bind('songLength', songLengthHandler);
  $('body').bind('linkedMetadata', linkedMetadataHandler);
  $('body').bind('comment', commentHandler);
  $('body').bind('clearComments', clearCommentsHandler);
  $('body').bind('score', scoreHandler);
  $('body').bind('userVote', userVoteHandler);

  //loops
  setupSearch();
  secondLoop();
  ajaxLoop();
  setTimeout('hideMessages()', MESSAGES_DELAY);
  
}); 
</script> 
</head> 
 
<body> 
 
<div id="bg1"></div> 
<div id="bg2"></div> 
<div id="leftmenu"> 
<a href="/playlist" id="leftmenulogo"></a> 
  <ul id="mainmenu"> 
    <li><a href="/playlist" class="boxed">&nbsp;playlist</a></li> 
    <li><a href="/search" class="boxed">&nbsp;search</a></li> 
    <li><form action="/search" method="get" class="boxed"><input accesskey="f" id="id_query" name="query" maxlength="100" type="text" value="quick search"></form></li> 
    <li><a href="/upload" class="boxed">&nbsp;upload</a></li> 
    <li><a href="/search/favourites" class="boxed">&nbsp;favourites</a></li>
    <li><a href="/search/artists" class="boxed">&nbsp;artists</a></li> 
    <li><a href="/user/1" class="boxed">&nbsp;user page</a></li> 
    <li><a href="/settings" class="boxed">&nbsp;settings</a></li> 
    <li><a href="/forum" class="boxed">&nbsp;forums</a></li> 
    <li><a href="http://gbs.fm:8080/listen.pls" class="boxed">&nbsp;listen!</a></li> 
    <li><a href="http://mibbit.com/?channel=%23gbs-fm&server=irc.synirc.net" class="boxed">&nbsp;live irc chat!</a></li> 
    <li><a href="/logout" class="boxed">&nbsp;logout</a></li> 

    <li><a href="/editqueue" class="boxed new">&nbsp;edit queue</a></li> 

    <li><a href="/reports" class="boxed new">&nbsp;reports</a></li> 

    <li><a href="{% url g2admin %}" class="boxed">&nbsp;g2 admin</a></li> 
  </ul> 
  <p class="listenersthing">icons from <a href="http://www.famfamfam.com/lab/icons/silk/">famfamfam</a></p> 
  <p class="listenersthing" title="g2:0 ghetto:0">listeners: 0</p> 
 
</div> 
 
<?php foreach ($msgs AS $msg) { ?><p id="messages"><?php echo $msg ?></p><?php }; ?>

<div id="maincontainer"> 
<?php echo $content; ?>
</div> 
 
<!-- Static bar at bottom of screen -->
<div id="bottomcont"> 
<div id="bottombg" title="geez what klutz designed this?"></div> 
 
      <div id="leftist"> 
        <form action="#" method="post" id="commentform">
          <input accesskey="c" autocomplete="off" id="commentbox" name="query" maxlength="400" type="text" 
                 value="You need to enable javascript to use the live comments system." disabled="disabled" />
        </form> 
        <div id="commentsbox"> 
          <a id="expandbutt" href="#" title="expand comment box"></a> 
          <?php foreach ($comments AS $comment) { ?>
            <p class="comment">
              @<?php echo $comment['time'].'&nbsp;&lt;'.$comment['username'].'&gt;&nbsp;'.HTML::chars($comment['text']); ?>
            </p>
          <?php } ?>
        </div> 
      </div> 
      <div id="rightist"> 
      
        <div id="progbarbox"> 
          <div id="progtube"> 
            <div style="width:<?php echo $now_playing['progress']; ?>%;" id="progbar"></div> 
          </div> 
          <span id="songPosition"><?php echo $now_playing['position']?></span>/<span id="songLength"><?php echo $now_playing['length']; ?></span>
        </div> 
        <span><div id="metadata"><a href="/search/artist/<?php echo $now_playing['artist_id']; ?>"><?php echo $now_playing['artist']; ?></a> (<a href="/search/album/<?php echo $now_playing['album_id']; ?>"><?php echo $now_playing['album']; ?></a>) - <a href="/search/song/<?php echo $now_playing['song_id']; ?>"><?php echo $now_playing['title']; ?></a></div>
        <div id="votebuttonslow"> 
        vote: 
         <?php foreach (array(1,2,3,4,5) AS $vote ) {?>
          <a href="#" class="<?php echo ($now_playing['user_vote'] == $vote) ? 'selectedvote' : ''; ?>" data-vote="<?php echo $vote ?>"><?php echo $vote ?></a> 
        <?php } ?>
        &nbsp;score: <span id='avgscore'>
		<?php if ($now_playing['avg_score'] > 0) {
        	echo $now_playing['avg_score'].' ('.$now_playing['vote_count'].' '.Inflector::plural('vote',$now_playing['vote_count']).')';
        } else {
        	echo 'no votes';
        } ?>
        </span>
        </div></span> 
      </div> 
 
 
</div> 
</body> 
</html> 
