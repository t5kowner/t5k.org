<link href="/includes/spaced.css" rel="stylesheet" type="text/css">
<?php
  include("basic.inc");
  $db = basic_db_connect(); # Connects or dies

  # Now let's keep the bad folks out
  include_once('http_auth.inc');
  include_once('../admin/permissions.inc');

if (!my_auth('allow any') or $permissions_array['view logs'][$http_auth_id] <> 'yes') {
    lib_die("You can not view this page without the proper authorization. [error: $http_auth_id]");
}
?>

<h1>Seeking info on <b><?php
  $ip = $_REQUEST['ip'];
  echo $ip;
?></b></h1>

<a name='top'></a><a href='#top'><img src='/gifs/here1.gif' alt='->'></a>
How might we check?&nbsp;  How about these methods:
<ol>
  <li><a href='#track'>track record</a></li>
  <li><a href='#host'>host</a></li>
  <li><a href='#dig'>dig</a></li>
  <li><a href='#whois'>whois</a></li>
</ol>
or maybe try the address: <a href=<?php echo "'http://" . $ip . "'>$ip</a>.  External link:
<a href='https://www.iplocation.net/?query=" . $ip . "'>https://www.iplocation.net/?query=" . $ip . "</a>"; ?>


<a name=track></a><h2><a href='#top'><img src='/gifs/up1.gif' alt='up'></a> 1. track record</h2>
The last 15 days (only those in the last 36 hours matter):<P>
<div class=record>
<table cellpadding=4><tr bgcolor="<?php echo $medcolor; ?>"><th>IP</th><th>username</th><th>prover id</th>
    <th>penalty</th><th>comment</th><th>hours ago</th><th>page</th></tr>
<?php
$query = "SELECT ip,id,username,person_id,penalty,comment,(unix_timestamp(NOW())-unix_timestamp(created))/3600
   AS ago, page, created FROM failed WHERE ip=:ip
	and created > SUBTIME(NOW(),'360:00:00') order by ago";
try {
    $sth = $db->prepare($query);
    $sth->bindValue(':ip', $ip);
    $sth->execute();
} catch (exception $e) {
    print "Error in whois.php database query<p>" . $e->getMessage();
}
while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
    $comment = $row['comment'];
    print lib_tr() . "<td title=\"id=$row[id]\">$row[ip]</td><td align=right>$row[username]</td>
        <td align=center>$row[person_id]</td><td align=center>$row[penalty]</td><td>$comment</td>
        <td title='$row[created]' align=center>" . sprintf('%.1f &nbsp; ', $row['ago']) . "</td>
	<td>$row[page]</td></tr>\n";
}
?>
</table></div>



<a name=host></a><h2><a href='#top'><img src='/gifs/up1.gif' alt='up'></a> 2. host</h2>
The results of host:
<pre class=record>
<?php
  echo shell_exec('host ' . escapeshellarg($ip));
?>
</pre>



<a name=dig></a><h2><a href='#top'><img src='/gifs/up1.gif' alt='up'></a> 3. dig</h2>
The results of dig:
<pre class=record>
<?php
  echo shell_exec('dig ' . escapeshellarg($ip));
?>
</pre>



<a name=whois></a><h2><a href='#top'><img src='/gifs/up1.gif' alt='up'></a> 3. whois</h2>
The results of whois:
<pre class=record>
<?php
  echo shell_exec('whois ' . escapeshellarg($ip));
?>
</pre>

