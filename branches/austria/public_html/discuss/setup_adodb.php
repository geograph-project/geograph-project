<?php
/*
This file is part of miniBB. miniBB is free discussion forums/message board software, without any warranty. See COPYING file for more details. Copyright (C) 2004 Paul Puzyrev, Sergei Larionov. www.minibb.net
*/

if (!defined('INCLUDED776')) die ('Fatal error.');

$bbdb = GeographDatabaseConnection();
#$bbdb->Execute("SET names 'latin1'"); # FIXME move to GeographDatabaseConnection()
##$minibb_link = @mysql_connect($DBhost, $DBusr, $DBpwd) or die ('<b>Database/configuration error.</b>');
##mysql_query("SET names 'latin1'",$minibb_link);#FIXME error handling
##@mysql_select_db($DBname,$minibb_link) or die ('<b>Database/configuration error (DB is missing).</b>');

function makeLim($page,$numRows,$viewMax){
	$page=pageChk($page,$numRows,$viewMax);
	if(intval($numRows/$viewMax)!=0&&$numRows>0){
		if ($page>0) return ($page*$viewMax).','.$viewMax;
		else return $viewMax;
	}
	else return '';
}

function getByDay($prefix,$table,$field){
	if($table!='') $table.='.';
	if($prefix!='') $prefix=' '.$prefix;
	$xtr2=$prefix.' TO_DAYS(now())-TO_DAYS('.$table.$field.')<'.$GLOBALS['days'];
	return $xtr2;
}

function getClForums($closedForums,$more,$prefix,$field,$syntax,$condition){
	$xtr=$more;
	if($prefix!='') $prefix=$prefix.'.';
	$siz=sizeof($closedForums);
	foreach($closedForums as $c) {
		$xtr.=' '.$prefix.$field.$condition.$c;
		$siz--;
		if ($siz!=0) $xtr.=' '.$syntax;
	}
	return $xtr;
}

function db_simpleSelect($sus,$table='',$fields='',$uniF='',$uniC='',$uniV='',$orderby='',$limit='',$uniF2='',$uniC2='',$uniV2='',$and2=true,$groupBy=''){
	global $bbdb;
	if(!$sus){
		$where='';
		if($uniF!='') $where=' where '.$uniF.$uniC."'".$uniV."'";
		$where = preg_replace('/IN \'(\([\d,]+\))\'/','IN $1',$where);
		if($uniF2!='') {
			$q=(substr_count($uniV2,'.')>0?'':"'");
			$a=($and2?'AND':'where');
			$where.=' '.$a.' '.$uniF2.$uniC2.$q.$uniV2.$q;
		}
		if($limit!='') $limit='limit '.$limit;
		if($orderby!='') $orderby='order by '.$orderby;
		if($groupBy!='') $groupBy='group by '.$groupBy;
		$xtr=(!isset($GLOBALS['xtr'])?'':$GLOBALS['xtr']);
		$sql='SELECT '.$fields.' FROM '.$table.$where.' '.$xtr.' '.$groupBy.' '.$orderby.' '.$limit;

		#$result=mysql_query($sql,$GLOBALS['minibb_link']);
		$result=$bbdb->Execute($sql);
		if($result) {
			$GLOBALS['countRes']=$result->RecordCount();
			#$GLOBALS['countRes']=mysql_num_rows($result);
			$GLOBALS['result']=$result;
		}
	}
	#if(($sus==1||isset($result))&&isset($GLOBALS['countRes'])&&$GLOBALS['countRes']>0)  return mysql_fetch_row($GLOBALS['result']);
	if(($sus==1||isset($result))&&isset($GLOBALS['countRes'])&&$GLOBALS['countRes']>0)  return $GLOBALS['result']->FetchRow();
	elseif($sus==2){
		$a=(strlen($uniF2)?'AND':'');
		$w=(strlen($uniF)||strlen($uniF2)?'WHERE':'');
		$xtr=(isset($GLOBALS['xtr'])?$GLOBALS['xtr']:'');
		#return mysql_result(mysql_query('SELECT '.$fields.' FROM '.$table.' '.$w.' '.$uniF.$uniC.$uniV.' '.$a.' '.$uniF2.$uniC2.$uniV2.' '.$xtr,$GLOBALS['minibb_link']),0);
		return $bbdb->GetOne('SELECT '.$fields.' FROM '.$table.' '.$w.' '.$uniF.$uniC.$uniV.' '.$a.' '.$uniF2.$uniC2.$uniV2.' '.$xtr);
	}
	else return FALSE;
}

