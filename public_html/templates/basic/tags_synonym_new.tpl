{include file="_std_begin.tpl"}
{dynamic}
<form class="simpleform" action="{$script_name}?tag={if $theprefix}{$theprefix|escape:'url'}:{/if}{$tag|escape:"url"}" method="post" name="theForm">

<input type="hidden" name="id" value="{$id|escape:"html"}"/>

<fieldset style="width:800px">
<legend>Synonym Suggestion Form</legend>

<p>Use this form to suggest that a tag is suffienctly similar to another tag, that the two tags can be "merged". ie they are synonums of each other. The 'Primary' tag will be the one shown in preference, so should be the main prefered version of the tag. Once we receive your suggestion, it will be reviewed, and hopefully applied shortly. If there is demand will open a voting process for moderating suggestions.</p>

<p>Note: This form is not for correcting typos or other issues - <a href="report.php?tag={if $theprefix}{$theprefix|escape:'url'}:{/if}{$tag|escape:"url"}">there is a special form for that</a> - but when the two forms are perfectly valid (just different).</p>

<hr/>

{if $message}
	<p>{$message}</p>
{/if}
{literal}
<style>
.column {
	width:300px; 
	float:left;
}
.column .item a {
	display:inline-block;
	width:200px;
	text-decoration:none;
	border:1px solid silver;
	border-radius:3px;
	padding:2px;
}
.column .item input[type="radio"] {
	/* by default hide the canon-radio */
	display:none;
}
.column .item input[type="checkbox"]:checked+a{ 
	/* highlight the active items */
	font-weight: bold; 
}
.column .item input[type="radio"]:checked{ 
	/* highlight the canon item*/
	background-color:yellow;
}
.column .item input[type="checkbox"]:checked~input[type="radio"]{
	/* show the canon-radio, if active */
        display:inline-block;
}
.column .item input[type="checkbox"]:checked~button{
	/* hide the X button, if active */
        display:none;
}
.column .item button:hover~a {
	background-color:yellow;
}

</style>
{/literal}

<container id="styles">
{if $canonical}
	<style id="hider{$canonical.tag_id}">.class{$canonical.tag_id} {literal}{ display:none }{/literal}</style>
{/if}
{if $synonyms}{foreach from=$synonyms item=row}
	<style id="hider{$row.tag_id}">.class{$row.tag_id} {literal}{ display:none }{/literal}</style>
{/foreach}{/if}
</container>

<div id="primary" class="column">
	{if $canonical}
		<div class="item">
			<input type=checkbox name="active[{$canonical.tag_id}]" checked>
			<a href="/tagged/{if $canonical.prefix}{$canonical.prefix|escape:'urlplus'}:{/if}{$canonical.tag|escape:'urlplus'}?exact=1"
			class="link">{if $canonical.prefix}{$canonical.prefix|escape:'html'}:{/if}{$canonical.tag|escape:'html'}</a>
			<input type=radio name="canonical" value="{$canonical.tag_id}" checked>
			<button type=button onclick="removeItem(this)">X</button>
		</div>
	{/if}
	{if $synonyms}{foreach from=$synonyms item=row}
		<div class="item">
			<input type=checkbox name="active[{$row.tag_id}]" checked>
			<a href="/tagged/{if $row.prefix}{$row.prefix|escape:'urlplus'}:{/if}{$row.tag|escape:'urlplus'}?exact=1"
			class="link">{if $row.prefix}{$row.prefix|escape:'html'}:{/if}{$row.tag|escape:'html'}</a>
			<input type=radio name="canonical" value="{$canonical.tag_id}">
			<button type=button onclick="removeItem(this)">X</button>
		</div>
	{/foreach}{/if}
</div>
<div class="column">
	Search: <input type=search name=q id=q value="{if $canonical.prefix}{$canonical.prefix|escape:'html'}:{/if}{$canonical.tag|escape:'html'}">
	<div id="results">
	</div>
</div>
<div class="column" id="pro">
</div>


{literal}
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js" type="text/javascript"></script>
<script>
	function removeItem(that) {
		$(that).parent().remove();
	}
	function useItem(that) {
		var tag_id = $(that).parent().attr('data-id');
		var text = $(that).parent().find('a').text();

		$('#primary').append(
			'<div class="item">'+
                        '<input type=checkbox name="active['+tag_id+']" checked> '+
                        '<a href="/tagged/'+encodeURIComponent(text)+'?exact=1"'+
                        ' class="link">'+text+'</a> '+
                        '<input type=radio name="canonical" value="'+tag_id+'">'+
                        '<button type=button onclick="removeItem(this)">X</button>'+
			'</div>');

		$(that).parent().remove();
		//$('container#styles').append(
		//	' <style id="hider'+tag_id+'">.class'+tag_id+' a {literal}{ color:silver; }</style>');
	}


	function loadTagSuggestions(div,query,mode,event) {

		if (event) {
			var unicode=event.keyCode? event.keyCode : event.charCode;
			if (unicode == 13) {
				return;
			}
		}

		url = "/tags/tags.json.php";
		url = url + '?mode='+encodeURIComponent(mode);
		if (mode == 'suggestions' || mode == 'prospective' || mode == 'automatic') {
			url = url + '&string='+encodeURIComponent(query);
		} else {
			url = url + '&q='+encodeURIComponent(query);
		}
		url = url + '&limit=100&counts=1'; //counts is to get the tag_id

		$.getJSON(url,

		// on search completion, process the results
		function (data) {
			div = $(div).empty();

			if (data && data.length > 0) {
				for(var idx in data) {
					var text = data[idx].tag;
					if (data[idx].prefix && data[idx].prefix!='term' && data[idx].prefix!='category' && data[idx].prefix!='cluster' && data[idx].prefix!='wiki') {
						text = data[idx].prefix+':'+text;
					}
					text = text.replace(/<[^>]*>/ig, "");
					text = text.replace(/['"]+/ig, " ");

					div.append('<div class="item class'+data[idx].tag_id+'" data-id="'+data[idx].tag_id+'"><button type=button onclick="useItem(this)">Use</button> <a href="/tagged/'+text+'">'+text+'</a></div>');
				}

			} else if (data.error) {
				div.text(data.error);
			} else {
				div.text("no tags/images");
			}
		});
	}
{/literal}

//loadTagSuggestions(div,query,mode,event)

{if $canonical}
	loadTagSuggestions('#results',$('input#q').val(),'ranked',false);
	loadTagSuggestions('#pro',$('input#q').val(),'prospective',false);
{/if}
</script>
{/dynamic}
{include file="_std_end.tpl"}

