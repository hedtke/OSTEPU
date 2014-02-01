<?php
/**
 * @todo make it cheaper to combine course status names with status ids
 * @todo make it cheaper to combine marking status names with status ids
 * @todo make it cheaper to combine exercise type names with type ids
 */ // could use a map indexed by status/type id taht is built on construction

require 'Slim/Slim.php';
include '../Include/Request.php';
include_once( '../Include/CConfig.php' );
include_once '../Include/Logger.php';

\Slim\Slim::registerAutoloader();
/**
 * The GetSite class
 *
 * This class gives all informations needed to print a Site
 */
class LgetSite
{
    /**
     *Values needed for conversation with other components
     */
    private $_conf=null;

    private static $_prefix = "getSite";

    public static function getPrefix()
    {
        return LgetSite::$_prefix;
    }
    public static function setPrefix($value)
    {
        LgetSite::$_prefix = $value;
    }


    /**
     *Address of the Logic-Controller
     *dynamic set by CConf below
     */
    private $lURL = "";
    private $flag = 0;

    public function __construct($conf)
    {
        /**
         *Initialise the Slim-Framework
         */
        $this->app = new \Slim\Slim();
        $this->app->response->headers->set('Content-Type', 'application/json');
        /**
         *Set the Logiccontroller-URL
         */
        $this->_conf = $conf;
        $this->query = array();

        $this->query = CConfig::getLink($conf->getLinks(),"controller");
        $this->lURL = $this->query->getAddress();


        //GET TutorAssignmentSiteInfo
        $this->app->get('/tutorassignment/user/:userid/course/:courseid/exercisesheet/:sheetid(/)', array($this, 'tutorAssignmentSiteInfo'));

        //GET StudentSiteInfo
        $this->app->get('/student/user/:userid/course/:courseid(/)', array($this, 'studentSiteInfo'));

        //GET AccountSettings
        $this->app->get('/accountsettings/user/:userid/course/:courseid(/)', array($this, 'userWithCourse'));

        //GET CreateSheet
        $this->app->get('/createsheet/user/:userid/course/:courseid(/)', array($this, 'userWithCourse'));

        //GET Index
        $this->app->get('/index/user/:userid(/)', array($this, 'userWithAllCourses'));

        //GET CourseManagement
        $this->app->get('/coursemanagement/user/:userid/course/:courseid(/)', array($this, 'courseManagement'));

        //GET Upload
        $this->app->get('/upload/user/:userid/course/:courseid(/)', array($this, 'userWithCourse'));

        //GET MarkingTool
        $this->app->get('/markingtool/user/:userid/course/:courseid/exercisesheet/:sheetid(/)', array($this, 'markingTool'));

        //GET UploadHistory
        $this->app->get('/uploadhistory/user/:userid/course/:courseid/exercise/:exerciseid(/)', array($this, 'uploadHistory'));

        //GET TutorSite
        $this->app->get('/tutor/user/:userid/course/:courseid(/)', array($this, 'tutorDozentAdmin'));

        //GET AdminSite
        $this->app->get('/admin/user/:userid/course/:courseid(/)', array($this, 'tutorDozentAdmin'));

        //GET DozentSite
        $this->app->get('/lecturer/user/:userid/course/:courseid(/)', array($this, 'tutorDozentAdmin'));

        //GET GroupSite
        $this->app->get('/group/user/:userid/course/:courseid/exercisesheet/:sheetid(/)', array($this, 'groupSite'));

        //GET Condition
        $this->app->get('/condition/user/:userid/course/:courseid(/)', array($this, 'checkCondition'));

        //run Slim
        $this->app->run();
    }

