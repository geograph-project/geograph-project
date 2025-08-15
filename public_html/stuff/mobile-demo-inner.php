<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Image Editor</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body, html {
            margin: 0;
            padding: 0;
            height: 100%;
            overflow: hidden;
            font-family: 'Inter', sans-serif;
        }
        #app {
            display: flex;
            flex-direction: column;
            height: 100%;
            width: 100%;
            position: relative;
        }
        #canvas-container {
            flex-grow: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #f3f4f6;
            overflow: hidden;
        }
        #editor-canvas {
            max-width: 100%;
            max-height: 100%;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        }
        .control-panel {
            background-color: #fff;
            padding: 1rem;
            display: flex;
            justify-content: space-around;
            align-items: center;
            border-top: 1px solid #e5e7eb;
            flex-wrap: wrap;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div id="app">
        <!-- Main canvas display area -->
        <div id="canvas-container">
            <canvas id="editor-canvas"></canvas>
        </div>

        <!-- Editor controls -->
        <div class="control-panel">
            <!-- Rotation Controls -->
            <div class="flex flex-col items-center p-2">
                <label class="text-xs text-gray-500">Rotation</label>
                <div class="flex items-center space-x-2 mt-1">
                    <button id="rotate-left-90" class="bg-gray-200 hover:bg-gray-300 p-2 rounded-lg text-gray-700 font-bold">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-rotate-ccw"><path d="M3 11a9 9 0 1 0 9 9.3V20"/><path d="M6 14h6"/><path d="M11 2v9"/></svg>
                    </button>
                    <input type="range" id="rotation-slider" min="-45" max="45" value="0" step="1" class="w-24">
                    <button id="rotate-right-90" class="bg-gray-200 hover:bg-gray-300 p-2 rounded-lg text-gray-700 font-bold">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-rotate-cw"><path d="M21 11a9 9 0 1 0-9 9.3V20"/><path d="M18 14h-6"/><path d="M13 2v9"/></svg>
                    </button>
                </div>
            </div>

            <!-- Brightness Slider -->
            <div class="flex flex-col items-center p-2">
                <label for="brightness-slider" class="text-xs text-gray-500">Brightness</label>
                <input type="range" id="brightness-slider" min="0.5" max="1.5" value="1" step="0.01" class="w-24 mt-1">
            </div>

            <!-- Contrast Slider -->
            <div class="flex flex-col items-center p-2">
                <label for="contrast-slider" class="text-xs text-gray-500">Contrast</label>
                <input type="range" id="contrast-slider" min="0.5" max="1.5" value="1" step="0.01" class="w-24 mt-1">
            </div>

            <!-- Crop button -->
            <div class="flex flex-col items-center p-2">
                <label class="text-xs text-gray-500">Crop</label>
                <button id="crop-toggle" class="bg-blue-200 hover:bg-blue-300 text-blue-700 font-bold py-2 px-4 rounded-full mt-1">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-crop"><path d="M6.13 1L1 6.13l17.7 17.7L23 17.7z"/><path d="M11 4v8a2 2 0 0 0 2 2h8"/><path d="M7 9v11a2 2 0 0 0 2 2h11"/></svg>
                </button>
            </div>
            
            <!-- Apply/Continue Buttons -->
            <div class="flex space-x-2 p-2">
                <button id="continue-btn" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded-full mt-auto">
                    Continue without edits
                </button>
                <button id="apply-edits" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-full mt-auto">
                    Apply
                </button>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const canvas = document.getElementById('editor-canvas');
            const ctx = canvas.getContext('2d');
            const rotationSlider = document.getElementById('rotation-slider');
            const brightnessSlider = document.getElementById('brightness-slider');
            const contrastSlider = document.getElementById('contrast-slider');
            const cropToggleBtn = document.getElementById('crop-toggle');
            const applyEditsBtn = document.getElementById('apply-edits');
            const continueBtn = document.getElementById('continue-btn');
            const rotateLeft90Btn = document.getElementById('rotate-left-90');
            const rotateRight90Btn = document.getElementById('rotate-right-90');
            const canvasContainer = document.getElementById('canvas-container');

            let image = new Image();
            let originalImage = new Image();
            let currentRotation = 0; // Tracks the 90-degree rotations
            let isCropping = false;
            let startX, startY;
            let cropRect = { x: 0, y: 0, w: 0, h: 0 };
            let imageScale = 1;

            // Function to redraw the image on the canvas with current settings
            function redraw() {
                const img = image;

                // Adjust canvas size to match the container
                const containerWidth = canvasContainer.clientWidth;
                const containerHeight = canvasContainer.clientHeight;
                
                imageScale = Math.min(containerWidth / img.width, containerHeight / img.height);
                
                // Combine 90-degree rotations and slider fine-tune rotation
                const totalRotation = parseInt(currentRotation, 10) + parseInt(rotationSlider.value, 10);
                const rad = totalRotation * Math.PI / 180;
                
                const rotatedWidth = (Math.abs(Math.cos(rad)) * img.width + Math.abs(Math.sin(rad)) * img.height);
                const rotatedHeight = (Math.abs(Math.sin(rad)) * img.width + Math.abs(Math.cos(rad)) * img.height);

                canvas.width = rotatedWidth * imageScale;
                canvas.height = rotatedHeight * imageScale;

                ctx.save();
                ctx.translate(canvas.width / 2, canvas.height / 2);
                ctx.rotate(rad);
                
                // Clear the canvas before drawing the new state
                ctx.clearRect(-img.width / 2, -img.height / 2, img.width, img.height);

                // Apply filters directly to the canvas context
                ctx.filter = `brightness(${brightnessSlider.value}) contrast(${contrastSlider.value})`;

                ctx.drawImage(img, -img.width / 2 * imageScale, -img.height / 2 * imageScale, img.width * imageScale, img.height * imageScale);
                ctx.restore();

                // Draw the cropping overlay if in cropping mode
                if (isCropping) {
                    ctx.fillStyle = 'rgba(0, 0, 0, 0.5)';
                    ctx.fillRect(0, 0, canvas.width, canvas.height);
                    ctx.clearRect(cropRect.x, cropRect.y, cropRect.w, cropRect.h);
                    ctx.strokeStyle = '#fff';
                    ctx.lineWidth = 2;
                    ctx.strokeRect(cropRect.x, cropRect.y, cropRect.w, cropRect.h);
                }
            }

            // Function to load the image and initialize the canvas
            function loadImage(imageData) {
                originalImage.onload = () => {
                    image.src = originalImage.src;
                    image.onload = () => {
                        currentRotation = 0;
                        rotationSlider.value = 0;
                        brightnessSlider.value = 1;
                        contrastSlider.value = 1;
                        isCropping = false;
                        cropRect = { x: 0, y: 0, w: 0, h: 0 };
                        redraw();
                    };
                };
                originalImage.src = imageData;
            }

            // Event listener for messages from the parent window (the app mockup)
            window.addEventListener('message', (event) => {
                if (event.data.type === 'image_data') {
                    loadImage(event.data.data);
                }
            });

            // Sliders for brightness, contrast, and rotation
            rotationSlider.addEventListener('input', redraw);
            brightnessSlider.addEventListener('input', redraw);
            contrastSlider.addEventListener('input', redraw);

            // 90-degree rotation buttons
            rotateLeft90Btn.addEventListener('click', () => {
                currentRotation = (currentRotation - 90 + 360) % 360;
                rotationSlider.value = 0; // Reset fine-tune slider
                redraw();
            });

            rotateRight90Btn.addEventListener('click', () => {
                currentRotation = (currentRotation + 90) % 360;
                rotationSlider.value = 0; // Reset fine-tune slider
                redraw();
            });

            // Crop toggle button
            cropToggleBtn.addEventListener('click', () => {
                isCropping = !isCropping;
                redraw();
            });

            // Mouse events for cropping
            canvas.addEventListener('mousedown', (e) => {
                if (isCropping) {
                    const rect = canvas.getBoundingClientRect();
                    startX = e.clientX - rect.left;
                    startY = e.clientY - rect.top;
                    cropRect.x = startX;
                    cropRect.y = startY;
                    cropRect.w = 0;
                    cropRect.h = 0;
                }
            });

            canvas.addEventListener('mousemove', (e) => {
                if (isCropping && startX !== undefined) {
                    const rect = canvas.getBoundingClientRect();
                    const endX = e.clientX - rect.left;
                    const endY = e.clientY - rect.top;
                    cropRect.w = endX - startX;
                    cropRect.h = endY - startY;
                    redraw();
                }
            });

            canvas.addEventListener('mouseup', () => {
                if (isCropping) {
                    startX = undefined;
                    startY = undefined;
                    // Normalize the crop rectangle to handle negative widths/heights
                    if (cropRect.w < 0) {
                        cropRect.x += cropRect.w;
                        cropRect.w = -cropRect.w;
                    }
                    if (cropRect.h < 0) {
                        cropRect.y += cropRect.h;
                        cropRect.h = -cropRect.h;
                    }
                    redraw();
                }
            });

            // Handle window resizing to keep the canvas responsive
            window.addEventListener('resize', redraw);

            // Apply edits and send the final image back to the parent frame
            applyEditsBtn.addEventListener('click', () => {
                isCropping = false;
                
                const totalRotation = parseInt(currentRotation, 10) + parseInt(rotationSlider.value, 10);
                const rad = totalRotation * Math.PI / 180;
                
                // Create a temporary canvas to perform all edits
                const tempCanvas = document.createElement('canvas');
                const tempCtx = tempCanvas.getContext('2d');
                
                const originalW = image.width;
                const originalH = image.height;
                const absCos = Math.abs(Math.cos(rad));
                const absSin = Math.abs(Math.sin(rad));

                let finalImageData;

                // Check if a custom crop rectangle was drawn
                if (cropRect.w > 0 && cropRect.h > 0) {
                    // Custom crop logic
                    tempCanvas.width = cropRect.w;
                    tempCanvas.height = cropRect.h;
                    const scaledCropX = cropRect.x / imageScale;
                    const scaledCropY = cropRect.y / imageScale;
                    const scaledCropW = cropRect.w / imageScale;
                    const scaledCropH = cropRect.h / imageScale;
                    
                    tempCtx.drawImage(canvas, scaledCropX, scaledCropY, scaledCropW, scaledCropH, 0, 0, tempCanvas.width, tempCanvas.height);
                    finalImageData = tempCanvas.toDataURL('image/jpeg');

                } else if (totalRotation !== 0) {
                    // Auto-crop logic for rotated image
                    
                    // The dimensions of the largest inscribed rectangle
                    const croppedW = originalW * absCos + originalH * absSin;
                    const croppedH = originalW * absSin + originalH * absCos;
                    const scaleFactor = Math.min(originalW / croppedW, originalH / croppedH);
                    const finalW = originalW * scaleFactor;
                    const finalH = originalH * scaleFactor;

                    tempCanvas.width = finalW;
                    tempCanvas.height = finalH;

                    tempCtx.save();
                    tempCtx.translate(tempCanvas.width / 2, tempCanvas.height / 2);
                    tempCtx.rotate(rad);
                    tempCtx.filter = `brightness(${brightnessSlider.value}) contrast(${contrastSlider.value})`;
                    tempCtx.drawImage(image, -originalW / 2, -originalH / 2, originalW, originalH);
                    tempCtx.restore();

                    finalImageData = tempCanvas.toDataURL('image/jpeg');
                } else {
                    // No rotation, just apply filters
                    tempCanvas.width = image.width;
                    tempCanvas.height = image.height;
                    tempCtx.filter = `brightness(${brightnessSlider.value}) contrast(${contrastSlider.value})`;
                    tempCtx.drawImage(image, 0, 0, image.width, image.height);
                    finalImageData = tempCanvas.toDataURL('image/jpeg');
                }

                window.parent.postMessage({ type: 'edited_image_data', data: finalImageData }, '*');
            });

            // Continue with the original unedited image
            continueBtn.addEventListener('click', () => {
                // Send the original image data back to the parent window
                window.parent.postMessage({ type: 'edited_image_data', data: originalImage.src }, '*');
            });
        });
    </script>
</body>
</html>
