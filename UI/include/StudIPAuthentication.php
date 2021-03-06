<?php
/**
 * @file StudIPAuthentication.php
 * Contains the StudIPAuthentication class.
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL version 3
 *
 * @package OSTEPU (https://github.com/ostepu/ostepu-core)
 * @since 0.1.0
 *
 * @author Till Uhlig <till.uhlig@student.uni-halle.de>
 * @date 2014-2016
 * @author Ralf Busch <ralfbusch92@gmail.com>
 * @date 2014
 * @author Florian Lücke <florian.luecke@gmail.com>
 * @date 2014
 */

include_once dirname(__FILE__) . '/Helpers.php';
include_once dirname(__FILE__) . '/AbstractAuthentication.php';
include_once dirname(__FILE__) . '/../../Assistants/Structures.php';
if (file_exists(dirname(__FILE__) . '/Config.php')) include_once dirname(__FILE__) . '/Config.php';
include_once dirname(__FILE__) . '/../../Assistants/Logger.php';

/**
 * StudIPAuthentication class.
 *
 * Class for StudIP Loginsystem.
 */
class StudIPAuthentication extends AbstractAuthentication
{
    /**
     * @var UserID from studip
     */
    private $uid;

    /**
     * @var SessionID from studip
     */
    private $sid;

    /**
     * @var CourseID
     */
    private $cid;
    private $vid;
    
    /**
     * @var userData of the user
     */
    private $userData;

    /**
     * @var CourseStatus of the user
     */
    private $courseStatus;
    
    private static $StudipAPI = "https://studip.uni-halle.de/ostepuGateway";
    
    /**
     * The default constructor which logs the user in, if uid, cid and sid is given in GET Parameters.
     */
    public function __construct()
    {
        if (!isset($_GET['logintype']) || $_GET['logintype']!='studip') {
            return;
        }
        
        if (isset($_GET['uid'])) {
            $this->uid = cleanInput($_GET['uid']);
///Logger::Log("uid: ".$this->uid , LogLevel::DEBUG, false, dirname(__FILE__) . '/../../auth.log');
        }
        
        if (isset($_GET['sid'])) {
            $this->sid = cleanInput($_GET['sid']);
///Logger::Log("sid: ".$this->sid , LogLevel::DEBUG, false, dirname(__FILE__) . '/../../auth.log');
        }
        if (isset($_GET['vid'])) {
            $this->vid = cleanInput($_GET['vid']);
///Logger::Log("vid: ".$this->vid , LogLevel::DEBUG, false, dirname(__FILE__) . '/../../auth.log');
        }
        
        if (isset($_GET['cid'])) {
            $this->cid = cleanInput($_GET['cid']);
///Logger::Log("cid: ".$this->cid , LogLevel::DEBUG, false, dirname(__FILE__) . '/../../auth.log');
            // if cid is not numeric
            if (!is_numeric($this->cid)) {
                set_error("409");
                exit();
            }
        }

        if (isset($_GET['uid']) && isset($_GET['sid']) && (isset($_GET['cid']) || isset($_GET['vid']))) {
                    
            // log in user and return result
            $signed = $this->loginUser($this->uid, "");

            if ($signed == true) {

                // multiplexer which site the user wants to see
                $sites = PRIVILEGE_LEVEL::$SITES;

                // if you are not in the course or the course doesn't exist set error 403
                if (isset($this->courseStatus) && (empty($sites[$this->courseStatus]) == false)) {
                    header('location: ' . $sites[$this->courseStatus] . '?cid=' . $this->cid);
                } else {
                    set_error("403");
                    exit();
                }
            } else {
                $this->logoutUser(true);
            }
        } else {
            set_error("401");
            exit();
        }
    }

    /**
     * Find the correct Course Status for given cid
     *
     * @return Status for Course
     */
    private function findCourseStatus()
    {
        if (isset($this->userData['courses'])) {
            foreach ($this->userData['courses'] as $course) {
                if ($course['course']['id'] == $this->cid) {
                    return $course['status'];
                }
            }
        }

    }

    /**
     * Check if user is logged in in StudIP
     *
     * @param string $uid Is the userid from StudIP
     * @param string $sid Is the sessionid from StudIP
     * @return true if user is logged in
     */
    public function checkUserInStudip($uid, $sid)
    {
        $message=null;
        $query = StudIPAuthentication::$StudipAPI . "/request.php?cmd=check_user&uid={$uid}&sid={$sid}";
        $check = http_get($query, false, $message);
        ///$check = "OK";$message=200;
///Logger::Log("check_user_url: ".$query, LogLevel::DEBUG, false, dirname(__FILE__) . '/../../auth.log');
///Logger::Log("check_user_message: ".$message, LogLevel::DEBUG, false, dirname(__FILE__) . '/../../auth.log');
///Logger::Log("check_user_result: ".$check, LogLevel::DEBUG, false, dirname(__FILE__) . '/../../auth.log');
        if ($message==200)
            return $check == "OK";
        
        return false;
    }

