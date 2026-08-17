<?php
/**
 * $Project: GeoGraph $
 * $Id: viewps.php 2302 2006-07-05 12:15:49Z barryhunter $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Barry Hunter (geo@barryhunter.co.uk)
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
init_session();

$smarty = new GeographPage;

$USER->hasPerm("director") || ($USER->user_id == 93) || $USER->mustHavePerm("admin");

$db = NewADOConnection($GLOBALS['DSN']);

#################################################

print "<b>SES Bounces</b> &middot; ";

if (!empty($_GET['summary'])) {
	print "<b>Summary</b> &middot; ";
} else {
	print "<a href=?summary=1>Summary</a> &middot; ";
}

if (!empty($_GET)) {
	print "<a href=?>Back to all</a> &middot; ";
	if (empty($_GET['summary']))
		print "<b>Filtered View</b> &middot; ";
} else {
	print "<b>All Reports</b> &middot; ";

	print "<a href=?recent=1>Recent</a> &middot; ";
}
print "<hr>";

#################################################

if (!empty($_GET['t'])) {
	$value = $db->getOne("SELECT Message FROM sns_message WHERE TimeStamp = ".$db->Quote($_GET['t']));

	$a = json_decode($value,true);
	print renderArrayAsHtml($a);
	if (empty($_GET['json']))
		exit;

	print "<hr><pre>";
	foreach (explode("\n",print_r(json_decode($value,true),true)) as $line) {
		if (empty($line) || preg_match('/^\s*([(){}]|Array)\s*$/',$line))
			continue;
		print htmlentities($line)."\n";
	}
	//print htmlentities(print_r(json_decode($value,true),true));
	exit;
}

#################################################

if (!empty($_GET['summary'])) {
	$where = array();

	$where[] = "sender LIKE '%@geograph.org.uk'";
	$where[] = "email NOT like '%@simulator.amazonses.com%'";
	$where[] = "email NOT in ('sample@email.tst','testing@example.com')";
	if (empty($_GET['all']))
		$where[] = "TimeStamp > date_sub(date(now()),interval 2 year)";

	$where = implode(' AND ',$where);
	$data = $db->getAll("SELECT sender,normalized,count(*) as cnt,count(distinct email_md5) as emails, max(TimeStamp) as latest
	, sum(TimeStamp > date_sub(date(now()),interval 7 day)) as 7day
	FROM sns_summary WHERE $where GROUP BY sender, normalized ORDER BY latest DESC LIMIT 50");

	print "<table cellspacing=0 cellpadding=4 border=1 bordercolor=#eee>";
	print "<tr>";
	foreach(array('Sender','Subject','Bounces','Emails','7 Days','Most Recent') as $h)
		print "<th>$h</th>";
	foreach($data as $row) {
		$url = "?".http_build_query(array('sender'=>$row['sender'],'subject'=>$row['normalized']));
		print "<tr>";
		print "<td>".htmlentities($row['sender']);
		print "<td><a href=\"$url\" style=\"text-decoration:none\">".preg_replace('/(\[geograph\])/i','<span style=color:silver>$1</span>',htmlentities($row['normalized']));
		print "<td align=right><a href=\"$url\">".number_format($row['cnt'],0);
		print "<td align=right>".number_format($row['emails'],0);
		if (!empty($row['7day'])) {
			$url .= "&amp;recent=1";
			print "<td align=right><a href=\"$url\">".number_format($row['7day'],0);
		} else
			print "<td align=right>0";
		print "<td>".str_replace('T',' <span style=color:silver>',$row['latest'])."</span>";
	}
	print "</table>";

	if (empty($_GET['all']))
		print "Only showing last 2 years. <a href=?summary=1&amp;all=1>View All</a>";

	exit;
}

#################################################

if (!empty($_GET['email'])) {
	$user = new GeographUser();
	$user->email = $_GET['email'];
	$msg = $user->getBounceMessage();

	if(!empty($msg)) {
		print "<p style=padding:10px;background-color:orange>$msg</p><hr>";
	}
}

#################################################

if (empty($_GET))
	$_GET['grouped'] = 1;

$where = array();

$limit = 50;
if (!empty($_GET['limit']))
	$limit = intval($_GET['limit']);

#################################################

$filters = array();
$filters['sender'] = $db->getCol("SELECT sender FROM sns_summary GROUP BY sender");
$filters['notificationType'] = array('Bounce','Complaint');
$filters['bounceType'] = array('Permanent','Transient');
$filters['SubType'] = array('OnAccountSuppressionList','!OnAccountSuppressionList');
$filters['user_id'] = 'text';
$filters['email'] = 'text';
$filters['subject'] = 'text';

$commonSubjects = $db->getCol("SELECT DISTINCT normalized FROM sns_summary ORDER BY normalized");

print "<form method=get style=background-color:#eee;padding:5px>Filter:";

// Render Subject Datalist
if (!empty($commonSubjects)) {
    print '<datalist id="commonsubject">';
    foreach ($commonSubjects as $subj) {
        printf('<option value="%s"></option>', htmlspecialchars($subj, ENT_QUOTES));
    }
    print '</datalist>';
}

foreach ($filters as $name => $rows) {
	if ($rows == 'text') {
		$listAttr = ($name === 'subject' && !empty($commonSubjects)) ? ' list="commonsubject"' : '';

		print "<input type=search name=$name$listAttr value=\"".htmlentities(@$_GET[$name])."\" onkeyup=\"if (event.key == 'Enter') {this.form.submit(); }\" title=$name size=10 placeholder=$name>";

		if (!empty($_GET[$name])) {
			if ($name == 'email') {
				$where[] = "JSON_VALUE(Message,'$.mail.destination[0]') = ".$db->Quote($_GET[$name]);
			} elseif ($name == 'subject') {
				if (strpos($_GET[$name],'#ticket-id') !== FALSE) {
					$where[] = "JSON_VALUE(Message,'$.mail.commonHeaders.subject') REGEXP ".$db->Quote(" #[0-9]$");
				} elseif (strpos($_GET[$name],'...') !== FALSE) {
					$where[] = "JSON_VALUE(Message,'$.mail.commonHeaders.subject') LIKE ".$db->Quote(str_replace('...','%',$_GET[$name]));
				} else {
					$where[] = "JSON_VALUE(Message,'$.mail.commonHeaders.subject') = ".$db->Quote($_GET[$name]);
				}
			} else {
				$where[] = "user.$name LIKE ".$db->Quote($_GET[$name]);
			}
		}
	} else {
		print "<select name=$name onchange=this.form.submit() title=$name placeholder=$name>";
		print "<option value=\"\" style=\"color:grey\">$name</option>";
		foreach ($rows as $value) {
			printf('<option value="%s"%s>%s</option>',$value, (@$_GET[$name] == $value)?' selected':'', $value);
			if(@$_GET[$name] == $value) {
				if ($name == 'sender') {
					$where[] = "JSON_VALUE(Message,'$.mail.source') = ".$db->Quote($_GET[$name]);
				} elseif (preg_match('/^!(\w+)/',$value,$m)) {
					$where[] = "Message NOT LIKE ".$db->Quote("%{$name}\":\"{$m[1]}\"%");
				} else {
					$where[] = "Message LIKE ".$db->Quote("%{$name}\":\"{$value}\"%");
				}
			}
		}
		print "</select>";
	}
}

$checked = empty($_GET['recent'])?'':' checked';
print "<label><input type=checkbox name=recent$checked onclick=this.form.submit()>Recent Only</label>";
if (!empty($_GET['recent'])) {
	$where[] = "TimeStamp > DATE(DATE_SUB(NOW(),INTERVAL 7 DAY))";
}

$checked = empty($_GET['active'])?'':' checked';
print "<label><input type=checkbox name=active$checked onclick=this.form.submit()>Active</label>";
if (!empty($_GET['active'])) {
	$where[] = "submitted > DATE(DATE_SUB(NOW(),INTERVAL 6 month))";
}

$checked = empty($_GET['grouped'])?'':' checked';
print "<label><input type=checkbox name=grouped$checked onclick=this.form.submit()>Grouped</label>";
if (!empty($_GET['grouped'])) {
	$group = "JSON_VALUE(Message,'$.mail.destination[0]'), JSON_VALUE(Message,'$.notificationType')";
} else {
	$group = "1"; //timestamp!
}

print "</form>";

#################################################

$where[] = "Type = 'Notification'";
$where[] = "JSON_VALUE(Message,'$.mail.destination[0]') is not null";
$where[] = "Message NOT like '%@simulator.amazonses.com%'";

$sql = "select TimeStamp,
CONCAT_WS(', ',
	JSON_VALUE(Message,'$.notificationType'),
	JSON_VALUE(Message,'$.bounce.bounceType'),
	NULLIF(JSON_VALUE(Message,'$.bounce.bounceSubType'),'General'),
	JSON_VALUE(Message,'$.complaint.complaintType'),
	NULLIF(JSON_VALUE(Message,'$.complaint.complaintSubType'),'null'),
	JSON_VALUE(Message,'$.complaint.complaintFeedbackType')) as type,
JSON_VALUE(Message,'$.mail.destination[0]') as `to`,
count(*),
user.user_id, if (rights LIKE '%basic%','confirmed','not verified') as rights, date(signup_date) as signup,
date(submitted) as last_image,
JSON_VALUE(Message,'$.mail.commonHeaders.subject') as `subject`,
JSON_VALUE(Message,'$.bounce.bouncedRecipients[0].diagnosticCode') as diagnosticCode
from sns_message
	left join user on (user.email = JSON_VALUE(Message,'$.mail.destination[0]'))
	left join user_stat using (user_id)
	left join gridimage_search on (gridimage_id = last)
where ".implode(" AND ",$where)."
group by $group
order by TimeStamp DESC
LIMIT $limit";

print $sql;

#################################################

//JSON_VALUE(Message,'$.mail.commonHeaders.replyTo[0]') as `reply`,

print "<p style=max-width:60em> If a message is marked <b>OnAccountSuppressionList</b>, the email was blocked due to a previous, permanent bounce. The address was added to the
suppression list to prevent future failed send attempts. Click the link to see the preceding bounce(s) that likely led to the suppression.</p>";


$count = dump_sql_table($sql); //"Recent Bounce and/or Complaints");

if ($count == $limit) {
	print "Last $limit Results";
} else {
	print "All $count Results";
}


function dump_sql_table($sql,$title = null) {
	global $db;

	$recordSet = $db->Execute($sql) or die ("Couldn't select photos : $sql " . $db->ErrorMsg() . "\n");

	if ($title)
		print "<H3>$title</H3>";
	if ($recordSet->EOF)
		return;

	$row = $recordSet->fields;

	print "<TABLE border='1' cellspacing='0' cellpadding='2'><TR>";
	foreach ($row as $key => $value) {
		if ($key != 'diagnosticCode' && $key != 'subject')
			print "<TH>$key</TH>";
	}
	print "<td>View</td>";
	print "</TR>";
	$last = null;
	while (!$recordSet->EOF) {
		$row = $recordSet->fields;

		if ($last && $last != $row['to'])
			print "<tr><td style=height:5px;background-color:grey colspan=9>";
		$last = $row['to'];

		print "<TR style=background-color:#eee;font-weight:bold>";
		$align = "left";
		foreach ($row as $key => $value) {
			$align = is_numeric($value)?"right":"left";
			if ($key == 'type') {
				//provide a link to view the preceding one!
				$value = str_replace('OnAccountSuppressionList', "<a href=\"?email=".urlencode($row['to'])."&amp;SubType=%21OnAccountSuppressionList\">OnAccountSuppressionList</a>", $value);
				print "<td>$value";
			} elseif ($key == 'TimeStamp') {
				 print "<TD>".str_replace('T',' <span style=color:silver>',$value)."</span>";
			} elseif ($key != 'diagnosticCode' && $key != 'subject')
				print "<TD ALIGN=$align>".htmlentities($value)."</TD>";
		}
		print "<td><a href=\"?t={$row['TimeStamp']}\">View</a> / <a href=\"?email=".urlencode($row['to'])."\">Others</a></td>";
		print "</TR>";

		if (!empty($row['subject']))
			print "<tr><td colspan=9>".htmlentities($row['subject']);

		if (!empty($row['diagnosticCode']))
			print "<tr><td colspan=9 style=font-size:0.8em;color:brown>".htmlentities($row['diagnosticCode']);

		$recordSet->MoveNext();
	}

	print "</TR></TABLE>";

	return $recordSet->RecordCount();
}



/**
 * Recursively renders a nested PHP array into formatted HTML.
 *
 * @param array $data The array to render.
 * @param int $depth The current recursion depth (used for indentation/styling).
 * @return string The generated HTML.
 */
