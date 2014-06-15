{assign var="page_title" value="Finder"}
{assign var="meta_description" value="At Geograph we have lots of content, here we have put together a list of search facilities to find the right content for you."}
{include file="_std_begin.tpl"}
{literal}<style type="text/css">
.tab {
	cursor:pointer;
	cursor:hand;
}

.navButtons A {
	border: 1px solid lightgrey;
	padding: 2px;
}

h3.title {
	background-color:black;
	color:white;
	padding:10px;
	margin:0px;
}
h4.title {
	background-color:gray;
	color:white;
	padding:10px;
	margin:0px;
	margin-top:20px;
}

	#taglist li {
		padding:2px
		z-index:1000px;
	}
	#taglist li a {
		text-decoration:none
	}
	#taglist li a:hover {
		text-decoration:underline
	}

</style>{/literal}

 <h2>Geograph Search Tools</h2>



<div style="position:relative;height:800px;">
	<div class="tabHolder">
	{dynamic}
		<a class="tab{if $tab == 1}Selected{/if} nowrap" id="tab1" onclick="tabClick('tab','div',1,10)">Images</a>
		<a class="tab{if $tab == 2}Selected{/if} nowrap" id="tab2" onclick="tabClick('tab','div',2,10)">Images by Square</a>
		<a class="tab{if $tab == 3}Selected{/if} nowrap" id="tab9" onclick="tabClick('tab','div',9,10)">Tags</a>
		<a class="tab{if $tab == 3}Selected{/if} nowrap" id="tab3" onclick="tabClick('tab','div',3,10)">Combined</a>
		<a class="tab{if $tab == 4}Selected{/if} nowrap" id="tab4" onclick="tabClick('tab','div',4,10)">Content</a>
		<a class="tab{if $tab == 5}Selected{/if} nowrap" id="tab5" onclick="tabClick('tab','div',5,10)">Contributors</a>
		<a class="tab{if $tab == 6}Selected{/if} nowrap" id="tab6" onclick="tabClick('tab','div',6,10)">Locations</a>
		<a class="tab{if $tab == 7}Selected{/if} nowrap" id="tab7" onclick="tabClick('tab','div',7,10)">Shared Descriptions</a>
		{if $enable_forums}
			<a class="tab{if $tab == 8}Selected{/if} nowrap" id="tab8" onclick="tabClick('tab','div',8,10)">Discussions</a>
		{/if}
	{/dynamic}
	</div>

	<div style="position:relative;{if $tab != 1}display:none{/if}" class="interestBox" id="div1">
		<h3 class="title">Image Searches</h3>

		<h4 class="title">Geograph standard search</h4>
		<form method="get" action="/search.php">
		<input type="hidden" name="form" value="finder"/>
		<div style="position:relative;" class="interestBox">
			<div style="position:relative;">
				<label for="searchq" style="line-height:1.8em"><b>Search for</b>:</label> <small>(<a href="/article/Searching-on-Geograph">help &amp; tips</a>)</small><br/>
				&nbsp;&nbsp;&nbsp;<input id="searchq" type="text" name="q" value="{$searchtext|escape:"html"|default:"(anything)"}" size="30" onfocus="if (this.value=='(anything)') this.value=''" onblur="if (this.value=='') this.value='(anything)'"/> (can enter multiple keywords)
			</div>
			<div style="position:relative;">
				<label for="searchlocation" style="line-height:1.8em">and/or a <b>placename, postcode, grid reference</b>:</label><br/>
				&nbsp;&nbsp;&nbsp;<i>near</i> <input id="searchlocation" type="text" name="location" value="{$searchlocation|escape:"html"|default:"(anywhere)"}" size="30" onfocus="if (this.value=='(anywhere)') this.value=''" onblur="if (this.value=='') this.value='(anywhere)'"/>
				<input id="searchgo" type="submit" name="go" value="Search..."/>
			</div>
		</div>
		</form>

		<p>&middot; <a href="/search.php?form=text">More options...</a></p>

		<h4 class="title">Quick search</h4>
		<form method="get" action="/finder/of.php">
			<label for="fq">Keywords </label> <input type="text" name="q" id="fq" size="40"{dynamic}{if $q} value="{$q|escape:'html'}"{/if}{/dynamic}/>
			<input type="submit" value="Search"/>
			<p>&middot; Searches the title, description, tags/category, nearby placenames and photographer name fields as well as various forms of the subject grid reference and data taken.</p>
		</form>

		<h4 class="title">Browser</h4>
		<form method="get" action="/browser/redirect.php">
			<label for="fq">Keywords </label> <input type="text" name="q" id="fq" size="40"{dynamic}{if $q} value="{$q|escape:'html'}"{/if}{/dynamic}/>
			<input type="submit" value="Search"/>
			<p>&middot; The <a href="/browser/">Browser</a> incorperates a comprehensive search facility too</p>
		</form>

		<h4 class="title">Grouped Results</h4>
		<form method="get" action="/finder/groups.php">
			<label for="fq">Keywords </label> <input type="text" name="q" id="fq" size="40"{dynamic}{if $q} value="{$q|escape:'html'}"{/if}{/dynamic}/>
			<label for="fgroup">Group By <select name="group" id="fgroup">
	        <option label="" value=":1"></option>
