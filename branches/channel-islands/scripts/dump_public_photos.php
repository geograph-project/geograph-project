<?php

chdir(__DIR__."/../public_html/");

$find = `find photos/ -mindepth 2 -maxdepth 2 -type d | sort`;


foreach (explode("\n",$find) as $folder) {
	if ($folder) {
		$dd = preg_replace('/\d{1}$/','',$folder);
		@$folders[$dd][] = $folder;
	}
}

foreach ($folders as $dd => $list) {
	$do = false;
	foreach ($list as $folder)
		if (filemtime($folder) > time()-3600*72)
			$do = true;
	$cmd = 'find '.implode(' ',$list).' -name "??????_????????.jpg" | tar -czf dumps/'.preg_replace('/[^\w]+/','',$dd).'.tar.gz -T -';
	if ($do) {
		print "$cmd\n";
		`$cmd`;
	}
}


