{include file="_std_begin.tpl"}

{literal}
    <style>
	details summary {
                background-color: #eee;
                border-radius:10px;
                padding:10px;
	} 
	details {
		margin-bottom:10px;
	}
	table#output thead th {
		background-color:black;
		color:white;
	}
	table#output thead a {
		color:silver;
		cursor:pointer;
	}
	form#filter {
		background-color: #eee;
		border-radius:3px;
		padding:10px;
	}
	form#filter input[type=search] {
		font-family:inherit;
		font-size:1em;
	}
	form#filter span.select {
		padding-left:10px;
		white-space:nowrap;
	}
	div.pages {
		margin:8px;
		line-height:1.3em;
	}
	div.pages b {
		border-radius:3px;
		padding:3px;
		color:brown;
		background-color:silver;
	}
	div.pages a {
		border-radius:3px;
		padding:3px;
		background-color:#eee;
	}
        .black_overlay{
            display: none;
            position: absolute;
            top: 0%;
            left: 0%;
            width: 100%;
            height: 100%;
            background-color: black;
            z-index:1001;
            -moz-opacity: 0.8;
            opacity:.80;
            filter: alpha(opacity=80);
        }
        .white_content {
            display: none;
            position: absolute;
            top: 20%;
            left: 20%;
            width: 60%;
            height: 60%;
            border: 6px solid orange;
            background-color: white;
            z-index:10000;
            overflow: hidden;
        }
	.white_content iframe {
		width:100%;
		height:100%;
		border:0;
	}
    </style>

{/literal}

{if $isadmin}
	<div style="float:right">
		<a href="edit.php?id={$feature_type_id}">Edit Dataset</a>
	</div>
{/if}

<h2 style="margin-bottom:0px" class="nowrap"><a href="/features/">Datasets</a> :: {$title|escape:'html'}</h2>
<div>By <a title="View profile" href="/profile/{$user_id}">{$realname|escape:'html'}</a></div>
<br>

{if $content}
	<details open>
		<summary>Introduction</summary>
		{$content|escape:'html'|nl2br}
	</details>
{/if}

