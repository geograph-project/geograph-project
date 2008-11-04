{assign var="page_title" value="Progress So far..."}
{assign var="meta_description" value="Short overview in numbers of the progress in photographing every grid square of the British Isles."}
{assign var="right_block" value="_block_recent.tpl"}
{include file="_std_begin.tpl"}
{literal}<style type="text/css">
.greenbar {
	position:relative; width:100%; background-color:#75FF65; border:1px solid blue; margin-bottom:10px; 
}
.redbar {
	position:relative; float:left; background-color:#FF0000; border-right:1px solid blue; 
}
.righttextbox {
	position:relative; float:right; text-align:right; color:#000066; padding-right: 5px; padding-top:10px; padding-bottom:10px
}
.greenbar .redbar .righttextbox {
	color: white;
}
.lefttextbox {
	position:relative; float:left; color:yellow; padding-left: 5px; padding-top:10px; padding-bottom:10px; 
	background-image: url('/templates/basic/img/numbers-arrow.gif');
	background-position: center left;
	background-repeat: no-repeat;
}
.innerlefttextbox {
	position:absolute; top:1px; left:1px; color:#000066; padding-left: 5px; padding-top:10px; padding-bottom:10px; white-space:nowrap; 
}

.statsbox {
	position:relative; width:70%; float:left
}

.statsbox div {
	position:relative; width:150px; background-color:#dddddd; float:left; padding:10px; margin-right:20px; margin-bottom:20px; text-align:center
}
.recentbox {
	position:relative; width:25%; float:left; background-color:#dddddd; padding:10px;
}
.recentbox h4 {
	font-size:0.9em; text-align: center; margin-bottom:3px; margin-top:0px; 
}
.recentbox .halvebox {
	position:relative; width:45%; float:left; padding:3px;
}

.finalboxframe {
	position:relative; width:100%; color:#000066; background-color:white; border: 1px solid #000066
}

.finalbox {
	position:relative; background-color:#000066; color:white; float:left; text-align:center; padding-top:10px; padding-bottom:10px; line-height:1.5em; font-size:1.1em;
	/*border-left:1px solid #000066; border-top:1px solid #000066; border-bottom:1px solid #000066;*/ /*margin:-1px;*/
	border-right:1px solid #000066;
	background-image: url('/templates/basic/img/numbers-arrow-down.gif');
	background-position: center right;
	background-repeat: no-repeat;
}
.finalbox2 {
	position:relative; color:#000066; /*background-color:white;*/ /*float:left;*/ text-align:center; padding-top:10px; padding-bottom:10px; line-height:1.5em; font-size:1.1em; 
	width: auto;
	margin-left: auto; margin-right: auto;
	/*border-right:1px solid #000066; border-top:1px solid #000066; border-bottom:1px solid #000066;*/ /*margin:-1px;*/
}
.finalbox2 A {
	color: red;
}
.linksbox {
	position:relative; width:100%; background-color:yellow; float:left; text-align:center; padding-top:10px; padding-bottom:10px;
}
.linksbox h3 {
	margin-top:0px; text-align: center; margin-bottom:0px;
}
</style>{/literal}

<div style="position:relative; float:right">
	&lt; <a href="/statistics.php">More Statistics</a> | <a href="/statistics/moversboard.php">Leaderboard</a> &gt;
</div>

<h2>Geograph British Isles</h2>

<div class="greenbar">{* for 33-66% coverage *}
	<div class="righttextbox">
		<b class="nowrap">{$stats.total|thousends}</b><br/>
		<br/>
	</div>
	<div class="redbar" style="width:{$stats.percentage}%;">
		<div class="righttextbox">
		{if $stats.percentage >= 50 }
			<b class="nowrap">{$stats.squares|thousends}</b> Squares photographed<br/>
		{else}
			<br/>
		{/if}
			<br/>
		</div>
		<br style="clear:both"/>
	</div>
	<div class="lefttextbox">
		{if $stats.percentage >= 50 }
		<div class="innerlefttextbox">
		<br/>
		<b class="nowrap">{$stats.percentage}%</b>
		</div>
		<br/>
		<b class="nowrap">{$stats.percentage}%</b><br/>
		{else}
		<div class="innerlefttextbox">
		<b class="nowrap">{$stats.squares|thousends}</b> Squares photographed<br/>
		<b class="nowrap">{$stats.percentage}%</b><br/>
		</div>
		<b class="nowrap">{$stats.squares|thousends}</b> Squares photographed<br/>
		<b class="nowrap">{$stats.percentage}%</b><br/>
		{/if}
	</div>
	<br style="clear:both"/>
</div>
<br style="clear:both"/>
<div style="position:relative; width: 100%;">
	
	<div class="statsbox">
		<div> 
			<b class="nowrap">{$stats.users|thousends}</b><br/>
			contributors
		</div>
		<div> 
			<b class="nowrap">{$stats.points|thousends}</b><br/>
			points awarded
		</div>
		<div> 
			<b class="nowrap">{$stats.images|thousends}</b><br/>
			images in total!
		</div>
		<div> 
			<b class="nowrap">{$stats.persquare}</b><br/>
			average images<br/>
			per square
		</div>
		<br style="clear:both"/>
	</div>

	<div class="recentbox">
		<h4>Recently completed<br/> hectads (<a href="/statistics/fully_geographed.php" title="Completed 10km x 10km squares">list all ...</a>)</h4>
		<div class="halvebox">
			{foreach from=$hectads key=id item=obj name="hectads"}
				<a title="View Mosaic for {$obj.hectad_ref}, completed {$obj.completed}" href="/maplarge.php?t={$obj.largemap_token}">{$obj.hectad_ref}</a><br/>
				{if $smarty.foreach.hectads.iteration eq 5}
		</div><div class="halvebox">
				{/if}
			{/foreach}
		</div>
		<br style="clear:both"/>
	</div>
</div>

<small><br style="clear:both"/><br/></small>
<div class="finalboxframe">
	<div class="finalbox" style="width:{$stats.fewpercentage}%;">
		{if $stats.fewpercentage >= 50 }
		<b class="nowrap">{$stats.fewphotos|thousends}</b>
		 photographed<br/> squares</b>... <br/> 
		{else}
		<br/><br/>
		{/if}
	</div>
	<!--div class="finalbox2" style="width:{$stats.negfewpercentage}%;"-->
	<div class="finalbox2">
		{if $stats.fewpercentage >= 50 }
		... with <b>fewer than 4 photos,<br/>
		<a href="/submit.php">add yours now!</a></b>
		{else}
		<b class="nowrap">{$stats.fewphotos|thousends}</b>
		 photographed squares</b>... <br/> 
		... with <b>fewer than 4 photos,
		<a href="/submit.php">add yours now!</a></b>
		{/if}
	</div>
	<!--br style="clear:both"/-->
</div>

<small><br style="clear:both"/><br/></small>
<div class="linksbox">
<h3>Further Statistics</h3>
| <b><a href="/statistics.php">More Numbers...</a></b> | <a href="/statistics.php#more">More Pages...</a> | 
<a href="/statistics/moversboard.php">Leaderboard</a> |
<a href="/statistics/pulse.php">Geograph Pulse</a> |
<a href="/statistics/estimate.php">Future Estimates</a> |
   
</div>

<br style="clear:both"/>
&nbsp;

{include file="_std_end.tpl"}
