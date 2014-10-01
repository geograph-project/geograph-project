{assign var="page_title" value="Grouped by `$groupname`"}
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
{/literal}

{if !$inner}

        {if $gridref}
                {include file="_bar_location.tpl"}
                <div class="interestBox">
			<h2><a href="/finder/">Finder</a> :: Grouped By {$groupname}</h2>
                </div>
        {else}
		<h2><a href="/finder/">Finder</a> :: Grouped By {$groupname}</h2>
        {/if}


{if $queries}
	<div class="interestBox" style="float:right">
	<form action="{$script_name}" method="get">
	     Or pick: <select name="q" onchange="this.form.submit()">
		<option value="">example queries...</option>
	        {html_options options=$queries selected=$q}
	     </select><br/><br/>
	</form>
	</div>
{/if}

<form action="{$script_name}" method="get" onsubmit="focusBox()">
	<label for="fq">Keywords</label>: <input type="text" name="q" id="fq" size="40"{if $q} value="{$q|escape:'html'}"{/if}/>
	<input type="submit" value="Search"/><br/>
	{if $groupings}
	     <label for="fgroup">Group By: <select name="group" id="fgroup">
	        {html_options options=$groupings selected=$group}
	     </select>
		{literal}<div style="float:right"><input type=button value="shuffle" onclick="with (this.form.elements['group']) { selectedIndex = (selectedIndex==options.length-1)?0:selectedIndex+1; if (options[selectedIndex].text=='') {selectedIndex=selectedIndex+1} this.form.submit()}"></div>{/literal}
	{/if}
</form>
{/if}



{assign var="last" value="-1"}
{foreach from=$results item=image}
	{if $image->group != $last}
		{if $last != -1}
			</div>
		{/if}
		<div class="interestBox" style="clear:both">
	
		{if $image->images > 3 && $group != 'cluster'}
			<div style="float:right"><a href="/browser/#!/q={$image->group|escape:'url'}+{$q|escape:'url'}">View {$image->images} text matches in {$image->group}</a></div>
		{/if}
	
		<big {if $image->group|strlen < 30}style=font-size:2em{/if}><b><a href="/browser/#!/q={$image->group|escape:'url'}+{$q|escape:'url'}" title="{$image->images} images in {$image->group}">{$image->group|replace:'0000s':'unknown'}</a></b></big> 
	
		{if $image->images}
			<small style="color:green">(<b>{$image->images|thousends}</b> images)</small>
		{/if}
	
		</div>
		<div style="position:relative;height:{$thumbh+90}px;overflow:hidden">
	{/if}
	{assign var="last" value=$image->group}

	
		<div style="float:left;width:160px" class="photo33"><div style="height:{$thumbh}px;vertical-align:middle"><a title="{$image->grid_reference} : {$image->title|escape:'html'} by {$image->realname} {$image->dist_string} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail($thumbw,$thumbh,false,true,'data-src')}</a></div>
		<div class="caption"><div class="minheightprop" style="height:2.5em"></div><a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a><div class="minheightclear"></div></div>
		<div class="statuscaption">by <a href="{$image->profile_link}">{$image->realname}</a></div>
		</div>
		{if $image->query}
			<div style="float:left;width:0;height:0" class="morediv" data-query="{$image->query|escape:'html'}" data-id="{$image->gridimage_id}"></div>
		{/if}	
{foreachelse}
	{if $q}
		<i>There is no content to display at this time.</i>
	{/if}
{/foreach}
		{if $last != -1}
			</div>
		{/if}

<br style="clear:both"/>

<script src="http://{$static_host}/js/lazy.v2.js" type="text/javascript"></script>
{literal}
<script type="text/javascript">

function initLazy2() {
        jQuery( 'div.morediv' ).bind( 'scrollin', function() {
                var $div = jQuery(this);

                $div.hide();

                $div.unbind( 'scrollin' ); // clean up binding

		$.ajax({
			url: "?query="+encodeURIComponent($div.attr('data-query'))+"&skip="+$div.attr('data-id'),
			dataType: 'html',
			cache: true,
			success: function (html) {
				$div.after(html);
			}
		});

        });
}
</script>
{/literal}
<script src="/preview.js.php?d=preview" type="text/javascript"></script>

<div style="margin-top:0px"> 
{if $pagesString}
	( Page {$pagesString})
{/if}
</div>	

{if $query_info}
	<p>{$query_info} : <a href="/browser/#!/q={$q|escape:'url'}">View All</a></p>
{/if}


{if !$inner}
<div class="interestBox" style="margin-top:60px;">
	<big><a name="cheatsheet"></a>Cheatsheet</big>:
	<ul class="explore">
		<li>prefix a keyword with - to <b>exclude</b> that word from the match; example: <tt>stone wall -sheep</tt></li>
		<li>use gridsquares, hectads or myriads as keywords <tt>stone wall sh65</tt> or <tt>stone wall tq</tt></li>
		<li>can use OR to match <b>either/or</b> keywords; example: <tt>bridge river OR canal</tt></li>
	</ul>
</div>
{/if}

{if $inner}
</body>
</html>
{else}
{include file="_std_end.tpl"}
{/if}
