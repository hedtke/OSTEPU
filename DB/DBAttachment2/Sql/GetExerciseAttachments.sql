/**
 * @file GetExerciseAttachments.sql
 * gets all exerchise attachments from %Attachment table
 * @author Till Uhlig
 * @param int \$eid an %Exercise identifier
 * @result 
 * - A, the attachment data
 * - F, the attachment file
 */

SET @course = (select E.C_id from `Exercise` E where E.E_id = {$eid} limit 1);
SET @statement = 
concat(
\"select 
    concat('\", @course ,\"','_',A.A_id) as A_id,
    A.E_id,
    F.F_id,
    F.F_displayName,
    F.F_address,
    F.F_timeStamp,
    F.F_fileSize,
    F.F_comment,
    F.F_hash
from
    Attachment_{$pre}_\", @course ,\" A
        left join
    File F ON F.F_id = A.F_id
where
    A.E_id = '{$eid}'\");
    
PREPARE stmt1 FROM @statement;
EXECUTE stmt1;