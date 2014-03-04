/**
 * @file GetCourseSubmissions.sql
 * gets the specified submissions from %Submission table
 * @author Till Uhlig
 * @param int \$courseid an %Course identifier
 * @result 
 * - F, the submission file
 * - S, the submission data
 * - SS, the selected data
 */
 
select 
    F.F_id,
    F.F_displayName,
    F.F_address,
    F.F_timeStamp,
    F.F_fileSize,
    F.F_comment,
    F.F_hash,
    S.U_id,
    S.S_id,
    S.F_id_file,
    S.S_comment,
    S.S_date,
    SS.S_id_selected as S_selected,
    S.S_accepted,
    S.S_flag,
    S.S_leaderId,
    S.S_hideFile,
    S.E_id
from
    Exercise E
    join
    Submission S ON (E.E_id = S.E_id)
       left join
    File F ON (S.F_id_file = F.F_id)
        left join
    SelectedSubmission SS ON (S.S_id = SS.S_id_selected
        and S.E_id = SS.E_id)
where
    E.C_id = '$courseid'