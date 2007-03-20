function showCaption(thatForm) {
	oldscore = parseInt(thatForm.points.value,10)
	score = Math.round(oldscore/2);
	
	
	for(q=0;q<oldscore;q++) {
		ele = document.images['pointimg'+q];
	
		if ((q+1)>score) {
			ele.style.display = 'none';
		}
	}
	
	
	thatForm.points.value = score;
	
	document.getElementById('caption').style.display = '';
	document.getElementById('anticaption').style.display = 'none';
	
}

function game_check(thatForm) {
		
	var url="/games/markit.php?check&token="+thatForm.token.value+
		"&points="+thatForm.points.value+
		"&grid_reference="+thatForm.grid_reference.value;

	//make the request
	var req=getXMLRequestObject();

	req.onreadystatechange=onCheckCompleted;
	req.open("GET", url,true);
	req.send(null);
}


function onCheckCompleted()
{
	if (xmlhttp.readyState==4) 
	{
		//alert(xmlhttp.responseText);
		var divresponce=document.getElementById('responce');
		
		bits = xmlhttp.responseText.split('^');
		
		divresponce.innerHTML=bits[0]+"<br/><br/>";
		if (bits[1]) {
			thatForm = document.forms[0]; //for some strange reason theForm doesnt work here...
			if (bits[1] == '-1') {
				oldscore = parseInt(thatForm.points.value,10)
				score = oldscore - 1;

				for(q=0;q<oldscore;q++) {
					ele = document.images['pointimg'+q];

					if ((q+1)>score) {
						ele.style.display = 'none';
					}
				}					
					
				thatForm.points.value = score;
			}
			
			if (bits[1] == '1' || score == 0) {
				thatForm.save.disabled= false;
				thatForm.save.style.backgroundColor = 'lightgreen';
				thatForm.save.style.fontSize = '1.3em';
				thatForm.next.disabled= false;
				thatForm.next.style.backgroundColor = 'lightgreen';
				thatForm.next.style.fontSize = '1.3em';
			}
		}
	}
}





var create_new_xmlhttp=true;
var xmlhttp=false;

function getXMLRequestObject()
{
	if (create_new_xmlhttp==false)
		return xmlhttp;
		
	/*@cc_on @*/
	/*@if (@_jscript_version >= 5)
	// JScript gives us Conditional compilation, we can cope with old IE versions.
	// and security blocked creation of the objects.
	 try {
	  xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
	 } catch (e) {
	  try {
	   xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
	  } catch (E) {
	   xmlhttp = false;
	  }
	 }
	@end @*/
	if (!xmlhttp && typeof XMLHttpRequest!='undefined') {
	  xmlhttp = new XMLHttpRequest();
	}

	return xmlhttp;
}