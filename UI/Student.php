<?php
/**
 * @file Student.php
 * Constructs the page that is displayed to a student.
 *
 * @author Felix Schmidt
 * @author Florian Lücke
 * @author Ralf Busch
 */
ob_start();

include_once dirname(__FILE__) . '/include/Boilerplate.php';
include_once dirname(__FILE__) . '/../Assistants/Structures.php';
include_once dirname(__FILE__) . '/../Assistants/Validation/Validation.php';

global $globalUserData;
Authentication::checkRights(PRIVILEGE_LEVEL::STUDENT, $cid, $uid, $globalUserData);

$langTemplate='Student_Controller';Language::loadLanguageFile('de', $langTemplate, 'json', dirname(__FILE__).'/');

$selectedUser = $uid;
$privileged = 0;
if (Authentication::checkRight(PRIVILEGE_LEVEL::LECTURER, $cid, $uid, $globalUserData)){
    if (isset($_POST['selectedUser'])){
        $URI = $serverURI . "/DB/DBUser/user/course/{$cid}/status/0";
        $courseUser = http_get($URI, true);
        $courseUser = User::decodeUser($courseUser);

        $correct = false;
        foreach ($courseUser as $user){
            if ($user->getId() == $_POST['selectedUser']){
                $correct = true;
                break;
            }
        }

        if ($correct){
            $_SESSION['selectedUser'] = $_POST['selectedUser'];
        }
    } elseif (!isset($_SESSION['selectedUser'])) {
        $_SESSION['selectedUser'] = $uid;
    }
    $selectedUser = isset($_SESSION['selectedUser']) ? $_SESSION['selectedUser'] : $uid;

    if (isset($_POST['privileged'])){
        $_SESSION['privileged'] = $_POST['privileged'];
    }
    $privileged = (isset($_SESSION['privileged']) ? $_SESSION['privileged'] : $privileged);
}

$postValidation = Validation::open($_POST, array('preRules'=>array('sanitize')))
  ->addSet('deleteSubmissionWarning',
           ['set_default'=>null,
            'valid_identifier',
            'satisfy_not_equals_field'=>'deleteSubmission',
            'on_error'=>['type'=>'error',
                         'text'=>Language::Get('main','invalidDeleteSubmissionWarning', $langTemplate)]])
  ->addSet('deleteSubmission',
           ['set_default'=>null,
            'valid_identifier',
            'satisfy_not_equals_field'=>'deleteSubmissionWarning',
            'on_error'=>['type'=>'error',
                         'text'=>Language::Get('main','invalidDeleteSubmission', $langTemplate)]])
  ->addSet('downloadMarkings',
           ['set_default'=>null,
            'valid_identifier',
            'on_error'=>['type'=>'error',
                         'text'=>Language::Get('main','invalidSheetId', $langTemplate)]]);

$postResults = $postValidation->validate();
$notifications = array_merge($notifications,$postValidation->getPrintableNotifications('MakeNotification'));
$postValidation->resetNotifications()->resetErrors();

if (isset($postResults['deleteSubmissionWarning'])) {
    $notifications[] = MakeNotification('warning', Language::Get('main','askDeleteSubmission', $langTemplate));
} elseif (isset($postResults['deleteSubmission'])) {
    $suid = $postResults['deleteSubmission'];

    // extractes the studentId of the submission
    $URI = $databaseURI . '/submission/' . $suid;
    $submission = http_get($URI, true);                 
    $submission = json_decode($submission, true);

    // only deletes the submission if it belongs to the user
    if ($submission['studentId'] === $selectedUser) {
        // setzt den Zeiger für die ausgewählte Einsendung zurück
        $URI = $databaseURI . '/selectedsubmission/submission/' . $suid;
        http_delete($URI, true, $message);
        
        if ($message === 201) {
            // markiert die Einsendung als "gelöscht", sie wird hierbei nicht wirklich
            // aus der Datenbank entfernt, sondern nur verborgen
            $submissionUpdate = Submission::createSubmission($suid,null,null,null,null,null,null,0);
            $URI = $databaseURI . '/submission/submission/' . $suid;
            http_put_data($URI, Submission::encodeSubmission($submissionUpdate), true, $message);

            if ($message === 201) {
                $notifications[] = MakeNotification('success', Language::Get('main','successDeleteSubmission', $langTemplate));
            } else {
                $notifications[] = MakeNotification('error', Language::Get('main','errorDeleteSubmission', $langTemplate));
            }
        } else {
            $notifications[] = MakeNotification('error', Language::Get('main','errorDeleteSubmission', $langTemplate));
        }
    }

} elseif (isset($postResults['downloadMarkings'])) {
    downloadMarkingsForSheet($selectedUser, $postResults['downloadMarkings']);
}

// load tutor data from GetSite
$URI = $getSiteURI . "/student/user/{$selectedUser}/course/{$cid}";
$student_data = http_get($URI, true);
$student_data = json_decode($student_data, true);
$student_data['filesystemURI'] = $filesystemURI;
$student_data['cid'] = $cid;
$student_data['uid'] = $selectedUser;
$user_course_data = $student_data['user'];

$menu = MakeNavigationElement($user_course_data,
                              PRIVILEGE_LEVEL::STUDENT);

$userNavigation = null;
if (isset($_SESSION['selectedUser'])){
    $courseStatus = null;
    if (isset($globalUserData['courses'][0]) && isset($globalUserData['courses'][0]['status']))
        $courseStatus = $globalUserData['courses'][0]['status'];
    
    $URI = $serverURI . "/DB/DBUser/user/course/{$cid}/status/0";
    $courseUser = http_get($URI, true);
    $courseUser = User::decodeUser($courseUser);
    $userNavigation = MakeUserNavigationElement($globalUserData,
                                                $courseUser,
                                                $privileged,
                                                PRIVILEGE_LEVEL::LECTURER,
                                                null,
                                                null,
                                                false,
                                                false,
                                                array('page/admin/studentMode','studentMode.md'),
                                                array(array('title'=>Language::Get('main','leaveStudent', $langTemplate),'target'=>PRIVILEGE_LEVEL::$SITES[$courseStatus].'?cid='.$cid)));
}

// construct a new header
$h = Template::WithTemplateFile('include/Header/Header.template.html');
$h->bind($user_course_data);
$h->bind(array('name' => $user_course_data['courses'][0]['course']['name'],
               'backTitle' => Language::Get('main','changeCourse', $langTemplate),
               'backURL' => 'index.php',
               'notificationElements' => $notifications,
               'navigationElement' => $menu,
               'userNavigationElement' => $userNavigation));
$h->bind($student_data);

$t = Template::WithTemplateFile('include/ExerciseSheet/ExerciseSheetStudent.template.html');
$t->bind($student_data);
$t->bind(array('uid'=>$selectedUser, 'privileged'=>$privileged));

$w = new HTMLWrapper($h, $t);
$w->defineForm(basename(__FILE__).'?cid='.$cid, false, $t);
$w->set_config_file('include/configs/config_student_tutor.json');
$w->show();

ob_end_flush();