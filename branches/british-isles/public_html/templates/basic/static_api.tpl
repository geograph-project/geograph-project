{assign var="page_title" value="API"}
{include file="_std_begin.tpl"}

	 <h2>Geograph API <sub>(last updated 1st May 2012)</sub></h2> 
	 <div
	  style="float:right;padding:5px;background:#dddddd;position:relative; font-size:0.8em;"><b>Contents</b><br/>
		<ul style="margin-top:0;margin-left:0;padding:0 0 0 1em;"> 
		  <li><a href="#dumps">Database Dumps</a></li> 
		  <li><a href="#torrents">Image bittorrent downloads</a></li> 
		  <li><a href="#rss">Images API</a> 
			 <ul> 
				<li><a href="#rss_param">Types</a>, <a href="#rss_options">Options</a>, <a href="#rss_format">Formats</a></li> 
			 </ul></li> 
		  <li><a href="#rest">Details API</a> 
			 <ul> 
				<li><a href="#rest_services">Services</a></li> 
				<li><a href="#rest_format">Formats</a></li> 
			 </ul></li> 
		  <li><a href="#csv">CSV Export</a> 
			 <ul> 
				<li><a href="#csv_param">Parameters</a>, <a href="#columns">Columns</a>, <br/><a href="#extra">Returning Extra Columns</a></li> 
			 </ul></li> 
		  <li><a href="#building">Building a Search Query</a> 
			 <ul> 
				<li><a href="#places">Places to use an i number</a></li> 
			 </ul></li> 
		  <li><a href="#others">Other Places to get information</a> 
		  <li><a href="#finally">and finally...</a></li> 
		</ul></div> 
	 <p>Geograph's <b>Application Programming Interface</b> (API) allows third
		party developers to create applications using data sourced from Geograph,
		in a friendly and polite way.</p> 
	 <p>Chances are you have come to this page because you are a developer
		looking to get access to some of the data. Well, you've come to the right place,
		below you will find brief details of what's available and how to get it...</p>
	 <p>Please note that the API is still in its early stages, these are the possibilities that 
		the developers needed, or felt would be useful, if you have any special requests then
		don't be shy, just <a href="/contact.php">let us know</a>.</p>

	<form action="http://groups-beta.google.com/group/geograph-api-users/boxsubscribe">
		<table border=0 style="background-color: #fff; border:1px solid green; padding: 5px;" cellspacing=2>
			<tr>
				<td rowspan="2">
					<img src="http://groups-beta.google.com/groups/img/3/groups_bar.gif" height="26" width="132" alt="Google Groups Beta"/>
				</td>
				<td style="padding-left: 5px">
					<b>Subscribe to <a href="http://groups-beta.google.com/group/geograph-api-users">Geograph-API-Users</a></b>
				</td>
			</tr>
			<tr>
				<td style="padding-left: 5px;"> Email: <input type=text name=email>
					<input type=submit name="sub" value="Subscribe">
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<i>Recommended; API updates will be posted here.<small><br/> Low traffic membership list is not disclosed.</small></i>
				</td>
			</tr>
			
		</table>
	</form>
