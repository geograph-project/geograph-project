<?php
/**
 * $Project: GeoGraph $
 * $Id: recreate_maps.php 2996 2007-01-20 21:39:07Z barry $
 *
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Barry Hunter (geo@barryhunter.co.uk)
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

############################################

$param = array('verbose'=>0, 'log'=>0, 'headers'=>0);

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

chdir(dirname($_SERVER['DOCUMENT_ROOT']));

$files = `find public_html/ -mtime -2 \\( -name "*.js" -or -name "*.css" \\)`;
$write = false;

foreach (explode("\n",$files) as $file) {
	if (empty($file))
		continue;

	$url = preg_replace('/^public_html/','',$file);
	print "checking revision for: $url";
	$data = `git info $file | grep "Commit ID"`;
	if (preg_match('/: (\w+)/',$data,$m)) {
		$rev = preg_replace('/[a-f]+/','',$m[1]);
		$rev = preg_replace('/^0+/','',$rev);
		$rev = substr($rev,0,8);
		if (empty($rev)) //unlikely to have nothing, but just in case!
			$rev = 111;

		print " :: $rev \n";
		$REVISIONS[$url] = $rev;
		$write = true;
	} else {
		print " :: unknown\n";
	}

}

if ($write) {
	print "Writing libs/conf/revisions.conf.php.test\n";

	//duplicated from geograph.pl
	$h = fopen('libs/conf/revisions.conf.php.test','w');
	fwrite($h, '<'.'?php'."\n");
	fwrite($h, '$REVISIONS = array();'."\n");
	foreach ($REVISIONS as $key => $value) {
		fwrite($h, "\$REVISIONS['$key']=$value;\n");
	}
	fwrite($h, '?'.'>');
	fclose($h);
}
