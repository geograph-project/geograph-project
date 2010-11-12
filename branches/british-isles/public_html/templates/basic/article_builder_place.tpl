{assign var="page_title" value="Create Article"}

{include file="_std_begin.tpl"}
{dynamic}


<h2>Create Article - "Place" Template</h2>

<p>Create an article somewhat like <a href="/article/St-Cuthberts-Church-Great-Salkeld" title="_blank">St Cuthbert's Church, Great Salkeld</a> in minutes! This creates an article based on a template, once created can tweak with the normal Article editor.</p>

<div style="position:relative;width:600px;float:left">


<form class="simpleform" action="{$script_name}" method="post" name="theForm">


<fieldset>


<div class="field">
	{if $errors.title}<div class="formerror"><p class="error">{$errors.title}</p>{/if}

	<label for="title"><b>Title:</b></label>
	<input type="text" name="title" value="{$title|escape:"html"}" style="font-size:1.1em" maxlength="64" size="47"/>

	<div class="fieldnotes">example: "St Cuthbert's Church"</div>

	{if $errors.title}</div>{/if}
</div>

<div class="field">
	{if $errors.locality}<div class="formerror"><p class="error">{$errors.locality}</p>{/if}

	<label for="locality"><b>Locality:</b></label>
	<input type="text" name="locality" value="{$locality|escape:"html"}" style="font-size:1.1em" maxlength="64" size="47"/>

	<div class="fieldnotes">example: "Great Salkeld"</div>

	{if $errors.locality}</div>{/if}
</div>

<div class="field">
	{if $errors.grid_reference}<div class="formerror"><p class="error">{$errors.grid_reference}</p>{/if}

	<label for="grid_reference"><b>Grid Reference: </b></label>
	<input type="text" name="grid_reference" value="{$grid_reference|escape:"html"}" maxlength="12" size="6"/>

	<div class="fieldnotes">Ideally 6 figure+, but 4 figure OK</div>

	{if $errors.grid_reference}</div>{/if}
</div>


	<div class="field">
		{if $errors.edit_prompt}<div class="formerror"><p class="error">{$errors.edit_prompt}</p>{/if}

		<label for="edit_prompt">Edit prompt:</label>
		<input type="text" name="edit_prompt" value="Help us improve, please edit!" maxlength="160" size="40"/>

		<div class="fieldnotes">If want to allow anyone to edit (open collaboration), a short message to prompt users to edit the article.</div>

		{if $errors.edit_prompt}</div>{/if}
	</div>


<div class="field">
	{if $errors.content}<div class="formerror"><p class="error">{$errors.content}</p>{/if}

	<label for="content">Introduction:</label>
	<textarea rows="7" cols="80" name="content" style="width:38em">{$content|escape:"html"}</textarea></p>

	{if $errors.content}</div>{/if}
</div>

<hr/>

<div class="field">
	{if $errors.title1}<div class="formerror"><p class="error">{$errors.title1}</p>{/if}

	<label for="title1"><b>Images Title 1</b>:</label>
	<input type="text" name="title1" id="title1" value="" maxlength="60" size="40"/>

	<div class="fieldnotes">Optional, example "Exterior Shots"</div>

	{if $errors.title1}</div>{/if}
</div>

<div class="field">
	{if $errors.thumbs1}<div class="formerror"><p class="error">{$errors.thumbs1}</p>{/if}

	<label for="thumbs1">Thumbnails 1:</label>
	<input type="radio" name="thumbs1" id="thumbs1" value="large" checked/>Large /
	<input type="radio" name="thumbs1" id="thumbs1" value="small"/>Small

	<div class="fieldnotes">large is great for being able to add captions</div>

	{if $errors.thumbs1}</div>{/if}
</div>


