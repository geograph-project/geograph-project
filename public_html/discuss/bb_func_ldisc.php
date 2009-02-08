<?php
/*
This file is part of miniBB. miniBB is free discussion forums/message board software, without any warranty. See COPYING file for more details. Copyright (C) 2004 Paul Puzyrev, Sergei Larionov. www.minibb.net
*/
if (!defined('INCLUDED776')) die ('Fatal error.');

if (!isset($user_sort) or $user_sort=='') $user_sort=$sortingTopics; // Sort messages default by last answer (0) desc OR 1 - by last new topics

if(isset($lastOut) and is_array($lastOut)){
    foreach($lastOut as $l){
        if(!in_array($l,$clForums)) $clForums[]=$l;
        if(!isset($clForumsUsers[$l])) $clForumsUsers[$l]=array();
    }
}

if (isset($clForumsUsers)) $closedForums=getAccess($clForums, $clForumsUsers, $user_id); else $closedForums='n';
if ($closedForums!='n') $xtr=getClForums($closedForums,'where','','forum_id','and','!='); else $xtr='';

$lPosts=array();
if ($user_sort==1) $orderBy='topic_id DESC'; else $orderBy='topic_last_post_id DESC';

if($cols=db_simpleSelect(0, $Tt, 'topic_last_post_id','forum_id','!=','5',$orderBy,$viewlastdiscussions,'forum_id','!=','11')){
    do $lPosts[]=$cols[0]; while($cols=db_simpleSelect(1));
}

$xtr1=$xtr;
if(sizeof($lPosts)>0) $xtr=getClForums($lPosts,'where','','post_id','or','='); else $xtr='';

if($row=db_simpleSelect(0, $Tp, 'poster_id, poster_name, post_time, topic_id')) do $pVals[$row[3]]=array($row[0],$row[1],$row[2]); while($row=db_simpleSelect(1));

$xtr=$xtr1;

$list_topics='';

if($cols=db_simpleSelect(0, "$Tt Tt left join geobb_lastviewed Tl on (Tt.topic_id = Tl.topic_id and Tl.user_id = {$USER->user_id})", 'Tt.topic_id, topic_title, topic_poster, topic_poster_name, topic_time, forum_id, posts_count, topic_last_post_id, topic_views, (topic_last_post_id > last_post_id) as isnew, last_post_id','forum_id','!=','5',$orderBy,$viewlastdiscussions,'forum_id','!=','11')){
    
    $i=1;
    $tpl=makeUp('main_last_discuss_cell');
    
    do{
        $forum=$cols[5];
      
      	$topic=$cols[0];
        $topic_views=$cols[8];
        $topic_reverse='';
        if(isset($themeDesc) and in_array($topic,$themeDesc)) $topic_reverse="<img src=\"{$static_url}/img/topic_reverse.gif\" align=middle border=0 alt=\"\">&nbsp;";
        
        if ($cols[9]) {
        	$topic_reverse = "<a href=\"{$indexphp}action=vpost&amp;forum={$forum}&amp;topic={$topic}&amp;post={$cols[10]}\"><img src=\"{$static_url}/img/topic_updated.gif\" align=middle border=0 alt=\"Updated Since Last Visit\" height=\"10\" width=\"10\"></a>&nbsp;";
        } elseif (is_null($cols[9])) {
        	$topic_reverse = "<img src=\"{$static_url}/img/topic_new.gif\" align=middle border=0 alt=\"New Since Last Visit\" height=\"6\" width=\"10\">&nbsp;";
        }
        
        $topic_title=$cols[1];
        if(isset($pVals[$topic][0])) $lastPosterID=$pVals[$topic][0]; else $lastPosterID='N/A';
        if(isset($pVals[$topic][1])) $lastPoster=$pVals[$topic][1]; else $lastPoster='N/A';
        if(isset($pVals[$topic][2])) $lastPostDate=convert_date($pVals[$topic][2]); else $lastPostDate='N/A';
        
        if($cols[3]=='') $cols[3]=$l_anonymous;
        $topicAuthor=$cols[3];
        
        $numReplies=$cols[6]; if($numReplies>=1) $numReplies-=1;
        
        if($topic_title=='') $topic_title=$l_emptyTopic;
        
        if($i>0) $bg='tbCel1'; else $bg='tbCel2';
        
        if(isset($mod_rewrite) and $mod_rewrite) $urlp="{$main_url}/{$forum}_{$topic}_"; else $urlp="{$main_url}/{$indexphp}action=vthread&amp;forum=$forum&amp;topic=$topic&amp;page=";
        $pageNavCell=pageNav(0,$numReplies+1,$urlp,($forum == 6 || $forum == 11)?10:$viewmaxreplys,TRUE);
        
        $whenPosted=convert_date($cols[4]);
        if(trim($cols[1])=='') $cols[1]=$l_emptyTopic;
        
        //Forum icon
        if(isset($fIcon[$forum])) $forumIcon=$fIcon[$forum]; else $forumIcon='default.gif';
        
        if(isset($mod_rewrite) and $mod_rewrite) $linkToTopic="{$main_url}/{$forum}_{$topic}_0.html"; else $linkToTopic="{$main_url}/{$indexphp}action=vthread&amp;forum={$forum}&amp;topic={$topic}";
        
        $list_topics.=ParseTpl($tpl);
        
        $i=-$i;
    }
    while($cols=db_simpleSelect(1));
    unset($result);
}
?>