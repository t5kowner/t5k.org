<?php

#t# Locked to English only

  $t_meta['description'] = "A short explaination of how we modify the
	user supplied text entries in our databases.";
  $t_title = "How HTML Text is modified";
  $t_adjust_path = '../';
  $t_limit_lang = 'en_US';

  $text     = isset($_POST['text']) ? $_POST['text'] : '';

  include('../bin/basic.inc');
  include('../bin/modify.inc');

  $t_text = "We provide these pages as a service to the world-wide community interested
	in primes.&nbsp;  This includes men, women and children of all ages.&nbsp; If you
	are submitting text please keep this in mind.&nbsp;  We strive to keep the text and images
	on this site clean and appropriate--anyone who does not do likewise will not be
	allowed to post here.\n";

  $t_text .= modify_show_adjustments($text);
  $t_submenu = 'help / HTML mods';

  include("../template.php");
