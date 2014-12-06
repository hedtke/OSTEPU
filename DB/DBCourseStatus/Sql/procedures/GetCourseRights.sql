DROP PROCEDURE IF EXISTS `DBCourseStatusGetCourseRights`;
CREATE PROCEDURE `DBCourseStatusGetCourseRights` (IN courseid varchar(120))
begin
select 
    U.U_id,
    U.U_username,
    U.U_firstName,
    U.U_lastName,
    U.U_email,
    U.U_title,
    U.U_flag,
    U.U_studentNumber,
    U.U_isSuperAdmin,
    U.U_comment,
    CS.CS_status,
    C.C_id,
    C.C_name,
    C.C_semester,
    C.C_defaultGroupSize
from
    CourseStatus CS 
        join
    Course C ON (CS.C_id = C.C_id)
 join
    User U
       ON (U.U_id = CS.U_id)
WHERE
    CS.C_id = courseid;
end;