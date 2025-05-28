<h1>Geograph Faceted Search API (api-facetql.php)</h1>

<p>This API provides a JSON-based interface to Geograph's underlying search technology, which is powered by Sphinx. It essentially acts as a wrapper around SphinxQL queries.</p>

<p>To effectively use this API, it is highly recommended to have a basic understanding of Sphinx and its query language (SphinxQL). You can find more information in the <a href="http://sphinxsearch.com/docs/current.html">official Sphinx documentation</a>.</p>

<p>The API allows for flexible searching and faceting of Geograph data.</p>
<h2>URL Parameters</h2>

<p>The API accepts a variety of URL parameters to control the search query and the format of the results. Here are the main parameters:</p>

<table>
  <thead>
    <tr>
      <th>Parameter</th>
      <th>Description</th>
      <th>Example Value</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><code>match</code></td>
      <td>The full-text search query. Uses Sphinx's SPH_MATCH_EXTENDED2 syntax.</td>
      <td><code>bridge over river</code></td>
    </tr>
    <tr>
      <td><code>callback</code></td>
      <td>The name of a JavaScript function to wrap the JSON response in (for JSONP requests).</td>
      <td><code>myCallbackFunction</code></td>
    </tr>
    <tr>
      <td><code>where</code></td>
      <td>A general WHERE clause for filtering results based on attribute values. Multiple conditions can be supplied.</td>
      <td><code>user_id = 123 AND taken_year > 2020</code></td>
    </tr>
    <tr>
      <td><code>order</code></td>
      <td>Specifies the order of the results.</td>
      <td><code>taken_date DESC, id ASC</code></td>
    </tr>
    <tr>
      <td><code>group</code></td>
      <td>The attribute name to group the results by (e.g., for faceting).</td>
      <td><code>user_id</code></td>
    </tr>
    <tr>
      <td><code>within</code></td>
      <td>Specifies the order of results within each group when using the <code>group</code> parameter.</td>
      <td><code>COUNT(*) DESC</code></td>
    </tr>
    <tr>
      <td><code>limit</code></td>
      <td>The maximum number of results to return.</td>
      <td><code>50</code></td>
    </tr>
    <tr>
      <td><code>offset</code></td>
      <td>The starting offset for the results. Used for pagination. Note that <code>limit + offset</code> should generally be less than 1000 (Sphinx's default <code>max_matches</code>).</td>
      <td><code>100</code></td>
    </tr>
    <tr>
      <td><code>select</code></td>
      <td>A comma-separated list of attributes to include in the results. Use <code>*</code> to retrieve all available attributes.</td>
      <td><code>id, title, user_id, grid_reference</code></td>
    </tr>
    <tr>
      <td><code>pretty</code></td>
      <td>If set to <code>1</code>, the JSON output will be pretty-printed, making it easier to read during testing. Avoid using in production.</td>
      <td><code>1</code></td>
    </tr>
    <tr>
      <td><code>describe</code></td>
      <td>If set to <code>1</code>, the API will return a description of the Sphinx index, including available fields and their types.</td>
      <td><code>1</code></td>
    </tr>
    <tr>
      <td><code>q</code></td>
      <td>Allows you to provide a complete, raw SphinxQL query. Use with caution.</td>
      <td><code>SELECT id, title FROM sample8 WHERE MATCH('landscape') ORDER BY taken_date DESC LIMIT 10</code></td>
    </tr>
    <tr>
      <td colspan="3"><strong>Sphinx Index Selection Parameters:</strong> These parameters select different Sphinx indexes. Only one should be used at a time. If none are specified, <code>sample8</code> (or <code>sample8E,sample8D</code> if <code>recent</code> is used) is the default.</td>
    </tr>
    <tr>
      <td><code>cc</code></td>
      <td>Uses the <code>content_stemmed</code> index.</td>
      <td><code>1</code></td>
    </tr>
    <tr>
      <td><code>suggest</code></td>
      <td>Uses the <code>suggestor</code> index (likely for autocomplete/suggestions).</td>
      <td><code>1</code></td>
    </tr>
    <tr>
      <td><code>gg</code></td>
      <td>Uses the <code>germany</code> index.</td>
      <td><code>1</code></td>
    </tr>
    <tr>
      <td><code>is</code></td>
      <td>Uses the <code>islands</code> index.</td>
      <td><code>1</code></td>
    </tr>
    <tr>
      <td><code>vv</code></td>
      <td>Uses the <code>viewpoint</code> index.</td>
      <td><code>1</code></td>
    </tr>
    <tr>
      <td><code>recent</code></td>
      <td>Modifies the default index to include recent items (<code>sample8E,sample8D</code>).</td>
      <td><code>1</code></td>
    </tr>
    <tr>
      <td colspan="3"><strong>Filtering Parameters:</strong></td>
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
      <td><code>exclude[tag_id][]=10&exclude[tag_id][]=11</code></td>
    </tr>
    <tr>
      <td><code>excluderange[attribute_name]</code></td>
      <td>Excludes results where <code>attribute_name</code> is BETWEEN two values (inclusive).</td>
      <td><code>excluderange[photo_id]=1000,2000</code></td>
    </tr>
     <tr>
      <td colspan="3"><strong>Geo-spatial Parameters:</strong></td>
    </tr>
    <tr>
      <td><code>geo_prefix</code></td>
      <td>Specifies the prefix for latitude and longitude attributes (e.g., <code>wgs84_</code>, <code>v_</code>). Defaults to <code>wgs84_</code> or <code>v_</code> for the viewpoint index.</td>
      <td><code>wgs84_</code></td>
    </tr>
    <tr>
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
      <td colspan="3"><strong>Other Parameters:</strong></td>
    </tr>
     <tr>
      <td><code>utf</code></td>
      <td>Set to <code>1</code> or <code>2</code> for different levels of UTF-8 encoding adjustments on text fields like <code>title</code> and <code>realname</code>.</td>
      <td><code>1</code></td>
    </tr>
    <tr>
      <td><code>mnmx</code></td>
      <td>Used in conjunction with date fields, potentially for getting min/max date ranges after a query. The API script has specific logic to convert <code>FROM_DAYS()</code> values.</td>
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
  <li>
    <strong><code>rows</code></strong>: An array of objects, where each object represents a result item (e.g., a Geograph image or article). The attributes included in each object depend on the <code>select</code> parameter used in the request. If the query yields no results, or an error occurs during the Sphinx query for rows, this can be <code>false</code>.
  </li>
  <li>
    <strong><code>meta</code></strong>: An object containing metadata about the query execution. This typically includes information returned by Sphinx's <code>SHOW META</code> command, such as:
    <ul>
      <li><code>total</code>: Total number of items found in the index that match the query.</li>
      <li><code>total_found</code>: Total number of items found matching the query before limit/offset.</li>
      <li><code>time</code>: Time taken by Sphinx to execute the query (in seconds).</li>
      <li><code>keyword[0]</code>, <code>docs[0]</code>, <code>hits[0]</code> (and so on for each keyword): Information about the search terms.</li>
      <li>Any errors encountered during the query will also typically appear in the <code>meta</code> object (e.g., under an 'error' key).</li>
    </ul>
  </li>
</ul>

<h3>Example JSON Output (with <code>pretty=1</code>):</h3>
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
<h2>Available Fields/Attributes</h2>

<p>The specific fields or attributes available for querying and including in the <code>select</code> parameter depend on the Sphinx index being used (controlled by parameters like <code>cc</code>, <code>gg</code>, etc., or defaulting to <code>sample8</code>).</p>

<p>A list of fields for the default <code>sample8</code> index (and others) can typically be found at:
<strong><a href="https://www.geograph.org.uk/stuff/describe.php?index=sample8" target="_blank">https://www.geograph.org.uk/stuff/describe.php?index=sample8</a></strong>
</p>
<p>You can also use the <code>describe=1</code> parameter in an API call to get a description of the currently active Sphinx index, which will list its fields and their types.</p>

<hr/>
<p><a href="/api-facetql.php">Back to API interactive query form</a> (if available)</p>
<p><a href="http://data.geograph.org.uk/facets/">Back to Main Faceted Browsing for Geograph</a></p>
