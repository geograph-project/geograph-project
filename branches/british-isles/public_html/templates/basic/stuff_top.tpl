{assign var="page_title" value="Top-Level Categories"}
{include file="_std_begin.tpl"}
{dynamic}
<h2><a href="?">Top-Level Category Mapping</a></h2>

{if $done}
	<p>Congratulations. Nothing more to do right now.</p>
{elseif $mode}
	<div class="interestBox" style="background-color:yellow;padding:20px;border:2px solid pink">
		<h2>WARNING - currently in Test mode</h2>
			This page is only for testing. While you can make suggestions below, <b>only do one or two to check it works</b>. The list of Top Level Categories hasnt been finalised yet. <u>So any suggestions made in the testing phase may well end up being deleted.</u>
	</div>

	<div style="float:right;width:200px;font-size:0.7em;position:relative;padding:8px;background-color:white">
	This is only the first pass over assigning top categories, don't worry too much about deciding borderline cases. Do all the easy ones, and give a good guess for the rest. We will have a second stage resolving the conflicts between suggestions.</div>

	<h3>Original category</h3>
	<div class="interestBox" style="padding-left:20px;">
		<h4>{$imageclass|escape:'html'}</h4>
	</div>
		<a href="/search.php?imageclass={$imageclass|escape:"url"}" class="nowrap" target="_blank" style="font-size:0.7em">View images in new window</a>

	<h3>Top-Level category</h3>

	<form method="post" action="{$script_name}?mode={$mode}" name="theForm">
		<input type="hidden" name="imageclass" value="{$imageclass|escape:'html'}"/>

		<div class="interestBox">
			Choose one:

			<p{if !$prev} style="display:none"{/if}><input type="radio" name="top" value="prev" id="iprev" onclick="checkform()"/> <label for="iprev"><b>Agree with:</b></label> - <select name="prev" onchange="checkform(1)"><option value=""></option>
			{foreach from=$prev item=i}<option>{$i.top|escape:'html'}</option>{/foreach}
			</select> (previous suggestions)</p>

			<p><input type="radio" name="top" value="other" id="iother" onclick="checkform()"/> <label for="iother"><b>Use this one:</b></label> - <select name="other" onchange="checkform(2)"><option value=""></option>
			{foreach from=$list item=i}<option{if $i.count < 3} style="color:gray"{/if}>{$i.top|escape:'html'}</option>{/foreach}
			</select> (current top list)</p>


			<p><input type="radio" name="top" value="bad" id="ibad" onclick="checkform()"/> <label for="ibad"><b>Nonsense category</b></label> - there isnt really a Top-Level category for this</p>

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
			f.elements['top'][autoselect-1].checked = true;
		}

		var good=false;
		if (f.elements['top'][0].checked) {
			if (f.elements['prev'].selectedIndex>0) {
				good=true;
			}
		} else if (f.elements['top'][1].checked) {
			if (f.elements['other'].selectedIndex>0) {
				good=true;
			}
		} else if (f.elements['top'][2].checked) {
			good=true;
		}

		f.elements['submit'].disabled = !good;
	}
	 AttachEvent(window,'load',function() { checkform(); },false);

	{/literal}</script>
	<br/><br/>
	<a href="?">Start over</a>
{else}



	<p>The general idea is to assign a Top-Level-Category to each current Category</p>


	<h3>First stage - suggesting initial top-level-category</h3>
	<blockquote>

	<div class="interestBox">

		<form method="get" style="display:inline">
			<input type="hidden" name="mode" value="random"/>
			<input type="submit" value="Randomly selected categories"/>
		</form>

		- or -

		<form method="get" style="display:inline">
			<input type="hidden" name="mode" value="alpha"/>
			<input type="submit" value="In alphabetical order"/>
		</form>

		- or -

		<form method="get" style="display:inline">
			<input type="hidden" name="mode" value="unmapped"/>
			<input type="submit" value="Unmapped categories"/>
		</form>

		<br/><br/>
		<form method="get">
			- or - Keyword Search: <input type="text" name="mode" value=""/>
			<input type="submit" value="Matching categories"/><br/>
			<small>works with at most 100 matching categories
			- uses the same format as the 'Auto-complete' category selector</small>
		</form>

	</div><br/>

	&middot; <a href="?sample=1">View sample mapping</a>
	&middot; <a href="?review=1">Review your recent suggestions</a> &middot;<br/><br/>

	<b>View preliminary results</b>
	&middot; <a href="?top=1">Canonical categories list</a>
	&middot; <a href="?preview=1">Category tree</a> &middot;
	</blockquote>



	<h3>Further links</h3>
	<blockquote>
	&middot; <a href="?stats=1">Statistics</a> &middot;<br/><br/>


	<b>View confirmed results</b>
	&middot; <a href="?final=1">Category tree</a> &middot;

	<!--b>Demo</b>
	 &middot; <a href="/stuff/category.php?type=top">Example of a category selector using top-level</a> &middot;-->


	</blockquote>
{/if}
	<br/><br/>

{/dynamic}
{include file="_std_end.tpl"}
