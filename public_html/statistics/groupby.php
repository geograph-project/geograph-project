<?php
/**
 * $Project: GeoGraph $
 * $Id: busyday.php 3514 2007-07-10 21:09:55Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Barry Hunter (geo@barryhunter.co.uk)
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

require_once('geograph/global.inc.php');
init_session();




$smarty = new GeographPage;

$smarty->caching = 2; // lifetime is per cache
$smarty->cache_lifetime = 3600*24; //24hr cache

if (!$CONF['sphinx_host']) {

	header("HTTP/1.1 503 Service Unavailable");
	$smarty->display('function_disabled.tpl');
	exit;
}


if (isset($_GET['output']) && $_GET['output'] == 'csv') {
	$template='statistics_table_csv.tpl';
	# let the browser know what's coming
	header("Content-type: application/octet-stream");
	header("Content-Disposition: attachment; filename=\"".basename($_SERVER['SCRIPT_NAME'],'.php').".csv\"");
} else {
	$template='statistics_groupby.tpl';
}



$cacheid='statistics|groupby';

$options = array(
	'submitted' 	=> array('name'=>'Day Submitted',		'select'=>0,'groupby'=>1,'distinct'=>0,'filter'=>'YYYY-MM-DD'),
	'submitted_month' 	=> array('name'=>'Month Submitted',	'select'=>0,'groupby'=>1,'distinct'=>0,'filter'=>'YYYY-MM'),
	'submitted_year' 	=> array('name'=>'Year Submitted',	'select'=>0,'groupby'=>1,'distinct'=>0,'filter'=>'YYYY'),
	'takendays' 	=> array('name'=>'Day Taken',			'select'=>0,'groupby'=>1,'distinct'=>1,'filter'=>'YYYY-MM-DD'),
	'takendays_month' 	=> array('name'=>'Month Taken',		'select'=>0,'groupby'=>1,'distinct'=>0,'filter'=>'YYYY-MM'),
	'takendays_year' 	=> array('name'=>'Year Taken',		'select'=>0,'groupby'=>1,'distinct'=>0,'filter'=>'YYYY'),
	'auser_id' 	=> array('name'=>'Contributor',			'select'=>0,'groupby'=>1,'distinct'=>1,'filter'=>'Example: <tt>'.($USER->user_id?$USER->user_id:rand(1,1000)).'</tt>'),
	'classcrc' 	=> array('name'=>'Category',			'select'=>0,'groupby'=>1,'distinct'=>1,'filter'=>'Example: [<tt>River</tt>] (case sensitive)'),
	'amyriad' 	=> array('name'=>'Myriad',			'select'=>0,'groupby'=>1,'distinct'=>1,'filter'=>'XX or X'),
	'ahectad' 	=> array('name'=>'Hectad',			'select'=>0,'groupby'=>1,'distinct'=>1,'filter'=>'XX99 or X99'),
	'agridsquare' 	=> array('name'=>'Grid Square',			'select'=>0,'groupby'=>1,'distinct'=>1,'filter'=>'XX9999 or X9999'),
	'scenti' 	=> array('name'=>'Centisquare',			'select'=>0,'groupby'=>'0','distinct'=>1,'filter'=>0 && 'XX999999 or X999999'),
);

$smarty->assign_by_ref('options', $options);   

$cacheid .= '|'.md5(serialize($_GET));


if (!$smarty->is_cached($template, $cacheid))
{
	$db=GeographDatabaseConnection(true);

	$filters = array();
	
	$title = "Geograph Breakdown mk2";
	
	$smarty->assign('groupby', $groupby = (isset($_GET['groupby']) && preg_match('/^\w+$/' , $_GET['groupby']) && $options[$_GET['groupby']])?$_GET['groupby']:'submitted');
	$smarty->assign('distinct', $distinct = (isset($_GET['distinct']) && preg_match('/^\w+$/' , $_GET['distinct']) && $options[$_GET['distinct']])?$_GET['distinct']:'');

	if (!empty($_GET['filter'])) {
		foreach ($_GET['filter'] as $key => $value) {
			if (!empty($options[$key]) && !empty($value) && preg_match('/^[\w -]+$/' , $value)) {
				$options[$key]['filtervalue'] = $value;
				$filters[str_replace(array('_month','_year'),'',$key)] = encode_option($key,$value);
			}
		}
	}
	
	if (empty($filters['scenti'])) {//todo, also disable when select an of the other GRs too?
		$ri = (isset($_GET['ri']) && is_numeric($_GET['ri']))?intval($_GET['ri']):0;
		
		if ($ri) {
			$smarty->assign('ri',$ri);
			$filters['scenti'] = array($ri * 10000000,(($ri+1) * 10000000)-1); //scenti is a shortcut to avoid indexing ri on its own. 
		}
	}
	
	if ($groupby) {
		$table = array();
	
		$sphinx = new sphinxwrapper($_GET['q']);
		if (!empty($sphinx->qclean)) {
			$smarty->assign('q',$sphinx->qclean);
		}
		$client = $sphinx->_getClient();
		
		$sphinx->pageSize = $pgsize = 100;
		$pg = 1;
		
		$sphinx->sort = "@id ASC"; //this is the WITHIN GROUP ordering (which we dont care about in this case)
		$is_date = false;
		
		$overall_sort = '@count DESC';
		if ($distinct) {
			$overall_sort = '@distinct DESC';
		}
		
		
		if ($groupby != ($groupby2 = str_replace('_year','',$groupby))) {
		
			$groupby2 = str_replace('takendays','aimagetaken',$groupby2); //TODO aimagetaken doesnt work before 1970!!!!
			$sphinx->SetGroupBy($groupby2,SPH_GROUPBY_YEAR,$overall_sort);
			$is_date = true;
		
		} elseif ($groupby != ($groupby2 = str_replace('_month','',$groupby))) {
			
			$groupby2 = str_replace('takendays','aimagetaken',$groupby2);  //TODO aimagetaken doesnt work before 1970!!!!
			$sphinx->SetGroupBy($groupby2,SPH_GROUPBY_MONTH,$overall_sort); 
			$is_date = true;
		
		} elseif ($groupby == 'submitted') { //we dont use GROUPBY_DAY with takendays as it prevents working before 1970
			
			$sphinx->SetGroupBy($groupby2,SPH_GROUPBY_DAY,$overall_sort);  
			$is_date = true;
		
		} else {
		
			$sphinx->SetGroupBy($groupby,SPH_GROUPBY_ATTR,$overall_sort);
		}
		
		if ($distinct) {
			$sphinx->SetGroupDistinct ( $distinct );
		}
		
		if (count($filters)) {
			$sphinx->addFilters($filters);
		}
		
		$res = $sphinx->groupByQuery($pg,'_images');
		
		if ($res && $res['matches']) {
			if ($groupby == 'auser_id') {
				$ids = array();
				foreach ($res['matches'] as $id => $row) {
					$ids[] = $row['attrs']['user_id'];
				}
				$users = $db->getAssoc("SELECT user_id,realname FROM user WHERE user_id IN (".implode(',',$ids).") ORDER BY NULL");
			
			}
			foreach ($res['matches'] as $id => $row) {
				$a = array();
				if ($groupby == 'auser_id' && $template=='statistics_groupby.tpl') {
					$a['User'] = "<a href=\"/profile/{$row['attrs']['auser_id']}\">".htmlentities($users[$row['attrs']['auser_id']]).'</a>';
				} elseif (0 && $is_date) { 
					$a['Date'] = date('d/m/Y',strtotime($row['attrs']['@groupby']));
				} else {
					$a[$options[$groupby]['name']] = decode_option($groupby,$row['attrs']['@groupby']);
				}
				if ($distinct) { 
					$a[$options[$distinct]['name'].'s'] = $row['attrs']['@distinct'];
				}
				$a['Images'] = $row['attrs']['@count'];
				$table[] = $a;
			}
		}
		
		
		#print "<pre>";
		#print_r($table);
		
		#print_r($res);
		#print_r($client);
		#exit;
		$smarty->assign("headnote",'This is just a preview, more options will be added soon.');
	
		$smarty->assign('footnote',$sphinx->query_info);
	}
	
	$smarty->assign_by_ref('table', $table);
	
	$smarty->assign("h2title",$title);
	$smarty->assign("total",count($table));
	$smarty->assign_by_ref('references',$CONF['references_all']);	
} 

$smarty->assign("filter",1);
#$smarty->assign("nosort",1);
$smarty->display($template, $cacheid);

	
function encode_option($option,$value) {
	global $db;
	$value = trim($value);
	switch ($option) {
		#case 'date...':	//todo - convert to timestamp RANGE
		case 'submitted_year':
			$days = date('t',strtotime("$value-12-01"));
			return array(strtotime("$value-01-01"), strtotime("$value-12-$days 23:59:59"));
			
		case 'submitted_month':
			$days = date('t',strtotime("$value-01"));
			return array(strtotime("$value-01"), strtotime("$value-$days 23:59:59"));
			
		case 'submitted':
			$day = 60*60*25;
			return array(strtotime($value),strtotime($value)+$day-1);		
				
		case 'takendays_year':
			$days = date('t',strtotime("$value-12-01"));
			return array($db->getOne("select to_days('$value-01-01')"), $db->getOne("select to_days('$value-12-$days')"));
			
		case 'takendays_month':
			$days = date('t',strtotime("$value-01"));
			return array($db->getOne("select to_days('$value-01')"), $db->getOne("select to_days('$value-$days')"));
			
		case 'takendays':
			//$value = strtotime($value); (imagetaken is mapped to takendays)
			
			$db->getOne("select to_days('$value')"); break;
			
			break;
		
		case 'agridsquare':
		case 'amyriad':
		case 'ahectad':
			$value = $db->getOne("select conv('$value',36,10)"); break;
		case 'classcrc':
			$value = $db->getOne("select crc32(lower('$value'))"); break;
		case 'scenti':
			//todo - get eastings/northings out of GR then use
			# (gi.reference_index * 10000000 + IF(natgrlen+0 <= 3,(nateastings DIV 100) * 100 + natnorthings DIV 100),0) AS scenti, \
			break;
	}
	return array(intval($value));
}
	
function decode_option($option,$value) {
	global $db;
	switch ($option) {
		#case 'date...': //handled externally
		#case 'user_id': //handled externally
		case 'takendays':
			$value = $db->getOne("select from_days('$value')"); break;
		case 'agridsquare':
		case 'amyriad':
		case 'ahectad':
			$value = $db->getOne("select conv('$value',10,36)"); break;
		case 'classcrc':
			$value = $db->getOne("select imageclass from category_stat where category_id = $value"); break; //category_id is now the CRC32 of the imageclass
		case 'scenti':
			//todo - get eastings/northings out and convert to GR
			break;
	}
	return $value;
}


