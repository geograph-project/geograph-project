{assign var="page_title" value="Finder"}
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

 <h2>Find Stuff on Geograph</h2>

<div style="position:relative;height:500px;">
	<div class="tabHolder">
	{dynamic}
		{if $enable_forums}
			{assign var="tabs" value="6"}
		{else}
			{assign var="tabs" value="5"}
		{/if}
		<a class="tab{if $tab == 1}Selected{/if} nowrap" id="tab1" onclick="tabClick('tab','div',1,{$tabs})">Combined</a>
		<a class="tab{if $tab == 2}Selected{/if} nowrap" id="tab2" onclick="tabClick('tab','div',2,{$tabs})">Images</a>
		<a class="tab{if $tab == 3}Selected{/if} nowrap" id="tab3" onclick="tabClick('tab','div',3,{$tabs})">Content</a>
		<a class="tab{if $tab == 4}Selected{/if} nowrap" id="tab4" onclick="tabClick('tab','div',4,{$tabs})">Contributors</a>
		<a class="tab{if $tab == 4}Selected{/if} nowrap" id="tab5" onclick="tabClick('tab','div',5,{$tabs})">Locations</a>
		{if $enable_forums}
			<a class="tab{if $tab == 5}Selected{/if} nowrap" id="tab6" onclick="tabClick('tab','div',6,{$tabs})">Discussions</a>
		{/if}
	{/dynamic}
	</div>

	<div style="position:relative;{if $tab != 1}display:none{/if}" class="interestBox" id="div1">
		<h3 class="title">Combined Searches</h3>
		
		<h4 class="title">Google Search <sup>[~75% coverage July '08]</sup></h4>
		<!-- Google CSE Search Box Begins -->
			<form id="searchbox_012385187440385394957:bpr_ubkvuy4" action="http://www.google.com/cse" STYLE="display:inline;">
				<input type="hidden" name="cx" value="012385187440385394957:bpr_ubkvuy4" />
				<input name="q" type="text" size="40" style="width:260px"/>
				<input type="submit" name="sa" value="Google Custom Search" />
				<input type="hidden" name="cof" value="FORID:0" />
			</form>
			<script type="text/javascript" src="http://www.google.com/coop/cse/brand?form=searchbox_012385187440385394957%3Abpr_ubkvuy4"></script>
		<!-- Google CSE Search Box Ends -->
	
		<h4 class="title">Yahoo Search</sup></h4>
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
			<p><i>coming soon...</i></p>
	</div>
	
	
	<div style="position:relative;{if $tab != 2}display:none{/if}" class="interestBox" id="div2">
		<h3 class="title">Image Searches</h3>
		
		<h4 class="title">Geograph Standard Search</h4>
		<form method="get" action="/search.php">
		<div style="position:relative;" class="interestBox">
			<div style="position:relative;">
				<label for="searchq" style="line-height:1.8em"><b>Search For</b>:</label> <small>(<a href="/help/search">help &amp; tips</a>)</small><br/>
				&nbsp;&nbsp;&nbsp;<input id="searchq" type="text" name="q" value="{$searchtext|escape:"html"|default:"(anything)"}" size="30" onfocus="if (this.value=='(anything)') this.value=''" onblur="if (this.value=='') this.value='(anything)'"/> (finds images containing this <b>exact phrase in title</b>)
			</div>
			<div style="position:relative;">
				<label for="searchlocation" style="line-height:1.8em">and/or a <b>Placename, Postcode, Grid Reference</b>:</label><br/>
				&nbsp;&nbsp;&nbsp;<i>near</i> <input id="searchlocation" type="text" name="location" value="{$searchlocation|escape:"html"|default:"(anywhere)"}" size="30" onfocus="if (this.value=='(anywhere)') this.value=''" onblur="if (this.value=='') this.value='(anywhere)'"/>
				<input id="searchgo" type="submit" name="go" value="Search..."/>
			</div>
		</div>
		</form>
		
		<h4 class="title">Geograph Text Search</h4>
		<form method="get" action="/full-text.php">
			<label for="fq">Keywords</label>: <input type="text" name="q" id="fq" size="40"{dynamic}{if $q} value="{$q|escape:'html'}"{/if}{/dynamic}/>
			<input type="submit" value="Search"/>
			<p>&middot; Currently searches the title, description, category and photographer name fields as well as various forms of the subject grid-reference</p>
		</form>
		
		<h4 class="title">Google Images</h4>
		<form method="get" action="http://images.google.co.uk/images">
		<div>
			<label for="gimq">Keywords: </label>
			<input type="text" name="q" value="{$searchq|escape:'html'}" id="gimq"/>
			<input type="hidden" name="as_q" value="site:geograph.org.uk"/>
			<input type="submit" name="btnG" value="Search"/></div>
		</form>
		
		<p>see also <a href="/search.php?form=advanced">advanced search</a>, and <a href="/search.php">more</a>
	</div>

	<div style="position:relative;{if $tab != 3}display:none{/if}" class="interestBox" id="div3">
		<h3 class="title">Content Search</h3>
		<form method="get" action="/content/">
			<label for="fq">Keywords</label>: <input type="text" name="q" id="cfq" size="40"{dynamic}{if $q} value="{$q|escape:'html'}"{/if}{/dynamic}/>
			<input type="submit" value="Search"/>
		</form>
		<p>&nbsp;</p>
	</div>
	
	<div style="position:relative;{if $tab != 4}display:none{/if}" class="interestBox" id="div4">
		<h3 class="title">Contributor Search</h3>
		
		<h4 class="title">Geograph</h4>
		<form method="get" action="/finder/contributors.php">
			<label for="fcq">Keywords</label>: <input type="text" name="q" id="fcq" size="40"{dynamic}{if $q} value="{$q|escape:'html'}"{/if}{/dynamic}/>
			<input type="submit" value="Search"/>
		</form>
		
		<h4 class="title">Google</h4>
		<form method="get" action="http://www.google.co.uk/search">
		<div>
			<label for="gcsq">Keywords: </label>
			<input type="text" name="q" value="{$searchq|escape:'html'}" id="gcsq"/>
			<input type="hidden" name="as_q" value="site:geograph.org.uk inurl:/profile/"/>
			<input type="submit" name="btnG" value="Search Geograph profiles"/></div>
		</form>
		
			<p><i>coming soon...</i>, see <a href="/statistics.php?by=user" target="_blank">Contributor List</a> in meantime</p>
	
		<p>&nbsp;</p>
	</div>
	
	<div style="position:relative;{if $tab != 5}display:none{/if}" class="interestBox" id="div5">
		<h3 class="title">Place Search</h3>
		
		
		<h4 class="title">Geograph</h4>
			<p><i>coming soon...</i>, see <a href="/explore/places/" target="_blank">Places</a> in meantime</p>
	
		<p>&nbsp;</p>
	</div>
	
	{if $enable_forums}
	<div style="position:relative;{if $tab != 6}display:none{/if}" class="interestBox" id="div6">
		<h3 class="title">Discussion Search</h3>
		<form method="get" action="/discuss/index.php">
			<input type="hidden" name="action" value="search"/>
			<input type="hidden" name="searchForum" value="0"/>
			<input type="hidden" name="days" value="60"/>
			<input type="hidden" name="searchWhere" value="0"/>
			<input type="hidden" name="searchHow" value="0"/>
			<div id="searchfield"><label for="searchterm">Keywords</label>
			<input id="searchterm" type="text" name="searchFor" value="{$searchFor}" size="30"/>
			<input id="searchbutton" type="submit" name="go" value="Find"/></div>
		</form>
		
		<h4 class="title">Grid Square Discussions</h3>
		<form method="get" action="/discuss/search.php">
		
			<div id="searchfield"><label for="searchterm">Search</label> 
			<input id="searchterm" type="text" name="q" value="" size="30"/>
			<input id="searchbutton" type="submit" name="go" value="Find"/> 
			<br/>
			<small>Enter a Placename, Postcode, Grid Reference</small></div>
			<br/>
			<label for="orderby">Order By</label>
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
{include file="_std_end.tpl"}

