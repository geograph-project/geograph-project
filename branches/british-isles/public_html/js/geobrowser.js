/**
 * Geograph Image Browser 
 * used at http://www.geograph.org.uk/stuff/geobrowser.php
 *
 * This file copyright (c)2009 Barry Hunter (geo@barryhunter.co.uk)
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
 * 
 */



function updateGridReference(form) {
	

	location.hash = "gridref="+escape(form.elements['gridref'].value);
	
	
	var grid=new GT_OSGB();
	if (grid.parseGridRef(form.elements['gridref'].value)) {
		var subgr=new GT_OSGB();
		var phgr=new GT_OSGB();
	} else {
		grid=new GT_Irish();
		if(grid.parseGridRef(form.elements['gridref'].value)) {
			var subgr=new GT_Irish();
			var phgr=new GT_Irish();
		}
	}
	
	if (!subgr) {
		document.getElementById('myTable').innerHTML = '';
		document.getElementById('info').innerHTML = "Invalid Grid Reference";
		return false;
	}
	
	document.getElementById('myTable').innerHTML = 'Fetching Data...';
	document.getElementById('info').innerHTML = "Please wait";
	
	$.ajax({
		type: "GET",
		url: "/api/Gridref/"+escape(form.elements['gridref'].value)+"?output=json",
		dataType: 'json',
		success: function(data) {
			if (data.error) {
				alert(data.error);
			} else {
				document.getElementById('info').innerHTML = data.length + " images in total";
			
				//todo - make this not hard coded!
				var columns = new Array('Image','Title',"Contributor","Description","Category","Taken Day","View Direction","Centisquare","Subject","Photographer","Distance");
			
				var opt = form.elements['groupby'].options;
				if (opt.length < 2) {
					var opt2 = form.elements['popularity'].options;
					for(var i = 1; i < columns.length; i++) {
						var newoption = new Option(columns[i],i);
						var newoption2 = new Option(columns[i],i);
						//if (idx_value == act) {
						//	newoption.selected = true;
						//}
						opt[opt.length] = newoption;
						opt2[opt2.length] = newoption2;
						if (columns[i] == 'Taken Day') {
							var newoption = new Option('Taken Month',i);
							var newoption2 = new Option('Taken Month',i);
							opt[opt.length] = newoption;
							opt2[opt2.length] = newoption2;
							var newoption = new Option('Taken Year',i);
							var newoption2 = new Option('Taken Year',i);
							opt[opt.length] = newoption;
							opt2[opt2.length] = newoption2;
						}
					}
				}
				
				//add virtual columns...
				columns.push('Count');
				columns.push('Popularity');
			
				table = document.getElementById('myTable');
				table.innerHTML = '';
				
				tablehead = document.createElement("thead");
				
				header_row  = document.createElement("tr");
				
				for(var i = 0; i < columns.length; i++) {
					header_cell = document.createElement("td");
					header_cell.className = columns[i];
					header_cell.appendChild(document.createTextNode(columns[i]));
					header_row.appendChild(header_cell);
				}
				
				tablehead.appendChild(header_row);
				
				table.appendChild(tablehead);
				
				tablebody = document.createElement("tbody");

				for(var j = 0; j < data.length; j++) {
					row = document.createElement("tr");
					
					if (data[j].nateastings) {
						subgr.eastings = data[j].nateastings;
						subgr.northings = data[j].natnorthings;
					}
					
					if (data[j].viewpoint_eastings) {
						phgr.eastings = data[j].viewpoint_eastings;
						phgr.northings = data[j].viewpoint_northings;
					}
					
					for(var i = 0; i < columns.length; i++) {
						switch(columns[i]) {
						
							case 'Image': 
								cell = document.createElement("td");
									img = document.createElement("img");
									img.setAttribute('alt',"hover to load");
									img.setAttribute('title',data[j].title+' by '+data[j].realname);
									img.setAttribute('lowsrc',data[j].thumbnail);
									img.setAttribute('onmouseover',"if (!this.src) this.src=this.lowsrc");
									a = document.createElement("a");
									a.appendChild(img);
									a.setAttribute('href','/photo/'+data[j].gridimage_id);
									a.setAttribute('target','gimage');
								cell.appendChild(a);
								cell.setAttribute('sortvalue',data[j].seq_no);
								row.appendChild(cell);
								break;

							case 'Title': 
								cell = document.createElement("td");
									a = document.createElement("a");
									a.appendChild(document.createTextNode(data[j].title || '-untitled-'));
									a.setAttribute('href','/photo/'+data[j].gridimage_id);
									a.setAttribute('target','gimage');
								cell.appendChild(a);
								cell.setAttribute('sortvalue',data[j].title || data[j].seq_no);
								row.appendChild(cell);
								break;

							case 'Contributor': 
								cell = document.createElement("td");
									a = document.createElement("a");
									a.appendChild(document.createTextNode(data[j].realname));
									a.setAttribute('href',data[j].profile_link);
									a.setAttribute('target','guser');
								cell.appendChild(a);
								cell.setAttribute('sortvalue',data[j].realname);
								row.appendChild(cell);
								break;

							case 'Description': 
								cell = document.createElement("td");
								cell.appendChild(document.createTextNode(data[j].comment || '-'));
								cell.className = 'caption';
								row.appendChild(cell);
								break;

							case 'Category': 
								cell = document.createElement("td");
								cell.appendChild(document.createTextNode(data[j].imageclass));
								row.appendChild(cell);
								break;

							case 'Taken Day': 
								if (!data[j].imagetaken || data[j].imagetaken == '0000-00-00') {
									data[j].imagetaken = '-';
								}
								cell = document.createElement("td");
								cell.appendChild(document.createTextNode(data[j].imagetaken));
								row.appendChild(cell);
								break;

							case 'View Direction': 
								if (!data[j].view_direction) {
									data[j].view_direction = 0;
								}
								if (data[j].view_direction == -1) {
									data[j].view_direction = '-';
								}
								cell = document.createElement("td");
								cell.appendChild(document.createTextNode(data[j].view_direction));
								row.appendChild(cell);
								break;

							case 'Centisquare': 
								if (data[j].nateastings && data[j].nateastings > 0) {//only set if 6fig+
									value=subgr.getGridRef(3);
								} else {
									value='-';
								}
								cell = document.createElement("td");
								cell.appendChild(document.createTextNode(value));
								row.appendChild(cell);
								break;
						
							case 'Subject': 
								if (data[j].nateastings && data[j].nateastings > 0) {//only set if 6fig+
									if (data[j].use6fig) {
										value=subgr.getGridRef(3);
									} else {
										value=subgr.getGridRef(data[j].natgrlen/2);
									}
								} else {
									value='-';
								}
								cell = document.createElement("td");
								cell.appendChild(document.createTextNode(value));
								row.appendChild(cell);
								break;
						
							case 'Photographer': 
								if (data[j].viewpoint_eastings && data[j].viewpoint_eastings > 0) {
									var prec = data[j].viewpoint_grlen/2;
									if (data[j].use6fig && prec > 3) {
										prec = 3;
									} 
										
									value=phgr.getGridRef(prec);
								} else {
									value='-';
								}
								cell = document.createElement("td");
								cell.appendChild(document.createTextNode(value));
								row.appendChild(cell);
								break;
								
							case 'Distance': 
								if (data[j].nateastings && data[j].viewpoint_eastings && (subgr.getGridRef(2) != phgr.getGridRef(2) || data[j].viewpoint_grlen > 4) ) {
									distance = Math.sqrt( Math.pow(subgr.eastings - phgr.eastings,2) + Math.pow(subgr.northings - phgr.northings,2) );
									if (distance < 100) {
										distance = Math.round(distance);
									} else if (distance < 1000) {
										distance = Math.round(distance/5)*5;
									} else{
										distance = Math.round(distance/50)*50;
									}
								} else {
									distance='-';
								}
								
								cell = document.createElement("td");
								cell.appendChild(document.createTextNode(distance));
								row.appendChild(cell);
								break;
							
							case 'Count':
							case 'Popularity':
								cell = document.createElement("td");
								cell.appendChild(document.createTextNode('-'));
								row.appendChild(cell);
								break;
							
							
						}
					}
					tablebody.appendChild(row);
				}
				table.appendChild(tablebody);
				
				sortables_init();
				dragtable.init();
				reGroup(form);
				document.getElementById('toolbar').style.display='';
			}
		}
	});
	
	
	
	return false;
}

