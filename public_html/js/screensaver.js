// really simple screensaver thingy
// note this needs either jquery, or geograph.js loaded

        //we have to be extra careful checking if a real jquery, as jQl creates a fake jQuery object.
        if (typeof jQuery === "undefined" || jQuery === null || typeof jQuery.fn === "undefined" || typeof jQuery.fn.load === "undefined") {
                jQl.loadjQ('https://ajax.googleapis.com/ajax/libs/jquery/1.5.1/jquery.min.js');
        }

var STimer = null;
var SIndex = 0;
var SX = 0;
var SY = 0;

$(function() {
	var disableMouseMove = false;
	$(document).keyup(function(e) {
	        if (e.key === "F11") {
        	        disableMouseMove = true; //The movemouse event will fire (becuse cursor possition changes relative to document!)
			setTimeout("disableMouseMove = false;",500);
        	}
	});

	$('body').mousemove(function(event) {
		if (disableMouseMove)
			return;
		if (SX == event.pageX && SY == event.pageY) //check the mouse really has moved!
			return;
		SX = event.pageX; SY = event.pageY;
		if (STimer)
			clearTimeout(STimer);
		if ($("#SSaver:hidden").length) {
			STimer = setTimeout(showSaver,30000);
		} else {
			$('#SSaver').hide();
		}
	}).scroll(function(event) {
		if (STimer) clearTimeout(STimer); STimer = null;
	}).keyup(function(event) {
                if (STimer) clearTimeout(STimer); STimer = null;
        });;

	$('body').append('<div id=SSaver></div>');
	$('#SSaver').hide().css({'position':'fixed', 'top':'0', 'left':'0', 'right':'0', 'bottom':'0', 'z-index':'10000', 'width':'100%', 'height':'100%'});
	$('#SSaver').append('<div id=STitle></div><div id=SDate></div><div id=SCredit></div><div id=SMessage>Geograph ScreenSaver&trade;</div>');
	$('#SSaver div').css({'position':'absolute', 'font-size':'1.3em', 'color':'white', 'text-shadow':'0px 0px 13px rgba(0, 0, 0, 1)', 'font-weight':' bold', 'opacity':'0.6', 'padding':'3px'});
	$('#STitle').css({'top':'0', 'left':'0', 'right':'0', 'text-align':'center', 'font-size':'2.2em'});
	$('#SDate').css({'bottom':'0', 'left':'0'});
	$('#SCredit').css({'bottom':'0', 'right':'0', 'text-align':'right'});
	$('#SMessage').css({'bottom':'0', 'left':'0', 'right':'0', 'text-align':'center', 'font-size':'0.9em'});
});

function showSaver() {
	if (SImages[SIndex]['url'].indexOf('/') ==0)
		SImages[SIndex]['url'] = 'https://s0.geograph.org.uk'+SImages[SIndex]['url'];

	$('#SSaver').fadeIn('slow').css({'background':' url('+SImages[SIndex]['url']+') no-repeat center center fixed', 'background-size':' cover'});
	$('#STitle').text(SImages[SIndex]['grid_reference'] + ': ' + SImages[SIndex]['title']);
	$('#SDate').text(SImages[SIndex]['taken']);
	$('#SCredit').text('by '+SImages[SIndex]['realname']);
	SIndex++;
	if (!SImages[SIndex])
		SIndex=0;

	STimer = setTimeout(showSaver,2000+Math.round(Math.random()*10000));
}


var SImages = new Array();

//SImages.push({title:'Great Litchfield Down',url:'/photos/30/62/306243_e3088bf9.jpg',taken:'taken 10 years ago',realname:'Andrew Smith'});
//SImages.push({title:'',url:'',taken:'',realname:''});


