$(function() {
	$('table#output').html('Loading...');
	refreshTable();

	var $form = $('form#filter');
	if (columns.indexOf('gridimage_id') > -1) {
		var $div = $('<div>Show:</div>');
		$div.append('<input type=radio name=gridimage value="" checked>All &nbsp;');
		$div.append('<input type=radio name=gridimage value="1">With Image &nbsp;');
		$div.append('<input type=radio name=gridimage value="0">without Image &nbsp;');
		$div.appendTo($form);
	}
	$form.find('input[type=radio]').on('click',refreshTable);


	$('div#maincontent').on('click', 'a.popupLink', function(event) {
		//console.log(this.href);
		//window.open(this.href+"&inner=1");

	        document.getElementById('light').style.display='block';
	        document.getElementById('fade').style.display='block';
		document.getElementById('light').style.position = 'fixed';

		if (item_id = $(this).data('item_id'))
			current_item_id = item_id;
		document.getElementById('iframe').src = this.href+"&inner=1";

		event.preventDefault();
	});

});

function closePopup(trigger) {
	document.getElementById('light').style.display='none';
	document.getElementById('fade').style.display='none';
	if (trigger) {
		uniqueSerial++;
		refreshTable();
	}
}

var current_item_id = null;
function useImage(gridimage_id) {
	if (editing) {
		var data = {};
		data['id'] = current_item_id;
		data['gridimage_id'] = gridimage_id;
		data['submit'] = 1;
		$.post('edit_item.php?type_id='+feature_type_id, data, function(result) {
			//only update table after got response!
			uniqueSerial++;
        	        refreshTable();
		});
	}
	closePopup(false); //close right away
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

var lastGroupData = '?';
var currentPage = 1;
var totalPages = 1;
var currentSorter = 'sorter';
var currentDir = 'asc';
var uniqueSerial = 0;

function selectPage(page) {
	if (page > totalPages)
		currentPage = totalPages;
	else
		currentPage = page;
	refreshTable(); //should automatically only refresh the table, not the group data!
	renderPages(); //but this still needs to be updated even if no group data
}
function reorderTable() {
	var $this = $(this); //or event.target would be better?
	currentDir = $this.hasClass('up')?'desc':'asc'; //the table does down, so assending is desc!
	currentSorter = $this.parent().attr('title');
	currentPage = 1; //need to reset back to page 1!
	refreshTable();
	renderPages(); //but this still needs to be updated even if no group data
}

function refreshTable() {
	//var url = "https://api.geograph.org.uk/curated/sample.json.php"; //api can cache it!
	var url1 = "/features/features.json.php";
	var url2 = "/features/groups.json.php";
	var data = $('form#filter').serialize();

	data = data.replace(/&\w+=\.any\./g,''); //remove these to prevent duplicate requests
	data = data.replace(/&gridimage=(&|$)/,'$1'); //empty param is not needed!

	if (uniqueSerial)
		 data = data + '&serial='+uniqueSerial; //really just done to bust the cache!

	/////////////////////////////////////////

	if (data != lastGroupData) {
		currentPage = 1; //need to reset back to page 1!
		$.ajax({
		  dataType: "json",
		  url: url2,
		  data: data,
		  cache: true,
		  success: renderGroups
		});
		lastGroupData = data;
	}

	/////////////////////////////////////////
	//add data['page'] ==... (not in filter, so not sent to group by
	data = data + '&page=' + currentPage;
	data = data + '&order=' + currentSorter + '+' + currentDir;

	$.ajax({
	  dataType: "json",
	  url: url1,
	  data: data,
	  cache: true,
	  success: renderTable
	});

	/////////////////////////////////////////
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function renderGroups(data) {
	var $form = $('form#filter');
	var before = $form.serializeArray(); //returns a multi-dimeniaonl array
	var beforeArray = {};
	$.each(before,function(key,row) {
		beforeArray[row.name] = row.value;
	});

	$form.find('span.select').remove();
	$.each(data,function(key,rows) {
		if (key == 'count') {
			$('div#status').text(rows+' rows'); //its just a number in this case
			resultCount = rows;
			totalPages = Math.ceil(rows/20);
			renderPages();

		} else if (rows.length > 1 || (beforeArray[key] && beforeArray[key] != '.any.')) {
			var $div = $('<span class=select><select><option value=".any.">{any}</option></select></span>');
			var $select = $div.find('select');
			$div.prepend($('<label>').text(key+':').attr('for',key));
			$select.attr('name',key).attr('id',key);
			for(q=0;q<rows.length;q++) {
				$select.append($('<option>').attr('value',rows[q][key]).text(((rows[q][key] && rows[q][key] !== ' ')?rows[q][key]:'{blank}')+' ['+rows[q].count+' rows]'));
				if (!rows[q][key] || rows[q][key] === ' ')
					$select.append($('<option>').attr('value','.nonblank.').text('{non-blank}'));
			}
			$div.appendTo($form);
		}
	});
	$.each(before,function(key,row) {
		$form.find('select[name="'+row.name+'"]').val(row.value);
	});
	$form.find('select').on('change',refreshTable);
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function renderPages() {
	if (resultCount>20) {
		var links = new Array();
		var start = Math.max(1,currentPage-10);
		var end = Math.min(totalPages,currentPage+10);
		if (1 != start) {
			q = 1;
			links.push('<a href="javascript:void(selectPage('+q+'));">'+q+'</a>');
			links.push(' ... ');
		}
		for(q=start;q<=end;q++) {
			if (currentPage==q)
				links.push('<b>'+q+'</b>');
			else
				links.push('<a href="javascript:void(selectPage('+q+'));">'+q+'</a>');
		}
		if (totalPages != end) {
			q = totalPages;
			links.push(' ... ');
			links.push('<a href="javascript:void(selectPage('+q+'));">'+q+'</a>');
		}
		$('div.pages').html(links.join(' '));
	} else {
		$('div.pages').html('&nbsp;'); //so still has min-height!
	}
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function renderTable(data) {
	var $table = $('table#output');
	var $body = $('table#output tbody');
	if (!$body.length) {
		$table.empty();

		/////////////////////
		//render header row

		var $head = $('<thead/>')
		.appendTo($table);

		var $tr = $('<tr/>')
		.appendTo($head);

		for(q=0;q<columns.length;q++) {
			var name = columns[q];
			var $th = $('<th/>').text(name+' ').attr('title',name);
			$th.append($('<a class=up/>').html('&#9650;'));
			$th.append($('<a class=down/>').html('&#9660;'));
			$th.appendTo($tr);
		}

		if (editing) {
			$('<th/>').text('Edit').appendTo($tr);
		}

		$head.find('a').on('click',reorderTable);

		/////////////////////

		$body = $('<tbody/>')
                .appendTo($table);
	} else {
		$body.empty();
	}

	/////////////////////
	// render features

	$.each(data,function(index,row) {
		var $tr = $('<tr/>').data('item_id',row['feature_item_id']);
		if (row['gridref']) {
			var near_url = '/features/near.php?q='+encodeURIComponent(row['gridref'])+'&type_id='+feature_type_id;
			if (row['radius'] && row['radius']>1)
				near_url = near_url + "&dist=" + Math.floor(row['radius']*1.2);
			if (row['gridimage_id'] && row['gridimage_id']>0)
				near_url = near_url + "&img=" + parseInt(row['gridimage_id'],10);
			if (row['name'])
				near_url = near_url + "&name=" + encodeURIComponent(row['name']);
			if (editing)
				near_url = near_url + "&editing=true";
			var links_url = '/gridref/'+encodeURIComponent(row['gridref'])+'/links'
		}
		var edit_url = 'edit_item.php?id='+row['feature_item_id']+'&type_id='+feature_type_id;

		for(q=0;q<columns.length;q++) {
                        var name = columns[q];
			if (row[name] === null || row[name] === 'null') { //would output the word null
	                        $tr.append($('<td/>'));
			} else if (name == 'gridref' && row['gridref']) {
				$tr.append($('<td/>').append( $('<a></a>').text(row[name]).attr('href',links_url).attr('target','_blank') ));
			} else if (name == 'nearby_images') {
				if (row['gridref']) {
					$tr.append($('<td align=right/>').append( $('<a></a>').text(row[name]).attr('href',near_url).addClass('popupLink') ));
				} else {
					$tr.append($('<td align=right/>').text(row[name]));
				}
			} else if (name == 'gridimage_id') {
				if (row[name] && row['thumbnail']) {
					var $a = $('<a><img loading="lazy"/></a>');
					$a.find('img').attr('src',row['thumbnail']);
					$a.attr('href','/photo/'+row['gridimage_id']).attr('target','_blank');
		                        $tr.append($('<td/>').append($a));
				} else if (editing) {
		                        $tr.append($('<td/>').append( $('<a>Suggest an Image</a>').attr('href',row['gridref']?near_url:edit_url).addClass('popupLink') ));
				} else {
					$tr.append($('<td/>'));
				}
			} else if (name == 'sorter' || name == 'radius') {
	                        $tr.append($('<td align=right/>').text(row[name]));
			} else
	                        $tr.append($('<td/>').text(row[name]));
		}
		if (editing) {
                        $tr.append($('<td/>').append( $('<a>Edit</a>').attr('href',edit_url).addClass('popupLink') ));
		}
                $tr.appendTo($body);
	});

}
