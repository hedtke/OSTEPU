<?php

/**
 * @file Serverliste.php
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL version 3
 *
 * @package OSTEPU (https://github.com/ostepu/system)
 * @since 0.3.5
 *
 * @author Till Uhlig <till.uhlig@student.uni-halle.de>
 * @date 2015-2016
 */
#region Serverliste
class Serverliste {

    public static $name = 'servers';
    public static $enabledShow = true;
    public static $rank = 100;
    private static $langTemplate = 'Serverliste';

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

    public static function showInfoBar(&$data) {
        Installation::log(array('text' => Installation::Get('main', 'functionBegin')));
        Language::loadLanguageFile('de', self::$langTemplate, 'json', dirname(__FILE__) . '/');
        Installation::log(array('text' => Installation::Get('main', 'languageInstantiated')));

        echo "<tr><td class='e'>" . Installation::Get('serverList', 'serverList', self::$langTemplate) . "</td></tr>";
        foreach (Einstellungen::$serverFiles as $serverFile) {
            $file = pathinfo($serverFile)['filename'];
            echo "<tr><td class='v'>" . Design::erstelleSubmitButtonFlach('server', $file, (Einstellungen::$selected_server == $file ? '<font color="maroon">' . $file . '</font>' : $file)) . "</td></tr>";
        }

        if (Einstellungen::$accessAllowed) {
            echo "<tr><td class='v'>" . Design::erstelleSubmitButtonFlach('actionAddServer', 'OK', Installation::Get('serverList', 'addServer', self::$langTemplate) . ">") . "</td></tr>";
        }
        echo Design::erstelleVersteckteEingabezeile(false, Einstellungen::$selected_server, 'selected_server', null);
        Installation::log(array('text' => Installation::Get('main', 'functionEnd')));
    }

}

#endregion Serverliste