<?php
/*********************************************************************
    class.thread.php

    Ticket thread
    XXX: Please DO NOT add any ticket related logic! use ticket class.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
include_once(INCLUDE_DIR.'class.ticket.php');
include_once(INCLUDE_DIR.'class.draft.php');

//Ticket thread.
class Thread {

    var $id; // same as ticket ID.
    var $ticket;

    function Thread($ticket) {

        $this->ticket = $ticket;

        $this->id = 0;

        $this->load();
    }

    function load() {

        if(!$this->getTicketId())
            return null;

        $sql='SELECT ticket.ticket_id as id '
            .' ,count(DISTINCT attach.attach_id) as attachments '
            .' ,count(DISTINCT message.id) as messages '
            .' ,count(DISTINCT response.id) as responses '
            .' ,count(DISTINCT note.id) as notes '
            .' FROM '.TICKET_TABLE.' ticket '
            .' LEFT JOIN '.TICKET_ATTACHMENT_TABLE.' attach ON ('
                .'ticket.ticket_id=attach.ticket_id) '
            .' LEFT JOIN '.TICKET_THREAD_TABLE.' message ON ('
                ."ticket.ticket_id=message.ticket_id AND message.thread_type = 'M') "
            .' LEFT JOIN '.TICKET_THREAD_TABLE.' response ON ('
                ."ticket.ticket_id=response.ticket_id AND response.thread_type = 'R') "
            .' LEFT JOIN '.TICKET_THREAD_TABLE.' note ON ( '
                ."ticket.ticket_id=note.ticket_id AND note.thread_type = 'N') "
            .' WHERE ticket.ticket_id='.db_input($this->getTicketId())
            .' GROUP BY ticket.ticket_id';

        if(!($res=db_query($sql)) || !db_num_rows($res))
            return false;

        $this->ht = db_fetch_array($res);

        $this->id = $this->ht['id'];

        return true;
    }

    function getId() {
        return $this->id;
    }

    function getTicketId() {
        return $this->getTicket()?$this->getTicket()->getId():0;
    }

    function getTicket() {
        return $this->ticket;
    }

    function getNumAttachments() {
        return $this->ht['attachments'];
    }

    function getNumMessages() {
        return $this->ht['messages'];
    }

    function getNumResponses() {
        return $this->ht['responses'];
    }

    function getNumNotes() {
        return $this->ht['notes'];
    }

    function getCount() {
        return $this->getNumMessages() + $this->getNumResponses();
    }

    function getMessages() {
        return $this->getEntries('M');
    }

    function getResponses() {
        return $this->getEntries('R');
    }

    function getNotes() {
        return $this->getEntries('N');
    }

    function getEntries($type, $order='ASC') {

        if(!$order || !in_array($order, array('DESC','ASC')))
            $order='ASC';

        $sql='SELECT thread.*
               , COALESCE(user.name,
                    IF(staff.staff_id,
                        CONCAT_WS(" ", staff.firstname, staff.lastname),
                        NULL)) as name '
            .' ,count(DISTINCT attach.attach_id) as attachments '
            .' FROM '.TICKET_THREAD_TABLE.' thread '
            .' LEFT JOIN '.USER_TABLE.' user
                ON (thread.user_id=user.id) '
            .' LEFT JOIN '.STAFF_TABLE.' staff
                ON (thread.staff_id=staff.staff_id) '
            .' LEFT JOIN '.TICKET_ATTACHMENT_TABLE.' attach
                ON (thread.ticket_id=attach.ticket_id
                        AND thread.id=attach.ref_id) '
            .' WHERE  thread.ticket_id='.db_input($this->getTicketId());

        if($type && is_array($type))
            $sql.=' AND thread.thread_type IN('.implode(',', db_input($type)).')';
        elseif($type)
            $sql.=' AND thread.thread_type='.db_input($type);

        $sql.=' GROUP BY thread.id '
             .' ORDER BY thread.created '.$order;

        $entries = array();
        if(($res=db_query($sql)) && db_num_rows($res))
            while($rec=db_fetch_array($res))
                $entries[] = $rec;

        return $entries;
    }

    function getEntry($id) {
        return ThreadEntry::lookup($id, $this->getTicketId());
    }

    function addNote($vars, &$errors) {

        //Add ticket Id.
        $vars['ticketId'] = $this->getTicketId();

        return Note::create($vars, $errors);
    }

    function addMessage($vars, &$errors) {

        $vars['ticketId'] = $this->getTicketId();
        $vars['staffId'] = 0;

        return Message::create($vars, $errors);
    }

    function addResponse($vars, &$errors) {

        $vars['ticketId'] = $this->getTicketId();
        $vars['userId'] = 0;

        return Response::create($vars, $errors);
    }

    function deleteAttachments() {

        $deleted=0;
        // Clear reference table
        $res=db_query('DELETE FROM '.TICKET_ATTACHMENT_TABLE.' WHERE ticket_id='.db_input($this->getTicketId()));
        if ($res && db_affected_rows())
            $deleted = AttachmentFile::deleteOrphans();

        return $deleted;
    }

    function delete() {

        /* XXX: Leave this out until TICKET_EMAIL_INFO_TABLE has a primary
         *      key
        $sql = 'DELETE mid.* FROM '.TICKET_EMAIL_INFO_TABLE.' mid
            INNER JOIN '.TICKET_THREAD_TABLE.' thread ON (thread.id = mid.thread_id)
            WHERE thread.ticket_id = '.db_input($this->getTicketId());
        db_query($sql);
         */

        $res=db_query('DELETE FROM '.TICKET_THREAD_TABLE.' WHERE ticket_id='.db_input($this->getTicketId()));
        if(!$res || !db_affected_rows())
            return false;

        $this->deleteAttachments();

        return true;
    }

    /* static */
    function lookup($ticket) {

        return ($ticket
                && is_object($ticket)
                && ($thread = new Thread($ticket))
                && $thread->getId()
                )?$thread:null;
    }

    function getVar($name) {
        switch ($name) {
        case 'original':
            return Message::firstByTicketId($this->ticket->getId())
                ->getBody();
            break;
        case 'last_message':
        case 'lastmessage':
            return $this->ticket->getLastMessage()->getBody();
            break;
        }
    }
}