<div class="field">
	{if $errors.ids1}<div class="formerror"><p class="error">{$ids1.content}</p>{/if}

	<label for="ids1"><b>Image IDs 1</b>:</label> (Recommemnded maximum 10 images, if Large)
	<textarea rows="10" cols="80" name="ids1" style="width:38em">{$ids1|escape:"html"}</textarea></p>

	<div class="fieldnotes">Just a list of images in any format (anything other than a number is a sperator). So can copy from the Marked list, or even just list links to the photo pages. TIP: run a search on the right, and the drag the thumbnail into this box, (<b>don't</b> worry about adding spaces between links)</div>

	{if $errors.content}</div>{/if}
</div>


<hr/>

<div class="field">
	{if $errors.title2}<div class="formerror"><p class="error">{$errors.title2}</p>{/if}

	<label for="title2">Images Title 2:</label>
	<input type="text" name="title2" id="title2" value="" maxlength="60" size="40"/>

	<div class="fieldnotes">Optional, example "Interior"</div>

	{if $errors.title2}</div>{/if}
</div>

<div class="field">
	{if $errors.thumbs2}<div class="formerror"><p class="error">{$errors.thumbs2}</p>{/if}

	<label for="thumbs2">Thumbnails 2:</label>
	<input type="radio" name="thumbs2" id="thumbs2" value="large"/>Large /
	<input type="radio" name="thumbs2" id="thumbs2" value="small" checked/>Small

	{if $errors.thumbs2}</div>{/if}
</div>


<div class="field">
	{if $errors.ids2}<div class="formerror"><p class="error">{$ids2.content}</p>{/if}

	<label for="ids2">Image IDs 2:</label> (Recommemnded maximum 50 images)
	<textarea rows="10" cols="80" name="ids2" style="width:38em">{$ids2|escape:"html"}</textarea></p>

	{if $errors.content}</div>{/if}
</div>

<hr/>

<div class="field">
	{if $errors.related}<div class="formerror"><p class="error">{$ids1.related}</p>{/if}

	<label for="related">Related Links:</label> (One Per line)
	<textarea rows="10" cols="80" name="related" style="width:38em">{$related|escape:"html"}</textarea></p>

	<div class="fieldnotes">Related webpages for 'further reading' section, eg other collections, or even external links.</div>

	{if $errors.content}</div>{/if}
</div>

<hr/>

<div class="field">
	{if $errors.q}<div class="formerror"><p class="error">{$errors.q}</p>{/if}

	<label for="searchqq">Search Query:</label>
	<input type="text" name="q" id="searchqq" value="" maxlength="60" size="40"/>

	<div class="fieldnotes">Used to fine more images eg for a Article about "St Cuthbert's Church" could enter "church OR Cuthbert". (Adds a link to the article for images near the supplied grid reference)</div>

	{if $errors.q}</div>{/if}
</div>

</fieldset>

<div id="buttonbar">
	<input type="reset" name="reset" value="Reset" onclick="return confirm('Are you sure? Changes will be lost!');" style="color:green"/>
	<input type="submit" name="submit" value="Create Article" style="font-size:1.1em; color:green"/><br/> (The article will only be published and made public once you have had a chance to tweak it)</p>
</div>


</form>

</div>
<div class="interestBox" style="float:left;width:230px;margin-top:430px">
	<form action="/finder/search-service.php" method="get" target="searchwindow" name="searchform">
			<label for="fq">Free Text Search</label>: &nbsp; &nbsp; &nbsp; <input type="submit" value="Search"/>
			<input type="text" name="q" id="fq" size="30"{dynamic}{if $q} value="{$q|escape:'html'}"{/if}{/dynamic}/>
	</form>

	<iframe {dynamic}{if $q} src="/finder/search-service.php?q={$q|escape:'url'}"{else}src="about:blank"{/if}{/dynamic} width="230" height="700" name="searchwindow"></iframe>
</div>

<br style="clear:both"/>

<script type="text/javascript">{literal}

var edited = false;
var gridref4 = '';

function checkGridReference(that,showmessage) {
	GridRef = /\b([a-zA-Z]{1,2}) ?(\d{2,5})[ \.]?(\d{2,5})\b/;
	ok = true;
	if (that.value.length > 0) {
		myArray = GridRef.exec(that.value);
		if (myArray && myArray.length > 0) {
			numbers = myArray[2]+myArray[3];
			if (numbers.length == 0 || numbers.length % 2 != 0) {
				ok = false;
			} else {
				var len = numbers.length/2;

				gridref4 = myArray[1]+myArray[2].substr(0,2)+numbers.substr(len,2);
			}
		} else {
			ok = false;
		}
	}
	if (ok == false && showmessage) {
		alert("please enter a valid subject grid reference");
		that.focus();
	}
	return ok;
}


function updateQuery() {
	if (edited)
		return;

	var f = document.forms['theForm'];

	if (!checkGridReference(f.elements['grid_reference'],false)) {
		return;
	}


	var q = f.elements['title'].value;

	if (q.indexOf(' ') > 0) {
		q = q.replace(/^ +| +$/g,"").replace(/ +/g,' ');
		var bits = q.split(" ");
		q = "("+bits.join(' OR ')+")";
	}

	f.elements['q'].value = q;


	var fd = document.forms['searchform'];

	fd.elements['q'].value = gridref4 + ' ' + q;
}


function markUp() {
	var f = document.forms['theForm'];

	var ele = f.elements['title'];
	ele.onkeyup = updateQuery;
	ele.onmouseup = updateQuery;

	var ele = f.elements['grid_reference'];
	ele.onkeyup = updateQuery;
	ele.onmouseup = updateQuery;

	var fd = document.forms['searchform'];

	var ele = f.elements['q'];
	ele.onkeyup = function() {
		edited = true;
	};

}

 AttachEvent(window,'load',markUp,false);

{/literal}</script>


{/dynamic}
{include file="_std_end.tpl"}
