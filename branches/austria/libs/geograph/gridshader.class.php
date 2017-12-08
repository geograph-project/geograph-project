<?php
/**
 * $Project: GeoGraph $
 * $Id$
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Paul Dixon (paul@elphin.com)
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



/**
* Provides the GridShader class
*
* @package Geograph
* @author Paul Dixon <paul@elphin.com>
* @version $Revision$
*/


/**
* Grid shader class
*
* Can process a PNG image of 1km pixels and create/update database grid squares
* @package Geograph
*/
class GridShader
{
	var $db=null;
	
	/**
	* internal function returns the grid reference by looking for
	* suitable gridsquares in the gridprefix table.
	*/
	function _getGridRef($x, $y, $reference_index)
	{
		$gridref="";
		
		//find all grid boxes in which the coordinate falls
		$sql="select prefix,origin_x,origin_y from gridprefix where ".
			"$x between origin_x and (origin_x+width-1) and ".
			"$y between origin_y and (origin_y+height-1) and ".
			"reference_index=$reference_index";
		
		$recordSet = $this->db->Execute($sql);
		if (!$recordSet->EOF) 
		{
			$gridref=sprintf("%s%02d%02d", 
				$recordSet->fields[0],
				$x-$recordSet->fields[1],
				$y-$recordSet->fields[2]);
			
		}
		$recordSet->Close(); 
		
		return $gridref;
	}
	
