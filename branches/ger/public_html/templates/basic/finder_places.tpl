{assign var="page_title" value="Place Search"}
{include file="_std_begin.tpl"}
{literal}
<style type="text/css">
	ul.explore tt {
		border:1px solid gray;
		padding:5px;
	}
</style>

  <script type="text/javascript">
  
  function focusBox() {
  	el = document.getElementById('fq');
  	el.focus();
  }
  AttachEvent(window,'load',focusBox,false);
  
  </script>

{/literal}

<h2><a href="/finder/">Finder</a> :: Places</h2>

<form action="{$script_name}" method="get" onsubmit="focusBox()">
	<p>
		<label for="fq">Name</label>: <input type="text" name="q" id="fq" size="40"{dynamic}{if $q} value="{$q|escape:'html'}"{/if}{/dynamic}/>
		<input type="submit" value="Search"/><br/>
		<label for="fuzzy">Include 'fuzzy' matches?</label> <input type="checkbox" name="f" id="fuzzy" {if $fuzzy}checked{/if}/>
		<!--<label for="loc">Search Localities?</label> <input type="checkbox" name="l" id="loc" {if $loc}checked{/if}/>
		<label for="palce" style="color:lightgrey">Include other features?</label> <input type="checkbox" name="p" id="place" {if $place}checked{/if} disabled/>-->
	</p>
</form>

{if count($results) eq 15}
	<p>
		<small>&middot; To refine the results simply add more keywords (view <a href="#cheatsheet">Cheatsheet</a>)</small>
	</p>
{/if}

{if $query_info}
	<p><i>Click a placename to find pictures around that location</i></p>
{/if}

<ol start="{$offset}">
{foreach from=$results item=item}
	<li style="font-size:{math equation="2-log(s)/2" s=$item.score}em">
	<tt>{$item.gr}</tt> <b><a href="/search.php?placename={$item.id}&amp;do=1" target="_top">{$item.name|escape:'html'|default:'unknown'}{if $item.name_2} <small>[{$item.name_2|escape:'html'}]</small>{/if}</a></b>
	{if $item.localities}<small>{$item.localities|escape:'html'}</small>{/if}
	{if $item.localities_2}<small>[{$item.localities_2|escape:'html'}]</small>{/if}
	<small><a href="/finder/search-maker.php?placename={$item.id}&amp;do=1" target="_top">Experimental Place Search</a></small>
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


<div class="interestBox" style="margin-top:60px;">
	<big><a name="cheatsheet"></a>Cheatsheet</big>:
	<ul class="explore">
		<li>you can enter just the first few letters of a name</li>
		<li>prefix a keyword with - to <b>exclude</b> that word from the match; example : <tt>smith -john</tt></li>
		<li>can use OR to match <b>either/or</b> keywords; example: <tt>john OR joan</tt></li>
	</ul>
</div>


{include file="_std_end.tpl"}