    public function getOSTEPUStatus($status){
        $Status_StudIPToOSTEPU = array();
        $Status_StudIPToOSTEPU['dozent'] = CourseStatus::getStatusDefinition(true)['administrator'];
        $Status_StudIPToOSTEPU['tutor'] = CourseStatus::getStatusDefinition(true)['tutor'];
        $Status_StudIPToOSTEPU['autor'] = CourseStatus::getStatusDefinition(true)['student'];
        return (array_key_exists($status,$Status_StudIPToOSTEPU) ? $Status_StudIPToOSTEPU[$status] : null);
    }
     
    /**
     * converts the studIP course IDs (vid) to ostepu IDs (cid)
     *
     * @param string $uid Is the userid from StudIP
     * @param string $sid Is the sessionid from StudIP
     * @return true if user is logged in
     */
    public function convertVidToCid($vid)
    {
        global $databaseURI;
        $url = "{$databaseURI}/externalid/externalid/S_".$vid;
        $message=null;
        $answer = http_get($url, false, $message);
        if ($message == 200){
            $externalId = ExternalId::decodeExternalId($answer);
            
            if ($externalId->getCourse()!==null){
                return $externalId->getCourse()->getId();
            } else
                return null;
        } else {
            return null;
        }
    }

    /**
     * Give user Data from Studip
     *
     * @param string $uid Is the userid from StudIP
     * @return User $user which ist our Structure User with the given information from StudIP
     */
    public function getUserInStudip($uid)
    {
        $message=null;
        $query = StudIPAuthentication::$StudipAPI . "/request.php?cmd=get_user&uid={$uid}";
        $getUserData = http_get($query, false, $message);
        ///$getUserData = "Till:Uhlig:-:hash:acfmr:211203809";$message=200;
///Logger::Log("get_user_url: ".$query, LogLevel::DEBUG, false, dirname(__FILE__) . '/../../auth.log');
///Logger::Log("get_user_message: ".$message, LogLevel::DEBUG, false, dirname(__FILE__) . '/../../auth.log');
///Logger::Log("get_user_result: ".$getUserData, LogLevel::DEBUG, false, dirname(__FILE__) . '/../../auth.log');
        if ($message == 200 && $getUserData != "not found") {
            // convert output to our user structure
            $getUserData = explode(":", utf8_encode($getUserData));

            $getUserData = cleanInput($getUserData);

            $user = User::createUser(NULL,$getUserData[4],$getUserData[2],$getUserData[0],$getUserData[1],NULL,"1","noPassword","noSalt","0",$uid);
        } else {
            $user = "not found";
        }
        return $user;
    }
    
    /**
     * Give user status from Studip
     *
     * @param string $uid Is the userid from StudIP
     * @param string $vid Is the courseid from StudIP
     * @return ostepu user status (0-3) or null
     */
    public function getUserStatusInStudip($uid, $vid)
    {
        $message=null;
        $query = StudIPAuthentication::$StudipAPI . "/request.php?cmd=get_user_status&uid={$uid}&vid={$vid}";
        $status = http_get($query, false, $message);
        ///$status = "dozent";$message=200;
///Logger::Log("get_user_status_url: ".$query, LogLevel::DEBUG, false, dirname(__FILE__) . '/../../auth.log');
///Logger::Log("get_user_status_message: ".$message, LogLevel::DEBUG, false, dirname(__FILE__) . '/../../auth.log');
///Logger::Log("get_user_status_result: ".$status, LogLevel::DEBUG, false, dirname(__FILE__) . '/../../auth.log');
        if($message==200)
            return $this->getOSTEPUStatus($status);

        return null;
    }
    
