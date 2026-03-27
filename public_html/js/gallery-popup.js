/**
 * Modern Native Modal Gallery
 * Usage: openGallery('.img', clickedElement);
 */

/**
 * Animated Touch-Friendly Native Modal Gallery
 */

const openGallery = (selector, startElement) => {
    const elements = Array.from(document.querySelectorAll(selector));
    let currentIndex = elements.indexOf(startElement);

    if (!document.getElementById('gallery-styles')) {
        const style = document.createElement('style');
        style.id = 'gallery-styles';
        style.textContent = `
            #img-modal {
                padding: 0; border: none; border-radius: 12px;
                background: #000; color: #fff;
                width: 95vw; height: 90vh; 
                max-width: 800px; max-height: 800px;
                overflow: hidden;
            }
            #img-modal::backdrop { background: rgba(0,0,0,0.9); backdrop-filter: blur(5px); }
            
            .gal-container {
                display: flex; flex-direction: column;
                width: 100%; height: 100%;
            }
            
            .gal-stage {
                position: relative; 
                flex: 1; 
                display: flex; align-items: center; justify-content: center;
                background: #000;
                overflow: hidden;
                touch-action: none; /* Prevents browser scroll while swiping */
            }
            
            #gal-main-img {
                width: auto; height: auto;
                max-width: min(100%, 640px); 
                max-height: min(100%, 640px);
                object-fit: contain;
                will-change: transform, opacity;
                cursor: grab;
            }
            #gal-main-img:active { cursor: grabbing; }

            .gal-info {
                height: 100px; padding: 0 30px;
                display: flex; flex-direction: column; justify-content: center;
                background: #0a0a0a; text-align: center;
                border-top: 1px solid #222; z-index: 5;
            }

            .gal-btn {
                position: absolute; top: 0; bottom: 0;
                width: 80px; background: none; border: none; 
                color: rgba(255,255,255,0.4); cursor: pointer; 
                font-size: 40px; z-index: 10; transition: color 0.2s;
            }
            .gal-btn:hover { color: #fff; }
            .prev-btn { left: 0; }
            .next-btn { right: 0; }
            
            .close-btn { 
                position: absolute; top: 15px; right: 15px; 
                background: none; border: none; color: #888; 
                font-size: 30px; cursor: pointer; z-index: 20;
            }

            @media (max-height: 500px) and (orientation: landscape) {
                .gal-container { flex-direction: row; }
                .gal-info { width: 280px; height: 100%; border-top: none; border-left: 1px solid #222; text-align: left; }
            }
        `;
        document.head.appendChild(style);
    }

    let dialog = document.getElementById('img-modal');
    if (!dialog) {
        dialog = document.createElement('dialog');
        dialog.id = 'img-modal';
        dialog.innerHTML = `
            <div class="gal-container">
                <button class="close-btn">&times;</button>
                <div class="gal-stage">
                    <button class="gal-btn prev-btn">&#10094;</button>
                    <img id="gal-main-img" src="" draggable="false" crossorigin style="image-orientation:none">
                    <button class="gal-btn next-btn">&#10095;</button>
                </div>
                <div class="gal-info">
                    <strong id="gal-title"></strong>
                    <span id="gal-counter"></span>
                </div>
            </div>
        `;
        document.body.appendChild(dialog);

        dialog.querySelector('.close-btn').onclick = () => dialog.close();
        
        const navigate = (step) => {
            currentIndex = (currentIndex + step + elements.length) % elements.length;
            updateContent(step); 
        };

        dialog.querySelector('.prev-btn').onclick = (e) => { e.stopPropagation(); navigate(-1); };
        dialog.querySelector('.next-btn').onclick = (e) => { e.stopPropagation(); navigate(1); };

        // --- Swipe & Animation Logic ---
        const img = dialog.querySelector('#gal-main-img');
        let startX = 0;
        let currentX = 0;
        let isDragging = false;

        const onStart = (e) => {
            startX = e.type.includes('touch') ? e.touches[0].clientX : e.clientX;
            isDragging = true;
            img.style.transition = 'none'; // Instant response while dragging
        };

        const onMove = (e) => {
            if (!isDragging) return;
            const x = e.type.includes('touch') ? e.touches[0].clientX : e.clientX;
            currentX = x - startX;
            // Move image and slightly fade it out as it moves away
            const opacity = 1 - (Math.abs(currentX) / 600);
            img.style.transform = `translateX(${currentX}px)`;
            img.style.opacity = opacity;
        };

        const onEnd = () => {
            if (!isDragging) return;
            isDragging = false;
            img.style.transition = 'all 0.3s ease-out';
            
            if (Math.abs(currentX) > 100) {
                // Trigger navigation
                navigate(currentX > 0 ? -1 : 1);
            } else {
                // Snap back
                img.style.transform = `translateX(0px)`;
                img.style.opacity = 1;
            }
            currentX = 0;
        };

        img.addEventListener('touchstart', onStart, {passive: true});
        window.addEventListener('touchmove', onMove, {passive: false});
        window.addEventListener('touchend', onEnd);
        
        // Also support Mouse dragging for desktop
        img.addEventListener('mousedown', onStart);
        window.addEventListener('mousemove', onMove);
        window.addEventListener('mouseup', onEnd);

        dialog.addEventListener('keydown', e => {
            if (e.key === 'ArrowLeft') navigate(-1);
            if (e.key === 'ArrowRight') navigate(1);
        });
    }

    const updateContent = (direction = 0) => {
        const el = elements[currentIndex];
        const img = dialog.querySelector('#gal-main-img');
        
        // Slide out animation if triggered by button
        if(direction !== 0) {
            img.style.transition = 'none';
            img.style.transform = `translateX(${direction * 100}px)`;
            img.style.opacity = 0;
        }

        const thumbSrc = el.querySelector('img').src;
        const fullSrc = thumbSrc.replace(/_\d+x\d+/, '');
        
        img.src = fullSrc;
        
        img.onload = () => {
            img.style.transition = 'all 0.3s ease-out';
            img.style.transform = `translateX(0px)`;
            img.style.opacity = 1;
        };
        
        dialog.querySelector('#gal-title').textContent = el.title || "";
        dialog.querySelector('#gal-counter').textContent = `${currentIndex + 1} / ${elements.length}`;
    };

    updateContent();
    dialog.showModal();
};

/* HOW TO USE...

window.addEventListener('click', (e) => {
    const link = e.target.closest('a.img');
    if (link) {
        e.preventDefault();
        openGallery('a.img', link);
    }
});

*/
