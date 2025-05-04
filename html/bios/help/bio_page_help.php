<?php

#t# Locked to English only

include('../bin/basic.inc');

$t_meta['description'] = "Explanation of Terms on the Page Displaying a Single Prover-Account from
	the database of the Largest Known Primes.";
$t_title = "Definitions of Terms Used in Biographical Pages";
$t_meta['add_keywords'] = "definitions, primes, terms, largest known primes";

$t_text = file_get_contents('bio_page_help.txt');
$t_adjust_path = '../';
$t_submenu = 'help / terms';

include("../template.php");
