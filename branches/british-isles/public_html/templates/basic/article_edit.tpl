{dynamic}
{assign var="page_title" value="Edit::$title"}

{include file="_std_begin.tpl"}
<script type="text/javascript">{literal}
function unloadMess() {
	var ele = document.forms['theForm'].elements['content'];
	if (ele.value == ele.defaultValue) {
		return;
	}
	return "**************************\n\nYou have unsaved changes in the content box.\n\n**************************\n";
}
//this is unreliable with AttachEvent
window.onbeforeunload=unloadMess;

function unloadDone() {
	new Image().src = "/article/edit.php?page={/literal}{$url|escape:"url"}{literal}&release";
}
AttachEvent(window,'unload',unloadDone,false);



function cancelMess() {
	window.onbeforeunload=null;
}
function setupSubmitForm() {
	AttachEvent(document.forms['theForm'],'submit',cancelMess,false);
}
AttachEvent(window,'load',setupSubmitForm,false);



function getXMLRequestObject()
{
	var xmlhttp=false;



	 try {
	  xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
	 } catch (e) {
	  try {
	   xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
	  } catch (E) {
	   xmlhttp = false;
	  }
	 }

	if (!xmlhttp && typeof XMLHttpRequest!='undefined') {
	  xmlhttp = new XMLHttpRequest();
	}

	return xmlhttp;
}

function lockArticle(form) {
	var url = "/article/edit.php?page={/literal}{$url|escape:"url"}{literal}&lock";

	//make the request
	var req=getXMLRequestObject();

	//need to exploit function closure
	req.onreadystatechange = function()
	{
		if (req.readyState==4)
		{
			if (req.responseText == 'ok') {
				form.elements['content'].disabled = false;
				form.elements['submit'].disabled = false;
				document.getElementById('buttonbar').style.display = '';
				document.getElementById('lockbar').style.display = 'none';
				document.getElementById('prompt1').style.display = '';
			} else {
				document.getElementById('lockbar').innerHTML=req.responseText;
			}
			//patch the memory leak
			req.onreadystatechange = function() {};
		}
	}
	req.open("GET", url,true);
	req.send(null)



}
function makeUneditable() {
	document.forms['theForm'].elements['content'].disabled = true;
	document.forms['theForm'].elements['submit'].disabled = true;
	document.getElementById('buttonbar').style.display = 'none';
}
{/literal}{if $approved == 2 && $user_id != $user->user_id}
	AttachEvent(window,'load',makeUneditable,false);
{/if}{literal}



{/literal}</script>
{if $error}
	<div><span class="formerror">{$error}</span></div>
{/if}

<form class="simpleform" action="/article/edit.php" method="post" name="theForm">

<input type="hidden" name="article_id" value="{$article_id|escape:"html"}"/>

	<div style="float:right;position:relative">
		New to Articles? See the {newwin href="/article/Help_on_formatting_of_articles" text="Overview Guide"}
	</div>

<fieldset>
{if $approved == 2 && $user_id != $user->user_id}
<legend>Edit Public Article</legend>

<div class="interestBox" id="prompt1" style="display:none;margin-bottom:20px">
	<a href="/article/edit.php?page={$url|escape:"url"}&amp;release=1">Close editor without saving edits</a><small> (leaving this page open prevents others from editing)</small>
</div>

<div class="interestBox" id="lockbar" style="margin-bottom:30px">
<input type="button" value="Lock Article so I can edit it" onclick="lockArticle(this.form);"/> <span style="color:red">Unlocks the edit box below</span>
</div>

{else}



{if $title == 'New Article'}
<legend>Create Article</legend>
{else}
<legend>Edit {if $approved == 2}Public Collaboration{/if} Article</legend>

<div class="field">
	{if $errors.url}<div class="formerror"><p class="error">{$errors.url}</p>{/if}

	<label for="url">URL:</label>
	<span class="nowrap"><small><tt>{$self_host}/article/</tt></small><input type="text" name="url" value="{$url|escape:"html"}" maxlength="64" size="40"/></span>

	<div class="fieldnotes">Shouldn't be changed once article published.</div>

	{if $errors.url}</div>{/if}
