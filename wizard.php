<?php

// TODO: move this page's HTML code into a new class "ovp_wizard" in html.inc.php

require_once('html.inc.php');

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
    goto_page('admin');
    break;
case 'admin':
    switch ($_GET['action']) {
    case 'save':
        // TODO
        goto_page('done');
        break;
    default:
        $html .= '
            <form action="'.$_SERVER['SCRIPT_NAME'].'?page=admin&action=save" method="POST"><table>
                <tr><td>Benutzer</td><td><input type="text" name="name" value="admin" disabled="true">
                <tr><td>Passwort</td><td><input type="password" name="pass" value="">
                <tr><td></td><td><input type="submit" value="Speichern und weiter">
            </table></form>';
    }
    break;
case 'done':
    switch ($_GET['action']) {
    case 'save':
        // TODO
        break;
    default:
        // TODO
    }
    break;
default:
    if (file_exists($temp)) {
        unlink($temp); // the wizard was restarted, discard unsaved changes
    }
    // TODO: pack sources
    copy($config, $temp);
    goto_page('mysql');
    break;
}
exit('<html><head><link rel="stylesheet" href="style.css" type="text/css"></head><body>'.menu().$html.'</body></html>');

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
    $html = '<div id="ovp_wiz_nav"><ol>';
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
    ovp_logger::redirect(basename($_SERVER['SCRIPT_NAME']).'?page='.$page);
}

?>