<br/><br/>

	 <div class="interestBox" style="background-color:pink; color:black; border:2px solid red; padding:10px;">
	 <img src="/templates/basic/img/icon_alert.gif" alt="Modify" width="50" height="44" align="left" style="margin-right:10px"/>
	 <b>All exports include the photographer credit/name, which under the CC licence MUST be displayed alongside any use of the image. Also the fact the image is CC licenced needs to be mentioned.</b><br/><br/>Ideally also you could link back to the main photo page, either with the link supplied or with <a href="http://{$http_host}/photo/[id]" rel="nofollow">http://{$http_host}/photo/[id]</a>.<br/><br/> <i>Thank you for your attention in this matter.</i>
	 </div>

	 <h3 style="border:1px solid #cccccc;background-color:lightgreen; padding:10px; clear:both;margin-top:30px;"><a name="dumps"></a>Database Dumps - Bulk Data download</h3> 
	 <p>We have created a mysqldump snapshot of the database and made it available for download, see 
		{external title="Geograph Archive Database Dump" href="http://data.geograph.org.uk/dumps/" text="data.geograph.org.uk/dumps"} for details.</p>
	
	 <h3 style="border:1px solid #cccccc;background-color:lightgreen; padding:10px; clear:both;margin-top:30px;"><a name="torrents"></a>Torrents - Bulk Image download</h3> 
	 <p>The entire (or at least it will be) archive is available for download via bittorrent, see 
		{external title="Geograph Archive Torrents" href="http://torrents.geograph.org.uk/" text="torrents.geograph.org.uk"} for details.</p>

	 <h3 style="border:1px solid #cccccc;background-color:pink; padding:10px; clear:both;margin-top:30px;"><a name="api"></a>API-key</h3> 
	 <p>If you haven't got one you will need to obtain a unique API-key, which
		gives you access to the feeds below, simply <a href="/contact.php">contact
		us</a>, with a brief outline of your project, please include the URL so we can
		take a look.</p> 
	 <p>Once you have a API-key simply replace [apikey] in the examples below to
		obtain your feed.</p> 
		
	 <h3 style="border:1px solid #cccccc;background-color:#dddddd; padding:10px;"><a name="rss"></a>Images API</h3> 
	 <p>This API accesses the Images database, allowing a wide variety of output formats, and filtering options. 
		Include RSS style outputs (can be used directly as a RSS feed), but also supports various XML, KML, JSON and HTML outputs. The feed lives at<br/><br/>
	 <a title="Geograph RSS feed"
		href="http://{$api_host}/syndicator.php?key=[apikey]" rel="nofollow">http://{$api_host}/syndicator.php?key=[apikey]</a><br/><br/>
	 and by default returns obtains an up-to-date listing of the 15 latest
		Geograph submissions, you can however return different results as below</p> 

         <div class="interestBox" style="background-color:pink; color:black; border:2px solid red; padding:10px;">
		Note: These feeds are designed for ondemand access of specific content, eg finding nearby images for a single page. Not for bulk download of content. <b>Most searches created via this system can only access the first 1,000 images matching the query.</b>
		For larger extracts, the database dumps are still the best method. 
	 </div>

	 <h4><a name="rss_param"></a>Feed Type</h4> 
	 <p>You should supply <b>one</b> of the following parameters to specify the type of
		results you would like...</p> 
	 <table cellpadding="3" cellspacing="0" border="1"> 
		<tr> 
		  <th rowspan="2"><i>-none-</i></th> 
		  <td>15 latest Geograph submissions</td> 
		</tr> 
		<tr> 
		  <td>
			 <a href="http://{$api_host}/syndicator.php?key=[apikey]" rel="nofollow">http://{$api_host}/syndicator.php?key=[apikey]</a></td>
		</tr> 
		<tr> 
		  <th rowspan="2">i=[searchid]</th> 
		  <td>runs a predefined search, see <a href="#building">Building a Search
			 Query</a> for more information on how to obtain a valid i number. 
			 Accepts additional parameter:</span>
			 <table border="1" cellpadding="3" cellspacing="0"> 
				<tr> 
				  <th>page=[number]</th> 
				  <td>return a specific page of results</td> 
				</tr> 
		 </table></td> 
		</tr> 
		<tr> 
		  <td>
			 <a href="http://{$api_host}/syndicator.php?key=[apikey]&amp;i=12345" rel="nofollow">http://{$api_host}/syndicator.php?key=[apikey]&amp;i=12345</a></td>
		</tr> 
		<tr> 
		  <th rowspan="2">u=[user_id]</th> 
		  <td>Limit results to particular user</td> 
		</tr> 
		<tr> 
		  <td>
			 <a href="http://{$api_host}/syndicator.php?key=[apikey]&amp;u=3" rel="nofollow">http://{$api_host}/syndicator.php?key=[apikey]&amp;u=3</a></td>
		</tr> 
		<tr> 
		  <th rowspan="2">q=[query]</th> 
		  <td>Tries to deduce the type of search, either a text or a location search. Can use the format {literal}q={what}+near+{where}{/literal} to be sure (or use separate params as below) - Will in fact create an i query on the fly, so you can use that to get page 2 etc of the results. Accepts additional parameter:</span>
			 <table border="1" cellpadding="3" cellspacing="0"> 
				<tr> 
				  <th>u=[user_id]</th> 
				  <td>Limit results to particular user</td> 
				</tr> 
				<tr> 
				  <th>distance=[number]</th> 
				  <td>Limit results to within this distance (in km, default 10km, maximum 20km)</td> 
				</tr> 
				<tr> 
				  <th>perpage=[number]</th> 
				  <td>Number of results per page (15 default, 100 maximum)</td> 
				</tr> 
		 </table></td> 
		</tr> 
		<tr> 
		  <td>
			 <a href="http://{$api_host}/syndicator.php?key=[apikey]&amp;q=bridge+near+TQ7054" rel="nofollow">http://{$api_host}/syndicator.php?key=[apikey]&amp;q=bridge+near+TQ7054</a></td>
		</tr> 
		<tr> 
		  <th rowspan="2">location=[location]</th> 
		  <td>Returns 15 or all within 10km (which ever is less) of the specified location (grid reference, postcode or decimal lat/long) (see also <a href="#building">Building a query</a> for pitfalls of the q parameter) - will in fact create an i query on the fly, so you can use that to get page 2 etc of the results. Accepts additional parameter:</span>
			 <table border="1" cellpadding="3" cellspacing="0"> 
				<tr> 
				  <th>text=[text string]</th> 
				  <td>Returns only images matching this text search</td> 
				</tr> 
				<tr> 
				  <th>u=[user_id]</th> 
				  <td>Limit results to particular user</td> 
				</tr> 
				<tr> 
				  <th>distance=[number]</th> 
				  <td>Limit results to within this distance (in km, default 10km, maximum 20km)</td> 
				</tr> 
				<tr> 
				  <th>perpage=[number]</th> 
				  <td>Number of results per page (15 default, 100 maximum)</td> 
				</tr> 
		 </table></td> 
		</tr> 
		<tr> 
		  <td>
			 <a href="http://{$api_host}/syndicator.php?key=[apikey]&amp;location=TQ7054" rel="nofollow">http://{$api_host}/syndicator.php?key=[apikey]&amp;location=TQ7054</a></td>
		</tr> 
		<tr> 
		  <th rowspan="2">text=[text string]</th> 
		  <td>Returns 15 results matching the <a href="/help/search_new">word search</a> - will in fact create an i query on the fly, so you can use that to get page 2 etc of the results. Accepts additional parameter:</span>
			 <table border="1" cellpadding="3" cellspacing="0"> 
				<tr> 
				  <th>u=[user_id]</th> 
				  <td>Limit results to particular user</td> 
				</tr> 
				<tr> 
				  <th>perpage=[number]</th> 
				  <td>Number of results per page (15 default, 100 maximum)</td> 
				</tr> 
		 </table></td> 
		</tr> 
		<tr> 
		  <td>
			 <a href="http://{$api_host}/syndicator.php?key=[apikey]&amp;text=bridge" rel="nofollow">http://{$api_host}/syndicator.php?key=[apikey]&amp;text=bridge</a></td>
		</tr> 
		<tr> 
		  <th rowspan="2">callback=[function]</th> 
		  <td>wraps the output in a function call. <b>Only works with <tt>format=JSON</tt></b>, to allow use in client-side Javascript web-apps</td> 
		</tr> 
		<tr> 
		  <td>
			 <a href="http://{$api_host}/syndicator.php?key=[apikey]&amp;format=JSON&amp;callback=function" rel="nofollow">http://{$api_host}/syndicator.php?key=[apikey]&amp;format=JSON&amp;callback=function</a></td>
		</tr> 
	 </table> 
	 
	 <h4><a name="rss_options"></a>Options</h4> 
	 
	 <table cellpadding="3" cellspacing="0" border="1"> 
		<tr> 
		  <th rowspan="2">expand=1</th> 
		  <td>If present includes the thumbnail of the image in the description as html (not applicable to KML format). </td> 
		</tr> 
		<tr> 
		  <td>
			 <a title="Geograph RSS feed"
			  href="http://{$api_host}/syndicator.php?key=[apikey]&amp;expand=1" rel="nofollow">http://{$api_host}/syndicator.php?key=[apikey]&amp;expand=1</a></td>
		</tr> 
	 </table>  
	 <h4><a name="rss_format"></a>Formats</h4> 
	 <p>The feed is available in a number of standard formats:</p> 
	 <table cellpadding="3" cellspacing="0" border="1"> 
		<tr> 
		  <td>format=<b>RSS0.91</b></td> 
		  <td><a title="Geograph RSS 0.91 feed"
			 href="http://{$api_host}/syndicator.php?key=[apikey]&format=RSS0.91" rel="nofollow">RSS 0.91</a> </td> 
		</tr> 
		<tr> 
		  <td>format=<b>RSS1.0</b></td> 
		  <td><a title="Geograph RSS 0.91 feed"
			 href="http://{$api_host}/syndicator.php?key=[apikey]&i=12345&format=RSS1.0" rel="nofollow">RSS 1.0</a></td> 
		</tr> 
		<tr> 
		  <td>format=<b>RSS2.0</b></td> 
		  <td><a title="Geograph RSS 2.0 feed"
			 href="http://{$api_host}/syndicator.php?key=[apikey]&format=RSS2.0" rel="nofollow">RSS 2.0</a></td> 
		</tr> 
		<tr> 
		  <td>format=<b>GeoRSS</b></td> 
		  <td><a title="Geograph GeoRSS feed"
			 href="http://{$api_host}/syndicator.php?key=[apikey]&i=12345&format=GeoRSS" rel="nofollow">GeoRSS</a> -<b><i>
			 Default</i></b> - 
			 Extension of RSS 1.0 to include the lat/long, see {external href="http://www.georss.org/" text="georss.org"}</td> 
		</tr> 
		<tr> 
		  <td>format=<b>GeoPhotoRSS</b></td> 
		  <td><a title="Geograph GeoPhotoRSS feed"
			 href="http://{$api_host}/syndicator.php?key=[apikey]&i=12345&format=GeoPhotoRSS" rel="nofollow">GeoPhotoRSS</a> - 
			 Further custom extension of GeoRSS to also include the thumbnail url, see {external href="http://www.pheed.com/pheed/rss_anatomy.html" text="pheed.com"}</td> 
		</tr> 
		<tr> 
		  <td>format=<b>GPX</b></td> 
		  <td><a title="Geograph GPX feed"
			 href="http://{$api_host}/syndicator.php?key=[apikey]&i=12345&format=GPX" rel="nofollow">GPX 1.0</a> - 
			 the GPS Exchange Format, see {external href="http://www.topografix.com/gpx.asp" text="topografix.com"}</td> 
		</tr> 
		<tr> 
		  <td>format=<b>OPML</b></td> 
		  <td><a title="Geograph OPML feed"
			 href="http://{$api_host}/syndicator.php?key=[apikey]&i=12345&format=OPML" rel="nofollow">OPML</a></td> 
		</tr> 
		<tr> 
		  <td>format=<b>HTML</b></td> 
		  <td><a title="Geograph HTML feed"
			 href="http://{$api_host}/syndicator.php?key=[apikey]&format=HTML" rel="nofollow">HTML</a> - ideal to be
			 output by a server side script or to be included in an IFRAME</td> 
		</tr> 
		<tr> 
		  <td rowspan="2">format=<b>JS</b></td> 
		  <td><a title="Geograph JavaScript feed"
			 href="http://{$api_host}/syndicator.php?key=[apikey]&format=JS" rel="nofollow">JavaScript</a> - ideal to
			 output a simple table with a single &lt;SCRIPT&gt; tag</td> 
		</tr> 
		<tr> 
		  <td>&lt;script
			 src="http://{$api_host}/syndicator.php?key=[apikey]&amp;format=JS"
			 type="text/javascript"&gt;&lt;/script&gt;</td> 
		</tr> 
		<tr> 
		  <td>format=<b>PHP</b></td> 
		  <td><a title="Geograph PHP feed"
			 href="http://{$api_host}/syndicator.php?key=[apikey]&format=PHP" rel="nofollow">PHP</a> - returns a valid php page, that builds a data-structure for use via include (includes the thumbnail url).</td> 
		</tr> 
		<tr> 
		  <td>format=<b>KML</b></td> 
		  <td><a title="Geograph Google Earth feed"
			 href="http://{$api_host}/syndicator.php?key=[apikey]&format=KML" rel="nofollow">KML</a> - suitable for use directly in Google Earth (XML based - includes the thumb url and lat/long!)<br/>accepts additional parameters:</span>
			 <table border="1" cellpadding="3" cellspacing="0"> 
				<tr> 
				  <th>simple=1</th> 
				  <td>If present includes styling to hide the picture label unless hovering over the photo (recommended if using with Google Earth!)</td> 
				</tr> 
		 </table></td> 
		</tr>
		<tr> 
		  <td>format=<b>JSON</b> <sup style="color:red">NEW!</sup></td> 
		  <td><a title="Geograph JSON feed"
			 href="http://{$api_host}/syndicator.php?key=[apikey]&format=JSON" rel="nofollow">JSON</a> encoded output - particully suitable for integration into Javascript apps, but many other languages have json decoders. Also supports JSONP with <b>&callback=functionname</b> so can use in a webapp - JQuery etc make this easy!</td> 
		</tr> 
	 </table> 
	 <h3 style="border:1px solid #cccccc;background-color:#dddddd; padding:10px;"><a name="rest"></a>Details API</h3>
	 <p>Provides a very simple interface for obtaining details about a particular image or grid square. Will later be extended to include contributors, and tags, and possibly even hectads and myriads and some aggregate statistics. If looking for bulk downloads please consider one of the alternative means.</p>
	 
	 <h4><a name="rest_services"></a>Services</h4> 
	 <table cellpadding="3" cellspacing="0" border="1"> 
		<tr> 
		  <th colspan="2"><a title="Example REST Request"
			 href="http://{$api_host}/api/photo/1234/[apikey]" rel="nofollow">http://{$api_host}/api/photo/[photo-id]/[apikey]</a></th> 
		</tr> 
		<tr> 
		  <th>Photo Details</th> 
		  <td>Returns an XML infoset about the particular photograph, currently returns:
<pre style="font-size:0.7em;">
&lt;?xml version="1.0" encoding="UTF-8" ?&gt; 
&lt;geograph&gt;
  &lt;status state="ok" /&gt; 
  &lt;title&gt;Bascote&lt;/title&gt; 
  &lt;gridref&gt;SP4063&lt;/gridref&gt; 
  &lt;user profile="http://{$http_host}/profile/120"&gt;David Stowell&lt;/user&gt; 
  &lt;img src="http://{$http_host}/photos/00/34/003456_e10e23bc.jpg"
     width="640" height="480" /&gt; 
&lt;/geograph&gt;</pre>
  </td> 
		</tr> 
		<tr> 
		  <th colspan="2"><a title="Example REST Request"
			 href="http://{$api_host}/api/Gridref/SD1234/[apikey]" rel="nofollow">http://{$api_host}/api/<b>G</b>ridref/[4fig gridref]/[apikey]</a></th> 
		</tr> 
		<tr> 
		  <th>Grid Square Details</th> 
		  <td>Returns an XML infoset about the particular square, currently returns:
<pre style="font-size:0.7em;">
&lt;?xml version="1.0" encoding="UTF-8" ?&gt; 
&lt;geograph&gt;
  &lt;status state="ok" count="1" /&gt; 
  &lt;image url="http://{$http_host}/photo/64854"&gt;
    &lt;title&gt;Afon Cynfal&lt;/title&gt; 
    &lt;user profile="http://{$http_host}/profile/3"&gt;Barry Hunter&lt;/user&gt; 
    &lt;img src="http://{$http_host}/photos/06/48/064854_d68e7342_120x120.jpg" width="90" height="120" /&gt; 
    &lt;location grid="1" eastings="270500" northings="341100" /&gt; 
  &lt;/image&gt;