<option label="Top Level Contexts" value="context_ids">Top Level Contexts</option>
<option label="Tags" value="tag_ids">Tags</option>
<option label="Shared Descriptions" value="snippet_ids">Shared Descriptions</option>
<option label="Buckets" value="bucket_ids">Buckets</option>
<option label="Automatic Clusters" value="group_ids">Automatic Clusters</option>
<option label="Extracted Terms" value="term_ids">Extracted Terms</option>
<option label="Image Category" value="imageclass">Image Category</option>
<option label="WikiMedia Categories" value="wiki_ids">WikiMedia Categories</option>
<option label="" value=":2"></option>
<option label="Myriad Square" value="myriad">Myriad Square</option>
<option label="Hectad Square" value="hectad">Hectad Square</option>
<option label="Grid Square" value="grid_reference">Grid Square</option>
<option label="" value=":3"></option>
<option label="Contributor" value="user_id">Contributor</option>
<option label="" value=":8"></option>
<option label="Country" value="country">Country</option>
<option label="County" value="county">County</option>
<option label="Placename" value="place">Placename</option>
<option label="" value=":4"></option>
<option label="Decade Taken" value="decade" selected>Decade Taken</option>
<option label="Year Taken" value="takenyear">Year Taken</option>
<option label="Month Taken" value="takenmonth">Month Taken</option>
<option label="Day Taken" value="takenday">Day Taken</option>
<option label="" value=":5"></option>
<option label="When Submitted" value="segment">When Submitted</option>
<option label="" value=":6"></option>
<option label="View Direction" value="direction">View Direction</option>
<option label="Subject Distance" value="distance">Subject Distance</option>
<option label="" value=":7"></option>
<option label="Image Format" value="format">Image Format</option>
<option label="Moderation Status" value="status">Moderation Status</option>

	     </select>

			<input type="submit" value="Search"/>
			
		</form>

		<h4 class="title">Google Images</h4>
		<form method="get" action="http://images.google.co.uk/images">
		<div>
			<label for="gimq">Keywords </label>
			<input type="text" name="q" value="{$searchq|escape:'html'}" id="gimq"/>
			<input type="hidden" name="as_q" value="site:geograph.org.uk"/>
			<input type="submit" name="btnG" value="Search"/></div>
		</form>

		<p>see also <a href="/search.php?form=advanced">Advanced search</a>, and <a href="/search.php">more</a>
	</div>

	<div style="position:relative;{if $tab != 2}display:none{/if}" class="interestBox" id="div2">
		<h3 class="title">Search by grid square</h3>
		<br/>
		<form method="get" action="/finder/sqim.php">
			<label for="fcq">Keywords </label> <input type="text" name="q" id="fcq" size="40"{dynamic}{if $q} value="{$q|escape:'html'}"{/if}{/dynamic}/>
			<input type="submit" value="Search"/>
		</form>


	<p>Search all images in a square at once. So can find squares that match keywords in separate images. For example a query of "bridge river" would match a square that has one image that matches "bridge" and a separate image that matches "river".</p>


		<p>&nbsp;</p>
	</div>

	<div style="position:relative;{if $tab != 9}display:none{/if}" class="interestBox" id="div9">
		<h3 class="title">Tag Search</h3>
		<br/>
	<form action="/tags/">
		Tag Search: <input type="text" name="tag" size="30" maxlength="60" onkeyup="if (this.value.length > 2) loadTagSuggestions(this,event);" autocomplete="off"/>
		<input type="submit" value="View"/><br/>

		<div style="position:relative;">
			<div style="position:absolute;top:0px;left:0px;background-color:lightgrey;margin-left:86px;padding-right:20px;display:none;z-index:10000" id="tagParent">
				<ol id="taglist">
				</ol>
			</div>
		</div>
	</form>

	<p>Search directly for tags using the form above</p>