{if $licence && $licence != 'none'}
	<details open>
		<summary>Dataset Licence</summary>

		{if $licence == 'copyright'}
			<small>&copy;</small> Copyright <a href="/profile/{$user_id}" title="View Geograph Profile for {$realname|escape:'html'}">{$realname|escape:'html'}</a>, {$published|date_format:" %B %Y"}

		{elseif $licence == 'cc-by-sa/2.0'}
			<!-- Creative Commons Licence -->
				&copy; Copyright {$published|date_format:" %B %Y"}, <a href="/profile/{$user_id}" title="View Geograph Profile for {$realname|escape:'html'}">{$realname|escape:'html'}</a>;
				licensed for re-use under a BY-SA/2.0 <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/" class="nowrap">Creative Commons Licence</a>.
			<!-- /Creative Commons Licence -->

		{elseif $licence == 'cc-by-sa/4.0'}
			<!-- Creative Commons Licence -->
				&copy; Copyright {$published|date_format:" %B %Y"}, <a href="/profile/{$user_id}" title="View Geograph Profile for {$realname|escape:'html'}">{$realname|escape:'html'}</a>;
				licensed for re-use under a BY-SA/4.0 <a rel="license" href="http://creativecommons.org/licenses/by-sa/4.0/" class="nowrap">Creative Commons Licence</a>.
			<!-- /Creative Commons Licence -->

		{elseif $licence == 'geograph'}
			<!-- Creative Commons Licence -->
				&copy; Copyright {$published|date_format:" %B %Y"}, <a href="/" title="Geograph Project">Geograph Project Ltd</a>;
				licensed for re-use under a BY-SA/4.0 <a rel="license" href="http://creativecommons.org/licenses/by-sa/4.0/" class="nowrap">Creative Commons Licence</a>.
			<!-- /Creative Commons Licence -->

		{elseif $licence == 'odbl'}
			This {$title|escape:'html'} is made available under the Open Database License: <a rel="license" href="http://opendatacommons.org/licenses/odbl/1.0/">http://opendatacommons.org/licenses/odbl/1.0/</a>. 
			Any rights in individual contents of the database are licensed under the Database Contents License: <a href="http://opendatacommons.org/licenses/dbcl/1.0/" class="nowrap">http://opendatacommons.org/licenses/dbcl/1.0/</a>

		{elseif $licence == 'ogl'}
			Contains public sector information licensed under the Open Government Licence v3.0.
			<a rel="license" href="https://www.nationalarchives.gov.uk/doc/open-government-licence/version/3/">https://www.nationalarchives.gov.uk/doc/open-government-licence/version/3/</a>

		{else}
			by <a href="/profile/{$user_id}" title="View Geograph Profile for {$realname|escape:'html'}">{$realname|escape:'html'}</a>, {$published|date_format:" %B %Y"};
			This work is dedicated to the <a rel="license" href="http://creativecommons.org/licenses/publicdomain/">Public Domain</a>.
		{/if}

		{if $moreCredits}
			<div class="ccmessage" style="color:gray; font-size:0.8em; text-align:right">With contributions by {$moreCredits}. (<a href="/article/history.php?page={$url|escape:'url'}">details</a>)</div>
		{/if}
		{if $imageCredits && $licence != 'cc-by-sa/2.0'}
			<div class="ccmessage" style="color:gray; font-size:0.9em; text-align:right">Images are under a separate <a href="#imlicence">Creative Commons Licence</a>.</div>
		{elseif $imageCredits}
			<div class="ccmessage" style="color:gray; font-size:0.9em; text-align:right">Images also under a similar <a href="#imlicence">Creative Commons Licence</a>.</div>
		{/if}

	</details>
{/if}

{if $source}
	<details open>
		<summary>Data Source</summary>
		{$source|escape:'html'|nl2br|GeographLinks:false}
	</details>
{/if}

{if $create_enabled}
	<div style="border:1px solid #a2a9b1;margin:10px;padding:6px;border-radius:6px;display:inline-block">
	&#128712;
	This list is <b>incomplete</b>; you can help by <a href="edit_item.php?id=new&amp;type_id={$feature_type_id}" class="popupLink">adding missing items</a>.
	The dataset is being created by Geograph.
	</div>
{/if}



<script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
<link href="{"/js/select2-3.3.2/select2.css"|revision}" rel="stylesheet"/>
<script src="{"/js/select2-3.3.2/select2.js"|revision}"></script>
<script src="{"/features/view.js"|revision}"></script>
<script>
var feature_type_id = {$feature_type_id};
var editing = {dynamic}{if $user->registered}1{else}0{/if}; //{$isadmin}{/dynamic}

var columns = {$item_columns};
var resultCount = {$count};
</script>

<form id="filter"><input type=hidden name=id value={$feature_type_id}></form>
<div id="status"></div>
<div class="pages"></div>
<table id="output" cellspacing=0 cellpadding=2 border=1 bordercolor=#eee></table>
<div class="pages"></div>

<br>
{if $create_enabled}
	<a href="edit_item.php?id=new&amp;type_id={$feature_type_id}" class="popupLink">Add a new Feature</a>
{else}
	This is an imported dataset. If there is missing or inaccurate data, the original dataset needs updating. Please contact us, if the dataset has updated, and our copy is now outdated. 
{/if}

{if $footnote}
	<details open>
		<summary>Footnote</summary>
		{$footnote|escape:'html'|nl2br}
	</details>
{/if}

<div id="light" class="white_content">
<iframe src="about:blank" id="iframe" width="100%" height="100%"></iframe>
</div><div id="fade" class="black_overlay" onclick="closePopup()"></div>


{include file="_std_end.tpl"}
