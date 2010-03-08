<?php
/**
 * $Project: GeoGraph $
 * $Id$
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005  Barry Hunter (geo@barryhunter.co.uk)
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
* Provides the SearchEngine class
*
* @package Geograph
* @author Barry Hunter <geo@barryhunter.co.uk>
* @version $Revision$
*/


/**
* SearchEngine
*
* 
* @package Geograph
*/
class SearchEngine
{
	var $db=null;
	
	var $query_id;

	/**
	* criteria object
	*/
	var $criteria;
	
	/**
	* array of GridImage's
	*/
	var $results;
	
	var $resultCount = 0;
	var $numberOfPages;
	var $currentPage;

	var $page = "search.php";
	var $searchuse = "search";

	/**
	* true if a where cluase is in effect
	*/	
	var $islimited = false;
	
	var $errormsg;
	
	//don't use the cached version of exercute
	var $noCache = false;
	
	//only run the count section of exercute
	var $countOnly = false;
	
	/**
	 * constructor
	 * @access public
	 */
	function SearchEngine($query_id = '')
	{
		if (is_numeric($query_id)) {
	
			$db=$this->_getDB(10);
			
			$tries = 0;
			$query = array();
			while (!count($query) && $tries < 10) {
				$query = $db->GetRow("SELECT *,crt_timestamp+0 as crt_timestamp_ts FROM queries WHERE id = $query_id LIMIT 1");
				if (!count($query)) {
					$query = $db->GetRow("SELECT *,crt_timestamp+0 as crt_timestamp_ts FROM queries_archive WHERE id = $query_id LIMIT 1");
				}
				if (!count($query)) {
					if ($db->readonly && $tries < 9) {
						if ($tries == 8) {
							//try swapping back to the master connection
							$db=$this->_getDB(false);
						}
						sleep(2); //give time for replication to catch up
						$tries++;
					} else {
						return false;
					}
				}
			}
			
			$this->query_id = $query_id;
			
			$classname = "SearchCriteria_".$query['searchclass'];
			$this->criteria = new $classname($query['q']);
			
			#if ($query['searchclass'] == "Special")	{
			#	$query['searchq'] = stripslashes($query['searchq']);
			#}

			$this->criteria->_initFromArray($query);
		} 
	} 
	
	/**
	 * count how many images in this saved 'marked list'
	 * @access public
	 */
	function getMarkedCount() {
		if ($this->query_id && $this->criteria->searchclass == 'Special' && $this->criteria->searchq == "inner join gridimage_query using (gridimage_id) where query_id = $this->query_id") {
			$db=$this->_getDB(true);
			return $db->getOne("SELECT COUNT(*) FROM gridimage_query WHERE query_id = ?",$this->query_id);
		}
	}

	function checkExplain($sql) {
		$bad = 0;

		if (strpos($sql,'gridimage_group') === FALSE) {
			return;
		}

		$db=$this->_getDB(true);
		global $ADODB_FETCH_MODE;
		$oldmode = $ADODB_FETCH_MODE;
		$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
		$explain = $db->getAll("EXPLAIN $sql");
		foreach ($explain as $i => $row) {
			if ($row['type'] == 'ALL') {
				//let this pass for NOW
			} elseif ($row['rows'] == 0) {
			
			} elseif (empty($row['key']) || is_null($row['key'])) {
				$bad = 1;
			} elseif ($row['rows'] > 20000 && !empty($row['Extra']) && $row['key'] != 'label') {
				$bad = 1;
			}
		}
		
		if ($bad) {
			unset($this->criteria->db);
                	ob_start();
        	        debug_print_backtrace();
			print "\n\nHost: ".`hostname`."\n\n";
			print_r($this->criteria);
	                print_r($explain);
        	        $con = ob_get_clean();
        	        mail('geograph@barryhunter.co.uk','[Geograph Search Quota] '.date('r'),$con);
		
			global $smarty,$USER;
			header("HTTP/1.1 503 Service Unavailable");
			$smarty->assign('searchq',stripslashes($_GET['q']));
			$smarty->display('search_unavailable.tpl');
			exit;
		}
		$ADODB_FETCH_MODE = $oldmode;
	}
	
