//this looks messy, but its so the webarchive url can be rewritten!

function loadimage(gridimage_id) {
	
	location.href = location.href.replace(/(geograph\.org.uk)\/.*/,'$1/photo/'+gridimage_id);
}