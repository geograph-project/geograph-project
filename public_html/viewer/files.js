let images = [];
let MAX_SIZE = 320; //oversize so can use 'browser zoom'
//todo, special case for wide panos?

/******************************************************************************
 WORK WITH <input type=file> */

//http://blogs.microsoft.co.il/ranw/2015/01/07/reading-gps-data-using-exif-js/
function toDecimal(number) {
	//oddly exif.js returns signed rationals as a plain number, but unsigned as a 'Number' object with numerator/denominator so need to cope with EITHER
	// some old files seem to have lat/long in signed format (even tough the number wont be negative)
	if (typeof number[0] == 'number')
		return number[0] + (number[1]/60.0) + (number[2]/3600);

	return number[0].numerator + number[1].numerator /
           (60 * number[1].denominator) + number[2].numerator / (3600 * number[2].denominator);
}

jQuery(function() {
	jQuery('input[type="file"]').on('change', function(event) {
            let total = event.target.files.length;
	    let done = 0;
            for (const file of event.target.files) {

	    	if (file && file.size && file.size > 8388608) {
			jQuery('#jpeg_exif').after('<div class=toobig><b>File appears to be '+file.size+' bytes, which is too big for final submission</b>. Please downsize the image to be under 8 Megabytes</div>');
	    	} else {
			jQuery('.toobig').remove();
	    	}
	    	if (file && file.type != "image/jpeg" && file.type != "image/heic") {
			jQuery('#message').append('<div class=nonjpeg>File appears to not be a JPEG image. We only accept .jpg files or heic/heif files</div>');
			total--;
            	} else if (file && file.name) {
                	if (file && file.size && file.size < 10000) {
				jQuery('#message').after('<div class=toobig>File appears to be '+file.size+' bytes, which is rather small. Please check selected right image.</div>');
			}

			let image = {};
			image['name'] = file.name;
			image['size'] = file.size;
			image['type'] = file.type;

			image['_file'] = file; //storing the whole file object, allows us to use filereader again later!

			if(file.webkitRelativePath) //only if uploading a folder
				image.path = file.webkitRelativePath;
			if(file.lastModified) //not usually available via the files array
				image.mod = file.lastModified;

			/////////////////////////
			//extract
                	EXIF.getData(file, function() {
				////////////////////////

				var dateraw = EXIF.getTag(this, 'DateTimeOriginal') || EXIF.getTag(this, 'DateTimeDigitized') || EXIF.getTag(this, 'DateTime');
				if (dateraw) {
					image['dateraw'] = dateraw.replace(/:/,'-').replace(/:/,'-'); //just replace two in date!
					image['date'] = new Date(image.dateraw);
				}

				////////////////////////

				var long = EXIF.getTag(this, 'GPSLongitude');
				var lat = EXIF.getTag(this, 'GPSLatitude');

				if (long&&lat) {
					long = toDecimal(long);
					lat = toDecimal(lat);

					if (long > 180) long = long - 360.0; //some apps (like geosetter) encode longitude as E 0-360 - but >180 is W
					if (EXIF.getTag(this, 'GPSLongitudeRef') == 'W') long = long * -1;
					if (EXIF.getTag(this, 'GPSLatitudeRef') == 'S') lat = lat * -1;

					image['lat'] = lat;
					image['lng'] = long;

					image['square'] = wgs2gridref(lat,long, 4);
					if (image['square']) {
						image['gridref'] = wgs2gridref(lat,long, 10);
					} else {
						//sometimes might have 0,0 as location, or not be OSGB?
						jQuery("#locations").show();
					}
				} else {
					jQuery("#locations").show();
				}

				////////////////////////
                	});

			/////////////////////////
			//https://stackoverflow.com/questions/12368910/html-display-image-after-selecting-filename
			//https://imagekit.io/blog/how-to-resize-image-in-javascript/

		    	var reader = new FileReader();

	            	reader.onload = function (e) {
				var img = document.createElement("img");
    				img.onload = function (event) {
					//original size
					image['width'] = img.width;
					image['height'] = img.height;

					//thumbnail size
					width = img.width;
					height = img.height;
					if (img.width > img.height && width > MAX_SIZE) {
						height = height * (MAX_SIZE / width);
						width = MAX_SIZE;
					} else if (height > MAX_SIZE) {
						width = width * (MAX_SIZE / height);
						height = MAX_SIZE;
					}

					// Dynamically create a canvas element
					var canvas = document.createElement("canvas");
					canvas.width = width;
					canvas.height = height;

					var ctx = canvas.getContext("2d");
					ctx.drawImage(img, 0, 0, width, height);

					image['data'] = canvas.toDataURL(file.type);

					//jQuery('#output').append('<img src="'+image['data']+'">');

					done++;
					jQuery('#message').text('processed '+done+' of '+total+' images');

					if (done == total) {
						jQuery("#hashes").show();
						updateViewer(true);
					}
    				}
    				img.src = e.target.result;
		    	};

	            	reader.readAsDataURL(file);
			/////////////////////////

			images.push(image);

			jQuery('#message').text('loaded '+images.length+' images, still to be processed...');
            	}
            }
        });
});