<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js" type="text/javascript"></script>

{literal}
<script>

	function loadTagSuggestions(that,event) {

		var unicode=event.keyCode? event.keyCode : event.charCode;
		if (unicode == 13) {
			//useTags(that);
			return;
		}

		param = 'q='+encodeURIComponent(that.value);

		$.getJSON("/tags/tags.json.php?"+param+"&counts=1&callback=?",

		// on search completion, process the results
		function (data) {
			var div = $('#taglist').empty();
			$('#tagParent').show();

			if (data && data.length > 0) {

				for(var tag_id in data) {
					var text = data[tag_id].tag;
					if (data[tag_id].prefix && data[tag_id].prefix!='term' && data[tag_id].prefix!='category' && data[tag_id].prefix!='cluster' && data[tag_id].prefix!='wiki') {
						text = data[tag_id].prefix+':'+text;
					}
					text = text.replace(/<[^>]*>/ig, "");
					text = text.replace(/['"]+/ig, " ");

					div.append("<li value=\""+data[tag_id].images+"\"><a href=\"/tagged/"+text+"\">"+text+"</a></li>");
				}

			} else {
				div.append("<li value=\"0\"><a href=\"/tagged/"+that.value+"\">"+that.value+"</a></li>");
			}
		});
	}
</script>
{/literal}

                <h3 class="title">By Tag</h3>

		<form method="get" action="/finder/bytag.php">
			<label for="fcq">Keywords </label> <input type="text" name="q" id="fcq" size="40"{dynamic}{if $q} value="{$q|escape:'html'}"{/if}{/dynamic}/>
			<input type="submit" value="Search"/>
		</form>

		<p>View searches grouped by tags</p>



                <h3 class="title">Category Search</h3>

		<form method="get" action="/finder/categories.php">
			<label for="fcq">Keywords </label> <input type="text" name="q" id="fcq" size="40"{dynamic}{if $q} value="{$q|escape:'html'}"{/if}{/dynamic}/>
			<input type="submit" value="Search"/>
		</form>
		<p>use this form to search the old categories</p>

		<p>&nbsp;</p>
	</div>

	<div style="position:relative;{if $tab != 3}display:none{/if}" class="interestBox" id="div3">
		<h3 class="title">Combined searches</h3>

		<h4 class="title">Google search (~70% coverage Apr 2011)</h4>
		<!-- Google CSE Search Box Begins -->
			<form id="searchbox_012385187440385394957:bpr_ubkvuy4" action="http://www.google.com/cse" STYLE="display:inline;">
				<input type="hidden" name="cx" value="012385187440385394957:bpr_ubkvuy4" />
				<input name="q" type="text" size="40" style="width:260px"/>
				<input type="submit" name="sa" value="Google Custom Search" />
				<input type="hidden" name="cof" value="FORID:0" />
			</form>
			<script type="text/javascript" src="http://www.google.com/coop/cse/brand?form=searchbox_012385187440385394957%3Abpr_ubkvuy4"></script>
		<!-- Google CSE Search Box Ends -->

		<h4 class="title">Yahoo search</sup></h4>
		<form  action="http://search.yahoo.com/search" style="padding:0;" method="get">
			<input type="text" id="searchTerm"
				onFocus="this.style.background='#fff';"
				onBlur="if(this.value=='')this.style.background='#fff url(http://us.i1.yimg.com/us.yimg.com/i/us/sch/gr/horiz_pwrlogo_red2.gif) 3px center  no-repeat'"
				name="p" style="width:260px; color:#666666; background:#fff url(http://us.i1.yimg.com/us.yimg.com/i/us/sch/gr/horiz_pwrlogo_red2.gif) 3px center no-repeat; position:relative;">
			<input type="submit" value="Yahoo Search">

			<input name="vs" value="{$http_host}" type="hidden">
		</form>

		<h4 class="title">Geograph</h4>
		<form action="/finder/multi.php" method="get" onsubmit="focusBox()">
				<div class="interestBox">
					<label for="fq">Combined search</label>: <input type="text" name="q" id="fq" size="40"{dynamic}{if $q} value="{$q|escape:'html'}"{/if}{/dynamic}/>
					<input type="submit" value="Search"/><br/>
					(Enter a placename, grid reference, word search, or person's name)
				</div>
		</form>
	</div>

	<div style="position:relative;{if $tab != 4}display:none{/if}" class="interestBox" id="div4">
		<h3 class="title">Content search</h3>
		<br/>
		<form method="get" action="/content/">
			<label for="fq">Keywords</label> <input type="text" name="q" id="cfq" size="40"{dynamic}{if $q} value="{$q|escape:'html'}"{/if}{/dynamic}/> <input type="submit" value="Search"/> <br/>

			Scope:<select name="scope">
				<option value="">All</option>
				<option value="article" {if $scope == 'article'} selected{/if}>Articles</option>
				<option value="gallery" {if $scope == 'gallery'} selected{/if}>Galleries</option>
				{dynamic}
				  {if $enable_forums && $user->registered}
					  <option value="themed" {if $scope == 'themed'} selected{/if}>Themed Topics</option>
				  {/if}
				{/dynamic}
				<option value="help" {if $scope == 'help'} selected{/if}>Help Pages</option>
				<option value="document" {if $scope == 'document'} selected{/if}>Information Pages</option>
			</select>


		</form>
		<p><b>Content comprises:</b></p>
		 <ul>
		  <li><a href="/article/">Articles</a></li>
		  <li><a href="/gallery/">Galleries</a></li>
		  {if $enable_forums}
			  <li><a href="/discuss/?action=vtopic&amp;forum=6">Themed topics</a></li>
		  {/if}
		  <li><a href="/help/sitemap">Help documents</a></li>
 		</ul>

	<p>&middot; <a href="/article/Content-on-Geograph">Read more about contributing content</a></p>
	</div>

	<div style="position:relative;{if $tab != 5}display:none{/if}" class="interestBox" id="div5">
		<h3 class="title">Contributor search</h3>

		<h4 class="title">Geograph</h4>
		<form method="get" action="/finder/contributors.php">
			<label for="fcq">Keywords</label> <input type="text" name="q" id="fcq" size="40"{dynamic}{if $q} value="{$q|escape:'html'}"{/if}{/dynamic}/>
			<input type="submit" value="Search"/>
		</form>

		<h4 class="title">Google</h4>
		<form method="get" action="http://www.google.co.uk/search">
		<div>
			<label for="gcsq">Keywords </label>
			<input type="text" name="q" value="{$searchq|escape:'html'}" id="gcsq"/>
			<input type="hidden" name="as_q" value="site:geograph.org.uk inurl:/profile/"/>
			<input type="submit" name="btnG" value="Search Geograph profiles"/></div>
		</form>

		<p>&nbsp;</p>
	</div>

	<div style="position:relative;{if $tab != 6}display:none{/if}" class="interestBox" id="div6">
		<h3 class="title">Place search</h3>
		<br/>
		<form method="get" action="/finder/places.php">
			<label for="fcq">Keywords</label> <input type="text" name="q" id="fcq" size="40"{dynamic}{if $q} value="{$q|escape:'html'}"{/if}{/dynamic}/>
			<input type="submit" value="Search"/>
		</form>

		<p>&nbsp;</p>
	</div>

	<div style="position:relative;{if $tab != 7}display:none{/if}" class="interestBox" id="div7">
		<h3 class="title">Shared Description <a href="/article/Shared-Descriptions" class="about" title="Read more about Shared Descriptions">About</a> search</h3>
		<br/>
		<form method="get" action="/snippets.php">


			<label for="fq">Keywords</label>
			<input type="text" name="q" id="fq" size="20"{dynamic}{if $q} value="{$q|escape:'html'}"{/if}{/dynamic}/>

			<input type="submit" value="Find"/><br/>
			<label for="gr">Grid reference</label>
			<input type="text" name="gr" id="gr" value="{$gr|escape:'html'}" size="12" maxlength="12"/><br/>

			<label for="gr">Radius</label>:
			{if $centisquare}
			<small><input type="radio" name="radius" value="0.1"{if $radius == 0.1} checked{/if}/> Centisquare /
			{/if}
			<input type="radio" name="radius" value="1" {if $radius == 1 || !$radius} checked{/if}/> Gridsquare  /
			<input type="radio" name="radius" value="2" {if $radius == 2} checked{/if}/> including surrounding gridsquares /
			<input type="radio" name="radius" value="10"{if $radius == 10} checked{/if}/> within 10km </small><br/>

			{dynamic}
				{if $user->registered}<br/>
					<small><input type="checkbox" name="onlymine" {if $onlymine} checked{/if}/> Only show my descriptions</small><br/>
				{/if}
			{/dynamic}

		</form>
	</div>

	{if $enable_forums}
	<div style="position:relative;{if $tab != 8}display:none{/if}" class="interestBox" id="div8">
		<h3 class="title">Discussion search - NEW</h3>
		<br/>
		<form method="get" action="/finder/discussions.php">
			<div><label for="searchnewterm">Keywords</label>
			<input id="searchnewterm" type="text" name="q" value="{$q}" size="30"/>
			<input id="searchbutton" type="submit" name="go" value="Find"/></div>
		</form>
		<!--<p>&nbsp;</p>
		<h3 class="title">Discussion Search - old</h3>
		<form method="get" action="/discuss/index.php">
			<input type="hidden" name="action" value="search"/>
			<input type="hidden" name="searchForum" value="0"/>
			<input type="hidden" name="days" value="60"/>
			<input type="hidden" name="searchWhere" value="0"/>
			<input type="hidden" name="searchHow" value="0"/>
			<div id="searchfield"><label for="searchterm">Keywords</label>
			<input id="searchterm" type="text" name="searchFor" value="{$searchFor}" size="30"/>
			<input id="searchbutton" type="submit" name="go" value="Find"/></div>
		</form>-->

		<h4 class="title">Grid Square Discussions</h3>
		<form method="get" action="/discuss/search.php">

			<div id="searchfield"><label for="searchterm">Search</label>
			<input id="searchterm" type="text" name="q" value="" size="30"/>
			<input id="searchbutton" type="submit" name="go" value="Find"/>
			<br/>
			<small>Enter a placename, postcode, grid reference</small></div>
			<br/>
			<label for="orderby">Order by</label>
			<select name="orderby" id="orderby">
				<option label="Distance" value="dist_sqd">Distance</option>
				<option label="Topic Started" value="topic_time desc">Topic Started</option>
				<option label="Latest Post" value="post_time desc">Latest Post</option>

				<option label="Grid Reference" value="grid_reference">Grid Reference</option>
				<option label="West-&gt;East" value="x">West-&gt;East</option>
				<option label="South-&gt;North" value="y">South-&gt;North</option>
			</select>

		</form>

		<p>&nbsp;</p>
	</div>
	{/if}
</div>

<h3 class="title">Experimental searches...</h3>

<ul>
	<li><form action="/finder/multi2.php" method="get" onsubmit="focusBox()">
		<div class="interestBox">
			<label for="fq">Combined <b>image</b> search</label>: <input type="text" name="q" id="fq" size="40"{dynamic}{if $q} value="{$q|escape:'html'}"{/if}{/dynamic}/>
			<input type="submit" value="Search"/><br/>
			(Enter a placename, grid reference, word search, or person's name)
		</div>
	</form><br/></li>

	<li><a href="/finder/human.php?create">Cooperative search</a></li>
</ul>


{include file="_std_end.tpl"}

