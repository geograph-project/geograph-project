import AppState from '/app/js/app-state.js';
import { escapeHTML, navigateTo } from '/app/js/utils.js';

export function render() {
    return `
            <div class="controls">
                <select id="sort-select">
                    <option value="uploaded-desc">Uploaded (Newest)</option>
                    <option value="uploaded-asc">Uploaded (Oldest)</option>
                    <option value="taken-desc">Date Taken (Newest)</option>
                    <option value="taken-asc">Date Taken (Oldest)</option>
                    <option value="grid-asc">Grid Ref (A-Z)</option>
                    <option value="grid-desc">Grid Ref (Z-A)</option>
                    <option value="titie-asc">Title (A-Z)</option>
                    <option value="title-desc">Title (Z-A)</option>
                </select>

                <label id="recent-label">
                    <input type="radio" name="view-filter" value="recent" checked> Last 3 Days <span id="counter"></span>
                </label>
                <label style="margin-left: 15px;">
                    <input type="radio" name="view-filter" value="all" id="all-checkbox"> Last <span id="counter2">100</span> Images
                </label>
            </div>

        <div class="view review-view">
            <div id="review-list" class="review-list">
                <p>Loading submissions for review...</p>
            </div>
        </div>

	<button class="btn btn-primary hidden" id="saveAll-btn" disabled>Apply Changes Now</button>
	<button class="btn btn-secondary hidden" id="clearAll-btn" disabled>Disgard All Changes</button>
    `;
}


const STORAGE_KEY = 'review_form_persistence';

export async function onMount(options) {
    const listContainer = document.getElementById('review-list');

    let link_id = null; //set to image_id to scroll the page
    if (options?.message) {
        try {
            const data = JSON.parse(options.message);
            link_id = data.id;
        } catch (e) {
            console.error("Failed to parse message JSON:", e);
        }
    }
    loadSubmissions(options?.filter || 'recent', link_id);

    // Handle Toggle Changes
    document.querySelectorAll('input[name="view-filter"]').forEach(radio => {
        radio.addEventListener('change', (e) => {
            loadSubmissions(e.target.value);
        });
    });

	//attach once at on whole element
	listContainer.addEventListener('input', (event) => {
	  const target = event.target;

      // 1. Clean up pasted newlines (title is now a textarea to get wrapping!)
      if (target.tagName === 'TEXTAREA' && target.classList.contains('title')) {
        // Regex catches all newline variants
        if (target.value.includes('\n') || target.value.includes('\r')) {
          target.value = target.value.replace(/[\r\n]+/g, ' ');
        }
      }

	  // Check if the element is one we care about
	  if (target.tagName === 'TEXTAREA' || (target.tagName === 'INPUT' && target.type === 'text')) {
	    // Toggle the 'changed' class based on value vs defaultValue
	    target.classList.toggle('changed', target.value !== target.defaultValue);
        if (target.value !== target.defaultValue) {
            target.classList.remove('saved');
            showSaveButton(target.form);
        }
	  }
	});

    listContainer.addEventListener('focusin', (event) => {
	  const target = event.target;
      if (target.tagName === 'TEXTAREA' && window.innerWidth > 300) {
        const img = target.closest('.review-main-row').querySelector('img');
        img.style.position = 'absolute';
        img.style.top = '-126px';
        img.style.left = '10px';
      }
    });
    listContainer.addEventListener('focusout', (event) => {
 	  const target = event.target;
      if (target.tagName === 'TEXTAREA' && window.innerWidth > 300) {
        const img = target.closest('.review-main-row').querySelector('img');
        img.style.position = null;
        img.style.top = null;
        img.style.left = null;
      }
    });

    // also Prevent "Enter" key
    listContainer.addEventListener('keydown', (e) => {
      const target = e.target;
      // Check if it's a textarea and has the 'title' class
      if (target.tagName === 'TEXTAREA' && target.classList.contains('title')) {
        if (e.key === 'Enter') {
          e.preventDefault();

          // Only move focus if it's a touch-based environment
          // ... because of enterkeyhint=next, a enter on mobile should really be a next button.
          if (window.matchMedia("(pointer: coarse)").matches) {
            const formElements = Array.from(listContainer.querySelectorAll('input, textarea, select, button'));
            const index = formElements.indexOf(target);
            if (index > -1 && index < formElements.length - 1) {
              formElements[index + 1].focus();
            }
          }
        }
      }
    });

    // The Save/Delete Logic
    listContainer.addEventListener('focusout', (e) => {
        const target = e.target;
        if (target.tagName === 'TEXTAREA') {
            const name = target.name;
            const currentVal = target.value.trim();
            const defaultVal = target.defaultValue.trim();

            let savedData = getSavedData();

            if (currentVal !== defaultVal) {
                // Value changed: Save it
                savedData[name] = currentVal;
            } else {
                // Value is back to default: Remove it to keep storage clean
                delete savedData[name];
            }

            localStorage.setItem(STORAGE_KEY, JSON.stringify(savedData));
        }
    });

    //document.getElementById('saveAll-btn').onclick (not sure if going to do save all yet!)
    document.getElementById('clearAll-btn').onclick = clearAllEdits;
}

