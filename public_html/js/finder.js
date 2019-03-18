//uses jQuery, which it will load if NOT already loaded.  
//however does need jQl loaded (provided by geograph.js) 

//we have to be extra careful checking if a real jquery, as jQl creates a fake jQuery object. 
if (typeof jQuery === "undefined" || jQuery === null || typeof jQuery.fn === "undefined" || typeof jQuery.fn.load === "undefined") {
	jQl.loadjQ('https://ajax.googleapis.com/ajax/libs/jquery/1.5.1/jquery.min.js');
}


$(function() {
	function urlplus(input) {
		return encodeURIComponent(input).replace(/%20/g,'+').replace(/%2F/g,'/');
	}
	function htmlentities(input) {
		return $('<div />').text(input).html()
	}
	$.ajaxSetup({
	 	cache: true
	});

	var query = $("#mainquery").val();
	var total = $("#total_found").val();

	if ($('#alternates').length > 0) {

		$.ajax({
			url: 'https://api.geograph.org.uk/finder/suggestions.json.php',
			dataType: 'json',
			cache: true,
			data: {
				q: query,
				total: total
			},
			success: function(data) {
				if (data && data.correction) {
					$.each(data.correction, function(key,value) {
						$('#correction_prompt').append('&middot; Did you mean: <b></b>?').css({'font-style':'italic'});;
						$('#correction_prompt b').append(
							$('<a>').attr('href','/of/'+urlplus(key)).text(key)
						);
						if (value > 0) {
							$('#correction_prompt').append(' (about '+parseInt(value,10)+' images)');
						}
					});
				}
				if (data && data.suggestions && data.suggestions.length > 0) {
					$('#alternates').html("Alternative Queries: ").css({'color':'gray','padding':'10px'});
					$.each(data.suggestions, function(key,value) {
						$('#alternates').append(
							$("<span class=nowrap>&middot; </span>").append(
								$('<a>').attr('href','/of/'+urlplus(value['query'])).text(value['query']).attr('rel','nofollow')
							).append(' (about '+parseInt(value['total_found'],10)+' images)')
						).append('  ');
					});
				}
			}
		});
	}

	if ($('#location_prompt').length > 0 || $('#location_list').length) {

                $.ajax({
                        url: 'https://api.geograph.org.uk/finder/places.json.php',
                        dataType: 'json',
                        cache: true,
                        data: {
                                q: query,
                                new: 1
                        },
                        success: function(data) {

				//if only one result, display as quick clickable link!
				if (data && data.total_found == 1) {
					var value = data.items[0];
					if (value['name'].indexOf(value['gr']) == -1)
						value['name'] = value['name'] + "/" + value['gr'];
					var name = value['name'];
					if (name.indexOf('Grid Reference') == 0)
						name = htmlentities(name).replace(/\//,'/ <b>')+'</b>';
					else
						name = '<b>'+htmlentities(name).replace(/\//,'</b> /');
					$('#location_prompt').html('Or view images <i>near</i> <a>'+name+'</a>');
					$('#location_prompt a').attr('href','/near/'+urlplus(value['name']));

				//display a dropdown of places
				} else if (data && data.total_found > 1) {
					var prefixMatch = 0;
					$('#location_prompt').html('Or view images <i>near</i> <select id=near><option value="">Choose Location</select>');
					$.each(data.items, function(key,value) {
						if (value['name'].toLowerCase().indexOf(query.toLowerCase()) == 0)
							prefixMatch++;
						if (value['name'].indexOf(value['gr']) == -1)
	                                                value['name'] = value['name'] + "/" + value['gr'];
						$('#location_prompt select').append(
							$('<option/>').attr('value',value['name'])
								.text(value['name'].replace(/\/([A-Z]{1,2}\d+)/,' - $1') + 
									(value['localities']?", "+value['localities']:'')
								)
						);
					});
					$('#location_prompt').append("("+data.total_found+")");

					if (data.query_info)
		                                $('#location_prompt select').append($('<optgroup/>').attr('label',data.query_info));
					if (data.copyright)
		                                $('#location_prompt select').append($('<optgroup/>').attr('label',data.copyright));

					$('select#near').change(function() {
						location.href = '/near/'+urlplus(this.value);
					});

					if (prefixMatch > 1) {
						$('#correction_prompt').before(
							'<div style="font-size:0.9em;padding:4px;border-bottom:1px solid gray">'+
							'There are a <a href="/finder/groups.php?q=place:'+urlplus(query)+'&group=place\">number of places matching ['+htmlentities(query)+']</a>, below are <b>combined</b> keyword results.' +
							' To search near specific place, select from the dropdown above. Or <a href="/browser/#!/q='+urlplus(query)+'/display=group/group=place/n=4/gorder=images%20desc">View images grouped by nearby Place</a>'+
							'</div>'
						);
					}


					//display a text place list, only used on /place/ and when no keyword results!
					//duplicates the 'dropdown' but at least makes the page less empty ;)
					if ($('#location_list').length) {
						$('#location_list').html('Can try viewing images <i>near</i> <ul></ul>');
						$.each(data.items, function(key,value) {
							if (value['name'].indexOf(value['gr']) == -1)
                        	                                value['name'] = value['name'] + "/" + value['gr'];
							slug = (value['localities'] && value['name'].indexOf('Postcode') == -1 && value['name'].indexOf('Grid Ref') == -1)?'place':'near';
							$('#location_list ul').append(
								$('<li/>').append(
									$('<a/>').attr('href','/'+slug+'/'+urlplus(value['name']))
									.text(value['name'].replace(/\//,' - ') +
                                                	                        (value['localities']?", "+value['localities']:'')
									)
								)
							)
						});
	
						if (data.query_info)
		                                	$('#location_list').append($('<p/>').text(data.query_info));
						if (data.copyright)
		                	                $('#location_list').append($('<p/>').text(data.copyright));
					}
				}

				if (data && data.total_found > 0) {				
					//display a basic link, only used if there ar NO keyword results :)
					//duplicates the first location from dropdown
					if ($('#location_link').length) {
	                                        var value = data.items[0];
        	                                if (value['name'].indexOf(value['gr']) == -1)
                	                                value['name'] = value['name'] + "/" + value['gr'];
						if (data.total_found == 1)
							location.replace('/near/'+urlplus(value['name']));
                        	                var name = value['name'];
                                	        if (name.indexOf('Grid Reference') == 0)
                                        	        name = htmlentities(name).replace(/\//,'/ <b>')+'</b>';
	                                        else
        	                                        name = '<b>'+htmlentities(name).replace(/\//,'</b> /');
                	                        $('#location_link').html('Or view images <i>near</i> <a>'+name+'</a>');
                        	                $('#location_link a').attr('href','/near/'+urlplus(value['name']));
					}
				}

			}
		});
	}

	if ($('#geocode_results').length > 0) {
                $.ajax({
                        url: 'https://api.geograph.org.uk/finder/geocode.json.php',
                        dataType: 'json',
                        cache: true,
                        data: {
                                q: query
                        },
			success: function(data) {
				console.log(data);
				if (data && data && data.address) {
					$('#geocode_results').append('<div style="float:right"><a><img id=img1></a><a><img id=img2></a></div>')
						.append('Looks like you might have been entering an address? If you searching for <a id=address></a>'+
							' (or a business at that address). Click the map on the right to view images near that location. <hr style="clear:both"/>');
					$('#geocode_results a').attr('href','/near/'+urlplus(data.coord)).attr('rel','nofollow');
					$('#geocode_results a#address').text(data.address);
					$('#geocode_results img#img1').attr('src',data.map1);
					$('#geocode_results img#img2').attr('src',data.map2);
                                }
			}
		});	
	}
});


