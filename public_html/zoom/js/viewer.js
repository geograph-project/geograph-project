document.addEventListener('DOMContentLoaded', () => {
    const viewerContainer = document.getElementById('viewer-container');
    const zoomInButton = document.getElementById('zoom-in');
    const zoomOutButton = document.getElementById('zoom-out');

    if (!viewerContainer) {
        console.error('Error: viewer-container element not found.');
        return;
    }
    if (!zoomInButton || !zoomOutButton) {
        console.error('Error: Zoom buttons not found.');
        // Allow viewer to work without buttons for now, can be controlled via console
    }

    // --- Configuration ---
    let TILE_SIZE = 256; // Default, will be updated from server
    const SOURCE_IMAGE_PATH = 'placeholder.jpg'; // Default sample image

    // --- State ---
    let imageInfo = {
        width: 0,
        height: 0,
        tile_size: TILE_SIZE // server might send a different preferred tile size
    };
    let currentZoom = 0; // Base zoom level
    let maxZoomLevel = 0; // Calculated based on image dimensions
    let pan = { x: 0, y: 0 }; // Pan offset in pixels at current zoom

    // For panning
    let isPanning = false;
    let lastPanPosition = { x: 0, y: 0 };

    // For pinch zoom
    let initialPinchDistance = null;
    // let currentPinchMidpoint = null; // Screen coordinates of midpoint, might not be needed if we use world coords
    let isPinching = false;

    // --- Initialization ---
    async function init() {
        try {
            const response = await fetch(`../tile_server.php?action=get_image_info&image_path=${encodeURIComponent(SOURCE_IMAGE_PATH)}`);
            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(`Failed to load image info: ${errorData.error || response.statusText}`);
            }
            const data = await response.json();
            imageInfo = {
                width: parseInt(data.width, 10),
                height: parseInt(data.height, 10),
                tile_size: parseInt(data.tile_size, 10) || TILE_SIZE
            };
            TILE_SIZE = imageInfo.tile_size; // Use server-defined tile size

            if (imageInfo.width === 0 || imageInfo.height === 0) {
                throw new Error('Image dimensions are zero.');
            }

            // Calculate initial zoom and max zoom
            // Max zoom: where 1 image pixel = 1 screen pixel (approximately, using tile size)
            // Smallest dimension / tile size, then log2
            // For example, if image is 1024px wide, and tile is 256, max zoom could be log2(1024/256) = log2(4) = 2
            // This means at zoom 2, we see 256 source pixels per tile.
            // The server's zoom interpretation: pixels_in_source_covered_by_tile_edge = TILE_SIZE / pow(2, zoom_level)
            // So, for 1 source pixel per screen pixel (roughly), pixels_in_source_covered_by_tile_edge = 1 (this implies TILE_SIZE is 1, which is not right)
            // Let's redefine maxZoomLevel based on the server's definition of zoom_level = 0 being the most detailed.
            // If server zoom_level 0 means TILE_SIZE source pixels are copied to a TILE_SIZE tile (1:1),
            // then this is our 'highest' zoom level from client perspective.
            // If server zoom_level `maxZoomLevel` means 1 source pixel is copied to a TILE_SIZE tile (scaled up),
            // then `TILE_SIZE / pow(2, maxZoomLevel_server) = 1` => `pow(2, maxZoomLevel_server) = TILE_SIZE` => `maxZoomLevel_server = log2(TILE_SIZE)`
            // The client will use zoom levels from 0 (most zoomed out) to N (most zoomed in).
            // The server's `zoom_level` parameter means:
            // 0 = copy TILE_SIZE from source
            // 1 = copy TILE_SIZE/2 from source
            // N = copy TILE_SIZE/(2^N) from source
            // So, client's `currentZoom` will map directly to server's `zoom_level`.
            // `maxZoomLevel` on client should be where we see the image at approx 1:1 or more detail.
            // Let's say `maxZoomLevel` (client) means we are requesting `zoom_level = M` from server
            // such that `TILE_SIZE / (2^M)` is small, e.g., 1. So `M = log2(TILE_SIZE)`.
            maxZoomLevel = Math.floor(Math.log2(Math.max(imageInfo.width, imageInfo.height))); // Max zoom where image is roughly actual size or larger
                                                                                             // This is a common definition, where image pixels are magnified.
                                                                                             // Let's cap it at a level where TILE_SIZE / 2^zoom is not too small.
            maxZoomLevel = Math.max(0, Math.floor(Math.log2(TILE_SIZE))); // This ensures that at maxZoomLevel, we copy 1 source pixel to the tile and scale it up.

            // Initial zoom: Fit image to viewer container.
            // We need to find a currentZoom (server zoom_level) such that the scaled image fits.
            // Scaled width = imageInfo.width / (sourcePixelsPerTileEdge / TILE_SIZE) = imageInfo.width * Math.pow(2, currentZoom)
            // This is not right.
            // At a given server_zoom_level 'z', the image is effectively scaled by:
            // Total pixels in image / (Number of tiles * TILE_SIZE)
            // Number of tiles along one dim = imageInfo.width / (TILE_SIZE / pow(2,z)) = imageInfo.width * pow(2,z) / TILE_SIZE
            // Full width of rendered image = (imageInfo.width * pow(2,z) / TILE_SIZE) * TILE_SIZE = imageInfo.width * pow(2,z) (This is if not scaled by client)
            // This is the width of the "world" in screen pixels if zoom level z means 1 image pixel = 2^z screen pixels.
            // But our server zoom means: source_pixels_per_tile = TILE_SIZE / 2^z.
            // So, the "effective" full image width at server zoom 'z' is imageInfo.width * (TILE_SIZE / (TILE_SIZE / 2^z)) = imageInfo.width * 2^z.
            // This is imageInfo.width pixels from source, scaled up by 2^z.
            // We want this to fit into viewerContainer.clientWidth.
            // imageInfo.width * Math.pow(2, currentZoom) = viewerContainer.clientWidth
            // Math.pow(2, currentZoom) = viewerContainer.clientWidth / imageInfo.width
            // currentZoom = log2(viewerContainer.clientWidth / imageInfo.width)
            // This is if currentZoom means scaling factor. But our currentZoom is server's zoom_level.
            // Higher server_zoom_level = more zoomed in = smaller piece of source image = appears larger.

            // Let's set initial zoom to 0 (server's most detailed base level for tiling)
            // and adjust pan to center it.
            currentZoom = 0;

            // Calculate total width of the image if rendered with currentZoom (server zoom_level 0)
            // At server zoom_level 0, sourcePixelsPerTileEdge = TILE_SIZE.
            // Number of tiles = imageInfo.width / TILE_SIZE.
            // Total rendered width = (imageInfo.width / TILE_SIZE) * TILE_SIZE = imageInfo.width.
            // This is if tiles are displayed at their native TILE_SIZE.
            // Calculate displayed image size at the initial zoom level (currentZoom is usually 0 here)
            const imageDisplayWidthAtCurrentZoom = imageInfo.width / Math.pow(2, currentZoom);
            const imageDisplayHeightAtCurrentZoom = imageInfo.height / Math.pow(2, currentZoom);

            pan.x = (viewerContainer.clientWidth - imageDisplayWidthAtCurrentZoom) / 2;
            pan.y = (viewerContainer.clientHeight - imageDisplayHeightAtCurrentZoom) / 2;


            console.log('Image Info:', imageInfo);
            console.log('Max zoom level (client maps to server zoom_level):', maxZoomLevel);
            console.log('Initial pan:', pan);

            renderTiles();
            setupEventListeners();

        } catch (error) {
            console.error('Initialization failed:', error);
            viewerContainer.innerHTML = `<p style="color:red; text-align:center;">Error loading viewer: ${error.message}</p>`;
        }
    }

    // --- Render Tiles ---

    // --- Touch Helper Functions ---
    function getDistance(p1, p2) {
        return Math.sqrt(Math.pow(p2.clientX - p1.clientX, 2) + Math.pow(p2.clientY - p1.clientY, 2));
    }

    function getMidpoint(p1, p2) {
        return {
            clientX: (p1.clientX + p2.clientX) / 2,
            clientY: (p1.clientY + p2.clientY) / 2
        };
    }


    // --- Touch Helper Functions ---
    function getDistance(p1, p2) {
        return Math.sqrt(Math.pow(p2.clientX - p1.clientX, 2) + Math.pow(p2.clientY - p1.clientY, 2));
    }

    function getMidpoint(p1, p2) {
        return {
            clientX: (p1.clientX + p2.clientX) / 2,
            clientY: (p1.clientY + p2.clientY) / 2
        };
    }

    function renderTiles() {
        if (!imageInfo.width || !imageInfo.height) return;

        viewerContainer.innerHTML = ''; // Clear previous tiles

        // sourcePixelsPerTileEdge: how many pixels from the source image are covered by one edge of a tile at the current zoom.
        const sourcePixelsPerTileEdge = TILE_SIZE / Math.pow(2, currentZoom);

        // numTilesX/Y: how many tiles are needed to cover the *entire source image* at this zoom level.
        const numTilesX = Math.ceil(imageInfo.width / sourcePixelsPerTileEdge);
        const numTilesY = Math.ceil(imageInfo.height / sourcePixelsPerTileEdge);

        // startTileX/Y, endTileX/Y: indices of tiles visible in the viewport.
        // pan.x is the offset of the image's top-left corner relative to the viewer's top-left corner.
        // If pan.x is 0, image top-left is at viewer top-left.
        // If pan.x is positive, image is shifted right. Tiles to the left become visible.
        // Tile with index 0 starts at pan.x. Its left edge is pan.x.
        // We need tiles where tile_left_edge < viewer_width AND tile_right_edge > 0
        // tx * TILE_SIZE + pan.x < viewerContainer.clientWidth
        // (tx + 1) * TILE_SIZE + pan.x > 0

        const firstVisibleTileX = Math.floor(-pan.x / TILE_SIZE);
        const firstVisibleTileY = Math.floor(-pan.y / TILE_SIZE);
        const lastVisibleTileX = Math.ceil((viewerContainer.clientWidth - pan.x) / TILE_SIZE);
        const lastVisibleTileY = Math.ceil((viewerContainer.clientHeight - pan.y) / TILE_SIZE);

        // console.log(`Zoom: ${currentZoom}, Pan: (${pan.x.toFixed(2)}, ${pan.y.toFixed(2)}), SourcePxPerTile: ${sourcePixelsPerTileEdge.toFixed(2)}`);
        // console.log(`Tile Range: X[${firstVisibleTileX}-${lastVisibleTileX}), Y[${firstVisibleTileY}-${lastVisibleTileY}) from total [${numTilesX}x${numTilesY}]`);

        for (let ty = firstVisibleTileY; ty < lastVisibleTileY; ty++) {
            for (let tx = firstVisibleTileX; tx < lastVisibleTileX; tx++) {

                if (tx < 0 || tx >= numTilesX || ty < 0 || ty >= numTilesY) {
                    continue; // Tile is outside the bounds of the image itself
                }

                const tile = document.createElement('img');
                tile.src = `../tile_server.php?action=get_tile&image_path=${encodeURIComponent(SOURCE_IMAGE_PATH)}&zoom_level=${currentZoom}&tile_x=${tx}&tile_y=${ty}`;
                tile.style.position = 'absolute';
                // tile.style.left: position of the tile's left edge within the viewer.
                // If tile tx=0, its left edge in the "world" is 0. With pan, it's pan.x.
                tile.style.left = `${pan.x + tx * TILE_SIZE}px`;
                tile.style.top = `${pan.y + ty * TILE_SIZE}px`;
                tile.style.width = `${TILE_SIZE}px`;
                tile.style.height = `${TILE_SIZE}px`;
                tile.setAttribute('data-tile-x', tx);
                tile.setAttribute('data-tile-y', ty);
                tile.draggable = false; // Prevent browser's default image drag
                tile.onerror = () => {
                    // console.error(`Error loading tile: ${tx}, ${ty} at zoom ${currentZoom}`);
                    tile.alt = `Error Tile ${tx},${ty} Z${currentZoom}`;
                };
                viewerContainer.appendChild(tile);
            }
        }
    }

    // --- Zoom Logic ---
    function performZoom(direction, mouseX, mouseY) { // +1 for zoom in, -1 for zoom out
        const oldZoom = currentZoom;
        const newZoom = Math.max(0, Math.min(maxZoomLevel, currentZoom + direction));

        if (newZoom === oldZoom) {
            console.log(`Already at min/max zoom: ${newZoom}`);
            return;
        }

        // worldX/YAtMouse: the coordinate in the source image (at current scale) that is under the mouse cursor.
        // mouseX/Y is relative to the viewer container.
        // pan.x is the viewer-relative position of the world's (0,0) point.
        // So, (mouseX - pan.x) is the world X coordinate under the mouse.
        const worldXAtMouse = mouseX - pan.x;
        const worldYAtMouse = mouseY - pan.y;

        // The scale factor changes how many "world" units fit into one screen pixel.
        // Or, how many screen pixels one "world" unit occupies.
        // Server zoom 'z' means TILE_SIZE source pixels are in TILE_SIZE / 2^z tile pixels.
        // Effective scale of source image at zoom 'z': Each source pixel becomes 2^z screen pixels.
        // Scale factor from oldZoom to newZoom: pow(2, newZoom) / pow(2, oldZoom) = pow(2, newZoom - oldZoom)
        const scaleChange = Math.pow(2, newZoom - oldZoom);

        // We want the worldXAtMouse to remain at the same mouseX position after zoom.
        // new_pan.x + worldXAtMouse * scaleChange = mouseX
        pan.x = mouseX - worldXAtMouse * scaleChange;
        pan.y = mouseY - worldYAtMouse * scaleChange;

        currentZoom = newZoom;
        console.log(`Zoomed to ${currentZoom}. Pan: ${pan.x}, ${pan.y}. ScaleChange: ${scaleChange}`);
        renderTiles();
    }

    // --- Pan Logic ---
    function startPan(event) {
        // Allow panning only with the primary mouse button (usually left)
        if (event.button !== 0) return;
        isPanning = true;
        lastPanPosition = { x: event.clientX, y: event.clientY };
        viewerContainer.style.cursor = 'grabbing';
        event.preventDefault(); // Prevent text selection or other default drag behaviors
    }

    function doPan(event) {
        if (!isPanning) return;
        const dx = event.clientX - lastPanPosition.x;
        const dy = event.clientY - lastPanPosition.y;

        pan.x += dx;
        pan.y += dy;

        lastPanPosition = { x: event.clientX, y: event.clientY };
        renderTiles();
    }

    function endPan() {
        if (!isPanning) return;
        isPanning = false;
        viewerContainer.style.cursor = 'grab';
    }

    function handleWheelZoom(event) {
        event.preventDefault(); // Prevent page scrolling
        const delta = -Math.sign(event.deltaY); // -1 for wheel up (zoom out by default), 1 for wheel down (zoom in by default)
                                             // Let's make wheel up = zoom in (+1), wheel down = zoom out (-1)
                                             // So delta = -Math.sign(event.deltaY)

        const rect = viewerContainer.getBoundingClientRect();
        const mouseX = event.clientX - rect.left; // Mouse position relative to viewer
        const mouseY = event.clientY - rect.top;

        performZoom(delta, mouseX, mouseY);
    }


    // --- Event Listeners ---

    // --- Touch Event Handlers ---
    function handleTouchStart(event) {
        event.preventDefault();
        // console.log("Touch start:", event.touches.length);
        if (event.touches.length === 2) {
            isPanning = false; // Stop any single-finger panning
            endPan(); // Reset pan state if it was active
            isPinching = true;
            const t0 = event.touches[0];
            const t1 = event.touches[1];
            initialPinchDistance = getDistance(t0, t1);
            // lastPinchMidpoint = getMidpoint(t0, t1); // Store initial midpoint for pan calc
            // console.log("Pinch start, dist:", initialPinchDistance);
        } else if (event.touches.length === 1 && !isPinching) { // Only start pan if not already pinching
            // isPinching = false; // Ensure not in pinch mode
            startPan({ clientX: event.touches[0].clientX, clientY: event.touches[0].clientY });
        }
    }

    function handleTouchMove(event) {
        event.preventDefault();
        // console.log("Touch move:", event.touches.length);
        if (isPinching && event.touches.length === 2) {
            const t0 = event.touches[0];
            const t1 = event.touches[1];
            const currentDistance = getDistance(t0, t1);
            const currentMidpoint = getMidpoint(t0, t1);

            if (initialPinchDistance === null) {
                initialPinchDistance = currentDistance; // Should have been set in touchstart
                // lastPinchMidpoint = currentMidpoint;
                return; // Or handle error
            }

            const scaleChangeFactor = currentDistance / initialPinchDistance;

            const zoomDelta = Math.log2(scaleChangeFactor);

            const oldZoom = currentZoom;
            let newZoomTarget = oldZoom + zoomDelta;
            let newCurrentZoom = Math.max(0, Math.min(maxZoomLevel, newZoomTarget));

            if (newCurrentZoom !== oldZoom) {
                const rect = viewerContainer.getBoundingClientRect();
                const pinchScreenX = currentMidpoint.clientX - rect.left;
                const pinchScreenY = currentMidpoint.clientY - rect.top;

                const worldXAtPinch = pinchScreenX - pan.x;
                const worldYAtPinch = pinchScreenY - pan.y;

                const newScaleFactor = Math.pow(2, newCurrentZoom - oldZoom);

                pan.x = pinchScreenX - worldXAtPinch * newScaleFactor;
                pan.y = pinchScreenY - worldYAtPinch * newScaleFactor;

                currentZoom = newCurrentZoom;
                renderTiles();
            }

            initialPinchDistance = currentDistance;
            // lastPinchMidpoint = currentMidpoint;
        } else if (isPanning && !isPinching && event.touches.length === 1) {
            // Panning with single touch (Step 4)
            doPan({ clientX: event.touches[0].clientX, clientY: event.touches[0].clientY });
        }
    }

    function handleTouchEnd(event) {
        // event.preventDefault(); // May not be needed or desirable on touchend for some scenarios
        // console.log("Touch end:", event.touches.length, "isPinching:", isPinching, "isPanning:", isPanning);
        if (isPinching && event.touches.length < 2) {
            isPinching = false;
            initialPinchDistance = null;
        }
        // If panning and the last finger is lifted (or all fingers if pinching also stopped)
        if (isPanning && event.touches.length === 0) {
             endPan();
        }
        // If it was a pinch and now less than 2 fingers, pinch ends.
        // If it was a pan and now 0 fingers, pan ends.
        // If after a pinch, one finger remains, it should not immediately start panning without a new touchstart.
        if (event.touches.length < 2 && isPinching) {
           isPinching = false;
           initialPinchDistance = null;
        }
        if (event.touches.length === 0 && isPanning) {
           endPan();
        }
        if (event.touches.length === 1 && !isPinching && !isPanning) {
           // Potentially treat as a new single touch start for panning if it moves, or handle tap actions.
           // For now, do nothing specific, subsequent move will trigger startPan via its own touchstart.
        }
    }

    function setupEventListeners() {
        if (zoomInButton) {
            zoomInButton.addEventListener('click', () => {
                const viewerCenterX = viewerContainer.clientWidth / 2;
                const viewerCenterY = viewerContainer.clientHeight / 2;
                performZoom(1, viewerCenterX, viewerCenterY); // Zoom in towards center
            });
        }
        if (zoomOutButton) {
            zoomOutButton.addEventListener('click', () => {
                const viewerCenterX = viewerContainer.clientWidth / 2;
                const viewerCenterY = viewerContainer.clientHeight / 2;
                performZoom(-1, viewerCenterX, viewerCenterY); // Zoom out from center
            });
        }

        viewerContainer.addEventListener('mousedown', startPan);
        document.addEventListener('mousemove', doPan);
        document.addEventListener('mouseup', endPan);
        viewerContainer.style.cursor = 'grab';

        viewerContainer.addEventListener('wheel', handleWheelZoom);

        // Touch events for pinch zoom and touch pan
        viewerContainer.addEventListener('touchstart', handleTouchStart, { passive: false });
        viewerContainer.addEventListener('touchmove', handleTouchMove, { passive: false });
        viewerContainer.addEventListener('touchend', handleTouchEnd, { passive: false });
        viewerContainer.addEventListener('touchcancel', handleTouchEnd, { passive: false }); // Treat cancel like end
    }

    // Start the application
    init();
});
