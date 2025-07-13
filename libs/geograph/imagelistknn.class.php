<?php
/**
 * $Project: GeoGraph $
 *
 * GeoGraph geographic photo archive project
 * http://geograph.sourceforge.net/
 *
 * This file copyright (C) 2023 Barry Hunter (barry@geograph.org.uk)
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

require_once('imagelist.class.php');

/**
* Provides the ImageListKNN class
*
* @package Geograph
*/

/**
* ImageListKNN class
* Provides facilities for building a list of GridImage instances using KNN
*/
class ImageListKNN extends ImageList
{
    public function getImagesSimilarToID($id, $limit = 100)
    {
        $id = intval($id);
        //always needs (id,user_id, title) (ideally realname,grid_reference too)
        $sql = "select id, user_id, realname, title, 1 as reference_index, knn_dist() as grid_reference from gridimage_embedding where knn ( image_vector, $limit, $id ) limit $limit";

        return $this->getImagesBySphinxQL($sql);
    }

    public function getImagesSimilarToLabel($label, $limit = 100)
    {
        $db = $this->_getDB();
        $quoted = $db->Quote($label);
        $binary = $db->getOne("SELECT embeddings FROM label_embedding WHERE label = $quoted");

        if (empty($binary)) {
            return 0;
        }

        $list = unpack('g*', $binary);
        $value = "(".implode(', ', $list).")";
        $vector = "image_vector";

        //always needs (id,user_id, title) (ideally realname,grid_reference too)
        $sql = "select id, user_id, realname, title, 1 as reference_index, knn_dist() as grid_reference from gridimage_embedding where knn($vector, $limit, $value) limit $limit";

        return $this->getImagesBySphinxQL($sql);
    }
}
?>
