<?php
#region LoggerSegment
class LoggerSegment
{
    public static $name = 'logger';
    public static $enabledShow = true;
    private static $initialized=false;
    public static $rank = 200;
    private static $langTemplate='LoggerSegment';
    
    private static $logLevel = array('info'=>LogLevel::INFO,'warning'=>LogLevel::WARNING,'error'=>LogLevel::ERROR);

    public static function getDefaults()
    {
        $res = array();
        foreach (self::$logLevel as $levelName => $level)
            $res['log_level_'.$levelName] = array('data[LOGGER][log_level_'.$levelName.']', null);
        return $res;
    }
    
    public static function showInfoBar(&$data)
    {
        Installation::log(array('text'=>Language::Get('main','functionBegin')));
        if (Einstellungen::$accessAllowed){
            echo "<tr><td class='e'>".Language::Get('logger','title',self::$langTemplate)."</td></tr>";
            foreach (self::$logLevel as $levelName => $level) {
                echo "<tr><td class='v'>".Design::erstelleAuswahl(false, $data['LOGGER']['log_level_'.$levelName], 'data[LOGGER][log_level_'.$levelName.']', 'selected', null, true).Language::Get('logger','log_level_'.$levelName,self::$langTemplate)."</td></tr>";
            }
        }
        Installation::log(array('text'=>Language::Get('main','functionEnd')));
    }

    public static function init($console, &$data, &$fail, &$errno, &$error)
    {
        Installation::log(array('text'=>Language::Get('main','functionBegin')));
        Language::loadLanguageFile('de', self::$langTemplate, 'json', dirname(__FILE__).'/');
        Installation::log(array('text'=>Language::Get('main','languageInstantiated')));
        
        Installation::$logLevel = LogLevel::NONE;
        foreach (self::$logLevel as $levelName => $level) {
            if (isset($data['LOGGER']['log_level_'.$levelName]) && $data['LOGGER']['log_level_'.$levelName] === 'selected') Installation::$logLevel |= $level;
        }
   
        $def = self::getDefaults();
        $text = '';
        foreach (self::$logLevel as $levelName => $level) {
            $text .= Design::erstelleVersteckteEingabezeile($console, $data['LOGGER']['log_level_'.$levelName], 'data[LOGGER][log_level_'.$levelName.']', '_', true);
        }
        echo $text;
        self::$initialized = true;
        Installation::log(array('text'=>Language::Get('main','functionEnd')));
    }
}
#endregion LoggerSegment