{assign var="page_title" value="Progress So far..."}
{assign var="meta_description" value="Short overview in numbers of the progress in photographing every grid square of Germany."}
{assign var="right_block" value="_block_recent.tpl"}
{include file="_std_begin.tpl"}

<div style="position:relative; float:right">
	&lt; <a href="/statistics.php">More Statistics</a> | <a href="/statistics/moversboard.php">Leaderboard</a> &gt;
</div>

<h2>Geograph Deutschland</h2>

<div class="greenbar">{* for 33-66% coverage *}
	<div class="righttextbox">
		<b class="nowrap">{$stats.total|thousends}</b><br/>
		<br/>
	</div>
	<div class="redbar" style="width:{$stats.percentage|string_format:"%.2f"}%;">
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
		<b class="nowrap">{$stats.percentage|floatformat:"%.2f"}%</b>
		</div>
		<br/>
		<b class="nowrap">{$stats.percentage|floatformat:"%.2f"}%</b><br/>
		{else}
		<div class="innerlefttextbox">
		<b class="nowrap">{$stats.squares|thousends}</b> Squares photographed<br/>
		<b class="nowrap">{$stats.percentage|floatformat:"%.2f"}%</b><br/>
		</div>
		<b class="nowrap">{$stats.squares|thousends}</b> Squares photographed<br/>
		<b class="nowrap">{$stats.percentage|floatformat:"%.2f"}%</b><br/>
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
			<b class="nowrap">{$stats.persquare|floatformat:"%.1f"}</b><br/>
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
	<div class="finalbox" style="width:{$stats.fewpercentage|string_format:"%.2f"}%;">
		{if $stats.fewpercentage >= 50 }
		<b class="nowrap">{$stats.fewphotos|thousends}</b>
		 photographed<br/> squares... <br/> 
		{else}
		<br/><br/>
		{/if}
	</div>
	<!--div class="finalbox2" style="width:{$stats.negfewpercentage|string_format:"%.1f"}%;"-->
	<div class="finalbox2">
		{if $stats.fewpercentage >= 50 }
		... with <b>fewer than 4 photos,<br/>
		<a href="/submit.php">add yours now!</a></b>
		{else}
		<b class="nowrap">{$stats.fewphotos|thousends}</b>
		 photographed squares... <br/> 
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
