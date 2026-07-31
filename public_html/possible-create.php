<?php
/**
 * $Project: GeoGraph $
 * $Id: faq.php 15 2005-02-16 12:23:35Z lordelph $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Paul Dixon (paul@elphin.com)
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

require_once('geograph/global.inc.php');
init_session();

ini_set('display_errors',1);

pageMustBeHTTPS();

$USER->mustHavePerm('admin');

$smarty = new GeographPage;

$db = GeographDatabaseConnection(false);

// 1. Handle Form Submission
if (!empty($_POST)) {
    // Quick Inline Category Update from Table
    if (isset($_POST['quick_category_id'])) {
        $cat_id = (int)$_POST['quick_category_id'];
        $category = trim($_POST['category']);
        
        $sql = 'UPDATE possible SET category = ? WHERE possible_id = ?';
        $db->Execute($sql, array($category, $cat_id)) or die("$sql\n\n" . $db->ErrorMsg() . "\n");
    } 
    // Main Form Submit (Insert / Full Update)
    else {
        $item_id = !empty($_POST['possible_id']) ? (int)$_POST['possible_id'] : 0;

        $updates = array();
        $updates['enabled']  = 1;
        $updates['user_id']  = $USER->user_id;
        $updates['title']    = trim($_POST['title']);
        $updates['category'] = trim($_POST['category']);
        $updates['content']  = trim($_POST['content']);

        if ($item_id > 0) {
            $setClauses = array();
            foreach (array_keys($updates) as $col) {
                $setClauses[] = "`$col` = ?";
            }
            $params = array_values($updates);
            $params[] = $item_id;

            $sql = 'UPDATE possible SET ' . implode(', ', $setClauses) . ' WHERE possible_id = ?';
            $db->Execute($sql, $params) or die("$sql\n\n" . $db->ErrorMsg() . "\n");
        } else {
            $sql = 'INSERT IGNORE INTO possible SET `' . implode('` = ?,`', array_keys($updates)) . '` = ?';
            $db->Execute($sql, array_values($updates)) or die("$sql\n\n" . $db->ErrorMsg() . "\n");
        }
    }

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// 2. Load Item for Editing
$edit_item = array('possible_id' => 0, 'title' => '', 'category' => '', 'content' => '');
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $row = $db->GetRow('SELECT possible_id, title, category, content FROM possible WHERE possible_id = ?', array($edit_id));
    if ($row) {
        $edit_item = $row;
    }
}

// 3. Fetch Distinct Categories for DataList
$categories = $db->GetCol('SELECT DISTINCT category FROM possible WHERE category != "" AND category IS NOT NULL ORDER BY category ASC');

// 4. Fetch All Items
$items = $db->GetAll('SELECT possible_id, title, category, content, enabled FROM possible ORDER BY possible_id DESC');
?>

<!-- DataList Shared by Main Form and Inline Table Inputs -->
<datalist id="category-list">
    <?php foreach ($categories as $cat): ?>
        <option value="<?= htmlspecialchars($cat, ENT_QUOTES) ?>"></option>
    <?php endforeach; ?>
</datalist>

<!-- Main Editor Form -->
<form method="post" style="padding:10px; background-color:#eee;">
    <input type="hidden" name="possible_id" value="<?= (int)$edit_item['possible_id'] ?>">
    
    <h3><?= $edit_item['possible_id'] ? 'Edit Item #' . (int)$edit_item['possible_id'] : 'Create New Item' ?></h3>
    
    <label>Title:</label><br>
    <input type="text" name="title" value="<?= htmlspecialchars($edit_item['title'], ENT_QUOTES) ?>" maxlength="128" size="80" required><br><br>
    
    <label>Category:</label><br>
    <input type="text" name="category" value="<?= htmlspecialchars($edit_item['category'], ENT_QUOTES) ?>" list="category-list" maxlength="64" size="40"><br><br>

    <label>Content:</label><br>
    <textarea name="content" style="field-sizing: content; min-height: 30px; min-width:800px;" cols="80" rows="15" wrap="soft" required><?= htmlspecialchars($edit_item['content'], ENT_QUOTES) ?></textarea><br><br>
    
    <input type="submit" value="<?= $edit_item['possible_id'] ? 'Update Item' : 'Create Item' ?>">
    <?php if ($edit_item['possible_id']): ?>
        <a href="<?= htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES) ?>" style="margin-left: 10px;">Cancel</a>
    <?php endif; ?>
</form>

<hr style="margin: 20px 0;">

<!-- Existing Items List -->
<h3>Existing Items</h3>
<?php if (!empty($items)): ?>
    <table border="1" cellpadding="6" cellspacing="0" style="border-collapse: collapse; width: 100%; max-width: 1000px;">
        <thead>
            <tr style="background-color: #f2f2f2; text-align: left;">
                <th style="width: 50px;">ID</th>
                <th>Title</th>
                <th style="width: 180px;">Category</th>
                <th>Content Excerpt</th>
                <th style="width: 60px;">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= (int)$item['possible_id'] ?></td>
                    <td><strong><?= htmlspecialchars($item['title'], ENT_QUOTES) ?></strong></td>
                    
                    <!-- Inline Category Form -->
                    <td>
                        <form method="post" style="margin:0; padding:0;">
                            <input type="hidden" name="quick_category_id" value="<?= (int)$item['possible_id'] ?>">
                            <input type="text" 
                                   name="category" 
                                   value="<?= htmlspecialchars($item['category'], ENT_QUOTES) ?>" 
                                   list="category-list" 
                                   style="width: 95%;"
                                   onchange="this.form.submit()">
                        </form>
                    </td>
                    
                    <td><?= htmlspecialchars(mb_strimwidth($item['content'], 0, 60, '...'), ENT_QUOTES) ?></td>
                    <td>
                        <a href="?edit=<?= (int)$item['possible_id'] ?>">Edit</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>No items found.</p>
<?php endif; ?>
