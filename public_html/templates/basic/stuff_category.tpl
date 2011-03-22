{assign var="page_title" value="Category Tester"}
{include file="_std_begin.tpl"}

<h2>Category Selection Tester</h2>


 <div class="interestBox" style="margin:10px">
   <form method="get" action="{$script_name}" style="display:inline">
    <select name="type" onchange="this.form.submit()">
    	{html_options options=$types selected=$type}
    </select>
  <noscript>
    <input type="submit" value="Update"/></noscript></form></div>



    <form enctype="multipart/form-data" action="{$script_name}" method="post" name="theForm" onsubmit="if (this.imageclass) this.imageclass.disabled=false;" {if $step ne 1}style="background-color:#f0f0f0;padding:5px;margin-top:0px; border:1px solid #d0d0d0;"{/if}>



{if $type eq 'top'}

{assign var="tab" value=$v+1}
	<div class="tabHolder" style="margin-left:10px">
		<a class="tab{if $tab == 1}Selected{/if} nowrap" href="?type=top&amp;v=0">Variation A</a>
		<a class="tab{if $tab == 2}Selected{/if} nowrap" href="?type=top&amp;v=1">Variation B</a>
		<a class="tab{if $tab == 3}Selected{/if} nowrap" href="?type=top&amp;v=2">Variation C</a>
		<a class="tab{if $tab == 4}Selected{/if} nowrap" href="?type=top&amp;v=3">Variation D</a>
		<a class="tab{if $tab == 5}Selected{/if} nowrap" href="?type=top&amp;v=4">Variation E</a>
		<a class="tab{if $tab == 6}Selected{/if} nowrap" href="?type=top&amp;v=5">Variation F</a>
	</div>
	<div class="interestBox">
		<h3>Dummy submission</h3>
	</div>







....

<p style="clear:both"><label for="comment"><b>Description/Comment</b></label> <span class="formerror" style="display:none" id="commentstyle">Possible style issue. See Guide above. <span id="commentstylet"></span></span><br/>
<textarea id="comment" name="comment" rows="7" cols="80" spellcheck="true" onblur="checkstyle(this,'comment',true);" onkeyup="checkstyle(this,'comment',false);">{$comment|escape:'html'}</textarea></p>

<div style="font-size:0.7em">TIP: use <span style="color:blue">[[TQ7506]]</span> to link to a Grid Square or <span style="color:blue">[[54631]]</span> to link to another Image.<br/>
For a weblink just enter directly like: <span style="color:blue">http://www.example.com</span><br/><br/></div>

<div>
	<div style="float:right"><a href="/article/Shared-Descriptions" text="Article about Shared Descriptions" class="about" target="_blank">About</a></div>
	<label><b>Shared Descriptions/References (Optional)</b></label>
	<span id="hideshare"><input type=button onclick="show_tree('share'); document.getElementById('shareframe').src='/submit_snippet.php?upload_id={$upload_id}&gr={$grid_reference|escape:'html'}';return false;" value="Expand"/></span>
	<div id="showshare" style="display:none">
		<iframe src="about:blank" height="400" width="98%" id="shareframe" style="border:2px solid gray">
		</iframe>
		<div><a href="#" onclick="hide_tree('share');return false">- Close <i>Shared Descriptions</I></a></div>
	</div>
</div>
<br/>

	<div style="float:right">Categories have changed! <a href="/article/Transitioning-Categories-to-Tags" text="Article about new tags and categories" class="about" target="_blank">Read More</a></div>

	<p><label for="top"><b>Geographical Context</b></label>{if $v == 3} <small style="font-size:0.7em">(tick as many as required, hover over name for a description){/if}</small><br />
		{if $v == 5}
			{foreach from=$tops key=key item=item}
				<div style="position:relative;float:left;">
					<b>{$key}</b><br/>
					<select id="top" name="top" multiple="multiple" size="13">
						{html_options options=$item selected=$top}
					</select>
				</div>
			{/foreach}
			<br style="clear:both"/> (To select multiple - hold down control, and select)
		{elseif $v == 4}
			{assign var="tab" value="1"}
			<div class="tabHolder" style="margin-left:10px">
				{foreach from=$tops key=key item=item name=tree}
						<a class="tab{if $tab == $smarty.foreach.tree.iteration}Selected{/if} nowrap" id="tab{$smarty.foreach.tree.iteration}" onclick="tabClick('tab','div',{$smarty.foreach.tree.iteration},6)">{$key}</a>&nbsp;
				{/foreach}
			</div>
			{foreach from=$tops key=key item=item name=tree}
				<div style="position:relative;{if $tab != $smarty.foreach.tree.iteration}display:none{/if}"  class="interestBox" id="div{$smarty.foreach.tree.iteration}">

					{html_checkboxes options=$item selected=$top separator=" | "}
				</div>
			{/foreach}
			<br style="clear:both"/>(tick as many as required)
		{elseif $v == 3}
			<style type="text/css">{literal}