function db_searchDeSlice($lsTopics,$id){
	/* Search page/anchor reconstruction */
	if(isset($GLOBALS['xtr'])){
		$xtrT=$GLOBALS['xtr'];
		$GLOBALS['xtr']='';
	}
	else $xtrT='';
	if($lsTopics){
		$GLOBALS['user_sort']+=0;
		if($GLOBALS['user_sort']==1) $i=db_simpleSelect(2,$GLOBALS['Tt'],'count(*)','forum_id','=',$GLOBALS['forum'],'','','topic_id','<=',$id);
		else{
			$GLOBALS['xtr']='AND '.$GLOBALS['Tt'].'.forum_id='.$GLOBALS['forum'].' AND '.$GLOBALS['Tt'].'.topic_last_post_id>'.db_simpleSelect(2,$GLOBALS['Tt'],'topic_last_post_id','topic_id','=',$id);
			$i=db_simpleSelect(2,$GLOBALS['Tt'].','.$GLOBALS['Tp'],'count(*)',$GLOBALS['Tt'].'.topic_last_post_id','=',$GLOBALS['Tp'].'.post_id');
		}
		$GLOBALS['xtr']=$xtrT;
		return intval(($i-1)/$GLOBALS['viewmaxtopic']);
	}
	else{
		$sign=(isset($GLOBALS['themeDesc'])&&in_array($GLOBALS['topic'],$GLOBALS['themeDesc'])?'>=':'<=');
		$i=db_simpleSelect(2,$GLOBALS['Tp'],'count(*)','topic_id','=',$GLOBALS['topic'],'','','post_id',$sign,$id);
		$vmax = ($GLOBALS['forum'] == $CONF['forum_submittedarticles']||$GLOBALS['forum'] == $CONF['forum_gallery'])?10:$GLOBALS['viewmaxreplys'];
		$pageAnchor[0]=intval(($i-1)/$vmax);
		$a=$i-intval($i/$vmax)*$vmax;
		if($i>0&&$a==0) $a=$vmax;
		$pageAnchor[1]='#'.$a;
		$GLOBALS['xtr']=$xtrT;
		return $pageAnchor;
	}
}

function db_searchWithin($w,$f){
	/* Search within forum */
	if($f){
		$and=(strlen($GLOBALS['searchString'])?'AND':'');
		if($w==0) return ' '.$GLOBALS['searchWithin'].' '.$and.' (('.$GLOBALS['searchString'].') OR ('.$GLOBALS['searchString2'].'))';
		elseif($w==1) return ' '.$GLOBALS['searchWithin'].' '.$and.' '.$GLOBALS['searchString'];
		elseif($w==2) return ' '.$GLOBALS['searchWithin'].' '.$and.' '.$GLOBALS['searchString'];
	}
	else{
		if($w==0) return ' AND '.$GLOBALS['Tt'].'.forum_id='.$GLOBALS['Tf'].'.forum_id';
		elseif($w==2) return ' AND '.$GLOBALS['Tt'].'.forum_id='.$GLOBALS['Tf'].'.forum_id';
	}
}

function db_searchMatchGen($column,$i){
	/* Search regular/match whole words */
	if(!$GLOBALS['exact']) return $column." LIKE '%".$GLOBALS['exploded'][$i]."%'";
	else return "( $column LIKE '% ".$GLOBALS['exploded'][$i]."' OR $column LIKE '".$GLOBALS['exploded'][$i]."' OR $column LIKE '".$GLOBALS['exploded'][$i]." %' OR $column LIKE '% ".$GLOBALS['exploded'][$i]." %' )";
}


/*
Because geograph allows nicknames to have apostrophes,
minibb code blows up - this function is for detecting
when strings haven't been escaped and adding appropriate
escapes

input:		noslash		no'escape	with\'escape	with\evil'slash		with\strangeslash
escaped:	noslash		no\'scape	with\\\'escape	with\\evil\'slash	with\\strangeslash
unescaped:	noslash		no'escape	with'escape	withevil'slash		withstrangeslash
reescaped:	noslash		no\'escape	with\'escape	withevil\'slash		withstrangeslash

returns old:	noslash		no\'scape	with\'escape	with\evil'slash		with\strangeslash
returns new:	noslash		no\'scape	with\'escape	with\\evil\'slash	with\\strangeslash

*/
function _smartQuote($input)
{
	global $bbdb;
	#$escaped = mysql_escape_string($input);
	$escaped = substr($bbdb->qstr($input), 1, -1);
	$unescaped = stripslashes($input);
	#$reescaped = mysql_escape_string($unescaped);
	$reescaped = substr($bbdb->qstr($unescaped), 1, -1);
	if ($input === $unescaped || $input !== $reescaped) {
		return $escaped;
	} else {
		return $reescaped;
	}
}


