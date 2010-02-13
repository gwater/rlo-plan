<?php

session_start();
require_once('user.inc.php');
require_once('entry.inc.php');

header('Content-Description: File Transfer');
header('Content-Disposition: attachment; filename="backup_'.date('Y-m-d').'.xml"');
header('Content-Type: application/xml; charset="utf-8"');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: '.date('D, d M Y H:i:s T', 0));

$user_manager = ovp_user_manager::get_singleton();
$current_user = $user_manager->get_current_user();
$current_user->authorize(ovp_user::VIEW_ADMIN);

$DOMDocument = new DOMDocument('1.0', 'UTF8');
$DOMBackup = $DOMDocument->createElement('backup');
$DOMDocument->appendChild($DOMBackup);
$DOMBackup->setAttribute('created', date('Y-m-d H:i:s P'));

$DOMUsers = $DOMDocument->createElement('users');
$DOMBackup->appendChild($DOMUsers);
$users = $user_manager->get_all_users();
foreach ($users as $user) {
    $DOMUser = $DOMDocument->createElement('user');
    $DOMUsers->appendChild($DOMUser);
    $DOMUser->setAttribute('id',        $user->get_id());
    $DOMUser->setAttribute('name',      $user->get_name());
    $DOMUser->setAttribute('pwd_hash',  $user->get_pwd_hash());
    $DOMUser->setAttribute('privilege', $user->get_privilege());
}

$DOMEntries = $DOMDocument->createElement('entries');
$DOMBackup->appendChild($DOMEntries);
$entry_manager = ovp_entry_manager::get_singleton();
if ($dates = $entry_manager->get_entries_by_date()) {
    foreach ($dates as $entries) {
        foreach ($entries as $entry) {
            $DOMEntry = $DOMDocument->createElement('entry');
            $DOMEntries->appendChild($DOMEntry);
            $DOMEntry->setAttribute('id', $entry->get_id());
            $values = $entry->get_values();
            foreach ($values as $key => $value) {
                $DOMEntry->setAttribute($key, $value);
            }
        }
    }
}

exit($DOMDocument->saveXML());

?>
