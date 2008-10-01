<?php

/**
 * $Project: GeoGraph $
 * $Id: functions.inc.php 2911 2007-01-11 17:37:55Z barry $
 *
 * GeoGraph geographic photo archive project
 * http://geograph.sourceforge.net/
 *
 * This file copyright (C) 2007 Barry Hunter (geo@barryhunter.co.uk)
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

/**************************************************
*
******/

class sphinxwrapper {

	public $q = '';
	public $qraw = '';
	public $qoutput = '';
	public $sort = '';
	public $submitted_range;

	public $filters = array();

	private $client = null;

	public function __construct($q = '') {
		if (!empty($q)) {
			return $this->prepareQuery($q);
		}
	}

	public function prepareQuery($q) {
		$this->rawq = $q;
		
		$q = preg_replace('/ OR /',' | ',$q);
		
		$q = preg_replace('/(-?)\b([a-z_]+):/','@$2 $1',$q);
		
		$q = trim(preg_replace('/[^\w~\|\(\)@"\/-]+/',' ',trim(strtolower($q))));
		
		$q = preg_replace('/(\w+)(-\w+[-\w]*\w)/e','"\\"".str_replace("-"," ","$1$2")."\\""',$q);
		
		$q = preg_replace('/^(.*) *near +([a-zA-Z]{1,2} *\d{2,5} *\d{2,5}) *$/','$2 $1',$q);
		
		$this->q = $q;
		$this->qclean = preg_replace('/(-?)[@]([a-z_]+) (-?)/','$1$3$2:',$q);
	}
	
	public function processQuery() {
		$q = $this->q;

		if (preg_match('/^([a-zA-Z]{1,2}) +(\d{1,5})(\.\d*|) +(\d{1,5})(\.*\d*|)/',$q,$matches) && $matches[1] != 'tp') {
			$square=new GridSquare;
			$grid_ok=$square->setByFullGridRef($matches[0],true);

			if ($grid_ok) {
				$gr = $square->grid_reference;
				$e = $square->nateastings;
				$n = $square->natnorthings;
				$q = preg_replace("/{$matches[0]}\s*/",'',$q);
			} else {
				$r = "\t--invalid Grid Ref--";
			}

		} else if (preg_match('/^([a-zA-Z]{1,2})(\d{2,10})\b/',$q,$matches) && $matches[1] != 'tp') {

			$square=new GridSquare;
			$grid_ok=$square->setByFullGridRef($matches[0],true);

			if ($grid_ok) {
				$gr = $square->grid_reference;
				$e = $square->nateastings;
				$n = $square->natnorthings;
				$q = preg_replace("/{$matches[0]}\s*/",'',$q);
			} else {
				$r = "\t--invalid Grid Ref--";
			}
		} 

		$qo = $q;
		if (strlen($qo) > 64) {
			$qo = '--complex query--';
		} 
		if ($r) {
			//Handle Error

		} elseif (!empty($e)) {
			//Location search

			require_once('geograph/conversions.class.php');
			$conv = new Conversions;

			$e = floor($e/1000);
			$n = floor($n/1000);
			$grs = array();
			for($x=$e-2;$x<=$e+2;$x++) {
				for($y=$n-2;$y<=$n+2;$y++) {
					list($gr2,$len) = $conv->national_to_gridref($x*1000,$y*1000,4,$square->reference_index,false);
					$grs[] = $gr2;

				}
			}
			if (strpos($q,'~') === 0) {
				$q = preg_replace('/^\~/','',$q);
				$q = "(".str_replace(" "," | ",$q).") (".join(" | ",$grs).")";
			} else {
				$q .= " (".join(" | ",$grs).")";
			}
			$qo .= " near $gr";
		} 
		
		$this->q = $q;
		$this->qoutput = $qo;
	}
	