Class ThreadEntry {

    var $id;
    var $ht;

    var $staff;
    var $ticket;

    var $attachments;


    function ThreadEntry($id, $type='', $ticketId=0) {
        $this->load($id, $type, $ticketId);
    }

    function load($id=0, $type='', $ticketId=0) {

        if(!$id && !($id=$this->getId()))
            return false;

        $sql='SELECT thread.*, info.email_mid '
            .' ,count(DISTINCT attach.attach_id) as attachments '
            .' FROM '.TICKET_THREAD_TABLE.' thread '
            .' LEFT JOIN '.TICKET_EMAIL_INFO_TABLE.' info
                ON (thread.id=info.thread_id) '
            .' LEFT JOIN '.TICKET_ATTACHMENT_TABLE.' attach
                ON (thread.ticket_id=attach.ticket_id
                        AND thread.id=attach.ref_id) '
            .' WHERE  thread.id='.db_input($id);

        if($type)
            $sql.=' AND thread.thread_type='.db_input($type);

        if($ticketId)
            $sql.=' AND thread.ticket_id='.db_input($ticketId);

        $sql.=' GROUP BY thread.id ';

        if(!($res=db_query($sql)) || !db_num_rows($res))
            return false;

        $this->ht = db_fetch_array($res);
        $this->id = $this->ht['id'];

        $this->staff = $this->ticket = null;
        $this->attachments = array();

        return true;
    }

    function reload() {
        return $this->load();
    }

    function getId() {
        return $this->id;
    }

    function getPid() {
        return $this->ht['pid'];
    }

    function getType() {
        return $this->ht['thread_type'];
    }

    function getSource() {
        return $this->ht['source'];
    }

    function getPoster() {
        return $this->ht['poster'];
    }

    function getTitle() {
        return $this->ht['title'];
    }

    function getBody() {
        return $this->ht['body'];
    }

    function setBody($body) {
        global $cfg;

        $sql='UPDATE '.TICKET_THREAD_TABLE.' SET updated=NOW()'
            .',body='.db_input(Format::sanitize($body,
                !$cfg->isHtmlThreadEnabled()))
            .' WHERE id='.db_input($this->getId());
        return db_query($sql) && db_affected_rows();
    }

    function getCreateDate() {
        return $this->ht['created'];
    }

    function getUpdateDate() {
        return $this->ht['updated'];
    }

    function getNumAttachments() {
        return $this->ht['attachments'];
    }

    function getTicketId() {
        return $this->ht['ticket_id'];
    }

    function getEmailMessageId() {
        return $this->ht['email_mid'];
    }

    function getEmailHeaders() {
        require_once(INCLUDE_DIR.'class.mailparse.php');

        $sql = 'SELECT headers FROM '.TICKET_EMAIL_INFO_TABLE
            .' WHERE thread_id='.$this->getId();
        $headers = db_result(db_query($sql));
        return Mail_Parse::splitHeaders($headers);
    }

    function getEmailReferences() {
        if (!isset($this->_references)) {
            $headers = self::getEmailHeaders();
            if (isset($headers['References']) && $headers['References'])
                $this->_references = $headers['References']." ";
            $this->_references .= $this->getEmailMessageId();
        }
        return $this->_references;
    }

    function getTaggedEmailReferences($prefix, $refId) {

        $ref = "+$prefix".Base32::encode(pack('VV', $this->getId(), $refId));

        $mid = substr_replace($this->getEmailMessageId(),
                $ref, strpos($this->getEmailMessageId(), '@'), 0);

        return sprintf('%s %s', $this->getEmailReferences(), $mid);
    }

    function getEmailReferencesForUser($user) {
        return $this->getTaggedEmailReferences('u', $user->getId());
    }

    function getEmailReferencesForStaff($staff) {
        return $this->getTaggedEmailReferences('s', $staff->getId());
    }

    function getUIDFromEmailReference($ref) {

        $info = unpack('Vtid/Vuid',
                Base32::decode(strtolower(substr($ref, -13))));

        if ($info && $info['tid'] == $this->getId())
            return $info['uid'];

    }

    function getTicket() {

        if(!$this->ticket && $this->getTicketId())
            $this->ticket = Ticket::lookup($this->getTicketId());

        return $this->ticket;
    }

    function getStaffId() {
        return $this->ht['staff_id'];
    }

    function getStaff() {

        if(!$this->staff && $this->getStaffId())
            $this->staff = Staff::lookup($this->getStaffId());

        return $this->staff;
    }

    function getUserId() {
        return $this->ht['user_id'];
    }

    function getUser() {

        if (!isset($this->user))
            $this->user = User::lookup($this->getUserId());

        return $this->user;
    }

    function getEmailHeader() {
        return $this->ht['headers'];
    }

    function isAutoReply() {

        if (!isset($this->is_autoreply))
            $this->is_autoreply = $this->getEmailHeader()
                ?  TicketFilter::isAutoReply($this->getEmailHeader()) : false;

        return $this->is_autoreply;
    }

    function isBounce() {

        if (!isset($this->is_bounce))
            $this->is_bounce = $this->getEmailHeader()
                ? TicketFilter::isBounce($this->getEmailHeader()) : false;

        return $this->is_bounce;
    }

    function isBounceOrAutoReply() {
        return ($this->isAutoReply() || $this->isBounce());
    }

    //Web uploads - caller is expected to format, validate and set any errors.
    function uploadFiles($files) {

        if(!$files || !is_array($files))
            return false;

        $uploaded=array();
        foreach($files as $file) {
            if($file['error'] && $file['error']==UPLOAD_ERR_NO_FILE)
                continue;

            if(!$file['error']
                    && ($id=AttachmentFile::upload($file))
                    && $this->saveAttachment($id))
                $uploaded[]=$id;
            else {
                if(!$file['error'])
                    $error = 'Unable to upload file - '.$file['name'];
                elseif(is_numeric($file['error']))
                    $error ='Error #'.$file['error']; //TODO: Transplate to string.
                else
                    $error = $file['error'];
                /*
                 Log the error as an internal note.
                 XXX: We're doing it here because it will eventually become a thread post comment (hint: comments coming!)
                 XXX: logNote must watch for possible loops
               */
                $this->getTicket()->logNote('File Upload Error', $error, 'SYSTEM', false);
            }

        }

        return $uploaded;
    }

    function importAttachments(&$attachments) {

        if(!$attachments || !is_array($attachments))
            return null;

        $files = array();
        foreach($attachments as &$attachment)
            if(($id=$this->importAttachment($attachment)))
                $files[] = $id;

        return $files;
    }

    /* Emailed & API attachments handler */
    function importAttachment(&$attachment) {

        if(!$attachment || !is_array($attachment))
            return null;

        $id=0;
        if ($attachment['error'] || !($id=$this->saveAttachment($attachment))) {
            $error = $attachment['error'];

            if(!$error)
                $error = 'Unable to import attachment - '.$attachment['name'];

            $this->getTicket()->logNote('File Import Error', $error, 'SYSTEM', false);
        }

        return $id;
    }

   /*
    Save attachment to the DB.
    @file is a mixed var - can be ID or file hashtable.
    */
    function saveAttachment(&$file) {

        if(!($fileId=is_numeric($file)?$file:AttachmentFile::save($file)))
            return 0;

        // TODO: Add a unique index to TICKET_ATTACHMENT_TABLE (file_id,
        // ticket_id), and remove this block
        if ($id = db_result(db_query('SELECT attach_id FROM '.TICKET_ATTACHMENT_TABLE
                .' WHERE file_id='.db_input($fileId).' AND ticket_id='
                .db_input($this->getTicketId()))))
            return $id;

        $sql ='INSERT IGNORE INTO '.TICKET_ATTACHMENT_TABLE.' SET created=NOW() '
             .' ,file_id='.db_input($fileId)
             .' ,ticket_id='.db_input($this->getTicketId())
             .' ,ref_id='.db_input($this->getId());

        return (db_query($sql) && ($id=db_insert_id()))?$id:0;
    }

    function saveAttachments($files) {
        $ids=array();
        foreach($files as $file)
           if(($id=$this->saveAttachment($file)))
               $ids[] = $id;

        return $ids;
    }

    function getAttachments() {

        if($this->attachments)
            return $this->attachments;

        //XXX: inner join the file table instead?
        $sql='SELECT a.attach_id, f.id as file_id, f.size, lower(f.`key`) as file_hash, f.name '
            .' FROM '.FILE_TABLE.' f '
            .' INNER JOIN '.TICKET_ATTACHMENT_TABLE.' a ON(f.id=a.file_id) '
            .' WHERE a.ticket_id='.db_input($this->getTicketId())
            .' AND a.ref_id='.db_input($this->getId());

        $this->attachments = array();
        if(($res=db_query($sql)) && db_num_rows($res)) {
            while($rec=db_fetch_array($res))
                $this->attachments[] = $rec;
        }

        return $this->attachments;
    }

    function getAttachmentUrls($script='image.php') {
        $json = array();
        foreach ($this->getAttachments() as $att) {
            $json[$att['file_hash']] = array(
                'download_url' => sprintf('attachment.php?id=%d&h=%s', $att['attach_id'],
                    strtolower(md5($att['file_id'].session_id().$att['file_hash']))),
                'filename' => $att['name'],
            );
        }
        return $json;
    }

    function getAttachmentsLinks($file='attachment.php', $target='', $separator=' ') {

        $str='';
        foreach($this->getAttachments() as $attachment ) {
            /* The hash can be changed  but must match validation in @file */
            $hash=md5($attachment['file_id'].session_id().$attachment['file_hash']);
            $size = '';
            if($attachment['size'])
                $size=sprintf('<em>(%s)</em>', Format::file_size($attachment['size']));

            $str.=sprintf('<a class="Icon file" href="%s?id=%d&h=%s" target="%s">%s</a>%s&nbsp;%s',
                    $file, $attachment['attach_id'], $hash, $target, Format::htmlchars($attachment['name']), $size, $separator);
        }

        return $str;
    }
    /**
     * postEmail
     *
     * After some security and sanity checks, attaches the body and subject
     * of the message in reply to this thread item
     *
     * Parameters:
     * mailinfo - (array) of information about the email, with at least the
     *          following keys
     *      - mid - (string) email message-id
     *      - name - (string) personal name of email originator
     *      - email - (string<email>) originating email address
     *      - subject - (string) email subject line (decoded)
     *      - body - (string) email message body (decoded)
     */
    function postEmail($mailinfo) {
        // +==================+===================+=============+
        // | Orig Thread-Type | Reply Thread-Type | Requires    |
        // +==================+===================+=============+
        // | *                | Message (M)       | From: Owner |
        // | *                | Note (N)          | From: Staff |
        // | Response (R)     | Message (M)       |             |
        // | Message (M)      | Response (R)      | From: Staff |
        // +------------------+-------------------+-------------+

        if (!$ticket = $this->getTicket())
            // Kind of hard to continue a discussion without a ticket ...
            return false;

        // Make sure the email is NOT already fetched... (undeleted emails)
        elseif ($this->getEmailMessageId() == $mailinfo['mid'])
            // Reporting success so the email can be moved or deleted.
            return true;

        $vars = array(
            'mid' =>    $mailinfo['mid'],
            'header' => $mailinfo['header'],
            'ticketId' => $ticket->getId(),
            'poster' => $mailinfo['name'],
            'origin' => 'Email',
            'source' => 'Email',
            'ip' =>     '',
            'reply_to' => $this,
            'recipients' => $mailinfo['recipients'],
            'to-email-id' => $mailinfo['to-email-id'],
        );
        $errors = array();

        if (isset($mailinfo['attachments']))
            $vars['attachments'] = $mailinfo['attachments'];

        $body = $mailinfo['message'];

        // Disambiguate if the user happens also to be a staff member of the
        // system. The current ticket owner should _always_ post messages
        // instead of notes or responses
        if ($mailinfo['userId']
                || strcasecmp($mailinfo['email'], $ticket->getEmail()) == 0) {
            $vars['message'] = $body;
            $vars['userId'] = $mailinfo['userId'] ? $mailinfo['userId'] : $ticket->getUserId();
            return $ticket->postMessage($vars, 'Email');
        }
        // XXX: Consider collaborator role
        elseif ($mailinfo['staffId']
                || ($mailinfo['staffId'] = Staff::getIdByEmail($mailinfo['email']))) {
            $vars['staffId'] = $mailinfo['staffId'];
            $poster = Staff::lookup($mailinfo['staffId']);
            $vars['note'] = $body;
            return $ticket->postNote($vars, $errors, $poster);
        }
        elseif (Email::getIdByEmail($mailinfo['email'])) {
            // Don't process the email -- it came FROM this system
            return true;
        }
        // Support the mail parsing system declaring a thread-type
        elseif (isset($mailinfo['thread-type'])) {
            switch ($mailinfo['thread-type']) {
            case 'N':
                $vars['note'] = $body;
                $poster = $mailinfo['email'];
                return $ticket->postNote($vars, $errors, $poster);
            }
        }
        // TODO: Consider security constraints
        else {
            //XXX: Are we potentially leaking the email address to
            // collaborators?
            $vars['message'] = sprintf("Received From: %s\n\n%s",
                $mailinfo['email'], $body);
            $vars['userId'] = 0; //Unknown user! //XXX: Assume ticket owner?
            return $ticket->postMessage($vars, 'Email');
        }
        // Currently impossible, but indicate that this thread object could
        // not append the incoming email.
        return false;
    }

    /* Returns file names with id as key */
    function getFiles() {

        $files = array();
        foreach($this->getAttachments() as $attachment)
            $files[$attachment['file_id']] = $attachment['name'];

        return $files;
    }


    /* save email info
     * TODO: Refactor it to include outgoing emails on responses.
     */

    function saveEmailInfo($vars) {

        if(!$vars || !$vars['mid'])
            return 0;

        $this->ht['email_mid'] = $vars['mid'];

        $header = false;
        if (isset($vars['header']))
            $header = $vars['header'];
        self::logEmailHeaders($this->getId(), $vars['mid'], $header);
    }

    /* static */
    function logEmailHeaders($id, $mid, $header=false) {
        $sql='INSERT INTO '.TICKET_EMAIL_INFO_TABLE
            .' SET thread_id='.db_input($id)
            .', email_mid='.db_input($mid); //TODO: change it to message_id.
        if ($header)
            $sql .= ', headers='.db_input($header);
        return db_query($sql)?db_insert_id():0;
    }

    /* variables */

    function __toString() {
        return $this->getBody();
    }

    function asVar() {
        return (string) $this;
    }

    function getVar($tag) {
        global $cfg;

        if($tag && is_callable(array($this, 'get'.ucfirst($tag))))
            return call_user_func(array($this, 'get'.ucfirst($tag)));

        switch(strtolower($tag)) {
            case 'create_date':
                return Format::date(
                        $cfg->getDateTimeFormat(),
                        Misc::db2gmtime($this->getCreateDate()),
                        $cfg->getTZOffset(),
                        $cfg->observeDaylightSaving());
                break;
            case 'update_date':
                return Format::date(
                        $cfg->getDateTimeFormat(),
                        Misc::db2gmtime($this->getUpdateDate()),
                        $cfg->getTZOffset(),
                        $cfg->observeDaylightSaving());
                break;
        }

        return false;
    }

    /* static calls */

    function lookup($id, $tid=0, $type='') {
        return ($id
                && is_numeric($id)
                && ($e = new ThreadEntry($id, $type, $tid))
                && $e->getId()==$id
                )?$e:null;
    }

    /**
     * Parameters:
     * mailinfo (hash<String>) email header information. Must include keys
     *  - "mid" => Message-Id header of incoming mail
     *  - "in-reply-to" => Message-Id the email is a direct response to
     *  - "references" => List of Message-Id's the email is in response
     *  - "subject" => Find external ticket number in the subject line
     *
     *  seen (by-ref:bool) a flag that will be set if the message-id was
     *      positively found, indicating that the message-id has been
     *      previously seen. This is useful if no thread-id is associated
     *      with the email (if it was rejected for instance).
     */
    function lookupByEmailHeaders(&$mailinfo, &$seen=false) {
        // Search for messages using the References header, then the
        // in-reply-to header
        $search = 'SELECT thread_id, email_mid FROM '.TICKET_EMAIL_INFO_TABLE
               . ' WHERE email_mid=%s ORDER BY thread_id DESC';

        if (list($id, $mid) = db_fetch_row(db_query(
                sprintf($search, db_input($mailinfo['mid']))))) {
            $seen = true;
            return ThreadEntry::lookup($id);
        }

        foreach (array('mid', 'in-reply-to', 'references') as $header) {
            $matches = array();
            if (!isset($mailinfo[$header]) || !$mailinfo[$header])
                continue;
            // Header may have multiple entries (usually separated by
            // spaces ( )
            elseif (!preg_match_all('/<[^>@]+@[^>]+>/', $mailinfo[$header],
                        $matches))
                continue;

            // The References header will have the most recent message-id
            // (parent) on the far right.
            // @see rfc 1036, section 2.2.5
            // @see http://www.jwz.org/doc/threading.html
            foreach (array_reverse($matches[0]) as $mid) {
                //Try to determine if it's a reply to a tagged email.
                $ref = null;
                if (strpos($mid, '+')) {
                    list($left, $right) = explode('@',$mid);
                    list($left, $ref) = explode('+', $left);
                    $mid = "$left@$right";
                }
                $res = db_query(sprintf($search, db_input($mid)));
                while (list($id) = db_fetch_row($res)) {
                    if (!($t = ThreadEntry::lookup($id))) continue;

                    //We found a match  - see if we can ID the user.
                    // XXX: Check access of ref is enough?
                    if ($ref && ($uid = $t->getUIDFromEmailReference($ref))) {
                        if ($ref[0] =='s') //staff
                            $mailinfo['staffId'] = $uid;
                        else //user or collaborator.
                            $mailinfo['userId'] = $uid;
                    }

                    return $t;
                }
            }
        }

        // Search for ticket by the [#123456] in the subject line
        // This is the last resort -  emails must match to avoid message
        // injection by third-party.
        $subject = $mailinfo['subject'];
        $match = array();
        if ($subject
                && $mailinfo['email']
                && preg_match("/#(?:[\p{L}-]+)?([0-9]{1,10})/u", $subject, $match)
                //Lookup by ticket number
                && ($ticket = Ticket::lookupByNumber((int)$match[1]))
                //Lookup the user using the email address
                && ($user = User::lookup(array('emails__address' => $mailinfo['email'])))) {
            //We have a valid ticket and user
            if ($ticket->getUserId() == $user->getId() //owner
                    ||  ($c = Collaborator::lookup( // check if collaborator
                            array('userId' => $user->getId(),
                                  'ticketId' => $ticket->getId())))) {

                $mailinfo['userId'] = $user->getId();
                return $ticket->getLastMessage();
            }
        }

        return null;
    }

    //new entry ... we're trusting the caller to check validity of the data.
    function create($vars) {
        global $cfg;

        //Must have...
        if(!$vars['ticketId'] || !$vars['type'] || !in_array($vars['type'], array('M','R','N')))
            return false;


        if (!$vars['body'] instanceof ThreadBody) {
            if ($cfg->isHtmlThreadEnabled())
                $vars['body'] = new HtmlThreadBody($vars['body']);
            else
                $vars['body'] = new TextThreadBody($vars['body']);
        }

        // Drop stripped images. NOTE: This should be done before
        // ->convert() because the strippedImages list will not propagate to
        // the newly converted thread body
        if ($vars['attachments']) {
            foreach ($vars['body']->getStrippedImages() as $cid) {
                foreach ($vars['attachments'] as $i=>$a) {
                    if (@$a['cid'] && $a['cid'] == $cid) {
                        // Inline referenced attachment was stripped
                        unset($vars['attachments']);
                    }
                }
            }
        }

        if (!($body = Format::sanitize(
                (string) $vars['body']->convertTo('html'))))
            $body = '-'; //Special tag used to signify empty message as stored.

        $poster = $vars['poster'];
        if ($poster && is_object($poster))
            $poster = (string) $poster;

        $sql=' INSERT INTO '.TICKET_THREAD_TABLE.' SET created=NOW() '
            .' ,thread_type='.db_input($vars['type'])
            .' ,ticket_id='.db_input($vars['ticketId'])
            .' ,title='.db_input(Format::sanitize($vars['title'], true))
            .' ,staff_id='.db_input($vars['staffId'])
            .' ,user_id='.db_input($vars['userId'])
            .' ,poster='.db_input($poster)
            .' ,source='.db_input($vars['source']);

        if (!isset($vars['attachments']) || !$vars['attachments'])
            // Otherwise, body will be configured in a block below (after
            // inline attachments are saved and updated in the database)
            $sql.=' ,body='.db_input($body);

        if(isset($vars['pid']))
            $sql.=' ,pid='.db_input($vars['pid']);
        // Check if 'reply_to' is in the $vars as the previous ThreadEntry
        // instance. If the body of the previous message is found in the new
        // body, strip it out.
        elseif (isset($vars['reply_to'])
                && $vars['reply_to'] instanceof ThreadEntry)
            $sql.=' ,pid='.db_input($vars['reply_to']->getId());

        if($vars['ip_address'])
            $sql.=' ,ip_address='.db_input($vars['ip_address']);

        //echo $sql;
        if(!db_query($sql) || !($entry=self::lookup(db_insert_id(), $vars['ticketId'])))
            return false;

        /************* ATTACHMENTS *****************/

        //Upload/save attachments IF ANY
        if($vars['files']) //expects well formatted and VALIDATED files array.
            $entry->uploadFiles($vars['files']);

        //Canned attachments...
        if($vars['cannedattachments'] && is_array($vars['cannedattachments']))
            $entry->saveAttachments($vars['cannedattachments']);

        //Emailed or API attachments
        if (isset($vars['attachments']) && $vars['attachments']) {
            $entry->importAttachments($vars['attachments']);
            foreach ($vars['attachments'] as $a) {
                // Change <img src="cid:"> inside the message to point to
                // a unique hash-code for the attachment. Since the
                // content-id will be discarded, only the unique hash-code
                // will be available to retrieve the image later
                if ($a['cid'] && $a['key']) {
                    $body = str_replace('src="cid:'.$a['cid'].'"',
                        'src="cid:'.$a['key'].'"', $body);
                }
            }
            $sql = 'UPDATE '.TICKET_THREAD_TABLE.' SET body='.db_input($body)
                .' WHERE `id`='.db_input($entry->getId());
            if (!db_query($sql) || !db_affected_rows())
                return false;
        }

        // Email message id (required for all thread posts)
        if (!isset($vars['mid']))
            $vars['mid'] = sprintf('<%s@%s>', Misc::randCode(24),
                substr(md5($cfg->getUrl()), -10));
        $entry->saveEmailInfo($vars);

        // Inline images (attached to the draft)
        $entry->saveAttachments(Draft::getAttachmentIds($body));

        return $entry;
    }

    function add($vars) {
        return ($entry=self::create($vars))?$entry->getId():0;
    }
}

