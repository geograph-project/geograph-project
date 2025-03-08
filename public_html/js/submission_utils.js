//extracted from submission process. (then extended to do clients ide downsizing

function check_jpeg(ele, max_size) {
    if (!ele)
	return true; //just in case the element is removed! (eg after resizing!)
    if (!max_size)
	max_size = 8 * 1024 * 1024;
    //max_size_mb = max_size / (1024 * 1024);

    if (ele && ele.value && ele.value.length > 0 && !ele.value.match(/\.(jpe?g|heic)$/i)) {
	if (!confirm("The name of the file does not appear to have a .jpg extension. Note, we only accept JPEG/HEIC images. To upload anyway, press OK. To select a different file click Cancel"))
		return false;
    }

    if (ele && ele.files) {
            var file = ele.files[0];
            if (file && file.size && file.size > max_size) {
                //alert('File appears to be '+file.size+' bytes, which is too big for final submission. Please downsize the image to be under 8 Megabytes.');
		alert('File appears to be '+file.size.toLocaleString()+' bytes, which is too big for final submission. We will now attempt to downsize the file automatically... (please wait)');
		let form = ele.form;
		resizeFileWorker(file, max_size, function(dataurl, final_size) {
			if (dataurl) {
				let element = document.createElement("input");
				element.setAttribute("id", "jpeg_data");
				element.setAttribute("type", "hidden");
				element.setAttribute("name", "jpeg_data");
				//element.setAttribute("value", dataurl);
				ele.after(element); //add the new input inplace of the original element.

				//seems to be more stable setting the value directly rather than on the in memory version!
				document.getElementById('jpeg_data').value = dataurl;

				//show some text, so user still sees something!
				let element2 = document.createElement("span");
				element2.innerText = 'Resized image ('+(final_size)+' bytes)';
				ele.after(element2);

				//note the form was not submitted, so needs sumitting again!
				ele.remove(); //and remove the original (we now submitting data url!)
				if (form.elements['sendfile'])
					form.elements['sendfile'].value = 'submitting, please wait';
				form.submit();
			}
		});

		if (form.elements['sendfile']) {
			form.elements['sendfile'].value = 'resizing file, please wait';
			let loading = document.createElement("img");
			loading.setAttribute("src", '/plupload/examples/img/throbber.gif');
			form.elements['sendfile'].after(loading);
		}

		//resizeFile is async, so can't submit the form now!
		return false;
            } else if (file && file.type && file.type != "image/jpeg" &&  file.type != "image/heic") {
		//todo, could use resizeImage, to convert to jpeg (even if under size) - will actlly support any filetype the browser can render in <img>!
		//also the server can now accept some other types too!
                alert('File appears to not be a JPEG image. We only accept .jpg/.heic files');
		return false;
            } else if (file && file.size && file.size < 10000) {
                return confirm('File appears to be '+file.size+' bytes, which is rather small. Please check selected right image.');
            }
    }
    return true;
}

function resizeFileWorker(file, max_size, callback) {
	if (!window.Worker || !OffscreenCanvas || !createImageBitmap || !window.fetch) { //fallback! the webworker version needs more advanced APIs
		return resizeFile(file, max_size, callback);
	}

	const message = document.createElement("div");
        message.setAttribute("id", "messageDiv");
	message.style.position = 'fixed';
	message.style.zIndex = 1000;
	message.style.top = '100px';
	message.style.left = '100px';
	message.style.right = '100px';
	message.style.backgroundColor = 'white';
	message.style.fontSize = '2em';
	message.style.padding = '1em';
	message.style.border = '2px solid gray';
	message.style.borderRadius = '3px';
	message.innerText = "Reading image...";
	document.body.after(message);

	const myWorker = new Worker("/js/resizeWorker.js?v=18");
	myWorker.onmessage = function(event) {
		if (event.data.error) {
			alert(event.data.error);
		}
		if (event.data.message) {
			message.innerText = event.data.message;
		}
		if (event.data.resizedDataUrl) {
			const { resizedDataUrl, width, height, quality, size } = event.data;
			message.innerText = 'Image has been resized to ' + width + 'x' + height + ' and saved at ' + Math.floor(quality * 100) + '% quality setting, resulting in a new image of ' + size.toLocaleString() + ' bytes. (EXIF is maintained)';
			callback(resizedDataUrl, size);
		}
	};

	const reader = new FileReader();
	reader.onload = function (e) {
		message.innerText = "Loading image...";

		myWorker.postMessage({ dataUrl: e.target.result, maxSize: max_size});
        }
        reader.readAsDataURL(file);
}

////////////////////////////////

//needed so can call on file object, which needs first converting to a dataURL
function resizeFile(file, max_size, callback) {
	if (!FileReader || !Blob) {
		alert('Sorry your browser doesnt seem to support client-size resizing');
		return;
	}
	var reader = new FileReader();
	reader.onload = function (e) {
		resizeImage(e.target.result, max_size, callback);
	};
        reader.readAsDataURL(file);
}
////////////////////////////////
// resize image to under 8mb
// based on code from Gemini: https://g.co/gemini/share/35c75ecb4cb4

function resizeImage(imageDataUrl, max_size, callback) {
	const img = new Image();
	img.onload = function() {
		let canvas = document.createElement('canvas');
		let ctx = canvas.getContext('2d');
		let width = img.width;
		let height = img.height;

		canvas.width = width;
		canvas.height = height;
		ctx.drawImage(img, 0, 0, width, height);

		let quality = 0.96; // Initial quality
		let resizedDataUrl = canvas.toDataURL('image/jpeg', quality);
		resizedDataUrl = 'data:image/jpeg;base64,'+ExifRestorer.restore(imageDataUrl,resizedDataUrl);
		let resizedBlob = dataURLtoBlob(resizedDataUrl);

		while (resizedBlob.size > max_size) {
			//first try reducing quality
			if (quality > 0.7) {
				quality = (quality*0.9).toFixed(2);
			} else if (width > 3000 && height > 3000){
				width = Math.floor(width*0.9);
				height = Math.floor(height*0.9);
				quality = 0.87; //reset quality, when downsize!
			} else {
				alert("Could not resize image under "+max_size);
				return;
			}

			canvas.width = width;
			canvas.height = height;
			ctx.drawImage(img, 0, 0, width, height);
			resizedDataUrl = canvas.toDataURL('image/jpeg', quality);
			resizedDataUrl = 'data:image/jpeg;base64,'+ExifRestorer.restore(imageDataUrl,resizedDataUrl);

			resizedBlob = dataURLtoBlob(resizedDataUrl);
		}
		alert('Image has been resized to '+width+'x'+height+' and saved at '+Math.floor(quality*100)+'% quality setting, resulting in a new image of '+resizedBlob.size.toLocaleString()+' bytes. (EXIF is maintained)');

		callback(resizedDataUrl, resizedBlob.size);
	};
	img.src = imageDataUrl;
}

function dataURLtoBlob(dataURL) {
	const parts = dataURL.split(';base64,');
	const contentType = parts[0].split(':')[1];
	const raw = window.atob(parts[1]);
	const rawLength = raw.length;
	const uInt8Array = new Uint8Array(rawLength);
	for (let i = 0; i < rawLength; ++i) {
		uInt8Array[i] = raw.charCodeAt(i);
	}
	return new Blob([uInt8Array], { type: contentType });
}
