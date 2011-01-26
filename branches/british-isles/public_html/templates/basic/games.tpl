{assign var="page_title" value="Geograph Games"}
{include file="_std_begin.tpl"}
{literal}
<script type="text/javascript">
function showHide(id,show) {
	document.getElementById(id).style.display=(show)?'':'none';
}
</script>
{/literal}

{dynamic}
{if $user->registered}

<div style="position:relative; display: none" id="helpout1">
	<div style="left:80px; border:1px solid red; color:white; background-color:green; width:84%; height:340px; margin-bottom:30px" class="interestBox">
		<div style="float:right">[ <a href="javascript:showHide('helpout1',false);">Close</a> ]</div>
		<h3 style="color:yellow">Playing in Rater Mode</h3>
		<p>You will play an essentially normal game, however you will be shown completely random images from the large collection of images submitted to Geograph. Because of this, many images won't actually be suitable for play, such as wide angle views or closeups that aren't visible on the map extract.</p>
		

		<div  class="interestBox" style="width:45%; float:left">
		<form action="/games/markit.php" method="get">
		<input type="hidden" name="rater" value="1"/>
		<input type="hidden" name="autoload" value="1"/>
		<h3 style="background-color:black;color:white;margin-top:0px; padding:10px;"><div style="float:right"><input type="submit" value="Go &gt; &gt;" style="font-size:1.2em"/></div> Mark It</h3>
		
		</form>
		<p>As you play please add an approximate level of difficultly for the image. If an image is not suitable please label it as such. Please rate assuming no local knowledge, and user of medium ability.</p>
		
		<a href="/games/moversboard.php?g=1&l=0">Rater Weekly Scoreboard</a>
		<br style="clear:both"/>
		</div>
	</div>
</div>


<div class="interestBox" style="background-color:lightgreen; color:black; border:2px solid red; padding:10px; width:260px; float:right; text-align:center">
Help us locate new <br/>images for the games.<br/>

<a href="javascript:showHide('helpout1',true);">Read more &gt;&gt;</a>
</div>


{/if}
{/dynamic}

<div style="float:left; padding-right:20px"><img src="http://{$static_host}/templates/basic/img/hamster.gif" width="161" height="174" alt="Perdita the Geograph hamster"/></div>



<div style="float:left; margin-top:60px; width:300px;height:85px; background-image:url('http://{$static_host}/templates/basic/img/callout2.gif');padding-left:90px;padding-top:15px; padding-right:10px; text-align:center;color:#0000FF">
Hi, my name is Perdita the Geograph hamster. Welcome to our <h2>games section</h2></div>

<br style="clear:both"/>



<div  class="interestBox" style="width:45%; float:left; height:350px; margin-right:20px">
<img src="http://{$static_host}/games/markit.gif" align="right" width="115" height="125"/>
<h3 style="background-color:black;color:white;margin-top:0px; padding:10px;">Mark It</h3>

<p>Earn hamster tokens by locating photos on the map! A single round consists of playing 10 images.</p>

<form action="/games/markit.php">

<b>Level:</b><br/>
<input type="radio" name="l" value="1"/>1 
<input type="radio" name="l" value="2" checked/>2 
<input type="radio" name="l" value="3"/>3 
<input type="radio" name="l" value="4"/>4 
<input type="radio" name="l" value="5"/>5 
<br/><tt>&lt;-- Easy | Harder --&gt;</tt><br/><br/>

<input type="submit" value="Play Now &gt; &gt;" style="font-size:1.3em"/> or view the <a href="/games/moversboard.php?g=1">scoreboard</a> 

</form><br/>


<br style="clear:both"/>
</div>


<div  class="interestBox" style="width:45%; float:left; height:350px; ">
<h3 style="background-color:black;color:white;margin-top:0px; padding:10px;">Place Memory (beta) <sup style="color:pink">New!</sup></h3>

<p>Earn hamster tokens by locating the grid Square of a photo. A single round consists of playing 10 images.</p>

<form action="/games/place-memory.php">

<b>Level:</b><br/>
<input type="radio" name="l" value="1"/>1 :: Within 3km of &nbsp;&nbsp;&nbsp; <div style="position:absolute; display:inline;"><div style="position:relative; top: 8px"><label for="grid_reference">Grid reference:</label> <input type="text" size="6" name="grid_reference" id="grid_reference"/></div></div><br/>
<input type="radio" name="l" value="2" checked/>2 :: Within 10km of  <br/>

{if !$user->registered || $user->stats.images > 10}<span style="color:gray">
<input type="radio" name="l" value="3" disabled/>3 :: Anywhere near one of my regular haunts<br/>
<input type="radio" name="l" value="4" disabled/>4 :: Anywhere near a photo I've submitted<br/>
(level 3 and 4 are only available to contributors)<br/>
</span>
{else}
<input type="radio" name="l" value="3" checked/>3 :: Anywhere near one of my regular haunts<br/>
<input type="radio" name="l" value="4"/>4 :: Anywhere near a photo I've submitted<br/>
{/if}

<input type="radio" name="l" value="5"/>5 :: Anywhere in Great Britain<br/>
<br/>

<input type="submit" value="Play Now &gt; &gt;" style="font-size:1.3em"/> or view the <a href="/games/moversboard.php?g=2">scoreboard</a> 

</form><br/>


<br style="clear:both"/>
</div>

<br style="clear:both"/>

<div class="interestBox" style="margin: 20px; padding:10px; width:260px; float:right; text-align:center">

More games coming soon...
</div>

<p>Just for fun, <a href="/games/statistics.php">some overview statistics</a>.</p>

<br style="clear:both"/>

{include file="_std_end.tpl"}

