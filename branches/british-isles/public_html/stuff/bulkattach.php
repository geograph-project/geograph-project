<?php
/**
 * $Project: GeoGraph $
 * $Id: xmas.php 6235 2009-12-24 12:33:07Z barry $
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
require_once('geograph/map.class.php');
require_once('geograph/mapmosaic.class.php');
init_session();

$smarty = new GeographPage;



$smarty->display('_std_begin.tpl',$_SERVER['PHP_SELF']);

print "<h2>NON-FUNCTIONAL Prototype</h2>";

?>

<h3>Images</h3>

<h4>Paste Image IDs</h4>
<textarea rows=3 cols=50></textarea><br/>
(max 1000)<br/><br/>

and/or<br/>
<h4>Search Results</h4>
i Number:<input size=10> page:<input size=3> (both required)<br/>
(Only works on a single page of results at a time)
<br/>
<hr/>
<h3>Action</h3>
<table border=1 cellspacing=0 cellpadding=3>
 <tr>
  <td><input type=radio name="action" checked> Add Tag</td>
  <td rowspan=2 valign=middle>tag: <input size=30> (eg place:Epping)<br/>
    <input type=checkbox checked> Public tag (if your image)<br/>
    <input type=checkbox> Only act on your images<br/>
	<small>Note: You can only remove tags you specifically added to the image(s)</small></td>
 </tr>
 <tr>
  <td><input type=radio name="action"> Remove Tag</td>
 </tr>
 <tr>
  <td><input type=radio name="action"> Add Snippet</td>
  <td rowspan=2 valign=middle>snippet: <input size=3> (id number)<br/>
    <small>Note: Only acts on your images</small></td>
 </tr>
 <tr>
  <td><input type=radio name="action"> Remove Snippet</td>
 </tr>
</table>
<?


$smarty->display('_std_end.tpl');


