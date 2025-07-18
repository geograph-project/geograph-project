<?php

function generate_schema_sql($db, $param, $multis, $joineds, $options) {
    $sql = $param['select'];

    $result = $db->Execute("$sql LIMIT 1") or die($db->ErrorMsg());

    $schema_sql = "";

    if (!empty($param['drop'])) {
        if (!empty($param['cluster'])) {
            $schema_sql .= "ALTER CLUSTER {$param['cluster']} DROP {$param['index']};\n";
        }
        $schema_sql .= "DROP TABLE IF EXISTS {$param['index']};\n";
    }

    $schema_sql .= "CREATE TABLE {$param['index']} (";
    $sep = "\n";

    $row =& $result->fields;
    $fields = $result->fieldCount();
    for ($i = 1; $i < $fields; $i++) {
        $r = $result->FetchField($i);
        $name  = $r->name;

        if ($param['debug'] === $name) {
            print_r($r);
        }

        $enc = mb_detect_encoding($row[$name], 'UTF-8, ISO-8859-15, ASCII');
        if ($enc == 'ISO-8859-15') {
            fwrite(STDERR, "\n$name encoding = $enc\n");
        }

        $type = "text";

        if ($name == 'embeddings') {
            $type = "FLOAT_VECTOR knn_type='hnsw' knn_dims='512' hnsw_similarity='COSINE'";
        } elseif (in_array($name, array('east', 'north', 'e', 'n', 'y', 'x', 'nateastings', 'natnorthings', 'geometry_x', 'geometry_y'))) {
            $type = 'bigint';
        } else {
            switch ($r->type) {
                case 'string':
                case 253:
                case 'blob':
                case 252:
                case 'mediumblob':
                case 250:
                case 'longblob':
                case 251:
                    if ($name == 'comment' || $name == 'words' || $name == 'url' || $name == 'tags' || $name == 'label') {
                        $type = 'text';
                    } elseif (preg_match('/s$/', $name) && array_key_exists(preg_replace('/s$/', '_ids', $name), $row)) {
                        $type = 'text';
                    } elseif (preg_match('/_ids$/', $name)) {
                        $type = 'multi';
                    } elseif (preg_match('/_json$/', $name)) {
                        $type = 'json';
                    } elseif ($name == 'user' || $name == 'larger') {
                        $type = 'text indexed';
                    } elseif ($name == 'hash' || $name == 'adm1' || strpos($name, '_lang')) {
                        $type = 'string';
                    } else {
                        $type = 'string attribute indexed';
                    }
                    break;
                case 'binary':
                case 254:
                    if (preg_match('/grlen$/', $name)) {
                        $type = 'bit(3)';
                    } elseif ($name == 'source' && array_key_exists('asource', $row)) {
                        $type = 'text indexed';
                    } elseif ($name == 'adm1' || strpos($name, '_lang')) {
                        $type = 'string';
                    } elseif (empty($r->binary)) {
                        $type = 'string attribute indexed';
                    } else {
                        $type = "TODO-CHECK";
                    }
                    break;
                case 'smallint':
                case 2:
                case 1:
                    if ($name == 'reference_index' || strpos($name, 'has_') === 0) {
                        $type = 'bit(4)';
                    } elseif ($r->unsigned) {
                        $type = 'bit(24)';
                    } else {
                        $type = 'bigint';
                    }
                    break;
                case 'mediumint':
                case 9:
                    if ($name == 'most_detail_view_res') {
                        $type = 'interger';
                    } elseif ($r->unsigned) {
                        $type = 'bit(16)';
                    } else {
                        $type = 'bigint';
                    }
                    break;
                case 'int':
                case 3:
                    if ($name == 'imagecount') {
                        $type = 'integer';
                    } elseif (in_array($name, array('e', 'n', 'gns_ufi')) || !$r->unsigned) {
                        $type = 'bigint';
                    } else {
                        $type = 'integer';
                    }
                    break;
                case 'bigint':
                case 8:
                    if (in_array($name, array('images', 'users', 'geosquares'))) {
                        $type = 'bit(16)';
                    } elseif (in_array($name, array('viewsquare', 'submitted', 'asource', 'updated', 'created', 'last_grouped', 'last_stat'))
                        || $r->unsigned || strpos($name, 'timestamp') !== false
                    ) {
                        $type = 'integer';
                    } else {
                        $type = 'bigint';
                    }
                    break;
                case 'real':
                case 4:
                case 5:
                    $type = "float";
                    break;
                case 'decimal':
                case 246:
                    if ($r->decimals == 0) {
                        $type = 'integer';
                    } else {
                        $type = 'float';
                    }
                    break;
            }
        }
        if ($type) {
            $schema_sql .= "$sep\t`$name` $type";
        }
        $sep = ",\n";
    }
    foreach ($multis as $name => $query) {
        $schema_sql .= "$sep\t`$name` multi";
    }
    foreach ($joineds as $name => $query) {
        $schema_sql .= "$sep\t`$name` text indexed";
    }

    $schema_sql .= ")";

    if (isset($row['dsg'])) {
        $options['min_prefix_len'] = '2';
        $options['prefix_fields'] = 'dsg';
    }

    if (!empty($options)) {
        foreach ($options as $key => $value) {
            $schema_sql .= " $key=" . $db->Quote($value);
        }
    }
    $schema_sql .= ";\n";
    if (!empty($param['cluster'])) {
        $schema_sql .= "ALTER CLUSTER {$param['cluster']} ADD {$param['index']};\n";
    }

    return $schema_sql;
}