    public function tutorAssignmentSiteInfo($userid, $courseid, $sheetid){

        $response = array();
        $assignedSubmissionIDs = array();
        /**
         * Get Tutors
         */
        $body = $this->app->request->getBody();
        $header = $this->app->request->headers->all();
        $URL = $this->lURL.'/DB/user/course/'.$courseid.'/status/1'; //status = 1 => Tutor
        $answer = Request::custom('GET', $URL, $header, $body);
        $tutors = json_decode($answer['content'], true);
        foreach ($tutors AS $tutor){
            //benoetigte Attribute waehlen
            $newTutor = array();
            $newTutor['id'] = $tutor['id'];
            $newTutor['userName'] = $tutor['userName'];
            $newTutor['firstName'] = $tutor['firstName'];
            $newTutor['lastName'] = $tutor['lastName'];
            //im Rueckgabe-Array für jeden Tutor ein Marking (ohne Submissions) anlegen
            $tutorAssignment = array(
                    'tutor' => $newTutor,
                    'submissions' => array()
                    );
            array_push($response,$tutorAssignment);
        }
        /**
         * Get Markings
         */
        $URL = $this->lURL.'/DB/marking/exercisesheet/'.$sheetid;
        $answer = Request::custom('GET', $URL, $header, $body);
        //fuer jedes Marking die zugeordnete Submision im Rueckgabearray dem passenden Tutor zuweisen
        foreach (json_decode($answer['content'], true) as $marking){
            foreach ($response as &$tutorAssignment){
                if ($marking['tutorId'] == $tutorAssignment['tutor']['id']){
                    array_push($tutorAssignment['submissions'], $marking['submission']);
                    //ID's aller bereits zugeordneten Submissions speicher
                    array_push($assignedSubmissionIDs, $marking['submission']['id']);
                    break;
                }
            }
        }

        /**
         * Get SelectedSubmissions
         */
        $URL = $this->lURL.'/DB/selectedsubmission/exercisesheet/'.$sheetid;
        $answer = Request::custom('GET', $URL, $header, $body);

        $virtualTutor = array(
                    'id' => null,
                    'userName' => "unassigned",
                    'firstName' => null,
                    'lastName' => null
                    );

        $unassignedSubmissions = array();


        $submissions = json_decode($answer['content'], true);
        foreach ($submissions as $submission){
            if (!in_array($submission['submissionId'], $assignedSubmissionIDs)){
                array_push($unassignedSubmissions, $submission);
            }
        }
        $newTutorAssignment = array(
            'tutor' => $virtualTutor,
            'submissions' => $unassignedSubmissions
                    );
        array_push($response, $newTutorAssignment);

        /**
        * @todo userWithCourse needs to be attached to the tutorAssignment data
        */

        $this->app->response->setBody(json_encode($response));
    }

    public function studentSiteInfo($userid, $courseid){

        $response = array(
                        'sheets' => array(),
                        'user' => array()
                        );
        $body = $this->app->request->getBody();
        $header = $this->app->request->headers->all();

        //get Exercisesheets

        $URL = $this->lURL.'/DB/exercisesheet/course/'.$courseid;
        $answer = Request::custom('GET', $URL, $header, $body);
        $sheets = json_decode($answer['content'], true);

        foreach ($sheets as $sheet){
            $newSheet = array(
                        'id' => $sheet['id'],
                        'courseId'=> $sheet['courseId'],
                        'endDate'=> $sheet['endDate'],
                        'startDate'=> $sheet['startDate'],
                        'sampleSolution'=> $sheet['sampleSolution'],
                        'sheetFile'=> $sheet['sheetFile'],
                        'group'=> array()
                        );

            $URL = $this->lURL.'/DB/exercise/exercisesheet/'.$sheet['id'];
            $answer = Request::custom('GET', $URL, $header, $body);
            $exercises = json_decode($answer['content'], true);

            foreach($exercises as &$exercise){
                foreach($exercise['submissions'] as &$submission){
                    $URL = $this->lURL.'/DB/marking/submission/'.$submission['id'];
                    $answer = Request::custom('GET', $URL, $header, $body);
                    $submission['marking'] = json_decode($answer['content'], true);
                }
            }

            $newSheet['exercises'] = $exercises;

            $maxPoints = 0;
            $points = 0;
            foreach($newSheet['exercises'] as $exercise){
                $maxPoints = $maxPoints + $exercise['maxPoints'];
                foreach($exercise['submissions'] as $submission){
                    $points = $points + $submission['marking']['points'];
                }
            }
            $newSheet['percentage'] = $points / $maxPoints;

            $response['sheets'][] = $newSheet;

            //get UserGroups
            $URL = $this->lURL.'/DB/group/user/'.$userid;
            $answer = Request::custom('GET', $URL, $header, $body);
            $groups = json_decode($answer['content'], true);
            foreach ($groups as $group){
                foreach ($response['sheets'] as &$sheet){
                    if ($sheet['id'] == $group['sheetId']){
                        $sheet['group'] = $group;
                        break;
                    }
                }
            }

        }


        $this->flag = 1;
        $response['user'] = $this->userWithCourse($userid, $courseid);

        $this->app->response->setBody(json_encode($response));

    }

