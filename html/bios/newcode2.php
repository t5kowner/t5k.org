<?php

#t# ready

$t_submenu =  "new Code";

# Part 2 of 2 : Confirmation and Database Altering Page

# These pages allows us to create new proof-codes.  The person must already
# be defined and a password is necessary...  If possible I'd like to also use it
# to edit an existing code.

# At this stage I expect to have a list of prover id's ($complete_list_of_ids)
# and a primary id ($xx_person_id) (one of the list) from the other progam and if
# confirmed, $confirmed will also be set.

include_once("bin/basic.inc");
$db = basic_db_connect();   # Connects or dies

include_once("newcode.inc");
include_once("bin/log.inc");

# we should make sure they are authorized.  This must come first as it alters the
# http headers!

include_once('bin/http_auth.inc');
if (isset($_REQUEST['xx_person_id'])) {
    $xx_person_id = htmlentities($_REQUEST['xx_person_id']);
    if (preg_match('/^\d+$/', $xx_person_id)) {
        $is_authorized = my_auth($xx_person_id);
    }
} else {
    $xx_person_id = 'not set';
    $is_authorized = 0;
}

# Okay, let's start

if (is_numeric($xx_person_id) && !$is_authorized) {
    $out = "<p>You are not authorized to act as this person! (id = $xx_person_id)</p>";
} elseif (
    !is_numeric($xx_person_id) or empty($_REQUEST['complete_list_of_ids'])
    or empty($_REQUEST['prog_prefix'])
) {
    $out = "To create a new proof-code, please begin with the page <a href=\"newcode.php\">newcode.php</a> ";
  # $out .= "(is_numeric($xx_person_id) or empty($_REQUEST[complete_list_of_ids])
  #  or empty($_REQUEST[prog_prefix]))";
} else {
    $out = '';    # Building the text in $out

  # First lets check out the complete list of ids--we will use the printing
  # routine to make sure there are no duplicates and set some globals for use
  # later

    $ids = htmlentities($_REQUEST['complete_list_of_ids']);
    $temp = show_list_of_ids($ids);
    $ids = $GLOBALS['show_list_of_ids_list'];
    $display_text = $GLOBALS['show_list_of_ids_text'];
    $display_html = $GLOBALS['show_list_of_ids_html'];
    $display_short = $GLOBALS['show_list_of_ids_short'];

# log_action($db, $xx_person_id, 'created', "code.name=$code", "(newcode2 line 64): ids: $ids.");

# $display_text  = mysql_real_escape_string($display_text);
# $display_html  = mysql_real_escape_string($display_html);
# $display_short = mysql_real_escape_string($display_short);

    $persons = $GLOBALS['show_list_of_ids_persons'];
    if (!empty($GLOBALS['show_list_of_ids_errors'])) {
        lib_die("illegal list of ids<P>$GLOBALS[show_list_of_ids_errors]");
    }

  # Let's make sure it is not a duplicate (relying on the fact that
  # $show_list_of_ids_text = $display_text is canonically sorted).
  # That is the tricky thing though, canonical sorting--must match
  # the perl routine buildcodes.

    if (
        $name = lib_get_column(
            "display_text='$display_text'",
            'code',
            'name',
            $GLOBALS['db']
        )
    ) {
        log_action(
            $db,
            $xx_person_id,
            'warning',
            "code.name=$name",
            'attempted to duplicate old code'
        );
      # also log the ip for future use
        http_auth_log_ip_error($xx_person_id, 1, '', '(omitted)', 'duplicate code');
        $t_text = '<div class="alert alert-danger m-5 bold" role="alert">' .
        "You already have a code with these members. Please use your existing code:
	<a href=\"code.php?code=$name\" class=\"alert-link\">$name</a>.</div>";
        $t_title = "ERROR: Duplicate Proof-Code";
        include("template.php");
        exit;
    }

  # Now get the list of those automatically included by this prefix

    $prefix = htmlentities($_REQUEST['prog_prefix']);
    $also = also_credits($prefix, '99999');
  # $out .= "need to omit ".show_list_of_ids($also);
    foreach (preg_split('/,\s*/', $also) as $omit) {
        if (empty($omit)) {
            continue; # When $also is empty, there is an empty $omit here
        }
        $ids = preg_replace('/(^|,\s*)' . $omit . '\b/', '', $ids);
    }
  # $out .= "leaving".show_list_of_ids($ids);

  # Now $also includes the automatically listed members of $prefix and $id the ones
  # the program needs to individually add to the database.  So lets generate a code
  # and tell them what we are doing:

# log_action($db, $xx_person_id, 'created', "code.name=$code", "(newcode2 line 108): ids: $ids.");

  # $out .= "prefix is $prefix. ";
    $temp1 = show_list_of_ids($ids);  # These three are relatively time consuming
    if (!empty($also)) {
        $temp2 = show_list_of_ids($also);
    }
    $code = generate_code($prefix);

  # One final piece of info to store in the new code: the prover program
    $program_id = lib_get_column("prog_prefix='$prefix'", 'person', 'id', $GLOBALS['db']);
    if (!preg_match('/^\d+$/', $program_id)) {
        lib_die("newcode2.php failed got \$program_id='$program_id' with \"prog_prefix='$prefix'\"");
    }

    $out .= '<h2 class="my-3">' . "The new proof-code is <font color=red><b><a href=\"code.php?code=$code\">$code</a></b></font>
	and credits:</h2> $temp1\n";
    if (!empty($also)) {
        $out .= "<p class=\"my-3\">This code will also automatically credit:</p><p> $temp2";
    }
    $out .= "</p>\n<p  class=\"my-3\"><b>To add primes, use the buttons to submit primes
	that can be found on both your prover-account page and on the page for this new
	code (use the link above).</b>";

  # Okay, let's actually do it!  Two steps: create a code entry and modify prover entries
  #
  #  This next code should closely parallel the Perl script buildcodes which build_codes
  #  from the database of provers using REPLACE.  So we first build the necessary credit
  #  strings (build_codes will overwrite these--but why require it be run?)

  # lib_add_row cannot return an id here because there is no auto-increment id
  # field in the code table.  But it will insert the new record or die (with a message)


    $query = "INSERT INTO code (name, display_text, display_html, display_short, persons, program_id)
	VALUES ('$code', '$display_text', '$display_html', '$display_short', $persons, $program_id)";
    try {
        $sth = $db -> prepare($query);
  #################### FIX ##############################
  #   $sth->bindValue(':code', $code, PDO::PARAM_STR);
  #   $sth->bindValue(':display_text',  $display_text, PDO::PARAM_STR);
  #   $sth->bindValue(':display_html',  $display_html, PDO::PARAM_STR);
  #   $sth->bindValue(':display_short', $display_short, PDO::PARAM_STR);
  #   $sth->bindValue(':persons', $persons, PDO::PARAM_INT);
  #   $sth->bindValue(':program_id', $program_id, PDO::PARAM_INT);
        $sth->execute();
        $code_id = $sth->rowCount();
    } catch (PDOException $ex) {
        lib_mysql_die('failed to create code (error newcode2 150): ' . $ex->getMessage(), $query);
    }

#  $code_id = lib_add_row('code',"(name, display_text, display_html, display_short, persons, program_id)
#   VALUES ('$code', '$display_text', '$display_html', '$display_short', $persons, $program_id)");


  # $_SERVER[PHP_AUTH_USER] Say something if editor does it?
    log_action(
        $db,
        $xx_person_id,
        'created',
        "code.name=$code",
        "(step 1 of 2): $display_html, $persons person(s); ids: $ids."
    );
  # also log the ip for future use
  # http_auth_log_ip_error($id,$penalty='',$who='',$passwd='',$comment='')
    http_auth_log_ip_error($xx_person_id, 1, '', '(omitted)', 'new code');

  # Now we have some provers to modify.  Let's do them all at once; omitting only
  # those already belonging to this code (perhaps we are editing not creating?)

    $ids = preg_replace('/\s+/', '', $ids);  # Need no spaces for this next command because of the FIND_IN_SET.
  # This IF works correctly for NULL and empty strings--by repeating ',' zero times; else once.
    $query = "UPDATE person SET codes=CONCAT(IFNULL(codes,''),IF(LENGTH(codes),',',''),:code) WHERE
	FIND_IN_SET(id,:ids) AND
 	NOT ( IFNULL(codes,'') REGEXP BINARY '\\\\b$code\\\\b')";
    try {
        $sth = $db -> prepare($query);
        $sth->bindValue(':code', $code);
        $sth->bindValue(':ids', $ids);
        $sth->execute();
        $updated = $sth->rowCount();
    } catch (PDOException $ex) {
        lib_mysql_die('failed to update code (error newcode2 180): ' . $ex->getMessage(), $query);
    }
    log_action($db, $xx_person_id, 'modified', "person.codes=$ids", '(step 2 of 2) ' . $query);

    $out .= "&nbsp; Remember that all unused codes will be deleted after 24 hours, so do not
	create a proof-code until you need it.<br>
	<div class=\"technote p-2 my-5\">The database 'code' table has been updated with the new item:
	<a href=\"code.php?code=$code\" class=\"none\">$code</a>.&nbsp;
	Note $updated database table prime.person entries have been updated.</div>\n";
}

# Set up variables for the template
$t_text = $out;
$t_title = "New Proof-Code";

include("template.php");

// generate_code($prefix) takes a program prefix (like g, p, x, SB...) and generates the
// first code of the form $prefix$n where $n is a positive integer that is not in the database.
// (e.g., given g it might respond 'g234')

function generate_code($prefix)
{
    if (empty($prefix)) {
        lib_die("generate_code was not passed a prefix");
    }
    $n = 1;
    while (lib_rows_in_table('code', $GLOBALS['db'], "name='$prefix$n'")) {
        $n++;
    }
    return "$prefix$n";
}