function generate_data_sql($db, $param, $multis, $joineds, $file_handle = null) {
    if (!empty($param['cluster'])) {
        $param['index'] = "{$param['cluster']}:{$param['index']}";
    }

    $data_sql = "";
    if (!$file_handle) {
        $data_sql = "";
    }
    $lastid = 0;
    $converted = array();
    while (true) {
        if (preg_match('/ LIMIT (\d+,)?(\d+)\s*$/i', $param['select'], $m)) {
            $postfix = '';
            $limit = intval($m[2]) + 1;
        } elseif (!empty($param['limit'])) {
            $postfix = " LIMIT " . $param['limit'];
            $limit = max(1000, $param['limit']);
        } else {
            $id = "id";
            if (preg_match('/SELECT( SQL_NO_CACHE)? (\w+\.)?(\w+) AS id/i', $param['select'], $m)) {
                $id = $m[3];
            }

            if (preg_match('/\bWHERE\b/i', $param['select'])) {
                $postfix = ($lastid) ? " AND $id > $lastid" : '';
            } else {
                $postfix = ($lastid) ? " WHERE $id > $lastid" : '';
            }
            $postfix .= " ORDER BY $id ASC LIMIT 1000";
            $limit = 1000;
        }

        $result = mysqli_query($db->_connectionID, $param['select'] . $postfix) or die("unable to run {$param['select']}$postfix;\n" . mysqli_error($db->_connectionID) . "\n\n");

        if (!mysqli_num_rows($result)) {
            break;
        }

        fwrite(STDERR, "-- dumping " . mysqli_num_rows($result) . " rows from $lastid\n\n");

        $names = array();
        $types = array();
        $fields = mysqli_fetch_fields($result);

        foreach ($fields as $key => $obj) {
            $names[] = $obj->name;
            if ($obj->name == 'embeddings') {
                $types[] = 'vector';
            } else {
                switch ($obj->type) {
                    case MYSQLI_TYPE_INT24:
                    case MYSQLI_TYPE_LONG:
                    case MYSQLI_TYPE_LONGLONG:
                    case MYSQLI_TYPE_SHORT:
                    case MYSQLI_TYPE_TINY:
                        $types[] = 'int';
                        break;
                    case MYSQLI_TYPE_FLOAT:
                    case MYSQLI_TYPE_DOUBLE:
                    case MYSQLI_TYPE_DECIMAL:
                        $types[] = 'real';
                        break;
                    default:
                        if (preg_match('/_ids$/', $obj->name)) {
                            $types[] = 'mva';
                            break;
                        }
                        $types[] = 'other';
                        break;
                }
            }
        }
        foreach ($multis as $name => $query) {
            $names[] = $name;
        }
        foreach ($joineds as $name => $query) {
            $names[] = $name;
        }

        $insert = "REPLACE INTO {$param['index']} (" . implode(",", $names) . ") VALUES\n";

        $c = 0;
        $rows_for_insert = [];
        while ($row = mysqli_fetch_row($result)) {
            $values = [];
            foreach ($row as $idx => $value) {
                if ($types[$idx] == 'vector') {
                    $list = unpack('f*', $value);
                    $value = "(" . implode(', ', $list) . ")";
                } elseif ($types[$idx] == 'mva') {
                    $value = "(" . mysqli_real_escape_string($db->_connectionID, $value) . ")";
                } elseif (is_null($value)) {
                    $value = "''";
                } elseif ($types[$idx] != 'int' && $types[$idx] != 'real') {
                    $enc = mb_detect_encoding($value, 'UTF-8, ISO-8859-15, ASCII');
                    if ($enc == 'ISO-8859-15' || strpos($value, '&#') !== false) {
                        $value = latin1_to_utf8($value);
                        @$converted[$names[$idx]]++;
                    }
                    $value = "'" . mysqli_real_escape_string($db->_connectionID, $value) . "'";
                }
                $values[] = $value;
            }
            foreach ($multis as $name => $query) {
                $query = str_replace('$id', $row[0], $query);
                $ids = $db->getCol($query);
                $values[] = "(" . implode(',', $ids) . ")";
            }
            foreach ($joineds as $name => $query) {
                $query = str_replace('$id', $row[0], $query);
                $words = $db->getCol($query);
                $values[] = $db->Quote(implode(' ', $words));
            }

            $rows_for_insert[] = "(" . implode(",", $values) . ")";

            if ($param['extended'] && count($rows_for_insert) >= 100) {
                $output = $insert . implode(",\n", $rows_for_insert) . ";\n";
                if ($file_handle) {
                    fwrite($file_handle, $output);
                } else {
                    $data_sql .= $output;
                }
                $rows_for_insert = [];
            }
            $lastid = $row[0];
            $c++;
        }
        if (!empty($rows_for_insert)) {
            $output = $insert . implode(",\n", $rows_for_insert) . ";\n";
            if ($file_handle) {
                fwrite($file_handle, $output);
            } else {
                $data_sql .= $output;
            }
        }

        if (mysqli_num_rows($result) < $limit) {
            break;
        }
    }

    if (!empty($converted)) {
        fwrite(STDERR, print_r($converted, true) . "\n");
    }

    return $data_sql;
}

