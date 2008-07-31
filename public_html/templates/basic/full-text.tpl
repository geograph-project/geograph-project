{assign var="page_title" value="Experimental Text Search"}
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

<h2>Full-Text Image Search <sup style="color:red">Experimental Beta</sup></h2>

<div class="interestBox">This is a preview of a new text search engine. With time the full-text keyword matching engine will be integrated into the main site search. This highly simplified frontend was simply created to test this backend-engine, and will not replace the more fully featured interface of the current <a href="/search.php">search</a>. The new engine makes some queries possible that weren't before, such as searching on photographer square, so you will find some features linking here simply as a preview. You can find other similar previews <a href="/finder/">here</a>. Thanks for your patience while we continue to improve our searching facilities.</div>

<form action="/stuff/search-service.php" method="get" target="searchwindow" onsubmit="focusBox()">
	<p>
		<label for="fq">Free Text Search</label>: <input type="text" name="q" id="fq" size="40"{dynamic}{if $q} value="{$q|escape:'html'}"{/if}{/dynamic}/>
		<input type="submit" value="Search"/>
	</p>
	<p>
		<small>&middot; There is no paging of results, to access more images simply add more keywords to refine your search (view <a href="#cheatsheet">Cheatsheet</a>)<br/>
		<small>&middot; Currently searches the title, description, category, photographer name and image taken (20071103, 200711 or just 2007) fields, as well the subject grid-reference (SH1234, SH13 or just SH)</small></small>
	</p>
</form>

<iframe {dynamic}{if $q} src="/stuff/search-service.php?q={$q|escape:'url'}"{else}src="about:blank"{/if}{/dynamic} width="700" height="700" name="searchwindow"></iframe>

<p><b>There is no paging of results</b>, to access more images simply add more keywords to refine your search</p>

<div class="interestBox" style="margin-top:60px;">
	<big><a name="cheatsheet"></a>Cheatsheet</big>:
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
	<small>Notice that that is a keyword based search, and not phrase based like you might be used to with the traditional Geograph search, and such operates like most popular search engines.</small>
</div>


{include file="_std_end.tpl"}
