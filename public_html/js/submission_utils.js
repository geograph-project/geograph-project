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
		alert('File appears to be '+file.size.toLocaleString()+' bytes, which is too big for final submission. We will now attempt to downsize the file automatically... (please wait, a few attempts may be needed to find the right settings)');
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

				//also send the filename. Some contributors include grid-ref in filename!
				if (file.name) {
					let element3 = document.createElement("input");
					    element3.setAttribute("type", "hidden");
					    element3.setAttribute("name", "jpeg_filename");
					    element3.setAttribute("value", file.name);
					ele.after(element3);
				}

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

function resizeFileWorker(file, max_size, callback, max_dimension) {
	if (!window.Worker || !OffscreenCanvas || !createImageBitmap || !window.fetch) { //fallback! the webworker version needs more advanced APIs
		return resizeFile(file, max_size, callback, max_dimension);
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

			myWorker.terminate(); // Kill the thread immediately
		}
	};

	const reader = new FileReader();
	reader.onload = function (e) {
		message.innerText = "Loading image...";

		myWorker.postMessage({ dataUrl: e.target.result, maxSize: max_size, maxDimension: max_dimension});
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

function resizeImage(imageDataUrl, max_size, callback, max_dimension) {
	const img = new Image();
	img.style.imageOrientation = 'none'; // This applies CSS after it's in memory
	img.onload = function() {
		let canvas = document.createElement('canvas');
		let ctx = canvas.getContext('2d');
		let width = img.width;
		let height = img.height;
		let quality = 0.96; // Initial quality

		//we can also explicity downsize (meaning it very unlikly to be over max_size anyway!)
		if (max_dimension && (width>max_dimension || height>max_dimension)) {
			var aspect = width/height;
			if (aspect > 1) { //wide (original is the width)
				width  = max_dimension;
				height = Math.floor(max_dimension / aspect);
			} else {
				width  = Math.floor(max_dimension * aspect);
				height = max_dimension;
			}
			quality = 0.87;
		}

		canvas.width = width;
		canvas.height = height;
		ctx.drawImage(img, 0, 0, width, height);

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

		img.src = ""; // Clear image memory
		canvas.width = 0;
		canvas.height = 0;
		canvas = null;
	};
	img.src = imageDataUrl;
}

//non async version, for legacy use
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

async function dataURLtoBlobAsync(dataUrl) {
    const res = await fetch(dataUrl);
    return await res.blob();
}

/////////////////////////////////
// New Functions from the App Upload, but generic enough to be used
// require exifr to be loaded, as well exifRestorer used by the above resize code!


    async function processItem(item, supportBlob) {
        if (item.dataUri) //might of already been processed on previous run
		return item;

        item.isHeic = item.file.name.toLowerCase().endsWith('.heic') || item.file.type === 'image/heic';

        // 1. Analyze EXIF (Handles JPEG and HEIC automatically)
        // By default, exifr parses the most common tags (GPS, Orientation, etc.)
        const data = await exifr.parse(item.file, {
            translateKeys: true,  // Keep this true so you get 'latitude'/'longitude'
            translateValues: false, // THIS is what gives you '1' instead of "Horizontal (normal)"
            reviveValues: false     // This prevents it from turning date strings into JS Date objects
        });

        const lat = data?.latitude ?? null;   // exifr helpfully maps GPSLatitude to 'latitude'
        const long = data?.longitude ?? null; // and GPSLongitude to 'longitude'
        const exifData = {
            // Number.isFinite returns false for NaN, null, undefined, and Infinity
            hasGeo: Number.isFinite(lat) && Number.isFinite(long),
            lat: lat,
            long: long,
            date: data?.DateTimeOriginal || data?.CreateDate || null,
            orientation: data?.Orientation || null
        };

        // 2. Support reading a Grid Ref from filename (e.g., photo_SU12345678.jpg)
        const match = item.file.name.match(/_([A-Z]{1,2}\d{4,10})\./i);
        if (match) {
            // Trust filename if EXIF is missing OR if user provided high precision GR (> 6 figures)
            if (!exifData.hasGeo || match[1].length > 7) {
                let wgs84 = GT_WGS84.parseGridRef(match[1]);
                if (wgs84 && wgs84.status === 'OK') {
                    exifData.lat = wgs84.latitude;
                    exifData.long = wgs84.longitude;
                    exifData.hasGeo = true;
                    exifData.source = 'filename';
                }
            }
        }
        item.exifData = exifData;

        // 3. Resize if necessary (converts file to a DataURL, resizeFileWorker, will naturally convert file to data URL naturally too)
                let needsResize = (item.file.size > window.max_size);

                if (!needsResize && window.uploadMaxDimension < 65535) {
                    // Also check dimensions
                    const dimensions = await new Promise(res => {
                        const img = new Image();
                        img.onload = () => {
                            const dims = {w: img.width, h: img.height};
                            URL.revokeObjectURL(img.src);
                            res(dims);
                        };
                        img.onerror = () => {
                            URL.revokeObjectURL(img.src);
                            res(null);
                        };
                        img.src = URL.createObjectURL(item.file);
                    });
                    if (dimensions && (dimensions.w > window.uploadMaxDimension || dimensions.h > window.uploadMaxDimension)) {
                        needsResize = true;
                    }
                }

        item.dataUri = await new Promise((resolve, reject) => {
                if (needsResize && !item.isHeic) {
                    // RESIZE: still returns a DataURL string via Worker
                    resizeFileWorker(item.file, window.max_size, (url) => {
                        window.requestAnimationFrame(function() {
                            const finished = document.getElementById('messageDiv');
                            if (finished) finished.remove();
                        });
                        resolve(url);
                    }, window.uploadMaxDimension);
                } else if (supportBlob) {
                    // We resolve the File object directly. (it's a Blob!)
            	    resolve(item.file);
                } else {
                    // LEGACY FALLBACK: Convert to DataURL string
                    const reader = new FileReader();
                    reader.onload = (e) => resolve(e.target.result);
                    reader.onerror = (e) => reject(e); // Good to handle errors!
                    reader.readAsDataURL(item.file);
                }
        });

        return item; // Still return it for Promise.all convenience
    }

/*
* Send file to server, provide the name to store in local cache
* provide a onProgress callback, to receive a percentage uploaded
* exifData is optional, not sent to server (should already be in the image bytes), but it can be saved locallly to help with plotting on map, for example
*/
async function sendToPHP(data, name, onProgress, exifData) {

    //more effient to use FormData to send the file, rather than JSON
    const formData = new FormData();

    // Handle either Blob/File or DataURL string
    if (data instanceof Blob) { //catches 'File' too!
        formData.append('jpeg_exif', data, name);
    } else if (typeof data === 'string' && data.startsWith('data:')) {
        formData.append('jpeg_exif', await dataURLtoBlobAsync(data), name);
    } else {
        throw new Error("Invalid data format sent to upload");
    }
    if (name)
        formData.append('name', name);

    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();

        // The magic happens here: track upload progress
        if (xhr.upload && onProgress) {
            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable) {
                    const percentComplete = Math.round((e.loaded / e.total) * 100);
                    onProgress(percentComplete);
                }
            });
        }

        xhr.open('POST', '/app/upload.php', true);

        xhr.onload = async () => {
            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    const result = JSON.parse(xhr.responseText);
                    if (result.ok) {
                        if (name && typeof MediaDatabase !== 'undefined') {
                        	const dbHistory = window.dbHistory || new MediaDatabase();
                        	//dbHistory.pruneHistory(MAX_FILES); for now no pruning

                            await dbHistory.updateMediaHistory(name, {
                                status: 'uploaded',
                                uploadId: result.upload_id,
                                exifData: exifData ?? null
                            });
                        }

                        resolve({ 
                            success: true, 
                            upload_id: result.upload_id, 
                            width: result.width, 
                            height: result.height 
                        });
                    } else {
                        resolve({ success: false, error: result.error });
                    }
                } catch (e) {
                    reject(new Error("Invalid JSON response from server"));
                }
            } else {
                reject(new Error(`Server returned status ${xhr.status}`));
            }
        };

        xhr.onerror = () => reject(new Error("Network error occurred"));

        xhr.send(formData);
    });
}

