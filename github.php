<?php

if ($_POST['payload']) {
  shell_exec('cd /home/webmaster/muq-website && git reset --hard HEAD && git pull');
}

?>
