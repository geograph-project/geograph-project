<?php
function fetch_url($url) {  // returns the path to the cached version of the requested url; if not cached, fetches it first
  $cachepath="../cache/file".md5($url).".cache";
  if (!empty($_GET['refresh'])||!file_exists($cachepath)||@filemtime($cachepath)<time()-604800) {
    $file=file_get_contents($url);
    if ($file) file_put_contents($cachepath,$file);
  }
  return $cachepath;
}

function whichtype($type) {
  if     ($type=='walk') return 'walk';
  elseif ($type=='bike') return 'cycle ride';
  elseif ($type=='road') return 'drive';
  elseif ($type=='rail') return 'train ride';
  elseif ($type=='boat') return 'boat trip';
  elseif ($type=='bus')  return 'journey by scheduled public transport';
  else                   return 'trip';
}

function xml_startTag($parser,$data,$attr) {
  global $trkpt;
  if ($data=='TRKPT'||$data='RTEPT') $trkpt[]=$attr;
}

function wgs2bng($lat,$lon) {
  $AG= 6378137.000        ;     // GRS80 semi-major axis         [m]
  $BG= 6356752.3141       ;     // GRS80 semi-minor axis         [m]
  $TX=    -446.448        ;     // translation parameters        [m]
  $TY=     125.157        ;     //   (approximate Helmert transformation)
  $TZ=    -542.060        ;
  $SS=      20.4894       ;     // Helmert scale factor          [ppm]
  $RX=       -.1502       ;     // rotation parameters           ["]
  $RY=       -.2470       ;     //   (approximate Helmert transformation)
  $RZ=       -.8421       ;
  $AA= 6377563.396        ;     // Airy 1830 semi-major axis     [m]
  $BA= 6356256.910        ;     // Airy 1830 semi-minor axis     [m]
  $E0=  400000.           ;     // Easting of true BNG origin    [m]
  $N0= -100000.           ;     // Northing of true BNG origin   [m]
  $F0=        .9996012717 ;     // BNG scale factor              [1]
  $LAT0=    49.           ;     // latitude of true BNG origin   [deg]
  $LON0=    -2.           ;     // longitude of true BNG origin  [deg]
// calculation of constants
  $e2g=$AG*$AG;
  $e2g=($e2g-$BG*$BG)/$e2g;
  $rx=2.*pi()*$RX/360./60./60.;
  $ry=2.*pi()*$RY/360./60./60.;
  $rz=2.*pi()*$RZ/360./60./60.;
  $s=$SS/1.e6;
  $e2a=$AA*$AA;
  $e2a=($e2a-$BA*$BA)/$e2a;
  $lat0=2.*pi()*$LAT0/360.;
  $lon0=2.*pi()*$LON0/360.;
  $n=($AA-$BA)/($AA+$BA);
// get WGS84 latitude and longitude
  $lat=2.*pi()*$lat/360.;
  $lon=2.*pi()*$lon/360.;
// convert from polar to Cartesian coordinates within WGS84
  $buf=sin($lat);
  $nu=$AG/sqrt(1.-$e2g*$buf*$buf);
  $x=$nu*cos($lat)*cos($lon);
  $y=$nu*cos($lat)*sin($lon);
  $z=(1.-$e2g)*$nu*$buf;
// transform from WGS84 to OSGB36 (approximate Helmert transform)
  $xx=$TX+$x*(1.+$s)-$rz*$y+$ry*$z;
  $yy=$TY+$rz*$x+$y*(1.+$s)-$rx*$z;
  $zz=$TZ-$ry*$x+$rx*$y+$z*(1.+$s);
// convert from Cartesian to polar coordinates within OSGB36
  $p=sqrt($xx*$xx+$yy*$yy);
  $lon=atan($yy/$xx);
  $lat=atan($zz/$p/(1.-$e2a));
  do {            // iterate at least once, until about 1mm (!) accuracy is reached
    $dlat=$lat;
    $buf=sin($lat);
    $nu=$AA/sqrt(1.-$e2a*$buf*$buf);
    $lat=atan(($zz+$e2a*$nu*$buf)/$p);
    $dlat-=$lat;
  } while ($dlat>1.e-8);
// convert from polar to BNG grid coordinates in OSGB36 (transverse Mercator projection)
  $buf=sin($lat);
  $buf=1.-$e2a*$buf*$buf;
  $nu=$AA*$F0/sqrt($buf);
  $rho=$AA*$F0*(1.-$e2a)/sqrt($buf*$buf*$buf);
  $eta2=$nu/$rho-1.;
  $dlat=$lat-$lat0;
  $slat=$lat+$lat0;
  $m=(1.+$n+5.*$n*$n/4.*(1.+$n))*$dlat;
  $m=$m-(3.*$n+3.*$n*$n+21.*$n*$n*$n/8.)*sin($dlat)*cos($slat);
  $m=$m+((15.*$n*$n/8.)*(1.+$n))*sin(2.*$dlat)*cos(2.*$slat);
  $m=$m-35.*$n*$n*$n*sin(3.*$dlat)*cos(3.*$slat)/24.;
  $m=$m*$BA*$F0;
  $clat=cos($lat);
  $tlat=tan($lat);
  $c1=$m+$N0;
  $c2=$nu*sin($lat)*$clat/2.;
  $c3=$nu*sin($lat)*$clat*$clat*$clat*(5.-$tlat*$tlat+9.*$eta2)/24.;
  $c3a=$nu*sin($lat)*pow($clat,5.)*(61.-58.*$tlat*$tlat+pow($tlat,4.))/720.;
  $c4=$nu*$clat;
  $c5=$nu*$clat*$clat*$clat*($nu/$rho-$tlat*$tlat)/6.;
  $c6=$nu*pow($clat,5.)*(5.-18.*$tlat*$tlat+pow($tlat,4.)+14.*$eta2-58.*$tlat*$tlat*$eta2)/120.;
  $dlon=$lon-$lon0;
  $nn=floor($c1+$c2*$dlon*$dlon+$c3*pow($dlon,4.)+$c3a*pow($dlon,6.));
  $ee=floor($E0+$c4*$dlon+$c5*$dlon*$dlon*$dlon+$c6*pow($dlon,5.));
  return array($ee,$nn);
}

