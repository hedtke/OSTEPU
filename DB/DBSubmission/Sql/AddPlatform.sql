<?php
/**
 * @file AddPlatform.sql
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL version 3
 *
 * @package OSTEPU (https://github.com/ostepu/system)
 * @since 0.1.1
 *
 * @author Till Uhlig <till.uhlig@student.uni-halle.de>
 * @date 2014-2015
 */
?>

<?php $profile = '';
    if (isset($profileName) && trim($profileName) !== ''){
        $profile = '_'.$profileName;
    }?>

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';

CREATE TABLE IF NOT EXISTS `Submission<?php echo $profile;?>` (
  `U_id` INT NOT NULL,
  `S_id` INT NOT NULL AUTO_INCREMENT,
  `F_id_file` INT NULL,
  `S_comment` VARCHAR(255) NULL,
  `S_date` INT UNSIGNED NOT NULL DEFAULT 0,
  `S_accepted` TINYINT(1) NOT NULL DEFAULT false,
  `E_id` INT NOT NULL,
  `ES_id` INT NULL,
  `S_flag` TINYINT NOT NULL DEFAULT 1,
  `S_leaderId` INT NULL,
  `S_hideFile` TINYINT NOT NULL DEFAULT 0,
  PRIMARY KEY (`S_id`),
  UNIQUE INDEX `S_id_UNIQUE` USING BTREE (`S_id` ASC),
  INDEX `redundanz5` USING BTREE (`ES_id` ASC, `E_id` ASC),
  CONSTRAINT `fk_Submission<?php echo $profile;?>_Exercise`
    FOREIGN KEY (`E_id`)
    REFERENCES `Exercise<?php echo $exerciseProfile;?>` (`E_id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_Submission<?php echo $profile;?>_User1`
    FOREIGN KEY (`U_id`)
    REFERENCES `User<?php echo $userProfile;?>` (`U_id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_Submission<?php echo $profile;?>_File1`
    FOREIGN KEY (`F_id_file`)
    REFERENCES `File<?php echo $fileProfile;?>` (`F_id`)
    ON DELETE RESTRICT
    ON UPDATE CASCADE,
  CONSTRAINT `redundanz5<?php echo $profile;?>`
    FOREIGN KEY (`ES_id` , `E_id`)
    REFERENCES `Exercise<?php echo $exerciseProfile;?>` (`ES_id` , `E_id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_Submission<?php echo $profile;?>_ExerciseSheet1`
    FOREIGN KEY (`ES_id`)
    REFERENCES `ExerciseSheet<?php echo $exerciseSheetProfile;?>` (`ES_id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
AUTO_INCREMENT = 1;

SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;

ALTER TABLE `Submission<?php echo $profile;?>` MODIFY COLUMN S_comment VARCHAR(255) NULL;
ALTER TABLE `Submission<?php echo $profile;?>` MODIFY COLUMN F_id_file INT NULL;
ALTER TABLE `Submission<?php echo $profile;?>` MODIFY COLUMN S_date INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `Submission<?php echo $profile;?>` MODIFY COLUMN S_flag TINYINT NOT NULL DEFAULT 1;
ALTER TABLE `Submission<?php echo $profile;?>` MODIFY COLUMN S_hideFile TINYINT NOT NULL DEFAULT 0;

DROP TRIGGER IF EXISTS `Submission<?php echo $profile;?>_BINS`;
CREATE TRIGGER `Submission<?php echo $profile;?>_BINS` BEFORE INSERT ON `Submission<?php echo $profile;?>` FOR EACH ROW
<?php
/*check if corresponding exerciseSheet exists
@if not send error message
@author Lisa*/
?>
BEGIN
if (NEW.ES_id is NULL) then
SET NEW.ES_id = (select E.ES_id from `Exercise<?php echo $exerciseProfile;?>` E where E.E_id = NEW.E_id limit 1);
if (NEW.ES_id is NULL) then
SIGNAL sqlstate '23000' set message_text = 'no corresponding exercisesheet';
END if;
END if;

SET NEW.S_leaderId = (SELECT G.U_id_member FROM `Group<?php echo $groupProfile;?>` G WHERE G.U_id_leader = NEW.U_id and G.ES_id = NEW.ES_id limit 1);
if (NEW.S_leaderId is NULL) then
SIGNAL sqlstate '23000' set message_text = 'no corresponding group leader';
END if;
END;

DROP TRIGGER IF EXISTS `Submission<?php echo $profile;?>_BUPD`;
CREATE TRIGGER `Submission<?php echo $profile;?>_BUPD` BEFORE UPDATE ON `Submission<?php echo $profile;?>` FOR EACH ROW
<?php
/*check if corresponding exerciseSheet exists
@if not send error message
@author Lisa*/
?>
BEGIN
if (NEW.ES_id is NULL) then
SET NEW.ES_id = (select E.ES_id from `Exercise<?php echo $exerciseProfile;?>` E where E.E_id = NEW.E_id limit 1);
if (NEW.ES_id is NULL) then
SIGNAL sqlstate '23000' set message_text = 'no corresponding exercisesheet';
END if;
END if;

SET NEW.S_leaderId = (SELECT G.U_id_member FROM `Group<?php echo $groupProfile;?>` G WHERE G.U_id_leader = NEW.U_id and G.ES_id = NEW.ES_id limit 1);
if (NEW.S_leaderId is NULL) then
SIGNAL sqlstate '23000' set message_text = 'no corresponding group leader';
END if;
END;

<?php if (is_dir($sqlPath.'/procedures')) array_map(function ($inp,$sqlPath){if ($inp!='.' && $inp!='..'){include($sqlPath.'/procedures/'.$inp);}},scandir($sqlPath.'/procedures'),array_pad(array(),count(scandir($sqlPath.'/procedures')),$sqlPath));?>
<?php if (is_dir($sqlPath.'/migrations')) array_map(function ($inp,$sqlPath){if ($inp!='.' && $inp!='..'){include($sqlPath.'/migrations/'.$inp);}},scandir($sqlPath.'/migrations'),array_pad(array(),count(scandir($sqlPath.'/migrations')),$sqlPath));?>