function renderArrayAsHtml(array $data, int $depth = 0): string
{
    if (empty($data)) {
        return '<p><em>Empty Data</em></p>';
    }

    $is_assoc = array_keys($data) !== range(0, count($data) - 1);

    // --- Table Detection Logic (Updated) ---
    // Condition 1: The current array ($data) must be an indexed array (i.e., keys are 0, 1, 2, ...).
    if (!$is_assoc && !empty($data)) {
        
        $first_value = reset($data);
        
        // Condition 2: The elements (values) of the current array must be associative arrays.
        if (is_array($first_value) && array_keys($first_value) !== range(0, count($first_value) - 1)) {
            
            $all_keys = [];
            $is_potential_table = true;

            // Condition 3: Check that all inner arrays are associative and have the same keys.
            foreach ($data as $item) {
                if (!is_array($item) || array_keys($item) === range(0, count($item) - 1)) {
                    // Stop if any element is not an array or is an indexed array itself
                    $is_potential_table = false; 
                    break;
                }
                
                $keys = array_keys($item);
                if (empty($all_keys)) {
                    $all_keys = $keys; // Set the baseline keys
                } elseif ($all_keys !== $keys) {
                    $is_potential_table = false; // Key sets don't match
                    break;
                }
            }

            // If confirmed, render as HTML table
            if ($is_potential_table) {
                $html = '<table class="data-table" style="border-collapse: collapse; width: 100%; margin-top: 5px; font-size: 0.9em;">';
                
                // Table Header
                $html .= '<thead style="background-color: #f2f2f2;"><tr>';
                foreach ($all_keys as $key) {
                    $html .= '<th style="border: 1px solid #ddd; padding: 8px; text-align: left;">' . htmlspecialchars((string)$key) . '</th>';
                }
                $html .= '</tr></thead>';

                // Table Body
                $html .= '<tbody>';
                foreach ($data as $item) {
                    $html .= '<tr>';
                    foreach ($all_keys as $key) {
                        $value = $item[$key] ?? '';
                        $html .= '<td style="border: 1px solid #ddd; padding: 8px;">';
                        
                        // Recurse for nested arrays/objects within a table cell
                        if (is_array($value)) {
                            // Recursively call for cell content, but don't try to table-detect the contents
                            $html .= renderArrayAsHtml($value, $depth + 1); 
                        } else {
                            $html .= nl2br(htmlspecialchars((string)$value)); // nl2br helps with your long 'Received' header
                        }
                        
                        $html .= '</td>';
                    }
                    $html .= '</tr>';
                }
                $html .= '</tbody></table>';

                return $html;
            }
        }
    }
    // --- End Table Detection ---


    // Default rendering as a nested list
    $html = '<ul style="list-style-type: none; padding-left: 20px; border-left: 1px solid #ccc; margin-left: 5px;">';
    
    foreach ($data as $key => $value) {
	if ($key == 'sendingAccountId' || $key == 'callerIdentity' || $key == 'sourceArn')
		continue;

        $html .= '<li style="margin-bottom: 5px;">';
        
        $key_display = is_numeric($key) && !$is_assoc ? '' : '<strong style="color: #333;">' . htmlspecialchars((string)$key) . '</strong>: ';
        
        if (is_array($value)) {
          if (count($value) == 1 && !empty($value[0]) && is_scalar($value[0]) ) {
	    //if a numberic array, with one scalar value, dont bother nesting. (this hides some of the complexity of the data, but keeps output more compact)
	    $html .= $key_display . htmlspecialchars((string)$value[0]);
          } else {
            // Updated type hint to be more accurate
            $type_hint = $is_assoc ? '(Assoc Array)' : (array_keys($value) !== range(0, count($value) - 1) ? '(Assoc Array)' : '(Indexed Array)');

            // If the current array is indexed, we only print the contents, not the key for the list item itself
            if (is_numeric($key) && !$is_assoc) {
                 // Don't show the array type hint for the sub-item if the current level is indexed list
                 $html .= renderArrayAsHtml($value, $depth + 1); 
            } else {
                 $html .= $key_display; // . '<span style="color: #007bff;">' . $type_hint . '</span>';
                 $html .= renderArrayAsHtml($value, $depth + 1); // Recurse
            }
          }
        } elseif (is_object($value)) {
            $html .= $key_display . '<span style="color: #dc3545;">(Object)</span>' . renderArrayAsHtml((array) $value, $depth + 1);
        } elseif (is_bool($value)) {
            $html .= $key_display . '<span style="color: #28a745;">' . ($value ? 'true' : 'false') . '</span>';
        } elseif (is_null($value)) {
            $html .= $key_display . '<span style="color: #6c757d;">NULL</span>';
        } else {
            $html .= $key_display . htmlspecialchars((string)$value);
        }
        
        $html .= '</li>';
    }

    $html .= '</ul>';

    return $html;
}

