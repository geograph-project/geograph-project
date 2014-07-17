<?php
foreach ($suggestions as $imageclass => $row) {
        //split into array
        if (!empty($row['tags']))
                $suggestions[$imageclass]['tags'] = preg_split('/\s*[,;]\s*/',$row['tags']);

	foreach(range(1,3) as $i)
                if (!empty($row['context'.$i]) && ($row['context'.$i] == 'forum alerted' || $row['context'.$i] =='-bad-') )
                         $suggestions[$imageclass]['context'.$i] = '';

}
if (!empty($delta)) {
	foreach($delta as $row) {
		if (!isset($suggestions[$row['imageclass']]))
			continue;
		if (!empty($approved) && $row['created'] > $approved)
			continue;

		$mod =& $suggestions[$row['imageclass']];
		switch($row['action']) {
			case 'add':
				switch($row['field']) {
					case 'context':
						foreach(range(1,3) as $i) {
							if (empty($mod['context'.$i])) {
								$mod['context'.$i] = $row['value'];
								break;
							}
						}
					break;
					case 'subject':
						$mod['subject'] = $row['value'];
					break;
					case 'tag':
						if (empty($mod['tags'])) $mod['tags'] = array();
						$mod['tags'][] = $row['value'];
					break;
				}
			break;
			case 'remove':
				switch($row['field']) {
					case 'context':
						foreach(range(1,3) as $i) {
							if (!empty($mod['context'.$i]) && $mod['context'.$i] == $row['value']) {
								$mod['context'.$i] = '';
								break;
							}
						}
					break;
					case 'subject':
						if ($mod['subject'] == $row['value'])
							$mod['subject'] = '';
					break;
					case 'tag':
						$idx = array_search($row['value'], $mod['tags']);
						if ($idx !== FALSE)
							unset($mod['tags'][$idx]);
					break;
					case 'canonical':
						if ($mod['canonical'] == $row['value'])
							$mod['canonical'] = '';
					break;
				}
			break;
			case 'checked':
				if (empty($_GET['show']))
					unset($suggestions[$row['imageclass']]);
			break;
		}
	}
}
