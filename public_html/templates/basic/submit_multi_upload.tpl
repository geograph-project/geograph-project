{if $mobile}
	{include file="_mobile_begin.tpl"}
{else}
	{include file="_std_begin.tpl"}
{/if}

<div style="float:right;position:relative"><a href="/submit.php?redir=false">v1</a> / <a href="/submit2.php">v2</a> /{if $mobile} <a href="/submit-mobile.php">single</a> / {/if}<b>multi</b> / <a href="/help/submit">more...</a></div>

	<h2>Multiple Image Submission</h2>

{dynamic}{$status_message}{/dynamic}

<div style="position:relative;">
	<div class="tabHolder">
		<a class="tabSelected nowrap" id="tab1">A) Add/Upload Images</a>&nbsp;
		<a class="tab nowrap" id="tab2" href="{$script_name}?tab=submit#sort=Uploaded%A0%A0%u2193">B) Submit Images (v1)</a>
		<a class="tab nowrap" id="tab3" href="/submit2.php?multi=true">B) Submit Images (v2)</a>
                <a class="tab nowrap" id="tab4" href="/submit2.php?multi=true&amp;display=tabs">(Tabs)</a>
	</div>

	<div class="interestBox">
<link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.9/themes/base/jquery-ui.css" type="text/css" />
<link rel="stylesheet" href="{"/plupload/js/jquery.ui.plupload/css/jquery.ui.plupload.css"|revision}" type="text/css" />

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.5.1/jquery.min.js" type="text/javascript"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.9/jquery-ui.min.js" type="text/javascript"></script>

<script type="text/javascript" src="{"/plupload/js/plupload.full.js"|revision}"></script>
<script type="text/javascript" src="{"/plupload/js/jquery.ui.plupload/jquery.ui.plupload.js"|revision}"></script>

{literal}
<script type="text/javascript">
// Convert divs to queue widgets when the DOM is ready
$(function() {
	$("#uploader").plupload({
		// General settings
		runtimes : 'html5,html4',
		url : '{/literal}{$script_name}{literal}',
		max_file_size : '24mb', //we will catch >8M seperately and force use of resize option!
		max_file_count: 100, // user can add no more then 100 files at a time
	//	chunk_size : '1mb',
		unique_names : true,
		multiple_queues : true,

		// Resize images on clientside if we can (we add this later, if the option is manually selected
		//resize : {width : 640, height : 640, quality : 90},

		// Rename files by clicking on their titles
		rename: true,

		// Sort files
		sortable: true,

		// draging
		dragdrop: {/literal}{if $mobile}false{else}true{/if}{literal},

		// Specify what files to browse for
		filters : [
			{title : "JPG files", extensions : "jpg,jpeg"},
		],

		//the actual 'resize' operation runs in the 'UploadFile' event, so we can't insert anything between 'resizing' and actully uploading :(

	        // Post init events, bound after the internal events
	        init : {
			//make sure a resize option is selected!                  
	            FilesAdded: function(up, files) {
			var ele = document.getElementById('maxSize');
	                plupload.each(files, function(file) {
				if (file.size >= 8192000 && !ele.checked) {
					alert(file.name + " is bigger than 8M, which is too big to upload as is.\n\n"+
					"However we have enabled the 'Upload Dimensions' option, that should mean it will be resized to be under 8Mb");
					ele.checked = true;
					setResize(ele);
					document.getElementById('noResize').style.display = 'none';
				}
		        });
	            },
		}
	});

	// Client side form validation
	$('#form').submit(function(e) {
		var uploader = $('#uploader').plupload('getUploader');

		// Validate number of uploaded files
		if (uploader.total.uploaded == 0) {
			// Files in queue upload them first
			if (uploader.files.length > 0) {
				// When all files are uploaded submit form
				uploader.bind('UploadProgress', function() {
					if (uploader.total.uploaded == uploader.files.length)
						$('#form').submit();
				});

				uploader.start();
			} else
				alert('You must at least upload one file.');

			e.preventDefault();
		}
	});

});

function setResize(that) {

	var uploader = $('#uploader').plupload('getUploader');

	if (that.value == "65536") {
		uploader.settings.resize= null;
	} else {
		var s = parseInt(that.value,10);
		uploader.settings.resize= {width : s, height : s, quality : 87};
	}
}

</script>
{/literal}

		<form method="post" action="{$script_name}?tab=submit" id="form">

			<fieldset>
				<legend>Upload Dimensions</legend>
				<span id="noResize">
				<input type="radio" name="size" value="65536" checked onclick="setResize(this)"/> No Resize |
				</span>
				<input type="radio" name="size" value="6400" onclick="setResize(this)"/> 6400 pixels |
				<input type="radio" name="size" value="3840" id="maxSize" onclick="setResize(this)"/> 3840 pixels |
				<input type="radio" name="size" value="3200" onclick="setResize(this)"/> 3200 pixels |
				<input type="radio" name="size" value="1600" onclick="setResize(this)"/> 1600 pixels |
				<input type="radio" name="size" value="1024" onclick="setResize(this)"/> 1024 pixels |
				<input type="radio" name="size" value="800" onclick="setResize(this)"/> 800 pixels |
				<input type="radio" name="size" value="640" onclick="setResize(this)"/> 640 pixels<br/>
				images are resized in your browser before being sent to Geograph - EXIF data <i>may</i> be stripped<br>
				<i>Note: If image is smaller than requested dimension, it will still be opened and resaved with 87% JPEG quality setting before upload (but not resized).</i>
			</fieldset>

			<div id="uploader">
				<p>You browser doesn't have Flash, Silverlight, Gears, BrowserPlus or HTML5 support.</p>
			</div>

			<ol>
				<li>Choose what size image you want to upload. (By resizing the image using the provided resize option, you save time and bandwidth. But EXIF data will probably not be available to the Geograph website)</li>
				<li>Click "<b>Add Files</b>" and select image(<b>s</b>) you want to upload. <b>Maximum filesize 8Mb</b>.<ul>
					<li style=background-color:yellow>Note, however can now select file upto 24Mb. But it will be resized before upload, can choose the max dimension. Note that even if file is smaller than requested dimension,  it will still be opened and resaved with 87% JPEG quality setting before upload.</li> 
					<li>Tip: In some browsers can also drag and drop images onto the white area above<br/> (you will see a message to that affect if your browser supports it)</li></ul></li>
				<li>Once you have selected all files (can repeat step 2. to select upto 100 files) - click "<b>Start Upload</b>"</li>
				<li>When all files are uploaded, click one of "<b>Submit Images</b>" tabs to continue to next stage using your favorite submission method</li>
			</ol>
		</form>

	</div>
</div>


{if $mobile}
	{include file="_mobile_end.tpl"}
{else}
	{include file="_std_end.tpl"}
{/if}
