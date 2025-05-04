<?php

# This page is called by ../page.php to allow editors to change the visibility of entries.
#
#     visible.php?edit=xx&curio_id=xx&visibile=xx
#
# where visible is yes or no.
# The plan is to show no output and return to previous window.

# It is not part of the admin pages, but putting it here adds the authorization check.

include_once("../bin/basic.inc");
$db = basic_db_connect(); # Connects or dies

# Register the form variables:

$edit      = (isset($_REQUEST['edit'])     ? $_REQUEST['edit'] : '');
if (!preg_match('/^\d+$/', $edit)) {
    lib_die("\$edit must be set and validated.");
}
$curio_id  = (isset($_REQUEST['curio_id'])  ? $_REQUEST['curio_id'] : '');
if (!preg_match('/^\d+$/', $curio_id)) {
    lib_die("curio_id was not a positive integer");
}
$visible   = ((isset($_REQUEST['set_visible']) and preg_match('/^(yes|no)$/', $_REQUEST['set_visible'])) ? $_REQUEST['set_visible'] : 'no');

# Now let's keep the bad folks out (relying on .htaccess :( )
include_once('permissions.inc');
if (empty($permissions_array['change visibility'][$edit]) or $permissions_array['view logs'][$edit] <> 'yes') {
    lib_die("You can not view this page without the proper authorization. [error: edit=$edit]");
}

# Given a table name, sets the 'visible' field of the record with id=$curio_id

include_once("../bin/log.inc");
$query = "UPDATE curios SET visible='$visible' WHERE id=$curio_id;";
lib_mysql_query($query, $db, "set visible=$visibile for curio_id=$curio_id failed");
log_action($db, $edit, 'visibility', "curios.id=$curio_id", "visibility changed to $visible");
# print "Curio $curio_id has been set to visible.\n";

header("Location: ../page.php?edit=$edit&curio_id=$curio_id");