function sanitise($str) {
  $str=preg_replace('%\r\n%',' <br/>',$str);
  $str=preg_replace('%http://[^\s]*%','[<a href="$0">link</a> <img alt="external link" title="" src="http://users.aber.ac.uk/ruw/templates/external.png" />]',$str);
  $str=preg_replace('% <br/>%','<br />',$str);
  $str=preg_replace('%\[\[([0-9]*)\]\]%','<a href="http://www.geograph.org.uk/photo/\1">Link</a>',$str);
  $str=preg_replace('%\[[1-9]\]%','',$str);  // temporary fix to remove numerical references to shared descriptions
  return addslashes($str);
}

function fake_precision($geo) {
  if ($geo[9]==8) {
    $geo[7]+=5;
    $geo[8]+=5;
  } elseif ($geo[9]==6) {
    $geo[7]+=50;
    $geo[8]+=50;
  }
  if ($geo[12]==8) {
    $geo[10]+=5;
    $geo[11]+=5;
  } elseif ($geo[12]==6) {
    $geo[10]+=50;
    $geo[11]+=50;
  }
  return $geo;
}

$lookup=array(0=>"SV",10=>"SW",20=>"SX",30=>"SY",40=>"SZ",50=>"TV",60=>"TW",1=>"SQ",11=>"SR",21=>"SS",31=>"ST",41=>"SU",51=>"TQ",61=>"TR",2=>"SL",12=>"SM",22=>"SN",32=>"SO",42=>"SP",52=>"TL",62=>"TM",3=>"SF",13=>"SG",23=>"SH",33=>"SJ",43=>"SK",53=>"TF",63=>"TG",4=>"SA",14=>"SB",24=>"SC",34=>"SD",44=>"SE",54=>"TA",64=>"TB",5=>"NV",15=>"NW",25=>"NX",35=>"NY",45=>"NZ",55=>"OV",65=>"OW",6=>"NQ",16=>"NR",26=>"NS",36=>"NT",46=>"NU",56=>"OQ",66=>"OR",7=>"NL",17=>"NM",27=>"NN",37=>"NO",47=>"NP",57=>"OL",67=>"OM",8=>"NF",18=>"NG",28=>"NH",38=>"NJ",48=>"NK",58=>"OF",68=>"OG",9=>"NA",19=>"NB",29=>"NC",39=>"ND",49=>"NE",59=>"OA",69=>"OB",110=>"HW",210=>"HX",310=>"HY",410=>"HZ",111=>"HR",211=>"HS",311=>"HT",411=>"HU",112=>"HM",212=>"HN",312=>"HO",412=>"HP");

function bbox2gr($bbox) {
  global $lookup;
  $bbox=explode(' ',$bbox);
  $ee=(int)(($bbox[0]+$bbox[2])/2000.);
  $nn=(int)(($bbox[1]+$bbox[3])/2000.);
  $myr=10*(int)($ee/100.)+(int)($nn/100.);
  $myr=$lookup[$myr];
  $ee-=(100*(int)($ee/100.));
  $nn-=(100*(int)($nn/100.));
  $gr=sprintf("%2s%02s%02s",$myr,$ee,$nn);
  return $gr;
}

?>

