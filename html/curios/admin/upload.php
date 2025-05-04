<?php

# This is to allow user (well, administrator) uploads of files (e.g., images on
# curios...).  The files will be stored in basic_upload_where() and limited to
# basic_upload_max_size() bytes (which if large, may be superceeded by the
# php.ini limitation.  Of course the server must be able to write in that
# directory.

// If came from somewhere else, remember so we can add a link back!
$referer = false;
if (!empty($_SERVER["HTTP_REFERER"])) {
    if (!preg_match("/admin\/index.php(.*)$/", $_SERVER["HTTP_REFERER"])) {
        $referer = $_SERVER["HTTP_REFERER"];
    }
}

// Okay, load up the include files, print the header
include_once("../bin/basic.inc");
$db = basic_db_connect(); # Connects or dies; must come before admin_html_head
include('admin.inc');
echo admin_html_head(!empty($xx_TableName) ? "$xx_TableName table"
        : "file upload page");

################ Now the real stuff (above was header... junk) ##############

$max_file_bytes = basic_upload_max_size();  # How many bytes do we allow?
$store_files_in = basic_upload_where();     # Where do we put it?

$userfile_name  = (empty($_FILES['userfile']['name']) ?
    '' : $_FILES['userfile']['name']);
$userfile_size  = (empty($_FILES['userfile']['size']) ?
    '' : $_FILES['userfile']['size']);
$userfile_type  = (empty($_FILES['userfile']['type']) ?
    '' : $_FILES['userfile']['type']);

# Also, do not allow attemps to change directories
$userfile_name = preg_replace('#/#', '(slash)', $userfile_name);

# Now let's process the data

