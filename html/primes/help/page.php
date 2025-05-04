<?php

$t_meta['description'] = "Explanation of Terms on the Page Displaying a Single Prime from
	the database of the Largest Known Primes.";
$t_title = "Explanation of Terms on the Page Displaying a Single Prime";
$t_meta['add_keywords'] = "definitions, primes, terms, largest known primes";
$t_submenu = "Explanation";

$t_text = file_get_contents('page.txt');
$t_adjust_path = '../';

include("../template.php");
