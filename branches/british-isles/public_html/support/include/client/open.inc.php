<?php
if(!defined('OSTCLIENTINC')) die('Kwaheri rafiki!'); //Say bye to our friend..

$info=($_POST && $errors)?Format::input($_POST):array(); //on error...use the post data

if (!empty($_GET['user_id']) && $_GET['t'] == hash_hmac('md5', intval($_GET['user_id']), TOKEN_SECRET) ) { 

	$loaduser =  db_query('select email,realname from geograph_live.user where user_id = '.intval($_GET['user_id']));
	if ($loaduser && db_num_rows($loaduser)) {
		list($info['email'],$info['name']) = db_fetch_row($loaduser);
	}} 

?>
<div>
    <?if($errors['err']) {?>
        <p align="center" id="errormessage"><?=$errors['err']?></p>
    <?}elseif($msg) {?>
        <p align="center" id="infomessage"><?=$msg?></p>
    <?}elseif($warn) {?>
        <p id="warnmessage"><?=$warn?></p>
    <?}?>
</div>
<div>Please fill in the form below to open a new ticket.</div><br>
<form action="open.php" method="POST" enctype="multipart/form-data">
<table align="left" cellpadding=2 cellspacing=1 width="90%">
    <tr>
        <th width="20%">Full Name:</th>
        <td>
            <?if ($thisclient && ($name=$thisclient->getName())) {
                ?>
                <input type="hidden" name="name" value="<?=$name?>"><?=$name?>
            <?}else {?>
                <input type="text" name="name" size="25" value="<?=$info['name']?>">
	        <?}?>
            &nbsp;<font class="error">*&nbsp;<?=$errors['name']?></font>
        </td>
    </tr>
    <tr>
        <th nowrap >Email Address:</th>
        <td>
            <?if ($thisclient && ($email=$thisclient->getEmail())) {
                ?>
                <input type="hidden" name="email" size="25" value="<?=$email?>"><?=$email?>
            <?}else {?>             
                <input type="text" name="email" size="25" value="<?=$info['email']?>">
            <?}?>
            &nbsp;<font class="error">*&nbsp;<?=$errors['email']?></font>
        </td>
    </tr>
    <!--tr>
        <td>Telephone:</td>
        <td><input type="text" name="phone" size="25" value="<?=$info['phone']?>">
             &nbsp;Ext&nbsp;<input type="text" name="phone_ext" size="6" value="<?=$info['phone_ext']?>">
            &nbsp;<font class="error">&nbsp;<?=$errors['phone']?></font></td>
    </tr-->
    <tr height=2px><td align="left" colspan=2 >&nbsp;</td></tr>
<? if (empty($_GET['user_id'])) { ?>
    <tr>
        <th valign="top">Anti-Spam:</th>
        <td>
	<img src="http://s0.geograph.org.uk/templates/basic/img/logo.gif" align="right">
        <input size="40" id="spam" name="spam" value=""/><br/>
        Please enter at least one word from our project website logo (duplicated on the right)

        </td>
    </tr>
<? } ?>
    <tr>
        <th valign="top">Help Topic:</th>
        <td>
            <select name="topicId" onchange="showTopicHelp(this.options[this.selectedIndex].value);">
                <option value="" selected >Select One</option>
                <?
                 $services= db_query('SELECT topic_id,topic FROM '.TOPIC_TABLE.' WHERE isactive=1 AND topic_id != 13 ORDER BY (topic like \'Other%\'),topic');
                 if($services && db_num_rows($services)) {
                     while (list($topicId,$topic) = db_fetch_row($services)){
                        $selected = ($info['topicId']==$topicId)?'selected':''; ?>
                        <option value="<?=$topicId?>"<?=$selected?>><?=$topic?></option>
                        <?
                     }
                 }else{?>
                    <option value="0" >General Inquiry</option>
                <?}?>
            </select>
            &nbsp;<font class="error">*&nbsp;<?=$errors['topicId']?></font>
		<div id="topic11" style="display:none;padding:10px;color:black;border:2px solid red;background-color:yellow;">
			Geograph is an online collaborative project collecting photos of every corner of the British Isles. <br/><br/>

			We don't hold contact details for the locations photographed, so it's unlikely we will be able to help. <br/><br/>

			<a href="javascript:void(showContact())">Click here if still want to contact the Geograph team</a>
		</div>
		<div id="topic10" style="display:none;padding:10px;color:black;border:2px solid red;background-color:yellow;">
			All photos on Geograph are <a href="http://creativecommons.org/licenses/by-sa/2.0/" target="_blank" 
