<?php

// Who is blocked or penalized recently.

// $edit must be set to penalize or forgive
$edit = !empty($_REQUEST['edit']) ? 'edit=' . preg_replace('/[^\d]/', '', $_REQUEST['edit']) : '';
// autorefresh page?
$refresh = !empty($_REQUEST['refresh']) ? 'refresh=' . preg_replace('/[^\d]/', '', $_REQUEST['refresh']) : '';
$add2url = $edit . ((!empty($edit) and !empty($refresh)) ? '&amp;' : '') . $refresh;
$add2url = (!empty($edit) or !empty($refresh)) ? '?' . $add2url : '';

$interval = preg_replace('/[^\d]/', '', $refresh);
if ($interval > 0) {
    $interval = 60 * $interval;
    $interval = "<META HTTP-EQUIV=Refresh CONTENT=$interval>";
} else {
    $interval = '';
}

// How many hours back?
$hours = isset($_REQUEST['hours']) ? $_REQUEST['hours'] : '';
if (preg_match('/(\d{1,5})/', $hours, $temp)) {
    $hours = $temp[1];
} else {
    $hours = 72;
}

include_once("basic.inc");
$db = basic_db_connect(); # Connects or dies

# Now let's keep the bad folks out
include_once('http_auth.inc');
include_once('../admin/permissions.inc');

if (!my_auth('allow any') or $permissions_array['view logs'][$http_auth_id] <> 'yes') {
    lib_die("You can not view this page without the proper authorization. [error: $http_auth_id]");
}

## To see these log pages you must be a Titan listed in ../admin/permissions.inc

$t_title = "Who is blocked?";
$t_text = "<p>Below I show a table of the penalty point sums.  Click on the IP address for
	the reasons for the penalties.</p><blockquote>Go back to the <a href=\"log.php$add2url\">log page</a>?
       <a href=\"$_SERVER[PHP_SELF]$add2url\">Reload this page</a>?</blockquote>
       <p>If you just forgave or penalized someone using the browser reload button will try to repeat the action.</p>";
$t_adjust_path = '../';

# Did we ask to penalize/forgive anyone?

foreach ($_REQUEST as $a => $b) {   # First, block standard evil access
    if (
        !empty($b) and preg_match('/^(penalize|forgive)$/', $b)
        and preg_match('/^ip(\d{1,3}_\d{1,3}_\d{1,3}_\d{1,3})$/', $a, $temp)
    ) {
        $what = $b;
        $who = preg_replace('/_/', '.', $temp[1]);
        $t_text .= "<blockquote class=technote>Action just taken: $what $who</blockquote><br>";
        http_auth_log_ip_error(
            $GLOBALS['http_auth_id'],
            $penalty = ($what == 'forgive' ? -2 : 2),
            '',
            '(omitted)',
            'editor: ' . $what,
            $who
        );
    }
}

$time = time();
$query = "SELECT ip, SUM(penalty) AS sum_ , count(*) AS count, username,
	DATE_FORMAT(MIN(created),'%a %H:%i') AS created, DATE_FORMAT(MAX(created),'%a %H:%i') as recent
     FROM failed WHERE created > DATE_SUB(NOW(),INTERVAL $hours HOUR) GROUP BY ip ORDER BY sum_ DESC, created";
$sth = lib_mysql_query($query, $db);

$t_text .= "\n<form method=post action=\"$_SERVER[PHP_SELF]$add2url\">
    <p>Include those active in the last <input type=text size=5 name=hours value='$hours'> hours
    <input type=submit name=\"change\" value=change>.</p>";

$t_text .= "<div class=\"datatable\">
\n<table class=\"table\">\n<thead>
  <tr bgcolor=\"$medcolor\"><th>IP</th><th>sum&nbsp;&nbsp;&nbsp;&nbsp;</th>" .
  "<th>count&nbsp;&nbsp;&nbsp;&nbsp;</th><th>username&nbsp;&nbsp;&nbsp;&nbsp;</th>" .
  "<th>earliest&nbsp;&nbsp;&nbsp;&nbsp;</th><th>recent&nbsp;&nbsp;&nbsp;&nbsp;</th>";

if (!empty($edit)) {
    $t_text .= "<th colspan=2>adjust by two&nbsp;&nbsp;&nbsp;&nbsp;</th>";
}

$t_text .= "\n</tr>\n</thead>\n<tbody>\n";

while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
    $note = $row['sum_'] < 10 ? '' : ' blocked';
    $ip = "<a href=\"whois.php?ip=$row[ip]\" class=none>$row[ip]</a>";
    $t_text .= lib_tr(empty($note) ? '' : 'class=warning') . "<td>$ip</td><td align=center>$row[sum_]</td>
	<td align=center>$row[count]</td><td>$row[username]</td><td>$row[created]</td>
	<td>$row[recent]$note</td>";
    if (!empty($edit)) {
        $t_text .= "\t<td><input type=submit name=\"ip$row[ip]\" value=penalize></td>
	<td><input type=submit name=\"ip$row[ip]\" value=forgive></td>";
    }
    $t_text .= "\n</tr>\n";
}
$t_text .= "\n</tbody>\n</table>\n</div>";
if (!empty($edit)) {
    $t_text .= "\n</form>";
}

include("../template.php");
exit;