function generate_select_from_table(&$param, $db, $db_primary, $CONF) {
    $columns = $db->getAssoc("DESCRIBE {$param['table']}");
    $cols = $sheets = $wheres = array();
    if (!empty($param['where'])) {
        $wheres[] = $param['where'];
    }
    foreach ($columns as $name => $row) {
        if ($row['Type'] == 'point') { //its just a index for other cols
            continue;
        }
        if (strpos($name, '_soundex') !== false) { //these are just 'index' cols as well
            continue;
        }
        if (preg_match('/(lat|long)_(deg|min)/', $name) || $name == 'gmt') { //the os gaz has lat/long in weird split format
            continue;
        }
        if ($name == 'e_date' || $name == 'update_co') { //no point indexing the date!
            continue;
        }
        fwrite(STDERR, "$name " . substr($row['Type'], 0, 40) . "\n");

        if ($name == 'enabled' || $name == 'status' || $name == 'approved') {
            //the index should only include enabled rows!
            if ($param['table'] == 'gridimage_tag') {
                $wheres[] = "$name = 2";
            } else {
                $wheres[] = "$name > 0";
            }
        } elseif (preg_match('/sheet_\d/', $name)) {
            $sheets[] = $name;
            //general ints
        } elseif (strpos($row['Type'], 'int(') !== false) {
            if ($row['Key'] == 'PRI') {
                array_unshift($cols, "{$name} AS id");
            } //make sure it first in index, even if not first in table
            else {
                $cols[] = $name;
            } //probably want it as attribute!
        } elseif ($name == 'wgs84_lat' || $name == 'wgs84_long' || $name == 'vlat' || $name == 'vlong') {
            $cols[] = "RADIANS($name) as $name";
        } elseif (preg_match('/(\w+)_utf/', $name, $m)) {
            if (($idx = array_search($m[1], $cols)) !== false) {
                $cols[$idx] = "$name as {$m[1]}";
            } //replace it!
            else {
                $cols[] = "$name as {$m[1]}";
            }
        } elseif ($name == 'grid_reference') {
            $cols[] = $name;
            if (empty($columns['hectad'])) {
                $cols[] = "CONCAT(SUBSTRING(grid_reference,1,LENGTH(grid_reference)-3),SUBSTRING(grid_reference,LENGTH(grid_reference)-1,1)) AS hectad";
            }
            //$cols[] = "SUBSTRING(grid_reference,1,LENGTH(grid_reference)-4) AS myriad";
            //general varchar
        } elseif (preg_match('/char\((\d+)/', $row['Type'], $m) || $row['Type'] == 'text' || preg_match('/enum\(/', $row['Type'], $m)) {
            if ($name == 'id') { //the os_open_names table has a text column called id - shouldnt confuse it with sphinx doc_id
                continue;
            }
            $cols[] = $name;
        } elseif ($row['Type'] == 'datetime' || $row['Type'] == 'timestamp') {
            $cols[] = "UNIX_TIMESTAMP($name) AS $name";
        } elseif ($row['Type'] == 'date') {
            $cols[] = "TO_DAYS($name) AS $name";
        } else {
            fwrite(STDERR, "$name {$row['Type']} unknown\n");
        }
    }
    if (!empty($sheets)) {
        $cols[] = "concat_ws(',',nullif(" . implode(",0),nullif(", $sheets) . ",0)) as sheet_ids";
    }

    $cols = implode(", ", $cols);
    $param['select'] = "SELECT $cols FROM {$param['table']}";
    if ($param['index'] == 'gridimage') { //just looking if still the 'default!
        $param['index'] = $param['table'];
    } //create index of same name

    if (!empty($param['delta'])) {
        $bits = explode('.', $CONF['manticorert_host']);
        $param['date'] = $db_primary->getOne($sql = "SELECT last_indexed FROM sph_server_index WHERE index_name = '{$param['index']}' AND server_id = '{$bits[0]}'");

        if (empty($param['date'])) {
            fwrite(STDERR, "#Warning: unable to find last index date - doing a full dump\n");
            $param['schema'] = 1; //almost certainly going to need schema!
        } else {
            $wheres[] = "{$param['delta']} > " . $db_primary->Quote($param['date']);
        }
    }

    if (!empty($wheres)) {
        $param['select'] .= " WHERE " . implode(' AND ', $wheres);
    }

    fwrite(STDERR, "Generated: {$param['select']};\n");
}