</div>
{/if}

<div class="field">
	{if $errors.title}<div class="formerror"><p class="error">{$errors.title}</p>{/if}

	<label for="title">Title:</label>
	<input type="text" name="title" value="{$title|escape:"html"}" style="font-size:1.1em" maxlength="64" size="47"/>

	{if $errors.title}</div>{/if}
</div>

<div class="field">
	{if $errors.licence}<div class="formerror"><p class="error">{$errors.licence}</p>{/if}

	<label for="licence">Licence:</label>
	<select name="licence">
	{html_options options=$licences selected=$licence}
	</select> by
	<a href="/profile/{$user_id}" title="View Geograph Profile for {$realname|escape:'html'}">{$realname|escape:'html'}</a>



	{if $errors.licence}</div>{/if}
</div>

<div class="field">
	{if $errors.publish_date}<div class="formerror"><p class="error">{$errors.publish_date}</p>{/if}

	<label for="publish_date">Publish Date:</label>
	{html_select_date prefix="publish_date" time=`$publish_date` start_year="-100" reverse_years=true day_empty="" month_empty="" year_empty="" field_order="DMY" day_value_format="%02d" month_value_format="%m"}

	<div class="fieldnotes">Date of copyright, when the piece was written.</div>

	{if $errors.publish_date}</div>{/if}
</div>

<div class="field">
	{if $errors.article_cat_id}<div class="formerror"><p class="error">{$errors.article_cat_id}</p>{/if}

	<label for="article_cat_id">Category:</label>
	<select name="article_cat_id">
	{html_options options=$article_cat selected=$article_cat_id}
	</select>



	{if $errors.article_cat_id}</div>{/if}
</div>



<div class="field">
	{if $errors.grid_reference}<div class="formerror"><p class="error">{$errors.grid_reference}</p>{/if}

	<label for="grid_reference">Relevant Grid Square:</label>
	<input type="text" name="grid_reference" value="{$grid_reference|escape:"html"}" maxlength="12" size="6"/>

	<div class="fieldnotes">Optional, helps organise Articles, such as enabling plotting on a map.</div>

	{if $errors.grid_reference}</div>{/if}
</div>

<div class="field">
	{if $errors.parent_url}<div class="formerror"><p class="error">{$errors.parent_url}</p>{/if}

	<label for="parent_url">Parent Article:</label>
	<input type="text" name="parent_url" value="{$parent_url|escape:"html"}" maxlength="128" size="60"/>

	<div class="fieldnotes">Optional, full url to parent article if there is one. To be used by Articles that are in a group.</div>

	{if $errors.parent_url}</div>{/if}
</div>

<div class="field">
	{if $errors.extract}<div class="formerror"><p class="error">{$errors.extract}</p>{/if}

	<label for="extract">Short Description:</label>
	<input type="text" name="extract" value="{$extract|escape:"html"}" maxlength="255" size="90" style="width:58em"/>

	<div class="fieldnotes">Please provide a meaningful short description of the content.</div>

	{if $errors.extract}</div>{/if}
</div>

{if $licence != 'Copyright'}
	<div class="field">
		{if $errors.edit_prompt}<div class="formerror"><p class="error">{$errors.edit_prompt}</p>{/if}

		<label for="edit_prompt">Edit prompt:</label>
		<input type="text" name="edit_prompt" value="{$edit_prompt|escape:"html"}" maxlength="160" size="90" style="width:58em"/>

		<div class="fieldnotes">If want a open collaboration article, enter a short message to prompt users to edit the article.<br/>Leave blank so that only you (and the moderators) can edit the article.</div>

		{if $errors.edit_prompt}</div>{/if}
	</div>
{/if}
{/if}

