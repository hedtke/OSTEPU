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

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';

CREATE TABLE IF NOT EXISTS `Marking` (
  `M_id` INT NOT NULL AUTO_INCREMENT,
  `U_id_tutor` INT NOT NULL,
  `F_id_file` INT NULL,
  `S_id` INT NOT NULL,
  `M_tutorComment` VARCHAR(255) NULL,
  `M_outstanding` TINYINT(1) NULL DEFAULT 0,
  `M_status` TINYINT NOT NULL DEFAULT 0,
  `M_points` FLOAT NULL DEFAULT 0,
  `M_date` INT UNSIGNED NULL DEFAULT 0,
  `E_id` INT NULL,
  `ES_id` INT NULL,
  `M_hideFile` TINYINT NOT NULL DEFAULT 0,
  PRIMARY KEY (`M_id`),
  UNIQUE INDEX `M_id_UNIQUE` USING BTREE (`M_id` ASC),
  INDEX `redundanz6` USING BTREE (`ES_id` ASC, `E_id` ASC, `S_id` ASC),
  CONSTRAINT `fk_Marking_Submission1`
    FOREIGN KEY (`S_id`)
    REFERENCES `Submission` (`S_id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_Marking_User1`
    FOREIGN KEY (`U_id_tutor`)
    REFERENCES `User` (`U_id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_Marking_File1`
    FOREIGN KEY (`F_id_file`)
    REFERENCES `File` (`F_id`)
    ON DELETE RESTRICT
    ON UPDATE CASCADE,
  CONSTRAINT `redundanz6`
    FOREIGN KEY (`ES_id` , `E_id` , `S_id`)
    REFERENCES `Submission` (`ES_id` , `E_id` , `S_id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_Marking_ExerciseSheet1`
    FOREIGN KEY (`ES_id`)
    REFERENCES `ExerciseSheet` (`ES_id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_Marking_Exercise1`
    FOREIGN KEY (`E_id`)
    REFERENCES `Exercise` (`E_id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
AUTO_INCREMENT = 1;

SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;

/* muss per Hand ausgefuehrt werden */
/*ALTER TABLE `Marking` DROP FOREIGN KEY `fk_Marking_User1`;
ALTER IGNORE TABLE `Marking` ADD CONSTRAINT `fk_Marking_User1` FOREIGN KEY (`U_id_tutor`) REFERENCES `User`(`U_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `Marking` DROP FOREIGN KEY `fk_Marking_Submission1`;
ALTER IGNORE TABLE `Marking` ADD CONSTRAINT `fk_Marking_Submission1` FOREIGN KEY (`S_id`) REFERENCES `Submission`(`S_id`) ON DELETE CASCADE ON UPDATE CASCADE;*/

ALTER TABLE `Marking` MODIFY COLUMN M_points FLOAT NULL DEFAULT 0;
ALTER TABLE `Marking` MODIFY COLUMN M_tutorComment VARCHAR(255) NULL;
ALTER TABLE `Marking` MODIFY COLUMN M_status TINYINT NOT NULL DEFAULT 0;
ALTER TABLE `Marking` MODIFY COLUMN M_hideFile TINYINT NOT NULL DEFAULT 0;
ALTER TABLE `Marking` MODIFY COLUMN M_date INT UNSIGNED NULL DEFAULT 0;

DROP TRIGGER IF EXISTS `Marking_BINS`;
CREATE TRIGGER `Marking_BINS` BEFORE INSERT ON `Marking` FOR EACH ROW
<?php
/*check if corresponding submission exists
@if not send error message
@author Lisa*/
?>
BEGIN
if (NEW.E_id is NULL) then
SET NEW.E_id = (select S.E_id from Submission S where S.S_id = NEW.S_id limit 1);
if (NEW.E_id is NULL) then
SIGNAL sqlstate '45001' set message_text = 'no corresponding submission';
END if;
END if;

if (NEW.ES_id is NULL) then
SET NEW.ES_id = (select S.ES_id from Submission S where S.S_id = NEW.S_id limit 1);
if (NEW.ES_id is NULL) then
SIGNAL sqlstate '45001' set message_text = 'no corresponding submission 2';
END if;
END if;

if (NEW.U_id_tutor = '') then
SET NEW.U_id_tutor = (select S.U_id from Submission S where S.S_id = NEW.S_id limit 1);
if (NEW.U_id_tutor is NULL) then
SIGNAL sqlstate '45001' set message_text = 'no corresponding submission 3';
END if;
END if;
END;

DROP TRIGGER IF EXISTS `Marking_BUPD`;
CREATE TRIGGER `Marking_BUPD` BEFORE UPDATE ON `Marking` FOR EACH ROW
<?php
/*check if corresponding submission exists
@if not send error message
@author Lisa*/
?>
BEGIN
if (NEW.E_id is NULL) then
SET NEW.E_id = (select S.E_id from Submission S where S.S_id = NEW.S_id limit 1);
if (NEW.E_id is NULL) then
SIGNAL sqlstate '45001' set message_text = 'no corresponding submission';
END if;
END if;

if (NEW.ES_id is NULL) then
SET NEW.ES_id = (select S.ES_id from Submission S where S.S_id = NEW.S_id limit 1);
if (NEW.ES_id is NULL) then
SIGNAL sqlstate '45001' set message_text = 'no corresponding submission';
END if;
END if;
END;
<?php if (is_dir($sqlPath.'/procedures')) array_map(function ($inp,$sqlPath){if ($inp!='.' && $inp!='..'){include($sqlPath.'/procedures/'.$inp);}},scandir($sqlPath.'/procedures'),array_pad(array(),count(scandir($sqlPath.'/procedures')),$sqlPath));?>
<?php if (is_dir($sqlPath.'/migrations')) array_map(function ($inp,$sqlPath){if ($inp!='.' && $inp!='..'){include($sqlPath.'/migrations/'.$inp);}},scandir($sqlPath.'/migrations'),array_pad(array(),count(scandir($sqlPath.'/migrations')),$sqlPath));?>