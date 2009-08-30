{assign var="page_title" value="Games Statistics"}
{include file="_std_begin.tpl"}

<h2><a href="/games/">Geograph Games</a> - Statistics</h2>

<form method="get" action="{$script_name}">
<p> <label for="g">Game</label>: <select name="g" id="g">
{html_options options=$gamelist selected=$g}
</select> 
<input type="submit" value="Go"></p>
</form>

{if $stats.images}
<h3>Images</h3>

<p>We currently have <b class="nowrap">{$stats.images|thousends} images</b> playable in the pool, with more being added all the time, a total of <b class="nowrap">{$stats.rates|thousends} ratings</b> have been recorded by <b class="nowrap">{$stats.raters|thousends} volunteers</b>. A further <b class="nowrap">{$stats.gone|thousends} images</b> have been classified as not suitable for game play.</p>
{/if}

<h3>Games</h3>

<p><b class="nowrap">{$stats.rounds|thousends} rounds</b> have been played by <b class="nowrap">{$stats.users_all|thousends} people</b> (<b class="nowrap">{$stats.users_users|thousends} users</b> and <b class="nowrap">{$stats.users_all-$stats.users_users|thousends} visitors</b>). During play a total of <b class="nowrap">{$stats.plays_rounds|thousends} plays</b> has been recorded, (a further <b class="nowrap">{$stats.plays_all-$stats.plays_rounds|thousends} plays</b> from unsaved rounds), resulting in <b class="nowrap">{$stats.tokens|thousends} tokens</b> being awarded (giving overall average of <b class="nowrap">{$stats.tokens/$stats.plays_rounds|number_format:2} tokens per play</b>).</p>

{if $stats.wrounds}
<h4>In the last 7 days...</h4>
<p><b class="nowrap">{$stats.wrounds|thousends} rounds</b> have been played by <b class="nowrap">{$stats.wusers_all|thousends} people</b> (<b class="nowrap">{$stats.wusers_users|thousends} users</b> and <b class="nowrap">{$stats.wusers_all-$stats.wusers_users|thousends} visitors</b>). During play a total of <b class="nowrap">{$stats.wplays_rounds|thousends} plays</b> has been recorded, resulting in <b class="nowrap">{$stats.wtokens|thousends} tokens</b> being awarded (giving overall average of <b class="nowrap">{$stats.wtokens/$stats.wplays_rounds|number_format:2} tokens per play</b>).</p>
{/if}


<br style="clear:both"/>


{include file="_std_end.tpl"}
