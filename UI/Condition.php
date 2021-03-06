<?php
/**
 * @file Condition.php
 * Constructs the page that is displayed when managing exam conditions.
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL version 3
 *
 * @package OSTEPU (https://github.com/ostepu/ostepu-core)
 * @since 0.1.0
 *
 * @author Max Brauer <ma.brauer@live.de>
 * @date 2016
 * @author Till Uhlig <till.uhlig@student.uni-halle.de>
 * @date 2014-2016
 * @author Ralf Busch <ralfbusch92@gmail.com>
 * @date 2014
 * @author Felix Schmidt <Fiduz@Live.de>
 * @date 2013-2014
 * @author Florian Lücke <florian.luecke@gmail.com>
 * @date 2013-2014
 */

ob_start();

include_once dirname(__FILE__) . '/include/Boilerplate.php';
include_once dirname(__FILE__) . '/../Assistants/Structures.php';
include_once dirname(__FILE__) . '/../Assistants/vendor/Validation/Validation.php';

$langTemplate='Condition_Controller';Language::loadLanguageFile('de', $langTemplate, 'json', dirname(__FILE__).'/');

$getValidation = Validation::open($_GET, array('preRules'=>array('sanitize')))
  ->addSet('downloadConditionCsv',
           ['valid_identifier',
            'on_error'=>['type'=>'error',
                         'text'=>Language::Get('main','errorDownloadConditionCsvValidation', $langTemplate)]])
  ->addSet('downloadConditionPdf',
           ['valid_identifier',
            'on_error'=>['type'=>'error',
                         'text'=>Language::Get('main','errorDownloadConditionPdfValidation', $langTemplate)]])
  ->addSet('sortby',
           ['set_default'=>'userName',
            'satisfy_in_list'=>['firstName','lastName','userName','studentNumber','isApproved','type'],
            'on_error'=>['type'=>'error',
                         'text'=>Language::Get('main','errorSortbyValidation', $langTemplate)]])                               
  ->addSet('sortId',
           ['valid_identifier',
            'on_error'=>['type'=>'error',
                         'text'=>Language::Get('main','errorSortIdValidation', $langTemplate)]]);

// TODO: ist etwas unsauber und muss daher noch korrekt validiert werden
if (isset($_POST['sortby'])){
    $tmp = explode('|',$_POST['sortby']);
    $_POST['sortby'] = $tmp[0];
    if (count($tmp)>=2) $_POST['sortId'] = $tmp[1];
}

$postValidation = Validation::open($_POST, array('preRules'=>array('sanitize')))
  ->addSet('action',
           ['set_default'=>'noAction',
            'satisfy_in_list'=>['noAction', 'SetCondition'],
            'on_error'=>['type'=>'error',
                         'text'=>Language::Get('main','invalidAction', $langTemplate)]])
  ->addSet('sortby',
           ['set_default'=>'userName',
            'satisfy_in_list'=>['firstName','lastName','userName','studentNumber','isApproved','type'],
            'on_error'=>['type'=>'error',
                         'text'=>Language::Get('main','errorSortbyValidation', $langTemplate)]])                              
  ->addSet('sortId',
           ['valid_identifier',
            'on_error'=>['type'=>'error',
                         'text'=>Language::Get('main','errorSortIdValidation', $langTemplate)]]);

$getResults = $getValidation->validate();
$postResults = $postValidation->validate();
$notifications = array_merge($notifications,$getValidation->getPrintableNotifications('MakeNotification'));
$notifications = array_merge($notifications,$postValidation->getPrintableNotifications('MakeNotification'));
$getValidation->resetNotifications()->resetErrors();
$postValidation->resetNotifications()->resetErrors();

if ($getValidation->isValid() && isset($getResults['downloadConditionCsv'])) {
    $cid = $getResults['downloadConditionCsv'];
    $postResults['sortby'] = $getResults['sortby'];
    $postResults['sortId'] = $getResults['sortId'];
} elseif(isset($getResults['downloadConditionCsv'])) {
    exit(1);
}

