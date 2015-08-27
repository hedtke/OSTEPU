<?php
/**
 * @file Boilerplate.php
 * Contains common code.
 *
 * @todo Configuration for logic controller uri could go here.
 */

include_once ( dirname(__FILE__) . '/Authorization.php' );
include_once ( dirname(__FILE__) . '/HTMLWrapper.php' );
include_once ( dirname(__FILE__) . '/Template.php' );
include_once ( dirname(__FILE__) . '/../../Assistants/Logger.php' );
include_once ( dirname(__FILE__) . '/Helpers.php' );
include_once ( dirname(__FILE__) . '/../../Assistants/Language.php' );
include_once ( dirname(__FILE__) . '/Config.php' );

$notifications = array();

if (isset($_GET['cid'])) {
    $cid = $_GET['cid'];
} else {
    Logger::Log('no course id!\n');
}

if (isset($_SESSION['UID'])) {
    $uid = $_SESSION['UID'];
} else {
    Logger::Log('no user id!\n');
}

if (isset($_GET['sid'])) {
    $sid = $_GET['sid'];
} else {
    Logger::Log('no sheet id!\n');
}

if (isset($_GET['suid'])) {
    $suid = $_GET['suid'];
} else {
    Logger::Log('no submission id!\n');
}

if (isset($uid)){
    initPage($uid);
}