function insertArray($insertArray,$tabh){
	global $bbdb;
	$into='';
	$values='';
	foreach($insertArray as $ia) {
		$iia=$GLOBALS[$ia];
		$into.=$ia.',';
		$values.=($iia==='now()'?$iia.',':"'"._smartQuote($iia)."',");
		#X$values.=($iia=='now()'?$iia.',':_smartQuote($iia).",");
	}
	$into=substr($into,0,strlen($into)-1);
	$values=substr($values,0,strlen($values)-1);
	#$res=mysql_query('insert into '.$tabh.' ('.$into.') values ('.$values.')',$GLOBALS['minibb_link']) or die('<p>'.mysql_error($GLOBALS['minibb_link']).'. Please, try another name or value.');
	#$GLOBALS['insres']=mysql_insert_id($GLOBALS['minibb_link']);
	#return mysql_errno($GLOBALS['minibb_link']);
	$res=$bbdb->Execute('insert into '.$tabh.' ('.$into.') values ('.$values.')') or die('<p>'.$bbdb->ErrorMsg().'. Please, try another name or value.');
	trigger_error('insert into '.$tabh.' ('.$into.') values ('.$values.')', E_USER_WARNING);
	$GLOBALS['insres']=$bbdb->Insert_ID();
	trigger_error(">{$GLOBALS['insres']}<", E_USER_WARNING);
	return $bbdb->ErrorNo(); # FIXME how could that be != 0? why assigning $res?
	
}

function updateArray($updateArray,$tabh,$uniq,$uniqVal){
	global $bbdb;
	$into='';
	foreach($updateArray as $ia) {
		$iia=$GLOBALS[$ia];
		$into.=($iia==='now()'?$ia.'='.$iia.',':$ia."='"._smartQuote($iia)."',");
		#$into.=($iia=='now()'?$ia.'='.$iia.',':$ia."="._smartQuote($iia).",");
	}
	$into=substr($into,0,strlen($into)-1);
	$unupdate=($uniq!=''?' where '.$uniq.'='."'".$uniqVal."'":'');
	#$res=mysql_query('update '.$tabh.' set '.$into.' '.$unupdate,$GLOBALS['minibb_link']) or die('<p>'.mysql_error($GLOBALS['minibb_link']).'. Please, try another name or value.');
	#return mysql_affected_rows($GLOBALS['minibb_link']);
	$res=$bbdb->Execute('update '.$tabh.' set '.$into.' '.$unupdate) or die('<p>'.$bbdb->ErrorMsg().'. Please, try another name or value.');
	return $bbdb->Affected_Rows();
}

function db_delete($table,$uniF='',$uniC='',$uniV='',$uniF2='',$uniC2='',$uniV2=''){
	global $bbdb;
	$where=($uniF!=''?'where '.$uniF.$uniC.$uniV:'');
	if($uniF2!='') {
		$where.=' AND '.$uniF2.$uniC2.$uniV2;
	}
	$sql='DELETE FROM '.$table.' '.$where;
	#$result=mysql_query($sql,$GLOBALS['minibb_link']);
	#if($result) return mysql_affected_rows($GLOBALS['minibb_link']);
	$result=$bbdb->Execute($sql);
	if ($result) return $bbdb->Affected_Rows();
	else return FALSE;
}

