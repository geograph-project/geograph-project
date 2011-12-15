{assign var="page_title" value="Submission Processes"}
{include file="_std_begin.tpl"}

<h2>Geograph Submission Processes</h2>

<div style="background-color:pink; color:black; border:2px solid red; padding:10px; width:200px; float:right"><b>First time here?</b><br/> Please check the following resources: <br/>
<a href="/help/submit_intro">Submission Introduction</a>, <br/>
<a href="/faq.php">FAQ</a>, <a href="/article/Geograph-Introductory-letter">Introduction</a> and<br/> <a href="/article/Geograph-Quickstart-Guide">Quickstart Guide</a>.</div>

 <p>We have a number of ways to submit images to Geograph, a summary:</p>


 <h3><a href="/help/submit">Main methods</a></h3>

 <p style="margin-left:20px">See the main submission methods - if unsure start here.</p>


 <h3><a href="/submit-nofrills.php">No-Frills Submit</a></h3>

 <p style="margin-left:20px">The bare minimum required to submit a image - recommended for seasoned contributors only!</p>


 <h3><a href="/juppy.php">JUppy Java&trade; Client</a></h3>

 <p style="margin-left:20px">Downloadable application to batch submit from your desktop. Works but a little rough around the edges.</p>

 <p style="font-size:0.7em;margin-left:20px"><a href="/juppy.php">JUppy</a> is coded in cross-platform Java, and is a solution to upload many images, allowing you to prepare the images without an internet connection. <b><a href="/juppy.php">Read More, and Get it Now!</a></b></p>


 <h3><a href="picasa://importbutton/?url=http://{$http_host}/stuff/geograph-for-picasa.pbz.php/geograph-for-picasa.pbz">Picasa Plugin</a></h3>

 <p style="margin-left:20px">Plugin for the popular {external href="http://picasa.google.com/" text="Picasa"} image mananagement program.</p>

 <p style="font-size:0.7em;margin-left:20px">With this button installed can use the selection tools in Picasa to upload photos in bulk, the submission process matches the online upload allowing selection with maps etc. Picasa automatically resizes the photo to Geograph specifications before upload, EXIF data is preserved, however it's only provided to Geograph at the end so it can't be used to find geolocation or dates embedded in the file. <br/>
 <b><a href="picasa://importbutton/?url=http://{$http_host}/stuff/geograph-for-picasa.pbz.php/geograph-for-picasa.pbz">Install the Geograph Uploader, Picasa Button</a></b>.<br/> (You will be asked to confirm this action, <b>only works if have Picasa installed!)</b></p>
 <p style="font-size:0.7em;margin-left:20px">Note while JUppy is an Offline Application, with which you can prepare the upload in advance of connecting; the Picasa button requires a Internet Connection to work as it integrates the interactive maps and other aids from the Geograph website.</p>

<hr/>
<br/>
<br/>


<h2>Feature Matrix</h2>

<table style="font-size:0.9em" border=1 cellspacing=0 cellpadding=3 class="report">
<thead>
 <tr>
  <td>.</td>
  <td>Submit</td>
  <td>Submit v2</td>
  <td>Multi-Submit</td>
  <td>No Frills Submit</td>
  <td>Juppy</td>
  <td>Picasa</td>
 </tr>
</thead>
 <tr>
  <td>JavaScript Required</td>
  <td style="background-color:lightgreen" align="center">NO</td>
  <td align="center">yes</td>
  <td align="center">yes</td>
  <td align="center">yes</td>
  <td align="center">na</td>
  <td align="center">yes</td>
 </tr> <tr><td colspan="7" style="background-color:gray;line-height:3px">.</td></tr>
 <tr>
  <td>Upload larger than 640px</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td></td>
  <td></td>
 </tr>
 <tr>
  <td>Upload via Website</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td></td>
  <td></td>
 </tr>
 <tr>
  <td>Upload via Picnik</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td></td>
  <td></td>
  <td></td>
  <td></td>
  <td></td>
 </tr>
 <tr>
  <td >Upload via Application</td>
  <td></td>
  <td></td>
  <td></td>
  <td></td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
 </tr>
 <tr>
  <td>Multiple Image Upload</td>
  <td></td>
  <td></td>
  <td style="background-color:lightgreen" align="center">20</td>
  <td></td>
  <td style="background-color:lightgreen" align="center">hundreds</td>
  <td style="background-color:lightgreen" align="center">10</td>
 </tr>
 <tr><td colspan="7" style="background-color:gray;line-height:3px">.</td></tr>
 <tr>
  <td>GPS Exif Extraction</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td></td>
  <td></td>
 </tr>
 <tr>
  <td>GR from Filename</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td></td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td></td>
 </tr>
 <tr>
  <td>Enter Grid Square</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
 </tr>
 <tr>
  <td>Select Grid Square</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td></td>
  <td></td>
  <td></td>
  <td></td>
  <td></td>
 </tr>
 <tr>
  <td>Find Square on Map</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td></td>
  <td></td>
  <td></td>
  <td style="background-color:lightgreen" align="center">*</td>
 </tr>
 <tr><td colspan="7" style="background-color:gray;line-height:3px">.</td></tr>
 <tr>
  <td>Subject/Photographer on 50k Map</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:red"></td>
  <td style="background-color:red"></td>
  <td style="background-color:lightgreen" align="center">*</td>
 </tr>
 <tr><td colspan="7" style="background-color:gray;line-height:3px">.</td></tr>
 <tr>
  <td>EXIF Date Extraction</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:red" align="center"></td>
  <td style="background-color:red"></td>
 </tr>
 <tr>
  <td>EXIF Preservation[1]</td>
  <td align="center">resized</td>
  <td align="center">resized</td>
  <td align="center">resized<span style="background-color:red">[2]</span></td>
  <td align="center">resized</td>
  <td align="center">resized</td>
  <td align="center">yes</td>
 </tr>
 <tr>
  <td>Image Dimensions Checked</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td></td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td></td>
  <td></td>
  <td></td>
 </tr>
 <tr><td colspan="7" style="background-color:gray;line-height:3px">.</td></tr>
 <tr>
  <td>Style Guide for Title/Description</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td></td>
  <td></td>
  <td style="background-color:lightgreen" align="center">*</td>
 </tr>
 <tr>
  <td>Shared Descriptions</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td></td>
  <td style="background-color:lightgreen" align="center">*</td>
 </tr>
 <tr><td colspan="7" style="background-color:gray;line-height:3px">.</td></tr>
 <tr>
  <td>Category Dropdown</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td></td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
 </tr>
 <tr>
  <td>Category Auto-Complete</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td></td>
  <td></td>
  <td style="background-color:lightgreen" align="center">*</td>
 </tr>
 <tr><td colspan="7" style="background-color:gray;line-height:3px">.</td></tr>
<thead>
 <tr>
  <td>.</td>
  <td>Submit</td>
  <td>Submit v2</td>
  <td>Multi-Submit</td>
  <td>No Frills Submit</td>
  <td>Juppy</td>
  <td>Picasa</td>
 </tr>
</thead>
</table>


<b>Notes</b>
<ol>
	<li>'resized' - EXIF perserved if image is resized to 640px before upload. If left to application, EXIF data lost from image file itself.<br/>
	However with all methods we still store the EXIF data for future use.</li>
	<li>The new multi-submit process, allows automatic resizing of the image. Unfortunately this strips all EXIF data from the image (so it's not even sent to the server). To preserve the data, you need to resize the image in a program that preserves it.</li>
</ol>

<br/><br/><br/>

{include file="_std_end.tpl"}
