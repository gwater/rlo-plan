<?php

/**
 * This file is part of RLO-Plan.
 *
 * Copyright 2009 Tillmann Karras, Josua Grawitter
 *
 * RLO-Plan is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * RLO-Plan is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with RLO-Plan.  If not, see <http://www.gnu.org/licenses/>.
 */

// TODO: move this page's HTML code into a new class "ovp_wizard" in html.inc.php

require_once('db.inc.php');
require_once('user.inc.php');
require_once('html.inc.php');

/* authorization placeholder */

$html = '';
$config = 'config.inc.php';
$temp = 'config.inc.tmp';
switch ($_GET['page']) {
case 'mysql':
    switch ($_GET['action']) {
    case 'save':
        if (!(isset($_POST['host']) && isset($_POST['base']) && isset($_POST['user']) && isset($_POST['pass']))) {
            die('ERROR: post parameter missing');
        }
        set('DB_HOST', "'".$_POST['host']."'");
        set('DB_BASE', "'".$_POST['base']."'");
        set('DB_USER', "'".$_POST['user']."'");
        set('DB_PASS', "'".$_POST['pass']."'");
        if ($error = db::check_creds($_POST['host'], $_POST['base'], $_POST['user'], $_POST['pass'])) {
            goto_page('mysql&error='.urlencode($error));
        }
        goto_page('settings');
        break;
    default:
        if (isset($_GET['error'])) {
            $html .= '<p><span class="ovp_error">ERROR: '.$_GET['error'].'</span></p>';
        }
        $html .= '
            <form action="'.$_SERVER['SCRIPT_NAME'].'?page=mysql&action=save" method="POST"><table>
                <tr><td>Server</td><td><input type="text" name="host" value="'.get('DB_HOST').'"></td></tr>
                <tr><td>Datenbank</td><td><input type="text" name="base" value="'.get('DB_BASE').'"></td></tr>
                <tr><td>Benutzer</td><td><input type="text" name="user" value="'.get('DB_USER').'"></td></tr>
                <tr><td>Passwort</td><td><input type="text" name="pass" value="'.get('DB_PASS').'"></td></tr>
                <tr><td></td><td><input type="submit" value="Speichern und weiter"></td></tr>
            </table></form>';
    }
    break;
case 'settings':
    switch ($_GET['action']) {
    case 'save':
        if (!(isset($_POST['debug']) && isset($_POST['delold']) && isset($_POST['skipweekends']) && isset($_POST['privdefault']))) {
            die('ERROR: post parameter missing');
        }
        set('DEBUG',             $_POST['debug']);
        set('DELETE_OLDER_THAN', $_POST['delold']);
        set('SKIP_WEEKENDS',     $_POST['skipweekends']);
        set('PRIV_DEFAULT',      $_POST['privdefault']);
        goto_page('admin');
        break;
    default:
        if (isset($_GET['error'])) {
            $html .= '<p><span class="ovp_error">ERROR: '.$_GET['error'].'</span></p>';
        }
        $debug         = get('DEBUG');
        $skip_weekends = get('SKIP_WEEKENDS');
        $priv_default  = get('PRIV_DEFAULT');
        $html .= '
            <form action="'.$_SERVER['SCRIPT_NAME'].'?page=settings&action=save" method="POST"><table>
                <tr>
                  <td>Sollen detaillierte Fehlermeldungen angezeigt werden?</td>
                  <td><select name="debug">
                    <option value="true"'.($debug == 'true' ? ' selected="selected"' : '').'>Ja</option>
                    <option value="false"'.($debug == 'false' ? ' selected="selected"' : '').'>Nein</option>
                  </select></td>
                </tr>
                <tr>
                  <td>Nach wie vielen Tagen sollen alte Einträge automatisch gelöscht werden? (-1 = nie)</td>
                  <td><input type="text" name="delold" value="'.get('DELETE_OLDER_THAN').'"></td>
                </tr>
                <tr>
                  <td>Sollen Wochenenden dabei <i>nicht</i> mitzählen?</td>
                  <td><select name="skipweekends">
                    <option value="true"'.($skip_weekends == 'true' ? ' selected="selected"' : '').'>Ja</option>
                    <option value="false"'.($skip_weekends == 'false' ? ' selected="selected"' : '').'>Nein</option>
                  </select></td>
                </tr>
                <tr>
                  <td>Welches Privileg sollen unangemeldete Besucher besitzen?</td>
                  <td><select name="privdefault">
                    <option value="0"'.($priv_default == '0' ? ' selected="selected"' : '').'>Keins</option>
                    <option value="1"'.($priv_default == '1' ? ' selected="selected"' : '').'>Public</option>
                    <option value="2"'.($priv_default == '2' ? ' selected="selected"' : '').'>Print</option>
                    <option value="3"'.($priv_default == '3' ? ' selected="selected"' : '').'>Autor</option>
                    <option value="4"'.($priv_default == '4' ? ' selected="selected"' : '').'>Admin</option>
                  </select></td>
                </tr>
                <tr><td></td><td><input type="submit" value="Speichern und weiter"></td></tr>
            </table></form>';
    }
    break;
case 'admin':
    switch ($_GET['action']) {
    case 'save':
        if (!(isset($_POST['name']) && isset($_POST['pwd']))) {
            die('ERROR: post parameter missing');
        }
        set('FIRST_RUN', 'true'); // this causes the database tables to be reset!
        if (file_exists($temp)) {
            unlink($config);
            rename($temp, $config);
        }
        // HACKHACK: what this does is "re-evaluate" db.inc.php
        session_start();
        $_SESSION['name'] = $_POST['name'];
        $_SESSION['pwd']  = $_POST['pwd'];
        goto_page('admin&action=save2');
        break;
    case 'save2':
        session_start();
        $_POST['name'] = $_SESSION['name'];
        $_POST['pwd']  = $_SESSION['pwd'];
        session_destroy();
        // END OF HACK
        $db = new db();
        $error = '';
        if (ovp_user::name_exists($db, $_POST['name'])) {
            $user = ovp_user::get_user_by_name($db, $_POST['name']);
            $user->set_password($_POST['pwd']);
        } else {
            ovp_user::add($db, $_POST['name'], $_POST['pwd'], 'admin');
        }
        goto_page('done');
        break;
    default:
        $html .= '
            <form action="'.$_SERVER['SCRIPT_NAME'].'?page=admin&action=save" method="POST"><table>
                <tr><td>Benutzer</td><td><input type="text" name="name" value="admin">
                <tr><td>Passwort</td><td><input type="password" name="pwd" value="">
                <tr><td></td><td><input type="submit" value="Speichern und weiter">
            </table></form>';
    }
    break;
case 'done':
    if (basename($_SERVER['SCRIPT_NAME']) == 'index.php') {
        rename('index.php', 'wizard.php');
        rename('index_.php', 'index.php');
    }
    $wizard = file_get_contents('wizard.php');
    $replacement = '$logger = new ovp_logger(new db()); $logger->authorize(ovp_logger::VIEW_ADMIN);';
    $wizard = preg_replace('|/\* authorization placeholder \*/|', $replacement, $wizard, 1);
    file_put_contents('wizard.php', $wizard);
    $html .= '<p>Sie können jetzt die <a href="index.php">Startseite</a> öffnen.</p>';
    break;
default:
    if (file_exists($temp)) {
        unlink($temp); // the wizard was restarted, discard unsaved changes
    }
    ovp_zipper::pack_dir();
    copy($config, $temp);
    goto_page('mysql');
    break;
}
exit('<html><head><link rel="stylesheet" href="style.css" type="text/css"></head><body class="ovp_container">'.menu().$html.'</body></html>');

