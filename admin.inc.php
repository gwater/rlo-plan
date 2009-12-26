<?php

/* FIXME: The user might use their ID
 * This will obviously manufactor problems at a rapid speed
 * we must get rid of ID -> name is a much better identifier.
 */
function update_user($user, $db) {
    if ($db->remove_user($user->id)) {
        $db->add_user($user);
        return true;
    }
    return false;
}


function evaluate_admin_request($db) {

    if (!is_authorized(VIEW_ADMIN)) {
        header('HTTP/1.0 401 Unauthorized');
        exit('you lack authorization');
    }

    switch ($_POST['action']) {
    case 'add':
        if (!(isset($_POST['name']) && isset($_POST['password']) && isset($_POST['role']))) {
            fail('parameter missing');
        }
        $user = new user($_POST);
        exit($db->add_user($user));
    case 'update':
        if (!(isset($_POST['id'])       && isset($_POST['name']) &&
              isset($_POST['password']) && isset($_POST['role']))) {
            fail('parameter missing');
        }
        $user = new user($_POST);
        if (update_user($user, $db)) {
            exit('updated');
        } else {
            fail('invalid data');
        }
    case 'delete':
        if (!(isset($_POST['id']) && is_numeric($_POST['id']))) {
            fail('invalid id');
        }
        if (!$db->remove_user($_POST['id'])) {
            fail('id not found');
        } else {
            exit('deleted');
        }
    default:
        fail('invalid action');
    }
}

?>