SImages = [{"url":"\/geophotos\/01\/99\/57\/1995755_fe17c933_1024x1024.jpg","title":"Eastleigh Lakeside Railway","grid_reference":"SU4417","taken":"Wednesday,  4 August, 2010","realname":"Rob Candlish","id":"1995755"},{"url":"\/geophotos\/05\/31\/57\/5315741_88db46fb_1024x1024.jpg","title":"Riverside houses in Enfield","grid_reference":"TQ3296","taken":"Wednesday, 15 March, 2017","realname":"Marathon","id":"5315741"},{"url":"\/geophotos\/04\/92\/01\/4920196_cf85baed_1024x1024.jpg","title":"Teversham: towards the church","grid_reference":"TL4958","taken":"Sunday, 24 April, 2016","realname":"John Sutton","id":"4920196"},{"url":"\/geophotos\/05\/31\/52\/5315223_144dd80a_1024x1024.jpg","title":"The Hope Fleet (3)","grid_reference":"TQ7678","taken":"Wednesday, 15 March, 2017","realname":"Stefan Czapski","id":"5315223"},{"url":"\/geophotos\/05\/28\/37\/5283782_c038526b_1024x1024.jpg","title":"Loch Toll an Lochain","grid_reference":"NH0783","taken":"16 March 1964","realname":"Julian Paren","id":"5283782"},{"url":"\/geophotos\/04\/22\/68\/4226895_78937fb3_1024x1024.jpg","title":"Randolph's Leap from the footpath to Daltulich Bridge","grid_reference":"NJ0049","taken":"Thursday, 30 October, 2014","realname":"Julian Paren","id":"4226895"},{"url":"\/geophotos\/03\/93\/31\/3933193_6d92a284_1024x1024.jpg","title":"The Shard, London SE1","grid_reference":"TQ3280","taken":"Friday, 11 April, 2014","realname":"Christine Matthews","id":"3933193"},{"url":"\/geophotos\/05\/31\/97\/5319742_0389a334_1024x1024.jpg","title":"Driveway to Poorton Hill","grid_reference":"SY5297","taken":"Monday, 13 March, 2017","realname":"Derek Harper","id":"5319742"},{"url":"\/geophotos\/05\/31\/63\/5316305_a0915f15_1024x1024.jpg","title":"Daffodils on East Wickham Open Space","grid_reference":"TQ4676","taken":"Thursday, 16 March, 2017","realname":"Marathon","id":"5316305"},{"url":"\/geophotos\/05\/31\/89\/5318919_d002cba5_1024x1024.jpg","title":"A tanker at the lane end","grid_reference":"TL3139","taken":"Tuesday, 21 March, 2017","realname":"John Sutton","id":"5318919"},{"url":"\/geophotos\/05\/31\/73\/5317393_1c444d57_1024x1024.jpg","title":"RSPB Pagham Harbour Nature Reserve","grid_reference":"SZ8794","taken":"Friday, 17 March, 2017","realname":"Peter Holmes","id":"5317393"},{"url":"\/geophotos\/01\/80\/88\/1808816_7b28453c_1024x1024.jpg","title":"Leeds Liverpool Canal","grid_reference":"SD9851","taken":"Friday, 16 April, 2010","realname":"David Dixon","id":"1808816"},{"url":"\/geophotos\/03\/83\/13\/3831340_70e3b6f7_1024x1024.jpg","title":"North end of Mochrum Loch","grid_reference":"NX3053","taken":"Thursday, 30 January, 2014","realname":"David Baird","id":"3831340"},{"url":"\/geophotos\/05\/31\/51\/5315147_dd1af17f_1024x1024.jpg","title":"Kedington: St Peter and St Paul","grid_reference":"TL7047","taken":"Friday, 17 March, 2017","realname":"John Sutton","id":"5315147"},{"url":"\/geophotos\/05\/20\/16\/5201642_3b0908da_1024x1024.jpg","title":"November afternoon at Kew, 9","grid_reference":"TQ1876","taken":"Friday, 18 November, 2016","realname":"Jonathan Billinger","id":"5201642"},{"url":"\/geophotos\/04\/23\/58\/4235832_e91690de_1024x1024.jpg","title":"Blood Swept Lands and Seas of Red, Tower Poppies","grid_reference":"TQ3380","taken":"Thursday,  6 November, 2014","realname":"Oast House Archive","id":"4235832"},{"url":"\/photos\/40\/62\/406259_e2980607_1024x1024.jpg","title":"Oilseed rape crop, Littlecote Park Farm, near Froxfield","grid_reference":"SU2969","taken":"Thursday, 19 April, 2007","realname":"Brian Robert Marshall","id":"406259"},{"url":"\/geophotos\/05\/29\/76\/5297664_a6bba981_1024x1024.jpg","title":"The Beeston Bee Man","grid_reference":"SK5236","taken":"Wednesday,  1 March, 2017","realname":"David Lally","id":"5297664"},{"url":"\/geophotos\/05\/32\/07\/5320740_afb35937_1024x1024.jpg","title":"Eyeworth Lodge Farmhouse","grid_reference":"TL2544","taken":"Tuesday, 21 March, 2017","realname":"John Sutton","id":"5320740"},{"url":"\/geophotos\/02\/83\/19\/2831908_86167864_1024x1024.jpg","title":"An international gathering","grid_reference":"SJ1605","taken":"Sunday,  4 September, 2011","realname":"K  A","id":"2831908"},{"url":"\/geophotos\/05\/31\/66\/5316695_73af3b74_1024x1024.jpg","title":"Eskadale","grid_reference":"NH4640","taken":"Monday, 25 July, 2016","realname":"Richard Webb","id":"5316695"},{"url":"\/geophotos\/04\/92\/47\/4924759_36244545_1024x1024.jpg","title":"Working on the railway","grid_reference":"TL4554","taken":"Wednesday, 27 April, 2016","realname":"John Sutton","id":"4924759"},{"url":"\/geophotos\/05\/29\/00\/5290055_76d16933_1024x1024.jpg","title":"Humber Estuary","grid_reference":"TA1128","taken":"Tuesday, 21 February, 2017","realname":"Bernard Sharp","id":"5290055"},{"url":"\/geophotos\/03\/87\/37\/3873725_8de8116d_1024x1024.jpg","title":"Early morning by the swollen River Thames","grid_reference":"SU5495","taken":"Tuesday,  4 March, 2014","realname":"Steve Daniels","id":"3873725"},{"url":"\/geophotos\/04\/40\/05\/4400534_bf96024c_1024x1024.jpg","title":"Lane approaching Upper Coberley","grid_reference":"SO9715","taken":"Saturday, 21 March, 2015","realname":"Derek Harper","id":"4400534"},{"url":"\/geophotos\/05\/32\/12\/5321250_6d6cf9dc_1024x1024.jpg","title":"On Northfield Road in early spring","grid_reference":"TL2641","taken":"Tuesday, 21 March, 2017","realname":"John Sutton","id":"5321250"},{"url":"\/geophotos\/05\/09\/76\/5097677_df1acb20_1024x1024.jpg","title":"Winter driving in Glen Clova","grid_reference":"NO3667","taken":"Saturday, 11 February, 1978","realname":"Alan Reid","id":"5097677"},{"url":"\/geophotos\/02\/63\/27\/2632778_f484fa6d_1024x1024.jpg","title":"Harrowing power","grid_reference":"SK9585","taken":"Thursday, 29 September, 2011","realname":"Jonathan Thacker","id":"2632778"},{"url":"\/geophotos\/05\/31\/60\/5316083_be7a9f27_1024x1024.jpg","title":"Agricultural land above Salt Lake, Fife","grid_reference":"NO5814","taken":"Monday, 13 March, 2017","realname":"Claire Pegrum","id":"5316083"},{"url":"\/geophotos\/04\/49\/68\/4496848_2bb142fd_1024x1024.jpg","title":"Three local heroes and a monitoring platform","grid_reference":"TR3241","taken":"Friday, 24 April, 2015","realname":"John Baker","id":"4496848"},{"url":"\/geophotos\/05\/25\/08\/5250824_899fdc17_1024x1024.jpg","title":"River Greta in Keswick, 1963","grid_reference":"NY2623","taken":" 7 April 1963","realname":"John Carter","id":"5250824"},{"url":"\/geophotos\/05\/32\/14\/5321405_90790363_1024x1024.jpg","title":"On the way to Eyeworth","grid_reference":"TL2640","taken":"Tuesday, 21 March, 2017","realname":"John Sutton","id":"5321405"},{"url":"\/geophotos\/05\/16\/87\/5168792_3324a9ac_1024x1024.jpg","title":"River Witham","grid_reference":"TF0571","taken":"Tuesday, 25 October, 2016","realname":"Richard Croft","id":"5168792"},{"url":"\/geophotos\/05\/31\/96\/5319627_33449d46_1024x1024.jpg","title":"Cheriton Fitzpaine : Grassy Field & Sheep","grid_reference":"SS8605","taken":"Sunday, 12 March, 2017","realname":"Lewis Clarke","id":"5319627"},{"url":"\/geophotos\/05\/25\/19\/5251913_43efd1a3_1024x1024.jpg","title":"Electricity sub-station, Stoke Newington","grid_reference":"TQ3385","taken":"Wednesday, 11 January, 2017","realname":"Julian Osley","id":"5251913"},{"url":"\/geophotos\/05\/31\/61\/5316176_87184fdb_1024x1024.jpg","title":"On the Limestone Way near Ashfield Farm","grid_reference":"SK1344","taken":"Monday, 13 March, 2017","realname":"Chris Morgan","id":"5316176"},{"url":"\/geophotos\/05\/31\/45\/5314577_03e39697_1024x1024.jpg","title":"Trees across the River Thames","grid_reference":"SU9083","taken":"Saturday, 11 March, 2017","realname":"Robert Eva","id":"5314577"},{"url":"\/geophotos\/05\/28\/25\/5282587_6896ee80_1024x1024.jpg","title":"Cottage by Square Lane","grid_reference":"SP2986","taken":"Tuesday,  7 February, 2017","realname":"Jonathan Billinger","id":"5282587"},{"url":"\/geophotos\/05\/28\/86\/5288644_caeaec67_1024x1024.jpg","title":"Boulders near Loch Doine","grid_reference":"NN4519","taken":"Tuesday, 14 September, 1976","realname":"Ian Taylor","id":"5288644"},{"url":"\/geophotos\/05\/30\/80\/5308050_25f3a8fb_1024x1024.jpg","title":"\"Scots Guardsman\" at Quintinshill - March 2017 (2)","grid_reference":"NY3169","taken":"Saturday, 11 March, 2017","realname":"The Carlisle Kid","id":"5308050"},{"url":"\/geophotos\/03\/85\/73\/3857309_f279cf75_1024x1024.jpg","title":"Mynydd Moel","grid_reference":"SH7213","taken":"Sunday, 16 February, 2014","realname":"Clive Giddis","id":"3857309"},{"url":"\/geophotos\/04\/89\/11\/4891174_d39557eb_1024x1024.jpg","title":"1st day  of trains running","grid_reference":"SJ8629","taken":"Tuesday, 29 March, 2016","realname":"Ian S","id":"4891174"},{"url":"\/geophotos\/02\/04\/36\/2043667_fcf068fd_1024x1024.jpg","title":"Notting Hill Carnival dancers","grid_reference":"TQ2382","taken":"Monday, 30 August, 2010","realname":"David Hawgood","id":"2043667"},{"url":"\/geophotos\/05\/29\/60\/5296015_21b864f0_1024x1024.jpg","title":"Skelmorlie ROC Post","grid_reference":"NS1964","taken":"Monday, 27 February, 2017","realname":"Raibeart MacAoidh","id":"5296015"},{"url":"\/geophotos\/04\/18\/56\/4185695_88e3449b_1024x1024.jpg","title":"The Falkirk Wheel at Night","grid_reference":"NS8580","taken":"Saturday, 27 September, 2014","realname":"Iain Smith","id":"4185695"},{"url":"\/geophotos\/05\/31\/51\/5315137_9a45dc33_1024x1024.jpg","title":"Shepham Windfarm","grid_reference":"TQ5905","taken":"Thursday, 16 March, 2017","realname":"Oast House Archive","id":"5315137"},{"url":"\/geophotos\/03\/25\/78\/3257834_cdc47caa_1024x1024.jpg","title":"Linlithgow Palace and church at night","grid_reference":"NT0077","taken":"Wednesday, 12 December, 2012","realname":"Greg Fitchett","id":"3257834"},{"url":"\/geophotos\/05\/31\/63\/5316313_3651834e_1024x1024.jpg","title":"Blossom in St Nicholas Gardens","grid_reference":"TQ4578","taken":"Thursday, 16 March, 2017","realname":"Marathon","id":"5316313"},{"url":"\/geophotos\/05\/30\/68\/5306805_c671bcc3_1024x1024.jpg","title":"Chestnut's Cafe","grid_reference":"TQ3288","taken":"Wednesday, 23 January, 2013","realname":"John Kingdon","id":"5306805"},{"url":"\/geophotos\/04\/27\/76\/4277690_f59f4058_1024x1024.jpg","title":"Bwlch y Marchlyn","grid_reference":"SH6161","taken":"Saturday, 13 December, 2014","realname":"Chris Andrews","id":"4277690"},{"url":"\/geophotos\/05\/31\/53\/5315374_11bf0ad1_1024x1024.jpg","title":"Brinkley: High Street houses","grid_reference":"TL6354","taken":"Friday, 17 March, 2017","realname":"John Sutton","id":"5315374"},{"url":"\/geophotos\/04\/88\/31\/4883124_3d9eb141_1024x1024.jpg","title":"Evening view of Loch Duich from Mam Ratagan","grid_reference":"NG9019","taken":"Sunday, 27 March, 2016","realname":"Julian Paren","id":"4883124"},{"url":"\/geophotos\/05\/31\/78\/5317880_d3f1875a_1024x1024.jpg","title":"Train approaching Shackerstone Station, Leicestershire","grid_reference":"SK3706","taken":"Saturday, 24 September, 2016","realname":"Roger  Kidd","id":"5317880"},{"url":"\/geophotos\/05\/31\/69\/5316977_20d2b5ec_1024x1024.jpg","title":"Burghead Harbour","grid_reference":"NJ1069","taken":"Thursday, 16 March, 2017","realname":"Walter Baxter","id":"5316977"},{"url":"\/geophotos\/02\/85\/50\/2855060_3e18f72b_1024x1024.jpg","title":"Stream above the Eas Anie waterfall on Beinn Chuirn","grid_reference":"NN2828","taken":"Sunday, 18 March, 2012","realname":"Alan O'Dowd","id":"2855060"}];


