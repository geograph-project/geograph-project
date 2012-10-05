
{include file="_std_begin.tpl"}

<h2>Please refine your search</h2>
{dynamic}
<p>From your search for images<i>{$searchdesc|escape:"html"}</i>, we found a few possible alternatives. Please click a button below to select one.</p>
<form action="{$script_name}" method="post" name="theForm">
<input type="hidden" name="form" value="multiple"/>

{if strlen($criteria->searchq) > 20 || count($criteria->matches) > 10}

	{if $post.q && $post.location}
		<button type="submit" name="{$multipleon}" value="text:{$post.q|escape:"html"} {$post.location|escape:"html"}">Keyword search for '{$post.q|escape:"html"} {$post.location|escape:"html"}'</button>
	{else}
		<button type="submit" name="{$multipleon}" value="text:{$criteria->searchq|escape:"html"}">Images matching <b>'{$criteria->searchq|escape:"html"}' keyword(s)</b></button>
	{/if}
{elseif count($criteria->matches) > 4}
	<p><button type="submit" name="{$multipleon}" value="text:{$criteria->searchq|escape:"html"}">Keywords search for '<b>{$criteria->searchq|escape:"html"}</b>'</button>
{/if}

	{if $pos_tag}

		<button type="submit" name="{$multipleon}" value="text:[{$criteria->searchq|escape:"html"}]">Images <b>tagged with [{$criteria->searchq|escape:"html"}]</b></button>
	{/if}

<h3 style="border-bottom:1px solid silver">Place search</h3>

<p>We have found the following possible match{if count($criteria->matches) > 1}es{/if} for '{$criteria->searchq|escape:"html"}': {if count($criteria->matches) > 0}<br/><small>(hover over a placename for the <a href="/faq.php#counties">historic county</a>, or click a grid reference to go directly to that square)</small>{/if}</p>


{foreach key=name item=value from=$post}
	{if $value && $name != 'placename' && $name != 'go'}
		<input type="hidden" name="{$name}" value="{$value|escape:'html'}">
	{/if}
{/foreach}
<input type="hidden" name="old-{$multipleon}" value="{$criteria->searchq|escape:'html'}">
{foreach from=$criteria->matches item=match}
	<tt><a href="/gridref/{$match.gridref}">{$match.gridref}</a></tt>
	<button type="submit" name="{$multipleon}" value="{$match.id}" id="match{$match.id}"{if $match.hist_county} title="Historic County: {$match.hist_county}"{/if}><b>{$match.full_name}</b></button><small><i>{if $match.adm1_name}, {$match.adm1_name}{/if}, {$references[$match.reference_index]}</i>
	<small>[{$match.dsg_name}]</small></small></label> <br/>
{foreachelse}
	<i>none</i>
{/foreach}

{if !$criteria->ismore}
	<br/>
	Place looking for not listed above? <button type="submit" name="{$multipleon}" value="{$criteria->searchq|escape:"html"}?"><b>Try a wider place search</b></button> <br/>
{/if}

<h3 style="border-bottom:1px solid silver">Other alternatives</h3>

{if $pos_realname}
	<button type="submit" name="{$multipleon}" value="user:{$pos_user_id}">Pictures taken <b>by {$pos_realname|escape:"html"}</b></button> {if $pos_nickname}(nickname: <b>{$pos_nickname|escape:"html"}</b>){/if} <a href="/profile/{$pos_user_id}" title="profile for {$pos_realname|escape:"html"}">Profile</a> <br/>
	<br/>
{/if}

{if preg_match('/near\s+/',$post.q) && !preg_match('/near\s+\(anywhere\)/',$post.q)}
	<button type="submit" name="{$multipleon}" value="text:{$post.q|replace:'near ':' '|escape:"html"}">Keyword search for '{$post.q|replace:'near ':' '|escape:"html"}'</button> <br/>
{elseif $post.q && $post.q != $criteria->searchq && preg_match('/near\s+$/',$post.q)}
	<button type="submit" name="{$multipleon}" value="text:{$post.q|escape:"html"} {$criteria->searchq|escape:"html"}">Keyword search for '{$post.q|escape:"html"} {$criteria->searchq|escape:"html"}'</button> <br/>
{elseif $post.searchtext && $post.searchtext != $criteria->searchq}
	1<button type="submit" name="{$multipleon}" value="text:{$post.searchtext|escape:"html"} {$criteria->searchq|escape:"html"}">Keyword search for '{$post.searchtext|escape:"html"} {$criteria->searchq|escape:"html"}'</button> <br/>
{elseif $post.q && $post.location && $post.location != '(anywhere)'}
	2<button type="submit" name="{$multipleon}" value="text:{$post.q|escape:"html"} {$post.location|escape:"html"}">Keyword search for '{$post.q|escape:"html"} {$post.location|escape:"html"}'</button> <br/>
{/if}


<button type="submit" name="{$multipleon}" value="text:{$criteria->searchq|escape:"html"}">Images matching '<b>{$criteria->searchq|escape:"html"}</b>' keyword(s)</button><br/>

{if $pos_tag}
	<button type="submit" name="{$multipleon}" value="text:[{$criteria->searchq|escape:"html"}]">Images tagged <b>with [{$criteria->searchq|escape:"html"}]</b></button><br/>
{/if}


<h3 style="border-bottom:1px solid silver"></h3>

<p>if none of the above fit, <input type="submit" name="refine" value="Refine further"> (goes to advanced search form)</p>

</form>

<br/><br/>

{if $suggestions}
	<div><b>Did you mean:</b>
	<ul>
	{foreach from=$suggestions item=row}
		<li><b><a href="/search.php?q={$row.query|escape:'url'}+near+{$row.gr}">{$row.query} <i>near</i> {$row.name}</a></b>? <small>({$row.localities})</small></li>
	{/foreach}
	</ul></div>
	<br/><br/>
{/if}
{/dynamic}

<div class="copyright">Great Britain locations based upon 1:50 000 Scale Gazetteer with the permission of Ordnance Survey on behalf of The Controller of Her Majesty's Stationery Office,<br/>
&copy; Crown copyright Ordnance Survey. All Rights Reserved. Educational licence 100045616.<br/>
<br/>
and enhanced with the Gazetteer of British Place Names, &copy; Association of British Counties, used with permission.<br/><br/>
Natural Language Query Parsing by {external href="http://developers.metacarta.com/" text="MetaCarta Web Services"}, Copyright MetaCarta 2006</div>

<script>
{literal}

function submitForm(theid) {
	document.getElementById(theid).checked = true;
	setTimeout('document.getElementById("'+theid+'").checked = true;document.theForm.submit()',100);
	return false;
}

{/literal}</script>

{include file="_std_end.tpl"}
