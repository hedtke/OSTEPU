<?php
/**
 * @file GetCourseMarkings.sql
 * gets all specified markings from %Marking table
 * @author Till Uhlig
 * @param int \$cid an %Course identifier
 * @result 
 * - ES, the exercise sheet data
 * - M, the marking data
 * - F, the marking file
 * - S, the submission data
 * - SS, the selected data
 */
?>
 
SELECT 
    M.M_id,
    M.U_id_tutor,
    M.S_id,
    M.M_tutorComment,
    M.M_outstanding,
    M.M_status,
    M.M_points,
    M.M_date,
    M.M_hideFile,
    F.F_id,
    F.F_displayName,
    F.F_address,
    F.F_timeStamp,
    F.F_fileSize,
    F.F_comment,
    F.F_hash,
    F.F_mimeType
    <?php if ($sub==1){ ?>
    ,
    F2.F_id as F_id2,
    F2.F_displayName as F_displayName2,
    F2.F_address as F_address2,
    F2.F_timeStamp as F_timeStamp2,
    F2.F_fileSize as F_fileSize2,
    F2.F_comment as F_comment2,
    F2.F_hash as F_hash2,
    F2.F_mimeType as F_mimeType2,
    S.U_id as U_id2,
    S.S_id as S_id2,
    S.F_id_file as F_id_file2,
    S.S_comment as S_comment2,
    S.S_date as S_date2,
    SS.S_id_selected as S_selected2,
    S.S_accepted as S_accepted2,
    S.S_flag as S_flag2,
    S.S_leaderId as S_leaderId2,
    S.S_hideFile as S_hideFile2,
    S.E_id as E_id2,
    S.ES_id as ES_id2
    <?php } ?>
from
    ExerciseSheet ES
    join
    Marking M ON (ES.ES_id = M.ES_id)
        join
    Submission S ON (M.S_id = S.S_id)
        left join
    SelectedSubmission SS ON (M.S_id = SS.S_id_selected)
        left join
    File F ON (F.F_id = M.F_id_file)
        left join 
    File F2 ON (F2.F_id = S.F_id_file)
where
    ES.C_id = '<?php echo $cid; ?>'