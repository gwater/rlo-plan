<?php

require_once('db.inc.php');
require_once('user.inc.php');

function evaluate_admin_request($db) {

    if (!is_authorized(VIEW_ADMIN)) {
        header('HTTP/1.0 401 Unauthorized');
        exit('you lack authorization');
    }

    switch ($_POST['action']) {
    case 'add':
        if (isset($_POST['name']) && isset($_POST['password']) && isset($_POST['role'])) {
            $id = ovp_user::add($db, $_POST['name'], $_POST['password'], $_POST['role']);
            exit($id);
        }
        fail('parameter missing');
    case 'update':
        if (!isset($_POST['id'])) {
            fail('parameter missing');
        } else if (!is_numeric($_POST['id'])) {
            fail('invalid id');
        }
        $user = new ovp_user($db, $_POST['id']);
        $result = true;
        foreach ($_POST as $key => $value) {
            switch ($key) {
            case 'id':
            case 'asset':
            case 'action':
                break;
            case 'name':
                $result = $user->set_name($value);
                break;
            case 'password':
                $result = $user->set_password($value);
                break;
            case 'role':
                $result = $user->set_role($value);
                break;
            default:
                fail('invalid data');
            }
            if (!($result)) {
                fail('Why Luigi, why?');
            }
        }
        exit('updated');
    case 'delete':
        if (!isset($_POST['id'])) {
            fail('parameter missing');
        } else if (!is_numeric($_POST['id'])) {
            fail('invalid id');
        } else if (ovp_user::remove($db, $_POST['id'])) {
            exit('deleted');
        }
        fail('id not found');
    default:
        fail('invalid action');
    }
}

?>