<?php

define('FIRST_RUN', false);            // this value is automatically set to false on first run
define('DEBUG', true);                 // true:  detailed error messages are shown
                                       // false: only very short or no error messages are shown
                                       // set this to false when uploading to IRL server!

define('DB_HOST', 'localhost');        // database server address
define('DB_BASE', 'd00c8bc6');         // database name
define('DB_USER', 'd00c8bc6');         // database user name
define('DB_PASS', 'kissingenstrasse'); // database password

define('DELETE_OLDER_THAN', -1);       // positive: entries older than DELETE_OLDER_THAN days are automatically deleted;
                                       // negative: never delete anything
define('SKIP_WEEKENDS', true);         // true: weekends are skipped when calculating the age of an entry,
                                       //       e.g. it's Monday, entry is from Friday, DELETE_OLDER_THAN = 1 --> entry is _not_ deleted;
                                       // false: weekends are just like every other day (this would suck for obvious reasons)

define('ADMIN_PWD', hash('sha256', 'nimda'));

// privilege levels required to view a particular page:
define('VIEW_NONE',    0);
define('VIEW_PUBLIC',  1);
define('VIEW_PRINT',   2);
define('VIEW_AUTHOR',  3);
define('VIEW_ADMIN',   4);
define('PRIV_DEFAULT', 0); // default privilege level for logged out visitors

?>