if (!empty($userfile_name)) {
  # This means a file name has been passed...
    echo "File upload: <blockquote><dl compact><dt>named<dd>$userfile_name,
	<dt>MIME type<dd>$userfile_type,
	<dt>size in bytes<dd>$userfile_size\n";

    $error = "<font color=red weight=900><dt>Error:<dd>";
    $success = "<font color=green face=bold><dt>Successful upload:<dd>";

    if ($userfile_size > $max_file_bytes) {
      # It is possible for folks to overide the limit in the HTML form
        echo "$error This file is to large (over $max_file_bytes byte) so will not
	be uploaded.";
    } elseif ($_FILES['userfile']['error']) {
        $temp = $_FILES['userfile']['error'];
        echo "$error File was not uploaded.  Server reports error: " .
        $temp . " " . print_r($temp, true);
    } elseif (
        $_FILES['userfile']['tmp_name'] == 'none' ||
        $_FILES['userfile']['size'] == 0
    ) {
      # If too big, IE gives no message on the browser, but sends a file
      # of size zero (at least IE 5.5 does)
        echo "$error No file uploaded. Does it exist?
		Could it be too big? Was it specified?";
    } elseif (is_uploaded_file($_FILES['userfile']['tmp_name'])) {
        if (
            ! move_uploaded_file(
                $_FILES['userfile']['tmp_name'],
                $store_files_in . $userfile_name
            )
        ) {
            echo "$error Copy failed, are permissions set correctly on the server?
		(Target $store_files_in$userfile_name";
        } elseif (! chmod($store_files_in . $userfile_name, 0644)) {
        # Using 644 so can update a file by loading on top of the old...
            echo "$error File uploaded and copied, but could not chmod--tell admin";
        } else {
            echo "$success The file is now available on the server";
        }
    } else {
        echo "$error No file uploaded.  I am not sure why.   Does it exist?";
    }

  # Both $success and $error open a font tag.
    echo "</font></dl></blockquote>";
}

if (!empty($delete)) {
  # Security!!! Don't let them choose the path! Don't allow ../../ ...
    $delete = preg_replace('#/#', '(slash)', $delete);
    $filepath = $store_files_in . $delete;
    if (unlink($filepath)) {
        echo "<font color=green>The file \"$filepath\" sucessfully deleted</font><p>\n";
    } else {
        echo "<font color=red>The file \"$filepath\" could not be deleted</font><p>\n";
    }
}

  # This code takes the directory of $store_files_in, omits files starting
  # with ., and presents them in a list with a delete radio button on the left
  # and the file size on the right.  The dimensions (if an image file) is in
  # the title of the linked file name (linked to the file).

  $d  = opendir($store_files_in);
while (false !== ($entry = readdir($d))) {
    if (preg_match("/^\./", $entry)) {
        continue;
    }
    $files[] = $entry;
}
if (!empty($files)) {
    sort($files);
}

  $out = "<blockquote><FORM METHOD=POST ACTION=\"$_SERVER[PHP_SELF]\">
	<table style=\"border:$GLOBALS[drkcolor] double 4px;\"
	cellSpacing=0 cellPadding=0 border=1>
	<caption title=\"Not starting with .\"><b>Files in $store_files_in</b>
	(press button below to delete selected files)</caption>
        <tr><td><table cellpadding=3>
	<tr bgColor=\"$GLOBALS[medcolor]\"><th width=40>delete</th>
	<th width=240 align=left>file name (click to view)</th>
	<th width=60>file size</th></tr>\n";
if (empty($files)) {
    $out .= lib_tr() . "<td>&nbsp;</td><td>NO FILES!</td><td>&nbsp;</td></tr>\n";
} else {
    foreach ($files as $entry) {
        $filepath = $store_files_in . $entry;
        $size = GetImageSize($filepath);
        $size = $size[3];
        $out .= lib_tr() . "<td align=center><input type=radio name=delete
	value=\"$entry\"></td>
	<td title='$size'><a href=\"$filepath\" target=\"_blank\">$entry</a></td>
	<td align=right>" . filesize($filepath) . "</td></tr>\n";
    }
}
  $out .= "</table></td></tr></table>
	<blockquote><input type=submit value=\"delete indicated files\">
	&nbsp; &nbsp; <input type=reset value=\"reset this form\"></blockquote>
	</FORM></blockquote>\n";

?>

Uploading a file involves two steps:
<blockquote><ol>
  <LI>Enter the file name (probably by using the "Browse..." button to find it).
  <LI>Press "Send File" button <b>once</b>.
</ol></blockquote>
Hopefully you will then get a version of this page saying the upload was successful.

<blockquote>
  <FORM METHOD=POST ENCTYPE="multipart/form-data" ACTION="<?php echo $_SERVER['PHP_SELF']; ?>">
    <table style="border:<?php echo $GLOBALS['drkcolor']; ?> double 4px;"
        cellSpacing=0 cellPadding=3 border=1><tr><td>
      <table bgColor="<?php echo $GLOBALS['medcolor']; ?>" cellSpacing=3><tr><td>
    <INPUT TYPE=hidden name="MAX_FILE_SIZE" value="<?php echo $max_file_bytes; ?>">
      <b>Send this file</b>: <INPUT NAME="userfile" VALUE="<?php echo
        $userfile_name; ?>" SIZE=40 TYPE="file">
    <INPUT TYPE=submit VALUE="Send File">
      </td></tr></table>
    </td></tr></table>
  </FORM>
</blockquote>

I would suggest using file names that are short but descriptive.&nbsp; Also try
to keep the files as small as possible (so pages load quickly).&nbsp; The page
<a href="../bin/modify.php">Modify routines</a> states how to include these
files. <blockquote>Hint: '\image(icon.gif)' or '\image(icon.gif,align=right)'.
</blockquote> See the <a href="#notes">notes</a> below for more information.

<?php echo $out; ?>

<a name=notes>Notes:</a>
<ul>
  <li>Saving a file with the same name as an existing file will just replace the
existing file (without any warning message).&nbsp; So to edit a file, replace it
with the new version.
  <li>There is no confirmation screen for file deletes.&nbsp; There <i>might</i>
be someday.&nbsp; So for now delete with care.
  <li>Be sure to obey all copyright laws.
  <li><b>Current maximum file size limits are <?php echo $max_file_bytes; ?> bytes
(basic.inc) and <?php echo ini_get('upload_max_filesize'); ?> (php.ini)</b>, but try
to stay <i>much</i> smaller!
  <li> In the list of files above, the dimensions (if an image file) is in
the title-field of the linked file name (linked to the file).
</ul>