	/**
	 * run a search via the gridimage table
	 * @access private
	 */
	function ExecuteReturnRecordset($pg,$extra_fields = '') 
	{
		global $CONF;
		$db=$this->_getDB(true);
		
		$this->criteria->getSQLParts();
		extract($this->criteria->sql,EXTR_PREFIX_ALL^EXTR_REFS,'sql');
		
		$this->currentPage = $pg;
	
		$pgsize = $this->criteria->resultsperpage;
	
		if (!$pgsize) {$pgsize = 15;}
		if ($pg == '' or $pg < 1) {$pg = 1;}
	
		$page = ($pg -1)* $pgsize;
	
	
		if (empty($_GET['legacy']) && empty($_SESSION['legacy']) && !empty($CONF['sphinx_host']) && 
			isset($this->criteria->sphinx) && 
			(strlen($this->criteria->sphinx['query']) || !empty($this->criteria->sphinx['d']) || !empty($this->criteria->sphinx['bbox']) || !empty($this->criteria->sphinx['filters']))
			&& $this->criteria->sphinx['impossible'] == 0) {
			$this->noCache = 1;
			return $this->ExecuteSphinxRecordSet($pg);
		} elseif ($this->criteria->sphinx['no_legacy']) {
			//oh dear, no point even trying :(
			$this->resultCount = 0;
			$this->error = "Impossible Search";
			return 0; 
		}
	
		if (!empty($this->criteria->searchtext) && !empty($GLOBALS['smarty']) && !empty($CONF['sphinx_host'])) { 
			//this really should have been turned over to sphinx
			header("HTTP/1.1 503 Service Unavailable");
			$GLOBALS['smarty']->assign('searchq',stripslashes($_GET['q']));
			$GLOBALS['smarty']->assign('temp',1);
			$GLOBALS['smarty']->display('function_disabled.tpl');
			
			ob_start();
			print "\n\nHost: ".`hostname`."\n\n";
			if (!empty($GLOBALS['USER']->user_id)) {
				print "User: {$GLOBALS['USER']->user_id} [{$GLOBALS['USER']->realname}]\n";
			}
			unset($this->criteria->db);
			print_r($this->criteria);
			print_r($_SERVER);
			$con = ob_get_clean();
			mail('geograph@barryhunter.co.uk','[Geograph Disabled] '.$this->criteria->searchdesc,$con);
			
			exit;
		}
		
	
		if (preg_match("/(left |inner |)join ([\w\,\(\) \.\'!=`]+) where/i",$sql_where,$matches)) {
			$sql_where = preg_replace("/(left |inner |)join ([\w\,\(\) \.!=\'`]+) where/i",'',$sql_where);
			$sql_from .= " {$matches[1]} join {$matches[2]}";
		}
		
		//need to ensure rejected/pending images arent shown
		if (empty($sql_where)) {
			$sql_where = " moderation_status in ('accepted','geograph')";
		} else {
			$this->islimited = true;
			if (strpos($sql_where,'moderation_status') === FALSE) 
				$sql_where = " moderation_status in ('accepted','geograph') and $sql_where";
		}
		
		if (!empty($_GET['safe'])) {
			$this->upper_limit = max(0,$db->getOne("SELECT MIN(gridimage_id) FROM gridimage WHERE moderation_status = 'pending'"));
			if ($this->upper_limit>1) {
				if (!empty($sql_where)) {
					$sql_where .= " AND ";
				}
				$sql_where .= " gi.gridimage_id < {$this->upper_limit}";
			}
		}
		
		$sql_from = str_replace('gridimage_query using (gridimage_id)','gridimage_query on (gi.gridimage_id = gridimage_query.gridimage_id)',$sql_from);
		
		if ($pg > 1 || $CONF['search_count_first_page'] || $this->countOnly) {
			$resultCount = $db->getOne("select `count` from queries_count where id = {$this->query_id}");
			if ($resultCount) {
				$this->resultCount = $resultCount;
			} else {
				$count_from = (strpos($sql_where,'gs.') !== FALSE || strpos($sql_from,'gs.') !== FALSE)?"INNER JOIN gridsquare AS gs USING(gridsquare_id)":'';
				$count_from .= (strpos($sql_where,'user.') !== FALSE || strpos($sql_from,'user.') !== FALSE)?" INNER JOIN user ON(gi.user_id=user.user_id)":'';

				// construct the count query sql
				if (preg_match("/group by ([\w\,\(\)\/ ]+)/i",$sql_where,$matches)) {
					$sql_where2 = preg_replace("/group by ([\w\,\(\)\/ ]+)/i",'',$sql_where);
					$sql = "SELECT count(DISTINCT {$matches[1]}) FROM gridimage AS gi $count_from $sql_from WHERE $sql_where2";
				} else {
					$sql = "SELECT count(*) FROM gridimage AS gi $count_from $sql_from WHERE $sql_where";
				}
				if (!empty($_GET['debug'])) {
					print "<BR><BR>$sql";
		                        if ($_GET['debug'] > 5)
                		                exit;
		                }

				$this->checkExplain($sql);
				$doneexplain = 1;

				$this->resultCount = $db->CacheGetOne(3600,$sql);
				if (empty($_GET['BBOX']) && $this->display != 'reveal') {
					if ($db->readonly) {
						$db2=$this->_getDB(false); //get a read/write connection
					} else {
						$db2=&$db;
					}
					$db2->Execute("replace into queries_count set id = {$this->query_id},`count` = {$this->resultCount}");
				}
			}
			$this->numberOfPages = ceil($this->resultCount/$pgsize);
		} 
		if ($this->countOnly
			|| ( ($pg > 1 || $CONF['search_count_first_page']) && !$this->resultCount)
			|| ( ($this->numberOfPages) && ($pg > $this->numberOfPages) ) 
			)
			return 0;
		
		if ($sql_order)
			$sql_order = "ORDER BY $sql_order";
	// construct the query sql
$sql = <<<END
SELECT gi.*,x,y,gs.grid_reference,gi.realname as credit_realname,if(gi.realname!='',gi.realname,user.realname) as realname $sql_fields $extra_fields
FROM gridimage AS gi INNER JOIN gridsquare AS gs USING(gridsquare_id)
	INNER JOIN user ON(gi.user_id=user.user_id)
	$sql_from
WHERE $sql_where
$sql_order
LIMIT $page,$pgsize
END;
		if (!empty($_GET['debug'])) {
			print "<BR><BR>$sql";
			if ($_GET['debug'] > 5)
				exit;
		}
		if (!isset($doneexplain)) {
			$this->checkExplain($sql);
		}
		
		list($usec, $sec) = explode(' ',microtime());
		$querytime_before = ((float)$usec + (float)$sec);
				
		$recordSet = &$db->Execute($sql);
		
		list($usec, $sec) = explode(' ',microtime());
		$querytime_after = ((float)$usec + (float)$sec);
						
		$this->querytime =  $querytime_after - $querytime_before;

		if ($pg == 1 && !$CONF['search_count_first_page']) {
			$count = $db->getOne("select `count` from queries_count where id = {$this->query_id}");
			if ($count) {
				$this->resultCount = $count;
				$this->numberOfPages = ceil($this->resultCount/$pgsize);
			} elseif (!empty($recordSet)) {
				$this->resultCount = $recordSet->RecordCount();
				if ($this->resultCount == $pgsize) {
					$this->numberOfPages = 2;
					$this->pageOneOnly = 1;
				} else {
					$this->numberOfPages = ceil($this->resultCount/$pgsize);
					if (empty($_GET['BBOX']) && $this->display != 'reveal') {
						$db=$this->_getDB(false); //'upgrade' to a read/write connection
						$db->Execute("replace into queries_count set id = {$this->query_id},`count` = {$this->resultCount}");
					}
				}
			} else {
				$this->resultCount = 0;
				$this->numberOfPages = 0;
				$this->pageOneOnly = 1;
			}
		}

		return $recordSet;
	}

