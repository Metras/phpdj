<script type="text/javascript"> 
 
var getLastEntry = function(){
  return $('#playlist > :last-child').attr('id');
}
 
/*
* Get the entry ID of the currently playing track
*/
function getNowPlayingID() {
  return $(".playing").attr('id')
}
 
var stom = function(time){
  seconds = time%60;
  minutes = parseInt(time/60);
  if (seconds < 10) { 
    seconds = "0" + seconds.toString(); //add zero to single digit numbers
  } else {
    seconds = seconds.toString();
  }
  return minutes.toString() + ':' + seconds;
}
 
var stripe = function(data) {
  if($('#playlist tr:first').hasClass('odd')) {
    $('#playlist tr:nth-child(odd)').removeClass('even').addClass('odd');
    $('#playlist tr:nth-child(even)').removeClass('odd').addClass('even');
  } else {
    $('#playlist tr:nth-child(even)').removeClass('even').addClass('odd');
    $('#playlist tr:nth-child(odd)').removeClass('odd').addClass('even');
  }
}
 
var pruneHandler = function(){
  oldplaylist = $(".history");
  for (var i=0; i<(oldplaylist.length - 11); i++) { //TODO historylength (10) should be a user setting so need to fix somehow
    //(but it's +1 for weird reasons I can't quite discern)
    $(oldplaylist[i]).fadeOut('slow', removeCallback);
  }
}
 
 
 
/**
* Main loop: handles all periodic calls
**/
function funLoop() {
  pruneHandler();
  stripe();
  setTimeout('funLoop()', DELAY);
}
 
var removeCallback = function() {
  $(this).remove();
}
 
 
/** event callbacks **/
function removalHandler(e, data) {
  removal = $(document.getElementById(data['entryid']));
  if (!removal.hasClass("playing")) { //if it's playing, it should stay there and become historical
    removal.fadeOut("slow", removeCallback);
  }
  ajax_args['last_removal'] = data['id'];
}
 
function addsHandler(e, html) {
  lastentry = $('#playlist > :last-child');
  newentry = lastentry.after(html);
  ajax_args['last_add'] = getLastEntry();
}
 
function nowPlayingHandler(e, entryid) {
  currplaying = $(".playing");
  currplaying.removeClass('playing');
  currplaying.addClass('history');
  //remove irrelevent action buttons
  currplaying.children(".actions").children(".skip").hide();
  nextplaying = $(document.getElementById(entryid));
  nextplaying.addClass('playing');
  //change action buttons
  nextplaying.children(".actions").children(".remove").hide();
  nextplaying.children(".actions").children(".skip").show();
  ajax_args['now_playing'] = getNowPlayingID();
}
 
function metadataHandler(e, metadata) {
  pltitle = metadata + " - GBS-FM";
  if ($("title").html() != pltitle) {
    $("title").html(pltitle);
  }
}
 
function pllengthHandler(e, html) {
  length = $("#length")
  if (length.html() != html) {
      length.html(html);
  }
}
 
 
$(document).ready(function(){
  //ajax calls & event handler bindings
  //TODO: do this automatically with just the events and args or something
  ajax_args['last_removal'] = <?php echo $lastremoval; ?>
  $('body').bind('removal', removalHandler);
  
  ajax_args['last_add'] = getLastEntry();
  $('body').bind('adds', addsHandler);
  
  ajax_args['now_playing'] = getNowPlayingID();
  $('body').bind('now_playing', nowPlayingHandler);
  
  //no-arg event handlers
  $('body').bind('metadata', metadataHandler);
  $('body').bind('pllength', pllengthHandler);
  funLoop();
 
});

</script> 
 
<?php echo Request::factory('noticearea')->execute(); ?>

<?php if ( count($playlist) > 0 ) : ?>
    <table class="playlist" id="playlisttable"> 
    <tbody id='playlist'> 
      <?php if ($tokens > 0 ): ?>
        <tr><th colspan="7" class="plinfo">
          You have <?php echo $tokens.' '.Inflector::plural('token',$tokens); ?>, so you can add extra dongs to the playlist!
        </th></tr>
      <?php endif; ?>
      <tr><th colspan="7" id="length">Playlist is <?php echo $playlistlength; ?> long with <?php echo count($playlist).' '.Inflector::plural('dong',count($playlist)); ?></th></tr>
      <tr id='columns'> 
        <th>Artist</th> 
        <th>Title</th> 
        <th>Time</th> 
        <th>Added by</th> 
        <th>Vote</th> 
        <th>Score</th> 
        <th>Actions</th> 
      </tr> 
<?php foreach ($playlist AS $entry) { ?>
        <tr id="<?php echo ($entry['pl'] == 1) ? '' : 'h'; echo $entry['id']; ?>"  
        class="<?php
        		echo ($entry['playing'] == 1) ? ' playing ' : ' ';
        		echo Text::alternate(' odd ',' even ');
				echo ($entry['pl'] == 1) ? ' ' : ' history ';
				echo ($entry['adder_id'] == $user['user_id']) ? ' adder ' : ' ';
				echo ($entry['uploader_id'] == $user['user_id']) ? ' uploaded ' : ' ';
				
        		?>"> 
        <td class="artistry"><a href="<?php echo '/search/artist/'.$entry['artist_id']; ?>"><?php echo $entry['artist']; ?></a></td> 
        <td><a href="<?php echo '/search/song/'.$entry['song_id']; ?>"><?php echo $entry['title']; ?></a></td> 
        <td class="time"><?php echo $entry['length']; ?></td> 
        <td><a title="uploaded by: <?php echo $entry['uploader']; ?>" href="<?php echo '/user/'.$entry['adder_id']; ?>"><?php echo $entry['adder']; ?></a></td> 
        <td class="votes details"> 
		<?php foreach (array(1,2,3,4,5) AS $vote ) {?>
          <a href="<?php echo '/api/vote/'.$entry['song_id'].'/'.$vote ?>" class="vote<?php echo $vote.' '; echo ($entry['user_vote'] == $vote) ? ' voted ' : '';  ?>" data-songid="<?php echo $entry['song_id']; ?>" data-vote="<?php echo $vote; ?>"><?php echo $vote; ?></a> 
        <?php } ?>
        </td> 
        <td class="score"><?php if ($entry['avg_score'] > 0) {
        	echo $entry['avg_score'].' ('.$entry['vote_count'].' '.Inflector::plural('vote',$entry['vote_count']).')';
        } else {
        	echo 'no votes';
        } ?> 
        </td> 
        <td class="actions"> 
          <?php if ($entry['favourite'] == 1) { ?>
            <a href="/"> 
              <img src="/assets/images/heart_delete.png" title="Remove this dong from your favourites" alt="unfavourite" /> 
            </a> 
          <?php } else { ?>
            <a href="/"> 
              <img src="/assets/images/heart_add.png" title="Add this dong to your favourites" alt="favourite" /> 
            </a> 
          <?php } ?>
          <?php if ($entry['can_remove']) { ?>
            <a class="remove" href="" class="removebutton"> 
              <img src = "/assets/images/cross.png" title="Remove song from playlist" alt="cross" /> 
            </a> 
          <?php } ?>
          <?php if ($entry['can_skip'] && ($entry['playing'])) { ?>
            <a class="skip" href=""> 
              <img src = "/assets/images/skip.png" title="Skip currently playing song" alt="skip" /> 
            </a> 
          <?php } ?>
 
              
        </td> 
        </tr> 
      <?php } // End playlist foreach ?>
	  
    </tbody> 
    </table> 
    <?php else: ?>
    <p>Playlist empty!</p> 
    <?php endif; ?>