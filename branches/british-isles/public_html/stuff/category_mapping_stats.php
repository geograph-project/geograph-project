<?php
/**
 * $Project: GeoGraph $
 * $Id: xmas.php 6235 2009-12-24 12:33:07Z barry $
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

$USER->mustHavePerm("basic");





if (empty($db))
	$db = GeographDatabaseConnection(false);


$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;


$smarty->display('_std_begin.tpl');


$title = "Number of categories";
$sql = "SELECT COUNT(*) FROM category_stat";
dump_sql_table($sql,$title);

$title = "Number of categories checked, by number of users having checked the category";
$sql = "select users,count(*) categories,imageclass as `example category` from 
	(select count(distinct user_id) users,imageclass from category_mapping_change where action='checked' group by imageclass order by null) t2 
	group by users with rollup";


$title = "Number of categories actully converted, by number of users having performed the conversion";
$sql = "select users,count(*) categories,sum(images) as images,sum(context) as `images gained context`,sum(subject) as `images gained subject`,imageclass as `example category` from
	(select count(distinct user_id) users,count(*) as images,sum(taglist like '%top:%') as context,sum(taglist like '%subject:%') as subject, imageclass from category_mapping_done group by imageclass order by null) t2 
	group by users with rollup";

print "the blank row at the end is the total acorss all images/users";

$smarty->display('_std_end.tpl');

function dump_sql_table($sql,$title,$autoorderlimit = false) {

        $result = mysql_query($sql.(($autoorderlimit)?" order by count desc limit 25":'')) or die ("Couldn't select photos : $sql " . mysql_error() . "\n");

        $row = mysql_fetch_array($result,MYSQL_ASSOC);
	if (empty($row))
		return;

        print "<H3>$title</H3>";

        print "<TABLE border='1' cellspacing='0' cellpadding='2'><TR>";
        foreach ($row as $key => $value) {
                print "<TH>$key</TH>";
        }
        print "</TR><TR>";
        foreach ($row as $key => $value) {
                print "<TD>$value</TD>";
        }
        print "</TR>";
        while ($row = mysql_fetch_array($result,MYSQL_ASSOC)) {
                print "<TR>";
                foreach ($row as $key => $value) {
                        print "<TD>$value</TD>";
                }
                print "</TR>";
        }
        print "</TR></TABLE>";
}