    public function userWithCourse($userid, $courseid){
        $body = $this->app->request->getBody();
        $header = $this->app->request->headers->all();
        $URL = $this->lURL.'/DB/coursestatus/course/'.$courseid.'/user/'.$userid;
        $answer = Request::custom('GET', $URL, $header, $body);
        $user = json_decode($answer['content'], true);
        $response = array(
                'id' =>  $user['id'],
                'userName'=>  $user['userName'],
                'firstName'=>  $user['firstName'],
                'lastName'=>  $user['lastName'],
                'flag'=>  $user['flag'],
                'email'=>  $user['email'],
                'courses'=>  array()
                );
        foreach ($user['courses'] as $course){
            $newCourse = array(
                'status' => $course['status'],
                'statusName' => $this->getStatusName($course['status']),
                'course' => $course['course']
                );
           $response['courses'][] = $newCourse;
        }
        if ($this->flag == 0){
        $this->app->response->setBody(json_encode($response));}
        else{
        $this->flag = 0;
        return $response;}
    }

    public function userWithAllCourses($userid){
        $body = $this->app->request->getBody();
        $header = $this->app->request->headers->all();
        $URL = $this->lURL.'/DB/user/user/'.$userid;
        $answer = Request::custom('GET', $URL, $header, $body);
        $user = json_decode($answer['content'], true);
        $response = array(
                'id' =>  $user['id'],
                'userName'=>  $user['userName'],
                'firstName'=>  $user['firstName'],
                'lastName'=>  $user['lastName'],
                'flag'=>  $user['flag'],
                'email'=>  $user['email'],
                'courses'=>  array()
                );
        foreach ($user['courses'] as $course){
            $newCourse = array(
                'status' => $course['status'],
                'statusName' => $this->getStatusName($course['status']),
                'course' => $course['course']
                );
           $response['courses'][] = $newCourse;
        }
        if ($this->flag == 0){
        $this->app->response->setBody(json_encode($response));}
        else{
        $this->flag = 0;
        return $response;}
    }

    /**
    * @todo Receive the names from the database instead of defining it here.
    */
    public function getStatusName($courseStatus){
        if ($courseStatus == 0){
            return "student";}
        elseif ($courseStatus == 1){
            return "tutor";}
        elseif ($courseStatus == 2){
            return "lecturer";}
        elseif ($courseStatus == 3){
            return "administrator";}
        elseif ($courseStatus == 4){
            return "super-administrator";}
    }

