<?php

require_once('geograph/global.inc.php');
init_session();


function post_to_url($url, $fields, $user_agent = 'MyPHPApp/1.0') {
    // 1. Initialize cURL session
    $ch = curl_init($url);

    // 2. Configure cURL options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return response as string
    curl_setopt($ch, CURLOPT_POST, true);           // Use POST method
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields)); // Format array to query string

    curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);

    // Optional: Add timeouts to prevent your script from hanging indefinitely
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    // 3. Execute the request
    $response = curl_exec($ch);

    // 4. Check for errors
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception("cURL Error: " . $error);
    }

    // 5. Close session
    curl_close($ch);

    return $response;
}


$message = null;

if (!empty($_POST['submit_x'])) {
	$result = post_to_url("https://company.geograph.org.uk/support/open.php", $_POST, $_SERVER['HTTP_USER_AGENT']);

	//print htmlentities($result);

	if (strpos($result,'Support ticket request created') !== FALSE) {
		$message = "Support ticket request created";
	} else {
		$message = "Sorry, something went wrong. Press Back and try submitting again, or errors persist, contact us at ".$CONF['contact_email'];
	}
}

##################################


$referring_page="n/a";
if (isset($_REQUEST['referring_page']))
        $referring_page=$_REQUEST['referring_page'];
elseif (isset($_SERVER['HTTP_REFERER']))
        $referring_page=$_SERVER['HTTP_REFERER'];

$t = time();
$n = bin2hex(random_bytes(16));
$tok = base64_encode("$n.$t").".".hash_hmac('sha256',$t.$n, $CONF['token_secret']);