	public function setSpatial($data) {
		require_once('geograph/conversions.class.php');
		$conv = new Conversions;

		list($e,$n,$reference_index) = $conv->internal_to_national($data['x'],$data['y'],0);

		$e = floor($e/1000);
		$n = floor($n/1000);
		$grs = array();

		list($gr2,$len) = $conv->national_to_gridref($e*1000,$n*1000,4,$reference_index,false);

		if ($data['d'] == 1) {
			$this->filters['grid_reference'] = $gr2;
		} elseif ($data['d'] < 10) {
			#$grs[] = $gr2;
			for($x=$e-$data['d'];$x<=$e+$data['d'];$x++) {
				for($y=$n-$data['d'];$y<=$n+$data['d'];$y++) {
					list($gr2,$len) = $conv->national_to_gridref($x*1000,$y*1000,4,$reference_index,false);
					$grs[] = $gr2;
				}
			}
			$this->filters['grid_reference'] = "(".join(" | ",$grs).")";
		} else {
			#$this->filters['grid_reference'] = $gr2;
			for($x=$e-10;$x<=$e+10;$x+=10) {
				for($y=$n-10;$y<=$n+10;$y+=10) {
					list($gr2,$len) = $conv->national_to_gridref($x*1000,$y*1000,2,$reference_index,false);
					$grs[] = $gr2;
				}
			}
			$this->filters['hectad'] = "(".join(" | ",$grs).")";
		}

		if ($data['d'] > 1) {
			list($lat,$long) = $conv->national_to_wgs84($e*1000+500,$n*1000+500,$reference_index);
			$cl = $this->_getClient();
			$cl->SetGeoAnchor('wgs84_lat', 'wgs84_long', deg2rad($lat), deg2rad($long) );
			$cl->SetFilterFloatRange('@geodist', 0.0, floatval($data['d']*1000));
		} else {
			$this->sort = preg_replace('/@geodist \w+,?\s*/','',$this->sort);
		}
	} 
	
	
	public function countMatches($index_in = "user") {
		
		$cl = $this->_getClient();
		
		if ($index_in == "_images") {
			$index = "gi_stemmed,gi_delta_stemmed";
		} elseif ($index_in == "_posts") {
			$index = "post_stemmed,post_delta_stemmed";
		} else {
			$index = $index_in;
		}
		
		$q = $this->q;
		#print "<pre>$index | $q</pre>";
		
		$cl->SetMatchMode ( SPH_MATCH_EXTENDED );
		$cl->SetLimits(0,1,0);
		$res = $cl->Query ( $q, $index );
		if ( $res===false ) {
			//lets make this non fatal
			$this->query_info = $cl->GetLastError();
			$this->resultCount = 0;
			return 0;
		} else {
			if ( $cl->GetLastWarning() )
				print "\nWARNING: " . $cl->GetLastWarning() . "\n\n";

			$this->query_info = "Query '{$q}' retrieved ".count($res['matches'])." of $res[total_found] matches in $res[time] sec.\n";
			$this->resultCount = $res['total_found'];
			if (!empty($this->pageSize))
				$this->numberOfPages = ceil($this->resultCount/$this->pageSize);
			return $this->resultCount;
		}
	}
	
	public function countQuery($q,$index_in) {
		$this->prepareQuery($q);
		return $this->countMatches($index_in);
	}
	
	public function countImagesViewpoint($e,$n,$ri,$exclude = '') {
		
		$q = "@viewsquare ".($ri*10000000 + intval($n/1000)*1000 + intval($e/1000));
		if ($exclude) {
			$q .= " @grid_reference -$exclude";
		}
		$this->q = $q;
		
		return $this->countMatches("_images");
	}
	
	public function returnIdsViewpoint($e,$n,$ri,$exclude = '',$page = 1) {
		
		$q = "@viewsquare ".($ri*10000000 + intval($n/1000)*1000 + intval($e/1000));
		if ($exclude) {
			$q .= " @grid_reference -$exclude";
		}
		$this->q = $q;
		
		
		return $this->returnIds($page,'_images');
	}
	
	
	public function getFilterString() {
		$q = '';
		foreach ($this->filters as $name => $value) {
			$q .= " @$name $value";
		}
		return $q;
	} 
	public function addFilters($filters) {
		if (is_array($filters)) {
			$this->filters = array_merge($filters,$this->filters);
		}
	}
	
