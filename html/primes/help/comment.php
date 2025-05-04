<?php

$t_meta['description'] = "Rules and guidelines for submitting comments on primes
	for the database of the Largest Known Primes.";
$t_title = "Rules and guidelines for submitting comments on primes";
$t_meta['add_keywords'] = "comments, guidelines, primes, largest known primes";
// $t_subtitle = "";

$t_text = file_get_contents('comment.txt');
$t_adjust_path = '../';
$t_submenu = 'help / comment';

include("../template.php");