    public function markingTool($userid, $courseid, $sheetid){

        $body = $this->app->request->getBody();
        $header = $this->app->request->headers->all();

        // load all sheets for the course with id $courseid
        $URL = $this->lURL.'/DB/exercisesheet/course/'.$courseid;
        $answer = Request::custom('GET', $URL, $header, $body);
        $sheets = json_decode($answer['content'], true);

        foreach ($sheets as $sheet) {
            $response['exerciseSheets'][] = $sheet['id'];

            $URL = $this->lURL.'/DB/group/exercisesheet/'.$sheet['id'];
            $answer = Request::custom('GET', $URL, $header, $body);
            $groups = json_decode($answer['content'], true);

            $response['groups'] = $groups;
        }

        foreach ($response['groups'] as &$group) {
            $group['exercises'] = array();
        }

        // load all exercise types
        $URL = $this->lURL . '/DB/exercisetype';
        $exerciseTypes = Request::custom('GET', $URL, $header, $body);
        $exerciseTypes = json_decode($exerciseTypes['content'], true);

        // load all exercises for the exercisesheet with id $sheetid
        $URL = $this->lURL.'/DB/exercise/exercisesheet/'.$sheetid;
        $answer = Request::custom('GET', $URL, $header, $body);
        $exercises = json_decode($answer['content'], true);

        /**
         * @todo maybe this should be available as a function?
         */
        // add the name of the exercise type to the exercise
        foreach ($exercises as &$exercise) {
            foreach ($exerciseTypes as $exerciseType) {
                if ($exerciseType['id'] == $exercise['type']) {
                    $exercise['typeName'] = $exerciseType['name'];
                }
            }
        }

        foreach ($response['groups'] as &$group) {
            // for all groups for the sheet with id $sheetid

            foreach ($exercises as $idx => $exercise) {
                // for all exercises of the sheet with id $seetid

                $group['exercises'][$idx] = $exercise;
                unset($group['exercises'][$idx]['submissions']);
                $group['exercises'][$idx]['submission'] = array();

                foreach ($exercise['submissions'] as $submission) {
                    // for all submissions belonging to $exercise

                    foreach ($$group['members'] as $member) {

                        // for each member of $group test if the member has
                        // submitted $submissin

                        if ($member['id'] == $submission['userID']) {
                            // a member of the
                            $group['exercises'][$idx]['submission'] = $submission;
                        }
                    }
                }
            }
        }

        /**
         * @todo actually fill in all marking status names and ids
         */
        $response['markingStatus'] = array();

        /**
         * @todo there should be an easier way
         */
        // add a marking to each submission
        foreach ($response['groups'] as &$group) {
            foreach($group['exercises'] as &$exercise) {
                $submission = $exercise['submission'];

                if (isset($submission['id'])) {
                    // load a marking belonging to $submission
                    $URL = $this->lURL.'/DB/marking/submission/'.$submission['id'];
                    $answer = Request::custom('GET', $URL, $header, $body);
                    $marking = json_decode($answer['content'], true);

                    // add the marking to the response
                    $exercise['submission']['marking'] = $marking;
                }
            }
        }

        // load all tutors in the course with id $courseid
        $URL = $this->lURL.'/DB/user/course/'.$courseid.'/status/1';
        $answer = Request::custom('GET', $URL, $header, $body);
        $tutors = json_decode($answer['content'], true);

        // add all tutors to the response
        $response['tutors'] = $tutors;

        $this->flag = 1;
        $response['user'] = $this->userWithCourse($userid, $courseid);

        $this->app->response->setBody(json_encode($response));

    }

    public function uploadHistory($userid, $courseid, $exerciseid){
        $body = $this->app->request->getBody();
        $header = $this->app->request->headers->all();

        $URL = $this->lURL.'/DB/submission/user/'.$userid.'/exercise/'.$exerciseid;
        $answer = Request::custom('GET', $URL, $header, $body);
        $submissions = json_decode($answer['content'], true);

        $response['submissionHistory'] = array();

        if(!empty($submissions)){
            foreach ($submissions as $submission){
                $response['submissionHistory'][] = $submission;
            }
        }

        $this->flag = 1;
        $response['user'] = $this->userWithCourse($userid, $courseid);

        $this->app->response->setBody(json_encode($response));
    }

    public function tutorDozentAdmin($userid, $courseid){

        $body = $this->app->request->getBody();
        $header = $this->app->request->headers->all();

        $URL = $this->lURL . '/DB/exercisetype';
        $exerciseTypes = Request::custom('GET', $URL, $header, $body);
        $exerciseTypes = json_decode($exerciseTypes['content'], true);

        $URL = $this->lURL . '/DB/exercisesheet/course/'.$courseid.'/exercise';
        $answer = Request::custom('GET', $URL, $header, $body);
        $sheets = json_decode($answer['content'], true);

        foreach ($sheets as &$sheet) {
            foreach ($sheet['exercises'] as &$exercise) {
                foreach ($exerciseTypes as $exerciseType) {
                    if ($exerciseType['id'] == $exercise['type']) {
                        $exercise['typeName'] = $exerciseType['name'];
                    }
                }
            }
        }

        $response['sheets'] = $sheets;

        $this->flag = 1;
        $response['user'] = $this->userWithCourse($userid, $courseid);

        $this->app->response->setBody(json_encode($response));
    }

