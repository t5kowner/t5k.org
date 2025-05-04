<?php

include_once('../bin/basic.inc');

$t_meta['description'] = "Explanation of Terms on the Page Displaying a Single Prime from
	the database of the Largest Known Primes.";
$t_title = "Explanation of Terms on the Page Displaying a Single Prime";
$t_meta['add_keywords'] = "definitions, primes, terms, largest known primes";
// $t_subtitle = "";

$t_limit_lang = 'en_US';
$t_text = file_get_contents('page.txt');
$t_adjust_path = '../';
$t_submenu = 'help / Bio Terms';

include("../template.php");
