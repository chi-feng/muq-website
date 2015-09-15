<?php

if (isset($_POST['payload'])) {
  $result = shell_exec('cd /home/webmaster/muq-website && git reset --hard HEAD && git pull');
  echo $result;
}

?>
