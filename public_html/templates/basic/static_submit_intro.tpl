{assign var="page_title" value="Submit to Geograph"}
{assign var="meta_description" value="General overview and introduction to the submit process used to contribute images."}
{include file="_std_begin.tpl"}
{literal}
<style type="text/css">
	ul.spaced li, ol.spaced li {
		padding-top:10px;
		padding-bottom:10px;
		border-top:1px solid #eeeeee;
		border-bottom:1px solid #eeeeee;
	}
	ul.spaced ul li {
		border:none;
	}
</style>
{/literal}

{dynamic}
{if !$user->registered}
   <h2>Login Required</h2>
   <div class="interestBox">You must <a href="/login.php">login</a> to access this page. If you haven't
registered yet, <a title="register now" href="/register.php">go and register</a>, it's quick and free!</div>
{/if}
{/dynamic}

<p>Submitting photos via Geograph is a 4-step process:</p>

<ol class="spaced">
	<li><b>Define the square</b> for your image - by entering or selecting the grid reference, searching by placename or dragging on map.</li>
	<li><b>Select the image</b> to upload, and pinpoint its <b>location on a 1:50,000 map</b> (or road map in Ireland).</li>
	<li>Enter the <b>title, description, category, and date</b> (which can be read direct from EXIF in the photo).</li>
	<li><b>Confirm the licence terms</b>, and attribution options.</li>
</ol>

{dynamic} 
{if $user->registered}
 <p>Go to the <a href="/submit.php">submit page</a> to send in your photo now!</p>
{/if}
{/dynamic}

<div class="interestBox">
        Other Resources: <a href="/submit-example.php">Preview pages of the Submission Process</a> &middot; <a href="/faq3.php?a=49#49">Video Demonstation</a> &middot; 
		<a href="/faq3.php?q=submit">FAQs</a><br/><br/>
	&nbsp; &nbsp; We also have a prototype <a href="/guider/submit-demo.html">Guided Demo of the First page of submission</a>.
	<div style="text-align:right"><a href="/article/Geograph-Introductory-letter">Geograph Introductory letter</a></div>
</div>

<br/><br/>

<p>As well as the above submission processes, we have the following alternatives:</p>

<ul class="spaced">
	<li>Upload a <b>Geotagged image</b> instead of entering location in step 1<ul>
		<li>(either Geo Extensions to the EXIF specification, or the file named with the grid reference)</li>
	</ul></li>
	
	<li>Pre-process the image with <b>{external href="http://www.picnik.com/" text="Picnik"} online image manipulation</b> service, transferring the image automatically to continue the above process.</li>
	
 	<li>Bulk upload photos using an on <b>offline Java application</b>, locate and describe your images offline, and then upload when ready to connect to the internet (this does not include the draggable icons on map).</li>
 	
 	<li>Upload <b>multiple</b> photos via <b>{external href="http://picasa.google.com/" text="Picasa"} image management</b> program, fully integrates the submission process into Picasa, <b>optimized for multiple images at once</b>.</li>
</ul>	
 {dynamic}
{if $user->registered}
 <p>Continue to the <a href="/submit.php">submit process</a> to access the above options</p>
{else}
 <p>Login to access all the above submission options.</p>
{/if}
{/dynamic}
{include file="_std_end.tpl"}