// Helper to get all saved data
function getSavedData() {
    return JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}');
}


function restoreSavedValues(container) {
    const savedData = getSavedData();
    const fields = container.querySelectorAll('textarea[name]');

    fields.forEach(field => {
        const savedValue = savedData[field.name];
        if (savedValue !== undefined) {
            field.value = savedValue;
            if (field.defaultValue != savedValue) {
                field.classList.add('changed');
                showSaveButton(field.form);
            }
        }
    });
}

function showSaveButton(container) {
    const btnSingle = container.querySelector('.save-btn');
    if (btnSingle.disabled) {
        btnSingle.removeAttribute("disabled");
        btnSingle.classList.remove("hidden");
    }
    /*
    const btnAll = document.getElementById('saveAll-btn');
    if (btnAll.disabled) {
        btnAll.removeAttribute("disabled");
        btnAll.classList.add("sticky-button");
        btnAll.classList.remove("hidden");
    }*/
    const btnClear = document.getElementById('clearAll-btn');
    if (btnClear.disabled) {
        btnClear.removeAttribute("disabled");
        btnClear.classList.remove("hidden");
    }
}

function clearAllEdits() {
    // 1. Ask for confirmation (optional but recommended)
    if (!confirm("Are you sure you want to discard all unsaved changes?")) return;

    // 2. Wipe the persistence storage
    localStorage.removeItem(STORAGE_KEY);

    // 3. Revert all fields in the container
    const container = document.getElementById('review-list');
    const fields = container.querySelectorAll('textarea[name]');

    fields.forEach(field => {
        // Reset the value to what was originally in the HTML
        field.value = field.defaultValue;

        // Remove the visual "dirty" markers
        field.classList.remove('changed');
    });


    // 4. Hide the Save button again
    document.querySelector('.save-btn').forEach(btnSingle => {
        btnSingle.setAttribute("disabled", "true");
        btnSingle.classList.add("hidden");
    });
    const btnAll = document.getElementById('saveAll-btn');
    if (btnAll) {
        btnAll.setAttribute("disabled", "true");
        btnAll.classList.add("hidden");
        btnAlln.classList.remove("sticky-button");
    }
}

///////////////////////////////////////////

let currentData = [];