<div class="field">
	{if $errors.content}<div class="formerror"><p class="error">{$errors.content}</p>{/if}

	<label for="content">Content:</label> (see markup reference at bottom of page)
	<textarea rows="40" cols="80" name="content" style="width:58em">{$content|escape:"html"}</textarea></p>

	{if $errors.content}</div>{/if}
</div>

<div class="field">
	{if $errors.complete}<div class="formerror"><p class="error">{$errors.complete}</p>{/if}

	<label for="complete">Completeness:</label>
	<select name="complete" style="text-align:right">
	{html_options values=$completes output=$completes selected=$complete}
	</select>% <small><small>(a rough estimate of how complete the article is compared to what it could/should be)</small></small>

	{if $errors.complete}</div>{/if}
</div>



</fieldset>

<div id="buttonbar">
<input type="reset" name="reset" value="Reset" onclick="return confirm('Are you sure? Changes will be lost!');" style="color:green"/>
<input type="submit" name="submit" value="Save Changes..." style="font-size:1.1em; color:green"/> {if $title == 'New Article'}<br/>(Articles will only show on the site once they have been approved by a site moderator){/if}</p>

</div>

</form>

<script type="text/javascript">{literal}
function previewArticle() {
	var f1 = document.forms['theForm'];
	var f2 = document.forms['previewForm'];

	for (q=0;q<f2.elements.length;q++) {
		if (f2.elements[q].name && f1.elements[f2.elements[q].name]) {
			f2.elements[q].value = f1.elements[f2.elements[q].name].value;
		}
	}

	window.open('','_preview');//forces a new window rather than tab?

	return true;
}
{/literal}</script>
<form action="/article/article.php?url=preview" method="post" name="previewForm" target="_preview" style="padding:10px; margin-top:10px; text-align:center; border:2px solid black;">
	<input type="hidden" name="url"/>
	<input type="hidden" name="title"/>
	<input type="hidden" name="user_id" value="{$user_id}"/>
	<input type="hidden" name="realname" value="{$realname|escape:'html'}"/>
	<input type="hidden" name="licence"/>
	<input type="hidden" name="publish_date"/>
	<input type="hidden" name="article_cat_id"/>
	<input type="hidden" name="grid_reference"/>
	<input type="hidden" name="parent_url"/>
	<input type="hidden" name="extract"/>
	<input type="hidden" name="edit_prompt"/>
	<textarea name="content" style="display:none"/></textarea>
	<input type="hidden" name="complete"/>

	<input type="submit" value="Preview Article in a new window" onclick="return previewArticle()" id="previewButton"/>
	<label for="pagenum">Page:</label>
	<input type="text" name="page" id="pagenum" value="1" size="2" maxlength="2"/> - if multiple, Note: page links WON'T work in the preview<br/>
</form>



 <br/> <br/>
Or <a href="/article/edit.php?page={$url|escape:"url"}&amp;release=1">Close editor without saving edits</a><small> (leaving this page open prevents others from editing)</small>



<br/><br/><br/>
<div style="padding:5px; border: 1px solid gray; background-color:silver; font-size:0.9em">
| <b>Quick Reference</b> | {newwin href="/article/Help_on_formatting_of_articles" text="Full Reference"} |
<hr/>

<h2><tt>[h2]Big Title[/h2]</tt></h2>
<h3><tt>[h3]Sub Title[/h3]</tt></h3>
<small>&middot; h2, h3 and h4 will create an automatic table of contents</small>

<p><tt>[b]<b>Bold</b>[/b]</tt>, <tt>[i]<i>Italic</i>[/i]</tt> and <tt>[big]<big>Big</big>[/big]</tt></p>

<p><tt>* Bulleted List<br/>
* Item Two</tt></p>

<p><tt>[[[123434]]]</tt> <small>&middot; inserts the thumbnail of a geograph image - get the id number from the image url</small></p>



<p>Link: <tt>[url=http://www.example.com]Goto this site[/url]</tt></p>
<hr/>
<small>&middot; See the full reference for including tables, map extracts, external images and other features.</small>
</div>

{include file="_std_end.tpl"}
{/dynamic}