function findColumn(table,text) {
	text = text.replace(/Month$/,'Day');
	text = text.replace(/Year$/,'Day');
	for (j=0;j<table.rows[0].cells.length;j++) {
		if (table.rows[0].cells[j].className == text) {
			return j;
		}
	}
	return -1;
}

function reGroup(form) {
	if (!form)
		form = document.forms['theForm'];
	numrows = form.elements['numrows'].value;
	showrows = form.elements['showrows'].value;
	groupby_text = form.elements['groupby'].options[form.elements['groupby'].selectedIndex].text;
	
	table = document.getElementById('myTable');
	
	groupby = findColumn(table,groupby_text);
	
	
	if (groupby == -1) {
		for (j=1;j<table.rows.length;j++) {
			if (j <= numrows) {
				table.rows[j].style.display = '';
			} else {
				table.rows[j].style.display = 'none';
			}
		}
		document.getElementById('info').innerHTML = (table.rows.length-1) + " images in total";
		return 0;
	}
	
	var done = new Object();
	var shown = 0;
	var clusters = 0;
	for (j=1;j<table.rows.length;j++) {
	 	value = ts_getInnerText(table.rows[j].cells[groupby]);
		if (groupby_text == 'Taken Month') {
			value = value.substring(1,7);
		} else if (groupby_text == 'Taken Year') {
			value = value.substring(1,4);
		}
				
		if (shown < numrows) {
			if (done[value]) {
				if (done[value] >= showrows) { 
					table.rows[j].style.display = 'none';
				} else {
					table.rows[j].style.display = '';
					shown = shown+1;
				}

				done[value] = done[value] + 1;
			} else {
				table.rows[j].style.display = '';
				shown = shown+1;

				done[value] = 1;
				clusters = clusters + 1;
			}
		} else {
			if (done[value]) {
				done[value] = done[value] + 1;
			} else {
				done[value] = 1;
				clusters = clusters + 1;
			}
			//we have enough rows already...
			table.rows[j].style.display = 'none';
			
		}
	}
	
	countColumn = findColumn(table,'Count');
	if (countColumn != -1) {
		for (j=1;j<table.rows.length;j++) {
			value = ts_getInnerText(table.rows[j].cells[groupby]);
			if (groupby_text == 'Taken Month') {
				value = value.substring(1,7);
			} else if (groupby_text == 'Taken Year') {
				value = value.substring(1,4);
	 		}
			if (done[value]) {
				table.rows[j].cells[countColumn].innerHTML = done[value];
			} else {
				table.rows[j].cells[countColumn].innerHTML = '0';
			}
			
		}
		document.getElementById('info').innerHTML = (clusters) + " different "+groupby_text+"'s";
	}
}

