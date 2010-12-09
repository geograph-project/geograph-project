{assign var="page_title" value="Canonical Categories"}
{include file="_std_begin.tpl"}
{dynamic}
<h2><a href="?">Canonical Category Mapping</a></h2>

{if $done}
	<p>Congratulations. Nothing more to do right now.</p>
{elseif $mode}
	<div style="float:right;width:200px;font-size:0.7em;position:relative;padding:8px;background-color:white">
	This is only the first pass over assigning canonical categories, don't worry too much about deciding borderline cases. Do all the easy ones, and give a good guess for the rest. We will have a second stage resolving the conflicts between suggestions.</div>
	
	<h3>Original Category</h3>
	<div class="interestBox" style="padding-left:20px;">
		<h4>{$imageclass|escape:'html'}</h4>
	</div>
		<a href="/search.php?imageclass={$imageclass|escape:"url"}" class="nowrap" target="_blank" style="font-size:0.7em">View images in new window</a>
	
	<h3>Canonical Category</h3>
	
	<form method="post" action="{$script_name}?mode={$mode}" name="theForm">
		<input type="hidden" name="imageclass" value="{$imageclass|escape:'html'}"/>
		
		<div class="interestBox">
			Choose one:
			
			<p><input type="radio" name="canonical" value="asis" id="iasis" onclick="checkform()"/> <label for="iasis"><b>Use as is (<tt>{$imageclass|escape:'html'}</tt>)</b></label> - creating the canonical category if required</p>

			<p{if !$prev} style="display:none"{/if}><input type="radio" name="canonical" value="prev" id="iprev" onclick="checkform()"/> <label for="iprev"><b>Agree with:</b></label> - <select name="prev" onchange="checkform(1)"><option value=""></option>
			{foreach from=$prev item=i}<option>{$i.canonical|escape:'html'}</option>{/foreach}
			</select> (previous suggestions)</p>

			<p><input type="radio" name="canonical" value="other" id="iother" onclick="checkform()"/> <label for="iother"><b>Use this one:</b></label> - <select name="other" onchange="checkform(2)"><option value=""></option>
			{foreach from=$list item=i}<option{if $i.count < 3} style="color:gray"{/if}>{$i.canonical|escape:'html'}</option>{/foreach}
			</select> (current canonical list)</p>

			<p><input type="radio" name="canonical" value="new" id="inew" onclick="checkform()"/> <label for="inew"><b>Create new:</b></label> - <input type="text" name="new" value="" maxlength="32" onkeyup="checkform(3)" onclick="checkform(3)"> (<a href="javascript:void(copyit());">copy</a> current, then edit)</p>

			<p><input type="radio" name="canonical" value="bad" id="ibad" onclick="checkform()"/> <label for="ibad"><b>Nonsense category</b></label> - this category needs review. A category such as 'Geograph' is clearly nonsensical.</p>
		
		</div>
		<br/><br/>
		<input type="submit" name="submit" value="submit suggestion" id="submitButton"/>	
	</form>	
	<script type="text/javascript">{literal}
	function copyit() {
		var f=document.theForm;
		f.elements['new'].value = f.elements['imageclass'].value;
	}
	function checkform(autoselect) {
		var f=document.theForm;
		if (autoselect) {
			f.elements['canonical'][autoselect].checked = true;
		}

		var good=false;
		if (f.elements['canonical'][0].checked) {
			good=true;
		} else if (f.elements['canonical'][1].checked) {
			if (f.elements['prev'].selectedIndex>0) {
				good=true;
			}
		} else if (f.elements['canonical'][2].checked) {
			if (f.elements['other'].selectedIndex>0) {
				good=true;
			}
		} else if (f.elements['canonical'][3].checked) {
			if (f.elements['new'].value.length>1 && f.elements['new'].value != f.elements['imageclass'].value) {
				good=true;
			}
		} else if (f.elements['canonical'][4].checked) {
			good=true;
		}
		
		f.elements['submit'].disabled = !good;
	}
	 AttachEvent(window,'load',function() { checkform(); },false);
	
	{/literal}</script>
	<br/><br/>
	<a href="?">Start over</a>
{else}
<div class="interestBox" style="background-color:pink">
        The Canonical Category project is an highly experimental attempt to create a simplified category listing. The project is ongoing.
</div>


	<p>The idea is to create a simplified short category list. By maintaining a mapping of full categories to canonical form, we can offer searching by a greatly simplified category dropdown. The full category would still be attached to the image to maintain the more specific information.</p>


	<h3>First Stage - Suggesting initial canonical categories</h3>
	<blockquote>
	<p>Use this section to suggest {external href="http://en.wikipedia.org/wiki/Canonical" text="canonical" title='Basic, canonic, canonical: reduced to the simplest and most significant form possible without loss of generality, e.g., "a basic story line"; "a canonical syllable pattern."'} categories for the full category list.</p>

	<div class="interestBox">
	
		<form method="get" style="display:inline">
			<input type="hidden" name="mode" value="random"/>
			<input type="submit" value="Randomly Selected Categories"/>
		</form>

		- or -

		<form method="get" style="display:inline">
			<input type="hidden" name="mode" value="alpha"/>
			<input type="submit" value="In Alphabetical Order"/>
		</form>
	
		- or -

		<form method="get" style="display:inline">
			<input type="hidden" name="mode" value="unmapped"/>
			<input type="submit" value="Unmapped categories"/>
		</form>
	
		<br/><br/>	
		<form method="get">
			- or - Keyword Search: <input type="text" name="mode" value=""/>
			<input type="submit" value="Matching Categories"/><br/>
			<small>works with at most 100 matching categories 
			- uses the same format as the 'Auto-complete' category selector</small>
		</form>

	</div><br/>
	
	&middot; <a href="?sample=1">View Sample Mapping</a>	
	&middot; <a href="?rename=1">Rename/Correct your recent suggestions</a>
	&middot; <a href="?review=1">Review your recent suggestions</a> &middot;<br/><br/>
	
	<b>View Preliminary Results</b>
	&middot; <a href="?canonical=1">Canonical Categories List</a>
	&middot; <a href="?preview=1">Category Tree</a> &middot;
	</blockquote>
	
	<h3>Second Stage - Merging Duplicate categories</h3>
	<blockquote>
	<div class="interestBox">
	
		<a href="?canonical=1">Current Canonical Categories List</a>
		
		- or -

		<form method="get" style="display:inline">
			<input type="hidden" name="mode" value="rename"/>
			<input type="submit" value="Vote on suggestions"/>
		</form>
		<br/>
		<small>tick to select categories you think should be renamed on the first link</small>
	</div>
	
	
	</blockquote>
	
	<h3>Further Links</h3>
	<blockquote>
	&middot; <a href="?stats=1">Statistics</a> &middot;<br/><br/>
	
	
	<b>View Confirmed Results</b>
	&middot; <a href="?final=1">Category Tree</a> &middot;

	<b>Demo</b>
	 &middot; <a href="/stuff/category.php?type=canonicalplus">Example of a category selector using canonical</a> &middot;

	
	</blockquote>
{/if}
	<br/><br/>

{/dynamic}
{include file="_std_end.tpl"}