	/**
	 * run a standard search via sphinxsearch index
	 * NOTE: $this->criteria->getSQLParts(...) needs to have been called before this function to populate sphinx criteria
	 * @access private
	 */
	function ExecuteSphinxRecordSet($pg) {
		global $CONF;
		$db=$this->_getDB(true);
		
		extract($this->criteria->sql,EXTR_PREFIX_ALL^EXTR_REFS,'sql');
		
		$sphinx = new sphinxwrapper($this->criteria->sphinx['query']);

		$this->fullText = 1;

		$sphinx->pageSize = $this->criteria->resultsperpage+0;

		$page = ($pg -1)* $sphinx->pageSize;
		if ($page > 1000) { //todo - hard-coded 1000 needs autodetecting!
			//lets jump to the last page of 'past results'
			$pg = intval(ceil(1000/$sphinx->pageSize));
			$this->currentPage = $pg;
		}

	//look for suggestions - this needs to be done before the filters are added - the same filters wont work on the gaz index
		if (isset($GLOBALS['smarty'])) {
		
			$suggestions = array();
			if (empty($this->countOnly) && $sphinx->q && strlen($sphinx->q) < 64 && empty($this->criteria->sphinx['x']) ) {
				$suggestions = $sphinx->didYouMean($sphinx->q);
			} elseif (
					$this->criteria->searchclass == 'Placename' 
					&& (empty($this->criteria->searchtext) || ($this->criteria->searchq == $this->criteria->searchtext) )
					&& isset($GLOBALS['smarty'])
				) {
				$suggestions = array(array(
					'query'=>$this->criteria->searchq,
					'gr'=>'(anywhere)',
					'localities'=>'as text search'
					));
			}
			if (!empty($this->criteria->searchtext)) {

				if (is_numeric($this->criteria->searchtext)) {

					require_once('geograph/gridsquare.class.php');
					require_once('geograph/gridimage.class.php');

					$image=new GridImage();
					$image->loadFromId($this->criteria->searchtext);

					if ($image->isValid() && ( 
						($image->moderation_status!='rejected' && $image->moderation_status!='pending')
						|| $image->user_id == $GLOBALS['USER']->user_id
					) ) {
						$suggestions += array(array(
							'link'=>"/photo/{$image->gridimage_id}",
							'query'=>htmlentities2($image->title),
							'gr'=>'(anywhere)',
							'localities'=>"Image by ".htmlentities($image->realname).", ID: {$image->gridimage_id}"
							));
					}
				} else {
					require_once("3rdparty/spellchecker.class.php");

					$correction = SpellChecker::Correct($this->criteria->searchtext);

					if (strcasecmp($correction,$this->criteria->searchtext) != 0 && levenshtein($correction,$this->criteria->searchtext) < 0.25*strlen($correction)) {

						$suggestions += array(array(
							'query'=>$correction,
							'gr'=>'(anywhere)',
							'localities'=>'spelling suggestion'
							));
					}
				}
			} 
			if (!empty($suggestions) && count($suggestions)) {
				$GLOBALS['smarty']->assign("suggestions",$suggestions);
			}
		}

	//setup the sphinx wrapper 
		if (!empty($this->criteria->sphinx['sort'])) {
			$sphinx->setSort($this->criteria->sphinx['sort']);
		}
		if (!empty($this->criteria->sphinx['groupby'])) {
			$sphinx->setGroupBy($this->criteria->sphinx['groupby'][0],$this->criteria->sphinx['groupby'][1],$this->criteria->sphinx['groupby'][2]);
		}
		if (empty($this->criteria->sphinx['sort']) || $this->criteria->sphinx['sort'] == '@relevance DESC, @id DESC') {
			if (preg_match('/\w+/',preg_replace('/(@\w+ |\w+:)\w+/','',$this->criteria->sphinx['query']))) {
				$this->criteria->searchdesc = str_replace('undefined','relevance',$this->criteria->searchdesc);
			} elseif (strlen($this->criteria->sphinx['query'])) {
				#$this->criteria->searchdesc = str_replace(', in undefined order','',$this->criteria->searchdesc);
			}
		}

		if (!empty($this->criteria->sphinx['d']) || !empty($this->criteria->sphinx['bbox'])) {
			$sphinx->setSpatial($this->criteria->sphinx);
		}

		//this step is handled internally by search and setSpatial
		//$sphinx->processQuery();

		if (!empty($CONF['fetch_on_demand'])) {
			$sphinx->upper_limit = $db->getOne("SELECT MAX(gridimage_id) FROM gridimage_search");
		}
		if (!empty($_GET['safe'])) {
			$sphinx->upper_limit = max(0,$db->getOne("SELECT MIN(gridimage_id)-1 FROM gridimage WHERE moderation_status = 'pending'"));
		}

		if (is_array($this->criteria->sphinx['filters']) && count($this->criteria->sphinx['filters'])) {
			$sphinx->addFilters($this->criteria->sphinx['filters']);
		}
		
	//run the sphinx search
		$ids = $sphinx->returnIds($pg,'_images');

		$this->resultCount = $sphinx->resultCount;
		$this->numberOfPages = $sphinx->numberOfPages;
		$this->maxResults = $sphinx->maxResults;

		$this->islimited = true;

		if (isset($GLOBALS['smarty']) && !empty($sphinx->res['words']) && (count($sphinx->res['words']) > 1 || !$this->resultCount)) {
			$GLOBALS['smarty']->assign("statistics",$sphinx->res['words']);
		} 


		if ($this->countOnly || !$this->resultCount) {
			if (!empty($sphinx->query_error)) {
				$this->error = $sphinx->query_error;
			}
			if (!empty($sphinx->query_info)) {
				$this->info = $sphinx->query_info;
			}
			return 0;
		}
		$this->orderList = $ids;
		
		if ($sql_order == ' dist_sqd ') {
			$this->sphinx_matches = $sphinx->res['matches'];
			$sql_fields = ',-1 as dist_sqd' ;
		} 

	// fetch from database
		$id_list = implode(',',$ids);
		if ($this->noCache) {
$sql = <<<END
SELECT gi.*,x,y,gs.grid_reference,gi.realname as credit_realname,if(gi.realname!='',gi.realname,user.realname) as realname $sql_fields
FROM gridimage AS gi INNER JOIN gridsquare AS gs USING(gridsquare_id)
	INNER JOIN user ON(gi.user_id=user.user_id)
WHERE gi.gridimage_id IN ($id_list)
END;
		} else {
			$sql = "SELECT gi.* $sql_fields FROM gridimage_search as gi WHERE gridimage_id IN ($id_list)";
		}
		
		if (!empty($_GET['debug'])) {
			print "<BR><BR>{$sphinx->q}<BR><BR>$sql";
                        if ($_GET['debug'] > 5)
                                exit;
                }


		list($usec, $sec) = explode(' ',microtime());
		$querytime_before = ((float)$usec + (float)$sec);

		$recordSet = &$db->Execute($sql);

		list($usec, $sec) = explode(' ',microtime());
		$querytime_after = ((float)$usec + (float)$sec);


		if ($this->display == 'excerpt') {
			$docs = array();
			foreach ($ids as $c => $id) {
				$row = $rows[$id];
				$docs[$c] = strip_tags(preg_replace('/<i>.*?<\/i>/',' ',$row['post_text']));
			}
			$reply = $sphinx->BuildExcerpts($docs, 'gi_stemmmed', $sphinx->q);	
		}

		$this->querytime = ($querytime_after - $querytime_before) + $sphinx->query_time;
		
		
	//finish off
		if (!empty($recordSet) && empty($_GET['BBOX']) && $this->display != 'reveal') {
			$db=$this->_getDB(false); //'upgrade' to a read/write connection
			$db->Execute("replace into queries_count set id = {$this->query_id},`count` = {$this->resultCount}");
		}

		return $recordSet;
	}

