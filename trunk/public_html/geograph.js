function popupOSMap(gridref)
{
        var wWidth = 740;
        var wHeight = 520;
        var wLeft = Math.round(0.5 * (screen.availWidth - wWidth));
        var wTop = Math.round(0.5 * (screen.availHeight - wHeight)) - 20;
        if (gridref.length > 0) {
        var newWin = window.open('http://getamap.ordnancesurvey.co.uk/getamap/frames.htm?mapAction=gaz&gazName=g&gazString='+gridref, 
		'gam',
		'left='+wLeft+',screenX='+wLeft+',top='+wTop+',screenY='+wTop+',width='+wWidth+',height='+wHeight+',status,scrolling=no');
	} else {
	var newWin = window.open('http://getamap.ordnancesurvey.co.uk/getamap/frames.htm', 
		'gam',
		'left='+wLeft+',screenX='+wLeft+',top='+wTop+',screenY='+wTop+',width='+wWidth+',height='+wHeight+',status,scrolling=no');
	}
}
