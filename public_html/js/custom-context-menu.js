/**
 * Custom Image Context Menu Component
 * * Creates a custom right-click menu for all <img> elements on the page,
 * replicating standard browser functionality and adding custom actions.
 */
document.addEventListener('DOMContentLoaded', () => {

    // --- Configuration ---
    const LONG_PRESS_DURATION = 700; // Time in ms for a press to count as 'long'
    const MOBILE_BREAKPOINT = 600;   // Viewport width (in pixels) to trigger fixed/full-screen menu

    // --- 1. Define CSS Styles for the Menu ---
    const menuStyles = `
        .custom-context-menu {
            /* Base styles for desktop/mobile */
            background-color: #fff;
            border: 1px solid #ccc;
            box-shadow: 3px 3px 8px rgba(0, 0, 0, 0.2);
            padding: 5px 0;
            z-index: 10000; /* High z-index to ensure visibility */
            display: none;
            list-style: none;
            margin: 0;
            font-family: Arial, sans-serif;
            font-size: 14px;
            min-width: 180px;

            /* Desktop positioning */
            position: absolute;
        }

        /* Mobile specific styles (Full-screen overlay) */
        @media (max-width: ${MOBILE_BREAKPOINT}px) {
            .custom-context-menu {
                position: fixed; /* Essential for mobile full-screen */
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                width: 100%;
                height: 100%;
                box-shadow: none; /* Remove box shadow on full screen */
                border: none;
                padding-top: 20vh; /* Push menu items down slightly */
                background-color: rgba(255, 255, 255, 0.95); /* Semi-transparent background */
                text-align: center;
                min-width: auto;
            }
            .custom-context-menu-item {
                font-size: 18px; /* Larger text for mobile touch targets */
                padding: 15px;
            }
        }

        .custom-context-menu-item {
            padding: 8px 15px;
            cursor: pointer;
            white-space: nowrap;
            user-select: none; /* Prevent text selection on menu interaction */
        }

        .custom-context-menu-item:hover {
            background-color: #e0e0e0;
        }

        .custom-context-menu-divider {
            height: 1px;
            background-color: #ddd;
            margin: 5px 0;
        }
    `;

    // Inject the CSS into the document's head
    const styleSheet = document.createElement("style");
    styleSheet.type = "text/css";
    styleSheet.innerText = menuStyles;
    document.head.appendChild(styleSheet);


    // --- 2. Create the Menu HTML Element ---
    const contextMenu = document.createElement('ul');
    contextMenu.id = 'customContextMenu';
    contextMenu.className = 'custom-context-menu';
    document.body.appendChild(contextMenu);

    let currentTarget = null;
    let longPressTimer = null; // Timer for long press detect

    // --- 3. Menu Utility Functions ---

    const hideMenu = () => {
        contextMenu.style.display = 'none';
    };

    const createMenuItem = (label, action, data = null) => {
        const li = document.createElement('li');
        li.className = 'custom-context-menu-item';
        li.textContent = label;
        li.addEventListener('click', (e) => {
            e.stopPropagation(); // Stop click from propagati
            action(data || currentTarget);
            hideMenu(); // Hide menu after action
        });
        return li;
    };

    const addDivider = () => {
        const divider = document.createElement('li');
        divider.className = 'custom-context-menu-divider';
        contextMenu.appendChild(divider);
    };

    // --- 4. Menu Content Builder Function ---

    const buildMenu = (e) => {
        const imageSrc = currentTarget.src;
        const parentLink = currentTarget.closest('A');

        // Clear and rebuild menu
        contextMenu.innerHTML = '';

        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;

        if (viewportWidth < MOBILE_BREAKPOINT) {

            if (viewportWidth < viewportHeight) {
                const thumbItem = document.createElement('li');
                thumbItem.style.marginBottom = '20px';
                thumbItem.style.pointerEvents = 'none'; // Make sure the thumbnail is not clickable
                thumbItem.innerHTML = `<img src="${imageSrc}" style="max-width: 100px; max-height: 100px; border: 1px solid #ccc; display: block; margin: 0 auto; object-fit: contain;">`;
                contextMenu.appendChild(thumbItem);
            }
	}

        const geographMatch = imageSrc.match(/\/(\d{6,})_\w{8}/);

        // A. Link Options (if inside an <a> tag)
        if (parentLink) {
            const linkUrl = parentLink.href;

            contextMenu.appendChild(createMenuItem('Open Link', () => { 
                window.open(linkUrl, '_self'); 
            }));
            contextMenu.appendChild(createMenuItem('Open Link in New Tab', () => { 
                window.open(linkUrl, '_blank'); 
            }));
            contextMenu.appendChild(createMenuItem('Copy Link Location', () => { 
                navigator.clipboard.writeText(linkUrl);
            }));
            addDivider();
        } else if (geographMatch) {
            contextMenu.appendChild(createMenuItem('Open Photo Page', () => {
                window.open(`/photo/{imageId}`, '_self');
            }));
            contextMenu.appendChild(createMenuItem('Open Photo Page in New Tab', () => {
                window.open(`/photo/{imageId}`, '_blank');
            }));
            contextMenu.appendChild(createMenuItem('Copy Photo Page URL', () => { 
                navigator.clipboard.writeText(window.location.origin+`/photo/{imageId}`);
            }));
	}

/*

        // B. Standard Image Options
        contextMenu.appendChild(createMenuItem('Open Image in New Tab', () => { 
            window.open(imageSrc, '_blank'); 
        }));
        contextMenu.appendChild(createMenuItem('Copy Image Location', () => { 
            navigator.clipboard.writeText(imageSrc);
        }));
        contextMenu.appendChild(createMenuItem('Copy Image (Best Effort)', () => { 
             // Note: Copying binary data is complex. This opens the image for user to copy.
            window.open(imageSrc, '_blank');
            alert('Please right-click and copy the image from the new tab.');
        }));
        contextMenu.appendChild(createMenuItem('Save Image As (Opens in new tab)', () => { 
            window.open(imageSrc, '_blank');
        }));
*/
        addDivider();

        // C. Custom Geograph Option
        if (geographMatch) {
            const imageId = geographMatch[1];

            const searchUrl = `https://www.geograph.org.uk/finder/finder.php?q=id%3A${imageId}`;
            contextMenu.appendChild(createMenuItem('Search Similar Images', () => {
                window.open(searchUrl, '_blank');
            }));

            contextMenu.appendChild(createMenuItem('View Licencing and Download Options', () => {
                window.open(`/reuse.php?id=${imageId}`, '_blank');
            }));

            contextMenu.appendChild(createMenuItem('Download Stamped Image', () => {
                window.open(`https://t0.geograph.org.uk/stamp.php?id=${imageId}&large=1024&download=true`, '_blank');
            }));


            addDivider();

            contextMenu.appendChild(createMenuItem('Search Nearby Images', () => {
		//todo, search will need updating to understand id: syntax. Or maybe do in places.json.php!
                window.open(`/finder/finder.php?loc=id:${imageId}&distance=2000`, '_blank');
            }));

        }
        
        // --- 5. Display and Positioning Logic (Handles Upward Opening) ---
        
        // Make visible for accurate dimension calculation
        contextMenu.style.display = 'block';

        if (viewportWidth > MOBILE_BREAKPOINT) {
            // --- DESKTOP Positioning (Absolute + Upward/Downward) ---
            
            const menuHeight = contextMenu.offsetHeight;
            const clickY = e.clientY; 

            // Horizontal position
            contextMenu.style.left = `${e.pageX}px`;

            // Vertical position: Open Upwards if insufficient space below
            const spaceBelow = viewportHeight - clickY;
            
            if (spaceBelow < menuHeight && clickY > menuHeight) {
                contextMenu.style.top = `${e.pageY - menuHeight}px`;
            } else {
                contextMenu.style.top = `${e.pageY}px`;
            }
        } else {
            // --- MOBILE Positioning (Fixed Full-Screen - CSS handles most of it) ---

            // On mobile, we don't need to calculate precise top/left, as CSS sets it fixed 0/0.
            contextMenu.style.top = '0px'; 
            contextMenu.style.left = '0px'; 
            
            // Add a "Cancel" or "Close" button for a better mobile experience
            addDivider();
            contextMenu.appendChild(createMenuItem('Close Menu', hideMenu));
        }

        // Prevent browser context menu on desktop/long-press
        contextMenu.oncontextmenu = (ce) => {
            ce.preventDefault();
        };
    };

    // --- 6. Attach Event Listeners ---

    // A. Desktop/Standard Context Menu (Right-Click)
    document.addEventListener('contextmenu', (e) => {
        if (e.target.tagName === 'IMG') {
            e.preventDefault();
            e.stopPropagation();
            currentTarget = e.target;
            buildMenu(e);
        } else {
            hideMenu();
        }
    });

    // B. Mobile Long-Press (Touch Events)

    // Helper to clear the timer
    const clearPressTimer = () => {
        if (longPressTimer) {
            clearTimeout(longPressTimer);
            longPressTimer = null;
        }
    };

    // 1. touchstart: Start the timer
    document.addEventListener('touchstart', (e) => {
        clearPressTimer();
        const touchTarget = e.target;

        if (touchTarget.tagName === 'IMG') {
            // Store the target and the touch coordinates
            currentTarget = touchTarget;
            const touch = e.touches[0];
            
            // Start the timer
            longPressTimer = setTimeout(() => {
                // When timer expires, trigger the menu and prevent default browser action
                e.preventDefault(); 
                buildMenu({
                    pageX: touch.pageX,
                    pageY: touch.pageY,
                    clientX: touch.clientX,
                    clientY: touch.clientY,
                    target: touchTarget // Pass target data for consistency
                });
            }, LONG_PRESS_DURATION);
        } else {
             hideMenu();
        }
    }, { passive: false }); // Use passive: false to allow e.preventDefault() in timer

    // 2. touchend, touchmove: Clear the timer (cancels the long press)
    document.addEventListener('touchend', clearPressTimer);
    document.addEventListener('touchmove', clearPressTimer); 
    
    // C. General Click Handler to Hide Menu
    document.addEventListener('click', (e) => {
        // Only hide if the click is outside the menu
        if (!contextMenu.contains(e.target)) {
            hideMenu();
        }
    });
});

