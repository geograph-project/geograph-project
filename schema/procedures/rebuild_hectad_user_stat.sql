-- This stored procedure rebuilds the 'hectad_user_stat' table from scratch.
-- This table contains summary statistics of each user's activity within each
-- "hectad" (a 10km x 10km grid area).
-- It is designed to be run daily as a scheduled event.
--
-- The procedure performs the following steps:
-- 1. It creates a temporary table with the same structure as 'hectad_user_stat'.
-- 2. It populates this temporary table by calculating fresh statistics from the
--    'gridsquare' and 'gridimage' tables.
-- 3. It joins with the 'map_origins' table to get coordinate system configuration,
--    rather than using hardcoded values.
-- 4. It atomically replaces the old 'hectad_user_stat' table with the new data
--    by using a DROP and RENAME TABLE operation.

DELIMITER //

CREATE OR REPLACE PROCEDURE rebuild_hectad_user_stat()
BEGIN
    -- Create a temporary table to build the new stats into.
    -- This ensures the old data is available while the new data is being generated.
    CREATE TEMPORARY TABLE hectad_user_stat_tmp LIKE hectad_user_stat;

    -- Insert the newly calculated statistics into the temporary table.
    -- This single query replaces the PHP loop from the original script by joining
    -- with the new 'map_origins' table to handle multiple coordinate systems.
    INSERT INTO hectad_user_stat_tmp
    SELECT
        gs.reference_index,
        -- Derive the hectad identifier string using the 'letterlength' from the map_origins table.
        CONCAT(SUBSTRING(gs.grid_reference, 1, mo.letterlength + 1), SUBSTRING(gs.grid_reference, mo.letterlength + 3, 1)) AS hectad,
        gi.user_id,
        COUNT(gi.gridimage_id) AS images,
        SUM(gi.moderation_status = 'geograph') AS geographs,
        COUNT(DISTINCT gs.gridsquare_id) AS squares,
        COUNT(DISTINCT IF(gi.moderation_status = 'geograph', gs.gridsquare_id, NULL)) AS geosquares,
        COUNT(DISTINCT IF(gs.has_recent = 1, gs.gridsquare_id, NULL)) AS recentsquares,
        SUM(gi.moderation_status = 'geograph' AND gi.ftf = 1) AS firsts,
        MIN(gi.submitted) AS first_submitted,
        MAX(gi.submitted) AS last_submitted,
        MIN(IF(gi.moderation_status = 'geograph' AND gi.ftf = 1, gi.submitted, NULL)) AS first_first_submitted,
        MAX(IF(gi.moderation_status = 'geograph' AND gi.ftf = 1, gi.submitted, NULL)) AS last_first_submitted
    FROM gridsquare gs
    INNER JOIN gridimage gi ON (gs.gridsquare_id = gi.gridsquare_id)
    -- Join with map_origins to get the origin and letterlength for the coordinate system.
    INNER JOIN map_origins mo ON (gs.reference_index = mo.reference_index)
    WHERE gs.percent_land > 0 AND gi.moderation_status IN ('geograph', 'accepted')
    GROUP BY
        -- Group by hectad using the coordinate offsets from the map_origins table.
        (gs.x - mo.origin_x) DIV 10,
        (gs.y - mo.origin_y) DIV 10,
        gi.user_id,
        gs.reference_index;

    -- Atomically replace the old table with the new one.
    DROP TABLE IF EXISTS hectad_user_stat_old;
    RENAME TABLE hectad_user_stat TO hectad_user_stat_old, hectad_user_stat_tmp TO hectad_user_stat;
    DROP TABLE IF EXISTS hectad_user_stat_old;

END//

DELIMITER ;

-- This event schedules the 'rebuild_hectad_user_stat' procedure to run once every day.
CREATE EVENT IF NOT EXISTS daily_rebuild_hectad_user_stat
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP + INTERVAL 4 HOUR -- Staggered start time
DO
  CALL rebuild_hectad_user_stat();
