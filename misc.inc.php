<?php

function fail($msg) {
    header('HTTP/1.0 400 Bad Request');
    exit($msg);
}

?>
