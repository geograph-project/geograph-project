/**
 * $Project: GeoGraph $
 * $Id: mapping1.js 3657 2007-08-09 18:12:09Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005  Barry Hunter (geo@barryhunter.co.uk)
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
 
String.prototype.trim = function () {
	return this.replace(/^\s+|\s+$/g,"");
}

function doMove(obj,e,n) {
	gridref = document.getElementById(obj).value.trim().toUpperCase();

	var grid=new GT_OSGB();
	var ok = false;
	if (grid.parseGridRef(gridref)) {
		ok = true;
	} else {
		grid=new GT_Irish();
		ok = grid.parseGridRef(gridref)
	}
	
	if (ok) {
		grid.eastings = grid.eastings + (e*1000);
		grid.northings = grid.northings + (n*1000);
	
		gridref = grid.getGridRef(2);
		
		document.getElementById(obj).value = gridref;
	}
}


function doMove2(e,n) {
	ob1 = document.getElementById('gridsquare');
	ob2 = document.getElementById('eastings');
	ob3 = document.getElementById('northings');
	gridref = ob1.options[ob1.selectedIndex].value+ob2.options[ob2.selectedIndex].text+ob3.options[ob3.selectedIndex].text;

	var grid=new GT_OSGB();
	var ok = false;
	if (grid.parseGridRef(gridref)) {
		ok = true;
	} else {
		grid=new GT_Irish();
		ok = grid.parseGridRef(gridref)
	}
	
	if (ok) {
		grid.eastings = grid.eastings + (e*1000);
		grid.northings = grid.northings + (n*1000);
	
		bits = grid.getGridRef(2).split(/ /);
		
		for(var q=0;q<ob1.options.length;q++) {
			if (ob1.options[q].value == bits[0])
				ob1.selectedIndex = q;
		}
		for(var q=0;q<ob2.options.length;q++) {
			if (ob2.options[q].text == bits[1])
				ob2.selectedIndex = q;
		}
		for(var q=0;q<ob3.options.length;q++) {
			if (ob3.options[q].text == bits[2])
				ob3.selectedIndex = q;
		}
		
		
	}
}