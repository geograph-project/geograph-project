{assign var="page_title" value="Submit to Geograph"}
{assign var="meta_description" value="General overview and introduction to the submit process used to contribute images."}
{include file="_std_begin.tpl"}

<h2>Submitting/Contributing to Geograph!</h2>

<table border=1 cellspacing=0 cellpadding=10 bordercolor="#dddddd">
<tr>
	<td align="center">
		<b style="font-size:1.2em"><a href="/submit.php?redir=false">Original Submission Method</a></b> (v1)<br/><br/>
		The original, and longest established process - recommended for first time users.
	</td>
</tr>
<tr>
	<td align="center">
		<b style="font-size:1.2em"><a href="/submit2.php">Submission v2</a></b> <small>(<a href="/submit2.php?display=tabs">Tabs Version</a>)</small><br/><br/>
		Newer, more advanced version, all on one page. Recommended for more advanced users.
	</td>
</tr>
<tr>
	<td align="center">
		<b style="font-size:1.2em"><a href="/submit-multi.php">Multi Submission</a></b><br/><br/>
		Upload multiple files to server in one go. Then continue via v1 or v2 to actually submit the photos.
	</td>
</tr>
<tr>
	<td align="center">
		<span style="font-size:1.2em">{external href="http://www.nearby.org.uk/geograph/speculative/" text="Speculative Upload"}</span><br/><br/>
		Upload photos, and enlist the help of others to locate the photo before transferring it for real to Geograph.
	</td>
</tr>
<tr>
	<td align="center">
		<b style="font-size:1.2em"><a href="/article/Content-on-Geograph">Submit a Collection</a></b><br/>
		This page details the various areas in which users can contribute content, including textual articles and collections of photos. <br/><br/>
		<small>Quick links to contribute:
                &middot; <a href="/article/edit.php?page=new">an Article</a>
                &middot; <a href="/discuss/?action=vtopic&forum=11">a Gallery</a>
                &middot; <a href="/blog/edit.php?id=new">a Blog Entry</a>
                &middot; {external href="http://users.aber.ac.uk/ruw/misc/geotrip_submit.php" text="GeoTrip"}
                &middot;</small><br/>
	</td>
</tr>
<tr>
	<td align="center" colspan="2">
		{external href="http://media.geograph.org.uk/" text="Media Upload"}<br/><br/>
		Dedicated hosting for drawings, files, and other media used to accompany Geograph submissions (eg drawings for incorporation into articles).
	</td>
</tr>
<tr>
        <td align="center" colspan="2">
                <a href="/submit-iphone.php">iPhone/iPad submission method</a><br/><br/>
		Experimental interface for uploading images from an iPhone/iPad device, using a third party application to get around the lack of native file upload capablitity. 
        </td>
</tr>

</table>

<br/>
<ul>
<li><a href="/help/submission">Even more submission methods</a>, including via Picasa button, or JUppy java client. Also includes technical details of the above methods.</li>
</ul>
<br/>

&middot; <label for="service">Prefered Map service in Step 2:</label> <select name="service" id="service" onchange="saveService(this);">

		<option value="OSOS">Zoomable Modern OS Mapping</option>

		<option value="OS50k">OS Modern 1:50,000 Mapping + 1940s New Popular</option>

		<option value="Google">Zoomable Google Mapping + 1920s to 1940s OS</option>

	</select> <small>(OS Maps not available for Ireland)</small></p>

{literal}
<script>

function saveService(that) {
	createCookie("MapSrv",that.options[that.selectedIndex].value,10);
}

function restoreService() {
	var newservice = readCookie('MapSrv');
	if (newservice) {
		var ele = document.getElementById('service');
		for(var q=0;ele.options.length;q++)
			if (ele.options[q].value == newservice)
				ele.options[q].selected = true;
	}
}

AttachEvent(window,'load',restoreService,false);

</script>
{/literal}

{include file="_std_end.tpl"}
