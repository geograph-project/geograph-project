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
	public $pageSize = 15;

	public $filters = array();

	private $client = null;

	public function __construct($q = '') {
		if (!empty($q)) {
			return $this->prepareQuery($q);
		}
	}

	public function prepareQuery($q) {
		$this->rawq = $q;
		
			//change OR to the right syntax
		$q = str_replace(' OR ',' | ',$q);

			//AND is pointless
		$q = str_replace(' AND ',' ',$q);
		
			//http: (urls) bombs out the field: syntax
		$q = str_replace('http://','http ',$q);
		
			//setup field: syntax
		$q = preg_replace('/(-?)\b([a-z_]+):/','@$2 $1',$q);
		
			//remove unsuitable chars
		$q = trim(preg_replace('/[^\w~\|\(\)@"\/\'=<^$,-]+/',' ',trim(strtolower($q))));
	
			//remove any / not in quorum
		$q = preg_replace('/(?<!")\//',' ',$q);
	
			//convert ="phrase" to = on all words
		$q = preg_replace('/="(\^)?(\w[\w ]+)(\$?)"/e','"\\"$1".preg_replace("/\b(\w+)/","=\\$1","$2")."$3\\""',$q);
	
			//remove any = not at word start
		$q = preg_replace('/(^|[\s\(~"^-]+)=/','$1%',$q);
		$q = str_replace('=',' ',$q);
		$q = trim(str_replace('%','=',$q));
	
			//remove any ^ not at field start
		$q = preg_replace('/(^|@[\(\)\w,]+ |")\^/','$1%',$q);
		$q = str_replace('^',' ',$q);
		$q = trim(str_replace('%','^',$q));
	
			//remove any $ not at field end
		$q = trim(preg_replace('/\$(?!@ |$|")/',' ',$q));
	
			//remove any strange chars at end
		$q = trim(preg_replace('/[@^=\(~-]+$/','',$q));
	
			//change it back to simple: syntax
		$q2 = preg_replace('/(-?)[@]([a-z_]+) (-?)/','$1$3$2:',$q);
		$q2 = str_replace('|',' OR ',$q2);
		$this->qclean = trim(str_replace('  ',' ',$q2));
	
	
			//make excluded hyphenated words phrases
		$q = preg_replace('/(?<!"|\w)-(=?\w+)(-[-\w]*\w)/e','"-(\\"".str_replace("-"," ","$1$2")."\\" | ".str_replace("-","","$1$2").")"',$q);
		
			//make hyphenated words phrases
		$q = preg_replace('/(?<!")(\w+)(-[-\w]*\w)/e','"\\"".str_replace("-"," ","$1$2")."\\" | ".str_replace("-","","$1$2")',$q);
		
			//make excluded aposphies work (as a phrase) 
		$q = preg_replace('/(?<!"|\w)-(=?\w+)(\'\w*[\'\w]*\w)/e','"-(\"".str_replace("\\\'"," ","$1$2")."\" | ".str_replace("\\\'","","$1$2").")"',$q);
		
			//make aposphies work (as a phrase) 
		$q = preg_replace('/(?<!")(\w+)(\'\w*[\'\w]*\w)/e','"\"".str_replace("\\\'"," ","$1$2")."\" | ".str_replace("\\\'","","$1$2")',$q);
		
			//change single quotes to double
		$q = preg_replace('/(^|\s)\b\'([\w ]+)\'\b(\s|$)/','$1"$2"$3',$q);
		
			//transform 'near gridref' to the put the GR first (thats how processQuery expects it) 
		$q = preg_replace('/^(.*) *near +([a-zA-Z]{1,2} *\d{2,5} *\d{2,5}) *$/','$2 $1',$q);
	
		$this->q = $q;
	}
	
	public function processQuery() {
		$q = $this->q;

	split_timer('sphinx'); //starts the timer

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
				$q = "(".str_replace(" "," | ",$q).") @grid_reference (".join(" | ",$grs).")";
			} else {
				$q .= "@grid_reference (".join(" | ",$grs).")";
			}
			$qo .= " near $gr";
		} 
	
	split_timer('sphinx','processQuery',$qo); //logs the wall time

	
		$this->q = $q;
		$this->qoutput = $qo;
	}
	
	public function setSpatial($data) {
		require_once('geograph/conversions.class.php');
		$conv = new Conversions;
		
		$grs = array();
	
	split_timer('sphinx'); //starts the timer
	
		if (!empty($data['bbox'])) {
			list($e1,$n1,$ri1,$e2,$n2,$ri2) = $data['bbox'];
			
			$span = max($e2-$e1,$n2-$n1);
			//todo-decide-o-area?
			if ($span > 250000) { //100k ie 1 myriad
				$mod = 100000;
				$grlen = -1;
			} elseif ($span > 25000) { //10k ie 1 hectad
				$mod = 10000;
				$grlen = 2;
			} else {
				$mod = 1000;
				$grlen = 4;
			}
			
			if ($e1 == $e2 && $n1 == $n2) {
				$e2 = $e1 = floor($e1 / 1000) * 1000;
				$n2 = $n1 = floor($n1 / 1000) * 1000;
			} else {
				$e1 = floor($e1 / 1000) * 1000;
				$e2 =  ceil($e2 / 1000) * 1000;

				$n1 = floor($n1 / 1000) * 1000;
				$n2 =  ceil($n2 / 1000) * 1000;
			}
			
			//probably a better way to do this?
			for ($ee = $e1;$ee <= $e2;$ee+=1000) {
				if ($ee%$mod == 0) {
					for ($nn = $n1;$nn <= $n2;$nn+=1000) {
						if ($nn%$mod == 0) {
							
							list($gr2,$len) = $conv->national_to_gridref($ee,$nn,$grlen,$ri1,false);
							if (strlen($gr2) > $grlen)
								$grs[] = $gr2;
						}
					}
				}
			}
			
			if (count($grs) > 100) {
				//if we have that many it really doesnt matter if we miss a few!
				shuffle($grs);
				$grs = array_slice($grs,0,100);
			}
			
			if (count($grs) == 0) {
				//somethig went wrong... 
				
			} elseif ($span > 250000) { //100k ie 1 myriad
				$this->filters['myriad'] = "(".join(" | ",$grs).")";
			} elseif ($span > 25000) { //10k ie 1 hectad
				$this->filters['hectad'] = "(".join(" | ",$grs).")";
			} else {
				$this->filters['grid_reference'] = "(".join(" | ",$grs).")";
			}
			
			$this->sort = preg_replace('/@geodist \w+,?\s*/','',$this->sort);
			$cl = $this->_getClient();
			if (!empty($cl->_groupsort))
				$cl->_groupsort = preg_replace('/@geodist \w+,?\s*/','',$cl->_groupsort);
			
			split_timer('sphinx','setSpatial-bbox',serialize($data)); //logs the wall time
			
		} else {
			$onekm = (floor($data['x']) == $data['x'] && floor($data['y']) == $data['y'])?1:0;
			
			if ($onekm) {
				list($e,$n,$reference_index) = $conv->internal_to_national($data['x'],$data['y'],0);
			} else {
				list($e,$n,$reference_index) = $conv->internalfloat_to_national($data['x'],$data['y'],0);
				$oe = $e; $on = $n;
			}
			
			//convert to work in 'integer' 1km's
			$e = floor($e/1000);
			$n = floor($n/1000);

			list($gr2,$len) = $conv->national_to_gridref($e*1000,$n*1000,4,$reference_index,false);

			if ($data['d'] > 0 && $data['d'] < 1 && !$onekm) {
				$d = 1; //gridsquares
				
				//simple alogorithm to cut down on number of filters added. If the center of the search circle and gridsquare can't touch ($rad is the furthest distance where the two shapes still /just/ intersect) then there is no need to even bother with that square. 
				// - can get slightly too many squares, but never too little! - good enough :)
				//do things squared, as no need to take square-root of both sides, just for a comparison. 
				$radmsqd = (pow(1*1000,2)+$data['d']*1000)*2;
				
				for($x=$e-$d;$x<=$e+$d;$x++) {
					for($y=$n-$d;$y<=$n+$d;$y++) {
						if (pow($oe-($x*1000+500),2)+pow($on-($y*1000+500),2) <= $radmsqd) {
						
							list($gr2,$len) = $conv->national_to_gridref($x*1000,$y*1000,4,$reference_index,false);
							if (strlen($gr2) > 4)
								$grs[] = $gr2;
						}
					}
				}
				$this->filters['grid_reference'] = "(".join(" | ",$grs).")";
			
			} elseif ($data['d'] > 0 && $data['d'] <= 1) {
				$this->filters['grid_reference'] = $gr2;
			
			} elseif ($data['d'] < 10) {
				#$grs[] = $gr2;
				$d = max(1,abs($data['d']));
				for($x=$e-$d;$x<=$e+$d;$x++) {
					for($y=$n-$d;$y<=$n+$d;$y++) {
						list($gr2,$len) = $conv->national_to_gridref($x*1000,$y*1000,4,$reference_index,false);
						if (strlen($gr2) > 4)
							$grs[] = $gr2;
					}
				}
				$this->filters['grid_reference'] = "(".join(" | ",$grs).")";
			
			} else {
				#$this->filters['grid_reference'] = $gr2;
				$d = intval(abs($data['d'])/10)*10;
				for($x=$e-$d;$x<=$e+$d;$x+=10) {
					for($y=$n-$d;$y<=$n+$d;$y+=10) {
						list($gr2,$len) = $conv->national_to_gridref($x*1000,$y*1000,2,$reference_index,false);
						if (strlen($gr2) > 2)
							$grs[] = $gr2;
					}
				}
				$this->filters['hectad'] = "(".join(" | ",$grs).")";
			}

			if ($data['d'] != 1) {
				$cl = $this->_getClient();
				if (!empty($data['lat']) || !empty($data['long'])) {
					$cl->SetGeoAnchor('wgs84_lat', 'wgs84_long', deg2rad($data['lat']), deg2rad($data['long']) );
				} else {
					if ($onekm) {
						list($lat,$long) = $conv->national_to_wgs84($e*1000+500,$n*1000+500,$reference_index);
					} else {
						list($lat,$long) = $conv->national_to_wgs84($oe,$on,$reference_index);
					}
					$cl->SetGeoAnchor('wgs84_lat', 'wgs84_long', deg2rad($lat), deg2rad($long) );
				}
				$cl->SetFilterFloatRange('@geodist', 0.0, floatval($data['d']*1000));
				if ($data['d'] > 0 && $data['d'] < 1 && !$onekm) {
					//exclude images without (at least) centisquare resolution.
					$cl->setFilter('scenti',array(10000000,20000000),true); //todo we have hard-coded ri - should be primed from $CONF['references']
				}
			} else {
				$this->sort = preg_replace('/@geodist \w+,?\s*/','',$this->sort);
				$cl = $this->_getClient();
				if (!empty($cl->_groupsort))
					$cl->_groupsort = preg_replace('/@geodist \w+,?\s*/','',$cl->_groupsort);
			}
			
			split_timer('sphinx','setSpatial',serialize($data)); //logs the wall time

		}
	} 
	
	public function SetSelect($clause) {
		return $this->_getClient()->SetSelect($clause);
	}
	
	public function SetGroupBy($attribute, $func, $groupsort="@group desc") {
		return $this->_getClient()->SetGroupBy($attribute, $func, $groupsort);
	}
	
	public function SetGroupDistinct($attribute) {
		return $this->_getClient()->SetGroupDistinct($attribute);
	}
	
	public function groupByQuery($page = 1,$index_in = "_images") {
		global $CONF;
		$cl = $this->_getClient();
	
	split_timer('sphinx'); //starts the timer

		if ($index_in == "_images") {
			$index = "{$CONF['sphinx_prefix']}gi_stemmed,{$CONF['sphinx_prefix']}gi_stemmed_delta";
		} elseif ($index_in == "_map") {
			$index = "{$CONF['sphinx_prefix']}gi_map,{$CONF['sphinx_prefix']}gi_map_delta";
		} else {
			$index = $CONF['sphinx_prefix'].$index_in;
		}
		

		$sqlpage = ($page -1)* $this->pageSize;
		$cl->SetLimits($sqlpage,$this->pageSize);
		
		if (!empty($this->upper_limit)) {
			//todo a bodge to run on dev/staging
			$cl->SetIDRange ( 1, $this->upper_limit+0);
		}
		

		if (is_array($this->filters) && count($this->filters)) {
			$this->getFilterString(); //we only support int filters which call SetFilter for us
		}
		
		if (empty($this->q)) {
			$cl->SetMatchMode ( SPH_MATCH_FULLSCAN );
		} else {
			$cl->SetMatchMode ( SPH_MATCH_EXTENDED );
		}
		
		if (!empty($this->sort)) {
			if ($this->sort == -1) {
				#special token to mean will deal with it externally!
			} else {
				$cl->SetSortMode ( SPH_SORT_EXTENDED, $this->sort);
			}
		}
		
		$res = $cl->Query ( $this->q, $index );
		if (!empty($_GET['debug']) && $_GET['debug'] == 2) {
			print "<pre>";
			print_r($cl);	
			print "<pre style='background-color:red'>( '$q', '$index' )</pre>";
			print "<pre>";
			print_r($res);	
			if ( $cl->GetLastWarning() )
				print "\nWARNING: " . $cl->GetLastWarning() . "\n\n";
			exit;
		}
		if ( $res===false ) {
			//lets make this non fatal
			$this->query_info = $cl->GetLastError();
			$this->resultCount = 0;
			
			split_timer('sphinx','groupByQuery-error',$this->query_info); //logs the wall time
			
			return 0;
		} else {
			if ( $cl->GetLastWarning() )
				print "\nWARNING: " . $cl->GetLastWarning() . "\n\n";

			$this->query_info = "Query '{$q}' retrieved ".count($res['matches'])." of $res[total_found] matches in $res[time] sec.\n";
			$this->resultCount = $res['total_found'];
			if (!empty($this->pageSize))
				$this->numberOfPages = ceil($this->resultCount/$this->pageSize);
				
			split_timer('sphinx','groupByQuery',$this->query_info); //logs the wall time

			return $res;
		}	
	}	
	
	public function countMatches($index_in = "user") {
		global $CONF;
		$cl = $this->_getClient();

split_timer('sphinx'); //starts the timer
		
		if ($index_in == "_images") {
			$index = "{$CONF['sphinx_prefix']}gi_stemmed,{$CONF['sphinx_prefix']}gi_stemmed_delta";
		} elseif ($index_in == "_posts") {
			$index = "{$CONF['sphinx_prefix']}post_stemmed,{$CONF['sphinx_prefix']}post_stemmed_delta";
		} else {
			$index = $CONF['sphinx_prefix'].$index_in;
		}
		
		$q = $this->q;
		#print "<pre>$index | $q</pre>";
		
		$cl->SetMatchMode ( SPH_MATCH_EXTENDED );
		$cl->SetLimits(0,1,0);
		$res = $cl->Query ( $q, $index );
		
		if (!empty($_GET['debug']) && $_GET['debug'] == 2) {
			print "<pre>";
			print_r($cl);	
			print "<pre style='background-color:red'>( '$q', '$index' )</pre>";
			print "<pre>";
			print_r($res);	
			if ( $cl->GetLastWarning() )
				print "\nWARNING: " . $cl->GetLastWarning() . "\n\n";
			exit;
		}
		if ( $res===false ) {
			//lets make this non fatal
			$this->query_info = $cl->GetLastError();
			$this->resultCount = 0;
			
			split_timer('sphinx','countMatches-error',$this->query_info); //logs the wall time
			
			return 0;
		} else {
			if ( $cl->GetLastWarning() )
				print "\nWARNING: " . $cl->GetLastWarning() . "\n\n";

			$this->query_info = "Query '{$q}' retrieved ".count($res['matches'])." of $res[total_found] matches in $res[time] sec.\n";
			$this->resultCount = $res['total_found'];
			if (!empty($this->pageSize))
				$this->numberOfPages = ceil($this->resultCount/$this->pageSize);
				
			split_timer('sphinx','countMatches',$this->query_info); //logs the wall time
			
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
		
		$cl = $this->_getClient();
		
		foreach ($this->filters as $name => $value) {
			if (is_array($value)) {
				if (count($value) == 2) {//todo this is a rather flaky assuption!
					$cl->SetFilterRange($name, $value[0], $value[1]);
				} else {
					$cl->SetFilter($name, $value);
				}
			} elseif (strpos($value,'!') === 0) {
				$q .= " @$name ".str_replace('!','-',$value);
			} else {
				$q .= " @$name $value";
			}
		}
		return $q;
	} 
	
	public function addFilters($filters) {
		if (is_array($filters)) {
			$this->filters = array_merge($filters,$this->filters);
		}
	}
	
	function explodeWithQuotes($delimeter, $string) {
		$insidequotes = false;
		for ($i = 0; $i < strlen($string); $i++) {
			if ($string{$i} == '"') {
				if ($insidequotes)
					$insidequotes = false;
				else
					$insidequotes = true;
				$currentelement .= $string{$i};
			} elseif ($string{$i} == $delimeter) {
				if ($insidequotes) {
					$currentelement .= $string{$i};
				} else {
					$returnarray[$elementcount++] = $currentelement;
					$currentelement = '';
				}
			} else {
				$currentelement .= $string{$i};
			}
		}
		$returnarray[$elementcount++] = $currentelement;
		return $returnarray;
	}
	
	public function returnIds($page = 1,$index_in = "user",$DateColumn = '') {
		global $CONF;
		$q = $this->q;
		if (empty($this->qoutput)) {
			$this->qoutput = $q;
		}
		$cl = $this->_getClient();
		
	split_timer('sphinx'); //starts the timer

		if (!empty($_GET['debug']) && $_GET['debug'] == 2) {
			print "<pre style='background-color:red'>";
			var_dump($q);
			print "</pre>";
			print "<pre>";
			print_r($this->filters);
		}
		$mode = SPH_MATCH_ALL;
		
		if (strpos($q,'=~') === 0) {
			$q = preg_replace('/^=/','',$q);
			$q = preg_replace('/\b(\w+)/','=$1',$q);
		} 
		
		if (strpos($q,'~') === 0) {
			$q = preg_replace('/^\~\s*/','',$q);
			
			$words = substr_count($q,' ');
			
			if (count($this->filters) || $words> 9 || strpos($q,'"') !== FALSE || strpos($q,'=') !== FALSE) { //(MATCH_ANY - truncates to 10 words!)
				$mode = SPH_MATCH_EXTENDED2;
				$q = "(".preg_replace('/\| [\| ]+/','| ',implode(" | ",$this->explodeWithQuotes(" ",$q))).")".$this->getFilterString();
				
				$q = preg_replace('/(@[\(\)\w,]+) \|/','$1',$q);
				
			} elseif ($words > 0) {//at least one word
				$mode = SPH_MATCH_ANY;
			}
		} elseif (preg_match('/^"[^"]+"$/',$q)) {
			$words = substr_count($q,' ');
			if (count($this->filters) || $words> 9) { //(MATCH_PHRASE - truncates to 10 words!)
				$mode = SPH_MATCH_EXTENDED2;
				$q .= $this->getFilterString();
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
			
			//todo maybe call SetRankingMode(SPH_RANK_NONE) ???
		} elseif (!empty($this->sort)) {
			if ($this->sort == -1) {
				#special token to mean will deal with it externally!
			} else {
				$cl->SetSortMode ( SPH_SORT_EXTENDED, $this->sort);
			}
			//todo maybe call SetRankingMode(SPH_RANK_NONE) ??? - but only if relevance isnt in the $sort
		} else {
			$cl->SetSortMode ( SPH_SORT_EXTENDED, "@relevance DESC, @id DESC" );
		}
		
		$sqlpage = ($page -1)* $this->pageSize;
		$cl->SetLimits($sqlpage,$this->pageSize); ##todo reduce the page size when nearing the 1000 limit - so at least get bit of page
		
		if (!empty($this->upper_limit)) {
			//todo a bodge to run on dev/staging
			$cl->SetIDRange ( 1, $this->upper_limit+0);
		}
		
		if ($index_in == "_images") {
			$q = preg_replace('/@text\b/','@(title,comment,imageclass)',$q);
			$index = "{$CONF['sphinx_prefix']}gi_stemmed,{$CONF['sphinx_prefix']}gi_stemmed_delta";
		} elseif ($index_in == "_images_exact") {
			$q = preg_replace('/@text\b/','@(title,comment,imageclass)',$q);
			$index = "{$CONF['sphinx_prefix']}gridimage,{$CONF['sphinx_prefix']}gi_delta";
		} elseif ($index_in == "_posts") {
			$index = "{$CONF['sphinx_prefix']}post_stemmed,{$CONF['sphinx_prefix']}post_stemmed_delta";
		} else {
			$index = $CONF['sphinx_prefix'].$index_in;
		}
		$q = preg_replace('/@notshared\b/','@!(snippet,snippet_title,snippet_id)',$q);
		$q = preg_replace('/@shared\b/','@(snippet,snippet_title,snippet_id)',$q);
		$q = preg_replace('/@not(\w+)\b/','@!($1)',$q);
		
		$res = $cl->Query ( $q, $index );
		
		if (!empty($_GET['debug']) && $_GET['debug'] == 2) {
			print "<pre>";
			print_r($cl);	
			print "<pre style='background-color:red'>( '$q', '$index' )</pre>";
			print "<pre>";
			print_r($res);	
			if ( $cl->GetLastWarning() )
				print "\nWARNING: " . $cl->GetLastWarning() . "\n\n";
			exit;
		}
		// --------------
		
		if ( $res===false ) {
			$this->query_info = $cl->GetLastError();
			if (strpos($this->query_info,"syntax error") !== FALSE) {
				$this->query_error = "Syntax Error";
			} else {
				$this->query_error = "Search Failed";
			}
			$this->resultCount = 0;
			
			split_timer('sphinx','returnIds-error',$this->query_info); //logs the wall time

			return 0;
		} else {
			#if ( $cl->GetLastWarning() )
			#	print "\nWARNING: " . $cl->GetLastWarning() . "\n\n";
		
			$this->query_info = "Query '{$this->qoutput}' retrieved ".count($res['matches'])." of $res[total_found] matches in $res[time] sec.\n";
			$this->query_time = $res['time'];
			$this->resultCount = $res['total_found'];
			$this->maxResults = $res['total'];
			$this->numberOfPages = ceil(min($this->resultCount,$res['total'])/$this->pageSize);
			$this->res = $res;

			if (is_array($res["matches"]) ) {
				$this->ids = array_keys($res["matches"]);
				
				split_timer('sphinx','returnIds',$this->query_info); //logs the wall time
				
				return $this->ids;
			}
			
			split_timer('sphinx','returnIds-zero',$this->query_info); //logs the wall time
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
		$q = preg_replace('/([a-z_]+):/','',$q);
		$q = preg_replace('/[\|"\']+/','',$q);
		$cl = $this->_getClient();

	split_timer('sphinx'); //starts the timer

		$cl->SetMatchMode ( SPH_MATCH_ANY );
		$cl->SetSortMode ( SPH_SORT_EXTENDED, "@relevance DESC, @id DESC" );
		$cl->SetLimits(0,100);
		$res = $cl->Query ( preg_replace('/\s*\b(the|to|of)\b\s*/',' ',$q), 'gaz_stopped' );
		
		$arr = array();
		if ( $res!==false && is_array($res["matches"]) && count($res["matches"]))
		{
			if ( $cl->GetLastWarning() )
				print "\nWARNING: " . $cl->GetLastWarning() . "\n\n";

			$db=GeographDatabaseConnection(true);

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
			
			split_timer('sphinx','didYouMean',$this->query_info); //logs the wall time

		} else {
			split_timer('sphinx','didYouMean-zero',$this->query_info); //logs the wall time
		}
		//todo maybe check users too? ( then skip setByUsername when building search!) 
		return $arr;
	}
	
	function BuildExcerpts($docs, $index, $words, $opts=array() ) {
		global $CONF;
		$cl = $this->_getClient();
		
	split_timer('sphinx'); //starts the timer

		$res &= $cl->BuildExcerpts ( $docs, $CONF['sphinx_prefix'].$index, $words, $opts);
		
	split_timer('sphinx','BuildExcerpts'.count($docs)); //logs the wall time
	
		return $res;
	}
	
	function setSort($sort) {
		$this->sort = $sort;
	}
	
	/**
	 * get stored db object, creating if necessary
	 * @access private
	 */
	function &_getDB($allow_readonly = false)
	{
		//check we have a db object or if we need to 'upgrade' it
		if (!is_object($this->db) || ($this->db->readonly && !$allow_readonly) ) {
			$this->db=GeographDatabaseConnection($allow_readonly);
		} 
		return $this->db;
	}
	
	function &_getClient()
	{
		if (is_object($this->client))
			return $this->client;
		
		global $CONF;
		
		split_timer('sphinx'); //starts the timer
		
		require_once ( "3rdparty/sphinxapi.php" );
		
		$this->client = new SphinxClient ();
		$this->client->SetServer ( $CONF['sphinx_host'], $CONF['sphinx_port'] );
		
		split_timer('sphinx','connect'); //logs the wall time
		
		return $this->client;
	}
	
}

