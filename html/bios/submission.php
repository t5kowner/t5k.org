<?php

#t# ready

$t_submenu =  "New Submission";

include("bin/basic.inc");
# $db = basic_db_connect(); # Connects or dies

$index = basic_index();

// Okay, lets start filling in the template variables

$t_meta['description'] = "Need to submit a prime or new bio to the list of
  largest known primes?  This is your page!";
$t_title = 'Submit a New Prime or  ... ';

$t_text = '<p>What do you want to do?</p>

<dl>
  <dt> <p style="font-size: larger;">Submit a Prime</p>
  <dd> The easiest way to submit a prime is to click on the links on the bottom of your
	prover-account page. Here is the index to these pages:' . $index . '<br>

  <dt> <p style="font-size: larger;">Create a New Prover-Account</p>
  <dd> If you are a new prover and have a prime to submit, then
	<a href="newprover.php">click here to create a prover-account<a>. This entry will
	be removed if you either fail to submit a prime or misuse the page in any way.
	New prover-accounts will not show up in the index or be linked in, until a prime
	is submitted. After you create a prover-account, there will be a link on
	the bottom of your page to create a proof-code.<br>

  <dt> <p style="font-size: larger;">Create a New Proof-Code</p>
  <dd> Every prime is submitted with a proof-code like \'g234\' or \'L23\' which specifies
	which programs were used, which projects were involved...  Most provers just need
	one (sometimes two) such codes, but if you have changed which programs you are
	using then you should change the code to accurately reflect this.  To do so use the
	links on the bottom of your prover-account page. (You must first have a
	prover-account before you can create a code.) We want proof-codes to be as accurate,
	and as few in number, as possible.<br>

  <dt> <p style="font-size: larger;">A Comment on a Prime</p>
  <dd> Go to the page for that prime and click on "User Comments".<br>

  <dt> <p style="font-size: larger;">Contact the Editors?</p>
  <dd> Use the "Mail Editor" link on the left (under "Join In"). These pages are for you--so
	we need your comments!
</dl>
<br>';

include("template.php");
