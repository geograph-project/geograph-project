<?php
/*********************************************************************
    login.php

    Handles staff authentication/logins

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require_once('../main.inc.php');
if(!defined('INCLUDE_DIR')) die('Fatal Error. Kwaheri!');

require_once(INCLUDE_DIR.'class.staff.php');
require_once(INCLUDE_DIR.'class.csrf.php');

$dest = $_SESSION['_staff']['auth']['dest'];
$msg = $_SESSION['_staff']['auth']['msg'];
$msg = $msg?$msg:'Authentication Required';
if($_POST) {
    //$_SESSION['_staff']=array(); #Uncomment to disable login strikes.
    if(($user=Staff::login($_POST['userid'], $_POST['passwd'], $errors))){
        $dest=($dest && (!strstr($dest,'login.php') && !strstr($dest,'ajax.php')))?$dest:'index.php';
        @header("Location: $dest");
        require_once('index.php'); //Just incase header is messed up.
        exit;
    }

    $msg = $errors['err']?$errors['err']:'Invalid login';
}
define("OSTSCPINC",TRUE); //Make includes happy!
include_once(INCLUDE_DIR.'staff/login.tpl.php');

print '<div style="margin:20px;padding:20px;background-color:yellow;border:3px solid red;">You may notice OSTicket now locks different - we have just upgraded to a new version. Please keep a close eye for any issues and let me know immiediatly - THANK YOU!</div>';


print "<p><b><big>If you would be willing to help answer 'contact form' emails, please email <a href=\"mailto:geograph@barryhunter.co.uk\">barry</a>.";
