<?php
/**
 * $Project: GeoGraph $
 * $Id: process_events.php 5211 2009-01-24 20:44:18Z barry $
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

$db = GeographDatabaseConnection(true);

$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$data = array(

	'backups' => $db->getRow("SELECT SUM(backedup > date_sub(now(),interval 24 hour)) AS day, SUM(backedup > date_sub(now(),interval 7 day)) AS week FROM _tables"),

//        'carrot_created' => $db->getOne("select count(*) from at_home_job where task = 'carrot2' and created > date_sub(now(),interval 24 hour)"),
//        'carrot_processed' => $db->getOne("select count(*) from at_home_job where task = 'carrot2' and completed > date_sub(now(),interval 24 hour) and images > 0"),
//        'carrot_outstanding' => $db->getOne("select count(*) from at_home_job where task = 'carrot2' and completed < '2000-00-00'"),

        'events' => $db->getRow("select count(*) as posted,sum(status='completed') as done from event where posted > date_sub(now(),interval 24 hour)"),

        'sphinx_tables' => $db->getAll("show table status like '%sphinx%'"),

        'sequence_table' => $db->getRow("show table status like 'gridimage_sequence'"),

        'system_spotcheck' => $db->getAll("select title,count(*) cnt,sum(spotcheck>date_sub(now(),interval 24 hour)) recent
                        from systemtask where status='active' and (title like '%smart%' or title like '%raid%' or title like '%disk space%')
                        group by substring(title,1,10)")
);



outputJSON($data);


