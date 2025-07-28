<?php
class SubmissionHelper
{
    public static function handleFileUpload($file)
    {
        $smarty = new GeographPage;
        switch ($file['error']) {
            case 0:
                if (!filesize($file['tmp_name'])) {
                    $smarty->assign('error', 'Sorry, no file was received - please try again');
                }
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $smarty->assign('error', 'Sorry, that file exceeds our maximum upload size of 8Mb - please resize the image and try again');
                break;
            case UPLOAD_ERR_PARTIAL:
                $smarty->assign('error', 'Your file was only partially uploaded - please try again');
                break;
            case UPLOAD_ERR_NO_FILE:
                $smarty->assign('error', 'No file was uploaded - please try again');
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $smarty->assign('error', 'System Error: Folder missing - please let us know');
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $smarty->assign('error', 'System Error: Can not write file - please let us know');
                break;
            case UPLOAD_ERR_EXTENSION:
                $smarty->assign('error', 'System Error: Upload Blocked - please let us know');
                break;
            default:
                $smarty->assign('error', 'We were unable to process your upload - please try again');
                break;
        }
        return $smarty;
    }

    public static function processExifAndFilename($uploadManager, $fileName)
    {
        $exif = $uploadManager->rawExifData;
        $post = [];

        if (!empty($exif['GPS']) && !empty($exif['GPS']['GPSLatitude']) && !empty($exif['GPS']['GPSLongitude'])) {
            $conv = new Conversions;

            list($e, $n, $reference_index) = ExifToNational($exif);

            list ($post['photographer_gridref'], $len) = $conv->national_to_gridref(intval($e), intval($n), 0, $reference_index);

            list ($post['grid_reference'], $len) = $conv->national_to_gridref(intval($e), intval($n), 4, $reference_index);

            $post['gridsquare'] = preg_replace('/^([A-Z]+).*$/', '', $post['grid_reference']);

        } elseif (preg_match("/(_|\b)([a-zA-Z]{1,3})[ \._-]?(\d{2,5})[ \._-]?(\d{2,5})(\b|[A-Za-z_])/", $fileName, $m)) {
            if (strlen($m[3]) != strlen($m[4])) {
                if (preg_match("/(_|\b)([a-zA-Z]{1,2})[ \._-]?(\d{4,10})(\b|[A-Za-z_])/", $fileName, $m)) {
                    $post['gridsquare'] = $m[2];
                    $post['grid_reference'] = $m[2] . $m[3];
                }
            } else {
                $post['gridsquare'] = $m[2];
                $post['grid_reference'] = $m[2] . $m[3] . $m[4];
            }

        } elseif (!empty($exif['COMMENT']) && preg_match("/(_|\b)([a-zA-Z]{1,2})[ \._-]?(\d{2,5})[ \._-]?(\d{2,5})(\b|[A-Za-z_])/", implode(' ', $exif['COMMENT']), $m)) {
            $post['gridsquare'] = $m[2];
            $post['grid_reference'] = $m[2] . $m[3] . $m[4];
        }
        return $post;
    }

    public static function finaliseSubmission($uploadManager, $post, $square)
    {
        $uploadManager->setTitle(stripslashes(trim($post['title'])));
        $uploadManager->setComment(stripslashes(trim($post['comment'])));
        $uploadManager->setTaken(stripslashes($post['imagetaken']));
        if (!empty($post['tags'])) {
            $uploadManager->setTags(explode('|', stripslashes(trim($post['tags']))));
        }
        if (!empty($post['subject'])) {
            $uploadManager->setSubject(stripslashes(trim($post['subject'])));
        }
        if (!empty($post['imageclass'])) {
            $uploadManager->setClass(stripslashes(trim($post['imageclass'])));
        }
        $uploadManager->setViewpoint(stripslashes($post['photographer_gridref']));
        $uploadManager->setDirection(stripslashes($post['view_direction']));
        $uploadManager->setUse6fig(stripslashes($post['use6fig']));
        if (!empty($post['user_status'])) {
            $uploadManager->setUserStatus(stripslashes($post['user_status']));
        }
        if (!empty($post['largestsize'])) {
            $uploadManager->setLargestSize($post['largestsize']);
        }

        if ($post['pattrib'] == 'other') {
            $uploadManager->setCredit(stripslashes($post['pattrib_name']));
        } elseif ($post['pattrib'] == 'self') {
            $uploadManager->setCredit('');
        }

        $uploadManager->setSquare($square);

        return $uploadManager->commit('submit', true);
    }

    public static function normaliseGridReference($gridReference)
    {
        $square = new GridSquare();
        $ok = $square->setByFullGridRef($gridReference);
        if ($ok && $square->natgrlen > 4 && !preg_match('/^[A-Z]/', $gridReference)) {
            //setByFullGridRef will now accept lat/long, which need to convert back to a GR
            $conv = new Conversions('');
            list($gridReference, $len) = $conv->national_to_gridref(
                $square->getNatEastings(),
                $square->getNatNorthings(),
                $square->natgrlen,
                $square->reference_index,
                false
            );
        }
        return $gridReference;
    }

