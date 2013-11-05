<?php
/*********************************************************************
    cli/files.php

    Import and export files to/from osTicket

    Jared Hancock <jared@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

class FileCliTool extends Module {
    var $prologue = "Import and export files from osTicket";

    var $options = array(
        'import' => array('-i', '--import', 'metavar'=>'FILE', 'help'=>
            "Import a file from disk into the ticketing system. Use the
            --thread option to specify a thread item to which to attach the
            file"
        ),
        'export' => array('-e', '--export', 'metavar'=>'ID', 'help'=>
            "Export a file from the ticketing system. Export is performed by
            the file ID. Id's can be looked up with the --list parameter and
            accompanying options"
        ),
        'list' => array('-L', '--list', 'help'=>
            "List files in the ticketing system. Filter your query with the
            --thread, --ticket, and --name options."
        ),
    );

    function run($args, $options) {
    }
}

Module::register('files', 'FileCliTool');
?>
