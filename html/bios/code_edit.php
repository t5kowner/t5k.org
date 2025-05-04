<?php

#t# ready.

$t_submenu =  "code_edit";   # added to breadcrumbs menu

// This is to edit a prover code, and just the comment field is editable at this time.
// Such comments are required ofr x-codes.
//
//  $xx_person_id   Who is editing? (person.id)
//  $code_str   What is the name of the code we are editing


// Okay, load up the include files
include_once("bin/basic.inc");
include_once("bin/log.inc");
$db = basic_db_connect(); # Connects or dies

// Set some page variables

$t_title = 'Edit Comment on Prover-code';
$t_allow_cache = 'yes!';

// Get the form variables
$xx_person_id = (isset($_REQUEST['xx_person_id']) ? htmlentities($_REQUEST['xx_person_id']) : '');
$code_str  = (isset($_REQUEST['code_str'])  ? htmlentities($_REQUEST['code_str'])  : '');
$xx_action = (isset($_REQUEST['xx_action']) ? htmlentities($_REQUEST['xx_action']) : '');

// Use them to set the global $get_the_codes_id.  These also two get_the_...
// routines add form fields to the global $out.

$get_the_person_query = '<p class="mt-4">Who is editing this comment (must be a human owner of the code)?';
$get_the_person_only  = 'person';       # Will limit submitter's to humans
include("bin/get_the_person.inc");      # Grab function "get_the_person"

$get_the_code_query = '<p class="mt-4">Enter the proof-code %s which you would like to edit.</p>';
include("bin/get_the_code.inc");        # Grab the function "get_the_code"

// These routines return 1 if all is well
$out = "<form method=post action=\"$_SERVER[PHP_SELF]\">\n"; # Building the text in $out
$done = get_the_person();
$done *= get_the_code();

// If something is wrong with what we were passed, ask again
if (!$done) {
    $t_text = "<p>This page is to edit the comment field of a code that you own.</p>
	$GLOBALS[out] <input type=\"submit\" class=\"btn btn-primary mb-4\" name=xx_action value=\"Edit Entry\"></form>
        <p>A password will be required before submission is completed.</p>\n";
    include("template.php");
    exit;
}

// the generic_edit routines use a parameter array, prepare it:
$parameters = array('entry_id' => $get_the_codes_id,
        'editors_id' => $xx_person_id,
        'database_name' => basic_DatabaseName(),
        'table_name' => 'code',
    'page_title' => 'Edit Comment on Prover-code',
    'extra_for_form' =>  lib_hidden_field('xx_person_id', $xx_person_id) .
        lib_hidden_field('code_str', $code_str),
    'put_row_silent' => 1);

// two options left: we are editing or returning fron an edit screen
if (isset($xx_action) and $xx_action == 'put') {      # returning from an edit form, put in db
    include('admin/edit.inc');
    update_database_screen(generic_put_row_by_id($parameters));
    include("template.php");
} else {
    include('admin/edit.inc');
    generic_show_edit_table($parameters, '');
    echo ReturnButtons('index', 'page');
}

exit;

##### Support routines

function ReturnButtons()
{
  # Make index and page not empty to show the corresponding links
    $out = "<P><form method=post action=\"../bios/page.php?id=$GLOBALS[xx_person_id]\">
	<input type=submit class=\"btn btn-primary p-2 mb-4\" value=\"Return to Your Prover-Account Page\"></form>";
    return $out;
}

#####  Now the various "screens"

function edit_button($field_name)
{
    return "\n &nbsp; &nbsp; &nbsp; <input type=submit value=change name=\"edit_$field_name\">\n";
}




function update_database_screen($success)
{
    global $t_title, $t_text;
    $t_title .= ($success == 1 ? 'Database Updated' : '<font color=red>Update Failure!</font>');

    if ($success == 1) {
        $t_text = 'The database has been successfully updated.';
    } elseif ($success == -1) {
        $t_text = "<font color=red size=+1>Database unchanged.</font>\n";
    } else {
        exit;  # database update returns 0 when reprinting the edit screen!
    }
    $t_text .= "
	<blockquote>
  	  <form method=post action=\"code.php?code=$GLOBALS[code_str]\">
	    <input type=submit class=\"btn btn-primary p-2 mb-4\" value=\"" .
        sprintf('Return to the %s Page', $GLOBALS['code_str']) . "\">
	  </form>
  	  <form method=post action=\"page.php?id=$GLOBALS[xx_person_id]\">
	    <input type=submit class=\"btn btn-primary p-2 mb-4\" value=\"Return to Your Prover-Account Page\">
	  </form>
	</blockquote>\n";
}
