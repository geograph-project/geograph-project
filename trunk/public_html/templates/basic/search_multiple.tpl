
{include file="_std_begin.tpl"}

<h2>Please refine your Search</h2>
{dynamic}
<p>In your search for images<i>{$searchdesc}</i>.</p>

<p>'{$criteria->searchq}' for {$multipletitle} has multiple possiblities:</p>

<form action="/search.php" method="post">

{foreach key=name item=value from=$post name=loop}
{if $value && $name != 'placename'}
<input type="hidden" name="{$name}" value="{$value|escape:'html'}">
{/if}		
{/foreach}

{foreach from=$criteria->matches item=match}

<input type="radio" name="placename" value="{$match.id}">
  <b>{$match.full_name}</b><small><i>{if $match.adm1_name}, {$match.adm1_name}{/if}, {$references[$match.reference_index]}</i>
 <small>[{$match.dsg_name}]</small></small> <br/>
		
{/foreach}
{/dynamic}
<p><input type="submit" value="Find"></p>

</form>	
{include file="_std_end.tpl"}