    public function groupSite($userid, $courseid, $sheetid){
        $body = $this->app->request->getBody();
        $header = $this->app->request->headers->all();

        //Get the Group of the User for the given sheet
        $URL = $this->lURL.'/DB/group/exercisesheet/'.$sheetid.'/user/'.$userid;
        $answer = Request::custom('GET', $URL, $header, $body);
        $response['group'] = json_decode($answer['content'], true);

        //Get the maximum Groupsize of the sheet
        $URL = $this->lURL.'/DB/exercisesheet/exercisesheet/'.$sheetid;
        $answer = Request::custom('GET', $URL, $header, $body);
        $sheet = json_decode($answer['content'], true);
        $response['groupSize'] = $sheet['groupSize'];

        //get the exercises of the sheet
        $URL = $this->lURL.'/DB/exercise/exercisesheet/'.$sheetid;
        $answer = Request::custom('GET', $URL, $header, $body);
        $exercises = json_decode($answer['content'], true);

        $response['groupSubmissions'] = array();

        //Get all Submissions of the group for the sheet (sorted by exercise)
        foreach ( $exercises as $exercise){
            $newGroupExercise = array();
            foreach ($response['group']['members'] as $user){
                $URL = $this->lURL.'/DB/submission/user/'.$user['id'].'/exercise/'.$exercise['id'];
                $answer = Request::custom('GET', $URL, $header, $body);
                $submission = json_decode($answer['content'], true);

                if ($submission != NULL){
                    $newGroupSubmission['user'] = $user;
                    $newGroupSubmission['submission'] = $submission;
                    $newGroupExercise[] = $newGroupSubmission;
                }
            }
            $response['groupSubmissions'][] = $newGroupExercise;
        }

        $URL = $this->lURL.'/DB/invitation/leader/user/'.$userid;
        $answer = Request::custom('GET', $URL, $header, $body);
        $response['invitations'] = json_decode($answer['content'], true);

        $URL = $this->lURL.'/DB/invitation/member/user/'.$userid;
        $answer = Request::custom('GET', $URL, $header, $body);
        $invitations = json_decode($answer['content'], true);

        foreach($invitations as $invitation){
            $response['invitations'][] = $invitation;
        }

        $this->flag = 1;
        $response['user'] = $this->userWithCourse($userid, $courseid);

        $this->app->response->setBody(json_encode($response));
    }

