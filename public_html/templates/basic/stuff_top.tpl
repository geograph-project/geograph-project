{assign var="page_title" value="Geographical Context Project"}
{include file="_std_begin.tpl"}
{dynamic}
<h2><a href="?">Geographical Context Mapping</a></h2>

{if $done}
	<p>Congratulations. Nothing more to do right now.</p>
{elseif $mode}

	<h3>Original category</h3>
	<div class="interestBox" style="padding-left:20px">
		<h4>{$imageclass|escape:'html'}</h4>
	</div>
		<a href="/search.php?imageclass={$imageclass|escape:"url"}" class="nowrap" target="_blank" style="font-size:0.7em">View images in new window</a>

	<h3>Geographical Context</h3>

	<form method="post" action="{$script_name}?mode={$mode}" name="theForm">
		<input type="hidden" name="imageclass" value="{$imageclass|escape:'html'}"/>

		<div class="interestBox">
			Choose one:

			<p{if !$prev} style="display:none"{/if}><input type="radio" name="top" value="prev" id="iprev" onclick="checkform()"/> <label for="iprev"><b>Agree with:</b></label> - <select name="prev" onchange="checkform(1)"><option value=""></option>
			{foreach from=$prev item=i}<option>{$i.top|escape:'html'}</option>{/foreach}
			</select> (previous suggestions)</p>

			<p><input type="radio" name="top" value="other" id="iother" onclick="checkform()"/> <label for="iother"><b>Use this one:</b></label> - <select name="other" onchange="checkform(2)"><option value=""></option>
			{foreach from=$list item=i}<option{if $i.count < 3} style="color:gray"{/if}>{$i.top|escape:'html'}</option>{/foreach}
			</select> (if unsure, please refer to <a href="/tags/primary.php" target="_blank">Geographical Context category list</a>)</p>


			<p><input type="radio" name="top" value="bad" id="ibad" onclick="checkform()"/> <label for="ibad"><b>Unallocated category</b></label> - there isnt really a Geographical Context for this</p>

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



	<p>The general idea is to assign a "Geographical Context" to each current Category</p>

<ul>
<il><b><a href="/tags/primary.php">Page listing the Geographical Context Category list</a></b></li>
</ul>

	<h3>First stage - suggesting initial Geographical Context</h3>
	<blockquote>

	<b>One By One</b>
	<div class="interestBox">

		Choose one:
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
			<small>works with at most 100 matching categories</small>
		</form>

	</div><br/>

	<b>Bulk Form</b>
	<div class="interestBox">

		<form method="get">
			Keyword Search: <input type="text" name="bulk" value=""/>
			<input type="submit" value="Matching categories"/><br/>
			<small>works with at most 250 matching categories (<a href="/stuff/category-list.php?unused&full">Full category list</a> for ideas :)</small>
		</form>

	</div><br/>

	<b>Bulk SpreadSheet</b>
	<div class="interestBox">
		<a href="/article/Assigning-the-current-Categories-to-Top-Level-Categories-Project#bulk-spreadsheet">read more...</a>
	</div><br/>

	&middot; <a href="?sample=1">View sample mapping</a>
	&middot; <a href="?review=1">Review your recent suggestions</a> &middot;<br/><br/>

	<b>View preliminary results</b><br/>
	&middot; <a href="?top=1">Canonical categories list</a>
	&middot; <a href="?preview=1">Category tree</a> &middot;
	</blockquote>



	<h3>Further links</h3>
	<blockquote>
	&middot; <a href="?stats=1">Statistics</a> &middot;<br/><br/>


	<b>View confirmed results</b><br/>
	&middot; <a href="?final=1">Category tree</a> &middot;

	<!--b>Demo</b>
	 &middot; <a href="/stuff/category.php?type=top">Example of a category selector using Geographical Context</a> &middot;-->


	</blockquote>
{/if}
	<br/><br/>

{/dynamic}
{include file="_std_end.tpl"}
