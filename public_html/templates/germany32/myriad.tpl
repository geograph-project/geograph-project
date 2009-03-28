{assign var="page_title" value="$myriad :: Myriad"}
{include file="_std_begin.tpl"}


<div style="width:40%; float:right; position:relative">
	<form method="get" action="/search.php" target="results">
		<label for="fq">Keywords</label>: <input type="text" name="searchtext" id="fq" size="30"{dynamic}{if $q} value="{$q|escape:'html'}"{/if}{/dynamic}/>
		<input type="submit" value="Search"/><br/>
		<input type="hidden" name="gridsquare" value="{$myriad}"/>
		<input type="hidden" name="displayclass" value="search"/>
		<input type="hidden" name="do" value="1"/>
	</form>
	<iframe src="/gridref/{$gridsquare.grid_reference}?inner" height="900" width="100%" name="results">
	
	</iframe>
</div>

<h2>Myriad :: {$myriad}</h2>

<div style="width:420px; float:left; position:relative">
	<iframe src="/mapprint.php?t={$token}&amp;inner" height="485" width="430">
	
	</iframe>
</div>

<div style="width:420px; float:left; position:relative">

	<div class="tabHolder">
		Contributors:
		<a href="/statistics/leaderboard.php?myriad={$myriad}&amp;inner&amp;type=images&amp;limit=100" class="tab" target="leaderboard">Images</a>
		<a href="/statistics/leaderboard.php?myriad={$myriad}&amp;inner&amp;type=geosquares&amp;limit=100" class="tab" target="leaderboard">GeoSquares</a>
		<a href="/statistics/leaderboard.php?myriad={$myriad}&amp;inner&amp;type=depth&amp;limit=100" class="tab" target="leaderboard">Depth</a>
		<span class="tabSelected">Points</span>

	</div>
	<iframe src="/statistics/leaderboard.php?myriad={$myriad}&amp;inner&amp;limit=100" height="500" width="420" name="leaderboard">
		
	</iframe>
</div>


<br style="clear:both"/>
{include file="_std_end.tpl"}
