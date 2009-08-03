<?php
/**
 * $Project: GeoGraph $
 * $Id$
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2009  Barry Hunter (geo@barryhunter.co.uk)
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


class RasterMapOS250 extends RasterMapOS {
	var $source = "OS250k";

	var $TIFF_W = 4000; 
	var $TIFF_KMW = 100;
	var $TIFF_KMW_BY10 = 10; #TIFF_KMW / 10;
	var $TIFF_PX_PER_KM = 40; #TIFF_W / TIFF_KMW);
	
	function listTiles() {
		global $CONF;
		
		$limit = (!empty($_GET['limit']))?intval($_GET['limit']):5;
		$skip = (!empty($_GET['skip']))?intval($_GET['skip']):0;
		
	#	$CONF['os50kimgpath'].$CONF['os50kepoch'].'tiffs/'.$ll.'/'.$tile.'.TIF';
		
		$root = $CONF['rastermap'][$this->source]['path'].$CONF['rastermap'][$this->source]['epoch'].'tiffs/';
		$lldh = opendir($root);
		$c = 1;
		$cs = 0;
	
		$tiledh = opendir($root);

		while (($tilefile = readdir($tiledh)) !== false) {
			if (is_file($root.$tilefile) && stripos($tilefile,'.TIF') !== FALSE) {
				$tile = str_ireplace(".TIF",'',$tilefile);
				if (($skip > 0) && ($cs < $skip)) {
					$cs++;
					print "skip $tile<BR>";
					continue;
				}
				print "TILE=$tile<BR>";
				$r = true;
				if ($_GET['checkTiles']) {
					die("not implemented!");
				} else {
					if ($_GET['processTile']) {
						die("not implemented!");
					}

					if ($_GET['processSingleTile'])
						$r = $this->processSingleTile($tile,$_GET['processSingleTile']);
				}

				if ($r)
					$c++;
				if ($c > $limit) {
					print "<pre>Terminated<pre>";
					exit;
				}
			}
		}
		
	}
		
	
	//take large 100k and split into 10k tiles
	function processSingleTile($tile,$width = 250) {
		global $CONF;
		$square=new GridSquare;

		$grid_ok=$square->setByFullGridRef($tile);

		$square->reference_index = 1; #if that x5x5 square is at sea then our detection fails!

		$ll = $square->gridsquare;
		
		$this->width = $width;

		#to get bottom left need to remove teh 'centering' 
		$this->nateastings = $square->getNatEastings() - 50000;
		$this->natnorthings = $square->getNatNorthings() - 50000;

		$path = $CONF['rastermap'][$this->source]['path'].$CONF['rastermap'][$this->source]['epoch']."tmp/$tile-$width.png";
	
	
		$n = $this->natnorthings; 
		$e = $this->nateastings; 
		$newpath = $this->getOSGBStorePath('pngs-10k-'.$this->width.'/',$e,$n);
		if (file_exists($newpath) && empty($_GET['force'])) {
			print "already done processSingleTile($tile,$width)<br>\n";
			flush();
			return false;
		}
	
	
		if (strlen($CONF['imagemagick_path'])) {
		
#/usr/bin/convert tiff:/var/www/geograph_live/rastermaps/OS-50k/tiffs/SH/SH64.TIF -gravity SouthWest -crop 3600x3600+100+100 +repage -crop 400x400 +repage -thumbnail 250x250 -colors 128 -font /usr/share/fonts/truetype/freefont/FreeSans.ttf -fill "#eeeeff" -draw "roundRectangle 8,230 153,243 3,3" -fill "#000066" -pointsize 10 -draw "text 10,240 '© Crown Copyright 100045616'" png:/var/www/geograph_live/rastermaps/OS-50k/pngs-2k-250/27/34/SH64.png

			$cmd = sprintf ('%s"%sconvert" tiff:%s -gravity SouthWest +repage -crop %ldx%ld +repage -thumbnail %ldx%ld -colors 128 png:%s', 
				isset($_GET['nice'])?'nice ':'',
				$CONF['imagemagick_path'],
				$this->getOSGBTilePath($ll,$tile),
				$this->TIFF_PX_PER_KM*10, $this->TIFF_PX_PER_KM*10, 
				$this->width, $this->width, 
				$path    );
				
			if (isset($_ENV["OS"]) && strpos($_ENV["OS"],'Windows') !== FALSE) 
				$cmd = str_replace('/','\\',$cmd);
			print "<pre>$cmd</pre>\n";
			flush();
			if (isset($_GET['run']))
				passthru ($cmd);
			 
			$c = 0;
			if (isset($_GET['print']))
				print "<table cellspacing=0 cellpadding=0 border=1>";
			foreach(range(	$this->natnorthings+90000 ,
							$this->natnorthings ,
							-10000 ) as $n) {
				if (isset($_GET['print']))
					print "<tr>";
				foreach(range(	$this->nateastings ,
								$this->nateastings+90000 ,
								10000 ) as $e) {
					$oldpath = preg_replace("/\./","-$c.",$path);
					$newpath = $this->getOSGBStorePath('pngs-10k-'.$this->width.'/',$e,$n);
					
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
			print "\ndone renaming in processSingleTile($tile); <BR>\n";
			flush();
		} else {
			//generate resized image
			die("gd not implemented!");
		}
		return true;
	
	}

	function getOSGBTilePath($ll,$tile) {
		global $CONF;
		return $CONF['rastermap'][$this->source]['path'].$CONF['rastermap'][$this->source]['epoch'].'tiffs/'.$tile.'.tif';
	}

	function getOSGBStorePath($folder = 'pngs-2k-250/',$e = 0,$n = 0) {
		global $CONF;

		if ($e && $n) {
			$e2 = floor($e /100000);
			$n2 = floor($n /100000);
			$e3 = floor($e /10000);
			$n3 = floor($n /10000);
		} else {
			$e2 = floor($this->nateastings /100000);
			$n2 = floor($this->natnorthings /100000);
			$e3 = floor($this->nateastings /10000);
			$n3 = floor($this->natnorthings /10000);
		}

		$dir=$CONF['rastermap'][$this->source]['path'].$CONF['rastermap'][$this->source]['epoch'].$folder;
		
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