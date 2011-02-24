{assign var="page_title" value="Top-Level Categories"}
{include file="_std_begin.tpl"}
<script src="{"/sorttable.js"|revision}"></script>

<h2><a href="?">Top-Level Category Mapping</a> :: Import</h2>

	<p>Import a list of suggestions in bulk, using the form below. This is designed to be used in conjunction with the Spreadsheet mentioned in the forum.</p>

	<form action="/stuff/category-list.php" method="post">
		<p style="border:2px solid pink">You can fetch a plain list of categories to work on: <input type=submit value="view plain list"><br/>
		(<b>you are given a unique list of 100 categories</b>, so only click the button if you intend to actully process the list, and submit it below.)</p>
		<p>You can get a similar list for just <a href="/stuff/category-list.php?mine">categories used on your own images</a>, or if feeling really adventious, get the <a href="/stuff/category-list.php?full">full list</a>.</p>
	</form>

{dynamic}
	{if $rows || $skipped}
		<p class="error"><b>{$rows}</b> Suggestions Processed. {if $skipped}With {$skipped} rows skipped.{/if} {if $affected}And {$affected} affected records.{/if}
	{/if}
{/dynamic}


	<form action="{$script_name}?import=1" method="post" class="interestBox">
		<b>Import suggestions:</b> (one per line, category, then top-level-category. Seperated by tabs, or semicolons)<br/>
		<textarea name="text" rows="10" cols="80" wrap="off"></textarea><br/>
		&middot; It's your responsiblity to make sure you only submitting suggestions from the latest Top-Level-Category List!<br/>
		<input type="submit" name="import" value="Import list"/>

		<hr/>
		<b>Example</b> (your lines should look something like this)<br/>
		<textarea name="example" rows="10" cols="80" readonly="readonly" disabled="disabled" style="font-size:0.9em">Churchyard gate	Town & city
Door knocker	-bad-
Harbour wall (ruined)	Docks & harbours
Indoor market	Commerce/Retail/Services
Memorial woodland	Forestry
Mountain railway	Railways
RAF airfield (former)	Defence
Water bottling plant	Manufacturing & Construction</textarea><br/>
	</form>


<a href="?">Go Back</a>

<br/><br/>

{include file="_std_end.tpl"}
