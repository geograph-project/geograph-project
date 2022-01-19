{assign var="page_title" value="Typo Hunter"}
{include file="_std_begin.tpl"}
{dynamic}
<h2><a href="/admin/typolist.php">Typos</a> :: Typo Hunter v0.9 {if $criteria}<small style="font-weight:normal">, submitted at or before: {$criteria|escape:'html'}</small>{/if}</h2>

<form action="{$script_name}" method="get">
	<div class="interestBox">
		<label for="include"><b>Include</b>:</label> <input type="text" size="40" name="include" value="{$include|escape:'html'}" id="include" onchange="document.getElementById('subbutton').style.display='none';"/> |
		<label for="exclude">Exclude (optional):</label> <input type="text" size="40" name="exclude" value="{$exclude|escape:'html'}" id="exclude"  onchange="document.getElementById('subbutton').style.display='none';"/>
		<input type="submit" value="Find" style="font-size:1.1em"/><br/>
		<select name="profile" onchange="document.getElementById('subbutton').style.display='none'; document.getElementById('moreopt').style.display=(this.selectedIndex==1)?'none':''">
			<option value="phrase">phrase - legacy style 'substring' matching</option>
			<option value="keywords"{if $profile=='keywords'} selected{/if}>keywords - new style whole-word keywords engine</option>
			<option value="expression"{if $profile=='expression'} selected{/if}>expression - case sensitive regular-expression engine</option>
		</select> |
		<span id="moreopt" {if $profile=='keywords'} style="display:none"{/if}>
		<label for="size">Number of images to search:</label> <select name="size" id="size">{html_options options=$sizes selected=$size}</select> |
		</span>
	</div>
	{if $include}
	<div style="text-align:right;max-width:900px" id="subbutton">
		{if $typo_id}
			(already on list)
		{else}
			<input type="submit" name="save" value="Save to List {if $old_id}(as new){/if}">
			{if $old_id}
				<br>or 
				<input type="submit" name="over" value="Overwrite [ {$old_title|escape:'html'} ]">
			{/if}
		{/if}
	</div>
	{/if}
	{if $old_id}
		<input type="hidden" name="old_id" value="{$old_id}">
	{/if}
</form>


	<br/>

	{foreach from=$images item=image}
	  <form action="/editimage.php?id={$image->gridimage_id}&amp;thumb=1" method="post" target="editor" style="background-color:#{cycle values="eaeaea,f8f8f8"};padding:8px 0;">
		<a name="{$image->gridimage_id}"><input type="text" name="title" size="80" value="{$image->title|escape:'html'}" spellcheck="true" onchange="this.style.backgroundColor=(this.value!=this.defaultValue)?'pink':''" style="max-width:80%"/></a>
		<br/>
		{if $image->title_html}
			{$image->title_html}<br>
		{/if}
		[[<a href="/photo/{$image->gridimage_id}">{$image->gridimage_id}</a>]] for square <a title="view page for {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a>{if $image->realname} by <a title="view user profile" href="/profile/{$image->user_id}">{$image->realname}</a>{/if}<br/>
		{if $image->imageclass}<small>Category: {$image->imageclass}</small>{/if}

		<div>{if $image->comment}<textarea name="comment" style="font-size:0.9em;max-width:80%" rows="7" cols="110" spellcheck="true" onchange="this.style.backgroundColor=(this.value!=this.defaultValue)?'pink':''">{$image->comment|escape:'html'}</textarea>{/if}<input type="submit" name="create" value="continue &gt;"/>
		{if $image->comment_html}
			<br>{$image->comment_html}
		{/if}
		</div>
	  </form>
	{foreachelse}
		{if $include}
			<i>No results</i>
		{/if}
		<ul>
			<li>For <b>'phrase'</b> searches:<br/><br/>
				<ul>
					<li>Only searches the <b>most recent and moderated</b> images (number configurable above)<br>
						(but note, if 'Saved' to list, the watchlist function will <i>eventually</i> run it against entire archive!)<br/><br/></li>
					<li>Both Include and Exclude boxes accept a <b>single exact string</b>, including special charactors; matches part words (but not case sensitive). There is NO special syntax (including <tt>OR</tt> etc)<br/><br/></li>
					<li>Looks in the title &amp; description <b>only</b>, as that is the most useful for typo hunting.</li>
				</ul></li>
			<li>For <b>'keyword'</b> searches:<br/><br/>
				<ul>
					<li>Searches ALL moderated images<br/><br/></li>
					<li>Include/Exclude boxes accept a <b>word search</b>, a list of keywords. Can use "phrase" and/or <b>=</b> to disable stemming, but other features not recommended.<ul>
						<li><tt>"the the"</tt> - searches for the two words immirately following each other - as a phrase</li>
						<li><tt>ont he</tt> - requires both words, in any order (don't have to be adjacent) - still subject to stemming</li>
						<li><tt>=maintainance</tt> - exact word, not subject to stemming. but still case insensitive.</li>
						<li><tt>"=trough =the"</tt> - the = must go on each individual keyword, if used and multiword</li>
					</ul>
					<br></li>
					
					<li>looks in the title, description and category <b>only</b><br/><br/></li>
				</ul></li>
			<li>Searches are can be saved so can be re-run easily (and powers automatic watchlist). <a href="/admin/typolist.php">View results here</a><br/><br/></li>
		</ul>
	{/foreach}

	<br/><br/>

	{if $image_count}
		<p>Showing {$image_count} image(s){if $image_count eq 50}, there might be more{/if}.</p>
	{/if}

<br/><br/>
{if $next}
<div class="interestBox">Navigation: <b>|
	<a href="{$script_name}?next={$next|escape:'url'}">Next</a> |
</b>
</div>
{/if}

<p><small>Note: {if $profile=='phrase'}Only searches the last {$size} images and{/if} only includes moderated images.<br/>
Page generated at 1 hour intervals, please don't refresh more often than that.</small></p>

{/dynamic}
{include file="_std_end.tpl"}
