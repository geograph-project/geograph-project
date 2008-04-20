{assign var="page_title" value="Submit to Geograph"}
{include file="_std_begin.tpl"}

   <h2>Login Required</h2>
   <p>You must <a href="/login.php">login</a> to access this page. If you haven't
registered yet, <a title="register now" href="/register.php">go and register</a>, it's quick and free!</p>


<p>Submitting photos via Geograph is a 4 step process:</p>

<ol>
	<li><b>Define the square</b> for your image - by entering or selecting the Grid Reference, searching by Placename, dragging on Map.</li>
	<li><b>Selecting the image</b> to upload, and pinpointing its <b>ocation  on a 1:50,000 map</b> (or Road Map in Ireland)</li>
	<li>Entering <b>title, description, category, and date</b> (which can be read direct from EXIF in the photo)</li>
	<li><b>Confirming the Licence Terms</b>, and attribution options</li>
</ol>
 
<p>There may be an optional 5th step if choose to enter the photograph in any competitions that happen to be running at the time.</p> 
 
<hr/>

<p>As well as the above submission process, we have the following alternatives</p>

<ul>
	<li>Upload a <b>Geotagged Image</b> instead of entering location in step 1<ul>
		<li>(either Geo Extensions to the EXIF specification, or the file named with the Grid Reference</li>
	</ul></li>
	
	<li>Preprocess the image with <b>{external href="http://www.picnik.com/" text="Picnik"} online image manipulation</b> service, transfering the image automatically to continue the above process</li>
	
 	<li>Bulk upload photos using an on <b>offline Java application</b>, locate and describe your images offline, and then upload when ready to connect to the internet. (note does not include the draggable icons on map)</li>
 	
 	<li>Upload <b>multiple</b> photos via <b>{external href="http://picasa.google.com/" text="Picasa"} image mananagement</b> program, fully intergrates the submission process into Picasa, optimized for multiple images at once.</li>
</ul>	
 	
 <p>Login to access all the above submission options.</p>

{include file="_std_end.tpl"}