async function loadSubmissions(filter, edit_id = null) {
    const listContainer = document.getElementById('review-list');

    const sortSelect = document.getElementById('sort-select');
    const url = filter === 'all'
        ? '/app/submissions.json.php?thumbs=1&images=100'
        : '/app/submissions.json.php?thumbs=1';

    listContainer.innerHTML = '<p>Loading...</p>';
    sortSelect.value = AppState.getPreference('profileSort', 'uploaded-desc');

    try {
        const response = await fetch(url);
        currentData = await response.json();

        //only show if something to sort!
        sortSelect.classList.toggle('hidden', currentData.length<2);

        if (currentData.length === 0) {
            if (filter == 'recent') {
                //well, if no results!
                document.getElementById('recent-label').style.display='none';
                document.getElementById('all-checkbox').checked = true;
 	         	loadSubmissions('all', edit_id);
                listContainer.innerHTML = '<p>Loading......</p>';
            } else {
                //the user has nothing!
                document.getElementById('counter2').textContent = currentData.length;
                listContainer.innerHTML = '<p>No submissions found.</p>';
     	    }
            return;
        }

        if (filter == 'recent')
            document.getElementById('counter').textContent = `[${currentData.length}]`;
  	    else //might as well make the count accurate, if there are less than 100
		    document.getElementById('counter2').textContent = currentData.length;

        // Render logic
  	    const updateList = () => {
            // 1. Sort based on select value
            const val = sortSelect.value;

            //AppState.setPreference('profileSort', val);

            const sorted = [...currentData].sort((a, b) => {
                if (val === 'uploaded-asc')  return a.gridimage_id - b.gridimage_id;
                if (val === 'uploaded-desc') return b.gridimage_id - a.gridimage_id;
                if (val === 'taken-asc')  return (a.imagetaken || '').localeCompare(b.imagetaken || '');
                if (val === 'taken-desc') return (b.imagetaken || '').localeCompare(a.imagetaken || '');
                if (val === 'grid-asc')  return (a.grid_reference || '').localeCompare(b.grid_reference || '');
                if (val === 'grid-desc') return (b.grid_reference || '').localeCompare(a.grid_reference || '');
                if (val === 'title-asc')  return (a.title || '').localeCompare(b.title || '');
                if (val === 'title-desc') return (b.title || '').localeCompare(a.title || '');
                return 0;
            });

            listContainer.innerHTML = sorted.map(item => `
                <form method="post" action="/editimage.php?id=${item.gridimage_id}" target="_blank" class="review-item" data-gridimage_id="${item.gridimage_id}"
                     onsubmit="this.style.opacity=0.5; this.elements['create'].textContent = 'Edit Page Opened';">
                    <div class="review-main-row" style="position:relative">
                        <img src="${item.thumbnail}" alt="${escapeHTML(item.title)}" loading="lazy">
                        <div class="review-fields">
                            <textarea name="title[${item.gridimage_id}]" class="title" wrap="soft" enterkeyhint="next">${escapeHTML(item.title)}</textarea>
                            <textarea name="comment[${item.gridimage_id}]" class="comment" placeholder="No Description" wrap="soft" enterkeyhint="enter">${escapeHTML(item.comment || '')}</textarea>
                        </div>
                    </div>
                    <div class="meta-info">
             	        <button type=button class=gid>[[${item.gridimage_id}]]</button>
                    	<strong>${item.grid_reference}</strong>
    	                <span>Taken: <strong>${formatTakenDate(item.imagetaken)}</strong></span>
            	        <span>Submitted: <strong>${formatRelativeTime(item.submitted)}</strong></span>
    	            </div>

                    <button class="btn btn-primary save-btn hidden" disabled type="button" style="width: auto; padding: 5px 15px;">Save Changes</button>
                    <button class="btn btn-secondary" type="submit" name="create" value=1 style="width: auto; padding: 5px 15px;">Open Edit Page</button>
                </form>
            `).join('');

	        listContainer.querySelectorAll('button.gid').forEach(btn => {
                btn.onclick = handleDoubleTapCopy;
                btn.oncontextmenu = handleDoubleTapCopy; //to catch if if they kinda like trying to select it.
            });
	        listContainer.querySelectorAll('button.save-btn').forEach(btn => {
                btn.onclick = () => {
                    submitForm(btn.form);
                }
            });

            listContainer.onpaste = resetBatch;
            restoreSavedValues(listContainer);
        };

        updateList(); // Initial render
        sortSelect.onchange = updateList;

        if (edit_id)
            scrollToForm(edit_id);

    } catch (err) {
        listContainer.innerHTML = '<p>Error loading review list.</p>';
    }
}