style="text-decoration:underline" title="View creative commons deed - opens in new window">Creative Commons</a><img style="padding-left:2px;" alt="New 
Window" title="opens in a new window" src="http://s0.geograph.org.uk/img/newwin.png" width="10" height="10"/> licensed. <br/><br/>

			So as long as you credit the photographer when you use the image, you can use it for pretty much any purpose. <br/><br/>

			If you want a higher resolution version, or have requirements not met by the Creative Commons licence, you need to contact the photographer. So best to contact them in the first instance, there is a link on the main photo page. <br/><br/>

			<a href="javascript:void(showContact())">Click here if still want to contact the Geograph team</a>
		</div>
                <div id="topic8" style="display:none;padding:10px;color:black;border:2px solid red;background-color:yellow;">
			Are you <b>sure</b> this is a <b>Geograph</b> related matter? (Geograph is an online project collecting photos of every square kilometre of the Britain and Ireland)<br/><br/>

			<a href="javascript:void(showContact())">Click here if still want to contact the Geograph team</a>
		</div>
		<script type="text/javascript">
			function showTopicHelp(id) {
				var needhide = false;
				for (q=1;q<=15;q++) {
					if (document.getElementById("topic"+q)) {
						document.getElementById("topic"+q).style.display=(id==q)?'':'none';
						if (id==q)
							needhide = 1;
					}
				}
				var rows = document.getElementsByTagName("tr");
				for (var i = 0; i < rows.length; i++) {
					if (rows[i].className && rows[i].className == 'tohide')
						 rows[i].style.display=(needhide)?'none':'';
				}
			}
			function showContact() {
				var rows = document.getElementsByTagName("tr");
				for (var i = 0; i < rows.length; i++) {
					if (rows[i].className && rows[i].className == 'tohide')
						 rows[i].style.display='';
				}
			}
		</script>
        </td>
    </tr>
    <tr class="tohide">
        <th>Subject:</th>
        <td>
            <input type="text" name="subject" size="35" value="<?=$info['subject']?>">
            &nbsp;<font class="error">*&nbsp;<?=$errors['subject']?></font>
        </td>
    </tr>
    <tr class="tohide">
        <th valign="top">Message:</th>
        <td>
            <? if($errors['message']) {?> <font class="error"><b>&nbsp;<?=$errors['message']?></b></font><br/><?}?>
            <textarea name="message" cols="35" rows="8" wrap="soft" style="width:85%"><?=$info['message']?></textarea><br/>

If you are writing in relation to a particular image or images, please don't forget to mention which!<br/>
Ideally copy & paste the page address (URL) of the photo page(s). <br/>
Example: <tt>http://www.geograph.org.uk/photo/1234</tt><br/><br/>


	</td>
    </tr>
    <?
    if($cfg->allowPriorityChange() ) {
      $sql='SELECT priority_id,priority_desc FROM '.TICKET_PRIORITY_TABLE.' WHERE ispublic=1 ORDER BY priority_urgency DESC';
      if(($priorities=db_query($sql)) && db_num_rows($priorities)){ ?>
      <tr>
        <td>Priority:</td>
        <td>
            <select name="pri">
              <?
                $info['pri']=$info['pri']?$info['pri']:$cfg->getDefaultPriorityId(); //use system's default priority.
                while($row=db_fetch_array($priorities)){ ?>
                    <option value="<?=$row['priority_id']?>" <?=$info['pri']==$row['priority_id']?'selected':''?> ><?=$row['priority_desc']?></option>
              <?}?>
            </select>
        </td>
       </tr>
    <? }
    }?>

    <?if(($cfg->allowOnlineAttachments() && !$cfg->allowAttachmentsOnlogin())  
                || ($cfg->allowAttachmentsOnlogin() && ($thisclient && $thisclient->isValid()))){
        
        ?>
    <tr class="tohide">
        <td>Attachment:</td>
        <td>
            <input type="file" name="attachment"><font class="error">&nbsp;<?=$errors['attachment']?></font>
        </td>
    </tr>
    <?}?>
    <?if($cfg && $cfg->enableCaptcha() && (!$thisclient || !$thisclient->isValid())) {
        if($_POST && $errors && !$errors['captcha'])
            $errors['captcha']='Please re-enter the text again';
        ?>
    <tr class="tohide">
        <th valign="top">Captcha Text:</th>
        <td><img src="captcha.php" border="0" align="left">
        <span>&nbsp;&nbsp;<input type="text" name="captcha" size="7" value="">&nbsp;<i>Enter the text shown on the image.</i></span><br/>
                <font class="error">&nbsp;<?=$errors['captcha']?></font>
        </td>
    </tr>
    <? } ?>
    <tr height=2px><td align="left" colspan=2 >&nbsp;</td</tr>
    <tr class="tohide">
        <td></td>
        <td>
            <input class="button" type="submit" name="submit_x" value="Submit Ticket">
            <input class="button" type="reset" value="Reset">
            <input class="button" type="button" name="cancel" value="Cancel" onClick='window.location.href="index.php"'>    
<? if (!empty($_GET['ref'])) {
	echo "<input type=hidden name=\"ref\" value=\"".htmlentities($_GET['ref'])."\"/>";
} ?>
<? if (!empty($_GET['user_id'])) {
	echo "<input type=hidden name=\"user_id\" value=\"".intval($_GET['user_id'])."\"/>";
} ?>
        </td>
    </tr>
</table>
</form>
