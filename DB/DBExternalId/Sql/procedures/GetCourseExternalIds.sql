<?php
/**
 * @file GetCourseExternalIds.sql
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL version 3
 *
 * @package OSTEPU (https://github.com/ostepu/system)
 * @since 0.3.0
 *
 * @author Till Uhlig <till.uhlig@student.uni-halle.de>
 * @date 2015
 */
?>

DROP PROCEDURE IF EXISTS `DBExternalIdGetCourseExternalIds`;
CREATE PROCEDURE `DBExternalIdGetCourseExternalIds` (IN courseid INT)
READS SQL DATA
begin
SET @s = concat("
select SQL_CACHE
    C.C_id,
    C.C_name,
    C.C_semester,
    C.C_defaultGroupSize,
    EX.EX_id
from
    ExternalId EX left join Course C ON (EX.C_id = C.C_id)
where
    EX.C_id = '",courseid,"';");
PREPARE stmt1 FROM @s;
EXECUTE stmt1;
DEALLOCATE PREPARE stmt1;
end;