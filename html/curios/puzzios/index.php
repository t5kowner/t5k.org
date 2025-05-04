<?php

$t_meta['description'] = "Prime Puzzles (Puzzios)";
$t_title = "Prime Puzzios";
$t_meta['add_keywords'] = "prime,puzzle,puzzio";

$t_text = <<<HERE
  <p>Here's the list of all puzzios the site contains, in order of release date.</p>
  <ul>
    <li><a href="puzzio.php">Mean Prime Gaps</a></li>
    <li><a href="chessearch.php">CHESSearch</a></li>
  </ul>
HERE;


$t_adjust_path = '../';
include("../template.php");
