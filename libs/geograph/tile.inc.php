<?

function projectpoint($x,$y,$d,$a) {//x/y/distance/angle
	$a = deg2rad($a);
	$xx = sin($a)*$d;
	$yy = cos($a)*$d;
	return array(round($x+$xx),round($y-$yy));     //minus, because images use top/left origin, e/n use bottom left, and $a is relative to north.
}

function imageaddalpha(&$im, $x, $y, $delta) {

        if ($x<0 || $x >= googleMapUtilityClass::TILE_SIZE)
                return;
        if ($y<0 || $y >= googleMapUtilityClass::TILE_SIZE)
                return;

	$rgba = imagecolorat($im, $x, $y);

		$r = ($rgba >> 16) & 0xFF;
		$g = ($rgba >> 8) & 0xFF;
		$b = $rgba & 0xFF;
		$a = ($rgba & 0x7F000000) >> 24;

	$a+=round($delta);
	if ($a<=0) $a = 0;
	$color = imagecolorallocatealpha($im, $r, $g, $b, $a);

	imagesetpixel($im, $x, $y, $color);
}



function add_to_where($filter,$all = true) {
	if (!empty($_GET['where'])) {
		if (is_array($_GET['where'])) {
	        	$_GET['where'][] = $filter;
		} elseif ($all || strpos($_GET['where'],'id ') !== 0) { //special case of it being a 'id' filter, skip adding this filter
			$_GET['where'] = array($_GET['where'],$filter);
		}
	} else {
        	$_GET['where'] = $filter;
	}
}


function getStaticColorKey(&$img) {
        $colour=array();

        for ($p=1; $p<=10; $p++) {
                switch (true) {
                        case $p == 1: $r=255; $g=255; $b=0; break;
                        case $p == 2: $r=255; $g=196; $b=0; break;
                        case $p == 3: $r=255; $g=132; $b=0; break;
                        case $p == 4: $r=255; $g=64; $b=0; break;
                        case $p == 5: $r=225; $g=0; $b=0; break;
                        case $p == 6: $r=200; $g=0; $b=0; break;
                        case $p == 7: $r=168; $g=0; $b=0; break;
                        case $p == 8: $r=136; $g=0; $b=0; break;
                        case $p == 9: $r=112; $g=0; $b=0; break;
                        case $p ==10: $r=80; $g=0; $b=0; break;
                }
                $colour[$p]=imagecolorallocate($img, $r,$g,$b);
        }
        return $colour;
}


function doBoundingBoxesIntersect(string $bbox1_str, string $bbox2_str): bool
{
    // Parse the first bounding box string
    list($xmin1, $ymin1, $xmax1, $ymax1) = array_map('floatval', explode(',', $bbox1_str));

    // Parse the second bounding box string
    list($xmin2, $ymin2, $xmax2, $ymax2) = array_map('floatval', explode(',', $bbox2_str));

    // Check for intersection
    if ($xmin1 > $xmax2 || $xmax1 < $xmin2) {
        return false;
    }

    if ($ymin1 > $ymax2 || $ymax1 < $ymin2) {
        return false;
    }

    return true;
}