function extract_query_from_conf(&$param, &$options, $cwdir) {
    chdir($cwdir);
    $text = file_get_contents($param['file']);

    //the config file allow 'escaped new lines' - remove them for easier parsing
    $text = str_replace("\\\n", ' ', str_replace("\r", '', $text));

    //this is a very rudimentry file parser, it wont cope with everything!
    //and only one index per file for now!

    if (preg_match_all('/sql_query\s*=(.+?)$/m', $text, $m)) {
        foreach ($m[1] as $query) {
            if (strpos($query, 'sleep(')) { //this is to skip the master query!
                continue;
            }
            $param['select'] = trim(preg_replace('/\s+/', ' ', $query));
            break;
        }
    }

    //todo, if sql_query_range then may need to correct the dynamic variables.

    //we DONT read the sql_attr* etc, just let them autodtect after running the query.

    if (preg_match('/^\s*index\s+(\w+)/m', $text, $m) && $param['index'] == 'gridimage') { //just looking if still the 'default!
        $param['index'] = $m[1];
    }

    if (preg_match('/index\s+' . $param['index'] . '\b(\s*:\s*\w+)?.*?\{(.+?)\}/s', $text, $m)) {
        foreach (explode("\n", $m[2]) as $line) {
            if (preg_match('/^\s*(\w+)\s*=\s*(.+)/', $line, $m) && !in_array($m[1], array('type', 'source', 'path', 'dict'))) {
                $options[$m[1]] = trim($m[2]);
            }
        }
    }
}

