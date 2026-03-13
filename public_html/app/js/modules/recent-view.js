import { escapeHTML } from '/app/js/utils.js';

export function render() {
    return `
        <div class="view review-view">
            <div id="review-list" class="review-list">
                <p>Loading submissions for review...</p>
            </div>
        </div>

    <button class="btn btn-primary" disabled>Save Edits</button>
    (doesn't save yet!)

	<p>Currently just submissions from last 3 days</p>
    `;
}

function formatTakenDate(dateStr) {
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


export async function onMount() {
    const listContainer = document.getElementById('review-list');

    try {
        const response = await fetch('/app/submissions.json.php?thumbs=1');
        const data = await response.json();

        listContainer.innerHTML = data.map(item => `
            <div class="review-item">
                <div class="review-main-row">
                    <img src="${item.thumbnail}" alt="Thumbnail">
                    <div class="review-fields">
                        <input type="text" value="${escapeHTML(item.title)}" placeholder="Title">
                        <textarea placeholder="No Description">${escapeHTML(item.comment || '')}</textarea>
                        <!--button class="demo-btn" style="width: auto; padding: 5px 15px;">Save Changes</button-->
                    </div>
                </div>
                <div class="meta-info">
         	        [[[${item.gridimage_id}]]]
                	<strong>${item.grid_reference}</strong></strong>
	                Taken: <strong>${formatTakenDate(item.imagetaken)}</strong>
        	        Submitted: <strong>${formatRelativeTime(item.submitted)}</strong>
	            </div>
            </div>
        `).join('');
    } catch (err) {
        listContainer.innerHTML = '<p>Error loading review list.</p>';
    }
}
