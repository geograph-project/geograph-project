document.addEventListener('DOMContentLoaded', () => {
    const images = document.querySelectorAll('img[data-full]');
    images.forEach(img => {
        img.style.cursor = 'zoom-in';
        img.addEventListener('click', () => {
            const fullResUrl = img.getAttribute('data-full');
            if (fullResUrl) {
                openFullscreenViewer(fullResUrl);
            }
        });
    });
});

function openFullscreenViewer(imageUrl) {
    // Dynamically create viewer elements
    const viewer = document.createElement('div');
    viewer.classList.add('image-viewer');
    viewer.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.9);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1000;
        overflow: hidden; /* Added overflow hidden to prevent scrollbars */
    `;

    const image = document.createElement('img');
    image.src = imageUrl;
    image.style.cssText = `
        max-width: 100%;
        max-height: 100%;
        object-fit: contain; /* Start with 'contain' to see the full image first */
        transition: transform 0.2s ease-out;
        will-change: transform;
        cursor: grab;
    `;

    image.onload = () => {
        // Now that the image has loaded, we can switch to 'cover' for the full-screen effect
        image.style.objectFit = 'cover';
    };

    const closeBtn = document.createElement('button');
    closeBtn.textContent = '✖';
    closeBtn.style.cssText = `
        position: absolute;
        top: 20px;
        right: 20px;
        background: none;
        border: none;
        color: white;
        font-size: 2.5em;
        cursor: pointer;
        z-index: 1001;
        text-shadow: 0 0 5px black;
    `;

    const zoomInBtn = document.createElement('button');
    zoomInBtn.textContent = '＋';
    zoomInBtn.style.cssText = `
        position: absolute;
        bottom: 20px;
        right: 80px;
        background: rgba(255, 255, 255, 0.2);
        border: 1px solid white;
        color: white;
        font-size: 2em;
        cursor: pointer;
        width: 40px;
        height: 40px;
        line-height: 40px;
        text-align: center;
        border-radius: 5px;
        z-index: 1001;
    `;

    const zoomOutBtn = document.createElement('button');
    zoomOutBtn.textContent = '－';
    zoomOutBtn.style.cssText = `
        position: absolute;
        bottom: 20px;
        right: 20px;
        background: rgba(255, 255, 255, 0.2);
        border: 1px solid white;
        color: white;
        font-size: 2em;
        cursor: pointer;
        width: 40px;
        height: 40px;
        line-height: 40px;
        text-align: center;
        border-radius: 5px;
        z-index: 1001;
    `;

    // Append elements to the body
    viewer.appendChild(image);
    viewer.appendChild(closeBtn);
    viewer.appendChild(zoomInBtn);
    viewer.appendChild(zoomOutBtn);
    document.body.appendChild(viewer);
    document.body.style.overflow = 'hidden';

    // State variables
    let scale = 1;
    let isPanning = false;
    let startX = 0;
    let startY = 0;
    let panX = 0;
    let panY = 0;

    // Function to update the image transform
    function updateTransform() {
        image.style.transform = `scale(${scale}) translate(${panX}px, ${panY}px)`;
        image.style.cursor = scale > 1 ? 'grab' : 'default';
    }

    // Event listeners
    closeBtn.addEventListener('click', () => {
        document.body.removeChild(viewer);
        document.body.style.overflow = 'auto';
    });

    viewer.addEventListener('click', (e) => {
        if (e.target === viewer) {
            document.body.removeChild(viewer);
            document.body.style.overflow = 'auto';
        }
    });

    zoomInBtn.addEventListener('click', () => {
        scale = Math.min(scale * 1.2, 5); // Max zoom 5x
        updateTransform();
    });

    zoomOutBtn.addEventListener('click', () => {
        scale = Math.max(scale / 1.2, 1); // Min zoom 1x
        if (scale === 1) {
            panX = 0;
            panY = 0;
        }
        updateTransform();
    });

    image.addEventListener('mousedown', (e) => {
        if (scale > 1) {
            isPanning = true;
            image.style.cursor = 'grabbing';
            startX = e.clientX;
            startY = e.clientY;
        }
    });

    viewer.addEventListener('mousemove', (e) => {
        if (!isPanning) return;
        const dx = e.clientX - startX;
        const dy = e.clientY - startY;

        panX += dx / scale;
        panY += dy / scale;

        startX = e.clientX;
        startY = e.clientY;

        updateTransform();
    });

    viewer.addEventListener('mouseup', () => {
        isPanning = false;
        updateTransform();
    });

    viewer.addEventListener('mouseleave', () => {
        isPanning = false;
        updateTransform();
    });
}
