/**
 * @file GetCourseForms.sql
 * gets all forms of a course from %Form table
 * @author Till Uhlig
 * @param int \$courseid an %Course identifier
 * @result 
 * - FO, the form data
 * - CH, the choice data
 */
 
select 
    concat('{$courseid}','_',FO.FO_id) as FO_id,
    FO.FO_type,
    FO.FO_solution,
    FO.FO_task,
    FO.E_id,
    concat('{$courseid}','_',CH.CH_id) as CH_id,
    CH.CH_text,
    CH.CH_correct
from
    `Form_{$courseid}` FO
        left join
    `Choice_{$courseid}` CH ON FO.FO_id = CH.FO_id