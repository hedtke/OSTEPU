<?php
/**
 * @file insertTestcase.sql
 * inserts a testcase into %Testcase table
 * @author  Ralf Busch
 * @param string \$values the input data, e.g. 'a=1, b=2'
 * @result -
 */

 $myProcess = Process::decodeProcess($object->getProcess(),false);
 $object->setProcess($myProcess);

?>

SET @course = <?php echo ($object->getProcess()->getObjectCourseFromProcessIdId()!== null ? '\''.$object->getProcess()->getObjectCourseFromProcessIdId().'\'' : "(select E.C_id from `Exercise` E where E.E_id = {$object->getProcess()->getExercise()->getId()} limit 1)"); ?>;

SET @statement = 
concat("INSERT INTO `Testcase<?php echo $pre; ?>_", @course, "` SET <?php echo $object->getInsertData(); ?>;");
PREPARE stmt1 FROM @statement;
EXECUTE stmt1;
select @course as 'C_id';