function scrollToForm(gridImageId) {
  // Find the element with the matching data attribute
  const selector = `form[data-gridimage_id="${gridImageId}"]`;
  const element = document.querySelector(selector);

  if (element) {
    element.scrollIntoView({
      behavior: 'smooth', // Smooth scrolling animation
      block: 'center',    // Vertically center the element
      inline: 'nearest'   // Minimal horizontal movement
    });

    // Apply the highlight
    element.style.backgroundColor = 'lightgreen';
    element.style.transition = 'background-color 0.5s ease'; // Optional: makes the fade-out smooth

    setTimeout(() => {
        element.style.backgroundColor = ''; // Reverts to original CSS
    }, 5000);


  } else {
    console.warn(`Form with gridimage_id "${gridImageId}" not found.`);
  }
}

////////////////////////////////////////////////////////

function formatTakenDate(dateStr) {
    if (dateStr < '1000-01-01')
        return 'unknown';

    const date = new Date(dateStr);
    const now = new Date();

    // Format: Wed 4th Jan
    const options = { weekday: 'short', day: 'numeric', month: 'short' };
    let formatted = date.toLocaleDateString('en-GB', options);

    // Add ordinal suffix (st, nd, rd, th)
    const day = date.getDate();
    const suffix = (day % 10 === 1 && day !== 11) ? 'st' : (day % 10 === 2 && day !== 12) ? 'nd' : (day % 10 === 3 && day !== 13) ? 'rd' : 'th';
    formatted = formatted.replace(day, `${day}<sup>${suffix}</sup>`);

    // Show year if not current year
    if (date.getFullYear() !== now.getFullYear()) {
        formatted += ` ${date.getFullYear()}`;
    }
    return formatted;
}

function formatRelativeTime(dateStr) {
    const past = new Date(dateStr.replace(' ', 'T'));
    const now = new Date();
    const diffInSeconds = Math.floor((now - past) / 1000);

    const days = Math.floor(diffInSeconds / 86400);
    const hours = Math.floor(diffInSeconds / 3600);

    if (days > 0) return `${days} day${days > 1 ? 's' : ''} ago`;
    if (hours > 0) return `${hours} hour${hours > 1 ? 's' : ''} ago`;
    return "Just now";
}

////////////////////////////////////////////////////////

let idQueue = [];

const handleDoubleTapCopy = (event) => {
  const btn = event.currentTarget;
  const textToCopy = btn.textContent;
  const FADE_TIMEOUT = 3000;

  if (event.type === 'contextmenu') {
    event.preventDefault();
  }

  // Function to kill the pending prompt timer
  const clearPendingPrompt = () => {
    if (btn.dataset.timerId) {
      clearTimeout(parseInt(btn.dataset.timerId));
      delete btn.dataset.timerId;
    }
  };

  // Helper to remove existing prompts for this button
  const removePrompt = () => {
    if (btn.dataset.promptId) {
      const oldPrompt = document.getElementById(btn.dataset.promptId);
      if (oldPrompt) oldPrompt.remove();
    }
  };

  if (btn.dataset.state === "primed") {
    // Action: Copy to clipboard
    idQueue.push(textToCopy);

    const finalString = idQueue.join(' ');
    navigator.clipboard.writeText(finalString).then(() => {
      clearPendingPrompt();
      removePrompt();

      showTooltip(btn, idQueue.length==1?"Copied!":`Batch Copied (${idQueue.length} items)`, 1000);
      btn.dataset.state = "idle";
    });
    btn.classList.add('copied'); // Highlight the button

  } else if (idQueue.includes(textToCopy)) {
    showTooltip(btn, "Image ID already copied", 2500);

  } else {
    // Action: Show "Tap again" prompt
    btn.dataset.state = "primed";
    const promptId = "prompt-" + Math.random().toString(36).substr(2, 9);
    btn.dataset.promptId = promptId;

    btn.dataset.timerId = setTimeout(() => {
        const tip = idQueue.length?`Tab again to add to list (${idQueue.length+1} items)`:"Tap again to copy"
        showTooltip(btn, tip, FADE_TIMEOUT, promptId, () => {
          btn.dataset.state = "idle";
        });
    }, 350);
  }
};