	/**
	* adds or updates squares
	*/
	function process($imgfile, $x_offset, $y_offset, $reference_index, $clearexisting,$updategridprefix = true,$expiremaps=true,$ignore100=false,$dryrun=false,
	                 $minx=0,$maxx=100000,$miny=0,$maxy=100000,
	                 $setpercland=true,$level=0,$cid=0,$limpland=false,$createsquares=false,$calcpercland=false)
	{
		#FIXME gridbuilder.php: upload image file (and delete it after processing!)
		if ($minx > $maxx || $miny > $maxy) {
			$this->_err("invalid x/y range");
			return;
		}
		if (file_exists($imgfile))
		{
			#tried to test for 8 bit grayscale without alpha channel... does not work anyway.
			#$imginfo = getimagesize($imgfile);
			#if ($imginfo[0] > 0 && $imginfo[1] > 0 && $imginfo[2] == IMAGETYPE_PNG && $imginfo["channels"] == 1 && $imginfo["bits"] == 8)
				$img=imagecreatefrompng($imgfile);
			#else
			#	$img=false;
			if ($img)
			{
				$this->db = GeographDatabaseConnection();
				if (!$this->db) die('Database connection failed');   

				$imgw=imagesx($img);
				$imgh=imagesy($img);
				$startx = max(0,$minx);
				$endx   = min($imgw-1,$maxx);
				$starty = ($imgh-1)-min($imgh-1,$maxy);
				$endy   = ($imgh-1)-max(0,$miny);

				$this->_trace("Image is {$imgw}km x {$imgh}km");
				if ($dryrun) $this->_trace("DRY RUN!");

				require_once('geograph/gridsquare.class.php');
				$gsquare=new GridSquare;
				$valid=0;
				$invalid=0;
				$vminx=0;
				$vmaxx=0;
				$vminy=0;
				$vmaxy=0;
				$invalx=0;
				$invaly=0;
				$created=0;
				$updated=0;
				$untouched=0;
				$skipped=0;
				$zeroed=0;
				$skipped100=0;
				$createdsquares=0;
				$recalcsquares=0;
				
				$lastpercent=-1;
				for ($imgy=$starty; $imgy<=$endy; $imgy++)
				{
					//output some progress
					$percent=round(100*($imgy-$starty)/($endy-$starty));
					$percent=round($percent/5)*5;
					if ($percent!=$lastpercent)
					{
						$this->_trace("{$percent}% completed...");
						$lastpercent=$percent;
					}
					
					for ($imgx=$startx; $imgx<=$endx; $imgx++)
					{
						//get colour of pixel 
						//255=white, 0% land
						//000=black, 100% land
						$colind=imagecolorat($img, $imgx, $imgy);
						$colarr=imagecolorsforindex($img, $colind); // works also if alpha channel present + for rgb / palette images
						$col=$colarr['green'];
						$percent_land=round(((255-$col)*100)/255);
						
						if ($ignore100 && ($percent_land == 100))
						{
							$skipped100++;
							continue;
						}
						
						
						//now lets figure out the internal grid ref
						$gridx=$x_offset + $imgx;
						$gridy=$y_offset + ($imgh-$imgy-1);
						
						$gridref=$this->_getGridRef($gridx,$gridy,$reference_index);
						$ok = !empty($gridref);
						if ($ok && !$setpercland) {
							#$ok = $gsquare->loadFromPosition($gridx,$gridy);
							#$ok = $gsquare->setGridRef($gridref,true);
							$gsquare = $this->db->GetRow("select gridsquare_id,percent_land from gridsquare where x='$gridx' and y='$gridy'");
							$ok = is_array($gsquare) && count($gsquare);
							if (!$ok && $createsquares && $percent_land != 0) {
								if (!$dryrun) {
									$sql="insert into gridsquare (grid_reference,reference_index,x,y,percent_land,point_xy) ".
										"values('{$gridref}','{$reference_index}',$gridx,$gridy,0,GeomFromText('POINT($gridx $gridy)'))";
									$this->db->Execute($sql);
									$gsquare = $this->db->GetRow("select gridsquare_id,percent_land from gridsquare where x='$gridx' and y='$gridy'");
								} else {
									$gsquare = array('percent_land'=>0,'gridsquare_id'=>null);
								}
								$ok = is_array($gsquare) && count($gsquare);
								$createdsquares++;
							}
							$needrecalc = $ok && $calcpercland && ($percent_land != 0 || $gsquare['percent_land'] != 0);
							#if ($ok && $limpland && $percent_land > $gsquare->percent_land)
							#	$percent_land = $gsquare->percent_land;
							if ($ok && $limpland && $percent_land > $gsquare['percent_land'])
								$percent_land = $gsquare['percent_land'];
						} else {
							$needrecalc = false;
						}

						if (!$ok) {
							if ($clearexisting || ($percent_land!=0)) {
								if ($invalid == 0) {
									$invalx = $gridx;
									$invaly = $gridy;
								}
								$invalid++;
							}
						} else {
							if ($valid == 0 || $gridx > $vmaxx) $vmaxx = $gridx;
							if ($valid == 0 || $gridy > $vmaxy) $vmaxy = $gridy;
							if ($valid == 0 || $gridx < $vminx) $vminx = $gridx;
							if ($valid == 0 || $gridy < $vminy) $vminy = $gridy;
							$valid++;

							//$this->_trace("img($imgx,$imgy) = grid($gridx,$gridy) $percent_land%");
							
							//ok, that's everything we need - can we obtain an existing grid square
							if ($setpercland) {
								$square = $this->db->GetRow("select gridsquare_id,percent_land from gridsquare where x='$gridx' and y='$gridy'");	
							} elseif (!is_null($gsquare['gridsquare_id'])) {

								$square = $this->db->GetRow("select gridsquare_id,percent as percent_land from gridsquare_percentage ".
								                            "where gridsquare_id='{$gsquare['gridsquare_id']}' ".
								                            #"where gridsquare_id='{$gsquare->gridsquare_id}' ".
								                            "and level='$level' and community_id='$cid'");
								#$square = $this->db->GetRow("select gridsquare_id,percent as percent_land from gridsquare ".
								#                            "inner join gridsquare_percentage using gridsquare_id ".
								#                            "where x='$gridx' and y='$gridy' ".
								#                            "and level='$level' and community_id='$cid'");
							} else {
								# square does not exist and we did not create one (dryrun)
								$square = array();
							}
						##no need to check this as this is the first import (and its rather expensive!)	
							
							if (is_array($square) && count($square))
							{
								if (($square['percent_land']!=$percent_land) &&
								    ($clearexisting || ($percent_land!=0)))
								{
									if ($setpercland)
										$sql="update gridsquare set grid_reference='{$gridref}', ".
											"reference_index='$reference_index', ".
											"percent_land='$percent_land' ".
											"where gridsquare_id={$square['gridsquare_id']}";
									else
										$sql="update gridsquare_percentage set percent='$percent_land' ".
										     "where gridsquare_id='{$gsquare['gridsquare_id']}' ".
										     #"where gridsquare_id='{$gsquare->gridsquare_id}' ".
										     "and level='$level' and community_id='$cid'";
									if (!$dryrun) $this->db->Execute($sql);
									if ($percent_land==0)
										$zeroed++;
									else
										$updated++;
								}
								else
								{
									$untouched++;
								}
							}
							else
							{
								//we only create squares for land
								if ($percent_land>0)
								{
									if ($setpercland)
										$sql="insert into gridsquare (grid_reference,reference_index,x,y,percent_land,point_xy) ".
											"values('{$gridref}','{$reference_index}',$gridx,$gridy,$percent_land,GeomFromText('POINT($gridx $gridy)'))";
									else
										$sql="insert into gridsquare_percentage (gridsquare_id,level,community_id,percent) ".
											"values('{$gsquare['gridsquare_id']}','$level','$cid','$percent_land')";
											#"values('{$gsquare->gridsquare_id}','$level','$cid','$percent_land'))";
									if (!$dryrun) $this->db->Execute($sql);

									$created++;
								}
								else
								{
									$skipped++;
								}
							}
							if ($needrecalc) {
								$recalcsquares++;
								if (!$dryrun) {
									$sql = "select ".
										"greatest(round(0.5*coalesce(gp1.percent,0)+0.5*coalesce(gp2.percent,0))-coalesce(gp4.percent,0),coalesce(gp1.percent,0)>0) as percent_land, ".
										"coalesce(gp1.percent,0)>0 as permit_photographs, ".
										"coalesce(gp1.percent,0)-coalesce(gp3.percent,0)>0 as permit_geographs ".
										"from gridsquare gs ".
										"left join gridsquare_percentage gp1 on (gs.gridsquare_id=gp1.gridsquare_id and gp1.level = -1 and gp1.community_id = 1) ".
										"left join gridsquare_percentage gp2 on (gs.gridsquare_id=gp2.gridsquare_id and gp1.level = -1 and gp2.community_id = 2) ".
										"left join gridsquare_percentage gp3 on (gs.gridsquare_id=gp3.gridsquare_id and gp1.level = -1 and gp3.community_id = 3) ".
										"left join gridsquare_percentage gp4 on (gs.gridsquare_id=gp4.gridsquare_id and gp1.level = -1 and gp4.community_id = 4) ".
										"where gs.gridsquare_id = {$gsquare['gridsquare_id']} limit 1";
									$newvalues = $this->db->GetRow($sql);
									$this->db->Execute("update gridsquare set ".
										"percent_land='{$newvalues['percent_land']}',".
										"permit_photographs='{$newvalues['permit_photographs']}',".
										"permit_geographs='{$newvalues['permit_geographs']}' ".
										"where gridsquare_id='{$gsquare['gridsquare_id']}'");
								}
							}
						}
					}
				}
				imagedestroy($img);
				
				if ($updategridprefix && $setpercland) {
					$this->_trace("Setting land flags for gridprefixes");
					$prefixes = $this->db->GetAll("select * from gridprefix");	
					foreach($prefixes as $idx=>$prefix)
					{

						$minx=$prefix['origin_x'];
						$maxx=$prefix['origin_x']+$prefix['width']-1;
						$miny=$prefix['origin_y'];
						$maxy=$prefix['origin_y']+$prefix['height']-1;


						$count=$this->db->GetOne("select count(*) from gridsquare where ".
							"x between $minx and $maxx and ".
							"y between $miny and $maxy and ".
							"reference_index={$prefix['reference_index']} and ".
							"percent_land>0");

						//$this->_trace("{$prefix['prefix']} $minx,$miny to $maxx,$maxy has $count");

						if (!$dryrun) $this->db->query("update gridprefix set landcount=$count where ".
							"reference_index={$prefix['reference_index']} and ".
							"prefix='{$prefix['prefix']}'");
					}
				}
				
				$this->_trace("$created new squares created");
				$this->_trace("$updated squares updated with new land percentage");
				$this->_trace("$zeroed squares set to zero");

				if (!$setpercland && $createsquares)
					$this->_trace("$createdsquares new gridsquares created");
				if (!$setpercland && $recalcsquares)
					$this->_trace("$recalcsquares squares were recalculated");
				
				if ($ignore100)
					$this->_trace("$skipped100 squares ignored because at 100% in source");
				
				$this->_trace("$untouched squares examined but left untouched");
				$this->_trace("$skipped squares were all water and not created");
				if ($valid == 0) {
					$this->_trace("0 valid pixels");
				} else {
					$this->_trace("$valid valid pixels: $vminx, $vminy  ...  $vmaxx, $vmaxy");
				}
				if ($invalid == 0) {
					$this->_trace("0 invalid pixels");
				} else {
					$this->_trace("$invalid invalid pixels; first: $invalx, $invaly");
				}

				
				
				if ($expiremaps) {
					$deleted = 0;
					$checked = 0;
					$root=&$_SERVER['DOCUMENT_ROOT'];
				
					$lastpercent=-1;
					for ($imgy=$starty; $imgy<=$endy; $imgy+=2)
					{
						//output some progress
						$percent=round(($imgy*100)/$imgh);
						$percent=round($percent/5)*5;
						if ($percent!=$lastpercent)
						{
							$this->_trace("{$percent}% completed...");
							$lastpercent=$percent;
						}
					
						for ($imgx=$startx; $imgx<=$endx; $imgx+=2)
						{
				
							//now lets figure out the internal grid ref
							$gridx=$x_offset + $imgx;
							$gridy=$y_offset + ($imgh-$imgy-1);

							$xycrit = "mercator='0' and '$gridx' between map_x and max_x and '$gridy' between map_y and max_y";
							$sql = "select gxlow,gylow,gxhigh,gyhigh from gridsquare gs inner join gridsquare_gmcache gm using (gridsquare_id) where x='$gridx' and y='$gridy' limit 1";
							$mercator = $this->db->GetRow($sql);
							$havemercator = $mercator !== false && count($mercator);
							if ($havemercator) {
								$MCscale = 524288/(2*6378137.*M_PI);
								$xMC_min = floor($mercator['gxlow'] * $MCscale);
								$yMC_min = floor($mercator['gylow'] * $MCscale);
								$xMC_max = ceil ($mercator['gxhigh'] * $MCscale);
								$yMC_max = ceil ($mercator['gyhigh'] * $MCscale);
								$xycrit .= " or mercator='1' and '$xMC_min'<=max_x and '$xMC_max'>=map_x and '$yMC_min'<=max_y and '$yMC_max'>=map_y";
							}
							$sql="select * from mapcache where $xycrit";
						
							
							$recordSet = &$this->db->Execute($sql);
							while (!$recordSet->EOF) 
							{

								$file = $this->getBaseMapFilename($recordSet->fields);
								if (file_exists($root.$file)) {
									if (!$dryrun) unlink($root.$file);
									$deleted++;
								} 
								$file = $this->getLabelMapFilename($recordSet->fields, false, true);
								if (file_exists($root.$file)) {
									if (!$dryrun) unlink($root.$file);
									$deleted++;
								} 
								$file = $this->getLabelMapFilename($recordSet->fields, false, false);
								if (file_exists($root.$file)) {
									if (!$dryrun) unlink($root.$file);
									$deleted++;
								} 
								$file = $this->getLabelMapFilename($recordSet->fields, true, false);
								if (file_exists($root.$file)) {
									if (!$dryrun) unlink($root.$file);
									$deleted++;
								} 
								$checked++;
								$recordSet->MoveNext();
							}
							$recordSet->Close();

							$sql="update mapcache set age=age+1 where $xycrit";
							if (!$dryrun) $this->db->Execute($sql);
						}
				
					}
					$this->_trace("$checked tiles checked (tiles checked multiple times)");
					$this->_trace("$deleted tiles deleted");

				}
				
			}
			else
			{
				$this->_err("$imgfile is not a valid PNG"); 
			}
		}
		else
		{
				$this->_err("$imgfile doesn't exist"); 
		}
	}

