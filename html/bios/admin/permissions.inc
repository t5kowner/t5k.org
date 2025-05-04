<?php

# Each database will have this file to be edited by the administrator.
# Eventually could build this into the database; but bootstrapping a new
# setup could be hard.  This just contains the %permissions_array which
# is used by security.inc to decide who gets access to what.

# Security note: if a later routine tries to redefine a constant, it will not succeed
# but only generates a (usually invisible) warning (not an error)

# Example: isset($permissions_array["$DatabaseName.*"]["$PersonID"]) ...

define('System', 254);
define('McLean', 4886);

# Basic format: $DatabaseName.$TableName => array of editors_ids;
# Use $DatabaseName.* to set for all tables in the database.
# See security.inc for more info.

# Added who can view logs here too, called by ../bin/log.php

$permissions_array = array(
    'primes.*'          =>  # defaults
    array(System => 'root', McLean => 'root'),
    'primes.meta_row'   => array(System => 'root', McLean => 'root'),
    'primes.meta_table' => array(System => 'root', McLean => 'root'),
    'primes.code'       => array(System => 'root', McLean => 'root'),
    'primes.prime_blob' => array(System => 'root', McLean => 'root'),
    'view logs'     => array(System => 'yes', McLean => 'root')
);
