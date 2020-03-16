var eastings = northings = null;

$(function () {

	$( "#qqq" ).autocomplete({
		minLength: 2,
		source: function( request, response ) {

			var url = "/finder/words-welsh.json.php?q="+encodeURIComponent(request.term);

			$.ajax({
				url: url,
				dataType: 'json',
				cache: true,
				success: function(data) {

					if (!data || data.length < 1) {
						//$("#message").html("Nothing Matching '"+request.term+"'"); //dont want to imply that no images!
						return;
					}

					var re=new RegExp(request.term.replace(/=/g,''),'i');

					var results = [];
					$.each(data.items, function(i,item){
						if (item.welsh) {
							results.push({label:item.welsh, tranlation:item.english});
						}
					});

					response(results);
				}
			});
		}
	})
	.data( "autocomplete" )._renderItem = function( ul, item ) {
		var re=new RegExp('('+$("#qqq").val().replace(/=/g,'').replace(/ /g,'|')+')','gi');
		return $( "<li></li>" )
			.data( "item.autocomplete", item )
			.append( "<a>" + item.label.replace(/=/g,'').replace(re,'<b>$1</b>') + " <small>(" + item.tranlation + ")</small></a>" )
			.appendTo( ul );
	};

	
	$( "#loc" ).autocomplete({
		minLength: 1,
		source: function( request, response ) {

			if ($( "input#wales:checked" ).length) {
				var url = "https://www.geograph.org.uk/finder/places-welsh.json.php?q="+encodeURIComponent(request.term);
			} else {
				var url = "https://www.geograph.org.uk/finder/places.json.php?q="+encodeURIComponent(request.term);
			}

			$.ajax({
				url: url,
				dataType: 'jsonp',
				jsonpCallback: 'serveCallback',
				cache: true,
				success: function(data) {

					if (!data || !data.items || data.items.length < 1) {
						$("#message").html("Does dim lleoedd yn cyfateb am '"+request.term+"'. Awgrym:  Rhowch gynnig ar sillafiad gwahanol, neu God post neu Gyfeirnod Grid OS");
						return;
					}
					var results = [];
					$.each(data.items, function(i,item){
						if (item.english)
							item.value = item.gridref+' '+item.english;
						else
							item.value = item.gr+' '+item.name;
						results.push(item);
					});
					results.push({value:'',label:'',title:data.query_info});
					results.push({value:'',label:'',title:data.copyright});
					response(results);
				}
			});
		},
                select: function(event,ui) {
			if (ui.item && ui.item.e && ui.item.n) {
				eastings = ui.item.e;
				northings = ui.item.n;
			}				
                }

	})
	.data( "autocomplete" )._renderItem = function( ul, item ) {
		var re=new RegExp('('+$("#loc").val()+')','gi');

		if (item.english) {
			if (item.welsh) {
				label = item.welsh.replace(re,'<b>$1</b>') + " (" + item.english.replace(re,'<b>$1</b>') +")";
			} else {
				label = item.english.replace(re,'<b>$1</b>')
			}
			item.title = item.county;
		} else {
			if (item.localities)
				item.title = item.localities;
			if (!item.title) item.title = '';
			label = item.label || item.name || '';
		}
		return $( "<li></li>" )
			.data( "item.autocomplete", item )
			.append( "<a>" + label + " <small> " + (item.gridref||item.gr||'') + "<br>&nbsp;&nbsp;&nbsp;&nbsp;" + item.title.replace(re,'<b>$1</b>') + "</small></a>" )
			.appendTo( ul );
	};


/////////////////////////////////////////////////////////

	if (q = getUrlParameter('q')) {
		$("#qqq").val(q);
		if (!getUrlParameter('loc'))
			setupLocationPrompt(q);
	}

	if (loc = getUrlParameter('loc'))
		$("#loc").val(loc); //todo, run query via API to get the exact location of the place

        if (context = getUrlParameter('context'))
                $("#context").val(context);

	if (q || loc)
		submitSearch($("#loc").get(0).form);

	if ($("#qqq").val().length > 2) {
		// $("#qqq").autocomplete( "search", $("#qqq").val() );
	}
});

