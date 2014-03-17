/**
 * @file GetFile.sql
 * gets a specified file from %File table
 * @author Till Uhlig
 * @param int \$fileid a %File identifier
 * @result F_id, F_displayName, F_address, F_timeStamp, F_fileSize, F_hash
 */
 
select 
    F_id,
    F_displayName,
    F_address,
    F_timeStamp,
    F_fileSize,
    F_comment,
    F_hash
from
    File
where
    F_id = '$fileid'