.plist {
	position:relative;float:left;width:190px;border-left:1px solid silver;padding-left:2px
}
.plist label {
    padding-left: 32px ;
    text-indent: -32px ;
	display:block;
}
			{/literal}</style>

			{foreach from=$tops key=key item=item}
				<div class="plist">
					<b>{$key}</b><br/>
					{foreach from=$item item=row}
						<label for="c-{$row.top|escape:'url'}" title="{$row.description|escape:'html'}">
							<input type="checkbox" name="checkbox[]" name="{$row.top|escape:'html'}" id="c-{$row.top|escape:'url'}"/>
							{$row.top|escape:'html'}
						</label>
					{/foreach}
				</div>
			{/foreach}
			<br style="clear:both"/>
		{elseif $v == 2}
			(tick as many as required)
			{foreach from=$tops key=key item=item}
				<b>{$key}</b><br/>
				&nbsp;&nbsp;&nbsp;{html_checkboxes options=$item selected=$top}<br/>
				<br/>
			{/foreach}
		{elseif $v == 1}
		<select id="top" name="top" multiple="multiple" size="15">
			{html_options options=$tops selected=$top}

		</select> (To select multiple - hold down control, and select)
		{else}
		<select id="top" name="top">
			<option value="">--please select feature--</option>
			{html_options options=$tops selected=$top}

		</select> (To select additional, or to add free-form tags, open the tagging box below...)
		{/if}
	</p>

<p><label><b>Tags (Optional)</b> <input type="button" value="expand" onclick="show_tree('tag'); document.getElementById('tagframe').src='/tags/tagger.php?ids=1,2&amp;v={$v}';" id="hidetag"/></p>
<div id="showtag" style="display:none">
	<ul>
		<li>Tags are simple free-form keywords/short phrases, used to describe the image.</li>
		<li>Please add as many Tags as you need. Tags will help other people find your photo.</li>
		<li>It is not compulsory to add any Tags.</li>
		<li>Note: Tags should be singular, ie a image of a Church should have the tag "Church", not "Churches" - it's a specific tag, not a category.<br/> <small>(however if photo is of multiple fence posts, then the tag "Fence Posts" should be used)</small></li>
		<li>Adding a placename as a tag, please prefix with "place:", eg "place:Croydon" - similarlly could use "near:Tring".</li>
		<li>... read more in {newwin href="/article/Tags" text="Article about Tags"}</li>
	</ul>
	<iframe src="about:blank" height="200" width="100%" id="tagframe">
	</iframe>

</div>


<p><label><b>Date photo taken</b></label> {if $error.imagetaken}
	<br/><span class="formerror">{$error.imagetaken}</span>
	{/if}<br/>
	{html_select_date prefix="imagetaken" time=$imagetaken start_year="-200" reverse_years=true day_empty="" month_empty="" year_empty="" field_order="DMY"}
	{if $imagetakenmessage}
	    {$imagetakenmessage}
	{/if}

	[ Use
	<input type="button" value="Today's" onclick="setdate('imagetaken','{$today_imagetaken}',this.form);" class="accept"/>
	{if $last_imagetaken}
		<input type="button" value="Last Submitted" onclick="setdate('imagetaken','{$last_imagetaken}',this.form);" class="accept"/>
	{/if}
	{if $imagetaken != '--' && $imagetaken != '0000-00-00'}
		<input type="button" value="Current" onclick="setdate('imagetaken','{$imagetaken}',this.form);" class="accept"/>
	{/if}
	Date ]

	<br/><br/>....


{elseif $type eq 'autocomplete'}

	<p><label for="imageclass"><b>Primary geographical category</b></label><br />
		<input size="32" id="imageclass" name="imageclass" value="{$imageclass|escape:'html'}" maxlength="32" spellcheck="true"/>
		</p>
	{literal}
	<script type="text/javascript">
	<!--

	AttachEvent(window,'load', function() {
		var inputWord = $('imageclass');

	    new Autocompleter.Request.JSON(inputWord, '/finder/categories.json.php', {
		'postVar': 'q',
		'minLength': 2,
		maxChoices: 60
	    });

	},false);

	//-->
	</script>
	{/literal}

