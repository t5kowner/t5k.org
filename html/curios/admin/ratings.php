<?php

# This page stores the ratings given by editors in the ratings table (curio_id, person_id, rating, id).
# This routine is  is not part of the "admin pages," but putting it here forces an authorization check.
# Call with $edit (the editor's id), and a list of 'rating$curio_id' => 'rating' pairs (so many rating
# may be done with each call).

$t_text = '';   # Will hold the text

include_once("../bin/basic.inc");
$db = basic_db_connect(); # Connects or dies

# First register the form variables:

$number_id = (isset($_REQUEST['number_id']) ? $_REQUEST['number_id'] : '');
$editor_id = (isset($_REQUEST['edit'])     ? $_REQUEST['edit'] : '');
if (!preg_match('/^\d*$/', $number_id)) {
    lib_die("\$number_id must be a small positive integer (was '$number_id')");
}
if (!preg_match('/^\d+$/', $editor_id)) {
    lib_die("\$edit must be your databse id (was '$editor_id').");
}

# Now let's keep the bad folks out (relying on .htaccess to verify the id, then we checj it has permissions)
include_once('permissions.inc');
if (empty($permissions_array['rate curios'][$editor_id]) or $permissions_array['view logs'][$editor_id] <> 'yes') {
    lib_die("You can not view this page without the proper authorization. [error: edit=$editor_id]");
}

# Do the work
include_once("../bin/log.inc");

foreach ($_REQUEST as $key => $val) {
   # ignore the number_id and edit values
    if ($key == 'number_id' or $key == 'edit') {
        continue;
    }

   # first lets validate this pair.
    if (!preg_match('/^rating(\d+)$/', $key, $temp) or !preg_match('/^(1|2|3|4|NULL)$/', $val)) {
        lib_warn("expecting 'rating\$id => \$rating' but got '$key => $val' (admin.ratings 39)");
        continue;
    }
    $curio_id = $temp[1];

   # Now we should see if there is a rating stored by this $edit'er for this editor/curio_id pair
    $sth = $db->query("SELECT count(*) AS num, rating, id FROM ratings WHERE person_id='$editor_id' AND curio_id='$curio_id'");
    if ($row = $sth->fetch() and $row['num'] > 0) {
        $id = $row['id'];
        print "<li> Found rating (id=$row[id]) of $row[rating] for curio_id = $curio_id by editor with id $editor_id";
        if ($val == $row['rating']) {
            print "<li> no change necessary";
            continue;
        }
    } else {
        $id = 0;
        print "<li> No rating found for curio_id = $curio_id by editor with id $editor_id";
    }

   // add new rating
    if ($id > 0 and $db->query("UPDATE ratings SET rating=$val WHERE person_id='$editor_id' AND curio_id='$curio_id'")) {
        print "<li> updated rating (id=$row[id]) to $val for curio_id = $curio_id by editor with id $editor_id";
        log_action($db, $editor_id, 'rated', "curios.id=$temp[1]", "altered rating to $val.");

    // modify old rating
    } elseif ($id == 0 and $db->query("INSERT INTO ratings (rating,person_id,curio_id) VALUES ($val,'$editor_id','$curio_id')")) {
        $id = $db->lastInsertId();
        print "<li> added new rating (id=$id) to $row[rating] for curio_id = $curio_id by editor with id $editor_id";
        log_action($db, $editor_id, 'rated', "curios.id=$temp[1]", "added rating of $val.");
    } else {
        log_action($db, $editor_id, 'warning', "curios.id=$temp[1]", "failed set rating to $val.");
    }
}