if ($getValidation->isValid() && isset($getResults['downloadConditionPdf'])) {
    $cid = $getResults['downloadConditionPdf'];
    $postResults['sortby'] = $getResults['sortby'];
    $postResults['sortId'] = $getResults['sortId'];
} elseif(isset($getResults['downloadConditionPdf'])) {
    exit(1);
}

global $globalUserData;
Authentication::checkRights(PRIVILEGE_LEVEL::LECTURER, $cid, $uid, $globalUserData);

if ($postValidation->isValid() && $postResults['action'] !== 'noAction') {
    // creates a new course
    if ($postResults['action'] === 'SetCondition') {
        $setConditionNotifications = array();
        
        // bool which is true if any error occured
        $RequestError = false;

        $getValidation = Validation::open($_POST, array('preRules'=>array('sanitize')))
          ->addSet('approvalCondition',
                   ['set_default'=>array(),
                    'perform_this_foreach'=>[['key',
                                             ['valid_identifier']],
                                            ['elem',
                                             ['to_integer',
                                              'satisfy_min_numeric'=>0,
                                              'satisfy_max_numeric'=>100]]],
                    'on_error'=>['type'=>'error',
                                 'text'=>Language::Get('main','errorApprovalConditionValidation', $langTemplate)]]);

        if ($getValidation->isValid()){
            $foundValues = $getValidation->getResult();

            foreach ($foundValues['approvalCondition'] as $approvalConditionId => $percentage) {
                // changes the percentage for each exercise type

                $percentage /= 100;
                $newApprovalCondition = ApprovalCondition::createApprovalCondition($approvalConditionId, $cid, null, $percentage);
                $newApprovalConditionSettings = ApprovalCondition::encodeApprovalCondition($newApprovalCondition);
                $URI = $databaseURI . '/approvalcondition/approvalcondition/' . $approvalConditionId;
                http_put_data($URI, $newApprovalConditionSettings, true, $message);

                if ($message !== 201) {
                    $setConditionNotifications[] = MakeNotification('error', Language::Get('main','errorSetCondition', $langTemplate));
                    $RequestError = true;
                }
            }
        } else {
            $setConditionNotifications =  array_merge($setConditionNotifications, $getValidation->getPrintableNotifications('MakeNotification'));
            $getValidation->resetNotifications()->resetErrors();
            $RequestError = true;
        }

        // creates a notification depending on RequestError
        if ($RequestError) {
            $setConditionNotifications[] = MakeNotification('error', Language::Get('main','errorSetConditions', $langTemplate));
        }
        else {
            $setConditionNotifications[] = MakeNotification('success', Language::Get('main','successSetConditions', $langTemplate));
        }

    }
}

//Bestimmt den Bereich der Übungsserien
if (isset($_POST['startSheet'])) {
    // TODO: es muss noch geprüft werden, ob diese Übungsserie gewählt werden darf
    $_SESSION['startSheet'] = $_POST['startSheet'];
}
if (isset($_POST['selectedSheet'])){
    // TODO: es muss noch geprüft werden, ob diese Übungsserie gewählt werden darf
    $_SESSION['selectedSheet'] = $_POST['selectedSheet'];
}

$minsid = null;
$maxsid = null;
$userNavigation = null;
if (isset($_SESSION['startSheet'])){
    $minsid = $_SESSION['startSheet'];
}
if (isset($_SESSION['selectedSheet'])){
    $maxsid = $_SESSION['selectedSheet'];
}
    


// load user data from the database
$URL = $getSiteURI . "/condition/user/{$uid}/course/{$cid}/firstsheet/{$minsid}/lastsheet/{$maxsid}";
$condition_data = http_get($URL, true);
$condition_data = json_decode($condition_data, true);
$user_course_data = $condition_data['user'];

$menu = MakeNavigationElement($user_course_data,
                               PRIVILEGE_LEVEL::LECTURER,true);

