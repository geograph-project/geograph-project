importScripts('/viewer/ExifRestorer.js');

self.addEventListener('message', async function(event) {
	const { dataUrl, maxSize } = event.data;

	try {
		const blob = await (await fetch(dataUrl)).blob();
   		const bitmap = await createImageBitmap(blob);

		let canvas = new OffscreenCanvas(bitmap.width, bitmap.height);
		let ctx = canvas.getContext('2d');
		let width = bitmap.width;
		let height = bitmap.height;
		let quality = 0.96; // Initial quality
		let attempt = 1;

		self.postMessage({message: 'Attempt '+attempt+': Resizing image to '+width+' x '+height+' ...'});
		canvas.width = width;
		canvas.height = height;
		ctx.drawImage(bitmap, 0, 0, width, height);

		//OffscreenCanvas does not have getDataUrl, so have to first convertToBlob, then convert to dataURL (ExifRestorer works on dataUrls!) 

		self.postMessage({message: 'Attempt '+attempt+': Saving as '+(quality*100)+'% ...'});
		let resizedBlob = await canvas.convertToBlob({ type: 'image/jpeg', quality: quality });
		let resizedDataUrl;

		if (resizedBlob.size < maxSize) { //no point addding exif, if already too big!
			self.postMessage({message: 'Attempt '+attempt+': Converting ...'});
			resizedDataUrl = await readDataURL(resizedBlob);

			self.postMessage({message: 'Attempt '+attempt+': Restoring EXIF ... ('+resizedBlob.size+' bytes without EXIF)'});
			resizedDataUrl = 'data:image/jpeg;base64,' + ExifRestorer.restore(dataUrl, resizedDataUrl);

			//need to convert back to a blob to get native size!
			resizedBlob = await (await fetch(resizedDataUrl)).blob();
		}

		while (resizedBlob.size > maxSize) {
			attempt++;
			//first try reducing quality
			if (quality > 0.7) {
				quality = (quality*0.9).toFixed(2);
				self.postMessage({message: 'Attempt '+attempt+': Saving as '+(quality*100)+'% ...'});

			} else if (width > 3000 && height > 3000) {
				width = Math.floor(width*0.9);
				height = Math.floor(height*0.9);
				self.postMessage({message: 'Attempt '+attempt+': Resizing image to '+width+' x '+height+' ...'});
				quality = 0.87; //reset quality, when downsize!

			} else {
				self.postMessage({error: "Could not resize image under " + maxSize});
				return;
			}

			canvas.width = width;
			canvas.height = height;
			ctx.drawImage(bitmap, 0, 0, width, height);
			resizedBlob = await canvas.convertToBlob({ type: 'image/jpeg', quality: quality });

			if (resizedBlob.size < maxSize) { //no point adding exif, if already too big!
				self.postMessage({message: 'Attempt '+attempt+': Converting ...'});
		                resizedDataUrl = await readDataURL(resizedBlob);

				self.postMessage({message: 'Attempt '+attempt+': Restoring EXIF ... ('+resizedBlob.size+' bytes without EXIF)'});
				resizedDataUrl = 'data:image/jpeg;base64,' + ExifRestorer.restore(dataUrl, resizedDataUrl);

				resizedBlob = await (await fetch(resizedDataUrl)).blob();
			}
		}

		self.postMessage({
			resizedDataUrl: resizedDataUrl,
			width: width,
			height: height,
			quality: quality,
			size: resizedBlob.size
		});
	} catch (error) {
		self.postMessage({ error: error.message });
	}
});


async function readDataURL(blob) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();

    reader.onload = () => {
      resolve(reader.result);
    };

    reader.onerror = () => {
      reject(reader.error);
    };

    reader.readAsDataURL(blob);
  });
}
