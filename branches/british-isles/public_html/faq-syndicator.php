<?

/**
 * $Project: GeoGraph $
 * $Id: glossary.php 2960 2007-01-15 14:33:27Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2008 Barry Hunter (geo@barryhunter.co.uk)
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
require_once('geograph/feedcreator.class.php');


        $valid_formats=array('RSS0.91','RSS1.0','RSS2.0','MBOX','OPML','ATOM','ATOM0.3','HTML','JS','PHP','KML','BASE','GeoRSS','GeoPhotoRSS','GPX','TOOLBAR','MEDIA');

        if (isset($_GET['extension']) && !isset($_GET['format']))
        {
            $_GET['format'] = strtoupper($_GET['extension']);
            $_GET['format'] = str_replace('GEO','Geo',$_GET['format']);
            $_GET['format'] = str_replace('PHOTO','Photo',$_GET['format']);
        }

        $format="GeoRSS";
        if (isset($_GET['format']) && in_array($_GET['format'], $valid_formats))
        {
            $format=$_GET['format'];
        }

        if ($format == 'KML') {
            if (!isset($_GET['simple']))
                $_GET['simple'] = 1; //default to on
            $extension = (empty($_GET['simple']))?'kml':'simple.kml';
        } elseif ($format == 'GPX') {
            $extension = 'gpx';
        } else {
            $extension = 'xml';
        }

        $rssfile=$_SERVER['DOCUMENT_ROOT']."/rss/{$CONF['template']}/faq-feed.$format.$extension";

        $db = GeographDatabaseConnection(truie);


        $data = $db->getAll("
SELECT question,title,content,q.question_id,answer_id,coalesce(u2.user_id,u1.user_id) as user_id,
    coalesce(u2.realname,u1.realname) as realname, coalesce(a.created,q.created) as created, coalesce(a.anon,q.anon) as anon
FROM answer_question q
    INNER JOIN user u1 ON (q.user_id = u1.user_id) 
    LEFT JOIN answer_answer a ON (a.question_id = q.question_id and a.status=1) 
        LEFT JOIN user u2 ON (a.user_id = u2.user_id)
WHERE q.status =1  
GROUP BY q.question_id
ORDER BY created DESC");
        
        if ($data) {
            
            $rss_timeout = 3600;
            $rss = new UniversalFeedCreator(); 
            if (empty($_GET['refresh']))
                $rss->useCached($format,$cachepath,$rss_timeout); 
            $rss->title = 'Geograph Knowledgebase'; 
            $rss->link = "http://{$_SERVER['HTTP_HOST']}/faq3.php";
        
            $rss->description = "Recent Activity";

            $rss->descriptionHtmlSyndicated = false;
 
            $rss->syndicationURL = "http://{$_SERVER['HTTP_HOST']}/faq-syndicator.php?format=$format";
            
            $geoformat = ($format == 'KML' || $format == 'GeoRSS' || $format == 'GeoPhotoRSS' || $format == 'GPX');
            $photoformat = ($format == 'KML' || $format == 'GeoPhotoRSS' || $format == 'BASE' || $format == 'MEDIA');

            foreach ($data as $idx => $row) {
                
                
                $item = new FeedItem(); 
                $item->guid = $row['created'];

                if ($row['answer_id']) {
                    $item->title = "Answer: ".($row['title']?$row['title']:$row['question']);
                    $item->link = "http://{$_SERVER['HTTP_HOST']}/faq3.php#{$row['answer_id']}";
                

                } else {
                    $item->title = "Question: ".$row['question'];
                    $item->link = "http://{$_SERVER['HTTP_HOST']}/faq-answer.php?id={$row['question_id']}";

                }
                $item->description = $row['content']; 


                $item->date = strtotime($row['created']); 
                if (empty($row['anon'])) {
                    $item->source = "http://{$_SERVER['HTTP_HOST']}/profile/".$row['user_id']; 
                    $item->author = $row['realname']; 
                }
                
                $rss->addItem($item);
            }
            customExpiresHeader($rss_timeout,true); //we cache it for a while anyway! 
            header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); 
                  
            
            $rss->saveFeed($format, $cachepath); 
        } else {
            header("HTTP/1.0 204 No Content");
        }