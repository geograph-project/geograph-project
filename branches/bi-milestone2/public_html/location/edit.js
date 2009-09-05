function getXMLRequestObject()
{
	var xmlhttp=false;
		
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



function performSearch(q)
{
	var url="/stuff/search-service.php?output=location&q="+encodeURIComponent(q);
		
	//make the request
	var req=getXMLRequestObject();
	
	//need to exploit function closure
	req.onreadystatechange = function()
	{
		if (req.readyState==4) 
		{
			var ele=document.getElementById('searchResults');
			ele.innerHTML=req.responseText;

			//patch the memory leak
			req.onreadystatechange = function() {};
			
			
			//restart the dragging for the new images
			initGallery();
		}
	}
	req.open("GET", url,true);
	req.send(null)


}