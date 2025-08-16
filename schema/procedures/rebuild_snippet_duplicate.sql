-- This stored procedure identifies duplicate snippets and updates their 'has_dup' count.
-- It is designed to be run daily, replacing the old PHP event handler.
--
-- The logic is as follows:
-- 1. It creates a temporary table 'snippet_dup'.
-- 2. It finds enabled snippets and groups them by a normalized title (where all
--    non-alphanumeric characters have been replaced by a space).
-- 3. It populates 'snippet_dup' with the titles and counts of snippets that
--    have more than one entry for the same normalized title.
-- 4. It then updates the 'has_dup' column in the original 'snippet' table for
--    all identified duplicates.

DELIMITER //

CREATE OR REPLACE PROCEDURE rebuild_snippet_duplicate()
BEGIN
    -- Create a temporary table to hold titles of duplicate snippets and their counts.
    -- A unique index is added to the title for performance. The table is automatically
    -- dropped at the end of the session.
    CREATE TEMPORARY TABLE snippet_dup (UNIQUE INDEX (title(255)))
    SELECT
        title,
        COUNT(*) AS has_dup
    FROM snippet
    WHERE enabled = 1
    GROUP BY REGEXP_REPLACE(title, '[^\\w]+', ' ')
    HAVING has_dup > 1;

    -- Update the 'has_dup' column in the 'snippet' table for the found duplicates.
    -- The 'updated = updated' part is a common technique to prevent the 'updated'
    -- timestamp column from being modified by this query.
    UPDATE snippet
    INNER JOIN snippet_dup USING (title)
    SET snippet.has_dup = snippet_dup.has_dup, updated = updated;

END//

DELIMITER ;

-- This event schedules the 'rebuild_snippet_duplicate' procedure to run once every day.
-- This replaces the daily cron job that triggered the old PHP event handler.
-- The start time is staggered by one hour to prevent it from running at the same
-- time as other daily jobs.
CREATE EVENT IF NOT EXISTS daily_rebuild_snippet_duplicate
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP + INTERVAL 1 HOUR
DO
  CALL rebuild_snippet_duplicate();
