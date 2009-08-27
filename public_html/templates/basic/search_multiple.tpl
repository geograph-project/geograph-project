
{include file="_std_begin.tpl"}

<h2>Please refine your Search</h2>
{dynamic}
<p>The meaning of your search for images<i>{$searchdesc|escape:"html"}</i>, is not totally clear, please find below a few alternatives.</p>

{if $form eq 'simple'}
<h3 style="border-bottom:1px solid silver">Place search</h3>
{/if}

<p>We have found the following possible match{if count($criteria->matches) > 1}es{/if} for '{$criteria->searchq|escape:"html"}': {if count($criteria->matches) > 0}<br/><small>(hover over a placename for the <a href="/faq.php#counties">historic county</a>, or click a grid reference to go directly to that square)</small>{/if}</p>

<form action="{$script_name}" method="post">

{foreach key=name item=value from=$post}
	{if $value && $name != 'placename' && $name != 'go'}
		<input type="hidden" name="{$name}" value="{$value|escape:'html'}">
	{/if}		
{/foreach}
<input type="hidden" name="old-{$multipleon}" value="{$criteria->searchq|escape:'html'}">

{if strlen($criteria->searchq) > 20} 
	<input type="radio" name="{$multipleon}" value="text:{$criteria->searchq|escape:"html"}" id="dotext1">
	<label for="dotext1">Perform a word search for '{$criteria->searchq|escape:"html"}'</i></label> <br/><br/>	
{/if}
	
{foreach from=$criteria->matches item=match}
	<input type="radio" name="{$multipleon}" value="{$match.id}" id="match{$match.id}">
	<span style="width:75px;position:absolute;"><a href="/gridref/{$match.gridref}">{$match.gridref}</a></span>
	<label style="padding-left: 75px;" for="match{$match.id}"{if $match.hist_county} title="Historic County: {$match.hist_county}"{/if}><b>{$match.full_name}</b><small><i>{if $match.adm1_name}, {$match.adm1_name}{/if}, {$references[$match.reference_index]}</i>
	<small>[{$match.dsg_name}]</small></small></label> <br/>
{/foreach}

{if !$criteria->ismore}
	<br/>
	<input type="radio" name="{$multipleon}" value="{$criteria->searchq|escape:"html"}?" id="domore">
	<label for="domore"><b>Place looking for not listed above? Try a wider search.</b></label> <br/>		
{/if}

{if $form eq 'simple'}
<h3 style="border-bottom:1px solid silver">Word search</h3>
{/if}

{if $pos_realname}
	<input type="radio" name="{$multipleon}" value="user:{$pos_user_id}" id="douser">
	<label for="douser"><i>Perform a search for pictures taken by '<a href="/profile/{$pos_user_id}" title="profile for {$pos_realname}">{$pos_realname}</a>' {if $pos_nickname}(nickname: '{$pos_nickname}'){/if}</i></label> <br/>		
	<br/>
{/if}

{if preg_match('/near\s+/',$post.q) && !preg_match('/near\s+\(anywhere\)/',$post.q)}
	<input type="radio" name="{$multipleon}" value="text:{$post.q|replace:'near ':'AND '|escape:"html"}" id="dotext">
	<label for="dotext">Perform a word search for '{$post.q|replace:'near ':'AND '|escape:"html"}'</label> <br/>
{elseif $post.q && $post.q != $criteria->searchq && preg_match('/near\s+$/',$post.q)}
	<input type="radio" name="{$multipleon}" value="text:{$post.q|escape:"html"} AND {$criteria->searchq|escape:"html"}" id="dotext">
	<label for="dotext">Perform a word search for '{$post.q|escape:"html"} AND {$criteria->searchq|escape:"html"}'</label> <br/>
{elseif $post.searchtext && $post.searchtext != $criteria->searchq}
	<input type="radio" name="{$multipleon}" value="text:{$post.searchtext|escape:"html"} AND {$criteria->searchq|escape:"html"}" id="dotext">
	<label for="dotext">Perform a word search for '{$post.searchtext|escape:"html"} AND {$criteria->searchq|escape:"html"}'</label> <br/>
{/if}


<input type="radio" name="{$multipleon}" value="text:{$criteria->searchq|escape:"html"}" id="dotext">
<label for="dotext">Perform a word search for '{$criteria->searchq|escape:"html"}'</i></label> <br/>	

<p><input type="submit" name="refine" value="Refine"> <input type="submit" value="Find &gt;" style="font-size:1.1em"></p>

</form>	

{if $suggestions} 
	<div><b>Did you mean:</b>
	<ul>
	{foreach from=$suggestions item=row}
		<li><b><a href="/search.php?q={$row.query|escape:'url'}+near+{$row.gr}">{$row.query} <i>near</i> {$row.name}</a></b>? <small>({$row.localities})</small></li>
	{/foreach}
	</ul></div>
{/if}
{/dynamic}

<div class="copyright">Great Britain locations based upon 1:50 000 Scale Gazetteer with the permission of Ordnance Survey on behalf of The Controller of Her Majesty's Stationery Office,<br/>
&copy; Crown copyright Ordnance Survey. All Rights Reserved. Educational licence 100045616.<br/>
<br/>
and enhanced with the Gazetteer of British Place Names, &copy; Association of British Counties, used with permission.<br/><br/>
Natural Language Query Parsing by {external href="http://developers.metacarta.com/" text="MetaCarta Web Services"}, Copyright MetaCarta 2006</div>

{include file="_std_end.tpl"}
