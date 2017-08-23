<?php
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
class GoogleMapUtility {
	const TILE_SIZE = 256;
	
	//(lat, lng, z) -> parent tile (X,Y)
	public static function getTileXY($lat, $lng, $zoom) {
		$normalised = GoogleMapUtility::_toNormalisedMercatorCoords(GoogleMapUtility::_toMercatorCoords($lat, $lng));
		$scale = 1 << ($zoom);
		return new Point(
			(int)($normalised->x * $scale), 
			(int)($normalised->y * $scale)
		);
	}//toTileXY
	
	//(lat, lng, z) -> (x,y) with (0,0) in the upper left corner of the MAP
	public static function getPixelCoords($lat, $lng, $zoom) {
		$normalised = GoogleMapUtility::_toNormalisedMercatorCoords(GoogleMapUtility::_toMercatorCoords($lat, $lng));
		$scale = (1 << ($zoom)) * GoogleMapUtility::TILE_SIZE;
		return new Point(
			(int)($normalised->x * $scale), 
			(int)($normalised->y * $scale)
		);
	}//getPixelCoords

	//(lat, lng, z) -> (x,y) in the upper left corner of the TILE ($X, $Y)
	public static function getOffsetPixelCoords($lat,$lng,$zoom, $X, $Y) {
		$pixelCoords = GoogleMapUtility::getPixelCoords($lat, $lng, $zoom);
		return new Point(
			$pixelCoords->x - $X * GoogleMapUtility::TILE_SIZE, 
			$pixelCoords->y - $Y * GoogleMapUtility::TILE_SIZE
		);
	}//getPixelOffsetInTile
	
	public static function getTileRect($X,$Y,$zoom) {
		$tilesAtThisZoom = 1 << $zoom;
		$lngWidth = 360.0 / $tilesAtThisZoom;
		$lng = -180 + ($X * $lngWidth);	
		$latHeightMerc = 1.0 / $tilesAtThisZoom;
		$topLatMerc = $Y * $latHeightMerc;
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
}//GoogleMapUtility

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

