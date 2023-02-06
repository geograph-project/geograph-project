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
	form#filter span.select {
		padding-right:10px;
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

{if $source}
	<details open>
		<summary>Data Source</summary>
		{$source|escape:'html'|nl2br}
	</details>
{/if}

<script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
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

<a href="edit_item.php?id=new&amp;type_id={$feature_type_id}" class="popupLink">Add a new Feature</a>

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
