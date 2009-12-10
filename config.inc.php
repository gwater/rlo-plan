<?php

define('DEBUG', 'TRUE');               // TRUE:  detailed error messages are shown
                                       // FALSE: only very short or no error messages are shown
                                       // set this to FALSE when uploading to IRL server!

define('DB_HOST', 'localhost');        // database server address
define('DB_BASE', 'd00c8bc6');         // database name
define('DB_USER', 'd00c8bc6');         // database user name
define('DB_PASS', 'kissingenstrasse'); // database password

define('DELETE_OLDER_THAN', -1);       // positive: entries older than DELETE_PAST days are automatically deleted;
                                       // negative: never delete anything
define('ADMIN_PWD', hash('sha256', 'lange'));

// privilege levels required for a specific action:
define('NO_RIGHTS',     0);
define('VIEW_DATA',     1); // allowed to view all data except for teacher names
define('VIEW_ALL_DATA', 2);
define('MODIFY_DATA',   3);
define('MODIFY_USERS',  4);
define('DEFAULT_PRIV',  NO_RIGHTS); // default privilege level for logged out visitors

?>
