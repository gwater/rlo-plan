<?php

/**
 * This file is part of RLO-Plan.
 *
 * Copyright 2009, 2010 Tillmann Karras, Josua Grawitter
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

session_start();
require_once('user.inc.php');
require_once('entry.inc.php');

$current_user = ovp_user_manager::get_current_user();
if (!$current_user->is_authorized(ovp_user::VIEW_ADMIN)) {
    header('HTTP/1.0 403 Access Denied');
    exit('Zugriff verweigert');
}

$json = array();

switch ($_GET['get']) {
case 'all':
    $json['users'] = get_users();
    $json['entries'] = get_entries();
    break;

case 'users':
    $json = get_users();
    break;

case 'entries':
    $json = get_entries();
    break;

default:
    $json['error'] = 'parameter missing';
}

exit(json_encode($json));

function get_users() {
    $result = array();
    $user_manager = ovp_user_manager::get_singleton();
    $users = $user_manager->get_all_users();
    foreach ($users as $user) {
        $result_user = array();
        foreach (array('id', 'name', 'privilege') as $attr) {
            $result_user[$attr] = $user->get($attr);
        }
        $result[] = $result_user;
    }
    return $result;
}

function get_entries() {
    $result = array();
    $entry_manager = ovp_entry_manager::get_singleton();
    if ($dates = $entry_manager->get_entries_by_date()) {
        foreach ($dates as $entries) {
            foreach ($entries as $entry) {
                $result[] = $entry->get_values();
            }
        }
    }
    return $result;
}

?>
