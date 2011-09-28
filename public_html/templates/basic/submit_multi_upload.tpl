{include file="_std_begin.tpl"}

<div style="float:right;position:relative">&middot; <a href="/help/submission">Alternative Submission Methods</a> &middot;</div>

	<h2>Multiple Image Submission <sup>Experimental</sup></h2>

<div style="position:relative;">
	<div class="tabHolder">
		<a class="tabSelected nowrap" id="tab1">A) Add/Upload Images</a>&nbsp;
		<a class="tab nowrap" id="tab2" href="{$script_name}?tab=submit#sort=Uploaded%A0%A0%u2193">B) Submit Images (v1)</a>
		<a class="tab nowrap" id="tab3" href="/submit2.php?multi=true">B) Submit Images (v2) <sup style="color:red">New!</sup></a>
	</div>

	<div class="interestBox">
<link rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.9/themes/base/jquery-ui.css" type="text/css" />
<link rel="stylesheet" href="{"/plupload/js/jquery.ui.plupload/css/jquery.ui.plupload.css"|revision}" type="text/css" />

<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.5.1/jquery.min.js" type="text/javascript"></script>
<script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.9/jquery-ui.min.js" type="text/javascript"></script>

<!-- Thirdparty intialization scripts, needed for the Google Gears and BrowserPlus runtimes -->
<script type="text/javascript" src="http://bp.yahooapis.com/2.4.21/browserplus-min.js"></script>

<script type="text/javascript" src="{"/plupload/js/plupload.full.js"|revision}"></script>
<script type="text/javascript" src="{"/plupload/js/jquery.ui.plupload/jquery.ui.plupload.js"|revision}"></script>

{literal}
<script type="text/javascript">
// Convert divs to queue widgets when the DOM is ready
$(function() {
	$("#uploader").plupload({
		// General settings
		runtimes : 'html5,browserplus,silverlight,gears,html4',
		url : '{/literal}{$script_name}{literal}',
		max_file_size : '8mb',
		max_file_count: 20, // user can add no more then 20 files at a time
	//	chunk_size : '1mb',
		unique_names : true,
		multiple_queues : true,

		// Resize images on clientside if we can
		//resize : {width : 640, height : 640, quality : 90},

		// Rename files by clicking on their titles
		rename: true,

		// Sort files
		sortable: true,

		// draging
		dragdrop: true,

		// Specify what files to browse for
		filters : [
			{title : "JPG files", extensions : "jpg,jpeg"},
		],

		// Flash settings
		flash_swf_url : '/plupload/js/plupload.flash.swf',

		// Silverlight settings
		silverlight_xap_url : '/plupload/js/plupload.silverlight.xap'
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
				<input type="radio" name="size" value="65536" checked onclick="setResize(this)"/> No Resize |
				<input type="radio" name="size" value="1600" onclick="setResize(this)"/> 1600 pixels |
				<input type="radio" name="size" value="1024" onclick="setResize(this)"/> 1024 pixels |
				<input type="radio" name="size" value="800" onclick="setResize(this)"/> 800 pixels |
				<input type="radio" name="size" value="640" onclick="setResize(this)"/> 640 pixels<br/>
				(images are resized in your browser before being sent to Geograph - EXIF data may be stripped)
			</fieldset>

			<div id="uploader">
				<p>You browser doesn't have Flash, Silverlight, Gears, BrowserPlus or HTML5 support.</p>
			</div>

			<ol>
				<li>Choose what size file you want to upload. By resizing the image before upload, you save time and bandwidth. But EXIF data will not be available to the Geograph website</li>
				<li>Click "<b>Add Files</b>" and select image(<b>s</b>) you want to upload. Maximum filesize 8Mb.<ul>
					<li>Tip: In some browsers can also drag and drop images onto the white area above<br/> (you will see a message to that affect if your browser supports it)</li></ul></li>
				<li>Once you have selected all files (can repeat step 1. to select upto 20 files) - click "<b>Start Upload</b>"</li>
				<li>When all files are uploaded, click "<b>Submit Images</b>" to continue to next stage</li>
			</ol>
		</form>

	</div>
</div>


{include file="_std_end.tpl"}
