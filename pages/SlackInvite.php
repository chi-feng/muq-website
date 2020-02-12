<?php
$tpl['page_name'] = 'Slack Invitation';

require_once ( 'sendSlackInvite.php' );


if(isset($_POST['first']))
{
  if((strlen($_POST['first'])>0) && (strlen($_POST['last'])>0) && (strlen($_POST['mail'])>5)){
    $message=sendForm();
    header('Location: /?message='.$message);
  }
}else{
  header('Location: /');
}

?>
