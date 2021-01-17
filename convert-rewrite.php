<?

$input = "system/webserver/etc/apache2/sites-available/www.geograph.org.uk";
$output = "system/docker/geograph/etc/nginx/sites-enabled/geograph";

$done = array();
foreach (file($output) as $line) {
	if (preg_match('/^\s*rewrite ([^ ]+)/',$line,$m))
		$done[$m[1]] = 1;
}

print_r($done);

$inside = false;
foreach (file($input) as $line) {
	if ($inside) {
		if (preg_match('/End General/',$line))
			$inside = false;
		else {
			if (preg_match('/^\s*RewriteRule ([^ ]+)\s+([^ ]+)\s*(\[[\w,]+\])?/',$line,$m)) {
				@list(,$rule,$replace,$flags) = $m;

				if (strpos($rule,'}') || strpos($rule,';')) //If a regular expression includes the .}. or .;. characters, the whole expressions should be enclosed in single or double quotes.
					$rule = '"'.$rule.'"';

				if (isset($done[$rule]))
					continue;

				/*
				RewriteRule ^/photo/([0-9]+)\.xml /api/photo/$1 [qsa]
				 rewrite ^/photo/([0-9]+)\.xml /api/photo/$1;
				*/
				if (empty($flags)) { //
					if (preg_match('/^http/',$replace)) {
						$replace = str_replace('%{HTTP_HOST}','$host',$replace);
						$replace = str_replace('http://','https://',$replace);
						print "  rewrite $rule $replace permanent;\n";
					} else {
						//QSA is automatic in NGINX.If you don't want it, add ? to the end of your new location
						print "  rewrite $rule $replace? ;\n";
					}
					continue;
				} elseif ($flags == '[qsa]') {
					print "  rewrite $rule $replace last;\n";
					continue;
				} elseif ($flags == '[qsa,r]') {
					print "  rewrite $rule $replace permanent;\n";
					continue;
				} elseif ($flags == '[L]') {
					print "  rewrite $rule $replace last;\n";
					continue;
				}

				print_r($m);
				exit;

			} else {
				print "\n";//just to maintain some sepeation";
			}
		}
	} elseif (preg_match('/GENERAL RULES/',$line))
		$inside=true;
	}
