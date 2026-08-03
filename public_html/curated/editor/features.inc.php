<?

/**
 * Updates a row in feature_item and logs changes to feature_item_log.
 *
 * @param int $id The feature_item_id to update
 * @param array $updates Key-value array of column => new_value
 * @param int $userId Current logged in user ID
 * @return bool
 */
function updateFeatureItem(int $id, array $updates, int $userId): bool
{
    global $db;

    if (empty($updates)) {
        return false;
    }

    // Fetch existing values to compare changes and get row metadata
    $row = $db->GetRow("SELECT * FROM feature_item WHERE feature_item_id = ?", [$id]);
    if (!$row) {
        return false;
    }

    // Perform UPDATE
    $setClause = '`' . implode('` = ?, `', array_keys($updates)) . '` = ?';
    $db->Execute("UPDATE feature_item SET {$setClause} WHERE feature_item_id = ?", array_merge(array_values($updates), [$id]));

    // Sync point_ll if coordinates changed during update
    if (isset($updates['wgs84_lat']) || isset($updates['wgs84_long'])) {
        $lat = (float)($updates['wgs84_lat'] ?? $row['wgs84_lat']);
        $lng = (float)($updates['wgs84_long'] ?? $row['wgs84_long']);
        $db->Execute("UPDATE feature_item SET point_ll = POINT({$lng}, {$lat}) WHERE feature_item_id = ?", [$id]);
    }

    // Log modified fields
    foreach ($updates as $field => $newValue) {
        $oldValue = $row[$field] ?? null;

        // Only log when value has changed and skip internal exclusions
        if ($newValue != $oldValue && $field !== 'gridimage_id_user_id') {
            $logData = [
                'feature_item_id' => $id,
                'feature_type_id' => $row['feature_type_id'] ?? null,
                'table_id'        => $row['table_id'] ?? null,
                'user_id'         => $userId,
                'field'           => $field,
                'oldvalue'        => $oldValue,
                'newvalue'        => $newValue,
            ];

            $logSet = '`' . implode('` = ?, `', array_keys($logData)) . '` = ?';
            $db->Execute("INSERT INTO feature_item_log SET {$logSet}", array_values($logData));
        }
    }

    return true;
}

#####################################

/**
 * Inserts a new row into feature_item and logs created fields to feature_item_log.
 *
 * @param object $db Database connection object
 * @param array $data Key-value array of column => value for the new row
 * @param int $userId Current logged in user ID
 * @return int|false The new insert ID or false on failure
 */
function insertFeatureItem($db, array $data, int $userId)
{
    if (empty($data)) {
        return false;
    }

    // Ensure spatial point column is set if not provided (spatial columns don't like NULL)
    if (!isset($data['point_ll'])) {
        // Handled via raw SQL clause
    }

    $data['user_id'] = $userId;

    // Construct INSERT query
    $fields = array_keys($data);
    $placeholders = array_fill(0, count($fields), '?');

    $sql = 'INSERT INTO feature_item (`' . implode('`, `', $fields) . '`, `point_ll`) '
         . 'VALUES (' . implode(', ', $placeholders) . ', POINT(0,0))';

    $db->Execute($sql, array_values($data));
    $newId = $db->Insert_ID();

    if ($newId) {
        // Update spatial column if valid coordinates were passed
        // MariaDB/MySQL won't accept bound parameters inside standard Spatial functions like POINT(?, ?) cleanly in older ADOdb setups
        if (isset($data['wgs84_lat'], $data['wgs84_long']) && $data['wgs84_lat'] !== '' && $data['wgs84_long'] !== '') {
            $lat = (float)$data['wgs84_lat'];
            $lng = (float)$data['wgs84_long'];
            $db->Execute("UPDATE feature_item SET point_ll = POINT({$lng}, {$lat}) WHERE feature_item_id = ?", [$newId]);
        }

        // Log newly inserted initial fields
        foreach ($data as $field => $newValue) {
            if ($field !== 'gridimage_id_user_id') {
                $logData = [
                    'feature_item_id' => $newId,
                    'feature_type_id' => $data['feature_type_id'] ?? null,
                    'table_id'        => $data['table_id'] ?? null,
                    'user_id'         => $userId,
                    'field'           => $field,
                    'oldvalue'        => null,
                    'newvalue'        => $newValue,
                ];

                $logSet = '`' . implode('` = ?, `', array_keys($logData)) . '` = ?';
                $db->Execute("INSERT INTO feature_item_log SET {$logSet}", array_values($logData));
            }
        }
    }

    return $newId;
}