    /**
     * Give course from Studip
     *
     * @param string $vid Is the courseid from StudIP
     * @return Course $course which contains our Structure Course with the given information from StudIP
     */
    public function getCourseInStudip($vid)
    {
        $message=null;
        $query = StudIPAuthentication::$StudipAPI . "/request.php?cmd=get_title&vid={$vid}";
        $title = http_get($query, false, $message);
        ///$title = "Veranstaltung";$message=200;
///Logger::Log("get_title_url: ".$query, LogLevel::DEBUG, false, dirname(__FILE__) . '/../../auth.log');
///Logger::Log("get_title_message: ".$message, LogLevel::DEBUG, false, dirname(__FILE__) . '/../../auth.log');
///Logger::Log("get_title_result: ".$title, LogLevel::DEBUG, false, dirname(__FILE__) . '/../../auth.log');

        if ($message == 200 && $title != "not found") {
            $query = StudIPAuthentication::$StudipAPI . "/request.php?cmd=get_semester&vid={$vid}";
            $semester = http_get($query, false, $message);
            ///$semester="SS 2015";$message=200;
///Logger::Log("get_semester_url: ".$query, LogLevel::DEBUG, false, dirname(__FILE__) . '/../../auth.log');
///Logger::Log("get_semester_message: ".$message, LogLevel::DEBUG, false, dirname(__FILE__) . '/../../auth.log');
///Logger::Log("get_semester_result: ".$semester, LogLevel::DEBUG, false, dirname(__FILE__) . '/../../auth.log');
        
            if ($message == 200 && $semester != "not found") {
                if (substr($semester,0,2)==='WS')
                    $semester = substr($semester,0,-2).'20'.substr($semester,-2);
                return Course::createCourse(null,utf8_encode($title),$semester,1);
            }
        }

        return null;
    }
    