function getUrlParameter(name) {
    name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
    var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
    var results = regex.exec(location.search);
    return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
};

window.onpopstate = function(event) {
	if (q = getUrlParameter('q'))
                $("#qqq").val(q);

        if (loc = getUrlParameter('loc'))
                $("#loc").val(loc);

	$("#wales").prop('checked',!!getUrlParameter('wales')); //always set this so can untick it!

        if (context = getUrlParameter('context'))
                $("#context").val(context);

        if (q || loc)
                submitSearch($("#loc").get(0).form, true);
};

/////////////////////////////////////////////////////////

function setupLocationPrompt(query) {
	if ($('#location_prompt').length > 0) {

                $.ajax({
                        url: 'https://api.geograph.org.uk/finder/places.json.php', //use the national, not just wales, may not be searching in wales!
                        dataType: 'json',
                        cache: true,
                        data: {
                                q: query,
                                new: 1,
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
					$('#location_prompt').html('Chwilio am lluniau wedi\'i dynnu ger <a>'+name+'</a>? (Cliciwch)');
					$('#location_prompt a').click(function(event) {
						$('#qqq').val('');
						$('#loc').val(value['name']);
						if (value['localities'] && value['localities'].indexOf('Wales') == -1)
							$('#wales').prop('checked',false);
						submitSearch($("#loc").get(0).form);
						event.preventDefault();
						$('#location_prompt').empty();
					}).attr('href','/near/'+urlplus(value['name'])); //still needed to make it look like a link!

				//display a dropdown of places
				} else if (data && data.total_found > 1) {
					var prefixMatch = 0;
					$('#location_prompt').html('...Neu lluniau wedi\'i dynnu ger <select id=near><option value="">Dewis lleoedd...</select>');
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

					//if (data.total_found && data.total_found > data.items.length)
					//	$('#location_prompt select').append($('<option/>').attr('value','...more').text('... View more place matches'));

					if (data.query_info) {
                                                if (m = data.query_info.match(/(\d+) matches.* ([\d\.]+) sec/))
							data.query_info = "Wedi canfod "+m[1]+" canlyniad mewn "+m[2]+" eiliad";
                                                $('#location_prompt select').append($('<optgroup/>').attr('label',data.query_info));
					}
					if (data.copyright)
		                                $('#location_prompt select').append($('<optgroup/>').attr('label',"Yn cynnwys data'r OS (c) Hawlfraint y Goron [a hawl cronfa ddata] 2018"));

					$('select#near').change(function() {
						if (this.value == '...more')
							location.href = '/place/'+urlplus(query)+'?more=1';
						else {
							//location.href = '/near/'+urlplus(this.value);
				                        $('#qqq').val('');
		                                        $('#loc').val(this.value);
							var opttext = this.options[this.selectedIndex].text;
							if (opttext && opttext.indexOf('Wales') == -1)
								$('#wales').prop('checked',false);
                	                                submitSearch($("#loc").get(0).form);
						}
					});

				}

				if (data && data.total_found > 0) {				
					//display a basic link, only used if there ar NO keyword results :)
					//duplicates the first location from dropdown
					if ($('#location_link').length) {
	                                        var value = data.items[0];
        	                                if (value['name'].indexOf(value['gr']) == -1)
                	                                value['name'] = value['name'] + "/" + value['gr'];
						//if (data.total_found == 1)
						//	location.replace('/near/'+urlplus(value['name']));
                        	                var name = value['name'];
                                	        if (name.indexOf('Grid Reference') == 0)
                                        	        name = htmlentities(name).replace(/\//,'/ <b>')+'</b>';
	                                        else
        	                                        name = '<b>'+htmlentities(name).replace(/\//,'</b> /');
                	                        $('#location_link').html('&middot; lluniau wedi\'i dynnu ger <a>'+name+'</a>? (Cliciwch i weld)');
						$('#location_link a').click(function(event) {
							$('#qqq').val('');
							$('#loc').val(value['name']);
							if (value['localities'] && value['localities'].indexOf('Wales') == -1)
								$('#wales').prop('checked',false);
							submitSearch($("#loc").get(0).form);
							event.preventDefault();
							$('#location_prompt').empty();
						}).attr('href','/near/'+urlplus(value['name']));
					}
				}

				$('form.finder input[type=submit]').click(function() {
					$('#location_prompt').empty();
				});
			}
		});
	}
}

/////////////////////////////////////////////////////////

var endpoint = "https://api.geograph.org.uk/api-facetql.php";

//function _call_cors_api(endpoint,data,uniquename,success,error) {

var perpage = 12;
var distance = 2000;

/////////////////////////////////////////////////////////

function submitSearch(form, skip_pop) {
	if (history.pushState && !skip_pop) {
		var data = $(form).serialize();
                history.pushState({data:data}, '', "?"+data);
        }

  var query = form.elements['q'].value;
  var location = form.elements['loc'].value;
  var geo = null; //todo, from gazetter!
  var loctext = '';

 /////////////

  if (location && location.length > 5) {
     if (gridref = location.toUpperCase().match(/(^|\/)\s*(\w{1,2}\d{2,10})/)) {
  
        //todo, get more detailed location from easting/norhting, returned from gazetter, maybe check right square??

	var grid=new GT_OSGB();
	var ok = false;
	if (grid.parseGridRef(gridref[2])) {
		ok = true;
		if (eastings && northings &&  //saved from the gazetter autocomplete!
				grid.eastings%1000 == 0 && grid.northings%1000 == 0 && // and that its a four figure!
				Math.floor(eastings/1000) == Math.floor(grid.eastings/1000) && // as a quick sanity check, check right square!
				Math.floor(northings/1000) == Math.floor(grid.northings/1000)) {
			grid.eastings = eastings;
			grid.northings = northings;
		}
	} else {
		grid=new GT_Irish();
		ok = grid.parseGridRef(gridref[2])
	}
        if (ok) {
		//convert to a wgs84 coordinate
		wgs84 = grid.getWGS84(true);

            geo=parseFloat(wgs84.latitude).toFixed(6)+","+parseFloat(wgs84.longitude).toFixed(6)+","+distance;
            loctext = " wedi'i dynnu ger "+gridref[2];
        }
     }
  }

  /////////////
  // try a translated word 

  if (query.length > 2) {
     var label = query.toLowerCase();
     //todo, this JUST looks for a single exact match at the moment! (although can be multiword). 
     _call_cors_api('https://www.geograph.org.uk/finder/words-welsh.json.php',{q:label},'WordLookup', function(data) {
        var foundWelsh = foundEnglish = foundCurated = false;
        if (data && data.items) {
           $.each(data.items,function(index,value) {

              if (value && value['welsh'] && value['welsh'].toLowerCase() == label) {
                 foundWelsh=true;
            
                 // curated Results
                 if (value['images'] && value['images'] > 2) {
                     var $div1 = $('#results #curatedResults');
                     fetchCuratedImages(value['english'],geo,$div1,"Delweddau rydyn ni wedi'u dewis ar gyfer ["+value['welsh']+']');
                     foundCurated = true;
                 }

                 // plainResults now contains the WELSH results

                 // translatedResults to contain ENGLISH results
                 var query2 = value.english;

                 var $div2 = $('#results #translatedResults');
                 fetchImages(query2,geo,$div2,"Delweddau sy'n cyfateb i dermau chwilio ["+query2+']'+loctext);

              } else if (value && value['welsh'] && !foundWelsh && value['english'] == label) {
                 foundEnglish=true;
            
                 // curated Results
                 if (value['images'] && value['images'] > 2) {
                     var $div1 = $('#results #curatedResults');
                     fetchCuratedImages(value['english'],geo,$div1,"Delweddau rydyn ni wedi'u dewis ar gyfer ["+value['welsh']+'] (o: '+value['english']+')');
                     foundCurated = true;
                 }

                 // plainResults now contains the ENGLISH results

                 // translatedResults to contain WELSH results
                 var query2 = value.welsh;

                 var $div2 = $('#results #translatedResults');
                 fetchImages(query2,geo,$div2,"Delweddau sy'n cyfateb i dermau chwilio ["+query2+']'+loctext);
              }
           });
        }
        if (!foundCurated)
            $('#results #curatedResults').empty();
        if (!foundWelsh && !foundEnglish)
            $('#results #translatedResults').empty();
     });
  } else {
     $('#results #curatedResults, #results #translatedResults').empty();
  }

  /////////////
  // finally general search results 

  if (query.length < 1 && !form.elements['wales'].checked && !geo) {
     $("#message").html('Rhowch ymholiad');
     return false;
  }
  var $div3 = $('#results #plainResults');
  fetchImages(query,geo,$div3,"Delweddau sy'n cyfateb i dermau chwilio ["+query+']'+loctext);

  /////////////

  var $div4 = $('#results #contentResults');
  if (!location) {
	  fetchContent(query,$div4,"Cyfatebiadau Casgliad Posibl");
  } else {
	$div4.empty();
  }

  /////////////

  //so the form doesnt actully submit
  return false;
}

/////////////////////////////////////////////////////////

var images = new Array();

function loadImage() {
    var link = this;
    if (!link || !link.href || !(m = link.href.match(/photo\/(\d+)/))) {
       return false;
    }
    var gridimage_id = parseInt(m[1],10);
    if (!images || !images[gridimage_id])
       return false;

    var value = images[gridimage_id];
    $element = $('#previewImage');

    //open at start, so can start loading while still populating??
    openLightbox($element);

    var $result = $element.find('#mainImage').empty();

    if (!value.hash && value.thumbnail && (m = value.thumbnail.match(/\/\d{6,}_(\w{8})/)))
        value.hash = m[1];
    value.full = getGeographUrl(value.id, value.hash, 'full');


    $result.append('<div class="part1"></div>');
    $result.append('<div class="part2"></div>');
    var $part1 = $result.find('.part1');
    var $part2 = $result.find('.part2');

    //TITLE BAR
    $part1.append('<div class="title"><a href="/photo/'+value.id+'" target="_blank"><b>'+value.title+'</b></a> gan <a href="/profile/'+value.user_id+'">'+value.realname+'</a><br/>'+
            'Yn sgw&acirc;r <a href="/gridref/'+value.grid_reference+'" target="_blank" id="gridref">'+value.grid_reference+"</a>, wedi'i dynnu ar <b>"+space_date(value.takenday || value.imagetaken)+'</b></span></div>')

    //IMAGE
    $part1.append('<div style="min-height:300px"><a href="/photo/'+value.id+'" title="'+value.grid_reference+' : '+value.title+' gan '+value.realname+'"><img src="'+value.full+'" id="full"/></a></div>')

    //CC MESSAGE
    $part1.append('<div class="ccmessage"><a rel="license" href="https://creativecommons.org/licenses/by-sa/2.0/"><img alt="Creative Commons Licence [Some Rights Reserved]" src="https://creativecommons.org/images/public/somerights20.gif"></a> &copy; Copyright <a title="View profile" href="/profile/'+value.user_id+'" xmlns:cc="http://creativecommons.org/ns#" property="cc:attributionName" rel="cc:attributionURL dct:creator">'+value.realname+'</a>  ac wedi.i drwyddedu ar gyfer cael ei <a href="/reuse.php?id='+value.gridimage_id+'">ail-ddefnyddio</a> o dan <a rel="license" href="https://creativecommons.org/licenses/by-sa/2.0/" about="'+value.full+'" title="Creative Commons Attribution-Share Alike 2.0 Licence">Drwydded Creative Commons</a>.</div>')

    //BUTTONS
    $part1.append('<div class=buttons>'+
       '&middot; <a href="/photo/'+value.id+'">Cliciwch i weld delwedd maint llawn</a> '+
       '&middot; <a href="/reuse.php?id='+value.id+'">Defnyddio</a> '+
       '&middot; <a href="/more.php?id='+value.id+'" id="larger" style="display:none;font-weight:bold">Meintiau mwy ar gael</a> '+
       '&middot; <a href="/stamp.php?id='+value.id+'">Llwytho\'r ddelwedd &acirc; dyfrnodau arni i lawr</a> '+
       '&middot; <a href="/reuse.php?id='+value.id+'&download='+value.hash+'" id="download">Llwytho\'r ddelwedd i lawr</a>.</div>');

    //DESCRIPTION
    $part2.append('<div id="maindesc"></div>');

    //MAP
	//https://t0.geograph.org.uk/tile-static.php?source=OSM-cymru&z=13&w=640&h=350&mlat=53.204592&mlon=-4.16174
        //https://openstreetmap.cymru?h=52.95404183016033&ll=-3.9584255218505864&ch=14
    if (value.wgs84_lat && value.wgs84_long) {
	var lat = (value.wgs84_lat<40)?rad2deg(value.wgs84_lat):parseFloat(value.wgs84_lat);
	var lng = (value.wgs84_lat<40)?rad2deg(value.wgs84_long):parseFloat(value.wgs84_long);
	var source = (document.getElementById('wales').checked)?"OSM-cymru":"OSM";
        $part2.append('<a href="https://openstreetmap.cymru/?h='+lat+'&ll='+lng+'&ch=13&p=1" class=osm><img src="https://t0.geograph.org.uk/tile-static.php?source='+source+'&z=13&w=640&h=350&mlat='+lat+'&mlon='+lng+'"/></a>');
        $part2.find("a.osm img").hover(function() {
		this.src = this.src.replace(/z=\d+/,'z=6');
        },function() {
		this.src = this.src.replace(/z=\d+/,'z=13');
	});
    }

    //FOOTER
    $part2.append("<br><br><div><i>Mae'r cyfrannwr yn caniat&aacute;u i chi ail-ddefnyddio'r llun ar gyfer unrhyw bwrpas, ar yr amod eich yn cydnabod hynny. Darllenwch y manylion llawn ar <a href='https://creativecommons.org/licenses/by-sa/2.0/'>Weithred Trwydded CC</a></i></div>");


    loadDescription(value.id);

    if (value.original && value.original > 1)
        $('#larger').show('slow');
    if (value.largest && value.largest > 640)
        $('#larger').show('slow');
}

function setupLoadImage($element) {
    // https://stackoverflow.com/questions/2180326/jquery-event-model-and-preventing-duplicate-handlers
      //although of course, this is normally called right after creation so wont be any existing!
    $element.find('.thumbs .thumb a').unbind('click.LoadImage',loadImage);
    $element.find('.thumbs .thumb a').bind('click.LoadImage',loadImage);
}

/////////////////////////////////////////////////////////


function openLightbox($element) {
    $('#lightbox-background').show(); 
    $element.show('fast').addClass('lightbox'); 
}
function closeLightbox($element) {
    $('#lightbox-background').hide(); 
    $element.hide('fast'); 
}

$(function() {
   $('#lightbox-background').click(function() {
      closeLightbox($('.lightbox'));
   });
});

/////////////////////////////////////////////////////////

function fetchContent(query,$output,title) {
  var data = {
     match: "@title "+query+" @source -themed"+(location.host.indexOf('schools')>-1?' -blogs':''),
     cc: 1,
     select: "id,title,url,asource,aimages"
  };
  _call_cors_api(
    endpoint,
    data,
    'contentCallback',
    function(data) {
     $output.empty();
     if (data && data.rows) {

        if (title) {
           $output.text(title);
        }

	$output.append('<select id=content_id><option value="">Dewis...</select>');
	$.each(data.rows, function(key,value) {
		//todo, do somthing with asource?
		$output.find('select').append(
			$('<option/>').attr('value',value['url'])
				.text(value['title']+' (gyda '+value['aimages']+' lluniau)')
		);
	});

	if (data.meta.total_found) {
		if (data.meta.total_found > data.rows.length) {
			$output.find('select').append(
				$('<option/>').attr('value','/content/?q='+encodeURIComponent(query)+'&scope=all&in=title')
					.text(data.meta.total_found+' canlyniad (gweld pob un...)')
			);
		}
		$output.append(' ('+data.meta.total_found+' canlyniad)');
	}

        $('select#content_id').change(function() {
                location.href = this.value;
        });
    }
  });
}


/////////////////////////////////////////////////////////

function fetchCuratedImages(label,geo,$output,title) {
  var data = {
     label: label,
  };
  var url = '/curated/sample.php?label='+encodeURIComponent(label);
  if (document.getElementById('wales').checked) {
     data.region = "Wales";
     url = url + '&amp;region=Wales';
  }
  if (selected = document.getElementById('context').value) {
     data.context = selected;
     url = url + '&amp;context='+encodeURIComponent(selected);//dont think this used, but may as well send it!
  }
  if (geo) {
     data.geo = geo;
     url = url + '&amp;geo='+encodeURIComponent(geo);//dont think this used, but may as well send it!
  }

  $("#message").text('Arhoswch os gwelwch yn dda ['+label+']...');

  _call_cors_api(
    'https://www.geograph.org.uk/curated/sample.json.php',
    data,
    'curatedCallback',
    function(data) {
      if (data && data.images && data.images.length > 2) {
        $output.empty().html('<div class="thumbs shadow"></div>');
        if (title) {
           $output.prepend('<h3></h3>');
           $output.find('h3').text(title);
        }
     
        var $thumbs = $output.find(".thumbs");
        $.each(data.images,function(index,value) {
          value.id = value.gridimage_id;
          
          $thumbs.append('<div class="thumb"><a href="/photo/'+value.gridimage_id+'" title="'+value.grid_reference+' : '+value.title+' gan '+value.realname+' - Cliciwch i weld delwedd maint llawn" onclick="loadImage('+index+'); return false;"><img src="'+value.thumbnail+'" onerror="refreshImage(this);"/></a></div>');
          images[value.gridimage_id] = value;
        });

        if (data.label && data.label[label] && data.label[label] >=20) {
           $output.append("<p>"+data.label[label]+' lluniau rydyn ni wedi\'u dewis. <a href="'+url+'">Gweld Nesaf</a></p>');

           $output.append("<p>Dydyn ni ond wedi casglu nifer fechan o ddelweddau hyd yma, mae chwiliad allweddair yn gallu cael canlyniadau gwell</p>");

           $output.find('h3').append(' (20 lluniau gyntaf)');
        }
        setupLoadImage($output);
      } else {
        $output.empty();
      }
    });
}

/////////////////////////////////////////////////////////

function fetchImages(query,geo,$output,title,order) {

  var data = {
     select: "id,title,grid_reference,realname,hash,user_id,takenday,wgs84_lat,wgs84_long,original",
     match: query,
     limit: perpage,
  };
  var url = "/browser/#!/q="+encodeURIComponent(query).replace(/%20/g,'+');

  if (document.getElementById('wales').checked) {
     data.match = data.match + " @country Wales";
     url = url + '/country+%22Wales%22'
  }
  if (selected = document.getElementById('context').value) {
     data.match = data.match + ' @contexts "'+selected+'"';
     url = url + '/contexts+'+encodeURIComponent('"'+selected+'"');
  }
  if (geo) {
     data.geo = geo;
     url = url + '/loc='+encodeURIComponent(document.getElementById('loc').value)+'/dist=2000';
  }

    if (page && page > 1) {
      data.offset=((page-1)*data.limit);
    } else {
      var page = 1;
    }

  if (!query && geo && typeof order === 'undefined')
     order = 'distance';

  if (order) {
    switch(order) {//defaults to relevence!
      case 'taken_down':  data.order="takendays DESC"; data.option='ranker=none';  break;
      case 'taken_up':  data.order="takendays ASC"; data.option='ranker=none';  break;
      case 'submitted_down':  data.order="id DESC"; data.option='ranker=none';  break;
      case 'submitted_up':  data.order="id ASC"; data.option='ranker=none';  break;
      case 'spread':  data.order="sequence ASC"; data.option='ranker=none';  break;
      case 'hash':  data.order="hash ASC"; data.option='ranker=none';  break;
      case 'score':  data.order="score DESC"; data.option='ranker=none';  break;
      case 'distance':  data.order="geodist ASC"; data.option='ranker=none';  break;
      case 'random':  data.order="RAND()";  break;
    }
    if (data.order) //just checks it set something really!
        url = url + '/sort='+encodeURIComponent(order);
  }

  $("#message").text('Arhoswch os gwelwch yn dda ['+query+']...');

  _call_cors_api(
    endpoint,
    data,
    'serveCallback',
    function(data) {
     if (data && data.rows) {

        $("#message").empty();

        $output.empty().html('<div class="thumbs shadow"></div>');
        if (title) {
           $output.prepend('<h3></h3>');
           $output.find('h3').text(title)
	           .prepend('<div style=float:right><select title="Trefnu yn &ocirc;l"/></div>');
	   var $ele = $output.find('select');
           $ele.append( $('<option/>').attr('value','').text('Mywaf perthnasol'));
           if (geo)
               $ele.append( $('<option/>').attr('value','distance').text('Pellter'));
	   $ele.append( $('<option/>').attr('value','taken_down').text('Dynnu - Diweddaraf i\'r henaf'));
	   $ele.append( $('<option/>').attr('value','taken_up').text('Dynnu - Henaf i\'r diweddaraf'));
	   $ele.append( $('<option/>').attr('value','submitted_down').text('Cyflwynwyd - Diweddaraf i\'r henaf'));
	   $ele.append( $('<option/>').attr('value','submitted_up').text('Cyflwynwyd - Henaf i\'r diweddaraf'));
	   $ele.append( $('<option/>').attr('value','hash').text('Mewn unrhyw drefn'));
           $ele.append( $('<option/>').attr('value','spread').text('Daearyddol'));
           $ele.append( $('<option/>').attr('value','score').html('Sg&ocirc;r'));
           if (order)
	      $ele.val(order);
           $ele.change(function() {
  		fetchImages(query,geo,$output,title,this.value);
	   });
        }
        var last = '';
        var $thumbs = $output.find(".thumbs");
        $.each(data.rows,function(index,value) {
          value.gridimage_id = value.id;
          value.thumbnail = getGeographUrl(value.id, value.hash, 'med');
          
          $thumbs.append('<div class="thumb"><a href="/photo/'+value.gridimage_id+'" title="'+value.grid_reference+' : '+value.title+' gan '+value.realname+' - Cliciwch i weld delwedd maint llawn" onclick="loadImage('+index+'); return false;"><img src="'+value.thumbnail+'" onerror="refreshImage(this);"/></a></div>');
          images[value.id] = value;
        });

        $output.append("<p><i>wedi canfod "+data.rows.length+" o "+data.meta.total_found+" canlyniad mewn "+data.meta.time+" eiliad</i></p>");
 
        if (data.meta.total_found > data.rows.length) {
             $output.append('<div><a href="'+url+'">Mwy llyniau &gt;&gt;&gt;</a> (<a href="'+url+'/display=map/pagesize=40">ar fap</a>)</div>');
        }
        setupLoadImage($output);

        if (data.rows.length == 1 && $('.thumbs a').length == 1) { //check also that only one thumbnail being displayed overall
            $output.find('.thumbs a').click();
        }

    } else {
        $("#message").html("Does dim Delweddau'n cyfateb. Gwiriwch y sillafu a rhoi cynnig arall arni.");
        $output.empty().html("<p>Does dim Delweddau'n cyfateb. Gwiriwch y sillafu a rhoi cynnig arall arni.</p><span id=\"location_link\"></span>");
    }
  });
}

/////////////////////////////////////////////////////////

function loadDescription(id) {

  _call_cors_api(
    'https://www.geograph.org.uk/stuff/description.json.php',
    {id: id},
    'loaddesc',
    function(data) {
      if (data && (data.comment || data.snippets)) {
        if (data.comment) {
          if ($("#qqq").val().length > 2) {
            var re=new RegExp('('+$("#query").val()+')','gi');
            data.comment = data.comment.replace(re,'<b>$1</b>');
          }
          $('#maindesc').html(data.comment.replace(/ href=/g,' target="_blank" href='));
        }
        if (data.snippets) {
          if (data.snippets.length == 1 && !data.comment) {
            $('#maindesc').html(data.snippets[0].comment.replace(/ href=/g,' target="_blank" href='));
            $('#maindesc').append('<br/><br/><small>See other images of <a href="/snippet/'+data.snippets[0].snippet_id+'" title="See other images in '+data.snippets[0].title+' by '+data.snippets[0].realname+'" target="_blank">'+data.snippets[0].title+'</a></small>');
          } else {
            if (data.comment) {
              $('#maindesc').append('<br/><br/>');
            }
            for(var q=0;q<data.snippets.length;q++) {
              $('#maindesc').append('<div><b>'+(data.snippets_as_ref?(q+1)+'. ':'')+'<a href="/snippet/'+data.snippets[q].snippet_id+'" title="See other images in '+data.snippets[q].title+' by '+data.snippets[q].realname+'" target="_blank">'+data.snippets[q].title+'</a></b></div>');
              $('#maindesc').append('<blockquote>'+data.snippets[q].comment.replace(/ href=/g,' target="_blank" href=')+'</blockquote>');
            }
          }
        }
        setTimeout(function() { //for some reaons, the div can have a bigger height initially?
          if ($('#maindesc').innerHeight() > 100) {
            $('#maindesc').css({height: '100px',overflow: 'auto'}).click(function() {
                $('#maindesc').css({height: 'inherit'});
                $('#descopener').hide();
            }).prepend('<div style="float:right" id="descopener">[mwy...]</div>');
            
          }
        }, 200);
      } else if (data && data.error) {
        var tmp = ele.find('> div').html();
        ele.html(tmp+"<br/><br/>Sorry. This image is no longer available");
      }
    }
  ); 
}

/////////////////////////////////////////////////////

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

function refreshImage(source) {
     //calling the ombed api should in theory cause the small thumbnail get created
     $.getJSON("https://api.geograph.org.uk/api/oembed?url="+encodeURIComponent(source.src)+"&output=json&callback=_",function(data) {
         source.onerror = null;
         source.src = source.src;
     });
}

function space_date(datestr) {
    if (datestr && datestr.length == 8)
       return datestr.substring(0,4)+'-'+datestr.substring(4,6)+'-'+datestr.substring(6,8);
    return datestr;
}


// function to allow using cors if possible
function _call_cors_api(endpoint,data,uniquename,success,error) {
  crossDomain = true; //todo/tofix!
  if (uniquename && crossDomain && !jQuery.support.cors) {
    //use a normal JSONP request - works accorss domain
    endpoint += (endpoint.indexOf('?')>-1?'&':'?')+"callback=?&";
    $.ajax({
      url: endpoint,
      data: data,
      dataType: 'jsonp',
      jsonpCallback: uniquename,
      cache: true,
      success: success,
      error: error
    });
  } else {
    //works as a json requrest - either same domain, or a browser with cors support
    $.ajax({
      url: endpoint,
      data: data,
      dataType: 'json',
      cache: true,
      success: success,
      error: error
    });
  }
}

	
	function _fullsize(thumbnail) {
		return thumbnail.replace(/_\d+x\d+\.jpg$/,'.jpg').replace(/s[1-9]\.geograph/,'s0.geograph');
	}


        function urlplus(input) {
                return encodeURIComponent(input).replace(/%20/g,'+').replace(/%2F/g,'/');
        }
        function htmlentities(input) {
                return $('<div />').text(input).html()
        }

