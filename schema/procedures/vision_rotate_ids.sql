-- This stored procedure is responsible for maintaining the 'tmp_label_clip' table.
-- This table holds a queue of images that are waiting to be processed by the 'clip'
-- computer vision model.
--
-- The procedure performs two main tasks:
-- 1. It removes entries from 'tmp_label_clip' that have already been processed
--    (i.e., a corresponding entry exists in the 'gridimage_label' table with the 'clip' model).
-- 2. It checks if the number of images remaining in the queue is below a minimum
--    threshold (100,000). If it is, the procedure tops up the queue with new,
--    unprocessed images from the 'gridimage_search' table.
--
-- This procedure is intended to be run periodically by a database event.

DELIMITER //

CREATE OR REPLACE PROCEDURE rotate_vision_ids()
BEGIN
    -- Declare variables
    DECLARE table_exists INT;
    DECLARE row_count INT;
    DECLARE model_name VARCHAR(255) DEFAULT 'clip';
    DECLARE minimum_rows INT DEFAULT 100000;

    -- Check if the temporary table 'tmp_label_clip' exists
    SELECT COUNT(*)
    INTO table_exists
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
    AND table_name = 'tmp_label_clip';

    IF table_exists > 0 THEN
        -- If the table exists, delete entries for images that have already been processed.
        DELETE t FROM tmp_label_clip AS t
        INNER JOIN gridimage_label AS gl ON t.gridimage_id = gl.gridimage_id
        WHERE gl.model = model_name;

        -- Get the current number of rows in the table.
        SELECT COUNT(*) INTO row_count FROM tmp_label_clip;
    ELSE
        -- If the table does not exist, set the row count to 0.
        SET row_count = 0;
    END IF;

    -- If the number of rows is below the minimum threshold, populate the table.
    IF row_count < minimum_rows THEN
        IF table_exists = 0 THEN
            -- If the table does not exist, create it and populate it with new images.
            -- The table structure is inferred from the SELECT statement.
            CREATE TABLE tmp_label_clip AS
            SELECT
                gi.gridimage_id,
                user_id,
                realname,
                width,
                height,
                original_width,
                title,
                grid_reference,
                IF(ii.gridimage_id IS NULL, 0, 1) AS skip_fs
            FROM gridimage_search gi
            INNER JOIN gridimage_size USING (gridimage_id)
            LEFT JOIN gridimage_label l ON (l.gridimage_id = gi.gridimage_id AND l.model = model_name)
            LEFT JOIN images_with_224 ii ON (ii.gridimage_id = gi.gridimage_id)
            WHERE l.gridimage_id IS NULL
            LIMIT minimum_rows;

            -- Add a primary key to the newly created table.
            ALTER TABLE tmp_label_clip ADD PRIMARY KEY (gridimage_id);
        ELSE
            -- If the table already exists, insert new, unprocessed images.
            -- The IGNORE keyword prevents errors if a duplicate gridimage_id is inserted.
            INSERT IGNORE INTO tmp_label_clip
            SELECT
                gi.gridimage_id,
                user_id,
                realname,
                width,
                height,
                original_width,
                title,
                grid_reference,
                IF(ii.gridimage_id IS NULL, 0, 1) AS skip_fs
            FROM gridimage_search gi
            INNER JOIN gridimage_size USING (gridimage_id)
            LEFT JOIN gridimage_label l ON (l.gridimage_id = gi.gridimage_id AND l.model = model_name)
            LEFT JOIN images_with_224 ii ON (ii.gridimage_id = gi.gridimage_id)
            WHERE l.gridimage_id IS NULL
            LIMIT minimum_rows;
        END IF;
    END IF;
END//

DELIMITER ;

-- Create a database event to run the 'rotate_vision_ids' procedure every 6 hours.
-- This replaces the functionality of the original cron job.
-- The event is created only if it does not already exist.
CREATE EVENT IF NOT EXISTS six_hourly_vision_rotate_ids
ON SCHEDULE EVERY 6 HOUR
DO
  CALL rotate_vision_ids();