{else}

	{literal}
	<script type="text/javascript">
	<!--
	//rest loaded in geograph.js
	function mouseOverImageClass() {
		if (!hasloaded) {
			setTimeout("prePopulateImageclass2()",100);
		}
		hasloaded = true;
	}

	function prePopulateImageclass2() {
		var sel=document.getElementById('imageclass');
		sel.disabled = false;
		var oldText = sel.options[0].text;
		sel.options[0].text = "please wait...";

		populateImageclass();

		hasloaded = true;
		sel.options[0].text = oldText;
		if (document.getElementById('imageclass_enable_button'))
			document.getElementById('imageclass_enable_button').disabled = true;
	}

	function showDetail() {
		{/literal}{if $type eq 'canonicalplus' || $type eq 'canonicalmore'}{literal}

		var sel=document.getElementById('imageclass');

		var idx=sel.selectedIndex;

		var isOther=idx==sel.options.length-1;

		if (idx > 0 && !isOther) {

			canonical = sel.options[sel.selectedIndex].value;

			{/literal}{if $type eq 'canonicalmore'}
			url = "/finder/categories.json.php?more=1&canonical="+encodeURIComponent(canonical);
			{else}
			url = "/finder/categories.json.php?canonical="+encodeURIComponent(canonical);
			{/if}{literal}

			var req = new Request({
				method: 'get',
				url: url,
				onComplete: function(response) {
					var sel=document.getElementById('imageclass2');

					var opt=sel.options;

					//clear out the options
					for(q=opt.length;q>=0;q=q-1) {
						opt[q] = null;
					}
					opt.length = 0; //just to confirm!
					//re-add the first
					opt[0] = new Option('Optionally select a more detailed category...','');

					var optionsList = JSON.decode(response);

					if (optionsList.length == 0 || (optionsList.length == 1 && optionsList[0] == canonical)) {
						document.getElementById('detailblock').style.display='none';
						return;
					}

					//add the whole list
					for(i=0; i < optionsList.length; i++) {
						act = optionsList[i];
						if (act != canonical) {
							opt[opt.length] = new Option(act,act);
						}
					}
				}
			}).send();

			document.getElementById('detailblock').style.display='';
		} else {
			document.getElementById('detailblock').style.display='none';
		}

		{/literal}{/if}{literal}
	}

	AttachEvent(window,'load',onChangeImageclass,false);
	AttachEvent(window,'load',showDetail,false);
	//-->
	</script>
	{/literal}

	{if $type eq 'canonicalmore'}
	<p><label for="imageclass"><b>Primary geographical category</b> (Unmoderated Full Canonical List)</label><br />
	{elseif $type eq 'canonical' || $type eq 'canonicalplus'}
	<p><label for="imageclass"><b>Primary geographical category</b> (Simplified List)</label><br />
	{else}
	<p><label for="imageclass"><b>Primary geographical category</b></label><br />
	{/if}
		<select id="imageclass" name="imageclass" onchange="onChangeImageclass();showDetail()" onfocus="prePopulateImageclass()" onmouseover="mouseOverImageClass()" style="width:300px">
			<option value="">--please select feature--</option>
			{if $imageclass}
				<option value="{$imageclass}" selected="selected">{$imageclass}</option>
			{/if}
			<option value="Other">Other...</option>
		</select>

		{if $type eq 'canonicalplus' || $type eq 'canonicalmore'}
			<span id="detailblock">
				<select id="imageclass2" name="imageclass2">
					<option value="">-- please wait ... --</option>
				</select>
			</span>
		{/if}

		<span id="otherblock">
		<label for="imageclassother">Please specify </label>
		<input size="32" id="imageclassother" name="imageclassother" value="{$imageclassother|escape:'html'}" maxlength="32" spellcheck="true"/>

		{if $type eq 'canonical' || $type eq 'canonicalplus' || $type eq 'canonicalmore'}
			<br/>Note: This doesn't automatically create a new Canonical Category, rather just adds it as a normal category, it will be assigned to a canonical category via a collaborative review.
		{/if}

		</span></p>

{/if}


{if $type eq 'autocomplete'}
	<link rel="stylesheet" type="text/css" href="{"/js/Autocompleter.css"|revision}" />

	<script type="text/javascript" src="{"/js/mootools-1.2-core.js"|revision}"></script>
	<script type="text/javascript" src="{"/js/Observer.js"|revision}"></script>
	<script type="text/javascript" src="{"/js/Autocompleter.js"|revision}"></script>
	<script type="text/javascript" src="{"/js/Autocompleter.Request.js"|revision}"></script>

{elseif $type eq 'canonical' || $type eq 'canonicalplus' || $type eq 'canonicalmore'}
	<script type="text/javascript" src="/categories.js.php?canonical=1{if $type eq 'canonicalmore'}&amp;more=1{/if}"></script>
	<script type="text/javascript" src="/categories.js.php?full=1&amp;u={$user->user_id}"></script>

	{if $type eq 'canonicalplus' || $type eq 'canonicalmore'}
		<script type="text/javascript" src="{"/js/mootools-1.2-core.js"|revision}"></script>
	{/if}
{else}
	<script type="text/javascript" src="/categories.js.php"></script>
	<script type="text/javascript" src="/categories.js.php?full=1&amp;u={$user->user_id}"></script>
{/if}

</form>

{include file="_std_end.tpl"}
