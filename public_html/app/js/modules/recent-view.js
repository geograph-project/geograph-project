import { escapeHTML } from '/app/js/utils.js';

export function render() {
    return `
        <div class="view review-view">
            <h2>Review Recent Submissions</h2>
            <div id="review-list" class="review-list">
                <p>Loading submissions for review...</p>
            </div>
        </div>

	<p>Currently just submissions from last 3 days</p>
    `;
}

export async function onMount() {
    const listContainer = document.getElementById('review-list');

    try {
        const response = await fetch('/app/submissions.json.php?thumbs=1');
        const data = await response.json();

        listContainer.innerHTML = data.map(item => `
            <div class="review-item">
                <img src="${item.thumbnail}" alt="Thumbnail">
                <div class="review-fields">
                    <input type="text" value="${escapeHTML(item.title)}" placeholder="Title">
                    <textarea placeholder="No Description">${escapeHTML(item.comment || '')}</textarea>
                    <!--button class="demo-btn" style="width: auto; padding: 5px 15px;">Save Changes</button-->
		    <span>[[[${item.gridimage_id}]]] ${item.grid_reference} - ${item.imagetaken} - ${item.submitted}</span>
                </div>
            </div>
        `).join('');
    } catch (err) {
        listContainer.innerHTML = '<p>Error loading review list.</p>';
    }
}
