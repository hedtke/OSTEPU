<?php
/**
 * @file EditAttachment.sql
 * updates an specified attachment from %Attachment table
 * @author  Till Uhlig
 * @param int \$aid a %Attachment identifier
 * @param string \<?php echo $values; ?> the input data, e.g. 'a=1, b=2'
 * @result -
 */
?>

UPDATE Attachment
SET <?php echo $values; ?>
WHERE A_id = '<?php echo $aid; ?>'