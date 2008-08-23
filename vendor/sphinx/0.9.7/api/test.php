<?php

//
// $Id: test.php,v 1.17 2007/04/01 21:11:14 shodan Exp $
//

require ( "sphinxapi.php" );

//////////////////////
// parse command line
//////////////////////

// for very old PHP versions, like at my home test server
if ( is_array($argv) && !isset($_SERVER["argv"]) )
	$_SERVER["argv"] = $argv;
unset ( $_SERVER["argv"][0] );

// build query
if ( !is_array($_SERVER["argv"]) || empty($_SERVER["argv"]) )
{
	print ( "Usage: php -f test.php [OPTIONS] query words\n\n" );
	print ( "Options are:\n" );
	print ( "-h, --host <HOST>\tconnect to searchd at host HOST\n" );
	print ( "-p, --port\t\tconnect to searchd at port PORT\n" );
	print ( "-i, --index <IDX>\tsearch through index(es) specified by IDX\n" );
	print ( "-s, --sortby <EXPR>\tsort matches by 'EXPR'\n" );
	print ( "-a, --any\t\tuse 'match any word' matching mode\n" );
	print ( "-b, --boolean\t\tuse 'boolean query' matching mode\n" );
	print ( "-e, --extended\t\tuse 'extended query' matching mode\n" );
	print ( "-f, --filter <ATTR>\tfilter by attribute 'ATTR' (default is 'group_id')\n" );
	print ( "-v, --value <VAL>\tadd VAL to allowed 'group_id' values list\n" );
	print ( "-g, --groupby <EXPR>\tgroup matches by 'EXPR'\n" );
	print ( "-gs, --groupsort <EXPR>\tsort groups by 'EXPR'\n" );
	exit;
}

$args = array();
foreach ( $_SERVER["argv"] as $arg )
	$args[] = $arg;

$q = "";
$mode = SPH_MATCH_ALL;
$host = "localhost";
$port = 3312;
$index = "*";
$groupby = "";
$groupsort = "@group desc";
$filter = "group_id";
$filtervals = array();
for ( $i=0; $i<count($args); $i++ )
{
	$arg = $args[$i];

	if ( $arg=="-h" || $arg=="--host" )				$host = $args[++$i];
	else if ( $arg=="-p" || $arg=="--port" )		$port = (int)$args[++$i];
	else if ( $arg=="-i" || $arg=="--index" )		$index = $args[++$i];
	else if ( $arg=="-s" || $arg=="--sortby" )		$sortby = $args[++$i];
	else if ( $arg=="-a" || $arg=="--any" )			$mode = SPH_MATCH_ANY;
	else if ( $arg=="-b" || $arg=="--boolean" )		$mode = SPH_MATCH_BOOLEAN;
	else if ( $arg=="-e" || $arg=="--extended" )	$mode = SPH_MATCH_EXTENDED;
	else if ( $arg=="-f" || $arg=="--filter" )		$filter = $args[++$i];
	else if ( $arg=="-v" || $arg=="--value" )		$filtervals[] = (int)$args[++$i];
	else if ( $arg=="-g" || $arg=="--groupby" )		$groupby = $args[++$i];
	else if ( $arg=="-gs"|| $arg=="--groupsort" )	$groupsort = $args[++$i];
	else
		$q .= $args[$i] . " ";
}

////////////
// do query
////////////

$cl = new SphinxClient ();
$cl->SetServer ( $host, $port );
$cl->SetWeights ( array ( 100, 1 ) );
$cl->SetMatchMode ( $mode );
if ( count($filtervals) )
	$cl->SetFilter ( $filter, $filtervals );
if ( $groupby )
	$cl->SetGroupBy ( $groupby, SPH_GROUPBY_ATTR, $groupsort );
if ( $sortby )
	$cl->SetSortMode ( SPH_SORT_EXTENDED, $sortby );
$res = $cl->Query ( $q, $index );

////////////////
// print me out
////////////////

if ( $res===false )
{
	print "Query failed: " . $cl->GetLastError() . ".\n";

} else
{
	if ( $cl->GetLastWarning() )
		print "WARNING: " . $cl->GetLastWarning() . "\n\n";

	print "Query '$q' retrieved $res[total] of $res[total_found] matches in $res[time] sec.\n";
	print "Query stats:\n";
	if ( is_array($res["words"]) )
		foreach ( $res["words"] as $word => $info )
			print "    '$word' found $info[hits] times in $info[docs] documents\n";
	print "\n";

	if ( is_array($res["matches"]) )
	{
		$n = 1;
		print "Matches:\n";
		foreach ( $res["matches"] as $doc => $docinfo )
		{
			print "$n. doc_id=$doc, weight=$docinfo[weight]";
			foreach ( $res["attrs"] as $attrname => $attrtype )
			{
				$value = $docinfo["attrs"][$attrname];
				if ( $attrtype==SPH_ATTR_TIMESTAMP )
					$value = date ( "Y-m-d H:i:s", $value );
				print ", $attrname=$value";
			}
			print "\n";
			$n++;
		}
	}
}

//
// $Id: test.php,v 1.17 2007/04/01 21:11:14 shodan Exp $
//

?>