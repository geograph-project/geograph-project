#!/usr/bin/perl

foreach (0..75) {
	$_ = sprintf("%02d",$_);

	if (-d "$_") {
		#carry on...
	} elsif (!(-e "$_")) {
		#create a link for the future
		`ln -s /mnt/jam/geograph_live/public_html/photos/$_ $_`;
	} else {
		#must be a link so could we convert it to a folder?
		#todo

$filter = '--include="*/" --include="*120x120.jpg" --include="*213x160.jpg" --exclude="*'

#mkdir $_-real if !-e
#rsync -rt --stats --bwlimit=200 $filter jam:/var/www/geograph_live/public_html/photos/$_/* $_-real/
#rmlink
#mv $_-real $_

	}

}