const resetBatch = () => {
  idQueue = [];
  // Remove the visual highlight from ALL buttons
  document.querySelectorAll('button.gid').forEach(btn => {
    btn.classList.remove('copied');
    btn.dataset.state = "idle";
  });
};

// Helper function to create and position the message
function showTooltip(anchorEl, message, duration, id = null, onClose = null) {
  const tooltip = document.createElement("div");
  if (id) tooltip.id = id;

  tooltip.textContent = message;

  // Basic Styling
  Object.assign(tooltip.style, {
    position: "absolute",
    backgroundColor: "#333",
    color: "#fff",
    padding: "5px 10px",
    borderRadius: "4px",
    fontSize: "12px",
    pointerEvents: "none",
    zIndex: "1000",
    transition: "opacity 0.3s ease",
    whiteSpace: "nowrap"
  });

  document.body.appendChild(tooltip);

  // Position logic (Centered above the button)
  const rect = anchorEl.getBoundingClientRect();
  const scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;
  const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

  //tooltip.style.left = `${rect.left + scrollLeft + (rect.width / 2) - (tooltip.offsetWidth / 2)}px`;
  tooltip.style.left = `${rect.left + scrollLeft}px`;
  tooltip.style.top = `${rect.top + scrollTop - tooltip.offsetHeight - 12}px`;

  // Fade out and remove
  setTimeout(() => {
    tooltip.style.opacity = "0";
    setTimeout(() => {
      tooltip.remove();
      if (onClose) onClose();
    }, 300);
  }, duration);
}


////////////////////////////////

async function submitForm(form) {
    const formData = new FormData(form);
    const url = "/app/editimage.json.php";
    const saveBtn = form.querySelector('.save-btn');

    try {
        saveBtn.disabled = true; //prevent double clicks!

        const response = await fetch(url, {
            method: 'POST',
            body: formData
        });
        if (!response.ok) {
            throw new Error(`Server responded with ${response.status}`);
        }

        const result = await response.json();

        //this is ok, as long as we know only submitting ONE image at a time!
        const firstEntry = Object.values(result)[0];

        if (firstEntry && firstEntry.status === 'closed') { //it should be immidately commiting!
            let savedData = getSavedData();

            Array.from(form.elements).forEach(input => {
                if (input.classList.contains('changed')) {
                    // Update classes: remove 'changed', add 'saved'
                    input.classList.remove('changed');
                    input.classList.add('saved');

                    input.defaultValue = input.value; //it now saved. so need to track if changed again!

                    // Value is back to default: Remove it to keep storage clean
                    delete savedData[input.name];
                }
            });

            localStorage.setItem(STORAGE_KEY, JSON.stringify(savedData));

            // Handle the save button
            if (saveBtn) {
                saveBtn.disabled = true;
                saveBtn.classList.add('hidden');
                saveBtn.textContent = "Apply Changes"; //just in case became an error
            }
        } else {
            saveBtn.disabled = false;

            saveBtn.textContent = `${firstEntry.error || firstEntry.status || "Unknown Error"}, Try Again`;
        }

    } catch (error) {
        console.error("Save Error:", error);
        saveBtn.disabled = false;
        saveBtn.textContent = `Unknown Error, Try Again`;
    }
}