?>
<!DOCTYPE html>
<html>
<head>
   <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact</title>

    <style>
        :root {
            --primary: #007AFF;
            --success: #28a745;
            --bg: #f8f9fa;
            --accent: #6c757d;
        }
        body { font-family: -apple-system, system-ui, sans-serif; background: var(--bg); margin: 0; padding: 20px; }
        .card { background: white; padding: 24px; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); width: 100%; max-width: 450px; text-align: center; }

        @media screen and (max-width: 500px) {
                body {
                        padding:20px 2px;
                }
                .card {
                        padding:20px 2px;
                }
        }

        /* Custom Buttons */
        .btn { padding: 14px 28px; border-radius: 12px; border: none; cursor: pointer; touch-action: manipulation; user-select: none; font-weight: 600; transition: all 0.2s; display: inline-block; margin: 8px 0; font-size: 16px; }
        .btn-select { background: var(--primary); color: white; width: 100%; box-sizing: border-box; text-align:center }
        .btn-upload { background: var(--primary); color: white; width: 100%; }
        .btn-upload:disabled { background: #ccc; cursor: not-allowed; }
        .btn-secondary { background: #e9ecef; color: #333; width: 100%; }


.report-form .form-group {
    display: flex;
    flex-wrap: wrap; /* Allows label to wrap above input on small screens */
    margin-bottom: 1em;
    align-items: center; /* Vertically align label and input group */
}

.report-form .form-label {
    flex-basis: 150px; /* Set a basis for the label width */
    margin-right: 1em;
    font-weight: bold;
    padding-top: 0.3em; /* Align with text box content */
}

.report-form .input-group {
    flex-grow: 1; /* Allows the input group to take remaining space */
    display: flex;
    flex-direction: column; /* Stack input and small text if any */
}

.report-form .input-group input[type="text"],
.report-form .input-group input[type="email"],
.report-form .input-group textarea {
    width: 100%; /* Make inputs fill their container */
    max-width: 640px; /* Optional: prevent inputs from becoming too wide */
    box-sizing: border-box; /* Include padding and border in the element's total width and height */
    padding: 0.4em; /* Added padding for better appearance */
    border: 1px solid #ccc; /* Added border for better appearance */
    border-radius: 3px; /* Added border-radius for better appearance */
}

.report-form .input-group textarea {
    min-height: 80px; /* Ensure textarea is a reasonable size */
    resize: vertical; /* Allow vertical resizing */
}

.report-form .error-marker {
    color: red;
    margin-left: 0.25em;
    font-weight: bold;
}

.report-form .input-group small {
    font-size: 0.85em;
    color: #555;
    margin-top: 0.25em;
}

.report-form .form-buttons {
    margin-top: 1.5em;
    margin-left: 160px; /* Align with inputs if labels are 150px + 1em margin */

    display: flex;
    justify-content: space-between; /* Pushes the buttons to opposite ends */
    align-items: center;            /* Vertically centers them */
    gap: 10px;                      /* Provides consistent spacing if they do wrap */
    padding: 10px 0;

}

.report-form .form-buttons input.button {
    margin-right: 0.5em;
    padding: 0.5em 1em; /* Added padding for better appearance */
    border-radius: 3px; /* Added border-radius for better appearance */
}

/* Responsive adjustments for smaller screens */
@media (max-width: 767px) {
    .report-form .form-group {
        flex-direction: column; /* Stack label and input group */
        align-items: flex-start; /* Align items to the start */
    }

    .report-form .form-label {
        flex-basis: auto; /* Allow label to take its own width */
        margin-right: 0;
        margin-bottom: 0.5em; /* Space between label and input */
        padding-top: 0;
    }

    .report-form .input-group input[type="text"],
    .report-form .input-group input[type="email"],
    .report-form .input-group textarea {
        max-width: 100%; /* Allow inputs to use full width */
    }

    .report-form .form-buttons {
        margin-left: 0; /* Remove padding when stacked */
    }
}

    </style>

</head>

<body>

<? if (!empty($message)) {
	print "<p>".htmlentities($message); ?></p>

	<button type="button" name="cancel" value="Cancel" class="btn btn-select">Back to Home</button>

<? } else { ?>
	<form method="POST" enctype="multipart/form-data" class="report-form">
	    <input type="hidden" name="ref" value="<? echo htmlentities($referring_page); ?>"/>
	    <input type="hidden" name="user_id" value="<? echo $USER->user_id; ?>"/>
	    <input type="hidden" name="gtok" value="<? echo $tok; ?>"/>

	    <div class="form-group">
	        <label for="name" class="form-label">Full Name:</label>
	        <div class="input-group">
	            <input type="text" id="name" name="name" size="25" value="<? echo htmlentities($USER->realname); ?>" required>
	        </div>
	    </div>

	    <div class="form-group">
	        <label for="email" class="form-label">Email Address:</label>
	        <div class="input-group">
	            <input type="email" id="email" name="email" size="25" value="<? echo htmlentities($USER->email); ?>" required>
			 <small>(using this form will reveal your email address to support representatives)</small>
	        </div>
	    </div>


	<? if (isset($_GET['concern'])) { ?>
	    <input type="hidden" name="topicId" value="15">
	<?
	$subject = "Report a concern";
	 } else {
	$subject = "";
	 ?>

	    <div class="form-group">
	        <label for="topicId" class="form-label">Topic:</label>
	        <div class="input-group">

	          <select id="topicId" name="topicId" onchange=" showTopicHelp(this.options[this.selectedIndex].value);" required>
	                <option value="" selected="selected">Select a Help Topic</option>
	                <option value="11">Contact the location shown in a photograph</option><option value="5">I'm a concerned landowner</option><option value="12">I'm volunteering to help with the running of Geograph Project</option><option value="4">Image Moderation Query</option><option value="3">Login Issues</option><option value="10">Permission to use Image(s)</option><option value="15">Report a Concern</option><option value="6">Suggest a correction to an image</option><option value="9">Website is Broken</option><option value="8">Other Geograph related query</option>  
	          </select>


	                <div id="topic11" style="display:none;padding:10px;color:black;border:2px solid red;background-color:yellow;">
	                        Geograph is an online collaborative project collecting photos of every corner of the British Isles. <br/><br/>

	                        We don't hold contact details for the locations photographed, so it's unlikely we will be able to help.
	                </div>
	                <div id="topic10" style="display:none;padding:10px;color:black;border:2px solid red;background-color:yellow;">
	                        All photos on Geograph are <a href="http://creativecommons.org/licenses/by-sa/2.0/" target="_blank" style="text-decoration:underline" title="View creative commons deed - opens in new window">Creative Commons</a><img style="padding-left:2px;" alt="New Window" title="opens in a new window" src="https://s1.geograph.org.uk/img/newwin.png" width="10" height="10"/> licensed. <br/><br/>

	                        So as long as you credit the photographer when you use the image, you can use it for pretty much any purpose. Don't need to ask permission first.<br/><br/>

	                        If you want a higher resolution version, or have requirements not met by the Creative Commons licence, you need to contact the photographer. So best to contact them in the first instance, there is a link on the main photo page.
	                </div>
	                <div id="topic8" style="display:none;padding:10px;color:black;border:2px solid red;background-color:yellow;">
	                        Are you <b>sure</b> this is a <b>Geograph</b> related matter? (Geograph is an online project collecting photos of every square kilometre of Britain and Ireland)
	                </div>

			<script>
		                 function showTopicHelp(id) {
	                                var needhide = false;
	                                for (q=0;q<=30;q++) {
	                                        if (document.getElementById("topic"+q)) {
	                                                document.getElementById("topic"+q).style.display=(id==q)?'':'none';
	                                                if (id==q)
	                                                        needhide = 1;
	                                        }
	                                }
	                                var rows = document.getElementsByTagName("tr");
	                                for (var i = 0; i < rows.length; i++) {
	                                        if (rows[i].className && rows[i].className == 'tohide')
	                                                 rows[i].style.display=(needhide)?'none':'';
	                                }
	                        }
			</script>
	        </div>
	    </div>

	<? } ?>

	    <div class="form-group">
	        <label for="subject" class="form-label">Subject:</label>
	        <div class="input-group">
	            <input type="text" id="subject" name="subject" size="35" value="<? echo $subject; ?>" required>
	        </div>
	    </div>

	    <div class="form-group">
	        <label for="message" class="form-label">Message:</label>
	        <div class="input-group">
	            <textarea id="message" name="message" cols="35" rows="8" wrap="soft" placeholder="enter your message here" required></textarea>
	        </div>
	    </div>

	    <div class="form-buttons">
	        <button type="submit" name="submit_x" value="Submit Report" class="btn btn-select">Send Message</button>
		&nbsp;&nbsp;&nbsp;
	        <button type="button" name="cancel" value="Cancel" class="btn btn-secondary">Cancel</button>
	    </div>
	</form>

<? } ?>

<script type="module">
	import { escapeHTML, navigateTo } from '/app/js/utils.js';

	window.addEventListener('DOMContentLoaded', function() {

		document.querySelector('button[name="cancel"]').addEventListener('click', function() {
			navigateTo('/app/home');
		});
	});
</script>

</body>
</html>
