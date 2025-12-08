<?php

$param = array('table'=>'ai_prompt',  'file'=>'', 'execute'=>0);

chdir(__DIR__);
require "./_scripts.inc.php";


$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

###################################################################

$table = $param['table'];

if (!is_file("../ai-schema/".$param['file']))
	die("file not found\n");

$prompt = file_get_contents("../ai-schema/".$param['file']);




        $updates = array();
	$updates['prompt_name'] = basename($param['file'],'.txt');
	$updates['content'] = file_get_contents("../ai-schema/".$param['file']);

		//invcalidate any older
		$update = "UPDATE $table SET active = active -1 WHERE prompt_name = ".$db->Quote($updates['prompt_name']);
		if ($param['execute'])
		        $db->Execute($update);
		else
			print "$update;\n";

		//insert new version
	        $update = "INSERT INTO $table SET `".implode('` = ?,`',array_keys($updates))."` = ?";
		if ($param['execute'])
		        $db->Execute($update, array_values($updates));
		else
			print "$update;\n";



/*

CREATE TABLE `ai_prompt` (
  `key` varchar(64) NOT NULL,
  `active` tinyint(3) NOT NULL DEFAULT 1,
  
  `prompt_name` varchar(64) NOT NULL,
  `content` TEXT NOT NULL,
  
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`prompt_name`)
) ENGINE=InnoDB;

*/
