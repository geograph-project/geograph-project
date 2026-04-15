<?php

require_once('geograph/global.inc.php');
init_session();

pageMustBeHTTPS();

//equivilent to dieUnderHighLoad();
if (!empty($CONF['readonly'])) {
       include __DIR__."/offline-readonly.inc.php";
}

$smarty = new GeographPage;

$USER->mustHavePerm('basic');

if ($_SERVER["REQUEST_METHOD"] === "POST" && empty($_POST['email'])) { //aovid a login request!

    $response = [];

    foreach($_POST['title'] as $gridimage_id => $value) {

        $image=new GridImage;
        $image->loadFromId(intval($gridimage_id));

        $isowner=($image->user_id==$USER->user_id)?1:0;
        $isadmin=(!$isowner && $USER->hasPerm('ticketmod'))?1:0;

        if ($image->isValid()) {
            if ($image->moderation_status=='rejected') {
                if ($isowner||$isadmin) {
                    //ok, we'll let it lie...
                } else {
                    header("Location: /photo/{$_REQUEST['id']}");
                    exit;
                }
            }

            $ticket=new GridImageTroubleTicket();

            $ticket->setSuggester($USER->user_id, $USER->realname);
            //$ticket->setModerator($USER->user_id);
            $ticket->setPublic('everyone');
            $ticket->setImage($image->gridimage_id);
            $ticket->setType('minor');
            $ticket->setNotify(''); //dont notify the suggestor (althoguh immidate closed tickets dont use this!)
            $ticket->setNotes("Automatic - recording changes applied directly");

            //we only support unmoderated fields!
            $ticket->updateField("title", $image->title, $_POST['title'][$image->gridimage_id], false);
            $ticket->updateField("comment", $image->comment, $_POST['comment'][$image->gridimage_id], false);

            if (!empty($ticket->changes) && count($ticket->changes)) {
                $response[$image->gridimage_id]['status'] = $ticket->commit();

                //clear any caches involving this photo
                $ab=floor($image->gridimage_id/10000);
                $smarty->clear_cache(null, "img$ab|{$image->gridimage_id}");

                //clear user specific stuff like profile page
                $ab=floor($image->user_id/10000);
                $smarty->clear_cache(null, "user$ab|{$image->user_id}");

            } else {
                $response[$image->gridimage_id]['status'] = 'no changes';
            }

        } else {
            $response[$image->gridimage_id]['status'] = 'invalid image';
        }
    }

    outputJSON($response);
	exit;
}

