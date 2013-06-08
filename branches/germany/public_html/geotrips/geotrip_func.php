<?php

function whichtype($type, $translate = true) {
	global $CONF;
	if ($translate && $CONF['lang'] === 'de') { # FIXME use $MESSAGES + use array
		if     ($type=='walk') return 'eine Wanderung';
		elseif ($type=='bike') return 'eine Radtour';
		elseif ($type=='road') return 'eine Fahrt';
		elseif ($type=='rail') return 'eine Zugfahrt';
		elseif ($type=='boat') return 'eine Bootsfahrt';
		elseif ($type=='bus')  return 'eine Reise mit öffentlichen Verkehrsmitteln';
		else                   return 'eine Tour';
	} else {
		if     ($type=='walk') return 'walk';
		elseif ($type=='bike') return 'cycle ride';
		elseif ($type=='road') return 'drive';
		elseif ($type=='rail') return 'train ride';
		elseif ($type=='boat') return 'boat trip';
		elseif ($type=='bus')  return 'journey by scheduled public transport';
		else                   return 'trip';
	}
}

function xml_startTag($parser,$data,$attr) {
  global $trkpt;
  if ($data=='TRKPT'||$data=='RTEPT') $trkpt[]=$attr;
}

function sanitise($str) {
  $str=preg_replace('%\r\n%',' <br/>',$str);
  $str=preg_replace('%http://[^\s]*%','[<a href="$0">link</a> <img alt="external link" title="" src="http://users.aber.ac.uk/ruw/templates/external.png" />]',$str);
  $str=preg_replace('% <br/>%','<br />',$str);
  $str=preg_replace('%\[\[([0-9]*)\]\]%','<a href="http://geo.hlipp.de/photo/\1">Link</a>',$str);
  $str=preg_replace('%\[[1-9]\]%','',$str);  // temporary fix to remove numerical references to shared descriptions
  return addslashes($str);
}

function fake_precision(&$geo) {
  if ($geo['natgrlen']==8) {
    $geo['nateastings']+=5;
    $geo['natnorthings']+=5;
  } elseif ($geo['natgrlen']==6) {
    $geo['nateastings']+=50;
    $geo['natnorthings']+=50;
  }
  if ($geo['viewpoint_grlen']==8) {
    $geo['viewpoint_eastings']+=5;
    $geo['viewpoint_northings']+=5;
  } elseif ($geo['viewpoint_grlen']==6) {
    $geo['viewpoint_eastings']+=50;
    $geo['viewpoint_northings']+=50;
  }
}

function bbox2gr($bbox) {
  global $lookup;
  $bbox=explode(' ',$bbox);
  $lat=.5*($bbox[0]+$bbox[2]);
  $lon=.5*($bbox[1]+$bbox[3]);
  require_once('geograph/conversionslatlong.class.php');
  $conv = new ConversionsLatLong;
  $en = $conv->wgs84_to_national($lat,$lon);
  if (!count($en)) {
    return '';
  }
  $gr = $conv->national_to_gridref($en[0],$en[1],4,$en[2]);
  return $gr[0];
}

