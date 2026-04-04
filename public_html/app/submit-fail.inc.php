<?php

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

    print "<div class='card'>";
        print "<h3>" . htmlentities($text) . "</h3>";

        if (!empty($existing)) {
        	echo "<p class='explanation'><strong>Note:</strong> The most likely cause is simply that the form was submitted multiple times in quick succession. It doesn't really matter - we already have your submission below.</p>";

            echo "<p>This image was already processed as ID: <strong>$existing</strong>.</p>";
	        echo "<div class='btn-group'>";
                echo "  <a href='/photo/$existing' class='btn btn-primary'>View the Photo Page</a>";
        } else {
            print "<p>Please go back, correct the values, and press <strong>'I Agree'</strong> again.</p>";
            echo "<div class='btn-group'>";
        }
            echo "  <a href='/app/' target='_top' class='btn btn-secondary'>Back to Home</a>";
        echo "</div>";

    print "</div>";