function get($define) {
    global $temp;
    $text = file_get_contents($temp);
    if (preg_match('/(?<=define\(\''.$define.'\', ).+?(?=\);)/i', $text, $matches) == 0) {
        die('ERROR: define ' + $define  + ' not found');
    }
    return trim($matches[0], "'");
}

function set($define, $value) {
    global $temp;
    $text = file_get_contents($temp);
    $text = preg_replace('/(?<=define\(\''.$define.'\', ).+?(?=\);)/i', $value, $text, 1);
    file_put_contents($temp, $text);
}

function menu() {
    $html = '<div id="ovp_navi"><ol>';
    $pages = array('mysql' => 'MySQL Credentials', 'settings' => 'Misc. Settings', 'admin' => 'Admin Account', 'done' => 'Save and Clean Up');
    foreach ($pages as $page => $title) {
        if ($_GET['page'] == $page) {
            $html .= '<li><b>'.$title.'</b></li><br>';
        } else {
            $html .= '<li><a href="'.basename($_SERVER['SCRIPT_NAME']).'?page='.$page.'">'.$title.'</a></li><br>';
        }
    }
    return $html.'</ul></div>';
}

function goto_page($page) {
    ovp_logger::redirect(basename($_SERVER['SCRIPT_NAME']).($page != '' ? '?page='.$page : ''));
}

?>