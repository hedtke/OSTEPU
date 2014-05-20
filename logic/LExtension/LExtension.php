<?php
/**
 * @file LExtension.php contains the LExtension class
 *
 * @author Till Uhlig
 */

require_once '../../Assistants/Slim/Slim.php';
include_once '../../Assistants/Request.php';
include_once '../../Assistants/CConfig.php';
include_once ( '../../Assistants/Logger.php' );
include_once ( '../../Assistants/Structures.php' );

\Slim\Slim::registerAutoloader();

/**
 * A class, to handle requests to the LExtension-Component
 */
class LExtension
{
    /**
     * @var Slim $_app the slim object
     */
    private $app = null;
    
    /**
     * @var Component $_conf the component data object
     */
    private $_conf=null;

    /**
     * @var string $_prefix the prefix, the class works with
     */
    private static $_prefix = "link";

    /**
     * the $_prefix getter
     *
     * @return the value of $_prefix
     */
    public static function getPrefix()
    {
        return LExtension::$_prefix;
    }

    /**
     * the $_prefix setter
     *
     * @param string $value the new value for $_prefix
     */
    public static function setPrefix($value)
    {
        LExtension::$_prefix = $value;
    }
    
    private $_extension = array( );
    
    /**
     * REST actions
     *
     * This function contains the REST actions with the assignments to
     * the functions.
     *
     * @param Component $conf component data
     */
    public function __construct($conf)
    {
        // initialize slim
        $this->app = new \Slim\Slim(array('debug' => true));
        $this->app->response->headers->set('Content-Type', 'application/json');

        // initialize component
        $this->_conf = $conf;
        $this->query = CConfig::getLink($conf->getLinks(),"controller");
        $this->_out = CConfig::getLinks($conf->getLinks(),"out");
        $this->_extension = CConfig::getLinks($conf->getLinks(),"extension");
        
        //POST AddCourseExtension
        $this->app->post('/link/course/:courseid/extension/:name(/)', array($this, 'addCourseExtension'));

        //DELETE DeleteCourseExtension
        $this->app->delete('/link/course/:courseid/extension/:name(/)', array($this, 'deleteCourseExtension'));
        
        //GET GetExtensionInstalled
        $this->app->get('/link/exists/course/:courseid/extension/:name(/)', array($this, 'getExtensionInstalled'));
        
        //GET GetInstalledExtensions
        $this->app->get('/link/course/:courseid/extension(/)', array($this, 'getInstalledExtensions'));
        
        
        
        
        
        //GET GetExtensions
        $this->app->get('/link/extension(/)', array($this, 'getExtensions'));
        
        //GET GetExtensionExists
        $this->app->get('/link/exists/extension/:name(/)', array($this, 'getExtensionExists'));
        
        //GET GetExtension
        $this->app->get('/link/extension/:name(/)', array($this, 'getExtension'));

        //run Slim
        $this->app->run();
    }
    
    public function deleteCourseExtension($courseid, $name)
    {
        foreach($this->_extension as $link){
            if ($link->getTargetName() === $name || $link->getTarget() === $name){
            
                $result = Request::routeRequest( 
                                                'DELETE',
                                                '/course/'.$courseid,
                                                $this->app->request->headers->all(),
                                                '',
                                                $link,
                                                'course'
                                                );

                // checks the correctness of the query
                if ( $result['status'] >= 200 && 
                     $result['status'] <= 299 ){

                    $this->app->response->setStatus( 201 );
                    $this->app->response->setBody( null );
                    if ( isset( $result['headers']['Content-Type'] ) )
                        $this->app->response->headers->set( 
                                                            'Content-Type',
                                                            $result['headers']['Content-Type']
                                                            );
                    $this->app->stop( );
                    
                } else {
                    Logger::Log( 
                                'DELETE DeleteCourseExtension failed',
                                LogLevel::ERROR
                                );
                    $this->app->response->setStatus( isset( $result['status'] ) ? $result['status'] : 409 );
                    $this->app->stop( );
                }
            }
        }
        
        $this->app->response->setStatus( 404 );
        $this->app->response->setBody( null );
    }
    
