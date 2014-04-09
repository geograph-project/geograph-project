<?php
if(!defined('OSTCLIENTINC')) die('Access Denied!');
$info=array();
if($thisclient && $thisclient->isValid()) {
    $info=array('name'=>$thisclient->getName(),
                'email'=>$thisclient->getEmail(),
                'phone'=>$thisclient->getPhone());
}

$info=($_POST && $errors)?Format::htmlchars($_POST):$info;

if (false && !empty($_GET['user_id']) && $_GET['t'] == hash_hmac('md5',intval($_GET['user_id']), TOKEN_SECRET) ) {
        $loaduser =  db_query('select email,realname from geograph_live.user where user_id = '.intval($_GET['user_id']));
        if ($loaduser && db_num_rows($loaduser)) {
                list($info['email'],$info['name']) = db_fetch_row($loaduser);
        }
}

?>
<h1>Open a New Ticket</h1>
<p>Please fill in the form below to open a new ticket.</p>
<form id="ticketForm" method="post" action="open.php" enctype="multipart/form-data">
  <?php csrf_token(); ?>
  <input type="hidden" name="a" value="open">
  <table width="800" cellpadding="1" cellspacing="0" border="0">
    <tbody>
    <tr>
        <td class="required">Help Topic:</td>
        <td>
            <select id="topicId" name="topicId" onchange=" showTopicHelp(this.options[this.selectedIndex].value); $('#dynamic-form').load('ajax.php/form/help-topic/' + this.value);">
                <option value="" selected="selected">&mdash; Select a Help Topic &mdash;</option>
                <?php
                if($topics=Topic::getPublicHelpTopics()) {
                    foreach($topics as $id =>$name) {
                        echo sprintf('<option value="%d" %s>%s</option>',
                                $id, ($info['topicId']==$id)?'selected="selected"':'', $name);
                    }
                } else { ?>
                    <option value="0" >General Inquiry</option>
                <?php
                } ?>
            </select>
            <font class="error">*&nbsp;<?php echo $errors['topicId']; ?></font>

                <div id="topic11" style="display:none;padding:10px;color:black;border:2px solid red;background-color:yellow;">
                        Geograph is an online collaborative project collecting photos of every corner of the British Isles. <br/><br/>

                        We don't hold contact details for the locations photographed, so it's unlikely we will be able to help.
                </div>
                <div id="topic10" style="display:none;padding:10px;color:black;border:2px solid red;background-color:yellow;">
                        All photos on Geograph are <a href="http://creativecommons.org/licenses/by-sa/2.0/" target="_blank" style="text-decoration:underline" title="View creative commons deed - opens in new window">Creative Commons</a><img style="padding-left:2px;" alt="New Window" title="opens in a new window" src="http://s0.geograph.org.uk/img/newwin.png" width="10" height="10"/> licensed. <br/><br/>

                        So as long as you credit the photographer when you use the image, you can use it for pretty much any purpose. Don't need to ask permission first.<br/><br/>

                        If you want a higher resolution version, or have requirements not met by the Creative Commons licence, you need to contact the photographer. So best to contact them in the first instance, there is a link on the main photo page.
                </div>
                <div id="topic8" style="display:none;padding:10px;color:black;border:2px solid red;background-color:yellow;">
                        Are you <b>sure</b> this is a <b>Geograph</b> related matter? (Geograph is an online project collecting photos of every square kilometre of the Britain and Ireland)
                </div>
                <script type="text/javascript">
                        function showTopicHelp(id) {
                                var needhide = false;
                                for (q=0;q<=30;q++) {
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
<?php
        if (!$thisclient) {
            $uform = UserForm::getUserForm()->getForm($_POST);
            if ($_POST) $uform->isValid();
            $uform->render(false, 'Your Information');
        }
        else { ?>
            <tr><td colspan="2"><hr /></td></tr>
        <tr><td>Email:</td><td><?php echo $thisclient->getEmail(); ?></td></tr>
        <tr><td>Client:</td><td><?php echo $thisclient->getName(); ?></td></tr>
        <?php }
        $tform = TicketForm::getInstance()->getForm($_POST);
        if ($_POST) $tform->isValid();
        $tform->render(false); ?>
    </tbody>
    <tbody id="dynamic-form">
        <?php if ($form) {
            include(CLIENTINC_DIR . 'templates/dynamic-form.tmpl.php');
        } ?>
    </tbody>
    <tbody>
    <?php
    if($cfg && $cfg->isCaptchaEnabled() && (!$thisclient || !$thisclient->isValid())) {
        if($_POST && $errors && !$errors['captcha'])
            $errors['captcha']='Please re-enter the text again';
        ?>
    <tr class="captchaRow">
        <td class="required">CAPTCHA Text:</td>
        <td>
            <span class="captcha"><img src="captcha.php" border="0" align="left"></span>
            &nbsp;&nbsp;
            <input id="captcha" type="text" name="captcha" size="6">
            <em>Enter the text shown on the image.</em>
            <font class="error">*&nbsp;<?php echo $errors['captcha']; ?></font>
        </td>
    </tr>
    <?php
    } ?>
    <tr><td colspan=2>&nbsp;</td></tr>
    </tbody>
  </table>

If you are writing in relation to a particular image or images, please don't forget to mention which!<br/>
Ideally copy & paste the page address (URL) of the photo page(s). <br/>
Example: <tt>http://www.geograph.org.uk/photo/1234</tt><br/><br/>

  <p style="padding-left:150px;">
        <input type="submit" value="Create Ticket">
        <input type="reset" value="Reset">
        <input type="button" value="Cancel" onClick='window.location.href="index.php"'>

	<? if (!empty($_GET['ref'])) {
	        echo "<input type=hidden name=\"ref\" value=\"".htmlentities($_GET['ref'])."\"/>";
	} ?>
	<? if (!empty($_GET['user_id'])) {
	        echo "<input type=hidden name=\"user_id\" value=\"".intval($_GET['user_id'])."\"/>";
	} ?>
  </p>
</form>
