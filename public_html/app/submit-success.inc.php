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

</body>
</html>

