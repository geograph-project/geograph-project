{assign var="page_title" value="API"}
{include file="_std_begin.tpl"}

	 <h2>Geograph API v0.6</h2> 
	 <div
	  style="float:right;padding:5px;background:#dddddd;position:relative; font-size:0.8em;"><b>Contents</b><br/>
		<ul style="margin-top:0;padding:0 0 0 1em;"> 
		  <li><a href="#api">API-key</a></li> 
		  <li><a href="#rss">RSS-feeds</a> 
			 <ul> 
				<li><a href="#rss_param">Parameters</a></li> 
				<li><a href="#rss_format">Formats</a></li> 
			 </ul></li> 
		  <li><a href="#csv">CSV Export</a> 
			 <ul> 
				<li><a href="#csv_param">Parameters</a></li> 
				<li><a href="#columns">Columns</a></li> 
				<li><a href="#extra">Returning Extra Columns</a></li> 
			 </ul></li> 
		  <li><a href="#building">Building a Search Query</a> 
			 <ul> 
				<li><a href="#places">Places to use an i number</a></li> 
			 </ul></li> 
		  <li><a href="#finally">and finally...</a></li> 
		</ul></div> 
	 <p>Geograph's <b>Application Programming Interface</b> (API) allows third
		party developers to create applications using data sourced from Geograph,
		in a friendly and polite way.</p> 
	 <p>Chances are you have come to this page because you are a developer
		looking to get access to some of the data, well you've come to the right place,
		below you will find brief details of what's available and how to get it...</p>
	 <p>Please note that the API is still in its early stages, these are the possiblities that 
	 	the developers needed, or felt would be useful, if you have any special requests then
	 	don't be shy, just <a href="/contact.php">let us know</a>.</p>
	 <h3 style="border:1px solid #cccccc;background-color:#dddddd; padding:10px;"><a name="api"></a>API-key</h3> 
	 <p>If you haven't got one you will need to obtain a unique API-key, which
		gives you access to the pages below, simply <a href="/contact.php">contact
		us</a>, with a brief outline of your project, please include the URL so we can
		take a look.</p> 
	 <p>Once you have a API-key simply replace [apikey] in the examples below to
		obtain your feed.</p> 
		
	 <p style="border:1px solid red;background-color: #FF3300; padding:10px;"><b>Each of these Feeds includes the photographer credit, which under the CC licence MUST be displayed along any use of the image.</b><br/><br/>Ideally also you should link back to the main photo page, either with the link supplied or with <a href="http://{$http_host}/photo/[id]">http://{$http_host}/photo/[id]</a>.<br/><br/> Thank you for your attention in this matter.</p>
		
	 <h3 style="border:1px solid #cccccc;background-color:#dddddd; padding:10px;"><a name="rss"></a>RSS-feeds <a title="RSS Feed of Recent Images" href="/faq.php" class="xml-rss">RSS</a></h3> 
	 <p>Really Simple Syndication allows you to obtain a details for a number of
		images, (usually) in XML format, this makes it really easy for you to reuse
		small section of information on your website or project. The feed lives at<br/><br/>
	 <a title="Geograph RSS feed"
		href="http://{$http_host}/syndicator.php?key=[apikey]">http://{$http_host}/syndicator.php?key=[apikey]</a><br/><br/>
	 and by default returns obtains an up-to-date listing of the 20 latest
		geograph submissions, you can however return different results as below</p> 
	 <h4><a name="rss_param"></a>Extra Parameters</h4> 
	 <p>You should supply <b>one</b> of the following parameters to specify the type of
		results you would like...</p> 
	 <table cellpadding="3" cellspacing="0" border="1"> 
		<tr> 
		  <th rowspan="2"><i>-none-</i></th> 
		  <td>20 latest geograph submissions</td> 
		</tr> 
		<tr> 
		  <td>
			 <a title="Geograph RSS feed"
			  href="http://{$http_host}/syndicator.php?key=[apikey]">http://{$http_host}/syndicator.php?key=[apikey]</a></td>
		</tr> 
		<tr> 
		  <th rowspan="2">i=[searchid]</th> 
		  <td>runs a predefined search, see <a href="#building">Building a Search
			 Query</a> for more information on how to obtain a valid i number. 
			 accepts additonal paramater:</span>
			 <table border="1" cellpadding="3" cellspacing="0"> 
				<tr> 
				  <th>page=[number]</th> 
				  <td>return a specific page of results</td> 
				</tr> 
		 </table></td> 
		</tr> 
		<tr> 
		  <td>
			 <a title="Geograph RSS feed"
			  href="http://{$http_host}/syndicator.php?key=[apikey]&i=12345">http://{$http_host}/syndicator.php?key=[apikey]&amp;i=12345</a></td>
		</tr> 
		<tr> 
		  <th rowspan="2">u=[user_id]</th> 
		  <td>limit results to particular user</td> 
		</tr> 
		<tr> 
		  <td>
			 <a title="Geograph RSS feed"
			  href="http://{$http_host}/syndicator.php?key=[apikey]&u=3">http://{$http_host}/syndicator.php?key=[apikey]&amp;u=3</a></td>
		</tr> 
		<tr> 
		  <th rowspan="2">q=[gridref or postcode]</th> 
		  <td>Returns 15 or all within 30km (which ever is less) of the specified location (see also <a href="#building">Building a query</a> for pitfals of the q paramater) - Will in fact create a i query on the fly, so you can use that to get page 2 etc of the results. Accepts additonal paramater:</span>
			 <table border="1" cellpadding="3" cellspacing="0"> 
				<tr> 
				  <th>u=[user_id]</th> 
				  <td>limit results to particular user</td> 
				</tr> 
		 </table></td> 
		</tr> 
		<tr> 
		  <td>
			 <a title="Geograph RSS feed"
			  href="http://{$http_host}/syndicator.php?key=[apikey]&q=TQ7054">http://{$http_host}/syndicator.php?key=[apikey]&amp;q=TQ7054</a></td>
		</tr> 
	 </table> 
	 <h4><a name="rss_format"></a>Formats</h4> 
	 <p>There is a number of standard formats the feed is available in</p> 
	 <table cellpadding="3" cellspacing="0" border="1"> 
		<tr> 
		  <th>format=RSS0.91</th> 
		  <td><a title="Geograph RSS 0.91 feed"
			 href="http://{$http_host}/syndicator.php?key=[apikey]&format=RSS0.91">RSS 0.91</a> </td> 
		</tr> 
		<tr> 
		  <th>format=RSS1.0</th> 
		  <td><a title="Geograph RSS 0.91 feed"
			 href="http://{$http_host}/syndicator.php?key=[apikey]&i=12345&format=RSS1.0">RSS 1.0</a> -<b><i>
			 Default</i></b></td> 
		</tr> 
		<tr> 
		  <th>format=RSS2.0</th> 
		  <td><a title="Geograph RSS 2.0 feed"
			 href="http://{$http_host}/syndicator.php?key=[apikey]&format=RSS2.0">RSS 2.0</a></td> 
		</tr> 
		<tr> 
		  <th>format=OPML</th> 
		  <td><a title="Geograph OPML feed"
			 href="http://{$http_host}/syndicator.php?key=[apikey]&i=12345&format=OPML">OPML</a></td> 
		</tr> 
		<tr> 
		  <th>format=HTML</th> 
		  <td><a title="Geograph HTML feed"
			 href="http://{$http_host}/syndicator.php?key=[apikey]&format=HTML">HTML</a> - ideal to be
			 output by a server side script or to be included in an IFRAME</td> 
		</tr> 
		<tr> 
		  <th rowspan="2">format=JS</th> 
		  <td><a title="Geograph JavaScript feed"
			 href="http://{$http_host}/syndicator.php?key=[apikey]&format=JS">JavaScript</a> - ideal to
			 output a simple table with a single &lt;SCRIPT&gt; tag</td> 
		</tr> 
		<tr> 
		  <td>&lt;script
			 src="http://{$http_host}/syndicator.php?key=[apikey]&key=[apikey]&amp;format=JS"
			 type="text/javascript"&gt;&lt;/script&gt;</td> 
		</tr> 
		<tr> 
		  <th>format=PHP</th> 
		  <td><a title="Geograph PHP feed"
			 href="http://{$http_host}/syndicator.php?key=[apikey]&format=PHP">PHP</a> - returns a valid php page, that builds a data-structure for use via include. (includes the thumbnail url)</td> 
		</tr> 
		<tr> 
		  <th>format=KML</th> 
		  <td><a title="Geograph Google Earth feed"
			 href="http://{$http_host}/syndicator.php?key=[apikey]&format=KML">KML</a> - suitable for use directly in Google Earth (XML based - includes the thumb url and lat/long!)<br/>accepts additonal paramaters:</span>
			 <table border="1" cellpadding="3" cellspacing="0"> 
				<tr> 
				  <th>simple=1</th> 
				  <td>If present includes styling to hide the picture label until pointed at</td> 
				</tr> 
		 </table></td> 
		</tr>
	 </table> 
	 <h3 style="border:1px solid #cccccc;background-color:#dddddd; padding:10px;"><a name="csv"></a>CSV Export</h3> 
	 <p>This is ideal for bulk downloads, or for keeping an offsite cache
		up-to-date, it lives at:<br/><br/>
	 <a title="Geograph RSS feed"
		href="http://{$http_host}/export.csv.php?key=[apikey]">http://{$http_host}/export.csv.php?key=[apikey]</a><br/><br/>
	 but you will probably want to supply a limiting parameter as follows</p>
	 <h4><a name="csv_param"></a>Parameters</h4> 
	 <table cellpadding="3" cellspacing="0" border="1"> 
		<tr> 
		  <th rowspan="2"><i>-none-</i></th> 
		  <td>returns the whole database, this should <i>only</i> be used for an
			 initial download, then use one of the following methods to just return a
			 subset</td> 
		</tr> 
		<tr> 
		  <td>
			 <a title="Geograph RSS feed"
			  href="http://{$http_host}/export.csv.php?key=[apikey]">http://{$http_host}/export.csv.php?key=[apikey]</a></td>
		</tr> 
		<tr> 
		  <th rowspan="2">since=[date] </th> 
		  <td>Returns all images submitted or <i>modified</i>* on or after this
			 date. Date is in YYYY-MM-DD format.<br/>This is the preferred method, where you
			 simply need to keep track of the day you last checked.</td> 
		</tr> 
		<tr> 
		  <td>
			 <a title="Geograph RSS feed"
			  href="http://{$http_host}/export.csv.php?key=[apikey]&since=2005-07-01">http://{$http_host}/export.csv.php?key=[apikey]&amp;since=2005-07-01</a></td>
		</tr> 
		<tr> 
		  <th rowspan="2">last=[number]+[interval]</th> 
		  <td>Returns all images submitted or <i>modified</i>* during the period
			 specified. 
			 <table border="1" cellpadding="3" cellspacing="0"> 
				<tr> 
				  <td rowspan="3">Valid formats for [interval]</td> 
				  <td>MINUTE </td> 
				  <td>WEEK</td> 
				</tr> 
				<tr> 
				  <td>HOUR</td> 
				  <td>MONTH</td> 
				</tr> 
				<tr> 
				  <td>DAY</td> 
				  <td>YEAR</td> 
				</tr> 
			 </table></td> 
		</tr> 
		<tr> 
		  <td>
			 <a title="Geograph RSS feed"
			  href="http://{$http_host}/export.csv.php?key=[apikey]&last=7+day">http://{$http_host}/export.csv.php?key=[apikey]&amp;last=7+DAY</a><br/><a
			 title="Geograph RSS feed"
			 href="http://{$http_host}/export.csv.php?key=[apikey]&last=6+hour">http://{$http_host}/export.csv.php?key=[apikey]&amp;last=6+HOUR</a></td>
		</tr> 
		<tr> 
		  <th rowspan="2">limit=[number] </th> 
		  <td>Returns the latest [number] images submitted or <i>modified</i>*
			 (also happens to be in descending date order)</td> 
		</tr> 
		<tr> 
		  <td>
			 <a title="Geograph RSS feed"
			  href="http://{$http_host}/export.csv.php?key=[apikey]&limit=30">http://{$http_host}/export.csv.php?key=[apikey]&amp;limit=30</a></td>
		</tr> 
		<tr> 
		  <th rowspan="2">ri=[1|2]</th> 
		  <td>Limit the results to a particular National Grid<br/>
		  <span style="color:#990000;">(can be combined
			 with the above parameters)</span></td> 
		</tr> 
		<tr> 
		  <td>
			 <a title="Geograph RSS feed"
			  href="http://{$http_host}/export.csv.php?key=[apikey]&ri=1">http://{$http_host}/export.csv.php?key=[apikey]&amp;ri=<b>1</b></a>
			 - <b>Great Britain</b><br/><a title="Geograph RSS feed"
			 href="http://{$http_host}/export.csv.php?key=[apikey]&ri=2">http://{$http_host}/export.csv.php?key=[apikey]&amp;ri=<b>2</b></a>
			 - <b>Ireland</b></td> 
		</tr> 
		<tr> 
		  <th rowspan="2">i=[searchid]</th> 
		  <td>runs a predefined search, see
			 <a href="#building">Building a Search Query</a> for more information on how to
			 obtain a valid i number.<br/>
			<span style="color:#990000;">NOTE: can't be combined with any of the above paramaters, but accepts additonal paramaters:</span>
			 <table border="1" cellpadding="3" cellspacing="0"> 
				<tr> 
				  <th>count=[number]</th> 
				  <td>overrides the pagesize specified in the query,<br/> or -1 for unlimited</td> 
				</tr> 
				<tr> 
				  <th>page=[number]</th> 
				  <td>return a specific page of results<br/> (paginated with the modified 'count')</td> 
				</tr> 
		 </table></td> 
		</tr> 
		<tr> 
		  <td>
			 <a title="Geograph RSS feed"
			  href="http://{$http_host}/export.csv.php?key=[apikey]&i=12345">http://{$http_host}/export.csv.php?key=[apikey]&amp;i=12345</a></td>
		</tr> 
	 </table> 
	 <p>* because these results also return images modified within the period
		its possible you will receive updates to images you already have. You can
		safely use the ID column to check for duplicates. the modified date is changed
		when anything about the image changes, for example of the title or comment, its
		possible you will get a row that nothing has apparentlly changed, but these
		should be very few.</p> 
	 <h4><a name="columns"></a>Columns</h4> 
	 <table border="1" cellpadding="3" cellspacing="0"> 
		<tr> 
		  <th>Id</th> 
		  <td><i>Unique</i> Numeric ID for the Picture, <br/>to be used to
			 construct the URL to link back to the image<br/><a title="Geograph RSS feed"
			 href="http://{$http_host}/photo/2345">http://{$http_host}/photo/2345</a></td> 
		  <td>2345</td> 
		</tr> 
		<tr> 
		  <th>Name</th> 
		  <td>Plain text title for the image</td> 
		  <td>Newbury High Street</td> 
		</tr> 
		<tr> 
		  <th>Grid Ref</th> 
		  <td>Four Figure Grid Reference for the image</td> 
		  <td>TQ6046 or B5467</td> 
		</tr> 
		<tr> 
		  <th>Submitter</th> 
		  <td>Full name of the submitter of the image, to be used for credit</td>
		  <td>Fred Bloggs</td> 
		</tr> 
		<tr> 
		  <th>Image Class</th> 
		  <td>Plain text image category</td> 
		  <td>Village Scene</td> 
		</tr> 
	 </table> 
	 <h4><a name="extra"></a>Returning Extra Columns</h4> 
	 <p style="color:#990000;">NOTE: You can only supply EITHER en OR ll, not both</p>
	 <table border="1" cellpadding="3" cellspacing="0"> 
		<tr> 
		  <th colspan="2" ALIGN="LEFT" rowspan="2">en=1 </th> 
		  <td colspan="2">add this parameter to add the Eastings and Northings
			 </td> 
		</tr> 
		<tr> 
		  <td colspan="2"><a title="Geograph RSS feed"
			 href="http://{$http_host}/export.csv.php?key=[apikey]&en=1">http://{$http_host}/export.csv.php?key=[apikey]&amp;en=1</a></td>
		</tr> 
		<tr> 
		  <th rowspan="2"> &nbsp;</th> 
		  <th>Easting</th> 
		  <td rowspan="2">Absolute position for the image in m from the Grid
			 False origin. Use the <i>Grid Ref</i> column to deduce with Grid the location
			 refers to.<b> These columns will be 0 when the image isn't positioned with more
			 than a 4 figure Grid Reference</b></td> 
		  <td>545667</td> 
		</tr> 
		<tr> 
		  <th>Northing</th> 
		  <td>234556</td> 
		</tr> 
		<tr> 
		  <th colspan="2" ALIGN="LEFT">ll=1 </th> 
		  <td colspan="2">add this parameter to return the WGS84 Latitude and
			 Longitude </td> 
		</tr> 
		<tr> 
		  <th rowspan="2"> &nbsp;</th> 
		  <th>Lat</th> 
		  <td rowspan="2">Position for the image in decimal degrees lat/long,
			 negative Longitude is West, specified to as high as accuracy as possible.</td> 
		  <td>53.5564</td> 
		</tr> 
		<tr> 
		  <th>Long</th> 
		  <td>-2.5466</td> 
		</tr> 
		<tr> 
		  <th colspan="2" ALIGN="LEFT">thumb=1 </th> 
		  <td colspan="2">add this parameter to return the full url to a 120x120
			 pixel thumbnail </td> 
		</tr> 
		<tr> 
		  <th>&nbsp;</th> 
		  <th>Thumb URL </th> 
		  <td colspan="2"> Example:<br/><a
			 href="http://{$http_host}/photos/01/76/017622_ed5d17d5_120x120.jpg">http://{$http_host}/photos/01/76/017622_ed5d17d5_120x120.jpg</a>
			 </td> 
		</tr> 
	 </table> 
	 <h3 style="border:1px solid #cccccc;background-color:#dddddd; padding:10px;"><a name="building"></a>Building a Search Query</h3> 
	 <p>There are there main methods for obtaining some valid <b>i</b> numbers
		for passing to the RSS or CSV feeds (or in fact directing the user to a search
		results page!).</p> 
	 <p>TIP: where ever you pass a i parameter you can also pass a <i>page</i>
		parameter to return another page of results. eg
		<a title="Geograph RSS feed"
		href="http://{$http_host}/syndicator.php?key=[apikey]&i=12345&page=2">http://{$http_host}/syndicator.php?key=[apikey]&amp;i=12345&amp;page=2</a></p>
	 <table border="1" cellpadding="3" cellspacing="0"> 
		<tr> 
		  <td>Predefined Searches</td> 
		  <td>Perhaps the simplest way is just to use one of our predefined
			 searches 
			 <table border="1" cellpadding="3" cellspacing="0"> 
				<tr> 
				  <td><a
					 href="/search.php?i=25654">25654</a></td> 
				  <td>15 Random images, by users joined in the last week </td> 
				</tr> 
				<tr> 
				  <td><a
					 href="/search.php?i=29439">29439</a></td> 
				  <td>50 Recently submitted images</td> 
				</tr> 
				<tr> 
				  <td><a
					 href="/search.php?i=25678">25678</a></td> 
				  <td>15 Random images from 15 categories</td> 
				</tr> 
				<tr> 
				  <td><a
					 href="/search.php?i=26586">26586</a></td> 
				  <td>15 Random first geographs for 15 squares</td> 
				</tr>
				<tr>
				  <td><a
					 href="/search.php?i=25666">25666</a></td>
				  <td>15 Random images specified to a 10 figure grid reference</td>
				</tr> 
			 </table>{dynamic}{if $user->registered}or even run one of your <a href="/search.php?more=1">recent searches</a>{/if}{/dynamic} </td> 
		</tr> 
		<tr> 
		  <td>Grid Reference or Postcode</td> 
		  <td>Simply pass to search.php and the response from the server will
			 include a brand new i number ready for use. <br/>For example
			 <a title="Geograph RSS feed"
			 href="http://{$http_host}/search.php?q=SH3467">http://{$http_host}/search.php?q=SH3467</a>
			 would return a <br/><TT style="border: 1px solid gray;">Location:
			 http://{$http_host}/search.php?i=12345</TT><br/>header, just parse out that i
			 number and pass it to the XML/CSV page.<br/>Also works with UK(inc NI)
			 Postcodes, e.g.<br/><a title="Geograph RSS feed"
			 href="http://{$http_host}/search.php?q=TN32+3DZ">http://{$http_host}/search.php?q=TN32+3DZ</a><br/>Technically
			 this can also accept place-names or free text search, however due to there
			 being no guarantee that it will only match one place-name (or none and run a
			 text search), if its not sure the it returns a page asking the user to confirm
			 their meaning. <br/><i>However its possible to pass a numeric place-name id,
			 please <a href="/contact.php">contact us</a> if you would be interested in a
			 copy of the dataset that we use.</i></td> 
		</tr> 
		<tr> 
		  <td>Advanced</td> 
		  <td>Its also possible to build more complicated searches using the
			 <a href="/search.php?form=advanced">advanced search</a> form. Either build a
			 search using the form and make note of the resultant i number, or have a look
			 at the source code to see the parameters possible. <br/><br/><i>Due to fact that
			 the exact parameters are subject to change and have some inter-relations, it's
			 probably easiest to <a href="/contact.php">contact us</a> with the details of
			 the type of searches you are trying to build. </i></td> 
		</tr> 
	 </table> 
	 <h4><a name="places"></a>Places to use an i number</h4> 
	 <p>Each one accepts the <i>Page</i> paramater, to get the next page of results.</p>
	 <table border="1" cellpadding="3" cellspacing="0"> 
		<tr> 
		  <td>Results Webpage</td> 
		  <td><a title="Geograph RSS feed"
			 href="http://{$http_host}/search.php?i=12345">http://{$http_host}/search.php?i=12345</a></td>
		</tr> 
		<tr> 
		  <td>Google Earth Webpage</td> 
		  <td><a title="Geograph KML/Google Earth feed"
			 href="http://{$http_host}/kml.php?i=12345">http://{$http_host}/kml.php?i=12345</a> </td>
		</tr> 
		<tr> 
		  <td>XML/HTML etc feed</td> 
		  <td><a title="Geograph RSS feed"
			 href="http://{$http_host}/syndicator.php?key=[apikey]&i=12345">http://{$http_host}/syndicator.php?key=[apikey]&amp;i=12345</a></td>
		</tr> 
		<tr> 
		  <td>CSV feed</td> 
		  <td><a title="Geograph RSS feed"
			 href="http://{$http_host}/export.csv.php?key=[apikey]&i=12345">http://{$http_host}/export.csv.php?key=[apikey]&amp;i=12345</a></td>
		</tr> 
		<tr> 
		  <td>Memory Map Feed</td> 
		  <td><a title="Geograph MemoryMap feed"
			 style="text-decoration: line-through">http://{$http_host}/memorymap.php?key=[apikey]&amp;i=12345</a> (Coming soon)</td>
		</tr> 
	 </table> 
	 <h3 style="border:1px solid #cccccc;background-color:#dddddd; padding:10px;"><a name="finally"></a>Finally</h3> 
	 <p>We wish you luck in you project and look forward to seeing the results! If you have any 
	 	problems using the API, then please do get in <a href="/contact.php">contact</a>.</p>
{include file="_std_end.tpl"}


