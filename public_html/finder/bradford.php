<?php
/**
 * $Project: GeoGraph $
 * $Id: faq.php 15 2005-02-16 12:23:35Z lordelph $
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

$smarty = new GeographPage;

$sph = GeographSphinxConnection('sphinxql',true);

$sql = "
SELECT id,title,realname,user_id,grid_reference,takenday, place,vlat,vlong,scenti, hash, width,height
, geodist(wgs84_lat,wgs84_long,0.93925808248603,-0.030558585900733) as geodist
FROM sample8
WHERE takendays BETWEEN 739755 AND 739757
 AND geodist < 4000
 AND MATCH(' @hectad (SE13 | SE23)')
 AND vlat > 0.1
LIMIT 1000
OPTION ranker=none, max_query_time = 10000
";

$images = $sph->getAll($sql);

$smarty->display('_std_begin.tpl');

?>

<form name="theForm">
	Order: <select onchange="order = this.value; outputTable()">
		<option value="geo">Cluster (photographer location)</option>
		<option value="grid_reference">Subject Square</option>
		<option value="scenti">Subject Centisquare</option>
		<option value="takenday">Day</option>
		<option value="place">Place</option>
	</select>

	<label>Collapse: <input type=checkbox name=collapse onclick="outputTable()"></label>
	<label>Large Clusters: <input type=checkbox name=large onclick="outputTable()"></label>
</form>

<div id="output"></div>

<script>

let images = <? echo json_encode($images); ?>;
let order = 'geo'; //the default
	//grid_reference/takenday also work!

const geoprefix = 'v';

/////////////////////////////////////////////

function outputTable() {
	let cols = {}; //assocative
	let rows = {}; //assocative

	let collapse = document.forms['theForm'].elements['collapse'].checked;
	let crit = document.forms['theForm'].elements['large'].checked?0.00001:0.000003;

	/////////////////////////////////////////////

	for (let image of images) {
		if (!image.thumbnail) {
			 image.gridimage_id = image.id;
		          image.thumbnail = getGeographUrl(image.id, image.hash, 'small');
		          //image.img = getGeographUrl(image.id, image.hash, 'full');
		          image.lat = rad2deg(image[geoprefix+'lat'])
		          image.lng = rad2deg(image[geoprefix+'long'])

				var width=120, height=120;
                                if (image.width > image.height) { //landscape
                                        height=Math.round(120*image.height/image.width);
                                } else { //portrait
                                        width=Math.round(120*image.width/image.height);
                                }
                                image.width = width;
                                image.height = height;
		}
	}

	if (order == 'geo') {
		//todo, detect if nees to set order!
			updateOrder(order); //just sets .order (doesnt do the reordering!)
			images.sort(function(a,b) {
				return a.order - b.order;
			});
	}

	/////////////////////////////////////////////
	//SELECT  id,title,realname,user_id,grid_reference,takenday, place,vlat,vlong, hash, width,height, geodist

	let block = 1;
	let lat = 40,lng = -20; //so start 'lands end' :)
	for (let image of images) {
		let row;
		if (order == 'geo') {
			if (!image.lat)
				continue;

			if (lat > 40) {
				dist = Math.pow(lat-image.lat,2)+Math.pow(lng-image.lng,2); //dont need to bother with sqrt
				if (dist > crit)
					block++;
			}
			row = "Cl#"+block;
			lat = image.lat;
			lng = image.lng;

		} else {
			row = image[order];
		}
		let col = image['realname'];

		if (!cols[col]) cols[col] = col;
		if (!rows[row])	rows[row] = {};

		if (!rows[row][col]) rows[row][col] = []; //this is a list
		rows[row][col].push(image);
	}

	/////////////////////////////////////////////

	const table = document.createElement('table');
	table.border = 1;

	let tr; //going to be many!
	let cell; //going to be many!

	//header row
	if (!collapse) {
		tr = document.createElement('tr');
		cell = document.createElement('td');
		cell.textContent = "Contributor";
		tr.appendChild(cell)

		for (const col in cols) {
			if (Object.prototype.hasOwnProperty.call(cols, col)) {
				cell = document.createElement('td');
				cell.textContent = col;
				tr.appendChild(cell)
			}
		}

		let thead = document.createElement('thead');
		thead.appendChild(tr)
		table.appendChild(thead)
	}
	//rows
	for (const row in rows) {
		if (Object.prototype.hasOwnProperty.call(rows, row)) {
			if (collapse) { //each row ges its own 'fixed' header ?!?!
				tr = document.createElement('tr');
				cell = document.createElement('td');
				cell.textContent = "Contributor";
				tr.appendChild(cell)

				let done = 0, total = 0;
				for (const col in cols) {
					if (Object.prototype.hasOwnProperty.call(cols, col)) {
						if (rows[row][col]) {
							cell = document.createElement('td');
							cell.textContent = col;
							tr.appendChild(cell)
							done++;
						}
						total++;
					}
				}
				//need empt cells a end to 'blank' out older rows!
				while (done < total) {
					cell = document.createElement('td');
					cell.textContent = " ";
					tr.appendChild(cell)
					done++;
				}

				let thead = document.createElement('thead');
				thead.appendChild(tr)
				table.appendChild(thead)
			}

			/////////////////////////////////////////////

			tr = document.createElement('tr');
			cell = document.createElement('td');
			cell.textContent = row;
			tr.appendChild(cell)

			for (const col in cols) {
				if (Object.prototype.hasOwnProperty.call(cols, col)) {
					cell = document.createElement('td');
					if (rows[row][col]) {
					//	cell.textContent = rows[row][col].length;
						for (let image of rows[row][col]) {
							let img = document.createElement('img')
							img.loading = 'lazy';
							img.src = image.thumbnail;
							img.width = image.width; //setting width/height, makes scrolling better, as imags have proper size in the dom!
							img.height = image.height;

							let a = document.createElement('a')
							a.href = "https://www.geograph.org.uk/photo/"+image.id;
							a.target = 'photo';
							a.title = image.grid_reference+" "+image.title+" by "+image.realname;
							a.appendChild(img);
							cell.appendChild(a);
//					break;
						}
						tr.appendChild(cell);

					} else if (!collapse) {
						tr.appendChild(cell); //still need empt cell!
					}
				}
			}

			table.appendChild(tr)
		}
	}

	/////////////////////////////////////////////

	const outputDiv = document.getElementById('output');
	outputDiv.innerHTML = '';
	outputDiv.appendChild(table);
};

/////////////////////////////////////////////

function onDocumentReady(callback) {
    if (document.readyState === "loading") { // Loading hasn't finished yet
        document.addEventListener("DOMContentLoaded", callback);
    } else { // `DOMContentLoaded` has already fired
        callback();
    }
}

onDocumentReady(outputTable);

/////////////////////////////////////////////
// stolen from viewer

function updateOrder(order) {
	if (images.length < 2) {
		console.log('not enough images to sort');
		return;
	}

	let lat = 40,lng = -20; //so start 'lands end' :)
	let index = 1;
	let count = 0;

	//first count images
	for (const image of images) {
		if (image.lat)
			count++;
		image.order = 0; //need to reset, so can get progress! (also need to set even if no location, so sorting works!
	}
	if (count < 2) {
		console.log('not enough locations to sort',count);
		return;
	}

	//then loop looking for the next closest
	while (index <= count) {
		let bestimage = null;
		let bestdist = Infinity;
		for (const image of images) {
			if (image.lat && image.order == 0) {
				dist = Math.pow(lat-image.lat,2)+Math.pow(lng-image.lng,2); //dont need to bother with sqrt
				if (dist < bestdist) {
					bestdist = dist;
					bestimage = image;
				}
			}
		}
		if (bestimage) {
			bestimage.order = index;
			index++;

			lat = bestimage.lat;
			lng = bestimage.lng;
		} else {
			//stop an infinite loop!
			return;
		}
	}
}

/////////////////////////////////////////////

function rad2deg (angle) {
    // Converts the radian number to the equivalent number in degrees
    //
    // version: 1109.2015
    // discuss at: http://phpjs.org/functions/rad2deg
    // +   original by: Enrique Gonzalez
    // +      improved by: Brett Zamir (http://brett-zamir.me)
    // *     example 1: rad2deg(3.141592653589793);
    // *     returns 1: 180
    return angle * 57.29577951308232; // angle / Math.PI * 180
}


function getGeographUrl(gridimage_id, hash, size) {

        yz=zeroFill(Math.floor(gridimage_id/1000000),2);
        ab=zeroFill(Math.floor((gridimage_id%1000000)/10000),2);
        cd=zeroFill(Math.floor((gridimage_id%10000)/100),2);
        abcdef=zeroFill(gridimage_id,6);

        if (yz == '00') {
                fullpath="/photos/"+ab+"/"+cd+"/"+abcdef+"_"+hash;
        } else {
                fullpath="/geophotos/"+yz+"/"+ab+"/"+cd+"/"+abcdef+"_"+hash;
        }

        switch(size) {
                case 'full': return "https://s0.geograph.org.uk"+fullpath+".jpg"; break;
                case 'original': return "https://s0.geograph.org.uk"+fullpath+"_original.jpg"; break;
                case '1024': return "https://s0.geograph.org.uk"+fullpath+"_1024x1024.jpg"; break;
                case 'med': return "https://s"+(gridimage_id%4)+".geograph.org.uk"+fullpath+"_213x160.jpg"; break;
                case 'small':
                default: return "https://s"+(gridimage_id%4)+".geograph.org.uk"+fullpath+"_120x120.jpg";
        }
}

function zeroFill(number, width) {
        width -= number.toString().length;
        if (width > 0) {
                return new Array(width + (/\./.test(number)?2:1)).join('0') + number;
        }
        return number + "";
}

</script>

<style>
form[name=theForm] {
	background-color:silver;
	padding:10px;
	margin-bottom:10px;
}
#output table {
	border-collapse: collapse;
	background-color:white;
}
#output table thead {
    position: sticky;
    top: 0; /* Stays at the top of the viewport */
    background-color: #f8f8f8; /* Important: Add a background so content doesn't show through */
    z-index: 10; /* Ensure it stays on top of other content */
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); /* Optional: add a subtle shadow */
}
#output table td {
	padding:2px;
	text-align:center;
}
#output table --img {
	max-width:100px;
	max-height:100px;
	width: auto;
	height: auto;
}
</style>

<?

$smarty->display('_std_end.tpl');