    public static function fixOrientation($uploadManager, $smarty)
    {
        if (!empty($uploadManager->rawExifData) && !empty($uploadManager->rawExifData['IFD0']['Orientation']) && $uploadManager->rawExifData['IFD0']['Orientation'] !== 1) {
            //actully, we need to check the file itself. Because it could of been rotated - the exif data (in the .exif file) is NOT updated when rotate the image!
            $uploadfile = $uploadManager->_pendingJPEG($uploadManager->upload_id);
            $orginalfile = $uploadManager->_originalJPEG($uploadManager->upload_id);

            $to = file_exists($orginalfile) ? $orginalfile : $uploadfile;
            $orient = `exiftool -Orientation -n $to`;
            if (strpos($orient, 'Orientation') !== FALSE && strpos($orient, '1') === FALSE) {
                $smarty->assign('rotation_warning', true);
            }
        }
    }

    public static function handleTransferId($transferId)
    {
        $uploadManager = new UploadManager();
        if ($uploadManager->validUploadId($transferId)) {
            $uploadManager->setUploadId($transferId);
            $uploadManager->reReadExifFile();
            $uploadManager->initOriginalUploadSize();

            $smarty_vars = [
                'upload_id' => $uploadManager->upload_id,
                'transfer_id' => $uploadManager->upload_id,
                'preview_url' => "/submit.php?preview=" . $uploadManager->upload_id,
                'preview_width' => $uploadManager->upload_width,
                'preview_height' => $uploadManager->upload_height,
            ];

            if ($uploadManager->hasoriginal) {
                $smarty_vars['original_width'] = $uploadManager->original_width;
                $smarty_vars['original_height'] = $uploadManager->original_height;
            }

            return $smarty_vars;
        }
        return false;
    }

    public static function getDirections()
    {
        $dirs = array(-1 => '');
        $jump = 360 / 16;
        $jump2 = 360 / 32;
        for ($q = 0; $q < 360; $q += $jump) {
            $s = ($q % 90 == 0) ? strtoupper(heading_string($q)) : ucwords(heading_string($q));
            $dirs[$q] = sprintf(
                '%s : %03d deg (%03d > %03d)',
                str_pad($s, 16, chr(160)),
                $q,
                ($q == 0 ? $q + 360 - $jump2 : $q - $jump2),
                $q + $jump2
            );
        }
        $dirs['00'] = $dirs[0];
        return $dirs;
    }

    public static function getNews()
    {
        global $CONF;
        if ($CONF['forums']) {
            if (empty($db)) {
                $db = GeographDatabaseConnection(false);
            }

            //let's find recent posts in the announcements forum made by administrators
            $sql = "select t.topic_title,p.post_text,t.topic_id,t.topic_time, DATEDIFF(NOW(),t.topic_time) as days
                        from geobb_topics as t
                        inner join geobb_posts as p on(t.topic_id=p.topic_id)
                        inner join user as u on (t.topic_poster=u.user_id)
                        where (find_in_set('director',u.rights)>0) and
			topic_time > DATE_SUB(NOW(),INTERVAL 1 MONTH) and
                        abs(unix_timestamp(t.topic_time) - unix_timestamp(p.post_time) ) < 10 and
                        t.forum_id=1
                        group by t.topic_id desc limit 5";
            $news = $db->CacheGetAll(3600, $sql);
            if ($news) {
                foreach ($news as $idx => $item) {
                    $news[$idx]['post_text'] = strip_tags($news[$idx]['post_text']);
                }
                return $news;
            }
        }
        return false;
    }

    public static function setLastSubmissionDetails($post, $square)
    {
        if ($post['imagetaken'] != '0000-00-00') {
            $_SESSION['last_imagetaken'] = $post['imagetaken'];
        }
        if (!empty($post['grid_reference']) && $square->natgrlen > 4) {
            $_SESSION['last_grid_reference'] = $post['grid_reference'];
        }
        if (!empty($post['photographer_gridref'])) {
            $_SESSION['last_photographer_gridref'] = $post['photographer_gridref'];
        }
    }

    public static function getLastSubmissionDetails()
    {
        $details = [];
        if (isset($_SESSION['last_imagetaken'])) {
            $details['last_imagetaken'] = $_SESSION['last_imagetaken'];
        }
        if (isset($_SESSION['last_grid_reference'])) {
            $details['last_grid_reference'] = $_SESSION['last_grid_reference'];
        }
        if (isset($_SESSION['last_photographer_gridref'])) {
            $details['last_photographer_gridref'] = $_SESSION['last_photographer_gridref'];
        }
        return $details;
    }
}
