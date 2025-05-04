<?php

#t# ready

# show_list_of_ids($list)   shows a list of id's, removing duplicates, setting globals...
# also_credits($prefix,$id) create a list of id's omitting $id

function show_list_of_ids($list)
{
// Will return a displayable list of ids linked to bios... with duplicates removed
// and errors omitted.  The list may mix last names and id's and have duplicates.
// A global string $show_list_of_ids_errors will describe the errors (if any)
// and show_list_of_ids_list will contain a neat list (no duplicates...)
// Also $show_list_of_ids_prefix is a comma delimited list of the prog_prefix'es
// of these
    global $show_list_of_ids_errors, $show_list_of_ids_list, $show_list_of_ids_prefix;
    global $show_list_of_ids_text, $show_list_of_ids_html, $show_list_of_ids_persons;
    global $show_list_of_ids_short;

    $show_list_of_ids_errors = '';    # Main program in newcode.php will use these first three
    $show_list_of_ids_list   = '';
    $show_list_of_ids_prefix = '';
    $show_list_of_ids_text   = '';    # Main program in newcode2.php will use these next four
    $show_list_of_ids_html   = '';
    $show_list_of_ids_short  = '';
    $show_list_of_ids_persons = 0;

  # Okay, lets start by processing the list to make a new one of only id's
  # and comma delimited (no spaces) like MySQL likes its sets.  We need to look up
  # any non-numbers as they should be 'username's in the table 'person'.  Going one item
  # at a time allows more informative error messages.

    $delim = '';
    $new_list = '';
    $say_once = "A biographical entry for the person must exist before you may use their username while generating a new code.";
    foreach (preg_split("/,\s*/", $list) as $item) {
        if (empty($item)) {
            continue;
        }

      # step one: get the numerical id (might already be one)
        if (preg_match("/^\d+$/", $item)) {
            $id = $item;
        } else {
            $temp = basic_get_person_from_username($item, 'id', 'also check surnames');
            $id = $temp['id'];
            if (empty($temp)) {
                $show_list_of_ids_errors .= '<li>' . sprintf(
                    "Sorry, but there is no database entry with the username %s.",
                    "'$item'"
                ) . "&nbsp; $say_once\n";
                $say_once = '';
                continue;
            }
        }
      # Since we are here the id is set

      # step two: make sure it is not a duplicate
        if (preg_match("/\b$id\b/", $show_list_of_ids_list)) {
            continue;
        }

      # step four: get the data
        $temp = basic_get_person_from_id($id, 'username,name,type,prog_prefix', $GLOBALS['db']);
        if (empty($temp)) {
            $show_list_of_ids_errors .= '<li>' . sprintf(
                "Sorry, but there is no database entry with id %d.",
                "'$item'"
            ) . "&nbsp; $say_once";
            $say_once = '';
            continue;
        }

      # step 5: update the list
        $new_list .= $delim . $id;
        $delim = ',';
    }

  # Now lets setup the output strings (the sort order should match that in
  # support/GetCodes.pm)

    $query = "SELECT id,surname,name,type,prog_prefix
	from person where FIND_IN_SET(id,'$new_list')
	order by IF(wild_codes IS NULL,0,1), type+0, created DESC";
    $sth = lib_mysql_query(
        $query,
        $GLOBALS['db'],
        'error newcode.inc 74'
    );

    $output = '';             # The return string is built here
    $delim = '';
    $delim2 = '';

    while ($temp = $sth->fetch(PDO::FETCH_ASSOC)) {
        $output .= "<li><a href=\"page.php?id=$temp[id]\">$temp[surname]</a> (#$temp[id]) : " .
        "$temp[name] ($temp[type])\n";
        $show_list_of_ids_list   .= $delim . $temp['id'];
        $show_list_of_ids_prefix .= $delim . $temp['prog_prefix'];
        $show_list_of_ids_text   .= $delim2 . $temp['surname'];
        if ($temp['type'] == 'person') {
            $show_list_of_ids_short .= $delim2 . $temp['surname'];
        }
        $show_list_of_ids_html   .= "$delim2<a href=\"/bios/page.php?id=$temp[id]\">$temp[surname]</a>";
        if ($temp['type'] == 'person') {
            $show_list_of_ids_persons++;
        }
        $delim = ',';
        $delim2 = ', ';
    }
  # For the short list of persons, turn the last comma into an '&'.
    $show_list_of_ids_short = preg_replace('/, ([^,]+)$/', ' & $1/', $show_list_of_ids_short);

  # print "<li>newcode.inc:<ul><li>\$show_list_of_ids_list : $show_list_of_ids_list\n";
  # print "<li>\$show_list_of_ids_prefix : $show_list_of_ids_prefix\n";
  # print "<li>\$show_list_of_ids_text : $show_list_of_ids_text\n";
  # print "<li>\$show_list_of_ids_short : $show_list_of_ids_short\n</ul>";

    if (!empty($output)) {
        $output = "<ul>\n$output\n</ul>\n";
    }
    return $output;
}

function also_credits($prefix, $id)
{
// A code of the form "$prefix".integer (e.g., g23) might also give credit to a
// person, project, ... other than the one owning the prefix. Which others?
// Should return a comma delimited list of ids.  $id is the id that owns the
// prefix so that we can omit it.

  # return '' unless we have both a preface and integer id
    if (empty($prefix) or !($id > 0)) {
        return '';
    }
  # No quoting needed for this next db call
    $query = "SELECT id, wild_codes from person WHERE wild_codes != '' and wild_codes is not NULL";
    $sth   = lib_mysql_query($query, $GLOBALS['db'], 'Invalid query (newcode.inc113)');

    $also_credit = '';    # Build the output in this variable
    $delim = '';      # just a comma-delimiter (see below)
    while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
        if ($row['id'] == $id) {
            continue; # skip the proof code
        }
        foreach (preg_split('/\s*,\s*/', $row['wild_codes']) as $match) {
            if (preg_match('/' . $match . '/', $prefix . '1')) {
                $also_credit .= $delim . $row['id'];
                $delim = ', ';
            }
        }
    }

    return $also_credit;
}
