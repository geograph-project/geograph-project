var cs=1;
var timer = false;
var marker = false;

function slide_go(delta) {
	document.getElementById("result"+cs).style.display = 'none';
	if (marker != false && document.getElementById(marker))
		document.getElementById(marker).style.display = 'none';
	csnext = cs + delta;
	if (document.getElementById("result"+csnext)) {
		cs = cs + delta;
		document.getElementById("result"+cs).style.display = '';
		document.images['image'+cs].src = document.images['image'+cs].lowsrc;
		csnext = cs + delta;
		if (document.getElementById("result"+csnext)) 
			document.images['image'+csnext].src = document.images['image'+csnext].lowsrc
	} else {
		marker = (delta > 0)?'marker_end':'marker_start';
		if (document.getElementById(marker))
			document.getElementById(marker).style.display = '';
		if (timer != false)
			clearInterval(timer);
		timer = false;
		document.getElementById("stopbutton").disabled = true;
	}
	if (timer == false) {
		document.getElementById("prevbutton").disabled = (cs <= 1);
		document.getElementById("nextbutton").disabled = (cs >= resultcount);
		document.getElementById("prevautobutton").disabled = (cs <= 1);
		document.getElementById("nextautobutton").disabled = (cs >= resultcount);
	}
}

function slide_stop() {
	clearInterval(timer);
	timer = false;
	slide_go(0);
	document.getElementById("stopbutton").disabled = true;
}

function auto_slide_go(delta) {
	timer = setInterval("slide_go("+delta+")",delayinsec*1000);
	document.getElementById("prevautobutton").disabled = true;
	document.getElementById("nextautobutton").disabled = true;
	document.getElementById("prevbutton").disabled = true;
	document.getElementById("nextbutton").disabled = true;
	document.getElementById("stopbutton").disabled = false;
}

	function my_getElementById(name) {
		return document.all[name];
	}

if (!document.getElementById) {
	document.getElementById = my_getElementById;
}
