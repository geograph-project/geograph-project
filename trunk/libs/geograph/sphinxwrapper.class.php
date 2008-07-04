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


	
	public function __construct($q = '') {
		if (!empty($q)) {
			return $this->prepareQuery($q);
		}
	}

	public function prepareQuery($q) {
		$this->rawq = $q;
		
		$q = preg_replace('/ OR /',' | ',$q);
		
		$q = trim(preg_replace('/[^\w~\|\(\)@"\/-]+/',' ',trim(strtolower($q))));
		
		$q = preg_replace('/^(.*) *near +([a-zA-Z]{1,2} *\d{2,5} *\d{2,5}) *$/','$2 $1',$q);
		
		$this->q = $q;
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
	
	public function returnImageIds($page = 1, $didyoumean = false) {
		global $CONF;
		$q = $this->q;
		
		require ( "3rdparty/sphinxapi.php" );
		
		$mode = SPH_MATCH_ALL;
		if (strpos($q,'~') === 0) {
			$q = preg_replace('/^\~/','',$q);
			if (substr_count($q,' ') > 1) //over 2 words
				$mode = SPH_MATCH_ANY;
		} elseif (preg_match('/[~\|\(\)@"\/-]/',$q)) {
			$mode = SPH_MATCH_EXTENDED;
		} 
		$index = "gi_stemmed,gi_delta_stemmed";
		
		$cl = new SphinxClient ();
		$cl->SetServer ( $CONF['sphinx_host'], $CONF['sphinx_port'] );
		$cl->SetWeights ( array ( 100, 1 ) );
		$cl->SetSortMode ( SPH_SORT_EXTENDED, "@relevance DESC, @id DESC" );
		$cl->SetMatchMode ( $mode );
		
		$sqlpage = ($page -1)* $this->pageSize;		
		$cl->SetLimits($sqlpage,$this->pageSize);
		
		$res = $cl->Query ( $q, $index );
		
		if ($didyoumean) {
			if (strlen($q) < 64)
				$smarty->assign("suggestions",$this->didYouMean($q,$cl));
		}
		
		// --------------
		
		if ( $res===false )
		{
			print "\tQuery failed: -- please try again later.\n";
			exit;
		} else
		{
			if ( $cl->GetLastWarning() )
				print "\nWARNING: " . $cl->GetLastWarning() . "\n\n";
		
			$this->query_info = "Query '$qo' retrieved ".count($res['matches'])." of $res[total_found] matches in $res[time] sec.\n";
			$this->resultCount = $res['total_found'];
			$this->numberOfPages = ceil($this->resultCount/$this->pageSize);
		}
		
		if (is_array($res["matches"]) ) {
			$this->res = $res;
			$this->ids = array_keys($res["matches"]);
			
			
			$this->where = "gridimage_id IN(".join(",",$this->ids).")";
		
			return $this->ids;
		} else {
			$r = "\t--none--";
		}		
	}

	public function returnUserIds($page = 1) {
		global $CONF;
		$q = $this->q;
		
		require ( "3rdparty/sphinxapi.php" );
		
		$mode = SPH_MATCH_ALL;
		if (strpos($q,'~') === 0) {
			$q = preg_replace('/^\~/','',$q);
			if (substr_count($q,' ') > 1) //over 2 words
				$mode = SPH_MATCH_ANY;
		} elseif (preg_match('/[~\|\(\)@"\/-]/',$q)) {
			$mode = SPH_MATCH_EXTENDED;
		} 
		$index = "user";
		
		$cl = new SphinxClient ();
		$cl->SetServer ( $CONF['sphinx_host'], $CONF['sphinx_port'] );
		$cl->SetWeights ( array ( 100, 1 ) );
		$cl->SetSortMode ( SPH_SORT_EXTENDED, "@relevance DESC, @id DESC" );
		$cl->SetMatchMode ( $mode );
		
		$sqlpage = ($page -1)* $this->pageSize;
		$cl->SetLimits($sqlpage,$this->pageSize);
		
		$res = $cl->Query ( $q, $index );
		
		// --------------
		
		if ( $res===false )
		{
			print "\tQuery failed: -- please try again later.\n";
			exit;
		} else
		{
			if ( $cl->GetLastWarning() )
				print "\nWARNING: " . $cl->GetLastWarning() . "\n\n";
		
			$this->query_info = "Query '$qo' retrieved ".count($res['matches'])." of $res[total_found] matches in $res[time] sec.\n";
			$this->resultCount = $res['total_found'];
			$this->numberOfPages = ceil($this->resultCount/$this->pageSize);
		}
		
		if (is_array($res["matches"]) ) {
			$this->res = $res;
			$this->ids = array_keys($res["matches"]);
			
			
			$this->where = "user_id IN(".join(",",$this->ids).")";
		
			return $this->ids;
		} else {
			$r = "\t--none--";
		}
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
}


?>