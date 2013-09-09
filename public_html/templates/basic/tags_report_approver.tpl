{include file="_std_begin.tpl"}

<div class="tabHolder">
        <a class="tab nowrap" href="?">Public Suggestion Form</a>
        <a class="tab nowrap" href="?admin=1">Admin Suggestion Form</a>
        <a class="tab nowrap" href="?finder=1">Quick Tag Searcher</a>
        <a class="tabSelected nowrap" href="?approver=1">Approve Suggestions</a>
</div>
<div class="interestBox">
        <h3>Tag Report/Suggestion Approver</h3>
</div>


<form class="simpleform" method="post" name="theForm">

<fieldset style="width:800px">

{dynamic}

{if $report.report_id}

	<input type="hidden" name="report_id" value="{$report.report_id|escape:'html'}">

	{if $message}
		<p>{$message}</p>
	{/if}

	<div style="float:left;width:100px">Type:</div> <i>{$report.type|escape:'html'}</i>, by <a href="/profile/{$report.user_id|escape:'url'}">{$report.realname|escape:'html'}</a>
	<hr/>

	<div id="tag-message" style="float:right"></div>
	<div style="float:left;width:100px">Original: <a href="http://www.google.com/search?q={$report.tag|escape:'url'}" target="_blank">G</a></div>
	<tt style="font-size:1.2em">{$report.tag|escape:'html'}</tt>
	<input type="hidden" name="tag" value="{$report.tag|escape:'html'}"/>
	<hr/>

	<div id="tag2-message" style="float:right"></div>
	{if $report.tag2} 
		<div style="float:left;width:100px">New: <a href="http://www.google.com/search?q={$report.tag2|escape:'url'}" target="_blank">G</a></div>  
		<tt style="font-size:1.2em">{$report.tag2|escape:'html'}</tt> 
		<input type="hidden" name="tag2" value="{$report.tag2|escape:'html'}"/>
	{else}
		<b>No suggestion made</b>. Please use <a href="report.php?admin=1&amp;close=1#t={$report.tag|escape:'url'}" target="_blank">Tag suggestion form</a> to create a new suggestion. Then return here and reject this suggestion.
	{/if}
	<hr/>

	<p>
		{if $report.tag2}
			<input type="submit" name="approve" value="Approve Suggestion" style="font-size:1.1em; color:green"/>
		{/if}
		<input type="submit" name="reject" value="Reject Suggestion" style="font-size:1.1em; color:red"/>
		<input type="submit" name="skip" value="Skip Suggestion" style="font-size:1.1em; color:gray"/>
	</p>
	{if $report.tag2}
		<p>If don't agree with the supplied suggestion, please use <a href="report.php?admin=1&amp;close=1#t={$report.tag|escape:'url'}" target="_blank">Tag suggestion form</a> to create a new suggestion. Then return here and reject this suggestion.</p>
	{/if}
	<p>Use Skip, to abstain from dealing with this suggestion.</p>

	<ul>
		<li>Remember this process is only for correctly clear mistakes and typos. Reject any suggestion that changes the wording, meaning or 'style' of the tag. This includes any suggestion clearly just intended to 'merge' multiple similar tags.
	</ul>

{else}
	<p>No more reports to process!</p>
{/if}

{/dynamic}
</form>

<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js" type="text/javascript"></script>
<script>
{literal}

$(function() {
	var form = document.forms['theForm'];
	if (form.elements['tag']) {
		loadTagSuggestions(form.elements['tag'],{keyCode:0});
	}
	if (form.elements['tag2']) {
		loadTagSuggestions(form.elements['tag2'],{keyCode:0});
	}
});

	function loadTagSuggestions(that,event) {

		param = 'q='+encodeURIComponent(that.value);

		$.getJSON("/tags/tag.json.php?"+param+((that.name == 'tag')?'&expand=1':''),

		// on search completion, process the results
		function (data) {
			var div = document.getElementById(that.name+'-message');

			if (data && data.tag_id) {

				var text = data.tag;
				if (data.prefix) {
					text = data.prefix+':'+text;
				}
				text = text.replace(/<[^>]*>/ig, "");
				text = text.replace(/['"]+/ig, " ");

				str = 'Found [<b><a href="/search.php?tag='+encodeURIComponent(text)+'" target="_blank">'+text+'</a></b>]';

				if (data.images) {
					str = str + " used by "+data.images+" images";
				}

				if (data.users) {
					str = str + ", by "+data.users+" users";
				}
			} else if (data.error) {
				if (that.name == 'tag') {
					str = data.error;
				} else {
					str = 'no tags/images';
				}
			} else {
				if (that.name == 'tag') {
					str = "tag not found!";
				} else {
					str = "no tags/images";
				}
			}
			$('#'+that.name+'-message').html(str);
		});
	}



{/literal}
</script>



{include file="_std_end.tpl"}

