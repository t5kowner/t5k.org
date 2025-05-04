<?php

$t_meta['description'] = "Examples of advanced searches in the
	prime number database.";
$t_title = "Examples of Advanced Description Searches";
$t_meta['add_keywords'] = "examples, searches";
// $t_subtitle = "The List of Largest Known Primes";

$t_text = file_get_contents('search_description.txt');
$t_adjust_path = '../';

include("../template.php");
