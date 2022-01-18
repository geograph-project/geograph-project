<?php

$prefix = filemtime("../templates");

header("Content-Type: text/plain");


$cmd = "grep pid= -m1 /mnt/efs/smarty-*/$prefix^*";
$cmd = "find /mnt/efs/smarty-* -type f -name '$prefix^*' | xargs grep pid= -m1";

$h = popen($cmd,'r');
$stat = array();
while ($h && !feof($h)) {
        $line = fgets($h);
//      /mnt/efs/smarty-basic/1642424295^%%02^028^028627DC%%_block_recent.tpl.inc:<?php /* Smarty version 2.6.19, created on 2022-01-17 13:08:13 pid=42

        if (preg_match("/(\w+\/$prefix.*?)\.(inc|php):.*pid=(\d+)/",$line,$m)) {
                @$stat[$m[1]][$m[2]] = $m[3];
        }
}
print "Tested ".count($stat)." templates for $prefix\n";

foreach ($stat as $tmp => $rows) {
        if (count($rows) == 2 && $rows['inc'] != $rows['php']) {
                print "$tmp\n";
                print_r($rows);
        }
}

