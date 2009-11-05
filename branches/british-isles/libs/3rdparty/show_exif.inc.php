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

/*
string angle_kml($exif,$ee,$nn,$len,$vdir,$lat,$lon,$filename,$reference_index)
version: 090711
string $exif: unserialised 'exif' array straight from the Geograph database
int $ee: camera easting as taken from 'viewpoint_eastings' in Geograph database
int $nn: camera northing as taken from 'viewpoint_northings' in Geograph database
int $len: camera GR resolution as taken from 'viewpoint_grlen' in Geograph database
int $dir: 'view_direction' as taken from Geograph database
float $lat: subject latitude as taken from 'wgs84_lat' in Geograph database
float $lon: subject longitude as taken from 'wgs84_long' in Geograph database
string $filename: filename for the kml file
int $reference_index: matches the Geograph Reference index (in British Isles, gb=1, ireland=2) 
return value: file name of kml file, or false if none was created
*/
function angle_kml($exif,$ee,$nn,$len,$vdir,$lat,$lon,$filename='',$reference_index = 1) {
  require_once('geograph/conversions.class.php');
  $conv = new Conversions;
  
  // get relevant exif data`
  $focalLength=($exif['EXIF']['FocalLength']) or die('<p><b>Error: Can\'t determine focal length.</b>');
  $fract=explode('/',$focalLength);
  $focalLength=$fract[0]/$fract[1];

  $fpxr=($exif['EXIF']['FocalPlaneXResolution']) or die('<p><b>Error: Can\'t determine focal plane resolution.</b>');
  $fract=explode('/',$fpxr);
  $fpxr=$fract[0]/$fract[1];

  if (!empty($_GET['debug']))
    printf("<dt>Focal plane width (x) resolution</dt>\n<dd>%d dpi</dd>",$fpxr);

  $imageWidth=($exif['EXIF']['ExifImageWidth']) or die('<p><b>Error: Can\'t determine cropped image width.</b>');

  if (!empty($_GET['debug']))
    printf("<dt>Image width</dt>\n<dd>$imageWidth pixels</dd>");

  // Compute opening angle.
  $viewAngle=2.*atan(25.4*$imageWidth/2./$focalLength/$fpxr)*180./M_PI;

  if (!empty($_GET['debug']))
    printf("<dt>Calculated opening angle:</dt>\n<dd>%5.1f deg</dd>",$viewAngle);

  if ($ee&&$vdir) {
    if ($len<12) {                   // move location to centre of referenced area
      $off=5.*pow(10,4-$len/2);
      $ee+=$off;
      $nn+=$off;
    }

    // Get lat/lon for camera and two points 80km and two points 2km distant at the edges of the field of view.
    $phi=$vdir-.5*$viewAngle;
    $left[0]=$ee+80000.*sin(M_PI*$phi/180.);
    $left[1]=$nn+80000.*cos(M_PI*$phi/180.);
    $left[2]=$ee+ 2000.*sin(M_PI*$phi/180.);    // The near point is needed because the line drawn in GE tends to
    $left[3]=$nn+ 2000.*cos(M_PI*$phi/180.);    // disappear underground in hilly terrain despite tessellation.
    $phi=$vdir+.5*$viewAngle;
    $right[0]=$ee+80000.*sin(M_PI*$phi/180.);
    $right[1]=$nn+80000.*cos(M_PI*$phi/180.);
    $right[2]=$ee+ 2000.*sin(M_PI*$phi/180.);
    $right[3]=$nn+ 2000.*cos(M_PI*$phi/180.);
    $gecm[0] =$ee-   50.*sin(M_PI*$vdir/180.);  // move the GE camera a little in the opposite direction for better overview
    $gecm[1] =$nn-   50.*cos(M_PI*$vdir/180.);
    $cmra=    $conv->national_to_wgs84($ee,      $nn,      $reference_index);
    $left_80= $conv->national_to_wgs84($left[0], $left[1], $reference_index);
    $right_80=$conv->national_to_wgs84($right[0],$right[1],$reference_index);
    $left_2=  $conv->national_to_wgs84($left[2], $left[3], $reference_index);
    $right_2= $conv->national_to_wgs84($right[2],$right[3],$reference_index);
    $gecm=    $conv->national_to_wgs84($gecm[0], $gecm[1], $reference_index);
    
    // Make kml file.
    Header("Content-Type:application/vnd.google-earth.kml+xml; charset=utf-8; filename=".basename($filename));
    Header("Content-Disposition: attachment; filename=".basename($filename));
			
    printf("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n");             /*<?*/
    printf("<kml xmlns=\"http://www.opengis.net/kml/2.2\" xmlns:gx=\"http://www.google.com/kml/ext/2.2\" xmlns:kml=\"http://www.opengis.net/kml/2.2\" xmlns:atom=\"http://www.w3.org/2005/Atom\">\n");
    printf("  <Document>\n");
    printf("    <Camera>\n");
    printf("      <longitude>%f</longitude>\n",$gecm[1]);
    printf("      <latitude>%f</latitude>\n",$gecm[0]);
    printf("      <altitude>10</altitude>\n");
    printf("      <heading>%f</heading>\n",$vdir);
    printf("      <tilt>80.</tilt>\n");
    printf("    </Camera>\n");
    printf("    <Placemark>\n");    // view cone
    printf("      <name>view cone</name>\n");
    printf("      <Style>\n");
    printf("        <LineStyle>\n");
    printf("          <color>aa0000ff</color>\n");
    printf("          <width>4</width>\n");
    printf("        </LineStyle>\n");
    printf("      </Style>\n");
    printf("      <LineString>\n");
    printf("        <tessellate>1</tessellate>\n");
    printf("        <coordinates>\n");   // note lon goes before lat in kml
    printf("%f,%f,0 %f,%f,0 %f,%f,0 %f,%f,0 %f,%f,0\n",$left_80[1],$left_80[0],$left_2[1],$left_2[0],$cmra[1],$cmra[0],$right_2[1],$right_2[0],$right_80[1],$right_80[0]);
    printf("        </coordinates>\n");
    printf("      </LineString>\n");
    printf("    </Placemark>\n");
    printf("    <Placemark>\n");    // subject
    printf("      <name>subject</name>\n");
    printf("      <Point>\n");
    printf("        <coordinates>$lon,$lat,0</coordinates>\n");
    printf("      </Point>\n");
    printf("    </Placemark>\n");
    printf("  </Document>\n");
    printf("</kml>\n");

  } else {
    echo "<p><b>Picture needs both camera position and view direction to create kml file.</b><p>";
    return false;
  }
}