/* Message - Ticket thread entry of type message */
class Message extends ThreadEntry {

    function Message($id, $ticketId=0) {
        parent::ThreadEntry($id, 'M', $ticketId);
    }

    function getSubject() {
        return $this->getTitle();
    }

    function create($vars, &$errors) {
        return self::lookup(self::add($vars, $errors));
    }

    function add($vars, &$errors) {

        if(!$vars || !is_array($vars) || !$vars['ticketId'])
            $errors['err'] = 'Missing or invalid data';
        elseif(!$vars['message'])
            $errors['message'] = 'Message required';

        if($errors) return false;

        $vars['type'] = 'M';
        $vars['body'] = $vars['message'];

        if (!$vars['poster']
                && $vars['userId']
                && ($user = User::lookup($vars['userId'])))
            $vars['poster'] = (string) $user->getName();

        return ThreadEntry::add($vars);
    }

    function lookup($id, $tid=0, $type='M') {

        return ($id
                && is_numeric($id)
                && ($m = new Message($id, $tid))
                && $m->getId()==$id
                )?$m:null;
    }

    function lastByTicketId($ticketId) {
        return self::byTicketId($ticketId);
    }

    function firstByTicketId($ticketId) {
        return self::byTicketId($ticketId, false);
    }

    function byTicketId($ticketId, $last=true) {

        $sql=' SELECT thread.id FROM '.TICKET_THREAD_TABLE.' thread '
            .' WHERE thread_type=\'M\' AND thread.ticket_id = '.db_input($ticketId)
            .sprintf(' ORDER BY thread.id %s LIMIT 1', $last ? 'DESC' : 'ASC');

        if (($res = db_query($sql)) && ($id = db_result($res)))
            return Message::lookup($id);

        return null;
    }
}