function inject_delta_data($table, $index = null, $delta = null, $wheres = array(), $execute = false, $autodelete=false) {
	global $param, $CONF, $db, $rt, $multis, $joineds, $db_primary;

	//use $param, just so code is mostly copy/pastable
	$param['table'] = $table;
	$param['index'] = $index?$index:$table;
	$param['delta'] = $delta;
	$param['execute'] = $execute;

	$table_columns = $db->getAssoc("DESCRIBE {$param['table']}");
	$index_columns = $rt->getAssoc("DESCRIBE {$param['index']}");
	$primary = null;
	if ($table == 'sphinx_view')
		$primary = 'id'; //the view, does show show primary key in DESCRIBE!
	elseif ($table == 'gridprefix')
		$primary = ''; //we dont have a numeric id, so rely on auto-insert-id. REPLAACE INTO fails, so need to delete, see below
	else foreach ($table_columns as $name => $row)
		if (strpos($row['Type'],'int(') !== FALSE)
                        if ($row['Key'] == 'PRI')
				$primary = $name; //todo will be confused if multiple keys. But probably not using this function for them!

	$cols = array();
	foreach ($index_columns as $name => $row) {
		if (!empty($multis[$name]) || !empty($joineds[$name]))
			continue;

		if (isset($table_columns[$name])) {
			$row = $table_columns[$name];
			if ($name == 'enabled' || $name == 'status' || $name == 'approved') {
	                        //the index should only include enabled rows!
				if ($param['table'] == 'gridimage_tag')
                                $wheres[] = "$name = 2";
			        else
		                        $wheres[] = "$name > 0";
	                } elseif ($name == 'wgs84_lat' || $name == 'wgs84_long' || $name == 'vlat' || $name == 'vlong') {
				$cols[] = "RADIANS($name) as $name";
			} elseif ($row['Type'] == 'datetime' || $row['Type'] == 'timestamp') {
				$cols[] = "UNIX_TIMESTAMP($name) AS $name";
		        } elseif ($row['Type'] == 'date') {
	                        $cols[] = "TO_DAYS($name) AS $name";
			} else {
				$cols[] = $name;
			}

		} elseif (isset($table_columns[$name.'_utf'])) {
			if (($idx = array_search($name,$cols)) !== FALSE)
                                $cols[$idx] = "{$name}_utf as {$name}"; //replace it!
                        else
                                $cols[] = "{$name}_utf as {$name}";
		} elseif ($name == 'hectad') {
                        $cols[] = "CONCAT(SUBSTRING(grid_reference,1,LENGTH(grid_reference)-3),SUBSTRING(grid_reference,LENGTH(grid_reference)-1,1)) AS hectad";
		} elseif ($name == 'myriad') {
			$cols[] = "SUBSTRING(grid_reference,1,LENGTH(grid_reference)-4) AS myriad";
		} elseif ($name == 'id' && !empty($primary)) {
			$cols[] = "{$primary} as id";
		} else {
			print "unknown $name";
		}
	}

	#################################################

	$cols = implode(", ",$cols);
        $param['select'] = "SELECT $cols FROM {$param['table']}";

        if (!empty($param['delta'])) {
                $bits = explode('.',$CONF['manticorert_host']);
                $param['date'] = $db_primary->getOne($sql = "SELECT last_indexed FROM sph_server_index WHERE index_name = '{$param['index']}' AND server_id = '{$bits[0]}'");

                if (empty($param['date'])) {
                        fwrite(STDERR,"#Warning: unable to find last index date - aborting replace_into_index()\n");
			return false;
                } else
                        $wheres[] = "{$param['delta']} > ".$db_primary->Quote($param['date']);

		if ($param['debug'])
			fwrite(STDERR,"#IMPORTANT, does not yet delete the old rows\n"); //todo
        }

        if (!empty($wheres))
                $param['select'] .= " WHERE ".implode(' AND ',$wheres);

	#################################################
	// actually fetch the rows

	if (!empty($param['cluster'])) //the inserts need the cluster name (the select query does not!)
		$param['index'] = "{$param['cluster']}:{$param['index']}";

	if ($param['debug'])
		print_r($param);

    $result = $db->Execute($param['select']);
    if (!$result) {
        die("unable to run {$param['select']}\n" . $db->ErrorMsg() . "\n\n");
    }

	if ($result->RecordCount() > $param['limit'])
		die("too many rows! ".$result->RecordCount()."\n");

	if ($result->RecordCount()) {
		//if there is a row, we first need to delete
		if (!empty($wheres) && $autodelete) { //where needs ot be compatible with mysql AND manticore!!
			$delete = "DELETE FROM {$param['index']} WHERE ".implode(' AND ',$wheres);
                        if (!empty($param['execute'])) {
                                $rt->Execute($delete);
                        } else {
				fwrite(STDERR,"$delete;\n");
			}
		}

		$names=array();
		$types=array();
        for ($i = 0; $i < $result->FieldCount(); $i++) {
            $field = $result->FetchField($i);
            $names[] = $field->name;
            switch ($field->type) {
                case 'int':
                case 'real':
                case 'mva':
                    $types[] = $field->type;
                    break;
                default:
                    if (preg_match('/_ids$/', $field->name)) { //dont have a better way at the moment!
                        $types[] = 'mva';
                        break;
                    }
                    $types[] = 'other';
                    break; //we dont actully care about the exact type, other than knowing numeric
            }
        }
		foreach($multis as $name => $query)
			$names[] = $name;
		foreach($joineds as $name => $query)
			$names[] = $name;

		//really need to always do 'complete' inserts, as manticore expects columns in same order as EXPLAIN, which is typically diffent to create table, eg all fields first. Also 'string attribute index' then need inserting twice, by naming columns dont need to duplicate!
		$insert = "REPLACE INTO {$param['index']} (".implode(",",$names).") VALUES\n";

		$c=0; $buffer = '';
        while (!$result->EOF) {
            $row = array_values($result->fields);
		        if ($param['extended'] && $c%100) { //ideally should work on length of line, but just number of lines. (so each insert fits in one packet (16M?)
		                $sep = "),\n(";
		        } elseif ($c) {
				if ($buffer && !empty($param['execute'])) {
					$buffer .= ")";
					if ($param['debug'] === '2')
						print "$buffer;\n";
					$rt->Execute($buffer);
					if ($param['debug'])
						 fwrite(STDERR,"affected: ".$rt->Affected_Rows()."\n");
				} else {
					fwrite(STDERR,"$buffer;\n");
				}
				$buffer = '';
		                $sep = "$insert(";
		        } else {
		                $sep = "$insert(";
		        }
			foreach($row as $idx => $value) {
				if ($types[$idx] == 'mva') //mva's need special treatment if importing into index
					$value = "(".$db->escape($value).")";
				elseif (is_null($value))
					$value = "''"; //doesnt support null!
				elseif ($types[$idx] != 'int' && $types[$idx] != 'real') { //Don't just use 'is_numeric', as inserting a number into string attribute, silently fails!
					$enc = mb_detect_encoding($value, 'UTF-8, ISO-8859-15, ASCII');
					if ($enc == 'ISO-8859-15' || strpos($value,'&#')!==FALSE) //dont just blindly convert, as while MOST columns in database are latin1, not quite all!
						$value = latin1_to_utf8($value);
					$value = $db->Quote($value);
				}
				$buffer .= "$sep$value";
				$sep = ',';
			}
			foreach($multis as $name => $query) {
				$query = str_replace('$id',$row[0],$query);
				$ids = $db->getCol($query);
				$buffer .= "$sep(".implode(',',$ids).")";
			}
			foreach($joineds as $name => $query) {
				$query = str_replace('$id',$row[0],$query);
				$words = $db->getCol($query);
				$buffer .= "$sep".$db->Quote(implode(' ',$words));
			}
			$c++; //ideally should work on length of line,
		}
		$buffer .= ")";
		if ($buffer && !empty($param['execute'])) {
			if ($param['debug'] === '2')
				print "$buffer;\n";
			$rt->Execute($buffer);
			if ($param['debug'])
				 fwrite(STDERR,"affected: ".$rt->Affected_Rows()."\n");
		} else {
			fwrite(STDERR,"$buffer;\n");
		}
	}

	#################################################
	//update the last_indexed, note will update the 'updated' even if there are no rows above :)

	if (!empty($param['delta']) && !empty($param['table'])) {
        $sql = update_last_indexed_sql($db, $db_primary, $param, $CONF);
		if ($param['execute'] > 1)
			$db_primary->Execute($sql);
		else
			fwrite(STDERR, "\nRun this on PRIMARY database: $sql;\n");
	}
}

function update_last_indexed_sql($db, $db_primary, $param, $CONF) {
    $row = $db->getRow("SELECT {$param['delta']} FROM {$param['table']} ORDER BY {$param['delta']} desc LIMIT 1");

    $bits = explode('.', $CONF['manticorert_host']);
    $sql = "REPLACE INTO sph_server_index SET index_name = '{$param['index']}', server_id = '{$bits[0]}', last_indexed = '{$row[$param['delta']]}', updated=NOW()";

    return $sql;
}
