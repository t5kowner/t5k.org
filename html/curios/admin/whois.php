<link href="/includes/spaced.css" rel="stylesheet" type="text/css">
<?php
#  include("basic.inc");
#  $db = basic_db_connect(); # Connects or dies

  # Now let's keep the bad folks out
#  include_once('http_auth.inc');
#  include_once('../admin/permissions.inc');

#  if (!my_auth('allow any') or $permissions_array['view logs'][$http_auth_id] <> 'yes')
#    lib_die("You can not view this page without the proper authorization. [error: $http_auth_id]");
?>

<h1>Seeking info on <b><?php
  $ip = $_REQUEST['ip'];
  echo $ip;
?></b></h1>

<a name='top'></a><a href='#top'><img src='/gifs/here1.gif' alt='->'></a>
How might we check?&nbsp;  How about these methods:
<ol>
  <li><a href='#host'>host</a></li>
  <li><a href='#dig'>dig</a></li>
  <li><a href='#whois'>whois</a></li>
  <li><a href='#ping'>ping</a></li> (currently block be SE Linux)
</ol>
or maybe try the address: <a href=<?php echo "'http://" . $ip . "'>$ip</a>"; ?>


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


<a name=whois></a><h2><a href='#top'><img src='/gifs/up1.gif' alt='up'></a> 4. ping</h2>
The results of ping (3 pings with record a maximum time of 10 seconds):
<pre class=record>
<?php
  echo shell_exec('ping -w 10 -c 3 ' . escapeshellarg($ip));
  # why did I write: Currently not allowed by SE Linux.  If it is valuable enough, I can find a
  # way to reinstate it.  But right now HTTPD is not allowed to create new sockets or run ping.
?>
</pre>
