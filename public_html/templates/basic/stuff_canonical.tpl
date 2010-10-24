{assign var="page_title" value="Canonical Categories"}
{include file="_std_begin.tpl"}
{dynamic}
<h2><a href="?">Canonical Category Mapping</a></h2>

{if $done}
	<p>Congratulations. Nothing more to do right now.</p>
{elseif $mode}
	<p>This is only the first pass over assigning canonical categories, don't worry too much about deciding borderline cases. Do all the easy ones, and give a good guess for the rest. We will have a second stage resolving the conflicts between suggestions.</p>
	
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

			<p><input type="radio" name="canonical" value="other" id="iother" onclick="checkform()"/> <label for="iother"><b>Use this one:</b></label> - <select name="other" onchange="checkform(1)"><option value=""></option>
			{foreach from=$list item=i}<option{if $i.count < 3} style="color:gray"{/if}>{$i.canonical|escape:'html'}</option>{/foreach}
			</select> (current canonical list)</p>

			<p><input type="radio" name="canonical" value="new" id="inew" onclick="checkform()"/> <label for="inew"><b>Create new:</b></label> - <input type="text" name="new" value="" maxlength="32" onkeyup="checkform(2)" onclick="checkform(2)"> (<a href="javascript:void(copyit());">copy</a> current, then edit)</p>

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
			if (f.elements['other'].selectedIndex>0) {
				good=true;
			}
		} else if (f.elements['canonical'][2].checked) {
			if (f.elements['new'].value.length>1 && f.elements['new'].value != f.elements['imageclass'].value) {
				good=true;
			}
		} else if (f.elements['canonical'][3].checked) {
			good=true;
		}
		
		f.elements['submit'].disabled = !good;
	}
	 AttachEvent(window,'load',function() { checkform(); },false);
	
	{/literal}</script>
	<br/><br/>
	<a href="?">Start over</a>
{else}

	<p>Use this section to suggest {external href="http://en.wikipedia.org/wiki/Canonical" text="canonical" title='Basic, canonic, canonical: reduced to the simplest and most significant form possible without loss of generality, e.g., "a basic story line"; "a canonical syllable pattern."'} categories for the full category list. The idea is to create a simplified short category list. By maintaining a mapping of full categories to canonical form, we can offer searching by a greatly simplified category dropdown. The full category would still be attached to the image to maintain the more specific information.</p>

	<p>Please choose: -</p>

	<form method="get">
		<input type="hidden" name="mode" value="random"/>
		<input type="submit" value="Randomly Selected Categories"/>
	</form>

	<p>- or -</p>

	<form method="get">
		<input type="hidden" name="mode" value="alpha"/>
		<input type="submit" value="In Alphabetical Order"/>
	</form>

	<p>- or -</p>

	<form method="get">
		Keyword Search: <input type="text" name="mode" value=""/>
		<input type="submit" value="Matching Categories"/> - works with at most 100 matching categories<br/>
		(uses the same format as the 'Auto-complete' category selector)
	</form>

	<p>- or -</p>

	<form method="get">
		<input type="hidden" name="mode" value="unmapped"/>
		<input type="submit" value="Unmapped categories"/>
	</form>

	<br/><br/>
	
	&middot; <a href="?sample=1">View Sample Mapping</a><br/>
	
	<h4>View Preliminary Results</h4>
	&middot; <a href="?stats=1">Statistics</a>
	&middot; <a href="?canonical=1">List Canonical Categories</a>
	&middot; <a href="?preview=1">View Category Tree</a>
	&middot; <a href="?final=1">View Confirmed results</a><br/>
	
	<h4>Your suggestions</h4>
	&middot; <a href="?rename=1">Rename/Correct your recent suggestions</a>
	&middot; <a href="?review=1">Review recent suggestions</a><br/>
	</p>
	
{/if}
	<br/><br/>

{/dynamic}
{include file="_std_end.tpl"}
