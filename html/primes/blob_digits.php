<?php

include_once("bin/basic.inc");  # basic routines, ...
$db = basic_db_connect();   # Connects or dies

# This page displays the digits from the prime_blob with id $id

if (isset($_REQUEST['id']) and is_numeric($_REQUEST['id'])) {
    $id = $_REQUEST['id'];
} else {
    lib_die('blob_digits.php must be passed an id');
}

$digits = lib_get_column("id='$id'", 'prime_blob', 'full_digit', $db);

if ($digits) {
    echo preg_replace('/\s/', '', $digits);
} else {
    lib_mysql_die('Failed to get row from prime_blob', $query2);
}
