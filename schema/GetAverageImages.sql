-- =========================================================================
-- Function: getAverageImages
-- Description: Calculates the average imagecount for a given spatial area.
--
-- Performance Note: 
-- This exists to bypass a historical MariaDB optimizer limitation. 
-- In standard INNER JOINs or dynamic subqueries, the optimizer fails to 
-- utilize spatial R-tree indexes on dynamically generated geometries.
-- Encapsulating the lookup inside an isolated stored function forces the 
-- engine to treat the input polygon as a constant, safely deploying the 
-- SPATIAL KEY index on `point_xy`.
-- =========================================================================

DELIMITER $$

CREATE FUNCTION `getAverageImages`(p POLYGON) RETURNS FLOAT READS SQL DATA DETERMINISTIC
BEGIN
    DECLARE ret_avg FLOAT;
    
    SET ret_avg = (
        SELECT AVG(imagecount) 
        FROM gridsquare 
        WHERE MBRContains(p, point_xy) 
          AND percent_land > 0
    );
    
    RETURN ret_avg;
END$$

DELIMITER ;
