<?php
/**
 * $Project: GeoGraph $
 * $Id: functions.inc.php 2911 2007-01-11 17:37:55Z barry $
 *
 * GeoGraph geographic photo archive project
 * http://www.geograph.org.uk/
 *
 * This file copyright (C) 2007 Barry Hunter (geo@barryhunter.co.uk)
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

/**
 * This provides a {box}{/box} block handler for rendering a chunk
 * of HTML as a nice rounded box - the implementation can be changed to
 * take advantage of new CSS
 */ 
 
function smarty_block_box($params, $content, &$smarty, &$repeat)
{
    // only output on the closing tag
    if(!$repeat)
    {
        if (isset($content)) 
        {
            $style=isset($params['style'])?" style=\"{$params['style']}\"":"";
            $colour=isset($params['colour'])?$params['colour']:"333";
   
   			global $CONF;
   			$imgdir="http://{$CONF['STATIC_HOST']}/templates/{$CONF['template']}/css/";
          
            $out="<div class=\"round{$colour}\"{$style}>";
            $out.='<div class="roundtop">';
            $out.="<img src=\"{$imgdir}b{$colour}_tl.gif\" width=\"12\" height=\"12\" class=\"corner\" style=\"display:none\">";
            $out.='</div>';
            $out.=$content;
            $out.='<div class="roundbottom">';
			$out.="<img src=\"{$imgdir}b{$colour}_bl.gif\" width=\"12\" height=\"12\" class=\"corner\" style=\"display:none\">";
            $out.='</div>';
           
           $out.='</div>';
            
            return $out;
        }
    }
}
?>