&lt;/geograph&gt;</pre>
  </td> 
		</tr> 
		<tr> 
		  <th colspan="2"><a title="Example REST Request"
			 href="http://{$api_host}/api/latlong/2km/50.64163,-1.94978/[apikey]" rel="nofollow">http://{$api_host}/api/latlong/[distance]km/[lat],[long]/[apikey]</a></th> 
		</tr> 
		<tr> 
		  <th>Search Nearby <b style="color:red">[DEPRECATED]</b></th> 
		  <td>
<div class="interestBox" style="background-color:pink; color:black; border:2px solid red; padding:10px;">
	In general, using the syndicator.php API is recommended instead of this option. It offers the same functionality, with a lot more options (eg specifying a sort order, filtering by keywords) <br><br>

	Example: <a href="http://{$api_host}/syndicator.php?format=JSON&q=50.64163,-1.94978&distance=2&key=[apikey]" rel="nofollow">http://{$api_host}/syndicator.php?format=JSON&q=[lat],[long]&distance=[distance]&key=[apikey]</a>
</div>



Returns an XML infoset of surrounding images, currently returns:
<pre style="font-size:0.7em;">
&lt;?xml version="1.0" encoding="UTF-8" ?&gt; 
&lt;geograph&gt;
  &lt;status state="ok" count="1" total="1034"/&gt; 
  &lt;image url="http://{$http_host}/photo/1575413"&gt; 
    &lt;title&gt;St Nicholas Hall&lt;/title&gt;
    &lt;user profile="http://{$http_host}/profile/17441"&gt;David Lally&lt;/user&gt; 
    &lt;img src="http://{$http_host}/geophotos/01/57/54/1575413_be18cb70_120x120.jpg" width="120" height="90" /&gt;
    &lt;location grid="1" lat="50.642106" long="-1.950011"/&gt; 
  &lt;/image&gt;