	/**
	 * run a standard search via the gridimage_search table (but will redirect to sphinx if possible)
	 * @access private
	 */
	function ExecuteCachedReturnRecordset($pg) 
	{
		global $CONF;
		$db=$this->_getDB(true);
		
		$this->criteria->getSQLParts();
		extract($this->criteria->sql,EXTR_PREFIX_ALL^EXTR_REFS,'sql');
		
		$this->currentPage = $pg;
	
		$pgsize = $this->criteria->resultsperpage;
	
		if (!$pgsize) {$pgsize = 15;}
		if ($pg == '' or $pg < 1) {$pg = 1;}
	
		$page = ($pg -1)* $pgsize;

		if (strpos($sql_where,'gs') !== FALSE) {
			$sql_where = str_replace('gs.','gi.',$sql_where);
		}
		$sql_fields = str_replace('gs.','gi.',$sql_fields);
	
		###################
		# run_via_sphinx
		if (empty($_GET['legacy']) && empty($_SESSION['legacy']) && !empty($CONF['sphinx_host']) && 
			isset($this->criteria->sphinx) && 
			(strlen($this->criteria->sphinx['query']) || !empty($this->criteria->sphinx['d']) || !empty($this->criteria->sphinx['bbox']) || !empty($this->criteria->sphinx['filters']))
			&& $this->criteria->sphinx['impossible'] == 0) {
			
			return $this->ExecuteSphinxRecordSet($pg);
		} elseif ($this->criteria->sphinx['no_legacy']) {
			//oh dear, no point even trying :(
			$this->resultCount = 0;
			$this->error = "Impossible Search";
			return 0; 
		}
		# /run_via_sphinx
		###################

                if (!empty($this->criteria->searchtext) && !empty($GLOBALS['smarty']) && !empty($CONF['sphinx_host'])) {
                        //this really should have been turned over to sphinx
                        header("HTTP/1.1 503 Service Unavailable");
                        $GLOBALS['smarty']->assign('searchq',stripslashes($_GET['q']));
			$GLOBALS['smarty']->assign('temp',1);
                        $GLOBALS['smarty']->display('function_disabled.tpl');

                        ob_start();
                        print "\n\nHost: ".`hostname`."\n\n";
                        if (!empty($GLOBALS['USER']->user_id)) {
                                print "User: {$GLOBALS['USER']->user_id} [{$GLOBALS['USER']->realname}]\n";
                        }
                        unset($this->criteria->db);
                        print_r($this->criteria);
                        print_r($_SERVER);
                        $con = ob_get_clean();
                        mail('geograph@barryhunter.co.uk','[Geograph Disabled] '.$this->criteria->searchdesc,$con);

                        exit;
                }


	
		//look for suggestions - this needs to be done before the filters are added - the same filters wont work on the gaz index
		if ($this->criteria->searchclass == 'Placename' && isset($GLOBALS['smarty']) && (empty($this->criteria->searchtext) || ($this->criteria->searchq == $this->criteria->searchtext) )) {
			$GLOBALS['smarty']->assign("suggestions",array(array(
				'query'=>$this->criteria->searchq,
				'gr'=>'(anywhere)',
				'localities'=>'as text search'
				) ));
		} elseif ($this->criteria->searchclass == 'Special' && preg_match('/labeled \[([\w ]+)\], in grid reference (\w+)/',$this->criteria->searchdesc,$m) && isset($GLOBALS['smarty'])) {
		
			$suggestions = array(array(
				'link'=>"/search.php?q=".urlencode($m[1])."+near+{$m[2]}",
				'query'=>$m[1],
				'name'=>$m[2],
				'localities'=>'as text search'
				) );
			$GLOBALS['smarty']->assign_by_ref("suggestions",$suggestions);

		}

		if (!empty($_GET['safe'])) {
			$this->upper_limit = max(0,$db->getOne("SELECT MIN(gridimage_id) FROM gridimage WHERE moderation_status = 'pending'"));
			if ($this->upper_limit>1) {
				if (!empty($sql_where)) {
					$sql_where .= " AND ";
				}
				$sql_where .= " gi.gridimage_id < {$this->upper_limit}";
			}
		}
		
		if (!empty($sql_where)) {
			$sql_where = "WHERE $sql_where";
			$this->islimited = true;
		} elseif (preg_match('/rand\(/',$sql_order)) {
			//homefully temporally
			dieUnderHighLoad(0,'search_unavailable.tpl');
		}
		
		if (preg_match("/(left |inner |)join ([\w\,\(\) \.\'!=`]+) where/i",$sql_where,$matches)) {
			$sql_where = preg_replace("/(left |inner |)join ([\w\,\(\) \.!=\'`]+) where/i",'',$sql_where);
			$sql_from .= " {$matches[1]} join {$matches[2]}";
		}
		
		if ($pg > 1 || $CONF['search_count_first_page'] || $this->countOnly) {
			$resultCount = $db->getOne("select `count` from queries_count where id = {$this->query_id}");
			if ($resultCount) {
				$this->resultCount = $resultCount;
			} else {
				// construct the count sql
				if (preg_match("/group by ([\w\,\(\)\/ ]+)/i",$sql_where,$matches)) {
					$sql_where2 = preg_replace("/group by ([\w\,\(\)\/ ]+)/i",'',$sql_where);
					if ($matches[1] == 'gridimage_id') {
						$matches[1] = 'gi.gridimage_id';
					}
					$sql = "SELECT count(DISTINCT {$matches[1]}) FROM gridimage_search as gi $sql_from $sql_where2";
				} else {
					$sql = "SELECT count(*) FROM gridimage_search as gi $sql_from $sql_where";
				}
				if (!empty($_GET['debug'])) {
					print "<BR><BR>$sql";
		                        if ($_GET['debug'] > 5)
                		                exit;
                		}

                                $this->checkExplain($sql);
                                $doneexplain = 1;

				$this->resultCount = $db->CacheGetOne(3600,$sql);
				if (empty($_GET['BBOX']) && $this->display != 'reveal') {
					if ($db->readonly) {
						$db2=$this->_getDB(false); //get a read/write connection
					} else {
						$db2=&$db;
					}
					$db2->Execute("replace into queries_count set id = {$this->query_id},`count` = {$this->resultCount}");
				}
			}
			$this->numberOfPages = ceil($this->resultCount/$pgsize);
		}
		if ($this->countOnly
			|| ( ($pg > 1 || $CONF['search_count_first_page']) && !$this->resultCount)
			|| ( ($this->numberOfPages) && ($pg > $this->numberOfPages) ) 
			)
			return 0;
			
		if ($sql_order)
			$sql_order = "ORDER BY $sql_order";
	// construct the query sql
$sql = <<<END
SELECT gi.* $sql_fields
FROM gridimage_search as gi $sql_from
$sql_where
$sql_order
LIMIT $page,$pgsize
END;
		if (!empty($_GET['debug'])) {
			print "<BR><BR>$sql";
                        if ($_GET['debug'] > 5)
                                exit;
                }

                if (!isset($doneexplain)) {
                        $this->checkExplain($sql);
                }
		
		list($usec, $sec) = explode(' ',microtime());
		$querytime_before = ((float)$usec + (float)$sec);
				
		$recordSet = &$db->Execute($sql);
				
		list($usec, $sec) = explode(' ',microtime());
		$querytime_after = ((float)$usec + (float)$sec);
		
		$this->querytime =  $querytime_after - $querytime_before;
		
		if ($pg == 1 && !$CONF['search_count_first_page']) {
			$count = $db->getOne("select `count` from queries_count where id = {$this->query_id}");
			if ($count) {
				$this->resultCount = $count;
				$this->numberOfPages = ceil($this->resultCount/$pgsize);
			} elseif (!empty($recordSet)) {
				$this->resultCount = $recordSet->RecordCount();
				if ($this->resultCount == $pgsize) {
					$this->numberOfPages = 2;
					$this->pageOneOnly = 1;
				} else {
					$this->numberOfPages = ceil($this->resultCount/$pgsize);
					if (empty($_GET['BBOX']) && $this->display != 'reveal') {
						$db=$this->_getDB(false); //'upgrade' to a read/write connection
						$db->Execute("replace into queries_count set id = {$this->query_id},`count` = {$this->resultCount}");
					}
				}
			} else {
				$this->resultCount = 0;
				$this->numberOfPages = 0;
				$this->pageOneOnly = 1;
			}
		}
		
		return $recordSet;
	}
	
