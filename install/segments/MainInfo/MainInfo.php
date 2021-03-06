<?php

/**
 * @file MainInfo.php
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL version 3
 *
 * @package OSTEPU (https://github.com/ostepu/system)
 * @since 0.3.3
 *
 * @author Till Uhlig <till.uhlig@student.uni-halle.de>
 * @date 2015-2016
 */
#region MainInfo
class MainInfo {

    public static $name = 'MainInfo';
    public static $installed = false; // ob die Installationsroutine ausgelöst wurde
    public static $page = 0; // die ID der Seite, auf welcher das Segment gezeigt werden soll
    public static $rank = 0; // bestimmt die Reihenfolge im Vergleich zu anderen Segmenten auf der selben Seite
    // niedriger Rank = fruehe Ausfuehrung, hoher Rank = spaetere Ausfuehrung
    public static $enabledShow = true; // ob die show() Funktion aufrufbar ist
    private static $langTemplate = 'MainInfo';
    public static $onEvents = array(
        '0' => array(
            'name' => 'MainInfo',
            'event' => array(
                'actionInstallMain', // vom Segment selbst gewaehlter Ausloeser
                'install', // bei der Installation
                'update', // bei einer Aktualisierung
                'page'), // beim Seitenaufruf
            'procedure' => 'install' // die im Installationsfall aufzurufende Funktion
        )
    );

    /**
     * initialisiert das Segment
     * @param type $console
     * @param string[][] $data die Serverdaten
     * @param bool $fail wenn ein Fehler auftritt, dann auf true setzen
     * @param string $errno im Fehlerfall kann hier eine Fehlernummer angegeben werden
     * @param string $error ein Fehlertext für den Fehlerfall
     */
    public static function init($console, &$data, &$fail, &$errno, &$error) {
        Installation::log(array('text' => Installation::Get('main', 'functionBegin')));
        Language::loadLanguageFile('de', self::$langTemplate, 'json', dirname(__FILE__) . '/');
        Installation::log(array('text' => Installation::Get('main', 'languageInstantiated')));
        Installation::log(array('text' => Installation::Get('main', 'functionEnd')));
    }

    public static function show($console, $result, $data) {
        if (!Einstellungen::$accessAllowed) {
            return;
        }

        Installation::log(array('text' => Installation::Get('main', 'functionBegin')));
        $text = '';
        $failure = false;
        $content = (isset($result[self::$onEvents['0']['name']]) ? $result[self::$onEvents['0']['name']] : null);

        if (!isset($content)) {
            $failure = true;
        } elseif (isset($content['status']) && $content['status'] == '412') {
            $text .= Design::erstelleZeile($console, Installation::Get('mainInfo', 'notWritable', self::$langTemplate), 'error');
            $failure = true;
        } else {
            Installation::log(array('text' => Installation::Get('mainInfo', 'positiveResult', self::$langTemplate)));
        }

        if ($failure) {
            echo Design::erstelleBlock($console, Installation::Get('mainInfo', 'title', self::$langTemplate), $text);
        }
        Installation::log(array('text' => Installation::Get('main', 'functionEnd')));
    }

    public static function install($data, &$fail, &$errno, &$error) {
        $res = array();
        Einstellungen::$path = $data['PL']['localPath'] . DIRECTORY_SEPARATOR . 'install' . DIRECTORY_SEPARATOR . 'config';
        Installation::log(array('text' => Installation::Get('mainInfo', 'checkPath', self::$langTemplate, array('path' => Einstellungen::$path))));
        Installation::log(array('text' => Installation::Get('mainInfo', 'checkFile', self::$langTemplate, array('file' => __FILE__))));
        if ((is_dir(Einstellungen::$path) && !is_dir(Einstellungen::$path)) || !is_writable(__FILE__)) {
            $res['status'] = 412;
            $fail = true;
            Installation::log(array('text' => Installation::Get('mainInfo', 'noWritePermission', self::$langTemplate), 'logLevel' => LogLevel::ERROR));
        } else {
            Installation::log(array('text' => Installation::Get('mainInfo', 'positiveResult', self::$langTemplate)));
        }

        return $res;
    }

}

#endregion MainInfo