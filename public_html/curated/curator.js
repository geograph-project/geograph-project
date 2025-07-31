let currentPage = 1;
let currentQuery = '';
const selectedImageIds = new Set(); // Stores IDs of images currently in the 'Selected Images' list
const rejectedImageIds = new Set(); // NEW: To store rejected image IDs

$(document).ready(function() {
    let pageSize = 20;
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

    const aiEnhancedCheckbox = $('#aiEnhancedCheckbox');
    const AI_ENHANCED_STORAGE_KEY = 'aiEnhancedSearch'; // For remembering state

    // --- Retrieve saved state for AI Enhanced checkbox ---
    const savedAiEnhanced = localStorage.getItem(AI_ENHANCED_STORAGE_KEY);
    if (savedAiEnhanced === 'true' && aiEnhancedCheckbox.is(':visible')) {
        aiEnhancedCheckbox.prop('checked', true);
    }

    // Event listener for AI Enhanced checkbox
    aiEnhancedCheckbox.on('change', function() {
        let useAiEnhanced = $(this).is(':checked');
        localStorage.setItem(AI_ENHANCED_STORAGE_KEY, useAiEnhanced); // Save state

        // If there's a current query, re-run the search with the new setting
        if (currentQuery) { // currentQuery should be initialized from input or STARTER_QUERY
            currentPage = 1; // Reset page to 1 when changing search mode
            fetchImages(currentQuery, currentPage);
        }
    });

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

    // --- NEW: Function to reject an image ---
    function rejectImage($imageItem) {
        const imageId = String($imageItem.data('id'));

        if (!rejectedImageIds.has(imageId)) {
            // Remove from search results (original item)
            $imageItem.remove();
            rejectedImageIds.add(imageId); // Add string ID to Set
            submitRejectedImages([imageId], 'add'); // Submit single rejected image

            // Update search display to potentially adjust 'no results' message
            // or re-fetch to fill the gap (though remove() handles visual removal)
            updateSearchDisplay();
        }
    }

    // New unified function to add an image to selected (remains mostly same, just calling it out)
    function addImageToSelected($imageItem) {
        const imageId = String($imageItem.data('id'));

        if (!selectedImageIds.has(imageId)) {
            // Important: if it was rejected, remove it from rejected before adding to selected
            if (rejectedImageIds.has(imageId)) {
                rejectedImageIds.delete(imageId);
                //submitRejectedImages([imageId], 'remove'); // Tell server it's no longer rejected
			//fornow, no point unrejecting at server, as adding will overwrite status anyway
            }

            $imageItem.remove(); // Remove from search results (original item)
            const $clonedItem = $imageItem.clone();
            $clonedItem.find('.delete-btn').remove(); // Remove old delete/reject buttons if any
            $clonedItem.find('.reject-btn').remove(); // Ensure reject button is gone
            $clonedItem.append('<button class="delete-btn">X</button>'); // Add new delete button
            $clonedItem.css('opacity', '1'); // Restore opacity
            $('#selectedImages').prepend($clonedItem);
            selectedImageIds.add(imageId);
            submitSelectedImages([imageId], 'add');

            updateSearchDisplay();
        }
    }

    // MODIFIED: Function to render images in a given container
    function renderImages(containerId, images) {
        const $container = $(`#${containerId}`);
        if (containerId === 'searchResults' || images.length > 0) {
            $container.empty();
        }

        let renderedCount = 0; // Keep track of how many images are actually rendered

        images.forEach(image => {
            // NEW: Skip rendering if already selected OR rejected
            if (selectedImageIds.has(String(image.id))) {
                return; // Skip rendering
            }
            if (rejectedImageIds.has(String(image.id))) {
                return; // Skip rendering
            }

		if (!image.thumbnail)
			image.thumbnail = getGeographUrl(image.id, image.hash, 'small');

            const imageHtml = `
                <div class="image-item" data-id="${image.id}" data-title="${image.title}">
                    <img src="${image.thumbnail}" alt="${image.title}" loading="lazy">
                    <span>${image.title}</span>
                </div>
            `;

            if (containerId === 'selectedImages') {
                const $item = $(imageHtml);
                $item.append('<button class="delete-btn">X</button>');
                $container.append($item);
                renderedCount++;
            } else if (containerId === 'searchResults') {
                const $item = $(imageHtml);
                $item.prepend('<button class="reject-btn" title="permanently hide this image for this tag">X</button>'); // NEW: Add reject button
                $container.append($item);
                renderedCount++;
            }
        });

        if (containerId === 'searchResults') {
            if (renderedCount === 0 && images.length > 0) { // If original response had items but none rendered
                $container.append("<p>All results on this page are already selected or rejected.</p>");
                //todo, if next page, could auto advance?
            } else if (renderedCount === 0 && images.length === 0) { // If original response had no items
                 $container.append('<p>No results found for this query.</p>');
            }
            initializeDragAndDrop(); // Re-initialize interactions for new elements
        }
    }

    // Function to fetch images from the API
    function fetchImages(query, page) {
        if (!query) {
            $('#searchResults').empty().html('<p>Please enter a search query.</p>');
            return;
        }

	let pageLimit = (page-1) * pageSize;
        let apiUrl = `${API_DOMAIN}/api-facetql.php`;
	let data = {'select': 'id,title,hash,realname'};

	if (query.trim().match(/^(?:\d{6,}|(?:,\d*|\d+,|\d*,\d*)+)$/)) {
		let ids = query.trim().split(',').filter(part => part !== '');
		data['where'] = 'id in ('+ids.join(',')+')';
		data['limit'] = ids.length;

	} else if (m = query.match(/\[\[(\d+)\]\]/g)) {
		let ids = [...query.matchAll(/\[\[(\d+)\]\]/g)].map(m => m[1]);
		data['where'] = 'id in ('+ids.join(',')+')';
		data['limit'] = ids.length;

	} else if (m = query.trim().match(/photo\/(\d+)$/)) {
		data['where'] = 'id='+m[1];

	} else if ($('#aiEnhancedCheckbox').is(':checked')) {
		apiUrl = `${API_DOMAIN}/api-facetql-vector.php`;
		//data['offset'] = pageLimit;
		//data['limit'] = pageSize;
		pageSize = 30; //if getting 30, might as well use thenm rather than having 20+10.
		data['limit'] = 30; //fixed for s3vectors - but need to override the defult mantyciore of 20!
	} else {
		data['match'] = query;
		data['offset'] = pageLimit;
		data['limit'] = pageSize;
	}
	apiUrl += "?"+$.param(data);

        console.log(`Fetching: ${apiUrl}`); // For debugging

        $.ajax({
            url: apiUrl,
            method: 'GET',
            success: function(response) {
                if (response && response.rows) {
                    renderImages('searchResults', response.rows);
                    // Update pagination controls
                    let totalFound, totalPages;
                    if ($('#aiEnhancedCheckbox').is(':checked')) {
                        // KNN queries don't return a total, so we use a reasonable fixed large number
                        //totalFound = 1000; // Use a very large number to indicate "many" results

			totalFound = parseInt(response.meta.total); //actully for now, s3vectors returns only 30. But it could be less if filtering!

                        totalPages = Math.ceil(totalFound / pageSize);
                    } else {
                        totalFound = parseInt(response.meta.total_found);
                        totalPages = Math.ceil(parseInt(response.meta.total) / pageSize); // using total delibveraly, not total_found
                    }
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

    // --- NEW: Function to fetch initially rejected images ---
    function fetchInitialRejectedImages() {
        const apiUrl = `curator.json.php?tag=${encodeURIComponent(CURRENT_TAG)}&limit=100&status=-1`;
        console.log(`Fetching initial rejected images: ${apiUrl}`);

        $.ajax({
            url: apiUrl,
            method: 'GET',
            success: function(response) {
                if (response && response.rows) {
                    response.rows.forEach(image => {
                        rejectedImageIds.add(String(image.id));
                    });
                    console.log("Initial rejected images loaded:", rejectedImageIds);
                    // No need to render them, just keep track
                } else {
                    console.log("No images currently rejected for this tag.");
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error("Error fetching initial rejected images:", textStatus, errorThrown);
                // Handle error: perhaps log or show a temporary message
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
        submitSelectedImages([imageId], 'remove');

        updateSearchDisplay();
    });

    // --- NEW: Event listener for rejecting images from search results ---
    $('#searchResults').on('click', '.reject-btn', function() {
        const $itemToReject = $(this).closest('.image-item');
        rejectImage($itemToReject);
    });

    // --- NEW: Function to submit rejected images to the API ---
    function submitRejectedImages(ids, action) {
        if (ids.length === 0) return;

        const idsParam = ids.join(',');
        let apiUrl = '';
        if (action === 'add') {
            apiUrl = `curator.json.php?reject=1&tag=${CURRENT_TAG}&ids=${idsParam}`;
        } else if (action === 'remove') {
            // This might be used if an image is un-rejected (e.g., added to selected)
            apiUrl = `curator.json.php?unreject=1&tag=${CURRENT_TAG}&ids=${idsParam}`;
        } else {
            console.warn("Invalid action for rejected submission.");
            return;
        }

        console.log(`Submitting ${action} rejected request: ${apiUrl}`);

        $.ajax({
            url: apiUrl,
	    data: {'confirm':1},
            method: 'POST',
            success: function(response) {
                console.log(`Successfully ${action}d rejected images:`, ids, response);
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error(`Error ${action}ing rejected images:`, textStatus, errorThrown);
                // Potentially revert UI state or notify user
            }
        });
    }

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

    // Fetch rejected images FIRST, as they might affect selected or search results
    fetchInitialRejectedImages();

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

