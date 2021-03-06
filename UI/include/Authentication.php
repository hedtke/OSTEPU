<?php
/**
 * @file Authentication.php
 * Contains the Authentication class.
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
if (file_exists(dirname(__FILE__) . '/Config.php')) include_once dirname(__FILE__) . '/Config.php';

/**
 * Authentication class.
 *
 * Class for Loginsystem.
 */
class Authentication extends AbstractAuthentication
{
    /**
     * The default contructor which sets our sitekey. Sitekey have to be the same all the time!!
     */
    public function __construct($handleSession = true)
    {
        if ($handleSession){
            // force to use session-cookies and to transmit SID over URL
            ini_set('session.use_only_cookies', '1');
            ini_set('session.use_trans_sid', '0');

            // start session
            session_start();
        }
    }

    /**
     * Prevent possible session fixation attack.
     */
    public static function preventSessionFix()
    {
        if (!isset( $_SESSION['SERVER_SID'] )) {
            // delete session content
            session_unset();
            $_SESSION = array();

            // restart session
            session_destroy();
            session_start();

            // generate new session id
            session_regenerate_id(true);

            // save status that serverSID is given
            $_SESSION['SERVER_SID'] = true;
        }
    }

    /**
     * Logs in a user.
     *
     * @param string $username
     * @param string $password
     * @return true if login is successful
     */
    public function loginUser($username, $password )
    {
        global $serverURI;
        $databaseURL = "{$serverURI}/DB/DBUser/user/user/{$username}";
        $user = http_get($databaseURL, false, $message);
        $user = json_decode($user, true);

        // prüfe den Wartungsmodus
        global $maintenanceMode;
        global $maintenanceText;
        global $maintenanceAllowedUsers;
        if ($maintenanceMode === '1' && !in_array($username,explode(',',str_replace(' ', '', $maintenanceAllowedUsers)))){
            $text = $maintenanceText;
            if (trim($maintenanceText) == '') $text = "Wartungsarbeiten!!!";
            return MakeNotification("error", $text);
        }

        // check if user exists
        if ($message != "404" && empty($user) == false) {
            // create passwordhash with salt as suffix
            if (isset($user['failedLogins']) && time()-$user['failedLogins']<5){
            
                $waitSeconds = 5-(time()-$user['failedLogins']);
                return MakeNotification("error", "Die Anmeldung ist für {$waitSeconds} Sekunden gesperrt!!!");
            }
            
            if (isset($user['salt'])){
                $password = $this->hashPassword($password,$user['salt']);

                if (isset($user['password']) && $password == $user['password']) {

                    // save logged in uid
                    $_SESSION['UID'] = $user['id'];
                    $refresh = $this->refreshSession();
                    return $refresh;
                } else {
                    $userid = $user['id'];
                    $databaseURL = "{$serverURI}/DB/DBUser/user/user/{$userid}/IncFailedLogin";
                    $user = http_get($databaseURL, false, $message);
                }
            }
        }
        return false;
    }
}