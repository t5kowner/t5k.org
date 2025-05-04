<?php

#t# Locked to English only

include_once('../bin/basic.inc');
$t_adjust_path = '../';

$object = "the <i>Largest Known Primes</i> (and related pages)";
$Object = "The <i>Largest Known Primes</i> (and related pages)";
$editors = "Editor: Reginald McLean";

$t_title = "User Submitted Text";
$t_subtitle = "Disclaimer and Abuse Reporting";
if (!empty($description)) {
    $t_text = "<p>$description";
}

$t_text = '<div style="margin-top: 15px; margin-bottom: 15px">' . "\n<p>";
$t_text .= "We provide these pages as a service to the world-wide community interested in primes.
	This includes men, women and children of all ages. We strive to keep the text and images on
	this site clean and appropriate. We can not be responsible for the content and accuracy of
	the views and comments posted by participants. However, we will gladly remove any comments
	that are incorrect or inappropriate and will ban participants from posting in the future if
	 they continue to violate our standards.\n</p>\n<p>";
$t_text .=  "Please contact the editors about any problems, errors, or questionable material that
	you see on this website. Please be specific about which page it occurs on.\n</p>\n</div>\n";

include('../template.php');
