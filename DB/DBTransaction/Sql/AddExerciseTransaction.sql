<?php
/**
 * @file AddExerciseTransaction.sql
 * inserts an transaction into %Transaction table
 * @author  Till Uhlig
 * @result -
 */
?>

SET @course = (select E.C_id from `Exercise` E where E.E_id = <?php echo $eid; ?> limit 1);
SET @statement = 
concat("INSERT INTO `Transaction<?php echo $name; ?>_", @course, "` SET <?php echo $object->getInsertData(); ?>,T_random = '<?php echo $random; ?>';");
PREPARE stmt1 FROM @statement;
EXECUTE stmt1;
select @course as 'C_id';