	/**
	 * run a standard search and return the raw database recordset
	 * @access public
	 */
	function ReturnRecordset($pg,$nocache = false) {
		if ($nocache || $this->noCache || ($this->criteria->searchclass == 'Special' && preg_match('/(gs|gi|user)\.(grid_reference|)/',$this->criteria->searchq,$m)) && !$m[2]) {
			//a Special Search needs full access to GridImage/GridSquare/User
			$recordSet =& $this->ExecuteReturnRecordset($pg);
		} else {
			$recordSet =& $this->ExecuteCachedReturnRecordset($pg); 
		}
		return $recordSet;
	}
		
	/**
	 * run a standard search and populate $this->results with GridImages
	 * @access public
	 */
	function Execute($pg) 
	{
		if ($this->noCache || ($this->criteria->searchclass == 'Special' && preg_match('/(gs|gi|user)\.(grid_reference|)/',$this->criteria->searchq,$m)) && !$m[2]) {
			//a Special Search needs full access to GridImage/GridSquare/User
			$recordSet =& $this->ExecuteReturnRecordset($pg);
		} else {
			$recordSet =& $this->ExecuteCachedReturnRecordset($pg); 
		}
		
		if (!empty($this->error)) {
			ob_start();
			print "\n\nHost: ".`hostname`."\n\n";
			if (!empty($this->info)) {
				print "Info: {$this->info}\n";
			}
			if (!empty($this->error)) {
				print "Error: {$this->error}\n";
			}
			if (!empty($GLOBALS['USER']->user_id)) {
				print "User: {$GLOBALS['USER']->user_id} [{$GLOBALS['USER']->realname}]\n";
			}
			unset($this->criteria->db);
			print_r($this->criteria);
			print_r($_SERVER);
			$con = ob_get_clean();
			mail('geograph@barryhunter.co.uk','[Geograph '.$this->error.'] '.$this->criteria->searchdesc,$con);
		}
		
		//we dont actully want to process anything
		if ($this->countOnly)
			return 0;
			
		if ($recordSet)	{
			$dist_format = ($this->criteria->searchclass == 'Postcode')?"Dist:%dkm %s":"Dist:%.1fkm %s";

			$this->results=array();
			$i=0;

			$showtaken = ($this->criteria->limit7 || preg_match('/^imagetaken/',$this->criteria->orderby));

			while (!$recordSet->EOF) 
			{
				$this->results[$i]=new GridImage;
				$this->results[$i]->fastInit($recordSet->fields);

				$this->results[$i]->dist_string = '';
				if (!empty($recordSet->fields['dist_sqd'])) {
					$angle = rad2deg(atan2( $recordSet->fields['x']-$this->criteria->x, $recordSet->fields['y']-$this->criteria->y ));
					
					if ($recordSet->fields['dist_sqd'] == -1) {
						$d = $this->sphinx_matches[$this->results[$i]->gridimage_id]['attrs']['@geodist']/1000;
					} else {
						$d = sqrt($recordSet->fields['dist_sqd']);
					}
					if ($d >= 0.1) {
						$this->results[$i]->dist_string = sprintf($dist_format,$d,heading_string($angle));
					} 
				}
				if (empty($this->results[$i]->title))
					$this->results[$i]->title="Untitled";

				//if we searching on imageclass then theres no point displaying it...
				if ($this->criteria->limit3) 
					unset($this->results[$i]->imageclass);

				//if we searching on taken date then display it...
				if ($showtaken) 
					$this->results[$i]->imagetakenString = getFormattedDate($this->results[$i]->imagetaken);

				$recordSet->MoveNext();
				$i++;
			}
			$recordSet->Close(); 
			$this->numberofimages = $i;
			
			if (!empty($this->orderList)) {
				if (!empty($_GET['debug']))
					print "REORDERING";
				
				//well we need to reorder...
				$lookup = array();
				foreach ($this->results as $gridimage_id => $image) {
					$lookup[$image->gridimage_id] = $gridimage_id;
				}
				$newlist = array();
				foreach ($this->orderList as $id) {
					if (!empty( $this->results[$lookup[$id]]))
						$newlist[] = $this->results[$lookup[$id]];
				}
				$this->results = $newlist;
			}
			
			if (!$i && $this->resultCount) {
				$pgsize = $this->criteria->resultsperpage;

				if (!$pgsize) {$pgsize = 15;}
				
				$lastPage = ($this->resultCount -1)* $pgsize;
			
				if ($this->currentPage < $lastPage) {
					if (empty($_GET['BBOX']) && $this->display != 'reveal') {
						$db=$this->_getDB(false); //'upgrade' to a read/write connection
						$db->Execute("replace into queries_count set id = {$this->query_id},`count` = 0");
					}
					$this->resultCount = 0;
				}
			}
		} else 
			return 0;
			
		return $this->querytime;
	}
	
