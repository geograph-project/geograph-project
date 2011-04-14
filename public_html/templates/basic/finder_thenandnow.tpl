{assign var="page_title" value="Then and Now comparison"}
{include file="_std_begin.tpl"}
{literal}
<style type="text/css">
	ul.explore tt {
		border:1px solid gray;
		padding:5px;
	}
</style>

  <script type="text/javascript">

  function focusBox() {
  	el = document.getElementById('fq');
  	el.focus();
  }
  AttachEvent(window,'load',focusBox,false);

function runsearch(form) {
	var url = "/finder/search-service.php?inner&q="+encodeURIComponent(form.elements['q'].value);
	var y = encodeURIComponent(form.elements['y'].value);
	document.getElementById("window1").src = url+"&before="+y;
	document.getElementById("window2").src = url+"&after="+y;

	focusBox()
}

  </script>

{/literal}

<h2>Then and Now comparison</h2>



<form action="#" method="get" onsubmit="return false;">
	<p>
		<label for="fq">Free Text Search</label>: <input type="text" name="q" id="fq" size="40"{dynamic}{if $q} value="{$q|escape:'html'}"{/if}{/dynamic}/>
		<label for="y">Year</label>: <input type="text" name="y" id="y" size="4"{dynamic}{if $y} value="{$y|escape:'html'}"{/if}{/dynamic}/>
		<input type="button" value="Search" onclick="runsearch(this.form)"/>
	</p>
	<p>
		<small>&middot; To access more images simply add more keywords to refine your search, or add <tt>page2</tt> as the last keyword (view more on the <a href="#cheatsheet">Cheatsheet</a>).<br/>
		<small>&middot; Currently searches the title, description, category, photographer name fields, as well the subject grid-reference (SH1234, SH13 or just SH)<br/>
		&middot; Entering a 4-figure grid reference as the first keyword is a special case as will look in the vicinity of the specified location. </small></small>
	</p>
</form>

<iframe {dynamic}{if $q} src="/finder/search-service.php?inner&amp;q={$q|escape:'url'}&amp;before={$y}"{else}src="about:blank"{/if}{/dynamic} width="350" height="700" id="window1" style="width:48%;float:left"></iframe>
<iframe {dynamic}{if $q} src="/finder/search-service.php?inner&amp;q={$q|escape:'url'}&amp;after={$y}"{else}src="about:blank"{/if}{/dynamic} width="350" height="700" id="window2" style="width:48%;float:left"></iframe>


<div class="interestBox" style="margin-top:60px;clear:both">
	<big><a name="cheatsheet"></a>Cheatsheet</big>:

	<ul class="explore">
		<li>To access page 2 of the results add <tt>page2</tt> as the <i>last</i> keyword, however you can access more images by simply adding more keywords to refine your search, eg adding negative keywords</li>
	</ul>

	<ul class="explore">
		<li>prefix a keyword with - to <b>exclude</b> that word from the match; example: <tt>stone wall -sh</tt></li>
		<li>use OR to match <b>either/or</b> keywords; example: <tt>bridge river OR canal</tt></li>
		<li>use grid squares, hectads or myriads as keywords <tt>stone wall sh65</tt> or <tt>stone wall tq</tt></li>
		<li>a grid reference as first keyword will find images near that location; example: <tt>TQ7041 bridge</tt></li>
		<li>start query with a ~ so that <b>any word</b> is matched; example: <tt>~train railway track</tt></li>
	</ul>
</div>


{include file="_std_end.tpl"}
