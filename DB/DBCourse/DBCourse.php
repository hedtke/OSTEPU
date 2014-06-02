<?php 


/**
 * @file DBCourse.php contains the DBCourse class
 *
 * @author Till Uhlig
 * @author Felix Schmidt
 * @example DB/DBCourse/CourseSample.json
 */

require_once ( '../../Assistants/Slim/Slim.php' );
include_once ( '../../Assistants/Structures.php' );
include_once ( '../../Assistants/Request.php' );
include_once ( '../../Assistants/DBJson.php' );
include_once ( '../../Assistants/DBRequest.php' );
include_once ( '../../Assistants/CConfig.php' );
include_once ( '../../Assistants/Logger.php' );

\Slim\Slim::registerAutoloader( );

// runs the CConfig
$com = new CConfig( DBCourse::getPrefix( ) );

// runs the DBCourse
if ( !$com->used( ) )
    new DBCourse( $com->loadConfig( ) );

/**
 * A class, to abstract the "DBCourse" table from database
 */
class DBCourse
{

    /**
     * @var Slim $_app the slim object
     */
    private $_app = null;

    /**
     * @var Component $_conf the component data object
     */
    private $_conf = null;

    /**
     * @var Link[] $query a list of links to a query component
     */
    private $query = array( );

    /**
     * @var string $_prefix the prefixes, the class works with (comma separated)
     */
    private static $_prefix = 'course';

    /**
     * the $_prefix getter
     *
     * @return the value of $_prefix
     */
    public static function getPrefix( )
    {
        return DBCourse::$_prefix;
    }

    /**
     * the $_prefix getter
     *
     * @return the value of $_prefix
     */
    public static function setPrefix( $value )
    {
        DBCourse::$_prefix = $value;
    }

    /**
     * REST actions
     *
     * This function contains the REST actions with the assignments to
     * the functions.
     *
     * @param Component $conf component data
     */
    public function __construct( $conf )
    {

        // initialize component
        $this->_conf = $conf;
        $this->query = array( CConfig::getLink( 
                                               $conf->getLinks( ),
                                               'out'
                                               ) );

        // initialize slim
        $this->_app = new \Slim\Slim( );
        $this->_app->response->headers->set( 
                                            'Content-Type',
                                            'application/json'
                                            );

        // PUT EditCourse
        $this->_app->put( 
                         '/' . $this->getPrefix( ) . '(/course)/:courseid(/)',
                         array( 
                               $this,
                               'editCourse'
                               )
                         );

        // DELETE DeleteCourse
        $this->_app->delete( 
                            '/' . $this->getPrefix( ) . '(/course)/:courseid(/)',
                            array( 
                                  $this,
                                  'deleteCourse'
                                  )
                            );

        // POST AddCourse
        $this->_app->post( 
                          '/' . $this->getPrefix( ) . '(/)',
                          array( 
                                $this,
                                'addCourse'
                                )
                          );

        // GET GetCourse
        $this->_app->get( 
                         '/' . $this->getPrefix( ) . '(/course)/:courseid(/)',
                         array( 
                               $this,
                               'getCourse'
                               )
                         );

        // GET GetAllCourses
        $this->_app->get( 
                         '/' . $this->getPrefix( ) . '(/course)(/)',
                         array( 
                               $this,
                               'getAllCourses'
                               )
                         );

        // GET GetUserCourses
        $this->_app->get( 
                         '/' . $this->getPrefix( ) . '/user/:userid(/)',
                         array( 
                               $this,
                               'getUserCourses'
                               )
                         );

        // starts slim only if the right prefix was received
        if ( strpos( 
                    $this->_app->request->getResourceUri( ),
                    '/' . $this->getPrefix( )
                    ) === 0 ){

            // run Slim
            $this->_app->run( );
        }
    }

