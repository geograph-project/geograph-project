{assign var="page_title" value="Place Search"}
{if $inner}
{include file="_basic_begin.tpl"}
{else}
{include file="_std_begin.tpl"}
{/if}
{literal}
<style type="text/css">
	ul.explore tt {
		border:1px solid gray;
		padding:5px;
	}
</style>

  <script type="text/javascript">
  
  function focusBox() {
  	if (el = document.getElementById('fq')) {
  		el.focus();
  	}
  }
  AttachEvent(window,'load',focusBox,false);
  
  </script>

{/literal}

{if !$inner}
	<h2><a href="/finder/">Finder</a> :: Places</h2>

	<form action="{$script_name}" method="get" onsubmit="focusBox()">
		<div class="interestBox">
			<label for="fq">Name</label>: <input type="text" name="q" id="fq" size="40"{dynamic}{if $q} value="{$q|escape:'html'}"{/if}{/dynamic}/>
			<input type="submit" value="Search"/><br/>
			<label for="fuzzy">Include 'fuzzy' matches?</label> <input type="checkbox" name="f" id="fuzzy" {if $fuzzy}checked{/if}/>
			<!--<label for="loc">Search Localities?</label> <input type="checkbox" name="l" id="loc" {if $loc}checked{/if}/>
			<label for="palce" style="color:lightgrey">Include other features?</label> <input type="checkbox" name="p" id="place" {if $place}checked{/if} disabled/>-->
		</div>
	</form>

	{if count($results) eq 15}
		<p>
			<small>&middot; To refine the results simply add more keywords (view <a href="#cheatsheet">Cheatsheet</a>)</small>
		</p>
	{/if}
{/if}

{if $query_info}
	<p><i>Click a placename to find pictures around that location, alternatively try the experimental <img src="http://{$static_host}/img/links/20/search.png" width="16" height="16" alt="search icon" align="absmiddle"/> icon which gives more focused results.</i></p>
{/if}

<ol start="{$offset}">
{foreach from=$results item=item}
	<li>
	<tt><a href="/gridref/{$item.gr}">{$item.gr}</a></tt> <a href="/finder/search-maker.php?placename={$item.id}&amp;do=1" target="_top"><img src="http://{$static_host}/img/links/20/search.png" width="20" height="20" alt="search icon" align="absmiddle"/></a> <b style="font-size:{math equation="2-log(s)/2" s=$item.score}em"><a href="/search.php?placename={$item.id}&amp;do=1" target="_top">{$item.name|escape:'html'|default:'unknown'}{if $item.name_2} <small>[{$item.name_2|escape:'html'}]</small>{/if}</a></b>
	{if $item.localities}&nbsp;&nbsp;&nbsp;<small style="color:#666666">{$item.localities|escape:'html'}</small>{/if}
	{if $item.localities_2}&nbsp;<small style="color:gray">[{$item.localities_2|escape:'html'}]</small>{/if}
	</li>
{foreachelse}
	{if $q}
		<li><i>There are no results to display at this time.</i></li>
	{/if}
{/foreach}

</ol>

<div style="margin-top:0px"> 
{if $pagesString}
	( Page {$pagesString})
{/if}
</div>	

{if $query_info}
	<p>{$query_info}</p>
{/if}



{if $inner}
</body>
</html>
{else}
<hr/>
<div style="margin-top:60px;">
	<big><a name="cheatsheet"></a>Cheatsheet</big>:
	<ul class="explore">
		<li>you can enter just the first few letters of a name; example <tt>beck</tt> (finds Beckley)</li>
		<li>matches against the name and county/country (so enter both refine search); example: <tt>beckley oxford</tt>, <tt>underwood wal</tt> (for wales)</li>
		<li>add a hectad or myriad reference to the end to only include results in that square; example: <tt>beckley tq</tt> </li>
		<li>prefix a keyword with - to <b>exclude</b> that word from the match; example : <tt>smith -john</tt></li>
		<li>combine the above; example : <tt>dublin -ireland</tt></li>
		<li>can use OR to match <b>either/or</b> keywords; example: <tt>grantham OR grantley</tt></li>
	</ul>
</div>

{include file="_std_end.tpl"}
{/if}