    public function checkCondition($userid, $courseid){

        $body = $this->app->request->getBody();
        $header = $this->app->request->headers->all();

        $URL = $this->lURL.'/DB/exercisetype';
        $answer = Request::custom('GET', $URL, $header, $body);
        $possibleExerciseTypes = json_decode($answer['content'], true);


        $URL = $this->lURL.'/DB/approvalcondition/course/'.$courseid;
        $answer = Request::custom('GET', $URL, $header, $body);
        $approvalconditions = json_decode($answer['content'], true);

        foreach ($approvalconditions as $ac){
            $newMinPercentage = array();
            foreach ($possibleExerciseTypes as $eT){
                if($ac['exerciseTypeId'] == $eT['id']){
                    $newMinPercentage['exerciseTypeID'] = $ac['exerciseTypeId'];
                    $newMinPercentage['exerciseType'] = $eT['name'];
                    $newMinPercentage['minimumPercentage'] = $ac['percentage'] * 100;

                    $response['minimumPercentages'][] = $newMinPercentage;
                    break;
                }
            }
        }
        $percentages = array();
        $URL = $this->lURL.'/DB/exercise/course/'.$courseid;
        $answer = Request::custom('GET', $URL, $header, $body);
        $exercises = json_decode($answer['content'], true);
        foreach($response['minimumPercentages'] as $condition){
            $maxPoints = 0;
            $percentage['exerciseTypeID'] = $condition['exerciseTypeID'];
            $percentage['exerciseType'] = $condition['exerciseType'];
            $percentage['minimumPercentage'] = $condition['minimumPercentage'];

            foreach($exercises as $exercise){
                if($exercise['type'] == $condition['exerciseTypeID']){
                    $maxPoints = $maxPoints + $exercise['maxPoints'];
                    $percentage['exerciseIds'][] = $exercise['id'];
                }
            }

            $percentage['maxPoints'] = $maxPoints;
            $percentage['points'] = "";
            $percentage['isApproved'] = "";

            $percentages[] = $percentage;
        }

        $allMarkings = array();
        foreach ($exercises as $exercise){
            $URL = $this->lURL.'/DB/marking/exercise/'.$exercise['id'];
            $answer = Request::custom('GET', $URL, $header, $body);
            $markings = json_decode($answer['content'], true);

            foreach($markings as $marking){
                $allMarkings[] = $marking;
            }
        }

        $URL = $this->lURL.'/DB/user/course/'.$courseid.'/status/0';
        $answer = Request::custom('GET', $URL, $header, $body);
        $students = json_decode($answer['content'], true);

        foreach ($students as $student){
            $isApprovedForAllExerciseTypes = true;
            $points = array();
            foreach($percentages as $percentage){
                $int = $percentage['exerciseTypeID'];
                $points[$int] = 0;
            }
            foreach($exercises as $exercise){
                foreach($allMarkings as $marking){
                    if(($marking['submission']['studentId'] == $student['id'])
                        and ($marking['submission']['exerciseId'] == $exercise['id'])){
                        foreach($percentages as $percentage){
                            if(in_array($exercise['id'], $percentage['exerciseIds'])){
                                $int = $percentage['exerciseTypeID'];
                                $points[$int] = $points[$int] + $marking['points'];
                            }
                        }

                    }
                }
            }
            foreach($percentages as &$percentage){
                $int = $percentage['exerciseTypeID'];
                $percentage['points'] = $points[$int];
                $percentage['percentage'] = round($percentage['points'] / $percentage['maxPoints'], 3) * 100;

                if (($percentage['points'] / $percentage['maxPoints']) >= $percentage['minimumPercentage'] / 100){
                    $percentage['isApproved'] = true;
                }
                else{
                    $percentage['isApproved'] = false;
                    $isApprovedForAllExerciseTypes = false;
                }
            }
            $student['percentages'] = $percentages;
            $student['isApproved'] = $isApprovedForAllExerciseTypes;
            $response['users'][] = $student;
        }

        $this->flag = 1;
        $response['user'] = $this->userWithCourse($userid, $courseid);

        $this->app->response->setBody(json_encode($response));
    }

    public function courseManagement($userid, $courseid) {
        $body = $this->app->request->getBody();
        $header = $this->app->request->headers->all();

        // returns basic course information
        $URL = $this->lURL.'/DB/course/'.$courseid;
        $answer = Request::custom('GET', $URL, $header, $body);
        $response['course'] = json_decode($answer['content'], true);

        // returns all users of the given course
        $URL = $this->lURL.'/DB/user/course/'.$courseid;
        $answer = Request::custom('GET', $URL, $header, $body);
        $allUsers = json_decode($answer['content'], true);

        // only selects the users whose course-status is tutor or lecturer 
        if(!empty($allUsers)) {
            foreach($allUsers as $user) {
                if ($user['courses'][0]['status'] > 0 && $user['courses'][0]['status'] < 4) {

                    // adds the course-status to the user objects in the response
                    $user['statusName'] = $this->getStatusName($user['courses'][0]['status']);

                    // removes unnecessary data from the user object
                    unset($user['password']);
                    unset($user['salt']);
                    unset($user['failedLogins']);
                    unset($user['courses']);

                    // adds the user to the response
                    $response['users'][] = $user;
                }
            }
        }

        $this->flag = 1;

        // adds the user_course_data to the response
        $response['user'] = $this->userWithCourse($userid, $courseid);

        $this->app->response->setBody(json_encode($response));
    }
}

/**
 * get new Config-Datas from DB
 */
$com = new CConfig(LgetSite::getPrefix());

/**
 * make a new instance of LgetSite-Class with the Config-Datas
 */
if (!$com->used())
    new LgetSite($com->loadConfig());
?>