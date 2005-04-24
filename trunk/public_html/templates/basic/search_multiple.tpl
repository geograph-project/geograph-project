
{include file="_std_begin.tpl"}

<h2>Please refine your Search</h2>

<p>'{$criteria->searchq}' for {$multipletitle} has multiple possiblities:</p>

<form action="/search.php" method="post">

{foreach key=name item=value from=$post name=loop}
{if $value && $name != 'placename'}
<input type="hidden" name="{$name}" value="{$value|escape:'html'}">
{/if}		
{/foreach}

{foreach from=$criteria->matches item=match}

<input type="radio" name="placename" value="{$match.full_name}"> {$match.full_name} <small><small>[<i>{$match.name}</i>]</small></small> <br/>
		
{/foreach}

<p><input type="submit" value="Find"></p>

</form>	
{include file="_std_end.tpl"}
