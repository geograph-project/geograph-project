// A reusable component that extends L.Marker
L.LineMarker = L.Marker.extend({
    initialize: function (latlng, options) {
        // Default options
        options = L.extend({
            img: "",
	    imgSize: 100, // Assuming a square image for simplicity
            dir: 'up', // 'down', 'up', 'left', 'right'
            lineLength: 100,
        }, options);

        // Calculate icon properties based on direction and line length
	const imgSize = options.imgSize;
        let html, iconSize, iconAnchor, className;

        if (options.dir === 'down') {
            html = `<img src="${options.img}" alt="Thumbnail" style="--img-size:${imgSize}px;"><div class="line" style="height:${options.lineLength}px;"></div>`;
            iconSize = [imgSize, imgSize + options.lineLength];
            iconAnchor = [imgSize / 2, imgSize + options.lineLength];
            className = 'thumbnail-marker';

        } else if (options.dir === 'up') {
            html = `<div class="line" style="height:${options.lineLength}px;"></div><img src="${options.img}" alt="Thumbnail" style="--img-size:${imgSize}px;">`;
            iconSize = [imgSize, imgSize + options.lineLength];
            iconAnchor = [imgSize / 2, 0];
            className = 'thumbnail-marker upward';

        } else if (options.dir === 'right') {
            html = `<img src="${options.img}" alt="Thumbnail" style="--img-size:${imgSize}px;"><div class="line horizontal" style="width:${options.lineLength}px;"></div>`;
            iconSize = [imgSize + options.lineLength, imgSize];
            iconAnchor = [imgSize + options.lineLength, imgSize / 2];
            className = 'thumbnail-marker horizontal';

        } else if (options.dir === 'left') {
            html = `<div class="line horizontal" style="width:${options.lineLength}px;"></div><img src="${options.img}" alt="Thumbnail" style="--img-size:${imgSize}px;">`;
            iconSize = [imgSize + options.lineLength, imgSize];
            iconAnchor = [0, imgSize / 2];
            className = 'thumbnail-marker horizontal';
        }

        const icon = L.divIcon({
            className: className,
            html: html,
            iconSize: iconSize,
            iconAnchor: iconAnchor
        });

        // Call the parent's constructor with the custom icon
        L.Marker.prototype.initialize.call(this, latlng, L.extend(options, { icon: icon }));



// Set initial zIndexOffset if needed, otherwise it's 0
this.setZIndexOffset(0);

// Add event listeners
this.on('mouseover', function () {
    this.setZIndexOffset(1000); // Bring marker to the front
});

this.on('mouseout', function () {
    this.setZIndexOffset(0); // Return to original stacking order
});



    }
});

// A factory function for convenience
L.lineMarker = function (latlng, options) {
    return new L.LineMarker(latlng, options);
};
