<?php

define('FIRST_RUN', true);            // this value is automatically set to false on first run
define('DEBUG', true);                 // true:  detailed error messages are shown
                                       // false: only very short or no error messages are shown
                                       // set this to false when uploading to IRL server!

define('DB_HOST', 'localhost');        // database server address
define('DB_BASE', '');                 // database name
define('DB_USER', '');                 // database user name
define('DB_PASS', '');                 // database password

define('DELETE_OLDER_THAN', -1);       // positive: entries older than DELETE_OLDER_THAN days are automatically deleted;
                                       // negative: never delete anything
define('SKIP_WEEKENDS', true);         // true: weekends are skipped when calculating the age of an entry,
                                       //       e.g. it's Monday, entry is from Friday, DELETE_OLDER_THAN = 1 --> entry is _not_ deleted;
                                       // false: weekends are just like every other day (this would suck for obvious reasons)
define('PRIV_DEFAULT', 1);             // default privilege level for logged out visitors, ovp_logger::PRIV_DEFAULT

?>
