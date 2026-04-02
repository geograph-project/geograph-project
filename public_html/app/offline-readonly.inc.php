<?php

//Note, this page is rendered in an iframe!

if (!empty($CONF['readonly'])) {

	header("HTTP/1.0 503 Unavailable");

	//if they sending data, should try to give better message!
        if ($_SERVER["REQUEST_METHOD"] === "POST" && empty($_POST['email'])) {

                $contentType = trim($_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '');

                if (strcasecmp($contentType, 'application/json') === 0) {

			$when = '';
			//  starting around 3pm and expected to resume by 3:15pm BST. Please
			if (preg_match('/expected to resume by ([\w: ]+)./', $CONF['submission_message'], $m))
				$when = " Expected back by " . $m[1] . ".";

			header("Content-Type:application/json");
			echo json_encode([
			        "error" => "Site Offline. Please try again later.$when"
			]);
			exit;

                } else {
                        //page to retry
			$message = "Submission is currently unavailable.";
			//want to try to get the browser to resubmit the form!
			$message .= " <a href='#' onclick='window.history.go()' class='btn btn-primary'>Retry Submission</a>";
			$message .= " (Keep app open to avoid loosing information you entered, use Resume Submission to return to this page if you navigate away)";
                }
        } else {
                //page
		$message = "Submission is currently unavailable. Please Try again later.  <a href='/app/uploaded' target='_top' class='btn btn-primary'>Back to List</a>";
        }

//} elseif (!empty($CONF['critical']) && date('Gi') >= $CONF['critical']) {
//	$message = $CONF['submission_message'];

}

############################################################

    print '<meta name="viewport" content="width=device-width, initial-scale=1">';

    // Minimal CSS for mobile legibility
    print "<style>
        body { font-family: -apple-system, system-ui, sans-serif;  line-height: 1.6;  color: #333;  padding: 16px;  margin: 0; background-color: #f4f7f6; }
        .card { max-width: 500px;  margin: 20px auto;  background: #fff;  padding: 24px;  border-radius: 12px;  box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h3 { color: #d93025; margin-top: 0; font-size: 1.4rem; }
        p { margin-bottom: 16px; font-size: 1rem; }
        .explanation { background: #fff3cd;  padding: 12px;  border-radius: 6px;  font-size: 0.95rem;  color: #856404;  border-left: 4px solid #ffeeba; }
        .btn-group { display: flex; flex-direction: column; gap: 12px; margin-top: 20px; }
        .btn { display: block;  text-align: center; padding: 14px;  text-decoration: none;  border-radius: 8px;  font-weight: 600; }
        .btn-primary { background: #1a73e8; color: #fff; }
        .btn-secondary { background: #e8eaed; color: #3c4043; }
    </style>";

##################

    print "<div class='card'>";
	//this is still expecting the message to be made safe, it just checking if HTML!
	if ($message != strip_tags($message)) {
		print $message;
	} else {
		print "<h3>" . htmlentities($message) . "</h3>";
	}
    print "</div>";

##################

    echo " <a href='/app/' target='_top' class='btn btn-secondary'>Back to Home</a>";
    exit;
