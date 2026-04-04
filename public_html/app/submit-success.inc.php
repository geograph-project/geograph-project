<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Submission Status</title>
    <link rel="stylesheet" href="<? echo smarty_modifier_revision('/app/assets/css/style.css'); ?>">
    <style>
        body { padding:10px; text-align: center; }
        .idNum { font-size:2em; font-family: math, sans-serif; }
        .nowrap { white-space: nowrap; }

        ul.stats-list {
            font-weight:bold
        }
    </style>
</head>
<body>

    <h3 align=center>Submission Successful</h3>
    <br>
    <hr>
    <br>
    <p>ID: <a class="idNum" href="https://www.geograph.org.uk/photo/<?= (int)$um->gridimage_id ?>" target="_blank"><?= (int)$um->gridimage_id ?></a> <span class=nowrap>(open photo page in browser)</span></p>

    <?php if ($need_larger): ?>
        <br>
        <b>If you now need to add the full size Panorama</b>:
        <a href="/resubmit.php?id=<?= (int)$um->gridimage_id ?>" target="_blank" class="btn btn-primary">Add Larger Image</a>
        (Opens in browser)<br><br>
    <?php endif; ?>

    <button class="btn btn-primary" onclick="navigateTo('/app/uploaded')">Submit Another</button>

    <button class="btn btn-primary" onclick="navigateTo('/app/upload')">Upload Another</button>

    <a href="/app/" class="btn" target="_top" onclick="navigateTo('/app/home'); return false;">Back To Home</a>

    <script type="module">
	import { navigateTo, updateAppState, setupSettingsListener } from '/app/js/utils.js?<? echo filemtime('js/utils.js'); ?>';

	//so the page can ues it
	window.navigateTo = navigateTo;

        //set this up right away
        setupSettingsListener();

        //now the submission is finished, need to tell the app, there is no longer an active upload_id - none is a special value
        window.addEventListener('DOMContentLoaded', function() {
            updateAppState({upload_id: 'none'});
        });
    </script>

	<? if (!empty($_POST['filename'])) { ?>
		<script src="<?php echo smarty_modifier_revision("/js/Geograph.MediaDatabase.class.js"); ?>"></script>
		<script>
			const dbHistory = new MediaDatabase();
			dbHistory.updateMediaHistory(<? echo json_encode($_POST['filename']); ?>, {
			    status: 'submitted'
			});
		</script>
	<? } ?>

    <div class="widget-container">
        <label>
            <input type="checkbox" id="statsToggle">
            <span id="toggleLabel">Show these stats</span>
        </label>

        <div id="resultsArea">
            <ul id="pointsList" class="stats-list"></ul>
            <p id="loadingMsg" class="status-msg" style="display:none;">Fetching points...</p>
        </div>
    </div>

.    <script>
        const statsToggle = document.getElementById('statsToggle');
        const toggleLabel = document.getElementById('toggleLabel');
        const resultsArea = document.getElementById('resultsArea');
        const pointsList = document.getElementById('pointsList');
        const loadingMsg = document.getElementById('loadingMsg');

        const STORAGE_KEY = 'show_image_stats';
        const API_URL = '/stuff/points.json.php?id=<?= (int)$um->gridimage_id ?>';

        function init() {
            const savedState = localStorage.getItem(STORAGE_KEY);
            // Default to true if never set, otherwise use saved boolean
            const isTicked = savedState === null ? true : savedState === 'true';
            statsToggle.checked = isTicked;
            updateUI(isTicked);
        }

        async function updateUI(isTicked) {
            // Update the label text based on state
            toggleLabel.textContent = isTicked ? 'Show these stats' : 'Show stats for this image';
            pointsList.innerHTML = '';
            resultsArea.classList.toggle('hidden',!isTicked);

            if (isTicked) {
                await fetchData();
            }
        }

        async function fetchData() {
            loadingMsg.style.display = 'block';
            try {
                const response = await fetch(API_URL);
                const data = await response.json();

                if (data.error) throw new Error(`Error: {data.error}`);

                //otherwise it just an array!
                renderPoints(data);
            } catch (error) {
                pointsList.innerHTML = `<li style="color:red;">Error loading stats.</li>`;
                console.error("Fetch error:", error);
            } finally {
                loadingMsg.style.display = 'none';
            }
        }

        function renderPoints(pointsArray) {
            if (!pointsArray || pointsArray.length === 0) {
                pointsList.innerHTML = "<li>No points due</li>";
                return;
            }

            // Create and Insert the Header
            const header = document.createElement('p');
            header.id = 'pointsHeader';
            header.style.marginTop = '10px';
            header.innerHTML = `Provisional Point${pointsArray.length>1?'s':''} for this image:<br>(may change due to moderation)`;
            pointsList.parentNode.insertBefore(header, pointsList);

            pointsList.innerHTML = pointsArray
                .map(point => `<li>${point}</li>`)
                .join('');
        }

        statsToggle.addEventListener('change', (e) => {
            const checked = e.target.checked;
            localStorage.setItem(STORAGE_KEY, checked);
            updateUI(checked);
        });

        init();
    </script>

</body>
</html>