    /**
     * Edits a course.
     *
     * Called when this component receives an HTTP PUT request to
     * /course/course/$courseid(/) or /course/$courseid(/).
     * The request body should contain a JSON object representing the course's new
     * attributes.
     *
     * @param int $courseid The id of the course that is being updated.
     */
    public function editCourse( $courseid )
    {
        Logger::Log( 
                    'starts PUT EditCourse',
                    LogLevel::DEBUG
                    );

        // checks whether incoming data has the correct data type
        DBJson::checkInput( 
                           $this->_app,
                           ctype_digit( $courseid )
                           );

        // decode the received course data, as an object
        $insert = Course::decodeCourse( $this->_app->request->getBody( ) );

        // always been an array
        $arr = true;
        if ( !is_array( $insert ) ){
            $insert = array( $insert );
            $arr = false;
        }

        foreach ( $insert as $in ){

            // generates the update data for the object
            $data = $in->getInsertData( );

            // starts a query, by using a given file
            $result = DBRequest::getRoutedSqlFile( 
                                                  $this->query,
                                                  'Sql/EditCourse.sql',
                                                  array( 
                                                        'courseid' => $courseid,
                                                        'values' => $data
                                                        )
                                                  );

            // checks the correctness of the query
            if ( $result['status'] >= 200 && 
                 $result['status'] <= 299 ){
                $this->_app->response->setStatus( 201 );
                if ( isset( $result['headers']['Content-Type'] ) )
                    $this->_app->response->headers->set( 
                                                        'Content-Type',
                                                        $result['headers']['Content-Type']
                                                        );
                
            } else {
                Logger::Log( 
                            'PUT EditCourse failed',
                            LogLevel::ERROR
                            );
                $this->_app->response->setStatus( isset( $result['status'] ) ? $result['status'] : 409 );
                $this->_app->stop( );
            }
        }
    }

    /**
     * Deletes a course.
     *
     * Called when this component receives an HTTP DELETE request to
     * /course/course/$courseid(/) or /course/$courseid(/).
     *
     * @param int $courseid The id of the course that is being deleted.
     */
    public function deleteCourse( $courseid )
    {
        Logger::Log( 
                    'starts DELETE DeleteCourse',
                    LogLevel::DEBUG
                    );

        // checks whether incoming data has the correct data type
        DBJson::checkInput( 
                           $this->_app,
                           ctype_digit( $courseid )
                           );

        // starts a query, by using a given file
        $result = DBRequest::getRoutedSqlFile( 
                                              $this->query,
                                              'Sql/DeleteCourse.sql',
                                              array( 'courseid' => $courseid )
                                              );

        // checks the correctness of the query
        if ( $result['status'] >= 200 && 
             $result['status'] <= 299 ){

            $this->_app->response->setStatus( 201 );
            if ( isset( $result['headers']['Content-Type'] ) )
                $this->_app->response->headers->set( 
                                                    'Content-Type',
                                                    $result['headers']['Content-Type']
                                                    );
            
        } else {
            Logger::Log( 
                        'DELETE DeleteCourse failed',
                        LogLevel::ERROR
                        );
            $this->_app->response->setStatus( isset( $result['status'] ) ? $result['status'] : 409 );
            $this->_app->stop( );
        }
    }

    /**
     * Adds a course.
     *
     * Called when this component receives an HTTP POST request to
     * /course(/).
     * The request body should contain a JSON object representing the course's
     * attributes.
     */
    public function addCourse( )
    {
        Logger::Log( 
                    'starts POST AddCourse',
                    LogLevel::DEBUG
                    );

        // decode the received course data, as an object
        $insert = Course::decodeCourse( $this->_app->request->getBody( ) );

        // always been an array
        $arr = true;
        if ( !is_array( $insert ) ){
            $insert = array( $insert );
            $arr = false;
        }

        // this array contains the indices of the inserted objects
        $res = array( );
        foreach ( $insert as $in ){

            // generates the insert data for the object
            $data = $in->getInsertData( );

            if ($in->getId()!==null){
                $res[] = $in;
                $this->_app->response->setStatus( 201 );
                continue;
            }
            
            // starts a query, by using a given file
            $result = DBRequest::getRoutedSqlFile( 
                                                  $this->query,
                                                  'Sql/AddCourse.sql',
                                                  array( 'values' => $data )
                                                  );

            // checks the correctness of the query
            if ( $result['status'] >= 200 && 
                 $result['status'] <= 299 ){
                $queryResult = Query::decodeQuery( $result['content'] );

                // sets the new auto-increment id
                $obj = new Course( );
                $obj->setId( $queryResult->getInsertId( ) );

                $res[] = $obj;
                $this->_app->response->setStatus( 201 );
                if ( isset( $result['headers']['Content-Type'] ) )
                    $this->_app->response->headers->set( 
                                                        'Content-Type',
                                                        $result['headers']['Content-Type']
                                                        );
                
            } else {
                Logger::Log( 
                            'POST AddCourse failed',
                            LogLevel::ERROR
                            );
                $this->_app->response->setStatus( isset( $result['status'] ) ? $result['status'] : 409 );
                $this->_app->response->setBody( Course::encodeCourse( $res ) );
                $this->_app->stop( );
            }
        }

        if ( !$arr && 
             count( $res ) == 1 ){
            $this->_app->response->setBody( Course::encodeCourse( $res[0] ) );
            
        } else 
            $this->_app->response->setBody( Course::encodeCourse( $res ) );
    }

