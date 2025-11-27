/**
 * Custom Image Context Menu Component
 * * Creates a custom right-click menu for all <img> elements on the page,
 * replicating standard browser functionality and adding custom actions.
 */
document.addEventListener('DOMContentLoaded', () => {

    // --- 1. Define CSS Styles for the Menu ---
    const menuStyles = `
        .custom-context-menu {
            position: absolute;
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

    // --- 3. Menu Utility Functions ---

    const hideMenu = () => {
        contextMenu.style.display = 'none';
    };

    const createMenuItem = (label, action, data = null) => {
        const li = document.createElement('li');
        li.className = 'custom-context-menu-item';
        li.textContent = label;
        li.addEventListener('click', () => {
            action(data || currentTarget);
        });
        return li;
    };

    const addDivider = () => {
        const divider = document.createElement('li');
        divider.className = 'custom-context-menu-divider';
        contextMenu.appendChild(divider);
    };

    // --- 4. Main Context Menu Handler ---

    const handleContextMenu = (e) => {
        // Only target images
        if (e.target.tagName !== 'IMG') {
            hideMenu();
            return; 
        }

        e.preventDefault(); 
        e.stopPropagation();

        currentTarget = e.target;
        const imageSrc = currentTarget.src;
        const parentLink = currentTarget.closest('A');

        // Clear and rebuild menu
        contextMenu.innerHTML = '';

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

            contextMenu.appendChild(createMenuItem('Mark Image', () => {
                window.open(searchUrl, '_blank');
            }));

            const searchUrl = `https://www.geograph.org.uk/finder/finder.php?q=id%3A${imageId}`;
            contextMenu.appendChild(createMenuItem('Search Similar Images', () => {
                window.open(searchUrl, '_blank');
            }));

            contextMenu.appendChild(createMenuItem('View Licencing and Download Options', () => {
                window.open(searchUrl, '_blank');
            }));

            contextMenu.appendChild(createMenuItem('Download Stamped Image', () => {
                window.open(searchUrl, '_blank');
            }));


            addDivider();

            contextMenu.appendChild(createMenuItem('Search Nearby Images', () => {
                window.open(searchUrl, '_blank');
            }));

        }
        
        // --- 5. Display and Positioning Logic (Handles Upward Opening) ---
        
        // Make visible for accurate dimension calculation
        contextMenu.style.display = 'block';

        const menuHeight = contextMenu.offsetHeight;
        const clickY = e.clientY; 
        const viewportHeight = window.innerHeight;

        // Horizontal position
        contextMenu.style.left = `${e.pageX}px`;

        // Vertical position: Open Upwards if insufficient space below
        const spaceBelow = viewportHeight - clickY;
        
        if (spaceBelow < menuHeight && clickY > menuHeight) {
            // Not enough space below, and enough space above -> Position UPWARDS
            contextMenu.style.top = `${e.pageY - menuHeight}px`;
        } else {
            // Default position -> DOWNWARDS
            contextMenu.style.top = `${e.pageY}px`;
        }

        // Prevent menu from closing/re-appearing if right-clicked again inside itself
        contextMenu.oncontextmenu = (ce) => {
            ce.preventDefault();
        };
    };

    // --- 6. Attach Event Listeners ---
    
    // Attach the main context menu handler to the whole document
    document.addEventListener('contextmenu', handleContextMenu);
    
    // Hide the menu on any regular click
    document.addEventListener('click', hideMenu);

    // Stop propagation on the menu itself to prevent instant hiding
    contextMenu.addEventListener('click', (e) => {
        e.stopPropagation();
        hideMenu(); // Hide after an item is clicked
    });
});