if (isset($condition_data['users'])){
    function compare_lastName($a, $b) {
        return strnatcmp(strtolower((isset($a['lastName']) ? $a['lastName'] : '???')), strtolower((isset($b['lastName']) ? $b['lastName'] : '???')));
    }
    usort($condition_data['users'], 'compare_lastName');

    // manages table sort
    if ($postValidation->isValid() && isset($postResults['sortby'])) {
        $sortBy = $postResults['sortby'];

        switch ($sortBy) {
            case 'firstName':
                $condition_data['users']=array_reverse($condition_data['users']);
                function compare_firstName($a, $b) {
                        if (!isset($a['firstName'])) return 0;
                        if (!isset($b['firstName'])) return 0;
                    return strnatcmp(strtolower($a['firstName']), strtolower($b['firstName']));
                }
                usort($condition_data['users'], 'compare_firstName');
                break;

            case 'userName':
                $condition_data['users']=array_reverse($condition_data['users']);
                function compare_userName($a, $b) {
                        if (!isset($a['userName'])) return 0;
                        if (!isset($b['userName'])) return 0;
                    return strnatcmp(strtolower($a['userName']), strtolower($b['userName']));
                }
                usort($condition_data['users'], 'compare_userName');
                break;

            case 'studentNumber':
                $condition_data['users']=array_reverse($condition_data['users']);
                function compare_studentNumber($a, $b) {
                        if (!isset($a['studentNumber'])) return 0;
                        if (!isset($b['studentNumber'])) return 0;
                    return $a['studentNumber'] < $b['studentNumber'];
                }
                usort($condition_data['users'], 'compare_studentNumber');
                break;

            case 'isApproved':
                $condition_data['users']=array_reverse($condition_data['users']);
                function compare_isApproved($a, $b) {
                    return strnatcmp($a['isApproved'], $b['isApproved']);
                }
                usort($condition_data['users'], 'compare_isApproved');
                break;

            case 'type':
                $condition_data['users']=array_reverse($condition_data['users']);
                function compare_type($a, $b) {
                    global $postResults;
                    $type=$postResults['sortId'];
                    $aId = null;
                    $bId = null;
                    if (isset($a['percentages']))
                        foreach ($a['percentages'] as $key => $per)
                            if ($per['exerciseTypeID']==$type){
                               $aId = $key;break;
                            }

                    if (isset($b['percentages']))
                        foreach ($b['percentages'] as $key => $per)
                            if ($per['exerciseTypeID']==$type){
                               $bId = $key;break;
                            }
                    if ($aId===null && $bId===null) return 0;
                    if ($aId!==null && $bId===null) return 1;
                    if ($aId===null && $bId!==null) return -1;
                    return strnatcmp($a['percentages'][$aId]['points'], $b['percentages'][$bId]['points']);
                }
                usort($condition_data['users'], 'compare_type');
                break;
        }
    }
}

// download csv-archive
if (isset($getResults['downloadConditionCsv']) || isset($getResults['downloadConditionPdf'])) {
    $rows = array();
    $firstRow = array('FirstName','LastName','UserName','IsApproved');//,'STUDENTNUMBER'

    // percentage for each exerciseType
    foreach ($condition_data['minimumPercentages'] as $percentage){
        $firstRow[] = $percentage['exerciseType'].' (P)';
        $firstRow[] = $percentage['exerciseType'].' (%)';
    }
    $rows[] = $firstRow;

    foreach($condition_data['users'] as $user){
        $row = array();

        // firstName
        $row[] = (isset($user['firstName']) ? $user['firstName'] : '???');

        // lastName
        $row[] = (isset($user['lastName']) ? $user['lastName'] : '???');

        // userId
        $row[] = (isset($user['userName']) ? $user['userName'] : '???');

        // studentNumber
        /*if (isset($user['studentNumber'])){
            $row[] = $user['studentNumber'];
        } else
            $row[] = '';*/

        // isApproved for all courses
        $row[] = $user['isApproved']?'Ja':'Nein';

        // percentage for each exerciseType
        foreach ($user['percentages'] as $percentage) {
                // achieved percentage and points
                $row[] = $percentage['points'];
                $row[] = $percentage['percentage'] . '%';
        }
        $rows[] = $row;
    }

    $file = new File();
    if (isset($getResults['downloadConditionPdf'])){
        $text = '<table>';
        foreach ($rows as $key => $row){
            if ($key==0){$text .= '<tr style="font-weight: bold;">';}
            else
            {$text .= '<tr>';}
            foreach ($row as $r){
                $text .= '<td>'.$r.'</td>';
            }
            if ($key==0){$text .= '</tr>';}
            else
            {$text .= '</tr>';}
        }
        $text .= '</table>';

        $pdf = Pdf::createPdf($text,'L');       
        $file = http_post_data($filesystemURI . '/pdf',  Pdf::encodePdf($pdf), true);
        $file = File::decodeFile($file);
        $file->setDisplayName('conditions.pdf');
        $fileData = File::encodeFile($file);
        $file = fileUtils::prepareFileObject(json_decode($fileData, true));
        $file = json_encode($file);
    } elseif (isset($getResults['downloadConditionCsv'])){       
        $csv = Csv::createCsv($rows);
        $file = http_post_data($filesystemURI . '/csv',  Csv::encodeCsv($csv), true);
        $file = File::decodeFile($file);
        $file->setDisplayName('conditions.csv');
        $fileData = File::encodeFile($file);
        $file = fileUtils::prepareFileObject(json_decode($fileData, true));
        $file = json_encode($file);
    }

    echo $file;
    exit(0);
}