&lt;/geograph&gt;</pre>
Note:the distance should be 10km or below.
  </td> 
		</tr> 
		<tr> 
		  <th colspan="2"><a title="Example RDF Request"
			 href="http://{$http_host}/photo/1234.rdf" rel="nofollow">http://{$http_host}/photo/[photo-id].rdf</a></th> 
		</tr> 
		<tr> 
		  <th colspan="2"><a title="Example KML File"
			 href="http://{$http_host}/photo/1234.kml" rel="nofollow">http://{$http_host}/photo/[photo-id].kml</a></th> 
		</tr> 
	 </table>

	 <h4><a name="rest_format"></a>Formats</h4> 
	 <p>The feed is available in a number of standard formats:</p> 
	 <table cellpadding="3" cellspacing="0" border="1"> 
		<tr> 
		  <td>output=<b>xml</b></td> 
		  <td><a href="http://{$api_host}/api/photo/1234/[apikey]?output=xml" rel="nofollow">XML</a> -<i>Default</i> as above</td> 
		</tr> 
		<tr> 
		  <td>format=<b>json</b></td> 
		  <td><a href="http://{$api_host}/api/photo/1234/[apikey]?output=json" rel="nofollow">JSON</a> alternative cross-platform format<br/><br/>
		  <table border="1" cellpadding="3" cellspacing="0"> 
			<tr> 
			  <th>callback=myFunc</th> 
			  <td>wraps the data in a function call - JSONP - useful for cross-domain javascript calls<br/>
			    <div class="interestBox" style="background-color:pink; color:black; border:2px solid red; padding:10px;">
			  	NOTE: When using in a public web-based project don't use your API key in requests, just use a <a href="http://{$api_host}/api/photo/1234/{$http_host}?output=json" title="example!" rel="nofollow">your domain name</a> as the api key. 
			    </div>
			  </td> 
			</tr> 
		  </table></td> 
		</tr> 
	 </table>

	 
	 <h3 style="border:1px solid #cccccc;background-color:#dddddd; padding:10px;"><a name="csv"></a>CSV Export</h3> 
	 
	 
	 <div class="interestBox" style="background-color:pink; color:black; border:2px solid red; padding:10px;">
	 <img src="/templates/basic/img/icon_alert.gif" alt="Modify" width="50" height="44" align="left" style="margin-right:10px"/>
	 <b>Note, these CSV exports have become very inefficient, and so will only return 1000 records without prior arrangement.</b><br/><br/>
	 
	 Please consider {external text="Database Dumps" href="http://data.geograph.org.uk/dumps/"} instead.
	 </div>
	 
	 <p>More suited for bulk downloads, or for keeping an offsite cache
		up-to-date, it lives at:<br/><br/>
	 <a title="Geograph RSS feed"
		href="http://{$api_host}/export.csv.php?key=[apikey]" rel="nofollow">http://{$api_host}/export.csv.php?key=[apikey]</a><br/><br/>
	 but works best in combination with the parameters below.</p>
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
			  href="http://{$api_host}/export.csv.php?key=[apikey]" rel="nofollow">http://{$api_host}/export.csv.php?key=[apikey]</a></td>
		</tr> 
		<tr> 
		  <th rowspan="2">since=[date] </th> 
		  <td>Returns all images submitted or <i>modified</i>* on or after this
			 date. Date is in YYYY-MM-DD format.<br/>This is the preferred method, where you
			 simply need to keep track of the day you last checked.</td> 
		</tr> 
		<tr> 
		  <td>
			 <a 
			  href="http://{$api_host}/export.csv.php?key=[apikey]&since=2005-07-01" rel="nofollow">http://{$api_host}/export.csv.php?key=[apikey]&amp;since=2005-07-01</a></td>
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
			 <a 
			  href="http://{$api_host}/export.csv.php?key=[apikey]&last=7+day" rel="nofollow">http://{$api_host}/export.csv.php?key=[apikey]&amp;last=7+DAY</a><br/><a
			 
			 href="http://{$api_host}/export.csv.php?key=[apikey]&last=6+hour" rel="nofollow">http://{$api_host}/export.csv.php?key=[apikey]&amp;last=6+HOUR</a></td>
		</tr> 
		<tr> 
		  <th rowspan="2">limit=[number] </th> 
		  <td>Returns the latest [number] images submitted or <i>modified</i>*
			 (also happens to be in descending date order)</td> 
		</tr> 
		<tr> 
		  <td>
			 <a 
			  href="http://{$api_host}/export.csv.php?key=[apikey]&limit=30" rel="nofollow">http://{$api_host}/export.csv.php?key=[apikey]&amp;limit=30</a></td>
		</tr> 
		<tr> 
		  <th rowspan="2">ri=[1|2]</th> 
		  <td>Limit the results to a particular National Grid<br/>
		  <span style="color:#990000;">(can be combined
			 with the above parameters)</span></td> 
		</tr> 
		<tr> 
		  <td>
			 <a 
			  href="http://{$api_host}/export.csv.php?key=[apikey]&ri=1" rel="nofollow">http://{$api_host}/export.csv.php?key=[apikey]&amp;ri=<b>1</b></a>
			 - <b>Great Britain</b><br/><a 
			 href="http://{$api_host}/export.csv.php?key=[apikey]&ri=2" rel="nofollow">http://{$api_host}/export.csv.php?key=[apikey]&amp;ri=<b>2</b></a>
			 - <b>Ireland</b></td> 
		</tr> 
		<tr> 
		  <th rowspan="2">i=[searchid]</th> 
		  <td>runs a predefined search, see
			 <a href="#building">Building a Search Query</a> for more information on how to
			 obtain a valid i number.<br/>
			<span style="color:#990000;">NOTE: can't be combined with any of the above parameters, but accepts additional parameters:</span>
			 <table border="1" cellpadding="3" cellspacing="0"> 
				<tr> 
				  <th>count=[number]</th> 
				  <td>overrides the page size specified in the query,<br/> or -1 for biggest possible (usually 1000)</td> 
				</tr> 
				<tr> 
				  <th>page=[number]</th> 
				  <td>return a specific page of results<br/> (paginated with the modified 'count')</td> 
				</tr> 
		 </table></td> 
		</tr> 
		<tr> 
		  <td>
			 <a 
			  href="http://{$api_host}/export.csv.php?key=[apikey]&i=12345" rel="nofollow">http://{$api_host}/export.csv.php?key=[apikey]&amp;i=12345</a></td>
		</tr> 
	 </table> 
	 <p>* because these results also return images modified within the period
		it's possible you will receive updates to images you already have. You can
		safely use the ID column to check for duplicates. The modified date is changed
		when anything about the image changes, for example of the title or comment, it's
		possible you will get a row that nothing has apparently changed, but these
		should be very few.</p> 
	 <h4><a name="columns"></a>Columns</h4> 
	 <table border="1" cellpadding="3" cellspacing="0"> 
		<tr> 
		  <th>Id</th> 
		  <td><i>Unique</i> Numeric ID for the Picture, <br/>to be used to
			 construct the URL to link back to the image<br/><a 
			 href="http://{$http_host}/photo/2345" rel="nofollow">http://{$http_host}/photo/2345</a></td> 
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
		<tr style="background-color:#eeeeee"> 
		  <th colspan="2" ALIGN="LEFT">&amp;taken=1</th> 
		  <td><b>taken date</b> of the image</td> 
		  <td class="nowrap">2008-07-22</td>
		</tr> 
		
		<tr style="background-color:#eeeeee"> 
		  <th colspan="2" ALIGN="LEFT" rowspan="2">&amp;en=1</th> 
		  <td colspan="2">add this parameter to add the <b>Eastings, Northings and Precision</b>
			 </td> 
		</tr> 
		<tr> 
		  <td colspan="2"><a 
			 href="http://{$api_host}/export.csv.php?key=[apikey]&en=1" rel="nofollow">http://{$api_host}/export.csv.php?key=[apikey]&amp;en=1</a></td>
		</tr> 
		<tr style="font-size:0.8em"> 
		  <th rowspan="3">&nbsp;</th> 
		  <th>Easting</th> 
		  <td rowspan="3">Absolute position for the image in m from the Grid
			 False origin. Use the <i>Grid Ref</i> column to deduce with Grid the location
			 refers to.<b> These columns will be 0 when the image isn't positioned with more
			 than a 4 figure grid reference. <span style="color:red">include <tt>&coords=1</tt> to get eastings/northings even for 4 figure references</span></b></td> 
		  <td>545667</td> 
		</tr> 
		<tr style="font-size:0.8em"> 
		  <th>Northing</th> 
		  <td>234556</td> 
		</tr> 
		<tr style="font-size:0.8em"> 
		  <th>Figures</th> 
		  <td>10</td> 
		</tr> 
		
		<tr style="background-color:#eeeeee"> 
		  <th colspan="2" ALIGN="LEFT">&amp;ll=1</th> 
		  <td colspan="2">add this parameter to return the <b>WGS84 Latitude and
			 Longitude</b></td> 
		</tr> 
		<tr style="font-size:0.8em"> 
		  <th rowspan="2" &nbsp;</th> 
		  <th>Lat</th> 
		  <td rowspan="2">Position for the image in decimal degrees lat/long,
			 negative longitude is west, specified to as high as accuracy as possible.</td> 
		  <td>53.5564</td> 
		</tr> 
		<tr style="font-size:0.8em"> 
		  <th>Long</th> 
		  <td>-2.5466</td> 
		</tr> 
		
		<tr style="background-color:#eeeeee"> 
		  <th colspan="2" ALIGN="LEFT">&amp;thumb=1</th> 
		  <td colspan="2">add this parameter to return the full url to a 120x120
			 pixel thumbnail </td> 
		</tr> 
		<tr style="font-size:0.8em"> 
		  <th>&nbsp;</th> 
		  <th>Thumb URL</th> 
		  <td colspan="2"> Example:<br/><a
			 href="http://{$http_host}/photos/01/76/017622_ed5d17d5_120x120.jpg" rel="nofollow">http://{$http_host}/photos/01/76/017622_ed5d17d5_120x120.jpg</a>
			 </td> 
		</tr> 
		
		<tr style="background-color:#eeeeee"> 
		  <th colspan="2" ALIGN="LEFT">&amp;desc=1</th> 
		  <td colspan="2">include the <b>photo description</b></td> 
		</tr> 
		
		<tr style="background-color:#eeeeee"> 
		  <th colspan="2" ALIGN="LEFT">&amp;dir=1</th> 
		  <td><b> view direction</b> in degrees (-1 = undefined)</td> 
		  <td>145</td>
		</tr> 
		
		<tr style="background-color:#eeeeee"> 
		  <th colspan="2" ALIGN="LEFT" rowspan="2">&amp;ppos=1</th> 
		  <td colspan="2">add this parameter to add the Eastings, Northings and Precision of the Photographer Position
			 </td> 
		</tr> 
		<tr> 
		  <td colspan="2"><a 
			 href="http://{$api_host}/export.csv.php?key=[apikey]&en=1" rel="nofollow">http://{$api_host}/export.csv.php?key=[apikey]&amp;ppos=1</a></td>
		</tr> 
		<tr style="font-size:0.8em"> 
		  <th rowspan="3">&nbsp;</th> 
		  <th>Easting</th> 
		  <td rowspan="3">Absolute position for the photographer in m from the Grid
			 False origin. Use the <i>Grid Ref</i> column to deduce with Grid the location
			 refers to.<b> These columns will be 0 when the image doesn't include a Photographer Grid Reference</b></td> 
		  <td>545667</td> 
		</tr> 
		<tr style="font-size:0.8em"> 
		  <th>Northing</th> 
		  <td>234556</td> 
		</tr> 
		<tr style="font-size:0.8em"> 
		  <th>Figures</th> 
		  <td>10</td> 
		</tr> 
		<tr style="background-color:#eeeeee"> 
		  <th colspan="2" ALIGN="LEFT">&amp;submitted=1</th> 
		  <td colspan="2">Date/Time of image submission</td> 
		</tr> 
		<tr style="background-color:#eeeeee"> 
		  <th colspan="2" ALIGN="LEFT">&amp;hits=1</th> 
		  <td colspan="2">Approximate hit count for the full photo page</td> 
		</tr> 
		<tr style="background-color:#eeeeee"> 
		  <th colspan="2" ALIGN="LEFT">&amp;status=1</th> 
		  <td colspan="2">Moderation status (geograph/accepted)</td> 
		</tr> 
		<tr style="background-color:#eeeeee"> 
		  <th colspan="2" ALIGN="LEFT">&amp;level=1</th> 
		  <td colspan="2">1=First Geograph,2=Second Geograph Contributor Point, etc.</td> 
		</tr> 
		<tr style="background-color:#eeeeee"> 
		  <th colspan="2" ALIGN="LEFT">&amp;tags=1</th> 
		  <td colspan="2">Include the tags as a question mark separated list. Note: <b>can't</b> be used in conjunction with en/ppos options.</td> 
		</tr> 

	 </table> 
	 <h3 style="border:1px solid #cccccc;background-color:#dddddd; padding:10px;"><a name="building"></a>Building a Search Query</h3> 
	 <p>There are three main methods for obtaining some valid <b>i</b> numbers
		for passing to the RSS or CSV feeds (or in fact directing the user to a search
		results page!).</p> 
	 <p>TIP: where ever you pass a i parameter you can also pass a <i>page</i>
		parameter to return another page of results. eg
		<a 
		href="http://{$api_host}/syndicator.php?key=[apikey]&i=12345&page=2" rel="nofollow">http://{$api_host}/syndicator.php?key=[apikey]&amp;i=12345&amp;page=2</a></p>
	 <table border="1" cellpadding="3" cellspacing="0"> 
		<tr> 
		  <td>Predefined Searches</td> 
		  <td>Perhaps the simplest way is just to use one of our predefined
			 searches 
			 <table border="1" cellpadding="3" cellspacing="0"> 
				<tr> 
				  <td><a
					 href="/search.php?i=25654">25654</a></td> 
				  <td>15 random images, by users joined in the last week </td> 
				</tr> 
				<tr> 
				  <td><a
					 href="/search.php?i=29439">29439</a></td> 
				  <td>50 recently submitted images</td> 
				</tr> 
				<tr> 
				  <td><a
					 href="/search.php?i=25678">25678</a></td> 
				  <td>15 random images from 15 categories</td> 
				</tr> 
				<tr> 
				  <td><a
					 href="/search.php?i=26586">26586</a></td> 
				  <td>15 random First Geographs for 15 squares</td> 
				</tr>
				<tr>
				  <td><a
					 href="/search.php?i=25666">25666</a></td>
				  <td>15 random images specified to a 10 figure grid reference</td>
				</tr> 
			 </table>{dynamic}{if $user->registered}or even run one of your <a href="/search.php?more=1">recent searches</a>{/if}{/dynamic} </td> 
		</tr> 
		<tr> 
		  <td>Location String (Postcode, Grid Reference or Lat/Long)</td> 
		  <td>Simply pass to search.php and the response from the server will
			 include a brand new i number ready for use. <br/><br/>For example
			 <a 
			 href="http://{$http_host}/search.php?q=SH3467" rel="nofollow">http://{$http_host}/search.php?q=SH3467</a>
			 would return a <br/><TT style="border: 1px solid gray;">Location:
			 http://{$http_host}/search.php?i=12345</TT><br/>header, just parse out that i
			 number and pass it to the XML/CSV page.<br/><br/>
			 
			 UK(inc NI)
			 Postcodes, e.g.<br/><a 
			 href="http://{$http_host}/search.php?q=TN32+3DZ" rel="nofollow">http://{$http_host}/search.php?q=TN32+3DZ</a><br/><br/>
			 
			 Lat/Long (Decimal Degrees only), example <a 
			 href="http://{$http_host}/search.php?q=52.332,-2.2345">http://{$http_host}/search.php?q=52.332,-2.2345</a><br/><br/>
			 
			 Technically
			 this can also accept place-names or free text search, however due to there
			 being no guarantee that it will only match one place-name (or none and run a
			 text search), if it's not sure it returns a page asking the user to confirm
			 their meaning. <br/><br/><i>However it's possible to pass a numeric place-name id,
			 please <a href="/contact.php">contact us</a> if you would be interested in a
			 copy of the dataset that we use.</i></td> 
		</tr> 
		<tr> 
		  <td>Advanced</td> 
		  <td>It's also possible to build more complicated searches using the
			 <a href="/search.php?form=advanced">advanced search</a> form. Either build a
			 search using the form and make note of the resultant i number, or have a look
			 at the source code to see the parameters possible. <br/><br/><i>Due to fact that
			 the exact parameters are subject to change and have some inter-relations, it's
			 probably easiest to <a href="/contact.php">contact us</a> with the details of
			 the type of searches you are trying to build. </i></td> 
		</tr> 
	 </table> 
	 <h4><a name="places"></a>Places to use an i number</h4> 
	 <p>Each one accepts the <i>Page</i> parameter, to get the next page of results.</p>
	 <table border="1" cellpadding="3" cellspacing="0"> 
		<tr> 
		  <td>Results Webpage</td> 
		  <td><a
			 href="http://{$http_host}/search.php?i=12345" rel="nofollow">http://{$http_host}/search.php?i=12345</a></td>
		</tr> 
		<tr> 
		  <td>Google Earth Webpage</td> 
		  <td><a title="Geograph KML/Google Earth feed"
			 href="http://{$http_host}/kml.php?i=12345" rel="nofollow">http://{$http_host}/kml.php?i=12345</a> </td>
		</tr> 
		<tr> 
		  <td>Statistics Webpage</td> 
		  <td><a title="Statistical Breakdown"
			 href="http://{$http_host}/statistics/breakdown.php?i=12345" rel="nofollow">http://{$http_host}/statistics/breakdown.php?i=12345</a> </td>
		</tr> 
		<tr> 
		  <td>XML/HTML etc feed</td> 
		  <td><a title="Geograph RSS feed"
			 href="http://{$api_host}/syndicator.php?key=[apikey]&i=12345" rel="nofollow">http://{$api_host}/syndicator.php?key=[apikey]&amp;i=12345</a></td>
		</tr> 
		<tr> 
		  <td>CSV feed</td> 
		  <td><a 
			 href="http://{$api_host}/export.csv.php?key=[apikey]&i=12345" rel="nofollow">http://{$api_host}/export.csv.php?key=[apikey]&amp;i=12345</a></td>
		</tr> 
		<tr> 
		  <td>MemoryMap feed</td> 
		  <td><a title="Geograph MemoryMap feed"
			 style="text-decoration: line-through" rel="nofollow">http://{$http_host}/memorymap.php?key=[apikey]&amp;i=12345</a> (Coming soon)</td>
		</tr> 
		<tr> 
		  <td>GPX Export</td> 
		  <td><a title="Geograph GPX Downloads"
		  	href="http://{$api_host}/syndicator.php?key=[apikey]&amp;format=GPX&amp;i=12345" rel="nofollow">http://{$api_host}/syndicator.php?key=[apikey]&amp;format=GPX&amp;i=12345</a></td>
		</tr> 
	 </table> 
	 <h3 style="border:1px solid #cccccc;background-color:#dddddd; padding:10px;"><a name="others"></a>Other Ways to download information</h3> 
	 <ul>
		<li><a href="/memorymap.php">MemoryMap Exports</a></li>
		<li><a href="/gpx.php">GPX Exports</a></li>
		<li>100x100km CheckSheets (<a href="http://{$http_host}/mapsheet.php?t=tolJ5oOXXJ0oOJFoOXXJfoMXbJqoOXXJL5405o4VZMlXwZblw4MMuX" rel="nofollow">example</a>) <small>- nice easy parsable listing, could be used for creating coverage maps</small></li>
		<li>sitemap.xml - see {external href="http://www.sitemaps.org"}</li>
		<li><a href="http://www.geourl.org/" style="text-decoration: line-through">geourl.org</a> (Coming soon)</li>
		<li>We can also create coverage CSVs on demand, (listing squares currently with images)</li>
		<li>{external title="Geograph Archive Database Dump" href="http://data.geograph.org.uk/dumps/" text="data.geograph.org.uk/dumps"} &amp; {external title="Geograph Archive Torrents" href="http://torrents.geograph.org.uk/" text="torrents.geograph.org.uk"}<br/><br/></li>
		<li>Please DON'T use the <tt>/list/</tt> and/or <tt>/sitemap/</tt> namespace as they are to help Search Engines crawlers.</li>
	 </ul>
	 <h3 style="border:1px solid #cccccc;background-color:#dddddd; padding:10px;"><a name="finally"></a>Finally</h3> 
	 <p>We wish you luck in you project and look forward to seeing the results! If you have any 
		problems using the API, then please do get in <a href="/contact.php">contact</a>. Please consider joining the <a href="http://groups-beta.google.com/group/geograph-api-users">Geograph-API-Users</a> Google Discussion Group, which is primarily used for announcements.</p>


<br/><hr/>

<div id="disqus_thread"></div>
{literal}
<script type="text/javascript">
    var disqus_shortname = 'geograph';
    var disqus_identifier = 'static_api';
    var disqus_url = 'http://www.geograph.org.uk/help/api'; //USE canonical domain, so works on all domains
    (function() {
        var dsq = document.createElement('script'); dsq.type = 'text/javascript'; dsq.async = true;
        dsq.src = 'http://' + disqus_shortname + '.disqus.com/embed.js';
        (document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(dsq);
    })();
</script>
{/literal}

<noscript>Please enable JavaScript to view the <a href="http://disqus.com/?ref_noscript">comments powered by Disqus.</a></noscript>
<a href="http://disqus.com" class="dsq-brlink">blog comments powered by <span class="logo-disqus">Disqus</span></a>


{include file="_std_end.tpl"}


