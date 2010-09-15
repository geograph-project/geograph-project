{assign var="page_title" value="Full-Text Play Area"}
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
  
  </script>

{/literal}

<h2>Full-Text Image Search Play Area</h2>

<div class="interestBox">Use this page to play with the new querying possibilities of the <a href="/help/search_new">new search</a>. It is not intended to replace main search which (now) includes full-text keyword matching as available here; this interface is deliberatly very simple to focus on the query syntax. You can find other <a href="/finder/">similar searches here</a>. <i>Thank you for your patience while we continue to improve our searching facilities.</i></div>
<div style="font-size:0.9em">TIP: the all the queries available here can now be entered in the "For" box on the <a href="/search.php">simple search</a>, or the "keywords" box on the <a href="/search.php?form=text">advanced search</a>.</div>

<form action="/finder/search-service.php" method="get" target="searchwindow" onsubmit="focusBox()">
	<p>
		<label for="fq">Free Text Search</label>: <input type="text" name="q" id="fq" size="40"{dynamic}{if $q} value="{$q|escape:'html'}"{/if}{/dynamic}/>
		<input type="submit" value="Search"/>
	</p>
	<p>
		<small>&middot; To access more images simply add more keywords to refine your search, or add <tt>page2</tt> as the last keyword. (view more on the <a href="#cheatsheet">Cheatsheet</a>)<br/>
		<small>&middot; Currently searches the title, description, category, photographer name and image taken (20071103, 200711 or just 2007) fields, as well the subject grid-reference (SH1234, SH13 or just SH)<br/>
		&middot; Entering a 4-figure grid-reference as the first keyword is a special case as will look in the vicinity of the specified location. </small></small>
	</p>
</form>

<iframe {dynamic}{if $q} src="/finder/search-service.php?q={$q|escape:'url'}"{else}src="about:blank"{/if}{/dynamic} width="700" height="700" name="searchwindow" style="width:100%"></iframe>


<div class="interestBox" style="margin-top:60px;">
	<big><a name="cheatsheet"></a>Cheatsheet</big>:
	
	<ul class="explore">
		<li>To access page 2 of the results add <tt>page2</tt> as the <i>last</i> keyword, however you can access more images simply add more keywords to refine your search, eg adding negative keywords</li>
	</ul>

	<ul class="explore">
		<li>prefix a keyword with - to <b>exclude</b> that word from the match; example: <tt>stone wall -sh</tt></li>
		<li>can use OR to match <b>either/or</b> keywords; example: <tt>bridge river OR canal</tt></li>
		<li>use gridsquares, hectads or myriads as keywords <tt>stone wall sh65</tt> or <tt>stone wall tq</tt></li>
		<li>use dates when photo taken, can use specific day, as well as shortened less specific versions, eg: <tt>river bridge 2007</tt> or <tt>tower 200712</tt></li>
		<li>a grid-reference as first keyword will find images near that location; example: <tt>TQ7041 bridge</tt></li>
		<li>start query with a ~ so that <b>any word</b> is matched; example: <tt>~train railway track</tt></li>
	</ul>
</div>
<div style="margin-top:6px; margin-bottom:60px;">
	<img src="/templates/basic/img/icon_alert.gif" alt="Alert" width="18" height="16" align="left" style="margin-right:10px"/>
	<small>Notice that that is a keyword based search, and not phrase based like you might be used to with the traditional Geograph search (from before 2008), and as such this new full text search operates like most popular search engines.</small>
</div>


{include file="_std_end.tpl"}