    public function get( 
                        $functionName,
                        $sqlFile,
                        $userid,
                        $courseid,
                        $esid,
                        $eid,
                        $suid,
                        $mid,
                        $singleResult = false
                        )
    {
        Logger::Log( 
                    'starts GET ' . $functionName,
                    LogLevel::DEBUG
                    );

        // checks whether incoming data has the correct data type
        DBJson::checkInput( 
                           $this->_app,
                           $userid == '' ? true : ctype_digit( $userid ),
                           $courseid == '' ? true : ctype_digit( $courseid ),
                           $esid == '' ? true : ctype_digit( $esid ),
                           $eid == '' ? true : ctype_digit( $eid ),
                           $suid == '' ? true : ctype_digit( $suid ),
                           $mid == '' ? true : ctype_digit( $mid )
                           );

        // starts a query, by using a given file
        $result = DBRequest::getRoutedSqlFile( 
                                              $this->query,
                                              $sqlFile,
                                              array( 
                                                    'userid' => $userid,
                                                    'courseid' => $courseid,
                                                    'esid' => $esid,
                                                    'eid' => $eid,
                                                    'suid' => $suid,
                                                    'mid' => $mid
                                                    )
                                              );

        // checks the correctness of the query
        if ( $result['status'] >= 200 && 
             $result['status'] <= 299 ){
            $query = Query::decodeQuery( $result['content'] );

            if ( $query->getNumRows( ) > 0 ){
                $res = Course::ExtractCourse( 
                                             $query->getResponse( ),
                                             $singleResult
                                             );
                $this->_app->response->setBody( Course::encodeCourse( $res ) );

                $this->_app->response->setStatus( 200 );
                if ( isset( $result['headers']['Content-Type'] ) )
                    $this->_app->response->headers->set( 
                                                        'Content-Type',
                                                        $result['headers']['Content-Type']
                                                        );

                $this->_app->stop( );
                
            } else 
                $result['status'] = 404;
        }

        Logger::Log( 
                    'GET ' . $functionName . ' failed',
                    LogLevel::ERROR
                    );
        $this->_app->response->setStatus( isset( $result['status'] ) ? $result['status'] : 409 );
        $this->_app->response->setBody( Course::encodeCourse( new Course( ) ) );
        $this->_app->stop( );
    }

    /**
     * Returns a course.
     *
     * Called when this component receives an HTTP GET request to
     * /course/course/$courseid(/) or /course/$courseid(/).
     *
     * @param int $courseid The id of the course that should be returned.
     */
    public function getCourse( $courseid )
    {
        $this->get( 
                   'GetCourse',
                   'Sql/GetCourse.sql',
                   isset( $userid ) ? $userid : '',
                   isset( $courseid ) ? $courseid : '',
                   isset( $esid ) ? $esid : '',
                   isset( $eid ) ? $eid : '',
                   isset( $suid ) ? $suid : '',
                   isset( $mid ) ? $mid : '',
                   true
                   );
    }

    /**
     * Returns all courses.
     *
     * Called when this component receives an HTTP GET request to
     * /course/course(/) or /course(/).
     */
    public function getAllCourses( )
    {
        $this->get( 
                   'GetAllCourses',
                   'Sql/GetAllCourses.sql',
                   isset( $userid ) ? $userid : '',
                   isset( $courseid ) ? $courseid : '',
                   isset( $esid ) ? $esid : '',
                   isset( $eid ) ? $eid : '',
                   isset( $suid ) ? $suid : '',
                   isset( $mid ) ? $mid : ''
                   );
    }

    /**
     * Returns all courses a given user belongs to.
     *
     * Called when this component receives an HTTP GET request to
     * /course/user/$userid(/).
     *
     * @param int $userid The id of the user.
     */
    public function getUserCourses( $userid )
    {
        $this->get( 
                   'GetUserCourses',
                   'Sql/GetUserCourses.sql',
                   isset( $userid ) ? $userid : '',
                   isset( $courseid ) ? $courseid : '',
                   isset( $esid ) ? $esid : '',
                   isset( $eid ) ? $eid : '',
                   isset( $suid ) ? $suid : '',
                   isset( $mid ) ? $mid : ''
                   );
    }
}

 
?>

