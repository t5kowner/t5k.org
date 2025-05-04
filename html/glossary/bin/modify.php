<?php

#t# ready.  Nothing for end users to see.

  $text     = isset($_POST['text']) ? stripslashes($_POST['text']) : '';

  include_once('basic.inc');
  include_once('modify.inc');
if (isset($_GET['entities']) ? $_GET['entities'] : '') {
    echo modify_show_entities($text);
} elseif (isset($_GET['links']) ? $_GET['links'] : '') {
    modify_show_links($text);
} elseif (isset($_GET['adjust']) ? $_GET['adjust'] : '') {
    echo modify_show_adjustments($text);
} else {
    modify_help();
}