/* Response - Ticket thread entry of type response */
class Response extends ThreadEntry {

    function Response($id, $ticketId=0) {
        parent::ThreadEntry($id, 'R', $ticketId);
    }

    function getSubject() {
        return $this->getTitle();
    }

    function getRespondent() {
        return $this->getStaff();
    }

    function create($vars, &$errors) {
        return self::lookup(self::add($vars, $errors));
    }

    function add($vars, &$errors) {

        if(!$vars || !is_array($vars) || !$vars['ticketId'])
            $errors['err'] = 'Missing or invalid data';
        elseif(!$vars['response'])
            $errors['response'] = 'Response required';

        if($errors) return false;

        $vars['type'] = 'R';
        $vars['body'] = $vars['response'];
        if(!$vars['pid'] && $vars['msgId'])
            $vars['pid'] = $vars['msgId'];

        if (!$vars['poster']
                && $vars['staffId']
                && ($staff = Staff::lookup($vars['staffId'])))
            $vars['poster'] = (string) $staff->getName();

        return ThreadEntry::add($vars);
    }


    function lookup($id, $tid=0, $type='R') {

        return ($id
                && is_numeric($id)
                && ($r = new Response($id, $tid))
                && $r->getId()==$id
                )?$r:null;
    }
}

/* Note - Ticket thread entry of type note (Internal Note) */
class Note extends ThreadEntry {

