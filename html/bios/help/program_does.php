<?php

include_once('../bin/basic.inc');

$t_meta['description'] = "How do you start finding large primes?  That depends on
you, on what you know and on what you want.  For example, you can download a
program and then either join an existing project or take off on your own; or you
might write your own program.  We briefly explain each of these options further
below.";
$t_title = "Help: What Primality Programs Do";
// $t_meta['add_keywords'] = "history, introduction, uses, Gauss";
$t_subtitle = "The List of Largest Known Primes";

$t_text = file_get_contents('program_does.txt');
$t_adjust_path = '../';
$t_submenu = 'help / prime terms';

include("../template.php");
