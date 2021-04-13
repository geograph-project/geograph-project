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

$USER->mustHavePerm("tagsmod");


if (empty($db))
	$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;


$smarty->display('_std_begin.tpl');

if (!empty($_POST['old']) && !empty($_POST['new'])) {
	$sql = "UPDATE category_mapping SET subject = ".$db->quote($_POST['new'])." WHERE  subject = ".$db->quote($_POST['old']);
	print "$sql<br>";
	$db->Execute($sql);
	print "Rows affected: ".$db->Affected_Rows()."<hr>";
}


$subject = $db->getOne("select subject from category_mapping left join subjects using (subject) where subjects.subject is null and subject != '' group by subject");

if (empty($subject))
	die("no more rows to fix!");

?>

        <form method=post>
		Old subject <input type=text name=old value="<? echo htmlentities($subject); ?>" readonly size=60><br>
                Choose offical subject: <select name="new" required><option></option>
                        <?
                        foreach ($db->getCol("SELECT subject FROM subjects ORDER BY subject") as $value) {
                                print "<option>$value</option>";
                        } ?>
                </select><br><br>

		<input type=submit>
	</form>
