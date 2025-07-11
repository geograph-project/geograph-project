let currentPage = 1;
let currentQuery = '';
const selectedImageIds = new Set(); // Stores IDs of images currently in the 'Selected Images' list

$(document).ready(function() {
    const API_DOMAIN = 'https://api.geograph.org.uk';
    const pageSize = 20;
    let longClickTimer = null; // To store the timeout ID for long click differentiation
    const LONG_CLICK_DELAY = 500; // Milliseconds to hold for a long click

    // --- Theme Toggle Logic ---
    const themeToggleBtn = $('#themeToggle');
    const body = $('body');
    const DARK_THEME_CLASS = 'dark-theme';
    const THEME_STORAGE_KEY = 'imageCuratorTheme';

    // Function to set the theme
    function setTheme(theme) {
        if (theme === 'dark') {
            body.addClass(DARK_THEME_CLASS);
            themeToggleBtn.text('Toggle Light Theme');
        } else {
            body.removeClass(DARK_THEME_CLASS);
            themeToggleBtn.text('Toggle Dark Theme');
        }
        localStorage.setItem(THEME_STORAGE_KEY, theme);
    }

    // Load theme from localStorage on startup
    const savedTheme = localStorage.getItem(THEME_STORAGE_KEY);
    if (savedTheme) {
        setTheme(savedTheme);
    } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
        // Optional: Detect OS dark mode preference if no theme is saved
        setTheme('dark');
    } else {
        setTheme('light'); // Default to light if no preference and no OS preference
    }

    // Event listener for theme toggle button
    themeToggleBtn.on('click', function() {
        if (body.hasClass(DARK_THEME_CLASS)) {
            setTheme('light');
        } else {
            setTheme('dark');
        }
    });

    // --- End Theme Toggle Logic ---

    // Initialize draggable and droppable
    function initializeDragAndDrop() {
        $('#searchResults .image-item').draggable({
            revert: 'invalid', // When dropped outside, revert to original position
            helper: 'clone',   // Drag a clone, not the original element
            cursor: 'move',
            zIndex: 9999,
            containment: '.container', // Confine dragging within the container
            start: function(event, ui) {
                $(this).css('opacity', '0.5'); // Make original item transparent
                if (longClickTimer) {
                    clearTimeout(longClickTimer);
                    longClickTimer = null; // Reset timer ID
                }
            },
            stop: function(event, ui) {
                $(this).css('opacity', '1'); // Restore opacity
            }
        });

        // Droppable setup
        $('#selectedImages').droppable({
            accept: '.image-item',
            drop: function(event, ui) {
                const $draggedItem = ui.draggable;
                addImageToSelected($draggedItem); // Call unified function
            }
        });

        $('.container').off('mousedown', '.image-item'); // Prevent multiple bindings
        $('.container').on('mousedown', '.image-item', function() {
            const $this = $(this);
            const imageId = $this.data('id');

            // Clear any previous timer if a quick click occurred before the previous long-click fired
            clearTimeout(longClickTimer);

            // Start a new timer
            longClickTimer = setTimeout(function() {
                // This simulates a mouseup on the document, which forces jQuery UI to clean up
                // any active drag helper or revert state.
                $('body').trigger('mouseup');

                // This code runs if the mouse button is held down for LONG_CLICK_DELAY
                window.open(`/photo/${imageId}`,'photo'); // Navigate to the photo page
                longClickTimer = null; // Reset timer ID
            }, LONG_CLICK_DELAY);
        });

        // --- NEW: Mouseup handler (to cancel long click for short clicks) ---
        $('.container').off('mouseup', '.image-item'); // Prevent multiple bindings
        $('.container').on('mouseup', '.image-item', function() {
            // If the mouse is released before the long-click timer fires, cancel it
            if (longClickTimer) {
                clearTimeout(longClickTimer);
                longClickTimer = null; // Reset timer ID
            }
        });

        // --- Double-click handler (for selection) ---
        // Double-click will happen even if mousedown/mouseup occurs, but `dblclick` is a distinct event.
        // It's crucial for dblclick to cancel any pending longClickTimer.
	// This remains delegated specifically to #searchResults
        $('#searchResults').off('dblclick', '.image-item'); // Prevent multiple bindings
        $('#searchResults').on('dblclick', '.image-item', function(e) {
            clearTimeout(longClickTimer); // Crucial: Prevent the long-click action from firing
            longClickTimer = null; // Reset timer ID

            // Add image to selected
            addImageToSelected($(this));
            e.preventDefault(); // Prevent default dblclick behavior (e.g., text selection)
        });
    }

    // New unified function to add an image to selected
    function addImageToSelected($imageItem) {
        const imageId = String($imageItem.data('id'));

        if (!selectedImageIds.has(imageId)) {
            // Remove from search results (original item)
            $imageItem.remove();

            // Clone and add to selected images
            const $clonedItem = $imageItem.clone();
            $clonedItem.find('.delete-btn').remove(); // Remove old delete button if any
            $clonedItem.append('<button class="delete-btn">X</button>'); // Add new delete button
            $clonedItem.css('opacity', '1'); // Restore opacity
            $('#selectedImages').prepend($clonedItem); // Add to start of selected images
            selectedImageIds.add(imageId); // Add string ID to Set
            submitSelectedImages([imageId], 'add'); // Submit single added image

	    $('#selectedImages').find('p').remove();

            updateSearchDisplay(); // Re-render search results to ensure consistency
        }
    }

    // Function to render images in a given container
    function renderImages(containerId, images) {
        const $container = $(`#${containerId}`);
	if (containerId === 'searchResults' || images.length > 0)
	        $container.empty(); // Clear existing images

        images.forEach(image => {
            // Only render if the image is not already in the selected list when rendering search results
            if (containerId === 'searchResults' && selectedImageIds.has(String(image.id))) {
                return;
            }
		if (!image.thumbnail)
			image.thumbnail = getGeographUrl(image.id, image.hash, 'small');

            const imageHtml = `
                <div class="image-item" data-id="${image.id}" data-title="${image.title}">
                    <img src="${image.thumbnail}" alt="${image.title}" loading="lazy">
                    <span>${image.title}</span>
                </div>
            `;
            // If rendering selected images, include the delete button from the start
            if (containerId === 'selectedImages') {
                const $item = $(imageHtml);
                $item.append('<button class="delete-btn">X</button>');
                $container.append($item);
            } else {
                $container.append(imageHtml);
            }
        });
        if (images.length && containerId === 'searchResults') {
            if (!$container.find('.image-item').length) { //where results, but none rendered
	        $container.append("<p>All results on this page are already selected.</p>");
	    }
            initializeDragAndDrop(); // Re-initialize draggable for new search results
        }
    }

    // Function to fetch images from the API
    function fetchImages(query, page) {
        if (!query) {
            $('#searchResults').empty().html('<p>Please enter a search query.</p>');
            return;
        }
	let pageLimit = (page-1) * pageSize;
        const apiUrl = `${API_DOMAIN}/api-facetql.php?match=${encodeURIComponent(query)}&select=id,title,hash,realname&offset=${pageLimit}&limit=${pageSize}`;
        console.log(`Fetching: ${apiUrl}`); // For debugging

        $.ajax({
            url: apiUrl,
            method: 'GET',
            success: function(response) {
                if (response && response.rows) {
                    renderImages('searchResults', response.rows);
                    // Update pagination controls
                    const totalFound = parseInt(response.meta.total_found);
                    const totalPages = Math.ceil(parseInt(response.meta.total) / pageSize); //not total_found!

                    $('#resultHeader').text(`Search Results [${totalFound.toLocaleString()} images]`);
                    $('#currentPage').text(`Page ${currentPage}/${totalPages}`);
                    $('#prevPage').prop('disabled', currentPage === 1);
                    $('#nextPage').prop('disabled', currentPage >= totalPages);
                } else {
                    $('#searchResults').html('<p>No results found.</p>');
                    $('#prevPage').prop('disabled', true);
                    $('#nextPage').prop('disabled', true);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error("Error fetching images:", textStatus, errorThrown);
                $('#searchResults').html('<p>Error loading images. Please try again.</p>');
            }
        });
    }

    // New function to fetch initially selected images
    function fetchInitialSelectedImages() {
        const apiUrl = `curator.json.php?tag=${encodeURIComponent(CURRENT_TAG)}&limit=100`;

        console.log(`Fetching initial selected images: ${apiUrl}`);

        $.ajax({
            url: apiUrl,
            method: 'GET',
            success: function(response) {
                if (response && response.rows) {
                    // Populate the Set with IDs of initially selected images
                    response.rows.forEach(image => {
                        selectedImageIds.add(String(image.id)); // Ensure IDs are strings
                    });
                    renderImages('selectedImages', response.rows); // Render them in the right column
                } else {
                    $('#selectedImages').html('<p>No images currently selected for this tag.</p>');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error("Error fetching initial selected images:", textStatus, errorThrown);
                $('#selectedImages').html('<p>Error loading selected images. Please try again.</p>');
            }
        });
    }

    // Function to update the search display based on selected images
    function updateSearchDisplay() {
        // Re-fetch the current query to ensure selected images are removed from results
        if (currentQuery) {
            fetchImages(currentQuery, currentPage);
        }
    }

    // Event listener for search button click
    $('#searchButton').on('click', function() {
        currentQuery = $('#queryInput').val().trim();
        currentPage = 1; // Reset to first page for new search
        fetchImages(currentQuery, currentPage);
    });

    // Event listener for Enter key in query input
    $('#queryInput').on('keypress', function(e) {
        if (e.which === 13) { // Enter key
            $('#searchButton').click();
        }
    });

    // Pagination controls
    $('#prevPage').on('click', function() {
        if (currentPage > 1) {
            currentPage--;
            fetchImages(currentQuery, currentPage);
        }
    });

    $('#nextPage').on('click', function() {
        currentPage++;
        fetchImages(currentQuery, currentPage);
    });

    // Event listener for deleting images from selected list
    $('#selectedImages').on('click', '.delete-btn', function() {
        const $itemToRemove = $(this).closest('.image-item');
        const imageId = String($itemToRemove.data('id'));

        selectedImageIds.delete(imageId);
        $itemToRemove.remove();
        submitSelectedImages([imageId], 'remove'); // Submit single removed image

        // Re-render search results to potentially show the image again
        updateSearchDisplay();
    });

    // Function to submit selected/removed image IDs to the API
    function submitSelectedImages(ids, action) {
        if (ids.length === 0) return;

        const idsParam = ids.join(',');
        let apiUrl = '';
        if (action === 'add') {
            apiUrl = `curator.json.php?add=1&tag=${CURRENT_TAG}&ids=${idsParam}`;
        } else if (action === 'remove') {
            apiUrl = `curator.json.php?remove=1&tag=${CURRENT_TAG}&ids=${idsParam}`;
        } else {
            console.warn("Invalid action for submission.");
            return;
        }

        console.log(`Submitting ${action} request: ${apiUrl}`); // For debugging

        $.ajax({
            url: apiUrl,
	    data: {'confirm':1},
            method: 'POST', // Typically POST for save/remove actions
            success: function(response) {
                console.log(`Successfully ${action}d images:`, ids, response);
                // You might want to add some visual feedback here, e.g., a small success message
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error(`Error ${action}ing images:`, textStatus, errorThrown);
                // Handle error feedback to the user
            }
        });
    }

    // Initial setup: Display the current tag
    $('#currentTag').text(CURRENT_TAG);

    // Initial message in search results
    $('#searchResults').html('<p>Enter a query and click "Search" to find images. Drag to right to select image (or can <b>double</b> click it!)</p>');

    // Create a container for all the instructional text
    const $instructionContainer = $('<div class="search-instructions"></div>');

    const $queryInput = $('#queryInput');
    if ($queryInput.val()) {
        $instructionContainer.append('<p>An example query may be provided (so you can just click "Search"), but you may well need to edit it to get good results.</p>');
    }

    $instructionContainer.append('<p>Try searching beyond the obvious! Think about related themes, objects, or activities.</p>');
    $instructionContainer.append("<p>For example, for [Coastal] images try 'beach', 'lighthouse', 'bucket and spade', or 'seabird' to uncover hidden gems.</p>");
    $instructionContainer.append('<p>Experiment with different keywords to expand the collection!</p>');

    $instructionContainer.append("<p>If can't find any images, or had enough, click link below to switch to new Random tag.</p>");

    // Append the entire instruction block to searchResults
    $('#searchResults').append($instructionContainer);

    // --- NEW: Set focus and cursor position on startup ---
    if ($queryInput.length) { // Check if the element exists
        $queryInput.focus(); // Set focus to the search input

        // Determine the position before " userX"
        const fullQuery = STARTER_QUERY;
        const userKeywordIndex = fullQuery.indexOf(` user${USER_ID}`);
        let cursorPosition = fullQuery.length; // Default to end of string

        if (userKeywordIndex !== -1) {
            cursorPosition = userKeywordIndex; // Set cursor just before the space
            if (cursorPosition < 0) cursorPosition = 0; // Ensure it's not negative
        }

        // Set the cursor position using a native DOM method
        $queryInput[0].setSelectionRange(cursorPosition, cursorPosition);
    }

    // --- IMPORTANT: Call the new function to fetch initial selected images on startup ---
    fetchInitialSelectedImages();
});





function getGeographUrl(gridimage_id, hash, size) {

        yz=zeroFill(Math.floor(gridimage_id/1000000),2);
        ab=zeroFill(Math.floor((gridimage_id%1000000)/10000),2);
        cd=zeroFill(Math.floor((gridimage_id%10000)/100),2);
        abcdef=zeroFill(gridimage_id,6);

        if (yz == '00') {
                fullpath="/photos/"+ab+"/"+cd+"/"+abcdef+"_"+hash;
        } else {
                fullpath="/geophotos/"+yz+"/"+ab+"/"+cd+"/"+abcdef+"_"+hash;
        }

        switch(size) {
                case 'full': return "https://s0.geograph.org.uk"+fullpath+".jpg"; break;
                case 'med': return "https://s"+(gridimage_id%4)+".geograph.org.uk"+fullpath+"_213x160.jpg"; break;
                case 'small':
                default: return "https://s"+(gridimage_id%4)+".geograph.org.uk"+fullpath+"_120x120.jpg";
        }
}

function zeroFill(number, width) {
        width -= number.toString().length;
        if (width > 0) {
                return new Array(width + (/\./.test(number)?2:1)).join('0') + number;
        }
        return number + "";
}

function space_date(datestr) {
    if (datestr && datestr.length == 8)
       return datestr.substring(0,4)+'-'+datestr.substring(4,6)+'-'+datestr.substring(6,8);
    return datestr;
}

