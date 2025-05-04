<?php

# Each database will have this file to be edited by the administrator.
# Eventually could build this into the database; but bootstrapping a new
# setup could be hard.  This just contains the %permissions_array which
# is used by security.inc to decide who gets access to what.

# Security note: if a later routine tries to redefine a constant, it will not succeed
# but only generates a (usually invisible) warning (not an error)

define('Honaker', 2);
define('root', 131);
define('rmclean', 81);
# Basic format: $DatabaseName.$TableName => array of editors_ids;
# Use $DatabaseName.* to set for all tables in the database.
# See security.inc for more info.

$permissions_array = array(
    'curios.*'          => array(root => 'root', Honaker => 'admin', rmclean => "admin"),   # the defaults
    'curios.curios'     => array(Honaker => 'admin', root => 'root', rmclean => "admin"),
    'view logs'         => array(Honaker => 'yes', root => 'yes', rmclean => "yes"),
    'rate curios'       => array(Honaker => 'yes', root => 'yes', rmclean => "yes"),
    'change visibility' => array(Honaker => 'yes', root => 'yes', rmclean => "yes")
);
