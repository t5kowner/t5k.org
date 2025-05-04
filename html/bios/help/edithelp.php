<?php

#t# Locked to English only

  include('../bin/basic.inc');
  include('../bin/modify.inc');
  $t_text = modify_show_entities(isset($_POST['text']) ? $_POST['text'] : '');
  $t_adjust_path = '../';
  $t_title = "Edit Help";
  $t_limit_lang = 'en_US';
  $t_submenu = 'help / entities';
  include('../template.php');
