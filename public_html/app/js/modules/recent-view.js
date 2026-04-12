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

	<button class="btn btn-primary" id="save-btn" disabled>Apply Changes Now</button>
    `;
}

export async function onMount() {
    const listContainer = document.getElementById('review-list');

    loadSubmissions('recent')

    // Handle Toggle Changes
    document.querySelectorAll('input[name="view-filter"]').forEach(radio => {
        radio.addEventListener('change', (e) => {
            loadSubmissions(e.target.value);
        });
    });

	//attach once at on whole element
	listContainer.addEventListener('input', (event) => {
	  const target = event.target;

	  // Check if the element is one we care about
	  if (target.tagName === 'TEXTAREA' || (target.tagName === 'INPUT' && target.type === 'text')) {
	    // Toggle the 'changed' class based on value vs defaultValue
	    target.classList.toggle('changed', target.value !== target.defaultValue);
            if (target.value !== target.defaultValue) {
		const btn = document.getElementById('save-btn');
                btn.removeAttribute("disabled");
		btn.classList.add("sticky-button");
	    }
	  }
	});

}

///////////////////////////////////////////

let currentData = [];

async function loadSubmissions(filter) {
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
 	         	loadSubmissions('all');
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
                <div class="review-item">
                    <div class="review-main-row">
                        <img src="${item.thumbnail}" alt="${escapeHTML(item.title)}" loading="lazy">
                        <div class="review-fields">
                            <input type="text" value="${escapeHTML(item.title)}" placeholder="Title">
                            <textarea placeholder="No Description">${escapeHTML(item.comment || '')}</textarea>
                            <!--button class="demo-btn" style="width: auto; padding: 5px 15px;">Save Changes</button-->
                        </div>
                    </div>
                    <div class="meta-info">
             	        <button type=button class=gid>[[[${item.gridimage_id}]]]</button>
                    	<strong>${item.grid_reference}</strong></strong>
    	                <span>Taken: <strong>${formatTakenDate(item.imagetaken)}</strong></span>
            	        <span>Submitted: <strong>${formatRelativeTime(item.submitted)}</strong></span>
    	            </div>
                </div>
            `).join('');

	            listContainer.querySelectorAll('button.gid').forEach(btn => {
                btn.onclick = handleDoubleTapCopy;
                btn.oncontextmenu = handleDoubleTapCopy; //to catch if if they kinda like trying to select it.
            });

            listContainer.onpaste = resetBatch;
        };

        updateList(); // Initial render
        sortSelect.onchange = updateList;

    } catch (err) {
console.log(err);
        listContainer.innerHTML = '<p>Error loading review list.</p>';
    }
}


function highlightChange(event) {
	const input = event.currentTarget;
	input.classList.toggle('changed', input.value != input.defaultValue);
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
