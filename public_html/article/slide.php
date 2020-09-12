<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" style="margin:0px">
<head>
<title>Slide Deck</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<link rel="stylesheet" type="text/css" title="Monitor" href="http://s0.geograph.org.uk/templates/basic/css/basic.v7543.css" media="screen" />
<link rel="stylesheet" type="text/css" title="Monitor" href="http://s0.geograph.org.uk/templates/basic/css/mapper.v3798.css" media="screen" />
<link rel="shortcut icon" type="image/x-icon" href="/favicon.ico"/>
<script type="text/javascript" src="http://s0.geograph.org.uk/js/geograph.v7508.js"></script>

</head>

<body style="margin:0px">

<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.6.1/jquery.min.js" type="text/javascript"></script>
<script src="/js/fathom.min.js"></script>
<script>
$(function() {
	if (window.location.hash.indexOf('#') == 0) {
		var article = window.location.hash.replace(/#/,'');
	} else {
		var article = "London-from-the-air";
	}
	$('#results').load('/article/'+article+' #maincontent',function () {
		var ttxt = $(this).html();

		ttxt = ttxt.replace(/<h1/,'<div class="slide"><h1');
		ttxt = ttxt.replace(/<h2/g,'</div><div class="slide"><h2');
		ttxt = ttxt.replace(/<h3/g,'</div><div class="slide"><h3');

		ttxt = ttxt.replace(/<\/h2>\s*<\/div><div class="slide"><h3/g,'</h2><h3'); //fix emptyish slides

//		ttxt = ttxt.replace(/<hr\/>\n\s*<div style="float:right;position:relative">/m,'</div><div class="slide"><div style="float:right;position:relative">');

		$(this).html(ttxt);
		$('#contents_table').hide();

		$('#results').fathom();

	});
});
</script>
<style>

div.slide {
        -webkit-box-shadow: 0 0 50px #c0c0c0;
        -moz-box-shadow: 0 0 50px #c0c0c0;
        box-shadow: 0 0 50px #c0c0c0;
        -moz-border-radius: 20px;
        -webkit-border-radius: 20px;
        border-radius: 20px;

	background-color: #eeeeee;
	padding:5px;
	border:1px solid silver;
	width:85%;
	min-height:400px;
}

.slide h1 {
        -moz-border-radius: 10px;
        -webkit-border-radius: 10px;
        border-radius: 10px;
}
.slide h2 {
        -moz-border-radius: 7px;
        -webkit-border-radius: 7px;
        border-radius: 7px;
}
.slide h3 {
        -moz-border-radius: 4px;
        -webkit-border-radius: 4px;
        border-radius: 4px;
}

</style>
(use cursor keys to navigate the deck... Left/Right for to move between slides. Up/Down to view long slides) 
<div id="results"></div>


</body>
</html>