    function Note($id, $ticketId=0) {
        parent::ThreadEntry($id, 'N', $ticketId);
    }

    function getMessage() {
        return $this->getBody();
    }

    /* static */
    function create($vars, &$errors) {
        return self::lookup(self::add($vars, $errors));
    }

    function add($vars, &$errors) {

        //Check required params.
        if(!$vars || !is_array($vars) || !$vars['ticketId'])
            $errors['err'] = 'Missing or invalid data';
        elseif(!$vars['note'])
            $errors['note'] = 'Note required';

        if($errors) return false;

        //TODO: use array_intersect_key  when we move to php 5 to extract just what we need.
        $vars['type'] = 'N';
        $vars['body'] = $vars['note'];

        return ThreadEntry::add($vars);
    }

    function lookup($id, $tid=0, $type='N') {

        return ($id
                && is_numeric($id)
                && ($n = new Note($id, $tid))
                && $n->getId()==$id
                )?$n:null;
    }
}

class ThreadBody /* extends SplString */ {

    static $types = array('text', 'html');

    var $body;
    var $type;
    var $stripped_images = array();

    function __construct($body, $type='text') {
        $type = strtolower($type);
        if (!in_array($type, static::$types))
            throw new Exception($type.': Unsupported ThreadBody type');
        $this->body = (string) $body;
        $this->type = $type;
    }

