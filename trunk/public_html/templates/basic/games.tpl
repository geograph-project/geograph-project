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
	<div style="position:absolute; left:80px; top:30px;border:1px solid red; z-index:100; color:white; background-color:green; width:84%; height:380px" class="interestBox">
		<div style="float:right">[ <a href="javascript:showHide('helpout1',false);">Close</a> ]</div>
		<h3 style="color:yellow">Playing in Rater Mode</h3>
		<p>You will play an estentially normal game, however you will be shown completly random images from the large collection of images submitted to Geograph. Because of this many images wont actully be suitable for play, such as wide angle views, or very closeups that arent visible on the map extract.</p>
		

		<div  class="interestBox" style="width:45%; float:left">
		<form action="/games/markit.php" method="get">
		<input type="hidden" name="rater" value="1"/>
		<h3 style="background-color:black;color:white;margin-top:0px; padding:10px;"><div style="float:right"><input type="submit" value="Go &gt; &gt;" style="font-size:1.2em"/></div> Mark It</h3>
		
		</form>
		<p>As you play please add an approximate level of difficultly for the image, if a image is not suitable please label it as such. Please rate asubing no local knowledge, and user of medium ability</p>
		<br style="clear:both"/>
		</div>
	</div>
</div>


<div class="interestBox" style="background-color:lightgreen; color:black; border:2px solid red; padding:10px; width:260px; float:right; text-align:center">
<img src="/templates/basic/img/icon_alert.gif" alt="Modify" width="50" height="44" align="left" style="margin-right:10px"/>

or Help us Locate new <br/>images for the game. <br/>

<a href="javascript:showHide('helpout1',true);">Read More &gt;&gt;</a>
</div>


{/if}
{/dynamic}

<div style="float:left; padding-right:20px"><img src="http://s0.{$http_host}/templates/basic/img/hamster.gif"/></div>



<div style="float:left; margin-top:60px; width:235px;height:59px; background-image:url('http://s0.{$http_host}/templates/basic/img/callout1.gif');padding-left:75px;padding-top:5px; padding-right:10px; text-align:center;">
Welcome to Geograph's <h2>Games Section</h2></div>

<br style="clear:both"/>



<div  class="interestBox" style="width:45%; float:left">
<img src="http://s0.{$http_host}/games/markit.gif" align="right"/>
<h3 style="background-color:black;color:white;margin-top:0px; padding:10px;">Mark It</h3>

<p>Earn Hamster Tokens by locating Photos on the Map!</p>

<form action="/games/markit.php">

<b>Level:</b><br/>
<input type="radio" name="l" value="1"/>1 
<input type="radio" name="l" value="2" checked/>2 
<input type="radio" name="l" value="3"/>3 
<input type="radio" name="l" value="4"/>4 
<input type="radio" name="l" value="5"/>5 
<br/><tt>&lt;-- Easy | Harder --&gt;</tt><br/><br/>

<input type="submit" value="Play Now &gt; &gt;" style="font-size:1.3em"/> or view the <a href="/games/moversboard.php">Scoreboard</a> 

</form><br/>


<br style="clear:both"/>
</div>



<br style="clear:both"/>

{include file="_std_end.tpl"}

