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
</style>{/literal}

 <h2>Geograph Search Tools</h2>



<div style="position:relative;height:600px;">
	<div class="tabHolder">
	{dynamic}
		{if $enable_forums}
			{assign var="tabs" value="8"}
		{else}
			{assign var="tabs" value="9"}
		{/if}
		<a class="tab{if $tab == 1}Selected{/if} nowrap" id="tab1" onclick="tabClick('tab','div',1,{$tabs})">Images</a>
		<a class="tab{if $tab == 2}Selected{/if} nowrap" id="tab2" onclick="tabClick('tab','div',2,{$tabs})">Images by Square</a>
		<a class="tab{if $tab == 3}Selected{/if} nowrap" id="tab3" onclick="tabClick('tab','div',3,{$tabs})">Combined</a>
		<a class="tab{if $tab == 4}Selected{/if} nowrap" id="tab4" onclick="tabClick('tab','div',4,{$tabs})">Content</a>
		<a class="tab{if $tab == 5}Selected{/if} nowrap" id="tab5" onclick="tabClick('tab','div',5,{$tabs})">Contributors</a>
		<a class="tab{if $tab == 6}Selected{/if} nowrap" id="tab6" onclick="tabClick('tab','div',6,{$tabs})">Locations</a>
		<a class="tab{if $tab == 7}Selected{/if} nowrap" id="tab7" onclick="tabClick('tab','div',7,{$tabs})">Shared Descriptions</a>
		{if $enable_forums}
			<a class="tab{if $tab == 8}Selected{/if} nowrap" id="tab8" onclick="tabClick('tab','div',8,{$tabs})">Discussions</a>
		{/if}
	{/dynamic}
	</div>

	<div style="position:relative;{if $tab != 1}display:none{/if}" class="interestBox" id="div1">
		<h3 class="title">Image Searches</h3>

		<h4 class="title">Geograph standard search</h4>
		<form method="get" action="/search.php">
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
		<form method="get" action="/full-text.php">
			<label for="fq">Keywords </label> <input type="text" name="q" id="fq" size="40"{dynamic}{if $q} value="{$q|escape:'html'}"{/if}{/dynamic}/>
			<input type="submit" value="Search"/>
			<p>&middot; Currently searches the title, description, category and photographer name fields as well as various forms of the subject grid reference</p>
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

	<div style="position:relative;{if $tab != 3}display:none{/if}" class="interestBox" id="div3">
		<h3 class="title">Combined searches</h3>

		<h4 class="title">Google search (~75% coverage July 2008)</h4>
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
		<form id="searchBoxForm_U0RWYBqs6@OUcAAT3rzik" action="http://builder.search.yahoo.com/a/bouncer" style="padding:0;">
			<input name="mobid" value="U0RWYBqs6@OUcAAT3rzik" type="hidden">
			<input name="ei" value="UTF-8" type="hidden">
			<input name="fr" value="ystg-c" type="hidden">
			<input type="text" id="searchTerm"
				onFocus="this.style.background='#fff';"
				onBlur="if(this.value=='')this.style.background='#fff url(http://us.i1.yimg.com/us.yimg.com/i/us/sch/gr/horiz_pwrlogo_red2.gif) 3px center  no-repeat'"
				name="p" style="width:260px; color:#666666; background:#fff url(http://us.i1.yimg.com/us.yimg.com/i/us/sch/gr/horiz_pwrlogo_red2.gif) 3px center no-repeat; position:relative;">
			<input type="submit" value="Yahoo Search">

			<input name="mobvs" id="site_U0RWYBqs6@OUcAAT3rzik" value="1" onclick='displayPopSearch("site","U0RWYBqs6@OUcAAT3rzik");'   type="hidden">
			<script type="text/javascript" src="http://builder.search.yahoo.com/j/popsearch?mobid=U0RWYBqs6%40OUcAAT3rzik&c=666666&default=web"></script>
		</form>

		<h4 class="title">Geograph</h4>
		<form action="/finder/multi.php" method="get" onsubmit="focusBox()">
				<div class="interestBox">
					<label for="fq">Combined search</label>: <input type="text" name="q" id="fq" size="40"{dynamic}{if $q} value="{$q|escape:'html'}"{/if}{/dynamic}/>
					<input type="submit" value="Search"/><sup style="color:red">Experimental beta</sup><br/>
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

<h3>Experimental searches...</h3>

<ul>
	<li><form action="/finder/multi.php" method="get" onsubmit="focusBox()">
		<div class="interestBox">
			<label for="fq">Combined <b>image</b> search</label>: <input type="text" name="q" id="fq" size="40"{dynamic}{if $q} value="{$q|escape:'html'}"{/if}{/dynamic}/>
			<input type="submit" value="Search"/><sup style="color:red">Experimental beta</sup><br/>
			(Enter a placename, grid reference, word search, or person's name)
		</div>
	</form><br/></li>

	<li><a href="/finder/human.php?create">Cooperative search</a></li>
</ul>


{include file="_std_end.tpl"}

