{assign var="page_title" value="Submit to Geograph"}
{assign var="meta_description" value="General overview and introduction to the submit process used to contribute images."}
{include file="_std_begin.tpl"}
{literal}
<style type="text/css">
	ul.spaced li, ol.spaced li {
		padding-top:10px;
		padding-bottom:10px;
	}
	ul.spaced ul li {
		border:none;
	}
</style>
{/literal}

<h2>Geograph Image Submission</h2>

<div style="max-width:940px">

{dynamic}
{if !$user->registered && $submit}
   <h3>Login Required</h3>
   <div class="interestBox">You must <a href="/login.php">login</a> to access this page. If you haven't
registered yet, <a title="register now" href="/register.php">go and register</a>, it's quick and free!</div>
{/if}
{/dynamic}

<p>Submitting photos via Geograph is a 4-step process: <i>(Note: this is our <b>desktop</b> process, on mobile we have a different process)</i></p>

<ol class="spaced">
	<li><b>Define the square</b> for your image - by entering or selecting the grid reference, searching by placename or dragging on map. (see below for more tips)
		<ul>
			<li>Alternatively if you have a image with EXIF location, use <b>Upload a Geotagged image</b> instead of entering location in step 1
		</ul></li>
	<li><b>Select the image</b> to upload (if not using EXIF), and pinpoint <b>Subject and Camera location</b> on a high resolution map.
	<li>Enter the <b>title, description, and date</b> (which can be read direct from EXIF in the photo).
		<ul>
			<li>We also ask you to select 1-6 "Geographical Context" tags that give a general sense of the image's subject and location. No need to be precise.
			<li>You can also add optional free-form tags for more specific details. Advanced users might also be interested in <a href="/article/Shared-Descriptions">Shared-Descriptions</a>
		</ul></li>
	<li><b>Confirm the licence terms</b>, and attribution options.</li>
</ol>

{dynamic}
{if $user->registered}
 <p>Go to the <a href="/submit.php">submit page</a> to send in your photo now!</p>
{/if}
{/dynamic}

<div class="interestBox">
	More Links: 
	<b><a href="/article/Geograph-Frequently-Asked-Questions" class=nowrap>Contributor FAQ</a></b>
	 and <a href="/article/Geograph-Introductory-letter" class=nowrap>Geograph Introductory letter</a>
	<hr>
	We also have some older resources which may be slightly outdated: 
	<span class="nowrap">&middot; <a href="/submit-example.php">Preview pages of the Submission Process</a></span>
	<span class="nowrap">&middot; <a href="/faq3.php?a=49#49">Video Demonstation</a></span>
	<span class="nowrap">&middot; <a href="http://{$http_host}/guider/submit-demo.html">Guided Demo of the First page of submission</a></span>
	(we currently reviewing them to update them)
</div>

<br/><br/>

<hr>

    <h2>Tips for Adding Locations to Your Photos</h2>

    <h2>Using Geotagged Images</h2>
    <p>If your photo already includes location data (EXIF), such as those taken with a smartphone, GPS-enabled camera, or processed with software like {external href="https://media.geograph.org.uk/public.php?profile=352" text="PicLocata" target="_blank"}, use the <strong>"GeoTagged Image"</strong> option. Simply upload your image, and we'll extract the location data automatically.</p>

    <p><strong>About PicLocata:</strong> For images taken with cameras without built-in GPS, {external href="https://media.geograph.org.uk/public.php?profile=352" text="PicLocata" target="_blank"} is an app that can help. It correlates a collection of your photos with a separate tracklog (recorded on a GPS device or mobile phone) to accurately add geographical coordinates to each image.</p>

    <p><strong>Important Note for Mobile Users:</strong> Some mobile devices may strip location data from images before sharing them to protect your privacy. This means the "Geotagged Image" upload won't work on those devices. In such cases, you'll need to extract the location separately (for example, using an app like Investigator on an iPhone) and then enter it manually via the <strong>"Enter Grid Reference"</strong> tab before uploading your image.</p>

    <h3>Manually Locating on a Map</h3>
    <p>You can pinpoint an approximate location using our <strong>"Locate on Map"</strong> function. This interactive, zoomable map allows you to search by placename or postcode to find the correct area. Once you've found it, you can refine the location on a more detailed map.</p>

    <h3>Using GPS Coordinates</h3>
    <p>If you have WGS84 latitude and longitude coordinates (e.g., from a GPS receiver), you can enter them directly into the <strong>"Enter Grid Reference"</strong> tab. We also have a handy <a href="/latlong.php">Lat/Long to Grid Reference Converter</a> if you need to convert your coordinates.</p>

    <h3>On-the-Go Location Tips</h3>
    <p>Modern smartphones can act as GPS devices to help you get Grid References while you're out and about. Consider using our <strong>'Radar' application</strong> (open {external href="http://m.geograph.org.uk" text="m.geograph.org.uk" target="_blank"} on your device), {external href="https://play.google.com/store/apps/details?id=uk.co.ordnancesurvey.oslocate.android" text="OS Locate" target="_blank"}, or similar apps. You can also use various apps to actively record your data track.</p>

    <h3>Resources for Great Britain and Ireland</h3>

    <h3>Great Britain Resources</h3>
    <ul>
        <li><strong>{external href="https://en.wikipedia.org/wiki/Ordnance_Survey_National_Grid" text="OSGB" target="_blank"} ({external href="http://www.ordnancesurvey.co.uk/resources/maps-and-geographic-resources/the-national-grid.html" text="Interactive Guide" target="_blank"}):</strong> This guide covers Great Britain and the Isle of Man and uses two-letter 'Myriad' prefix letters for grid references.</li>
        <li><strong>{external href="https://osmaps.ordnancesurvey.co.uk/" text="OS Maps Online" target="_blank"}:</strong> Provides interactive, zoomable maps and readily available Grid References.</li>
        <li><strong>{external href="http://magic.gov.uk/MagicMap.aspx" text="Magic Map" target="_blank"}:</strong> Offers a great selection of mapping and aerial imagery for Great Britain. You can use its 'Where am I' tool to get a 'Grid Ref' to paste into our form.</li>
    </ul>

    <h3>Ireland Resources</h3>
    <ul>
        <li><strong>{external href="http://en.wikipedia.org/wiki/Irish_national_grid_reference_system" text="Irish Grid" target="_blank"} ({external href="https://www.osi.ie/wp-content/uploads/2015/04/The-Irish-Grid-A-Description-of-the-Coordinate-Reference-System-Used-in-Ireland.pdf" text="Fuller Description" target="_blank"}):</strong> This system uses single-letter 'Myriad' prefix letters.</li>
        <li>We have a useful article: <a href="/article/Locating-photos---Republic-of-Ireland">Locating photos - Ireland</a> with additional tips covering both Northern and Republic of Ireland.</li>
    </ul>

<hr>

{dynamic}
{if $user->registered}
 <p>Continue to the <a href="/submit.php">submit process</a> to access the above options</p>
{else}
 <p>Login to access all the above submission options.</p>
{/if}
{/dynamic}

</div>

{include file="_std_end.tpl"}
