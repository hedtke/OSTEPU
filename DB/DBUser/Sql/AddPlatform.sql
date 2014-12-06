SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';

CREATE TABLE IF NOT EXISTS `User` (
  `U_id` INT NOT NULL AUTO_INCREMENT,
  `U_username` VARCHAR(120) NOT NULL,
  `U_email` VARCHAR(120) NULL,
  `U_lastName` VARCHAR(120) NULL,
  `U_firstName` VARCHAR(120) NULL,
  `U_title` CHAR(10) NULL,
  `U_password` CHAR(64) NOT NULL,
  `U_flag` SMALLINT NULL DEFAULT 1,
  `U_salt` CHAR(40) NULL,
  `U_failed_logins` INT NULL DEFAULT 0,
  `U_externalId` VARCHAR(255) NULL,
  `U_studentNumber` VARCHAR(120) NULL,
  `U_isSuperAdmin` INT NULL DEFAULT 0,
  `U_comment` VARCHAR(255) NULL,
  PRIMARY KEY (`U_id`),
  UNIQUE INDEX `U_id_UNIQUE` (`U_id` ASC),
  UNIQUE INDEX `U_username_UNIQUE` (`U_username` ASC))
ENGINE = InnoDB
AUTO_INCREMENT = 1;

SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;

DROP TRIGGER IF EXISTS `User_BUPD`;
CREATE TRIGGER `User_BUPD` BEFORE UPDATE ON `User` FOR EACH ROW
<?php
/*delete from user
@just keep id, username and flag
@author Till*/
?>
begin
IF NEW.U_flag = 0 and OLD.U_flag = 1 THEN
SET NEW.U_email = '';
SET NEW.U_lastName = '';
SET NEW.U_firstName = '';
SET NEW.U_title = '';
SET NEW.U_password = '';
SET NEW.U_failed_logins = ' ';
END IF;
end; 

DROP TRIGGER IF EXISTS `User_AUPD`;
CREATE TRIGGER `User_AUPD` AFTER UPDATE ON `User` FOR EACH ROW
<?php
/*if user is inactiv or deleted delete session
@author Lisa Dietrich */
?>
begin
If NEW.U_flag != 1
then delete from `Session` where NEW.U_id = U_id;
end if;
end; 
<?php include $sqlPath.'/procedures/GetUser.sql'; ?>
<?php include $sqlPath.'/procedures/GetUsers.sql'; ?>
<?php include $sqlPath.'/procedures/GetUserByStatus.sql'; ?>
<?php include $sqlPath.'/procedures/GetCourseUserByStatus.sql'; ?>
<?php include $sqlPath.'/procedures/GetGroupMember.sql'; ?>
<?php include $sqlPath.'/procedures/GetCourseMember.sql'; ?>
<?php include $sqlPath.'/procedures/GetIncreaseUserFailedLogin.sql'; ?>
<?php include $sqlPath.'/procedures/GetExistsPlatform.sql'; ?>