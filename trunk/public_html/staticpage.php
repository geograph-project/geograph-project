<?php
/**
 * $Project: GeoGraph $
 * $Id$
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
init_session();

//get page from request
$page=isset($_GET['page'])?$_GET['page']:'404';

//do we trust it? like hell we do! alphanumerics only please!
if (!preg_match('/^[a-z0-9_]+$/' , $page))
{
	$page='404';
}

//next, we want to be sure you can only view pages intended for static viewing
$template='static_'.$page.'.tpl';

//lets be sure it exists...
$smarty = new GeographPage;
if (!$smarty->templateExists($template))
{
	$template='static_404.tpl';
}



$smarty->display($template);

	
?>
