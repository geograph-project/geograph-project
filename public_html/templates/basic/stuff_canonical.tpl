{assign var="page_title" value="Canonical Categories"}
{include file="_std_begin.tpl"}
{dynamic}
<h2>Canonical Category Mapping</h2>

{if $done}
	<p>Congratulations. Nothing more to do right now.</p>
{elseif $mode}
	
	<h3>Original Category</h3>
	<div class="interestBox" style="padding-left:20px;">
		<h4>{$imageclass|escape:'html'}</h4>
	</div>
	
	<h3>Canonical Category</h3>
	
	<form method="post" action="{$script_name}?mode={$mode}" name="theForm">
		<input type="hidden" name="imageclass" value="{$imageclass|escape:'html'}"/>
		
		<div class="interestBox">
			Choose one:
			
			<p><input type="radio" name="canonical" value="asis" id="iasis" onclick="checkform()"/> <label for="iasis"><b>Use as is (<tt>{$imageclass|escape:'html'}</tt>)</b> - creating the canonical category if required</label></p>

			<p><input type="radio" name="canonical" value="other" id="iother" onclick="checkform()"/> <label for="iother"><b>Use this one:</b> - <select name="other" onchange="checkform()"><option value=""></option>
			{foreach from=$list item=i}<option>{$i|escape:'html'}</option>{/foreach}
			</select> (current canonical list)</label></p>

			<p><input type="radio" name="canonical" value="new" id="inew" onclick="checkform()"/> <label for="inew"><b>Create new:</b> - <input type="text" name="new" value="" maxlength="32" onkeyup="checkform()"></label> (<a href="javascript:void(copyit());">copy</a> current, then edit)</p>

			<p><input type="radio" name="canonical" value="bad" id="ibad" onclick="checkform()"/> <label for="ibad"><b>Nonsence category</b> - this should be changed. A category such as 'Geograph' is clearly non-sensical.</label></p>
		
		</div>
		<br/><br/>
		<input type="submit" name="submit" value="submit suggestion" disabled id="submitButton"/>	
	</form>	
	<script type="text/javascript">{literal}
	function copyit() {
		var f=document.theForm;
		f.elements['new'].value = f.elements['imageclass'].value;
	}
	function checkform() {
		var f=document.theForm;
		var good=false;
		if (f.elements['canonical'][0].checked) {
			good=true;
		} else if (f.elements['canonical'][1].checked) {
			if (f.elements['other'].selectedIndex>0) {
				good=true;
			}
		} else if (f.elements['canonical'][2].checked) {
			if (f.elements['new'].value.length>3 && f.elements['new'].value != f.elements['imageclass'].value) {
				good=true;
			}
		} else if (f.elements['canonical'][3].checked) {
			good=true;
		}
		
		f.elements['submit'].disabled = !good;
	}
	
	{/literal}</script>
	<br/><br/>
	<a href="?">Start over</a>
{else}

	<p>Use this section to suggest {external href="http://en.wikipedia.org/wiki/Canonical" text="canonical" title='Basic, canonic, canonical: reduced to the simplest and most significant form possible without loss of generality, e.g., "a basic story line"; "a canonical syllable pattern."'} categories for the full category list. The idea is to create a simplified short category list. By maintaining a mapping of full categories to canonical form, we can offer searching by a greatly simplified category dropdown. The full category would still be attached to the image to maintain the more specific information.</p>

	Please choose: -

	<form method="get">
		<input type="hidden" name="mode" value="random"/>
		<input type="submit" value="Randomly Selected Categories"/>
	</form>

	- or -

	<form method="get">
		<input type="hidden" name="mode" value="alpha"/>
		<input type="submit" value="In Alphabetical Order"/>
	</form>

	- or -

	<form method="get">
		Keyword Search: <input type="text" name="mode" value=""/>
		<input type="submit" value="Matching Categories"/> - works with at most 100 matching categories<br/>
		(uses the same format as the 'Auto-complete' category selector)
	</form>

	- or -

	<form method="get">
		<input type="hidden" name="mode" value="unmapped"/>
		<input type="submit" value="Unmapped categories"/>
	</form>

	<br/><br/>
	
	<a href="?sample=1">View Sample Mapping</a>

{/if}
	<br/><br/>

{/dynamic}
{include file="_std_end.tpl"}