    /**
     * Create User in DB
     *
     * @param User $data UserData which contains the created User
     * @return true if user is created
     */
    public function createUser($data)
    {
        global $databaseURI;
        $data = User::encodeUser($data);

        $url = "{$databaseURI}/user";
        $message = null;
        $answer = http_post_data($url, $data, false, $message);
        if ($message=='201'){
            $user = User::decodeUser($answer);
            if ($user->getStatus()== '201')
                return true;
        } else {
            // wenn der Nutzer nicht angelegt werden konnte, dann existiert er vielleicht schon
            $username = $data->getUserName();
            $url = "{$databaseURI}/user/user/{$username}";
            $message=null;
            $user = http_get($url, false, $message);
            $user = json_decode($user, true);
            // check if user exists in our system
            if ($message != "404" && empty($user) == false) {
                // der Nutzer existiert schon (also updaten wir ihn)
                $userUpdate = User::createUser(null,null,null,null,null,NULL,null,null,null,null,$data->getExternalId());
                $url = "{$databaseURI}/user/user/".$user['id'];
                $message = null;
                $answer = http_put_data($url, User::encodeUser($userUpdate), false, $message);
                
                if ($message=='201'){
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Add Course to an user
     *
     * @param string $userId UserID in our System (Attention: NOT the externalID)
     * @param string $courseID CourseID
     * @param string $status The Status the user wants to have in given course.
     * @return true if user is logged in
     */
    public function createCourseStatus($userId,$courseId,$status)
    {
        global $databaseURI;
        $data = User::encodeUser(User::createCourseStatus($userId,$courseId,$status));

        $url = "{$databaseURI}/coursestatus";
        $message=null;
        http_post_data($url, $data, true, $message);
///Logger::Log("create_user_status_url: ".$url, LogLevel::DEBUG, false, dirname(__FILE__) . '/../../auth.log');
///Logger::Log("create_user_status_message: ".$message, LogLevel::DEBUG, false, dirname(__FILE__) . '/../../auth.log');
///Logger::Log("create_user_status_data: ".$data, LogLevel::DEBUG, false, dirname(__FILE__) . '/../../auth.log');
        return $message == "201";
    }
    
    /**
     * Give course data
     *
     * @param string $courseID CourseID
     * @return course data
     */
    public function getCourseData($cid)
    {
        global $databaseURI;
        
        $url = "{$databaseURI}/course/course/{$cid}";
        $message=null;
        $data = http_get($url, false, $message);
        $data = Course::decodeCourse($data);
        if ($data === array()) return null;
        return $data;
    }

    /**
     * Logs in a user.
     *
     * @param string $username
     * @param string $password
     * @return true if login is successful
     */
    public function loginUser($username, $password)
    {
        // prüfe den Wartungsmodus
        global $maintenanceMode;
        global $maintenanceText;
        global $maintenanceAllowedUsers; // hier bezieht sich unsername aber auf die externe Bezeichnung von Studip
        if ($maintenanceMode === '1' && !in_array($username,explode(',',str_replace(' ', '', $maintenanceAllowedUsers)))){
            $text = $maintenanceText;
            if (trim($maintenanceText) == '') $text = "Wartungsarbeiten!!!";
            set_error("error", $text);
            exit(); 
        }
        
        global $databaseURI;
        global $logicURI;
        global $serverURI;

        // check if logged in in studip
        $studip = $this->checkUserInStudip($this->uid,$this->sid);
        $studipStatus=null;
        if ($studip == true) {
///Logger::Log("inStudip", LogLevel::DEBUG, false, dirname(__FILE__) . '/../../auth.log');

            $url = "{$databaseURI}/user/user/{$username}";
            $message=null;
            $this->userData = http_get($url, false, $message);
///Logger::Log("ostepuUser_url: ".$url, LogLevel::DEBUG, false, dirname(__FILE__) . '/../../auth.log');
///Logger::Log("ostepuUser_message: ".$message, LogLevel::DEBUG, false, dirname(__FILE__) . '/../../auth.log');
///Logger::Log("ostepuUser_data: ".$this->userData, LogLevel::DEBUG, false, dirname(__FILE__) . '/../../auth.log');
            $this->userData = json_decode($this->userData, true);
            // check if user exists in our system
            if ($message != "404" && empty($this->userData) == false) {
                // save logged in uid
                $_SESSION['UID'] = $this->userData['id'];

                // refresh Session in UI and DB
                $refresh = $this->refreshSession();
                
                if (isset($_GET['vid']) && (!isset($_GET['cid']) || $this->cid===null)){
                    // convert vid to cid
                    // create course if does not exist
                    
                    
                    $this->cid = $this->convertVidToCid($_GET['vid']);
///Logger::Log("cid: ".$this->cid , LogLevel::DEBUG, false, dirname(__FILE__) . '/../../auth.log');
                    if ($this->cid===null){
                        // create course       
                        $studipStatus = $this->getUserStatusInStudip($this->uid,$this->vid);
                        if ($studipStatus===CourseStatus::getStatusDefinition(true)['administrator']){
///Logger::Log("createCourse>>".$_GET['vid'] , LogLevel::DEBUG, false, dirname(__FILE__) . '/../../auth.log');
                            $courseObject = $this->getCourseInStudip($this->vid);
                            if ($courseObject!==null){
                                $url = "{$serverURI}/logic/LCourse/course";
                                $courseObject = http_post_data($url, Course::encodeCourse($courseObject), false, $message);
                                if ($message===201){
                                    // new course was created
                                    $courseObject = Course::decodeCourse($courseObject);
                                    if ($courseObject!==null){
                                        $this->cid = $courseObject->getId();
                                        $url = "{$databaseURI}/externalid";
                                        $externalId = ExternalId::createExternalId('S_'.$_GET['vid'],$this->cid);
                                        $externalId = http_post_data($url, ExternalId::encodeExternalId($externalId), false, $message);

                                        if ($message!==201){
                                            // create externalId fails, remove course
                                            $url = "{$logicURI}/course/course/".$this->cid;
                                            http_delete($url, false, $message);
                                            $this->cid=null;
                                        }

                                        if ($this->cid!==null && $studipStatus===CourseStatus::getStatusDefinition(true)['administrator']){
                                            // redirect user to course settings
                                            /// ???
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                if (!isset($this->cid) || $this->cid===null){
                    set_error("unbekannte Veranstaltung!!!");
                    exit(); 
                }
                    
                // get the courseStatus for given course
                $this->courseStatus = $this->findCourseStatus();
///Logger::Log("courseStatus: ".$this->courseStatus , LogLevel::DEBUG, false, dirname(__FILE__) . '/../../auth.log');
               
                // if user has no status in course create it
                if (!isset($this->courseStatus)) {
                    if ($studipStatus===null)
                        $studipStatus = $this->getUserStatusInStudip($this->uid,$this->vid);
                    if ($studipStatus!==null){
///Logger::Log("createCourseStatus" , LogLevel::DEBUG, false, dirname(__FILE__) . '/../../auth.log');
                        // check whether an registration is allowed
                        $courseData = $this->getCourseData($this->cid);
                        if ($courseData===null){
                            // no course data
                            set_error("Keine Veranstaltung gefunden!");
                            exit(); 
                        }
                        
                        if ($courseData->getSettings()!==null){
                            $end = Course::containsSetting($courseData,'RegistrationPeriodEnd');
                            if ($end !== null && $end != 0 && $end<time()){
                                // no registration allowed
                                set_error("Eine Anmeldung ist nicht möglich!!! Ablaufdatum: ".date('d.m.Y - H:i', $end));
                                exit(); 
                            }
                        }
                            
                        $CourseStatusResponse = $this->createCourseStatus($this->userData['id'],$this->cid,$studipStatus);

                        // set courseStatus to studipStatus only if status is created in DB successfully
                        if ($CourseStatusResponse == true) {
                            $this->courseStatus = $studipStatus;
                        }
                    }
                }

                return $refresh;
            } else {
                // get new user from studIP
                $newUser = $this->getUserInStudip($username);

                // if user is a valid user
                if ($newUser != "not found") {
///Logger::Log("creatUser: ".json_encode($newUser), LogLevel::DEBUG, false, dirname(__FILE__) . '/../../auth.log');
                    $response = $this->createUser($newUser);

                    // if successful try to login new user
                    if ($response == true) {
                        return $this->loginUser($username, "");
                    }
                }
            }
        }
        return false;
    }
}