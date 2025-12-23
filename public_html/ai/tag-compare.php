<?php

require_once('geograph/global.inc.php');
init_session();

$smarty = new GeographPage;

	$smarty->display('_std_begin.tpl');

$db = GeographDatabaseConnection(true);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

##########################################

// Handle the Vote Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['gridimage_id'];
    $choice = $_POST['choice']; // 'caption', 'vision', 'tie', or 'both_bad'

    if ($choice !== 'skip')
        $db->Execute("UPDATE joined_tags SET evaluation_result = ? WHERE gridimage_id = ?", [$choice, $id]);

    if (!empty($_GET['id']))
	unset($_GET['id']); //just to make sure a new random one is selected (now a redirect)
}

##########################################

if (!empty($_GET['id'])) {
	$row = $db->GetRow("SELECT * FROM joined_tags WHERE gridimage_id = ".intval($_GET['id']));
	if (!$row) {
	    echo "<h2>Image Not Found</h2>";
	    exit;
	}
} else {
	// Fetch one random row that hasn't been evaluated yet
	$row = $db->GetRow("SELECT gridimage_id FROM joined_tags WHERE evaluation_result IS NULL ORDER BY RAND() LIMIT 1");

	if (!$row) {
	    echo "<h2>All caught up! No more tags to evaluate.</h2>";
	    exit;
	}
        header("Location: " . $_SERVER['PHP_SELF']."?id={$row['gridimage_id']}"); //redirect so the new id is in the URL
	exit;
}

##########################################


//caption is LLM based, so sometimes, messy, clean up when json etc
	$row['caption'] = normalize_tags($row['caption']); //specifically decodes json!

		//our own cleanup code. 
           $list = explode(',', strtolower(preg_replace('/[\r\n]+/','',utf8_to_latin1($row['caption']))));
		$tags = array();
                         foreach($list as $tag) {
                                $tags[] = str_replace('_',' ',trim($tag, '[]", '));
			 };
	$row['caption'] = implode(', ',$tags);

	//this list is much cleaner, but lets add the space
	$row['vision'] = str_replace(',',', ',$row['vision']);

$image = new GridImage($row['gridimage_id']);

// Randomization Logic
// We map 'A' and 'B' to actual model names randomly
$models = ['caption' => $row['caption'], 'vision' => $row['vision']];
$keys = array_keys($models);
shuffle($keys); 

$modelA_name = $keys[0]; // Either 'caption' or 'vision'
$modelB_name = $keys[1]; // The other one
?>
    <style>
        .container { max-width: 800px; margin: auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .image-placeholder { width: 100%; height: 480px; background: #ddd; display: flex; align-items: center; justify-content: center; margin-bottom: 20px; }
        .image-placeholder img { max-height:480px; width: auto; }
        .options { display: flex; gap: 20px; justify-content: center; margin-top: 20px; }
        .tag-box { flex: 1; padding: 15px; border: 1px solid #ccc; border-radius: 5px; background: #fafafa; }
        button { padding: 10px 20px; cursor: pointer; border: none; border-radius: 4px; background: #007bff; color: white; font-weight: bold; }
        button.secondary { background: #6c757d; }
        button.danger { background: #dc3545; }
        .meta { color: #666; font-size: 0.8em; margin-top: 10px; }
    </style>

<div class="container">
    <h2>Which tags describe the image better?</h2>
    
    <div class="image-placeholder">
	<? print $image->getFull(); ?>
    </div>

    <form method="POST">
        <input type="hidden" name="gridimage_id" value="<?= $row['gridimage_id'] ?>">
        
        <div class="options">
            <div class="tag-box">
                <strong>Option A</strong><br>
                <p><?= htmlspecialchars($models[$modelA_name]) ?></p>
                <button type="submit" name="choice" value="<?= $modelA_name ?>">A is Better</button>
            </div>
            
            <div class="tag-box">
                <strong>Option B</strong><br>
                <p><?= htmlspecialchars($models[$modelB_name]) ?></p>
                <button type="submit" name="choice" value="<?= $modelB_name ?>">B is Better</button>
            </div>
        </div>

        <div style="margin-top: 20px;">
            <button type="submit" name="choice" value="tie" class="secondary">It's a Tie</button>
            <button type="submit" name="choice" value="both_bad" class="danger">Both are Bad</button>

            <button type="submit" name="choice" value="skip" style="background: #95a5a6; margin-left: 20px;">
                 Skip (Unsure)
            </button>

	<button type="submit" name="choice" value="broken" 
            style="background: #e67e22; margin-left: 20px;" 
            onclick="return confirm('Flag this record as broken/truncated?')">
        Tag list(s) are Broken</button>

        </div>
    </form>

    <div class="meta">ID: <?= $row['gridimage_id'] ?>, by <? echo htmlentities2($image->realname); ?></div>
</div>

<div class="shortcuts" style="margin-top: 15px; color: #888; font-size: 0.85em; font-style: italic;">
    <strong>Keyboard Shortcuts:</strong> 
    [1] A is Better &bull; 
    [2] B is Better &bull; 
    [3] Tie &bull; 
    [4] Both Bad &bull; 
    [5] Broken &bull; 
    [0] Skip
</div>

<script>
document.addEventListener('keydown', function(event) {
    // Don't trigger if the user is typing in an input/textarea (if you add any later)
    if (event.target.tagName === 'INPUT' || event.target.tagName === 'TEXTAREA') return;

    const map = {
        '1': 'button[value="<?= $modelA_name ?>"]',
        '2': 'button[value="<?= $modelB_name ?>"]',
        '3': 'button[value="tie"]',
        '4': 'button[value="both_bad"]',
        '5': 'button[value="broken"]',
        '0': 'button[value="skip"]'
    };
    
    if (map[event.key]) {
        const btn = document.querySelector(map[event.key]);
        if (btn) {
            // If it's the 'broken' button, we might want to skip the confirm() 
            // when using a keyboard shortcut for speed, or keep it for safety.
            // This triggers the click just like a mouse would.
            btn.click();
        }
    }
});
</script>

<?

	$smarty->display('_std_end.tpl');


// Helper function to normalize tags
function normalize_tags($input) {
    $input = trim($input);
    
    // Check if it starts with { or [ (potential JSON)
    if (strpos($input, '{') === 0) {
        $data = json_decode($input, true);
        
        // If it's valid JSON, flatten it
        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            // This handles both ["tag1", "tag2"] and {"key": "value"}
            return implode(', ', array_values($data));
        }
    }
    
    // If not JSON, return as-is
    return $input;
}
