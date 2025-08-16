-- This stored procedure recalculates and updates summary statistics for each grid prefix.
-- It is intended to be run daily as a scheduled event, replacing the old PHP handler.
--
-- The procedure performs the following steps:
-- 1. It aggregates data from the 'gridsquare' table into a temporary table,
--    calculating the total image count, the number of geograph-covered squares,
--    and the most recent submission timestamp for each grid prefix.
-- 2. It updates the 'gridprefix' table with these new summary statistics.
-- 3. It uses a database lock ('RebuildAGridPrefix') to prevent concurrent executions.

DELIMITER //

CREATE OR REPLACE PROCEDURE rebuild_grid_prefix()
BEGIN
    -- Attempt to get a database lock with a 10-second timeout to ensure
    -- that this procedure does not run concurrently with itself.
    IF GET_LOCK('RebuildAGridPrefix', 10) THEN

        -- Create a temporary table to hold the aggregated statistics for each grid prefix.
        -- The ORDER BY NULL is a performance optimization for MariaDB/MySQL.
        CREATE TEMPORARY TABLE gridprefix_tmp
        SELECT
            SUBSTRING(grid_reference, 1, 3 - reference_index) AS prefix,
            SUM(imagecount) AS imagecount,
            SUM(has_geographs > 0) AS geosquares,
            MAX(last_timestamp) AS last_timestamp
        FROM gridsquare
        WHERE imagecount > 0
        GROUP BY prefix
        ORDER BY NULL;

        -- Update the main 'gridprefix' table with the fresh statistics from the temporary table.
        UPDATE gridprefix
        INNER JOIN gridprefix_tmp USING (prefix)
        SET
            gridprefix.imagecount = gridprefix_tmp.imagecount,
            gridprefix.geosquares = gridprefix_tmp.geosquares,
            gridprefix.last_timestamp = gridprefix_tmp.last_timestamp;

        -- The temporary table is automatically dropped at the end of the session.

        -- Release the database lock.
        DO RELEASE_LOCK('RebuildAGridPrefix');

    END IF;
END//

DELIMITER ;

-- This event schedules the 'rebuild_grid_prefix' procedure to run once every day.
-- This replaces the daily cron job that triggered the old PHP event handler.
-- The start time is staggered to prevent it from running at the same time as other daily jobs.
CREATE EVENT IF NOT EXISTS daily_rebuild_grid_prefix
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP + INTERVAL 3 HOUR -- Staggered start time
DO
  CALL rebuild_grid_prefix();
