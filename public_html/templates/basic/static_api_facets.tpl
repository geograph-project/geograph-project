{assign var="page_title" value="Facet API"}
{include file="_std_begin.tpl"}
{literal}<style>
.important {
	background-color:yellow;
}
#maincontent {
	max-width:940px;
}
</style>{/literal}

<h2>Geograph Faceted Search API (api-facetql.php)</h2>

<p>The API allows for flexible searching and faceting of Geograph data. Aside from the description (and shared-descriptions) this API can pretty much get all data we hold for images, 
although some may be obscure formats, tailored for indexing and searching. (there is an older api-facet.php, but its recomemned to use api-facetql.php)</p>

<p>This API provides a JSON-based interface to Geograph's underlying search technology, which is powered by ManticoreSearch, originally SphinxSearch. It essentially acts as a wrapper 
around SphinxQL queries.</p>

<p>To effectively use this API, it is highly recommended to have a basic understanding of Sphinx and its query language (SphinxQL). You can find more information in the <a 
href="http://sphinxsearch.com/docs/current.html">official Sphinx documentation</a>. This API still only supports the native sphinx full-text query, not the geograph customiziations. 
Manticore also has a nice reference on the <a href="https://manual.manticoresearch.com/Searching/Full_text_matching/Operators#Full-text-operators">Full Text Operators</a></p>

<p>Endpoint: <tt>https://api.geograph.org.uk/api-facetql.php</tt></p>

<h3>URL Parameters</h3>

<p>The API accepts a variety of URL parameters to control the search query and the format of the results. Here are the main parameters:</p>