	public function returnIds($page = 1,$index_in = "user",$DateColumn = '') {
		$q = $this->q;
		if (empty($this->qoutput)) {
			$this->qoutput = $q;
		}
		$cl = $this->_getClient();
		if (!empty($_GET['debug']) && $_GET['debug'] == 2) {
			print "<pre style='background-color:red'>";
			var_dump($q);
			print "</pre>";
			print "<pre>";
			print_r($this->filters);
		}
		$mode = SPH_MATCH_ALL;
		if (strpos($q,'~') === 0) {
			$q = preg_replace('/^\~/','',$q);
			if (count($this->filters)) {
				$mode = SPH_MATCH_EXTENDED2;
				$q = "(".str_replace(" "," | ",$q).")".$this->getFilterString();
			} else {
				if (substr_count($q,' ') > 0) //at least one word
					$mode = SPH_MATCH_ANY;
			}
		} elseif (preg_match('/^"[^"]+"$/',$q)) {
			if (count($this->filters)) {
				$mode = SPH_MATCH_EXTENDED2;
				$q = "\"".$q."\" ".$this->getFilterString();
			} else {
				$mode = SPH_MATCH_PHRASE;
			}
		#} elseif (preg_match('/^[\w\|\(\) -]*[\|\(\)-]+[\w\|\(\) -]*$/',$q)) {
		#	$mode = SPH_MATCH_BOOLEAN; //doesnt perform no relvence !
		#	//todo if we enable this need to deal with filters
		} elseif (preg_match('/[~\|\(\)@"\/-]/',$q)) {
			if (count($this->filters)) {
				$q .= $this->getFilterString();
			} 
			$mode = SPH_MATCH_EXTENDED2;
		} elseif (count($this->filters)) {
			$q .= $this->getFilterString();
			$mode = SPH_MATCH_EXTENDED2;
		}
		$cl->SetMatchMode ( $mode );
		
		$cl->SetWeights ( array ( 100, 1 ) );
		if (!empty($DateColumn)) {
			$cl->SetSortMode ( SPH_SORT_TIME_SEGMENTS, $DateColumn);
		} elseif (!empty($this->sort)) {
			$cl->SetSortMode ( SPH_SORT_EXTENDED, $this->sort);
		} else {
			$cl->SetSortMode ( SPH_SORT_EXTENDED, "@relevance DESC, @id DESC" );
		}
		
		$sqlpage = ($page -1)* $this->pageSize;
		$cl->SetLimits($sqlpage,$this->pageSize); ##todo reduce the page size when nearing the 1000 limit - so at least get bit of page
		
		if (!empty($this->submitted_range)) {
			$cl->SetFilterRange ('submitted', $this->submitted_range[0], $this->submitted_range[1]);
		}

		if (!empty($this->upper_limit)) {
			//todo a bodge to run on dev/staging
			$cl->SetIDRange ( 1, $this->upper_limit+0);
		}
		
		if ($index_in == "_images") {
			$index = "gi_stemmed,gi_delta_stemmed";
		} elseif ($index_in == "_images_exact") {
			$index = "gridimage,gi_delta";
		} elseif ($index_in == "_posts") {
			$index = "post_stemmed,post_delta_stemmed";
		} else {
			$index = $index_in;
		}
		
		$res = $cl->Query ( $q, $index );
		
		if (!empty($_GET['debug']) && $_GET['debug'] == 2) {
			print "<pre>";
			print_r($cl);	
			print "<pre style='background-color:red'>( '$q', '$index' )</pre>";
			print "<pre>";
			print_r($res);	
			exit;
		}
		// --------------
		
		if ( $res===false ) {
			$this->query_info = $cl->GetLastError();
			$this->resultCount = 0;
			return 0;
		} else {
			if ( $cl->GetLastWarning() )
				print "\nWARNING: " . $cl->GetLastWarning() . "\n\n";
		
			$this->query_info = "Query '{$this->qoutput}' retrieved ".count($res['matches'])." of $res[total_found] matches in $res[time] sec.\n";
			$this->query_time = $res['time'];
			$this->resultCount = $res['total_found'];
			$this->maxResults = $res['total'];
			$this->numberOfPages = ceil(min($this->resultCount,$res['total'])/$this->pageSize);
			$this->res = $res;

			if (is_array($res["matches"]) ) {
				$this->ids = array_keys($res["matches"]);

				return $this->ids;
			}
		}
	}
	function didYouMean($q = '') {
		if (empty($q)) {
			$q = $this->q;
		}
		if (empty($q)) {
			return array();
		}
		$q = preg_replace('/@([a-z_]+) /','',$q);
		$cl = $this->_getClient();
		$cl->SetMatchMode ( SPH_MATCH_ANY );
		$cl->SetSortMode ( SPH_SORT_EXTENDED, "@relevance DESC, @id DESC" );
		$cl->SetLimits(0,100);
		$res = $cl->Query ( preg_replace('/\s*\b(the|to|of)\b\s*/',' ',$q), 'gaz_stopped' );
		
		$arr = array();
		if ( $res!==false && is_array($res["matches"]) && count($res["matches"]))
		{
			if ( $cl->GetLastWarning() )
				print "\nWARNING: " . $cl->GetLastWarning() . "\n\n";

			$db=NewADOConnection(!empty($GLOBALS['DSN2'])?$GLOBALS['DSN2']:$GLOBALS['DSN']);

			$ids = array_keys($res["matches"]);

			$where = "id IN(".join(",",$ids).")";

			$sql = "SELECT gr,name,localities
			FROM placename_index
			WHERE $where
			LIMIT 60";

			$results = $db->getAll($sql);
			$r = '';
			if (!empty($results) && count($results)) {
				foreach ($results as $row) {
					foreach (preg_split('/[\/,\|]+/',trim(strtolower($row['name']))) as $word) {
						$word = preg_replace('/[^\w ]+/','',$word);
						if (strpos($q,$word) !== FALSE) {
							$row['query'] = str_replace($word,'',$q);
							$arr[] = $row;
						}

					}
				}
			}
		}
		//todo maybe check users too? ( then skip setByUsername when building search!) 
		return $arr;
	}
	
	function BuildExcerpts($docs, $index, $words, $opts=array() ) {
		$cl = $this->_getClient();
		return $cl->BuildExcerpts ( $docs, $index, $words, $opts);
	}
	
	function setSort($sort) {
		$this->sort = $sort;
	}
	function setSubmittedRange($range) {
		$this->submitted_range = $range;
	}
	
	/**
	 * get stored db object, creating if necessary
	 * @access private
	 */
	function &_getDB()
	{
		if (!is_object($this->db))
			$this->db=NewADOConnection($GLOBALS['DSN']);
		if (!$this->db) die('Database connection failed'); 
		return $this->db;
	}
	
	function &_getClient()
	{
		if (is_object($this->client))
			return $this->client;
		
		global $CONF;
		
		require_once ( "3rdparty/sphinxapi.php" );
		
		$this->client = new SphinxClient ();
		$this->client->SetServer ( $CONF['sphinx_host'], $CONF['sphinx_port'] );
		
		return $this->client;
	}}


?>