function countPopularity(form) {
	if (!form)
		form = document.forms['theForm'];

	popularity_text = form.elements['groupby'].options[form.elements['popularity'].selectedIndex].text;
	
	table = document.getElementById('myTable');
	
	popularity = findColumn(table,popularity_text);
	
	
	var done = new Object();
	for (j=1;j<table.rows.length;j++) {
	 	value = ts_getInnerText(table.rows[j].cells[popularity]);
	 	if (popularity_text == 'Taken Month') {
	 		value = value.substring(1,7);
	 	} else if (popularity_text == 'Taken Year') {
	 		value = value.substring(1,4);
	 	}
			
		if (done[value]) {
			done[value] = done[value] + 1;
		} else {
			done[value] = 1;
		}
	}
	
	popularityColumn = findColumn(table,'Popularity');
	if (popularityColumn > -1) {
		for (j=1;j<table.rows.length;j++) {
			value = ts_getInnerText(table.rows[j].cells[popularity]);
			if (popularity_text == 'Taken Month') {
				value = value.substring(1,7);
			} else if (popularity_text == 'Taken Year') {
				value = value.substring(1,4);
	 		}
	 		
			if (done[value]) {
				table.rows[j].cells[popularityColumn].innerHTML = done[value];
			} else {
				table.rows[j].cells[popularityColumn].innerHTML = '0';
			}
			
		}
	}
}



function viewThumbnails() {
	document.getElementById('light').style.display='block';
	document.getElementById('fade').style.display='block';
	
	table = document.getElementById('myTable');

	column = findColumn(table,'Image');
	
	var ele = document.getElementById('thumbnails');
	
	ele.innerHTML = '';
	
	num = table.rows.length;
	if (num > 100) {
		num = 100;
	}
	
	for (j=1;j<100;j++) {
		value = table.rows[j].cells[column].innerHTML;
		
		ele.innerHTML = ele.innerHTML + '<div style="float:left;position:relative; width:130px; height:130px"><div align="center">'+value+'</div></div>';
		
	}	
	
}