<table cellspacing=0 cellpadding=4 border=1 bordercolor=#eee>
  <thead>
    <tr>
      <th>Parameter</th>
      <th>Description</th>
      <th>Example Value</th>
    </tr>
  </thead>
  <tbody>
    <tr class="important">
      <td><code>apikey</code></td>
      <td>Please always add you API key - for tracking and resource management.</td>
      <td><code>yourkey</code></td>
    </tr>
    <tr>
      <td><code>callback</code></td>
      <td>The name of a JavaScript function to wrap the JSON response in (for JSONP requests).</td>
      <td><code>myCallbackFunction</code></td>
    </tr>
    <tr>
      <td><code>order</code></td>
      <td>Specifies the order of the results, can specify multiple <b>attributes</b> (any except MVAs)</td>
      <td><code>taken_date DESC, id ASC</code></td>
    </tr>
    <tr>
      <td><code>group</code></td>
      <td>The <b>attribute</b> name(s) to group the results by (e.g., for faceting, or aggregation), this is perhaps the true power of this API.</td>
      <td><code>user_id</code></td>
    </tr>
    <tr>
      <td><code>n</code></td>
      <td>Sphinx/Manticore have ability to return multiple rows per group, this specifies how many rows per group</td>
      <td><code>1</code></td>
    </tr>
    <tr>
      <td><code>within</code></td>
      <td>Specifies the order of results within each group when using the <code>group</code> parameter.</td>
      <td><code>COUNT(*) DESC</code></td>
    </tr>
    <tr>
      <td><code>limit</code></td>
      <td>The maximum number of results to return. (default=20!)</td>
      <td><code>50</code></td>
    </tr>
    <tr>
      <td><code>offset</code></td>
      <td>The starting offset for the results. Used for pagination. <b>Note that <code>limit + offset</code> should generally be less than 1000</b> (Sphinx's default <code>max_matches</code>).</td>
      <td><code>100</code></td>
    </tr>
    <tr class="important">
      <td><code>select</code></td>
      <td>A comma-separated list of <b>attributes</b> to include in the results. Use <code>*</code> to retrieve all available <b>attributes</b> - but not recommended.</td>
      <td><code>id,title,user_id,grid_reference</code></td>
    </tr>

    <tr>
      <td colspan="3"><br><strong>Sphinx Index Selection Parameters:</strong> These parameters select different Sphinx indexes. Only one should be used at a time. If none are specified, uses the normal britain+ireland image index.</td>
    </tr>
    <tr>
      <td><code>gg</code></td>
      <td>Uses the <code>germany</code> index. (Geograph Germany images!)</td>
      <td><code>1</code></td>
    </tr>
    <tr>
      <td><code>is</code></td>
      <td>Uses the <code>islands</code> index. (Geograph Channel Islands!)</td>
      <td><code>1</code></td>
    </tr>
    <tr>
      <td><code>recent</code></td>
      <td>Modifies the default index to include recent items (approximately last 25% of images).</td>
      <td><code>1</code></td>
    </tr>
    <tr>
      <td colspan="3"><br><strong>Filtering Parameters:</strong></td>
    </tr>
    <tr class="important">
      <td><code>match</code></td>
      <td>The full-text search query, matches against all <b>fields</b>. Uses Sphinx's SPH_MATCH_EXTENDED2 syntax.</td>
      <td><code>bridge over river</code></td>
    </tr>
    <tr>
      <td><code>where</code></td>
      <td>A general WHERE clause for filtering results based on <b>attribute</b> values. Multiple conditions can be supplied.</td>
      <td><code>user_id = 123 AND taken_year > 2020</code></td>
    </tr>
    <tr>
      <td><code>filter[attribute_name][]</code></td>
      <td>Filters results where <code>attribute_name</code> is IN a list of values.</td>
      <td><code>filter[user_id][]=123&filter[user_id][]=456</code></td>
    </tr>
    <tr>
      <td><code>filterrange[attribute_name]</code></td>
      <td>Filters results where <code>attribute_name</code> is BETWEEN two values (inclusive).</td>
      <td><code>filterrange[taken_year]=2018,2020</code></td>
    </tr>
    <tr>
      <td><code>exclude[attribute_name][]</code></td>
      <td>Excludes results where <code>attribute_name</code> is IN a list of values.</td>
      <td><code>exclude[tag_ids][]=10&exclude[tag_ids][]=11</code></td>
    </tr>
    <tr>
      <td><code>excluderange[attribute_name]</code></td>
      <td>Excludes results where <code>attribute_name</code> is BETWEEN two values (inclusive).</td>
      <td><code>excluderange[id]=1000,2000</code></td>
    </tr>
     <tr>
      <td colspan="3"><br><strong>Geo-spatial Parameters:</strong> (note always use decimal degrees, even though the output has radians)</td>
    </tr>
    <tr>
      <td><code>geo_prefix</code></td>
      <td>Specifies the prefix for latitude and longitude attributes (e.g., <code>wgs84_</code>, <code>v_</code>). Defaults to <code>wgs84_</code> or <code>v_</code> for the viewpoint index. Applies to geo, bounds and olbounds.</td>
      <td><code>wgs84_</code></td>
    </tr>
    <tr class="important">
      <td><code>geo</code></td>
      <td>Filters results within a certain distance of a geographic point. Format: <code>latitude,longitude,distance_in_meters</code>. Also adds a <code>geodist</code> field to the select list.</td>
      <td><code>53.0,-2.0,5000</code></td>
    </tr>
    <tr>
      <td><code>bounds</code></td>
      <td>Filters results within a bounding box. Format: <code>min_latitude,min_longitude,max_latitude,max_longitude</code> (comma-separated, no parentheses).</td>
      <td><code>52.0,-3.0,54.0,-1.0</code></td>
    </tr>
    <tr>
      <td><code>olbounds</code></td>
      <td>Alternative bounding box filter, often from OpenLayers. Format: <code>min_longitude,min_latitude,max_longitude,max_latitude</code>.</td>
      <td><code>-3.0,52.0,-1.0,54.0</code></td>
    </tr>
    <tr>
      <td><code>geo2</code></td>
      <td>Similar to <code>geo</code>, but allows for a second geo-distance calculation with a potentially different prefix, adding a <code>geo2</code> field to the select list. Format: <code>latitude,longitude,distance_in_meters,optional_prefix</code>. A negative distance inverts the condition (greater than).</td>
      <td><code>53.1,-2.1,10000,v_</code></td>
    </tr>
    <tr>
      <td colspan="3"><br><strong>Other Parameters:</strong></td>
    </tr>
    <tr>
      <td><code>describe</code></td>
      <td>If set to <code>1</code>, the API will return a description of the Sphinx index, including available fields and their types. (ignores most other params other than index selection)</td>
      <td><code>1</code></td>
    </tr>
    <tr>
      <td><code>utf</code></td>
      <td>Set to <code>1</code> or <code>2</code> for different levels of UTF-8 encoding adjustments on text fields like <code>title</code> and <code>realname</code>.</td>
      <td><code>1</code></td>
    </tr>
    <tr>
      <td><code>mnmx</code></td>
      <td>Used in conjunction with date fields, potentially for getting min/max date ranges after a query. The API can use <code>FROM_DAYS()</code> values to convert interger DAYS back to Date.</td>
      <td><code>1</code> (when selecting <code>MIN(date_attr) as mn, MAX(date_attr) as mx</code>)</td>
    </tr>
    <tr>
      <td><code>option</code></td>
      <td>Allows passing specific options to the Sphinx query engine.</td>
      <td><code>max_query_time=5000</code></td>
    </tr>
  </tbody>
</table>

<p><strong>Note:</strong> Some parameter interactions can be complex. For instance, geo searches might automatically add <code>MATCH</code> clauses or bounding box filters under certain conditions. The <code>api-facetql.php</code> script contains internal logic to handle these cases.</p>
<h2>Output Format</h2>

<p>The API returns data in JSON format by default. If the <code>callback</code> parameter is supplied, the output will be JSONP, wrapped in the specified callback function.</p>

<p>The JSON response is an object with two main properties:</p>
<ul>
  <li class=important>
    <strong><code>rows</code></strong>: An array of objects, where each object represents a result item (e.g., a Geograph image or article). The attributes included in each object depend on the <code>select</code> parameter used in the request. If the query yields no results, or an error occurs during the Sphinx query for rows, this can be <code>false</code>.
  </li>
  <li>
    <strong><code>meta</code></strong>: An object containing metadata about the query execution. This typically includes information returned by Sphinx's <code>SHOW META</code> command, such as:
    <ul>
      <li><code>total</code>: Total number of items found in the index that match the query (ie maximum can retrive, typically capped at 1000).</li>
      <li class=important><code>total_found</code>: Total number of items found matching the query.</li>
      <li><code>time</code>: Time taken by Sphinx to execute the query (in seconds).</li>
      <li><code>keyword[0]</code>, <code>docs[0]</code>, <code>hits[0]</code> (and so on for each keyword): Information about the search terms.</li>
      <li>Any errors encountered during the query will also typically appear in the <code>meta</code> object (e.g., under an 'error' key).</li>
    </ul>
  </li>
</ul>

<h3>Example JSON Output (with <code>pretty=1</code>):</h3>
{literal}
<pre><code>
{
  "rows": [
    {
      "id": "12345",
      "title": "A Sample Geograph Title",
      "user_id": "789",
      "grid_reference": "SO123456"
    },
    {
      "id": "67890",
      "title": "Another Interesting Photo",
      "user_id": "101",
      "grid_reference": "TL654321"
    }
  ],
  "meta": {
    "total": "1500",
    "total_found": "1500",
    "time": "0.023",
    "keyword[0]": "sample",
    "docs[0]": "2000",
    "hits[0]": "3500"
  }
}
</code></pre>

<h3>Example JSONP Output (with <code>callback=myCallbackFunction</code>):</h3>
<pre><code>
/**/myCallbackFunction({
  "rows": [
    // ... (data as above)
  ],
  "meta": {
    // ... (metadata as above)
  }
});
</code></pre>
{/literal}
<h2>Available Fields/Attributes</h2>

<p>The specific fields or attributes available for querying and including in the <code>select</code> parameter depend on the Sphinx index being used (controlled by parameters like <code>cc</code>, <code>gg</code>, etc., or defaulting to <code>sample8</code>).</p>

<p>A list of fields for the default <code>sample8</code> index (and others) can typically be found at:
<strong><a href="/stuff/describe.php?index=sample8" target="_blank">Describe Index Sample8</a></strong>
</p>
<p>You can also use the <code>describe=1</code> parameter in an API call to get a JSON listing of the currently active Sphinx index, which will list its fields and their types.</p>

<h3>Special Fields/Attributes</h3>
Most fields are fairly easy to work with, but some need extra care. 
<ul>
	<li><tt>wgs84_lat/wgs84_long</tt> - Subject lat/long in <b>radians</b>, not degrees. most languages have rad2deg function can use
	<li><tt>vlat/vlong</tt> - ditto, they are radians for the photographer position (but still wgs84)
	<li><tt>hash</tt> - the encoded hash, to be used with <a href="https://github.com/geograph-project/Leaflet.GeographPhotos/blob/c252a1247d832bf6b572146a42b502fcfa6f67d3/Leaflet.GeographPhotos.js#L370">getGeographUrl</a> to get the image URL!
	<li><tt>comment</tt> - the image description. Notably its one of the few non-stored fields, so can query the image description in MATCH, but NOT retrive. The raw text is not stored. 
		If want image description, will need a seperate API call. 
	<li>multi-strings eg <tt>tags</tt> - fiels ending in 's' and a string attrute are multit tag seperated by _SEP_. Example JS...<br>
		<code>var tags = images[q].tags.replace(/(^\s*_SEP_\s*|\s*_SEP_\s*$)/g,'').split(/ _SEP_ /);</code>
	
	<li>MVA + string pairs, eg <tt>tags/tag_ids</tt> - MVAs are good for GROUP BY, but interger only. So may need a way to decode back, particully during GROUP BY<br>Perhaps best demonstrated with some code
	{literal}
	<pre><code>    const column = 'tags'; // The column that contains the string values
    const group = 'tag_ids'; // This is the MVA column

    const queryParams = {
        select: `GROUPBY() AS groupby,${group},${column},COUNT(*) AS count`,
        match: 'bridge',
        group: group,
        order: 'count DESC',
    };
    
    //fetch values from the api and decode the json
    
    let rows = decoded.rows;
    
    if (rows && rows.length > 0 && group.endsWith('_ids')) {
	rows = rows.map(row => {
	    const ids = row[group].split(',');
	    // The first element after splitting by '_SEP_' is always blank,
	    // so we account for that by slicing from index 1.
	    const names = row[column].split('_SEP_').slice(1);

	    // Find the index of groupby value in ids and use that index to get the corresponding name
	    const groupbyIndex = ids.indexOf(row['groupby']);
	    if (groupbyIndex !== -1 && names[groupbyIndex] !== undefined) {
		row['groupby'] = names[groupbyIndex].trim();
	    }
	    return row;
	});
    }</code></pre>{/literal}
	<li><tt>width/height</tt> - size of the 640px version, mainly useful to get aspect ratio. There may well be a bigger image available, see <tt>original</tt>
	<li><tt>scenti/viewsquare</tt> - special encoded versions of eastings/northings (encoded as single interger!)
	<li>There are some other obscure fields/attibutes, used for internal Geograph purposes, but probably not of interest. 
</ul>
<hr/>
<p><a href="/api-facetql.php">Basic API interactive query form</a></p>

{include file="_std_end.tpl"}