	function getBaseMapFilename($row) # FIXME map.class.php?
	{
		$dir="/maps/base/";
		
		if (empty($row['mercator'])) {
			$map_x = $row['map_x'];
			$map_y = $row['map_y'];
			$ext = 'gd';
		} else {
			$map_x = $row['tile_x'];
			$map_y = $row['tile_y'];
			$ext = 'png';
		}

		$dir.="{$map_x}/";
		
		$dir.="{$map_y}/";

		$param = "";
		//FIXME palette?
		if (!empty($row['force_ri'])) {
			$param .= "_i{$row['force_ri']}";
		}
		
		if (empty($row['mercator'])) {
			$scale = $row['pixels_per_km'];
		} else {
			$scale = $row['level'];
			$param .= "_m";
		}

		$file="base_{$map_x}_{$map_y}_{$row['image_w']}_{$row['image_h']}_{$scale}$param.$ext";
		
		return $dir.$file;
	}
	function getLabelMapFilename($row, $towns, $regions) # FIXME map.class.php?
	{
		$dir="/maps/label/";

		if (empty($row['mercator'])) {
			$map_x = $row['map_x'];
			$map_y = $row['map_y'];
		} else {
			$map_x = $row['tile_x'];
			$map_y = $row['tile_y'];
		}

		$dir.="{$map_x}/";
		
		$dir.="{$map_y}/";

		$param = "";
		//FIXME palette?
		if (!empty($row['force_ri'])) {
			$param .= "_i{$row['force_ri']}";
		}
		if ($towns) {
			$param .= "_t";
		}
		if ($regions) {
			$param .= "_r";
		}

		if (empty($row['mercator'])) {
			$scale = $row['pixels_per_km'];
		} else {
			$scale = $row['level'];
			$param .= "_m";
			if (!empty($row['overlay'])) {
				$param .= "_o";
				#if ($row['overlay'] != 1 /*&& $layers != 2 another set of tiles needed because imagecopymerge is great!*/)
				#	$param .= $row['overlay']; # only relevant for base layer, square layer and combined tiles
			}
		}

		$file="label_{$map_x}_{$map_y}_{$row['image_w']}_{$row['image_h']}_{$scale}$param.png";
		
		return $dir.$file;
	}

	function _trace($msg)
	{
		echo "$msg<br/>";
		flush();
	}	
	function _err($msg)
	{
		echo "<p><b>Error:</b> $msg</p>";
		flush();
	}
	
}

?>
