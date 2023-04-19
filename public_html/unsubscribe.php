<?php
/**
 * $Project: GeoGraph $
 * $Id: staticpage.php 6962 2010-12-09 14:56:48Z geograph $
 *
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Paul Dixon (paul@elphin.com)
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


$smarty->display("_std_begin.tpl");

print "<h2>Email Subscription - Preference</h2>";

        $token=new Token;

        if (!$token->parse($_GET['t']) || !$token->hasValue("id")) {
                die("Invalid URL");
        }


        $user_id = intval($token->getValue("id"));
        $t = $token->getToken();


if (isset($_GET['sub'])) {

        $db = GeographDatabaseConnection(false);

        if (empty($_GET['sub'])) {
                $value = "no";
                print "<p>You have been unsubcribed from further mailings. Thank you</p>";
                print "<p><a href=\"?t=$t&sub=1\">Click here to re-subscribe</a></p>";
        } else {
                $value = ""; //the default is yes!
                print "<p>You have been re-subscribed to further mailings. Thank you</p>";
                print "<p><a href=\"?t=$t&sub=0\">Click here to unsubscribe</a></p>";
        }

        if (isset($value)) {
                $key = 'mailing';
                $values = "value=".$db->Quote($value);

                $db->Execute($sql = "INSERT INTO user_preference SET user_id={$user_id},created=NOW(),pkey = ".$db->Quote($key).",$values
                                ON DUPLICATE KEY UPDATE $values,changes=changes+1");

        }
} else {

        print "<p><a href=\"?t=$t&sub=1\">Click here to subscribe</a></p>";
        print "<p><a href=\"?t=$t&sub=0\">Click here to unsubscribe</a></p>";
}


$smarty->display("_std_end.tpl");

