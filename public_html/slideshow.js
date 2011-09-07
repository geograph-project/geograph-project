var cs=1;
var timer = false;
var marker = false;

function slide_go(delta) {
	
	document.getElementById("result"+cs).style.display = 'none';
	if (document.getElementById("mapA"+cs)) {
		document.getElementById("mapA"+cs).style.display = 'none';
	}
	if (marker != false && document.getElementById(marker))
		document.getElementById(marker).style.display = 'none';
	csnext = cs + delta;
	if (document.getElementById("result"+csnext)) {
		cs = cs + delta;
		if (document.getElementById("mapA"+cs)) {
			document.getElementById("mapA"+cs).style.display = '';
			if (timer != false) {
				setTimeout('show_slide_part2('+cs+')',mapdelayinsec*1000);
			}
			document.images['mapC'+cs].src = document.images['mapC'+cs].lowsrc;
			document.images['mapD'+cs].src = document.images['mapD'+cs].lowsrc;
		} else {
			document.getElementById("result"+cs).style.display = '';
		}
		document.images['image'+cs].src = document.images['image'+cs].lowsrc;
		csnext = cs + delta;
		if (document.getElementById("result"+csnext)) {
			document.images['image'+csnext].src = document.images['image'+csnext].lowsrc;
			if (document.getElementById("mapA"+csnext)) {
				document.images['mapC'+csnext].src = document.images['mapC'+csnext].lowsrc;
				document.images['mapD'+csnext].src = document.images['mapD'+csnext].lowsrc;
			}
		}
	} else {
		if (csnext > resultcount && hasnextpage) {
			for(q=0;q<document.links.length;q++)
				if (document.links[q].text.indexOf("next ") == 0)
					window.location.href = document.links[q].href+((timer != false)?"#autonext":'');
		}
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
		document.getElementById("nextbutton").disabled = (cs >= resultcount && !hasnextpage);
		document.getElementById("prevautobutton").disabled = (cs <= 1);
		document.getElementById("nextautobutton").disabled = (cs >= resultcount);
	}
}

function show_slide_part2(cs) {
	document.getElementById("result"+cs).style.display = '';
	if (document.getElementById("mapA"+cs)) {
		document.getElementById("mapA"+cs).style.display = 'none';
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