function db_ipCheck($thisIp,$thisIpMask,$user_id){
	global $bbdb;
	#$res=mysql_query('select id from '.$GLOBALS['Tb'].' where 
	#	banip='."'".$thisIp."'".' or banip='."'".$thisIpMask[0]."'".' or 
	#	banip='."'".$thisIpMask[1]."'".' or banip='."'".$user_id."'",$GLOBALS['minibb_link']);
	#if($res and mysql_num_rows($res)>0) return TRUE; else return FALSE;
	$res=$bbdb->Execute('select id from '.$GLOBALS['Tb'].' where 
		banip='."'".$thisIp."'".' or banip='."'".$thisIpMask[0]."'".' or 
		banip='."'".$thisIpMask[1]."'".' or banip='."'".$user_id."'");
	if($res and $res->RecordCount()>0) return TRUE; else return FALSE;
}

function db_sendMails($sus,$Tu,$Ts){
	/*User mass emailing*/
	global $bbdb;
	if (!$sus) {
		#$result=mysql_query('SELECT '.$Tu.'.'.$GLOBALS['dbUserSheme']['user_email'][1].' FROM '.$Tu.','.$Ts.' where '.$Ts.'.topic_id='.$GLOBALS['topic'].' and '.$Ts.'.user_id='.$Tu.'.'.$GLOBALS['dbUserId'].' and '.$Ts.'.user_id!='.$GLOBALS['user_id'],$GLOBALS['minibb_link']);
		$result=$bbdb->Execute('SELECT '.$Tu.'.'.$GLOBALS['dbUserSheme']['user_email'][1].' FROM '.$Tu.','.$Ts.' where '.$Ts.'.topic_id='.$GLOBALS['topic'].' and '.$Ts.'.user_id='.$Tu.'.'.$GLOBALS['dbUserId'].' and '.$Ts.'.user_id!='.$GLOBALS['user_id']);
		if ($result) { $GLOBALS['result']=$result; }
	}
	#if($GLOBALS['result']) return $row=mysql_fetch_row($GLOBALS['result']); else return FALSE;
	if($GLOBALS['result']) return $row=$GLOBALS['result']->FetchRow(); else return FALSE;
}

function db_inactiveUsers($sus,$what=''){
	/*Admin - users that didnt any post */
	global $bbdb;
	if(!$sus) {
		if($GLOBALS['makeLim']>0) $GLOBALS['makeLim']='LIMIT '.$GLOBALS['makeLim'];
		#$result=mysql_query('select '.$what.' from '.$GLOBALS['Tu'].' LEFT JOIN '.$GLOBALS['Tp'].' ON '.$GLOBALS['Tu'].'.'.$GLOBALS['dbUserId'].'='.$GLOBALS['Tp'].'.poster_id where '.$GLOBALS['Tp'].'.poster_id IS NULL order by '.$GLOBALS['Tu'].'.'.$GLOBALS['dbUserId'].' '.$GLOBALS['makeLim'],$GLOBALS['minibb_link']);
		$result=$bbdb->Execute('select '.$what.' from '.$GLOBALS['Tu'].' LEFT JOIN '.$GLOBALS['Tp'].' ON '.$GLOBALS['Tu'].'.'.$GLOBALS['dbUserId'].'='.$GLOBALS['Tp'].'.poster_id where '.$GLOBALS['Tp'].'.poster_id IS NULL order by '.$GLOBALS['Tu'].'.'.$GLOBALS['dbUserId'].' '.$GLOBALS['makeLim']);
		if($result) {
			#$GLOBALS['countRes']=mysql_num_rows($result);
			$GLOBALS['countRes']=$result->RecordCount();
			$GLOBALS['result']=$result;
		}
	}
	#if(isset($GLOBALS['countRes']) and $GLOBALS['countRes']>0) return mysql_fetch_row($GLOBALS['result']);
	if(isset($GLOBALS['countRes']) and $GLOBALS['countRes']>0) return $GLOBALS['result']->FetchRow();
	else return FALSE;
}

function db_deadUsers($sus,$less){
	/*Admin-dead users*/
	global $bbdb;
	if(!$sus){
		$GLOBALS['makeLim']=(isset($GLOBALS['makeLim'])&&$GLOBALS['makeLim']>0?'LIMIT '.$GLOBALS['makeLim']:'');
		#$result=mysql_query('select '.$GLOBALS['Tu'].'.'.$GLOBALS['dbUserId'].','.$GLOBALS['Tu'].'.'.$GLOBALS['dbUserSheme']['username'][1].','.$GLOBALS['Tu'].'.'.$GLOBALS['dbUserDate'].','.$GLOBALS['Tu'].'.'.$GLOBALS['dbUserSheme']['user_password'][1].','.$GLOBALS['Tu'].'.'.$GLOBALS['dbUserSheme']['user_email'][1].',max('.$GLOBALS['Tp'].'.post_time) as m from '.$GLOBALS['Tu'].','.$GLOBALS['Tp'].' where '.$GLOBALS['Tu'].'.'.$GLOBALS['dbUserId'].'='.$GLOBALS['Tp'].'.poster_id group by '.$GLOBALS['Tp'].'.poster_id having m<'."'".$less."' ".$GLOBALS['makeLim'],$GLOBALS['minibb_link']);
		$result=$bbdb->Execute('select '.$GLOBALS['Tu'].'.'.$GLOBALS['dbUserId'].','.$GLOBALS['Tu'].'.'.$GLOBALS['dbUserSheme']['username'][1].','.$GLOBALS['Tu'].'.'.$GLOBALS['dbUserDate'].','.$GLOBALS['Tu'].'.'.$GLOBALS['dbUserSheme']['user_password'][1].','.$GLOBALS['Tu'].'.'.$GLOBALS['dbUserSheme']['user_email'][1].',max('.$GLOBALS['Tp'].'.post_time) as m from '.$GLOBALS['Tu'].','.$GLOBALS['Tp'].' where '.$GLOBALS['Tu'].'.'.$GLOBALS['dbUserId'].'='.$GLOBALS['Tp'].'.poster_id group by '.$GLOBALS['Tp'].'.poster_id having m<'."'".$less."' ".$GLOBALS['makeLim']);
		if($result){
			#$GLOBALS['countRes']=mysql_num_rows($result);
			$GLOBALS['countRes']=$result->RecordCount();
			$GLOBALS['result']=$result;
		}
	}
	#if(isset($GLOBALS['countRes']) and $GLOBALS['countRes']>0) return mysql_fetch_row($GLOBALS['result']);
	if(isset($GLOBALS['countRes']) and $GLOBALS['countRes']>0) return $GLOBALS['result']->FetchRow();
	else return FALSE;
}

function db_forumReplies($forum,$Tp,$Tf){
	/* Function to calculate and get forum replies after posting, deleting threads */
	global $bbdb;
	$forumReplies=0;#FIXME ?
	#$forumReplies=mysql_result(mysql_query('select count(*) from '.$Tp.' where forum_id='.$forum,$GLOBALS['minibb_link']),0);
	$forumReplies=$bbdb->GetOne('select count(*) from '.$Tp.' where forum_id='.$forum);
	#mysql_query('update '.$Tf.' set posts_count='."'".$forumReplies."'".' where forum_id='.$forum,$GLOBALS['minibb_link']);
	$bbdb->Execute('update '.$Tf.' set posts_count='."'".$forumReplies."'".' where forum_id='.$forum);
	return $forumReplies;
}

function db_forumTopics($forum,$Tt,$Tf){
	/* Function to calculate and get forum topics after posting, deleting topics */
	global $bbdb;
	$forumTopics=0;
	#$forumTopics=mysql_result(mysql_query('select count(*) from '.$Tt.' where forum_id='.$forum,$GLOBALS['minibb_link']),0);
	$forumTopics=$bbdb->GetOne('select count(*) from '.$Tt.' where forum_id='.$forum);
	#mysql_query('update '.$Tf.' set topics_count='."'".$forumTopics."'".' where forum_id='.$forum,$GLOBALS['minibb_link']);
	$bbdb->Execute('update '.$Tf.' set topics_count='."'".$forumTopics."'".' where forum_id='.$forum);
	return $forumTopics;
}

function db_topicPosts($topic,$Tt,$Tp){
	/* Function to calculate and get forum topics after posting, deleting topics */
	global $bbdb;
	$topicPosts=0;
	#$topicPosts=mysql_result(mysql_query('select count(*) from '.$Tp.' where topic_id='.$topic,$GLOBALS['minibb_link']),0);
	$topicPosts=$bbdb->GetOne('select count(*) from '.$Tp.' where topic_id='.$topic);
	#mysql_query('update '.$Tt.' set posts_count='."'".$topicPosts."'".' where topic_id='.$topic,$GLOBALS['minibb_link']);
	$bbdb->Execute('update '.$Tt.' set posts_count='."'".$topicPosts."'".' where topic_id='.$topic);
	return $topicPosts;
}

function db_lastviewed($topic, $user_id, $postID)
{
	global $bbdb;
	$bbdb->Execute("insert into geobb_lastviewed set topic_id=$topic,user_id=$user_id,last_post_id = $postID on duplicate key update last_post_id = if(last_post_id < $postID,$postID,last_post_id)") or die('<p>'.$bbdb->ErrorMsg().'. Please, try another name or value.');
}

function db_gridreftopics($Tt, $forum, $gridref)
{
	global $bbdb;
	$result=$bbdb->Execute("SELECT topic_id FROM $Tt WHERE forum_id = $forum AND topic_title = ".$bbdb->Quote($gridref));
	$currentgridreftopics = $result->RecordCount();
		
	if ($currentgridreftopics == 1) {
		$action = "vthread";
		$topic = $result->fields[0];
	} else {
		$action = "vtopic";
		$topic=0;
	}
	return array($currentgridreftopics, $action, $topic);
}

?>