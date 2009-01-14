<?
/*
This file is part of miniBB. miniBB is free discussion forums/message board software, without any warranty. See COPYING file for more details. Copyright (C) 2004 Paul Puzyrev, Sergei Larionov. www.minibb.net
*/
@set_time_limit(0);
error_reporting (E_ALL);

include ('./setup_options.php');
include ($pathToFiles.'setup_mysql.php');

/* Forums Replies, Topics count */
if($res=mysql_query("select forum_id from $Tf") and mysql_num_rows($res)>0 and $row=mysql_fetch_row($res)){
do{
$forum=$row[0];

$forumReplies=db_forumReplies($forum,$Tp,$Tf);
$forumTopics=db_forumTopics($forum,$Tt,$Tf);

echo 'Forum '.$forum.' : Replies - '.$forumReplies.' Topics - '.$forumTopics.'<br>';

}
while($row=mysql_fetch_row($res));
}

/* Forums Replies, Topics count */
if($res=mysql_query("select topic_id,topic_status from $Tt") and mysql_num_rows($res)>0 and $row=mysql_fetch_row($res)){
do{
$topic=$row[0];
$sticky=$row[1];
if($sticky==8){
/* Sticky & locked */
mysql_query("update $Tt set topic_status=1 where topic_id=$topic");
mysql_query("update $Tt set sticky=1 where topic_id=$topic");
}
elseif($sticky==9){
mysql_query("update $Tt set topic_status=0 where topic_id=$topic");
mysql_query("update $Tt set sticky=1 where topic_id=$topic");
}

$topicPosts=db_topicPosts($topic,$Tt,$Tp);

echo 'Topic '.$topic.' : Replies - '.$topicPosts.'<br>';

}
while($row=mysql_fetch_row($res));
}

?>
