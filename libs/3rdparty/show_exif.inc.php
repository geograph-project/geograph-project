<?php
/**
 * $Project: GeoGraph $
 * $Id$
 * 
 * GeoGraph geographic photo archive project
 * http://geograph.sourceforge.net/
 *
 * This file copyright (C) 2009  Rudi Winter (http://www.geograph.org.uk/profile/2520)
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
*
* @package Geograph
* @author Rudi Winter <http://www.geograph.org.uk/profile/2520>
* @version $Revision$
*/

#based on http://users.aber.ac.uk/ruw/geograph/show_exif.inc

/*
integer show_exif(string $exif_array)
exif_array - Raw EXIF data from exif_read_data
return value: Number of exif tags successfully processed
*/
function show_exif($exif_array) {
  // list of exif tags to be processed - show_exif() silently ignores any that aren't present in the file
  $relevant=array(
    'Model',
    'CCDWidth',
    'ExposureTime',
    'FNumber',
    'ISOSpeedRatings',
    'FocalLength',
    'ExposureProgram',
    'Flash',
    'MeteringMode',
    'LightSource'
  );

  $exif=array_merge($exif_array['COMPUTED'],$exif_array['IFD0'],$exif_array['EXIF']);
  
  // find relevant tags
  foreach ($relevant as $tag) {
    // look up non-numerical values, and add units where appropriate
    if     ($tag=='ExposureTime') $value=$exif[$tag].' s';
    elseif ($tag=='FNumber') {
      $fract=explode('/',$exif[$tag]);
      $value=$fract[0]/$fract[1];
      $value='f/'.$value;
    }
    elseif ($tag=='ExposureProgram') {
      if     ($exif[$tag]==1) $value='manual';
      elseif ($exif[$tag]==2) $value='normal';
      elseif ($exif[$tag]==3) $value='aperure priority';
      elseif ($exif[$tag]==4) $value='shutter priority';
      elseif ($exif[$tag]==5) $value='creative (biased towards depth of field)';
      elseif ($exif[$tag]==6) $value='action (biased towards shutter speed)';
      elseif ($exif[$tag]==7) $value='portrait (closeup with background out of focus)';
      elseif ($exif[$tag]==8) $value='landscape (background in focus)';
      else                    $value=null;
    }
    elseif ($tag=='MeteringMode') {
      if     ($exif[$tag]==1) $value='average';
      elseif ($exif[$tag]==2) $value='centre-weighted average';
      elseif ($exif[$tag]==3) $value='spot';
      elseif ($exif[$tag]==4) $value='multi-spot';
      elseif ($exif[$tag]==5) $value='pattern';
      elseif ($exif[$tag]==6) $value='partial';
      else                    $value=null;
    }
    elseif ($tag=='LightSource') {
      if     ($exif[$tag]== 1) $value='daylight';
      elseif ($exif[$tag]== 2) $value='fluorescent';
      elseif ($exif[$tag]== 3) $value='tungsten (incandescent)';
      elseif ($exif[$tag]== 4) $value='flash';
      elseif ($exif[$tag]== 9) $value='fine weather';
      elseif ($exif[$tag]==10) $value='cloudy';
      elseif ($exif[$tag]==11) $value='shade';
      elseif ($exif[$tag]==12) $value='daylight fluorescent (7100K)';
      elseif ($exif[$tag]==13) $value='day white fluorescent (5400K)';
      elseif ($exif[$tag]==14) $value='cool white fluorescent (4500K)';
      elseif ($exif[$tag]==15) $value='white fluorescent (3700K)';
      elseif ($exif[$tag]==17) $value='standard light A';  // whatever that means!
      elseif ($exif[$tag]==18) $value='standard light B';
      elseif ($exif[$tag]==19) $value='standard light C';
      elseif ($exif[$tag]==20) $value='D55';
      elseif ($exif[$tag]==21) $value='D65';
      elseif ($exif[$tag]==22) $value='D75';
      elseif ($exif[$tag]==23) $value='D50';
      elseif ($exif[$tag]==24) $value='ISO studio tungsten';
      else                     $value=null;
    }
    elseif ($tag=='Flash') $exif[$tag]&1 ? $value='on' : $value='off';  // bit 8 indicates if flash was on; rest ignored here
    elseif ($tag=='FocalLength') {
      $fract=explode('/',$exif[$tag]);
      $value=$fract[0]/$fract[1];
      $value=$value.'mm (actual, <b>not</b> 35mm equivalent)';
    }
    else $value=$exif[$tag];
    if ($tag=='FNumber') $tag='Aperture (F number)';
    // show result
    if ($value) {
      echo "<dt>$tag</dt>\n<dd>$value</dd>\n";
      $count++;
    }
  }
  return $count;
}

?>