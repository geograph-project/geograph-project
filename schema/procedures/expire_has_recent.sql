-- This stored procedure updates the 'has_recent' flag on the 'gridsquare' table.
-- It is intended to be run daily as a scheduled event.
--
-- The procedure identifies gridsquares that are currently marked as having recent
-- photos ('has_recent' = 1), but upon recalculation, are found to have no
-- accepted geograph photos taken within the last 5 years. For these squares,
-- the 'has_recent' flag is updated to 0.
--
-- It uses a database lock ('ExpireHasRecent') to prevent concurrent executions.

DELIMITER //

CREATE OR REPLACE PROCEDURE expire_has_recent()
BEGIN
    -- Attempt to get a database lock with a 10-second timeout to ensure
    -- that this procedure does not run concurrently with itself.
    IF GET_LOCK('ExpireHasRecent', 10) THEN

        -- Create a temporary table to store the gridsquare_ids of squares
        -- that are currently marked as 'has_recent' but no longer have any
        -- recent, accepted photos.
        CREATE TEMPORARY TABLE no_recent (PRIMARY KEY (gridsquare_id))
        SELECT
            gridsquare_id,
            -- Recalculate the has_recent flag based on photos from the last 5 years.
            IF(SUM(imagetaken > DATE_SUB(NOW(), INTERVAL 5 YEAR) AND moderation_status = 'geograph') > 0, 1, 0) AS has_recent
        FROM gridsquare
        INNER JOIN gridimage_search USING (grid_reference)
        WHERE has_recent = 1
        GROUP BY grid_reference
        -- Only keep the gridsquares where the new flag is 0.
        HAVING has_recent = 0;

        -- Update the main 'gridsquare' table, setting has_recent to 0 for the
        -- gridsquares identified in the temporary table.
        UPDATE gridsquare
        INNER JOIN no_recent USING (gridsquare_id)
        SET gridsquare.has_recent = no_recent.has_recent;

        -- The temporary table 'no_recent' is automatically dropped at the end of the session.

        -- Release the database lock.
        DO RELEASE_LOCK('ExpireHasRecent');

    END IF;
END//

DELIMITER ;

-- This event schedules the 'expire_has_recent' procedure to run once every day.
-- This replaces the daily cron job that triggered the old PHP event handler.
-- The start time is staggered to prevent it from running at the same time as other daily jobs.
CREATE EVENT IF NOT EXISTS daily_expire_has_recent
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP + INTERVAL 2 HOUR -- Staggered start time
DO
  CALL expire_has_recent();
