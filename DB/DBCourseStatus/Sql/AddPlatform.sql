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

CREATE TABLE IF NOT EXISTS `CourseStatus` (
  `C_id` INT NOT NULL,
  `U_id` INT NOT NULL,
  `CS_status` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`C_id`, `U_id`),
  CONSTRAINT `fk_CourseStatus_Course1`
    FOREIGN KEY (`C_id`)
    REFERENCES `Course` (`C_id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_CourseStatus_User1`
    FOREIGN KEY (`U_id`)
    REFERENCES `User` (`U_id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB;

SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;

ALTER TABLE `CourseStatus` MODIFY COLUMN CS_status TINYINT NOT NULL DEFAULT 0;

DROP TRIGGER IF EXISTS `CourseStatus_AINS`;
CREATE TRIGGER `CourseStatus_AINS` AFTER INSERT ON `CourseStatus` FOR EACH ROW
<?php
/*add group for the new member in this course
@author: Lisa Dietrich */
?>
begin
if NEW.CS_status = 0 then
INSERT IGNORE INTO `Group`
SELECT NEW.U_id , NEW.U_id , null, E.ES_id
FROM ExerciseSheet E
WHERE E.C_id = NEW.C_id;
end if;
end;

DROP TRIGGER IF EXISTS `CourseStatus_AUPD`;
CREATE TRIGGER `CourseStatus_AUPD` AFTER UPDATE ON `CourseStatus` FOR EACH ROW
<?php
/*add group for the new member in this course
@author: Till Uhlig */
?>
begin
if NEW.CS_status = 0 then
INSERT IGNORE INTO `Group`
SELECT NEW.U_id , NEW.U_id , null, E.ES_id
FROM ExerciseSheet E
WHERE E.C_id = NEW.C_id;
end if;
end;

<?php if (is_dir($sqlPath.'/procedures')) array_map(function ($inp,$sqlPath){if ($inp!='.' && $inp!='..'){include($sqlPath.'/procedures/'.$inp);}},scandir($sqlPath.'/procedures'),array_pad(array(),count(scandir($sqlPath.'/procedures')),$sqlPath));?>
<?php if (is_dir($sqlPath.'/migrations')) array_map(function ($inp,$sqlPath){if ($inp!='.' && $inp!='..'){include($sqlPath.'/migrations/'.$inp);}},scandir($sqlPath.'/migrations'),array_pad(array(),count(scandir($sqlPath.'/migrations')),$sqlPath));?>