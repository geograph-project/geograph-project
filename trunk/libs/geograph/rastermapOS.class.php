<?php
/**
 * $Project: GeoGraph $
 * $Id: rastermapOS.class.php 2239 2006-05-21 23:29:08Z barryhunter $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005  Barry Hunter (geo@barryhunter.co.uk)
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


class RasterMapOS {

	function listTiles() {
		global $CONF;
		
		$limit = (!empty($_GET['limit']))?intval($_GET['limit']):5;
		$skip = (!empty($_GET['skip']))?intval($_GET['skip']):0;
		
	#	$CONF['os50ktilepath'].$ll.'/'.$tile.'.TIF';
		
		$root = $CONF['os50ktilepath'];
		$lldh = opendir($root);
		$c = 1;
		$cs = 0;
		while (($llfile = readdir($lldh)) !== false) {
			if (is_dir($root.$llfile) && strpos($llfile,'.') !== 0) {
				$folder = $llfile.'/';
				$tiledh = opendir($root.$folder);
						
				while (($tilefile = readdir($tiledh)) !== false) {
					if (is_file($root.$folder.$tilefile) && strpos($tilefile,'.TIF') !== FALSE) {
						$tile = str_replace(".TIF",'',$tilefile);
						if (($skip > 0) && ($cs < $skip)) {
							$cs++;
							print "skip $tile<BR>";
							continue;
						}
						print "TILE=$tile<BR>";
						$r = true;
						if ($_GET['processTile']) {
							$this->processTile($tile,100,100);
							$this->processTile($tile,300,100);
							$this->processTile($tile,300,300);
							$r = $this->processTile($tile,100,300);
						}
					
						if ($_GET['processSingleTile'])
							$r = $this->processSingleTile($tile,$_GET['processSingleTile']);
						
						if ($r)
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

$square->reference_index = 1; #if that x5x5 square is at sea then our detection fails!


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

		if (!$square->setByFullGridRef($gr)) {
			return false;
		}

$square->reference_index = 1; #if that x5x5 square is at sea then our detection fails!


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

			$cmd = sprintf ('%s"%smontage" -geometry +0+0 %s +page -crop %ldx%ld+%ld+%ld +repage -thumbnail %ldx%ld -colors 128 -font "%s" -fill "#eeeeff" -draw "roundRectangle 6,230 155,243 3,3" -fill "#000066" -pointsize 10 -draw "text 10,240 \'© Crown Copyright %s\'" png:%s', 
				isset($_GET['nice'])?'nice ':'',
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

$square->reference_index = 1; #if that x5x5 square is at sea then our detection fails!

		$ll = $square->gridsquare;
		
		$this->width = $width;

		#to get bottom left need to remove teh 'centering' 
		$this->nateastings = $square->getNatEastings() - 5000;
		$this->natnorthings = $square->getNatNorthings() - 5000;

		$path = $CONF['os50kimgpath'].$tile.'.png';
	
	
		$n = $this->natnorthings; 
		$e = $this->nateastings; 
		$newpath = $this->getOSGBStorePath('pngs-1k-'.$this->width.'/',$e,$n);
		if (file_exists($newpath) && empty($_GET['force'])) {
			print "already done processSingleTile($tile,$width)<br>";
			flush();
			return false;
		}
	
	
		if (strlen($CONF['imagemagick_path'])) {
		
#/usr/bin/convert tiff:/var/www/geograph_live/rastermaps/OS-50k/tiffs/SH/SH64.TIF -gravity SouthWest -crop 3600x3600+100+100 +repage -crop 400x400 +repage -thumbnail 250x250 -colors 128 -font /usr/share/fonts/truetype/freefont/FreeSans.ttf -fill "#eeeeff" -draw "roundRectangle 8,230 153,243 3,3" -fill "#000066" -pointsize 10 -draw "text 10,240 '© Crown Copyright 100045616'" png:/var/www/geograph_live/rastermaps/OS-50k/pngs-2k-250/27/34/SH64.png

			$cmd = sprintf ('%s"%sconvert" tiff:%s -gravity SouthWest +repage -crop %ldx%ld +repage -thumbnail %ldx%ld -colors 128 png:%s', 
				isset($_GET['nice'])?'nice ':'',
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
			flush();
			 
			$c = 0;
			if (isset($_GET['print']))
				print "<table cellspacing=0 cellpadding=0 border=1>";
			foreach(range(	$this->natnorthings+19000 ,
							$this->natnorthings ,
							-1000 ) as $n) {
				if (isset($_GET['print']))
					print "<tr>";
				foreach(range(	$this->nateastings ,
								$this->nateastings+19000 ,
								1000 ) as $e) {
					$oldpath = preg_replace("/\./","-$c.",$path);
					$newpath = $this->getOSGBStorePath('pngs-1k-'.$this->width.'/',$e,$n);
					
					if (!empty($_GET['force'])) 
						@unlink($newpath);
				
					rename($oldpath,$newpath);
					if (isset($_GET['print']))
						print "<td>$c =<br> <B>$e</B>,<br> $n</td>";
					print "$c ";flush();
					$c++;
				}
				if (isset($_GET['print']))
					print "</tr>";
			}
			if (isset($_GET['print']))
				print "</table>";
			print "done renaming in processSingleTile($tile);<BR>";
			flush();
		} else {
			//generate resized image
			die("gd not implemented!");
		}
		return true;
	}
	
//take a 20k tile and create 81 2km tiles	
	function processTile($tile,$offsetX,$offsetY) {
		global $CONF;
		$square=new GridSquare;

		$grid_ok=$square->setByFullGridRef($tile);

$square->reference_index = 1; #if that x5x5 square is at sea then our detection fails!

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
		if (file_exists($newpath) && empty($_GET['force'])) {
			print "already done processTile($tile,$offsetX,$offsetY)<br>";
			flush();
			return false;
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
			flush();
			
			$c = 0;
			if (isset($_GET['print']))
				print "<table cellspacing=0 cellpadding=0 border=1>";
			foreach(range(	$this->natnorthings+(8*2000)+$kmoffsetY ,
							$this->natnorthings+$kmoffsetY ,
							-2000 ) as $n) {
				if (isset($_GET['print']))
					print "<tr>";
				foreach(range(	$this->nateastings+$kmoffsetX ,
								$this->nateastings+(8*2000)+$kmoffsetX ,
								2000 ) as $e) {
					$oldpath = preg_replace("/\./","-$c.",$path);
					$newpath = $this->getOSGBStorePath('pngs-2k-250/',$e,$n);
				#	print "<hr/><pre>Rename $oldpath\nTo $newpath</pre>";
			
					if (!empty($_GET['force'])) 
						@unlink($newpath);
				
					rename($oldpath,$newpath);
					if (isset($_GET['print']))
						print "<td>$c =<br> <B>$e</B>,<br> $n</td>";
					print "$c ";flush();
					$c++;
				}
				if (isset($_GET['print']))
					print "</tr>";
			}
			if (isset($_GET['print']))
				print "</table>";
		} else {
			//generate resized image
			die("gd not implemented!");
		}
		return true;
	}

//create a single 2km tile out of a 20k tile (no longer working?)
	function fakeSetup($gr) {
		global $CONF;
		$square=new GridSquare;

		$grid_ok=$square->setByFullGridRef($gr);

$square->reference_index = 1; #if that x5x5 square is at sea then our detection fails!


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
?>