    public function addCourseExtension($courseid, $name)
    {
        foreach($this->_extension as $link){
            if ($link->getTargetName() === $name || $link->getTarget() === $name){
            
                // TODO: hier eventuell alle Course Daten verwenden (vorher Abrufen)
                $courseObject = Course::createCourse(
                                                    $courseid,
                                                    null,
                                                    null,
                                                    null
                                                    );

                $result = Request::routeRequest( 
                                                'POST',
                                                '/course',
                                                $this->app->request->headers->all(),
                                                Course::encodeCourse($courseObject),
                                                $link,
                                                'course'
                                                );

                // checks the correctness of the query
                if ( $result['status'] >= 200 && 
                     $result['status'] <= 299 ){

                    $this->app->response->setStatus( 201 );
                    $this->app->response->setBody( null );
                    if ( isset( $result['headers']['Content-Type'] ) )
                        $this->app->response->headers->set( 
                                                            'Content-Type',
                                                            $result['headers']['Content-Type']
                                                            );
                    $this->app->stop( );
                    
                } else {
                    Logger::Log( 
                                'POST AddCourseExtension failed',
                                LogLevel::ERROR
                                );
                    $this->app->response->setStatus( isset( $result['status'] ) ? $result['status'] : 409 );
                    $this->app->stop( );
                }
            }
        }
        
        $this->app->response->setStatus( 404 );
        $this->app->response->setBody( null );
    }
    
    public function getInstalledExtensions($courseid)
    {
        $extensions = array();
        
        foreach($this->_extension as $link){
            $result = Request::routeRequest( 
                                            'GET',
                                            '/link/exists/course/'.$courseid,
                                            $this->app->request->headers->all(),
                                            '',
                                            $link,
                                            'link'
                                            );

            // checks the correctness of the query
            if ( $result['status'] >= 200 && 
                 $result['status'] <= 299 ){
                $extensions[] = $link;                  
            }
        }
        
        if (!empty($extensions)){
            $this->app->response->setStatus( 200 );
        } else
            $this->app->response->setStatus( 404 );
        
        $this->app->response->setBody( Component::encodeComponent( $extensions ) );
    }
    
    public function getExtensionInstalled($courseid, $name)
    {
        foreach($this->_extension as $link){
            if ($link->getTargetName() === $name || $link->getTarget() === $name){
                $result = Request::routeRequest( 
                                                'GET',
                                                '/exists/course/'.$courseid,
                                                $this->app->request->headers->all(),
                                                '',
                                                $link,
                                                'course'
                                                );

                // checks the correctness of the query
                if ( $result['status'] >= 200 && 
                     $result['status'] <= 299 ){

                    $this->app->response->setStatus( 200 );
                    $this->app->response->setBody( null );
                    if ( isset( $result['headers']['Content-Type'] ) )
                        $this->app->response->headers->set( 
                                                            'Content-Type',
                                                            $result['headers']['Content-Type']
                                                            );
                    $this->app->stop( );
                } else {
                    Logger::Log( 
                                'POST GetExtensionInstalled failed',
                                LogLevel::ERROR
                                );
                    $this->app->response->setStatus( isset( $result['status'] ) ? $result['status'] : 409 );
                    $this->app->stop( );
                }
            }
        }
        $this->app->response->setStatus( 404 );
        $this->app->response->setBody( null );
    }
    
    public function getExtensionExists($name)
    {
        foreach($this->_extension as $link){
            if ($link->getTargetName() === $name || $link->getTarget() === $name){
                $this->app->response->setStatus( 200 );
                $this->app->response->setBody(null);
                $this->app->stop( );
            }
        }
        
        $this->app->response->setStatus( 404 );
        $this->app->response->setBody( null );
    }
    
    public function getExtension($name)
    {
        foreach($this->_extension as $link){
            if ($link->getTargetName() === $name || $link->getTarget() === $name){
                $this->app->response->setStatus( 200 );
                $this->app->response->setBody(Link::encodeLink($link));
                $this->app->stop( );
            }
        }
        
        $this->app->response->setStatus( 404 );
        $this->app->response->setBody( null );
    }

    public function getExtensions()
    {
        $this->app->response->setStatus( 200 );
        $this->app->response->setBody( Component::encodeComponent( $this->_extension ) );
    }
}
/**
 * get new Config-Datas from DB
 */
$com = new CConfig(LExtension::getPrefix());

/**
 * run a new instance of Extension-Class with the Config-Datas
 */
if (!$com->used())
    new LExtension($com->loadConfig());
?>