    function convertTo($type) {
        if ($type === $this->type)
            return $this;

        $conv = $this->type . ':' . strtolower($type);
        switch ($conv) {
        case 'text:html':
            return new ThreadBody(sprintf('<pre>%s</pre>',
                Format::htmlchars($this->body)), $type);
        case 'html:text':
            return new ThreadBody(Format::html2text((string) $this), $type);
        }
    }

    function stripQuotedReply($tag) {

        //Strip quoted reply...on emailed  messages
        if (!$tag || strpos($this->body, $tag) === false)
            return;

        // Capture a list of inline images
        $images_before = $images_after = array();
        preg_match_all('/src="cid:([\w_-]+)(?:@|")/', $this->body, $images_before,
            PREG_PATTERN_ORDER);

        // Strip the quoted part of the body
        if ((list($msg) = explode($tag, $this->body, 2)) && trim($msg)) {
            $this->body = $msg;

            // Capture a list of dropped inline images
            if ($images_before) {
                preg_match_all('/src="cid:([\w_-]+)(?:@|")/', $this->body,
                    $images_after, PREG_PATTERN_ORDER);
                $this->stripped_images = array_diff($images_before[1],
                    $images_after[1]);
            }
        }
    }

    function getStrippedImages() {
        return $this->stripped_images;
    }

    function __toString() {
        return $this->body;
    }
}

class TextThreadBody extends ThreadBody {
    function __construct($body) {
        parent::__construct(Format::stripEmptyLines($body), 'text');
    }
}
class HtmlThreadBody extends ThreadBody {
    function __construct($body) {
        $body = trim($body, " <>br/\t\n\r") ? Format::safe_html($body) : '';
        parent::__construct($body, 'html');
    }
}
?>
