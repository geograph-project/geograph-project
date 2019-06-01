<?php

        //use a cache, because we reusing lots of coords!
        function getPixCoord($x,$y,$ri) {
                global $conv, $g;

                static $cache = array();
                $key = "$x.$y";
                if (empty($cache[$key])) {
                        list($lat,$lng) = $conv->internal_to_wgs84($x,$y,$ri, 0); //zero fudge to get bottom left!
                        $cache[$key] = $g->getOffsetPixelCoords($lat,$lng);
                }
                return $cache[$key];
        }


/*
*DISCLAIMER
* 
*THIS SOFTWARE IS PROVIDED BY THE AUTHOR 'AS IS' AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES *OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, *INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF *USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT *(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*
*	@author: Olivier G. <olbibigo_AT_gmail_DOT_com>
*	@version: 1.1
*	@history:
*		1.0	creation
		1.1	disclaimer added
*/
class GoogleMapUtilityClass {
	const TILE_SIZE = 256;

	public function __construct($X,$Y,$zoom) {
		$this->X = $X;
		$this->Y = $Y;
		$this->zoom = $zoom;

		$this->scale = (1 << ($zoom)) * GoogleMapUtilityClass::TILE_SIZE;
		$this->Xscale = $this->X * GoogleMapUtilityClass::TILE_SIZE;
		$this->Yscale = $this->Y * GoogleMapUtilityClass::TILE_SIZE;
	}

	//(lat, lng, z) -> parent tile (X,Y)
	public static function getTileXY($lat, $lng, $zoom) {
		$normalised = GoogleMapUtilityClass::_toNormalisedMercatorCoords(GoogleMapUtilityClass::_toMercatorCoords($lat, $lng));
		$scale = 1 << ($zoom);
		return new Point(
			(int)($normalised->x * $scale),
			(int)($normalised->y * $scale)
		);
	}//toTileXY

	//(lat, lng, z) -> (x,y) with (0,0) in the upper left corner of the MAP
	public static function getPixelCoords($lat, $lng, $zoom) {
		$normalised = GoogleMapUtilityClass::_toNormalisedMercatorCoords(GoogleMapUtilityClass::_toMercatorCoords($lat, $lng));
		$scale = (1 << ($zoom)) * GoogleMapUtilityClass::TILE_SIZE;
		return new Point(
			(int)($normalised->x * $scale),
			(int)($normalised->y * $scale)
		);
	}//getPixelCoords

	//(lat, lng, z) -> (x,y) in the upper left corner of the TILE ($X, $Y)
	public function getOffsetPixelCoordsOriginal($lat,$lng) {
		$pixelCoords = GoogleMapUtilityClass::getPixelCoords($lat, $lng, $this->zoom);
		return new Point(
			$pixelCoords->x - $this->X * GoogleMapUtilityClass::TILE_SIZE,
			$pixelCoords->y - $this->Y * GoogleMapUtilityClass::TILE_SIZE
		);
	}//getPixelOffsetInTile

	// new version of getOffsetPixelCoordsOriginal that has everything inlined! (reduces function calls, and object initiations
	public function getOffsetPixelCoords($lat,$lng) {
		//_toMercatorCoords
		if ($lng > 180) {
                        $lng -= 360;
                }
                $lng /= 360;
                $lat = asinh(tan(deg2rad($lat)))/M_PI/2;
		$point = new Point($lng, $lat);

		//_toNormalisedMercatorCoords
                $point->x += 0.5;
                $point->y = abs($point->y-0.5);

		//getPixelCoords  (calculating scale in constructor now!)
		$point->x = (int)($point->x * $this->scale);
		$point->y = (int)($point->y * $this->scale);

		//getOffsetPixelCoords (multiply by SIZE in constructor now!)
		$point->x = $point->x - $this->Xscale;
		$point->y = $point->y - $this->Yscale;
		return $point;
	}//getOffsetPixelCoords

	public function getTileRect() {
		$tilesAtThisZoom = 1 << $this->zoom;
		$lngWidth = 360.0 / $tilesAtThisZoom;
		$lng = -180 + ($this->X * $lngWidth);
		$latHeightMerc = 1.0 / $tilesAtThisZoom;
		$topLatMerc = $this->Y * $latHeightMerc;
		$bottomLatMerc = $topLatMerc + $latHeightMerc;
		$bottomLat = (180 / M_PI) * ((2 * atan(exp(M_PI * (1 - (2 * $bottomLatMerc))))) - (M_PI / 2));
		$topLat = (180 / M_PI) * ((2 * atan(exp(M_PI * (1 - (2 * $topLatMerc))))) - (M_PI / 2));
		$latHeight = $topLat - $bottomLat;
		return new Boundary($lng, $bottomLat, $lngWidth, $latHeight);
	}//getTileRect

	private static function _toMercatorCoords($lat, $lng) {
		if ($lng > 180) {
			$lng -= 360;
		}
		$lng /= 360;
		$lat = asinh(tan(deg2rad($lat)))/M_PI/2;
		return new Point($lng, $lat);
	}//_toMercatorCoords

	private static function _toNormalisedMercatorCoords($point) {
		$point->x += 0.5;
		$point->y = abs($point->y-0.5);
		return $point;
	}//_toNormalisedMercatorCoords
}//GoogleMapUtilityClass

class Point {
	public $x,$y;
	function __construct($x,$y) {
		$this->x = $x;
		$this->y = $y;
	}
	function __toString() {
		return "({$this->x},{$this->y})";
	}
}//Point

class Boundary {
	public $x,$y,$width,$height;
	function __construct($x,$y,$width,$height) {
		$this->x = $x;
		$this->y = $y;
		$this->width = $width;
		$this->height = $height;
	}
	function __toString() {
		return "({$this->x} x {$this->y},{$this->width},{$this->height})";
	}
}//Boundary