if ($postValidation->isValid() && isset($postResults['sortby'])) {
    $condition_data['sortby'] = $postResults['sortby'];
}
if ($postValidation->isValid() && isset($postResults['sortId'])) {
    $condition_data['sortId'] = $postResults['sortId'];
}

if (!isset($_SESSION['selectedSheet'])){
    $maxsid = isset($condition_data['allsheets'][0]['id']) ? $condition_data['allsheets'][0]['id'] : null;
}

if (!isset($_SESSION['startSheet'])){
    $last = end($condition_data['allsheets']);
    $minsid = ($last !== false) ? $last['id'] : null;
}

$userNavigation = MakeUserNavigationElement($user_course_data,
                                            null,
                                            null,
                                            PRIVILEGE_LEVEL::LECTURER,
                                            $maxsid,
                                            isset($condition_data['allsheets']) ? ExerciseSheet::decodeExerciseSheet(json_encode($condition_data['allsheets'])) : null,
                                            false,
                                            false,
                                            null,
                                            array(),
                                            $minsid);

// construct a new header
$h = Template::WithTemplateFile('include/Header/Header.template.html');
$h->bind($user_course_data);
$h->bind(array('name' => $user_course_data['courses'][0]['course']['name'],
               'notificationElements' => $notifications,
               'navigationElement' => $menu,
               'userNavigationElement' => $userNavigation));

// construct a content element for setting exam paper conditions
$setCondition = Template::WithTemplateFile('include/Condition/SetCondition.template.html');
$setCondition->bind($condition_data);
$setCondition->bind($condition_data);
if (isset($setConditionNotifications))
    $setCondition->bind(array('SetConditionNotificationElements' => $setConditionNotifications));

$userList = Template::WithTemplateFile('include/Condition/UserList.template.html');
$userList->bind($condition_data);
$userList->bind($condition_data);
if (isset($userListNotifications))
    $userList->bind(array('UserListNotificationElements' => $userListNotifications));

$summary = Template::WithTemplateFile('include/Condition/Summary.template.html');
$summary->bind($condition_data);
if (isset($summaryNotifications))
    $summary->bind(array('SummaryNotificationElements' => $summaryNotifications));

// wrap all the elements in some HTML and show them on the page
$w = new HTMLWrapper($h, $summary, $setCondition, $userList);
$w->defineForm(basename(__FILE__).'?cid='.$cid, false, $userList);
$w->defineForm(basename(__FILE__).'?cid='.$cid, false, $setCondition);
$w->defineForm(basename(__FILE__).'?cid='.$cid, false, $summary);
$w->set_config_file('include/configs/config_condition.json');
if (isset($maintenanceMode) && $maintenanceMode === '1'){
    $w->add_config_file('include/configs/config_maintenanceMode.json');
}

$w->show();

ob_end_flush();