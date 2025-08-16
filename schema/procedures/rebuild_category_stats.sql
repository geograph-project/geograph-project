-- @@TEST_METADATA@@
-- TARGET_TABLE: category_stat
-- PRIMARY_KEY: category_id
-- @@END_TEST_METADATA@@
--
-- This stored procedure rebuilds the 'category_stat' table from scratch.
-- This table contains summary statistics for each 'imageclass'.
-- It is designed to be run daily as a scheduled event.
--
-- The procedure performs the following steps:
-- 1. It creates a temporary table 'category_stat_tmp' and populates it with fresh
--    statistics by grouping data from the 'gridimage_search' table.
-- 2. It then atomically replaces the old 'category_stat' table with the temporary table.
-- 3. The entire operation is wrapped in a database lock ('RebuildCategoryStats')
--    to prevent concurrent executions.

DELIMITER //

CREATE OR REPLACE PROCEDURE rebuild_category_stats()
BEGIN
    -- Attempt to get a database lock with a 10-second timeout.
    IF GET_LOCK('RebuildCategoryStats', 10) THEN

        -- Drop the temporary table if it exists from a previous failed run.
        DROP TABLE IF EXISTS category_stat_tmp;

        -- Create and populate the temporary table in a single statement.
        -- This query replicates the logic from the original PHP script.
        CREATE TABLE category_stat_tmp (
            -- Define indexes directly in the CREATE TABLE statement.
            PRIMARY KEY (category_id),
            INDEX (c)
        )
        SELECT
            CRC32(LOWER(imageclass)) AS category_id,
            imageclass,
            COUNT(*) AS c,
            -- Using ANY_VALUE() to be explicit and compatible with modern SQL modes (like ONLY_FULL_GROUP_BY).
            -- This preserves the original script's behavior of selecting a non-deterministic
            -- representative gridimage_id for each category.
            ANY_VALUE(gridimage_id) AS gridimage_id,
            MIN(submitted) AS first,
            MAX(submitted) AS last
        FROM gridimage_search
        WHERE imageclass != ''
        GROUP BY imageclass;

        -- Atomically swap the old table with the new one.
        DROP TABLE IF EXISTS category_stat;
        RENAME TABLE category_stat_tmp TO category_stat;

        -- Release the database lock.
        DO RELEASE_LOCK('RebuildCategoryStats');

    END IF;
END//

DELIMITER ;

-- This event schedules the 'rebuild_category_stats' procedure to run once every day.
-- The start time is staggered to prevent it from running at the same time as other daily jobs.
CREATE EVENT IF NOT EXISTS daily_rebuild_category_stats
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP + INTERVAL 5 HOUR -- Staggered start time
DO
  CALL rebuild_category_stats();