	/**
	 * finds the current displayclass
	 * @access public
	 */
	function getDisplayclass() {
		return $this->criteria->displayclass;
	}
	
	/**
	 * applies a new display class to this search
	 * @access public
	 */
	function setDisplayclass($di) {
		global $USER;
		$db=$this->_getDB(false);
		
		if ($this->query_id) {
			$db->Execute("update queries set displayclass = ".$db->Quote($di)." where id = {$this->query_id} and user_id = {$USER->user_id}");
			$this->criteria->displayclass = $di;
		}
	}
	
	/**
	 * returns html for paging
	 * note: it caches so can be called multiple times easily
	 * @access public
	 */
	function pagesString($postfix = '',$extrahtml ='') {
		static $r;
		if (!empty($r))
			return($r);
		if (isset($this->temp_displayclass)) {
			$postfix .= "&amp;displayclass=".$this->temp_displayclass;
		}
		if (!empty($_GET['legacy'])) { //todo - technically a bodge!
			$postfix .= "&amp;legacy=true";
		}
		if (!empty($_GET['safe'])) {
			$postfix .= "&amp;safe=true";
		}
		
		if ($this->currentPage > 1) 
			$r .= "<a href=\"/{$this->page}?i={$this->query_id}&amp;page=".($this->currentPage-1)."$postfix\"$extrahtml>&lt; &lt; prev</a> ";
		$start = max(1,$this->currentPage-5);
		$endr = min($this->numberOfPages+1,$this->currentPage+8);
		
		if ($start > 1)
			$r .= "<a href=\"/{$this->page}?i={$this->query_id}&amp;page=1$postfix\"$extrahtml>1</a> ... ";

		for($index = $start;$index<$endr;$index++) {
			if ($index == $this->currentPage && !$this->countOnly) 
				$r .= "<b>$index</b> "; 
			else
				$r .= "<a href=\"/{$this->page}?i={$this->query_id}&amp;page=$index$postfix\"$extrahtml>$index</a> ";
		}
		if ($endr < $this->numberOfPages+1 || $this->pageOneOnly) 
			$r .= "... ";
			
		if ( ($this->numberOfPages > $this->currentPage || $this->pageOneOnly ) && !$this->countOnly) 
			$r .= "<a href=\"/{$this->page}?i={$this->query_id}&amp;page=".($this->currentPage+1)."$postfix\"$extrahtml>next &gt;&gt;</a> ";
	
		if ( $this->fullText && empty($_GET['legacy']) && $this->currentPage < $this->numberOfPages && $this->resultCount <= $this->maxResults ) 
			$r .= "<a href=\"/{$this->page}?i={$this->query_id}&amp;page=".($this->numberOfPages)."$postfix\"$extrahtml>last</a> ";
		return $r;	
	}
	
	
	/**
	* return true if instance references a valid search
	*/
	function isValid()
	{
		return isset($this->criteria);
	}

	/**
	* assign members from recordset containing required members
	*/
	function loadFromRecordset(&$rs)
	{
		$this->_clear();
		$this->_initFromArray($rs->fields);
		return $this->isValid();
	}

	/**
	 * clear all member vars
	 * @access private
	 */
	function _clear()
	{
		$vars=get_object_vars($this);
		foreach($vars as $name=>$val)
		{
			if ($name!="db")
				unset($this->$name);
		}
	}
	
	/**
	* assign members from array containing required members
	*/
	function _initFromArray(&$arr)
	{
		foreach($arr as $name=>$value)
		{
			if (!is_numeric($name))
				$this->$name=$value;
		}
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

	/**
	 * set stored db object
	 * @access private
	 */
	function _setDB(&$db)
	{
		$this->db=$db;
	}

	function _trace($msg)
	{
		echo "$msg<br/>";
		flush();
	}	
	function _err($msg)
	{
		echo "<p><b>Error:</b> $msg</p>";
		flush();
	}
	
	
	/**
	* store error message
	*/
	function _error($msg)
	{
		$this->errormsg=$msg;
	}
	
}



?>
