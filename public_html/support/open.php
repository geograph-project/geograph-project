<?php
/*********************************************************************
    open.php

    New tickets handle.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2010 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
    $Id: $
**********************************************************************/
require('client.inc.php');
define('SOURCE','Web'); //Ticket source.
$inc='open.inc.php';    //default include.
$errors=array();
if($_POST):

if ($_POST['subject'] == 'Victoria Supper Saloon Hope St') {
	die("STOP! Just stop already.");
}

    $_POST['deptId']=$_POST['emailId']=0; //Just Making sure we don't accept crap...only topicId is expected.
    if(!$thisuser && $cfg->enableCaptcha()){
        if(!$_POST['captcha'])
            $errors['captcha']='Enter text shown on the image';
        elseif(strcmp($_SESSION['captcha'],md5($_POST['captcha'])))
            $errors['captcha']='Invalid - try again!';
    }
	if (!empty($_POST['ref'])) {
		$_POST['message'].="\n\n-------------------------------\n";
		$_POST['message'].="Referring page: ".$_POST['ref']."\n";
		if (!empty($_POST['user_id'])) {
			$_POST['message'].="User profile: http://{$_SERVER['HTTP_HOST']}/profile/".intval($_POST['user_id'])."\n";
		}
		$_POST['message'].="Browser: ".$_SERVER['HTTP_USER_AGENT']."\n";
	}
    //Ticket::create...checks for errors..
    if(($ticket=Ticket::create($_POST,$errors,SOURCE))){
        $msg='Support ticket request created';
        if($thisclient && $thisclient->isValid()) //Logged in...simply view the newly created ticket.
            @header('Location: tickets.php?id='.$ticket->getExtId());
        //Thank the user and promise speedy resolution!
        $inc='thankyou.inc.php';

        if ($_POST['topicId'] == 13) {
                header('Location: /ask.php?done&id='.$ticket->getExtId());
                exit;
        }

    }else{
        $errors['err']=$errors['err']?$errors['err']:'Unable to create a ticket. Please correct errors below and try again!';
    }
endif;

//page
require(CLIENTINC_DIR.'header.inc.php');
require(CLIENTINC_DIR.$inc);
require(CLIENTINC_DIR.'footer.inc.php');
?>