/*
integer show_exif(string $exif_array)
exif_array - Raw EXIF data from exif_read_data
return value: Number of exif tags successfully processed
*/
function show_exif($exif_array) {
  // list of exif tags to be processed - show_exif() silently ignores any that aren't present in the file
  $relevant=array(
    'Make',
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
    if ($exif[$tag]) {
      $found++;
      if     ($tag=='ExposureTime') {
        $fract=explode('/',$exif[$tag]);
        if ($fract[0]==1) $value=$exif[$tag].' s';
        elseif ($fract[0]==10) {
          $value=(int)($fract[1]/10);
          $value='1/'.$value.' s';
        } else {
          $value=(int)(1000*$fract[0]/$fract[1]);
          $value=$value.' ms';
        }
      }
      elseif ($tag=='FNumber') {
        $fract=explode('/',$exif[$tag]);
        $value=$fract[0]/$fract[1];
        $value=sprintf('f/%3.1f',$value);
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
        $value=sprintf('%3.1fmm (actual, <b>not</b> 35mm equivalent)',$value);
      }
      else $value=$exif[$tag];
      if ($tag=='FNumber') $tag='Aperture (F number)';
      // show result
      if ($value) {
        echo "<dt>$tag</dt>\n<dd>$value</dd>\n";
        $count++;
      }
    }
  }
  if (!$count) echo "<p>Geograph has no EXIF data for this image.</p>\n";
  return $count;
}

?>
