<?php
/**
 * $Project: GeoGraph $
 * $Id: imagemap.php 1690 2005-12-22 15:05:42Z barryhunter $
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

require_once('geograph/global.inc.php');
require_once('geograph/gridsquare.class.php');
require_once('geograph/gridimage.class.php');
require_once('geograph/map.class.php');
require_once('geograph/mapmosaic.class.php');
require_once('geograph/image.inc.php');
init_session();

$gr = "SH7042";
$tile = "SH64";

		define('TIFF_W',4000); 
		define('TIFF_KMW',20);
		define('TIFF_KMW_BY10',TIFF_KMW / 10);
		define('TIFF_PX_PER_KM',TIFF_W / TIFF_KMW);

$USER->mustHavePerm("admin");

$m = new RasterMapOS();


if ($_GET['listTiles']) {
	$m->listTiles();
	
	print "DDONE";
	exit;
}

if ($_GET['fakeSetup'])
	$m->fakeSetup($gr);

if ($_GET['processTile1'])
	$m->processTile($tile,100,100);
if ($_GET['processTile3'])
	$m->processTile($tile,300,300);
if ($_GET['processTile']) {
	$m->processTile($tile,100,100);
	$m->processTile($tile,300,100);
	$m->processTile($tile,300,300);
	$m->processTile($tile,100,300);
}

if ($_GET['testTable'])
	$m->testTable($tile);

if ($_GET['processSingleTile'])
	$m->processSingleTile($tile);

if ($_GET['processSingleTile2'])
	$m->processSingleTile($tile,200);


if ($_GET['combineTiles'])
	$m->combineTiles($gr);


class RasterMapOS {

	function listTiles() {
		global $CONF;
		
		$limit = (!empty($_GET['limit']))?intval($_GET['limit']):5;
		
	#	$CONF['os50ktilepath'].$ll.'/'.$tile.'.TIF';
		
		$root = $CONF['os50ktilepath'];
		$lldh = opendir($root);
		$c = 1;
		while (($llfile = readdir($lldh)) !== false) {
			if (is_dir($root.$llfile) && strpos($llfile,'.') !== 0) {
				$folder = $llfile.'/';
				$tiledh = opendir($root.$folder);
						
				while (($tilefile = readdir($tiledh)) !== false) {
					if (is_file($root.$folder.$tilefile) && strpos($tilefile,'.TIF') !== FALSE) {
						$tile = str_replace(".TIF",'',$tilefile);
						print "TILE=$tile<BR>";
					
						if ($_GET['processTile']) {
							$this->processTile($tile,100,100);
							$this->processTile($tile,300,100);
							$this->processTile($tile,300,300);
							$this->processTile($tile,100,300);
						}
					
						if ($_GET['processSingleTile'])
							$this->processSingleTile($tile);
						
						$c++;
						if ($c > $limit) {
							print "<pre>Terminated<pre>";
							exit;
						}
					}
				}
			}
		}
	}


//display some tiles...
	function testTable($tile) {
		global $CONF;
		$square=new GridSquare;

		$grid_ok=$square->setByFullGridRef($tile);

		$ll = $square->gridsquare;
		
		$this->width = 250;

		#to get bottom left need to remove teh 'centering' 
		$this->nateastings = $square->getNatEastings() - 5000;
		$this->natnorthings = $square->getNatNorthings() - 5000;
		
		$CONF['os50kimgpath'] = "http://geograph.local/testtiles/";
		
		$kmoffset = ($offset==100)?1000:2000; 
		$c = 0;
		print "<table cellspacing=0 cellpadding=0 border=0 bordercolor=blue>";
		foreach(range(	$this->natnorthings+(8*2000)+$kmoffset ,
						$this->natnorthings+$kmoffset ,
						-1000 ) as $n) {
			print "<tr>";
			foreach(range(	$this->nateastings+$kmoffset ,
							$this->nateastings+(8*2000)+$kmoffset ,
							1000 ) as $e) {
				
				$newpath = $this->getOSGBStorePath('pngs-1k-200/',$e,$n);

				print "<td><img src='$newpath'></td>";
				$c++;
			}
			print "</tr>";
		}
		print "</table>";
		
	}

//take number of 1km tiles and create a 2km tile
	function combineTiles($gr) {
		global $CONF;
		$square=new GridSquare;

		$grid_ok=$square->setByFullGridRef($gr);

		$ll = $square->gridsquare;
		
		$this->width = 250;

		$this->nateastings = $square->getNatEastings()-500;
		$this->natnorthings = $square->getNatNorthings()-500;

		$path = $CONF['os50kimgpath'].$gr.'.png';
	
		if (strlen($CONF['imagemagick_path'])) {
			$tilelist = array();
			$c = 0;
			print "<table cellspacing=0 cellpadding=0 border=1>";
			foreach(range(	$this->natnorthings+1000 ,
							$this->natnorthings-1000 ,
							-1000 ) as $n) {
				print "<tr>";
				foreach(range(	$this->nateastings-1000 ,
								$this->nateastings+1000 ,
								1000 ) as $e) {
					$newpath = $this->getOSGBStorePath('pngs-1k-125/',$e,$n);
					//todo
					//if (exists) {
						$tilelist[] = $newpath;
					//} else {
					//	use empty tile
					//}
					print "<td>$c =<br> <B>$e</B>,<br> $n</td>";
					$c++;
				}
				print "</tr>";
			}
			print "</table>";	

			$path = $this->getOSGBStorePath('pngs-2k-250/');

			$cmd = sprintf ('"%smontage" -geometry +0+0 %s +page -crop %ldx%ld+%ld+%ld +repage -thumbnail %ldx%ld -colors 128 -font "%s" -fill "#eeeeff" -draw "roundRectangle 6,230 155,243 3,3" -fill "#000066" -pointsize 10 -draw "text 10,240 \'© Crown Copyright %s\'" png:%s', 
				$CONF['imagemagick_path'],
				implode(' ',$tilelist),
			#	$this->width*1.5, $this->width*1.5, 
				$this->width, $this->width, 
				$this->width/2, $this->width/2,
				$this->width, $this->width, 
				$CONF['imagemagick_font'],
				$CONF['OS_licence'],
				$path    );
				
			if (isset($_ENV["OS"]) && strpos($_ENV["OS"],'Windows') !== FALSE) 
				$cmd = str_replace('/','\\',$cmd);
			if (isset($_GET['run']))
				passthru ($cmd);
			print "<pre>$cmd</pre>";
			 

		} else {
			//generate resized image
			die("gd not implemented!");
		}
	}
	

	
//take large 20k and split into 1k tiles
	function processSingleTile($tile,$width = 125) {
		global $CONF;
		$square=new GridSquare;

		$grid_ok=$square->setByFullGridRef($tile);

		$ll = $square->gridsquare;
		
		$this->width = $width;

		#to get bottom left need to remove teh 'centering' 
		$this->nateastings = $square->getNatEastings() - 5000;
		$this->natnorthings = $square->getNatNorthings() - 5000;

		$path = $CONF['os50kimgpath'].$tile.'.png';
	
		if (strlen($CONF['imagemagick_path'])) {
		
#/usr/bin/convert tiff:/var/www/geograph_live/rastermaps/OS-50k/tiffs/SH/SH64.TIF -gravity SouthWest -crop 3600x3600+100+100 +repage -crop 400x400 +repage -thumbnail 250x250 -colors 128 -font /usr/share/fonts/truetype/freefont/FreeSans.ttf -fill "#eeeeff" -draw "roundRectangle 8,230 153,243 3,3" -fill "#000066" -pointsize 10 -draw "text 10,240 '© Crown Copyright 100045616'" png:/var/www/geograph_live/rastermaps/OS-50k/pngs-2k-250/27/34/SH64.png

			$cmd = sprintf ('"%sconvert" tiff:%s -gravity SouthWest +repage -crop %ldx%ld +repage -thumbnail %ldx%ld -colors 128 png:%s', 
				$CONF['imagemagick_path'],
				$this->getOSGBTilePath($ll,$tile),
				TIFF_PX_PER_KM, TIFF_PX_PER_KM, 
				$this->width, $this->width, 
				$path    );
				
			if (isset($_ENV["OS"]) && strpos($_ENV["OS"],'Windows') !== FALSE) 
				$cmd = str_replace('/','\\',$cmd);
			if (isset($_GET['run']))
				passthru ($cmd);
			print "<pre>$cmd</pre>";
			 
			$c = 0;
			print "<table cellspacing=0 cellpadding=0 border=1>";
			foreach(range(	$this->natnorthings+19000 ,
							$this->natnorthings ,
							-1000 ) as $n) {
				print "<tr>";
				foreach(range(	$this->nateastings ,
								$this->nateastings+19000 ,
								1000 ) as $e) {
					$oldpath = preg_replace("/\./","-$c.",$path);
					$newpath = $this->getOSGBStorePath('pngs-1k-'.$this->width.'/',$e,$n);
					rename($oldpath,$newpath);
					print "<td>$c =<br> <B>$e</B>,<br> $n</td>";
					$c++;
				}
				print "</tr>";
			}
			print "</table>";
		} else {
			//generate resized image
			die("gd not implemented!");
		}
	}
	
//take a 20k tile and create 81 2km tiles	
	function processTile($tile,$offsetX,$offsetY) {
		global $CONF;
		$square=new GridSquare;

		$grid_ok=$square->setByFullGridRef($tile);

		$ll = $square->gridsquare;
		
		$this->width = 250;

		#to get bottom left need to remove teh 'centering' 
		$this->nateastings = $square->getNatEastings() - 5000;
		$this->natnorthings = $square->getNatNorthings() - 5000;

		$path = $CONF['os50kimgpath'].$tile.'.png';
	
		$kmoffsetX = ($offsetX==100)?1000:2000; 
		$kmoffsetY = ($offsetY==100)?1000:2000; 
	
		$n = $this->natnorthings+$kmoffsetY; 
		$e = $this->nateastings+$kmoffsetX; 
		$newpath = $this->getOSGBStorePath('pngs-2k-250/',$e,$n);
		if (file_exists($newpath)) {
			print "already done processTile($tile,$offsetX,$offsetY)<br>";
			return;
		}
	
		if (strlen($CONF['imagemagick_path'])) {
		
#/usr/bin/convert tiff:/var/www/geograph_live/rastermaps/OS-50k/tiffs/SH/SH64.TIF -gravity SouthWest -crop 3600x3600+100+100 +repage -crop 400x400 +repage -thumbnail 250x250 -colors 128 -font /usr/share/fonts/truetype/freefont/FreeSans.ttf -fill "#eeeeff" -draw "roundRectangle 8,230 153,243 3,3" -fill "#000066" -pointsize 10 -draw "text 10,240 '© Crown Copyright 100045616'" png:/var/www/geograph_live/rastermaps/OS-50k/pngs-2k-250/27/34/SH64.png

			$cmd = sprintf ('"%sconvert" tiff:%s -gravity SouthWest -crop %ldx%ld+%ld+%ld +repage -crop %ldx%ld +repage -thumbnail %ldx%ld -colors 128 -font "%s" -fill "#eeeeff" -draw "roundRectangle 6,230 155,243 3,3" -fill "#000066" -pointsize 10 -draw "text 10,240 \'© Crown Copyright %s\'" png:%s', 
				$CONF['imagemagick_path'],
				$this->getOSGBTilePath($ll,$tile),
				TIFF_W*0.9, TIFF_W*0.9, 
				$offsetX, $offsetY, 
				TIFF_PX_PER_KM*2, TIFF_PX_PER_KM*2, 
				$this->width, $this->width, 
				$CONF['imagemagick_font'],
				$CONF['OS_licence'],
				$path    );
			if (isset($_ENV["OS"]) && strpos($_ENV["OS"],'Windows') !== FALSE) 
				$cmd = str_replace('/','\\',$cmd);
			if (isset($_GET['run']))
				passthru ($cmd);
			print "<pre>$cmd</pre>";
			
			$c = 0;
			print "<table cellspacing=0 cellpadding=0 border=1>";
			foreach(range(	$this->natnorthings+(8*2000)+$kmoffsetY ,
							$this->natnorthings+$kmoffsetY ,
							-2000 ) as $n) {
				print "<tr>";
				foreach(range(	$this->nateastings+$kmoffsetX ,
								$this->nateastings+(8*2000)+$kmoffsetX ,
								2000 ) as $e) {
					$oldpath = preg_replace("/\./","-$c.",$path);
					$newpath = $this->getOSGBStorePath('pngs-2k-250/',$e,$n);
				#	print "<hr/><pre>Rename $oldpath\nTo $newpath</pre>";
				#	rename($newpath,$oldpath);
					rename($oldpath,$newpath);
					print "<td>$c =<br> <B>$e</B>,<br> $n</td>";
					$c++;
				}
				print "</tr>";
			}
			print "</table>";
		} else {
			//generate resized image
			die("gd not implemented!");
		}
	}

//create a single 2km tile out of a 20k tile (no longer working?)
	function fakeSetup($gr) {
		global $CONF;
		$square=new GridSquare;

		$grid_ok=$square->setByFullGridRef($gr);

		$gr = $square->grid_reference;
		$ll = $square->gridsquare;
		$le = $square->eastings;
		$ln = $square->northings;


		$this->nateastings = $square->getNatEastings();
		$this->natnorthings = $square->getNatNorthings();
#	}

#	function writeOSGBStore() {
		$this->width = 300;


		$te = floor($le / TIFF_KMW) * TIFF_KMW_BY10;
		$tn = floor($ln / TIFF_KMW) * TIFF_KMW_BY10;

		$tile = sprintf("%s%01d%01d",$ll,$te,$tn);

		$oe = $le - $te * 10;
		$on = $ln - $tn * 10;

		$pe = $oe * TIFF_PX_PER_KM;
		$pn = $on * TIFF_PX_PER_KM;

		foreach (explode(' ','ll le ln e n te tn tile path out oe on pe pn') as $k){print "$k = '".$$k."'<br>";}

		if (strlen($CONF['imagemagick_path'])) {
		
#/usr/bin/convert tiff:/var/www/geograph_live/rastermaps/OS-50k/tiffs/SH/SH64.TIF -gravity SouthWest -crop 3600x3600+100+100 +repage -crop 400x400 +repage -thumbnail 250x250 -colors 128 -font /usr/share/fonts/truetype/freefont/FreeSans.ttf -fill "#eeeeff" -draw "roundRectangle 8,230 153,243 3,3" -fill "#000066" -pointsize 10 -draw "text 10,240 '© Crown Copyright 100045616'" png:/var/www/geograph_live/rastermaps/OS-50k/pngs-2k-350/27/34/SH64.png

			$cmd = sprintf ("\"%sconvert\" -gravity SouthWest -crop %ldx%ld+%ld+%ld -resize %ldx%ld tiff:%s png:%s", 
				str_replace('"','',$CONF['imagemagick_path']),
				TIFF_PX_PER_KM*2, TIFF_PX_PER_KM*2, 
				$pe - TIFF_PX_PER_KM/2, $pn - TIFF_PX_PER_KM/2, 
				$this->width, $this->width, 
				$this->getOSGBTilePath($ll,$tile),
				$this->getOSGBStorePath('pngs-2k-'.$this->width.'/'));
				
			if (isset($_ENV["OS"]) && strpos($_ENV["OS"],'Windows') !== FALSE) 
				$cmd = str_replace('/','\\',$cmd);
			if (isset($_GET['run']))
				passthru ($cmd);
			print "<pre>$cmd</pre>";
		} else {
			//generate resized image

			die("gd not implemented!");
		}
	}

	function getOSGBTilePath($ll,$tile) {
		global $CONF;
		return $CONF['os50ktilepath'].$ll.'/'.$tile.'.TIF';
	}

	function getOSGBStorePath($folder = 'pngs-2k-250/',$e = 0,$n = 0) {
		global $CONF;

		if ($e && $n) {
			$e2 = floor($e /10000);
			$n2 = floor($n /10000);
			$e3 = floor($e /1000);
			$n3 = floor($n /1000);
		} else {
			$e2 = floor($this->nateastings /10000);
			$n2 = floor($this->natnorthings /10000);
			$e3 = floor($this->nateastings /1000);
			$n3 = floor($this->natnorthings /1000);
		}

		$dir=$CONF['os50kimgpath'].$folder;
		
		$dir.=$e2.'/';
		if (!is_dir($dir))
			mkdir($dir);

		$dir.=$n2.'/';
		if (!is_dir($dir))
			mkdir($dir);

		return $dir.$e3.'-'.$n3.'.png';
	}
}
	
?>Done
