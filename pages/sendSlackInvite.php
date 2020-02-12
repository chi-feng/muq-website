<?php

include 'slackSecrets.php';

function sendForm(){

	$email = $_POST['mail'];
	$first = $_POST['first'];
	$last = $_POST['last'];

  if (filter_var($email, FILTER_VALIDATE_EMAIL)) {

    // Get a list of all slack users
    $slackInviteUrl='https://slack.com/api/users.list';
    $fields = array('token' => TOKEN);
    $fields_string='';
    foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
    rtrim($fields_string, '&');

    // open connection
    $ch = curl_init();

    // set the url, number of POST vars, POST data
    curl_setopt($ch,CURLOPT_URL, $slackInviteUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch,CURLOPT_POST, count($fields));
    curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);

    // Execute the curl command to get a list of users
    $replyRaw = curl_exec($ch);
    $reply=json_decode($replyRaw,true);

    // Figure out if this email exists yet
    $emailExists=false;
    foreach($reply['members'] as $value){
      if(strcmp($email, $value['profile']['email'])==0){
        $emailExists=true;
        break;
      }
    }

    if($emailExists){
      return 'DuplicateEmail';
    }else{

      // Send a message to the slack channel informing developers that an invitation has been sent
      $slackInviteUrl='https://slack.com/api/chat.postMessage';
      $fields = array(
              'text' => 'New user *'.urlencode($first).' '.urlencode($last).'* ('.urlencode($email).') has been invited to join the MUQ slack channel.',
              'channel' => '#invitations',
              'token' => TOKEN
      );

      // url-ify the data for the POST
      $fields_string='';
      foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
      rtrim($fields_string, '&');

      // set the url, number of POST vars, POST data
      curl_setopt($ch,CURLOPT_URL, $slackInviteUrl);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch,CURLOPT_POST, count($fields));
      curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);

      // Execute the curl command to get a list of users
      $replyRaw = curl_exec($ch);
      $reply=json_decode($replyRaw,true);

      $slackInviteUrl='https://slack.com/api/users.admin.invite';

      $fields = array(
              'email' => urlencode($email),
              'real_name' => urlencode($first).' '.urlencode($last),
              'token' => TOKEN,
              'channels' => 'help-approximation,help-installation,help-modeling,help-optimization,help-sampling,help-utilities'
      );

      // url-ify the data for the POST
      $fields_string='';
      foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
      rtrim($fields_string, '&');

      // set the url, number of POST vars, POST data
      curl_setopt($ch,CURLOPT_URL, $slackInviteUrl);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch,CURLOPT_POST, count($fields));
      curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);

      // exec
      $replyRaw = curl_exec($ch);
      $reply=json_decode($replyRaw,true);
      if($reply['ok']==false) {
        return  'SuccessfulInvite';
      }else{
        return 'FailedInvite';
      }

      // close connection
      curl_close($ch);
    }
  } // end of email check
}

function showForm(){

  ?>

    <form method="post" action="pages/SlackInvite.php">

      <div class="form-group">
        <label for="firstNameInput">First Name</label>
        <input type="text" class="form-control" id="firstNameInput" name="first" placeholder="Thomas" <?php echo isset($_POST['first']) ? 'value="'.$_POST['first'].'"' : ''; ?> />
      </div>

      <div class="form-group">
        <label for="lastNameInput">Last Name</label>
        <input type="text" class="form-control" id="lastNameInput" name="last" placeholder="Bayes" <?php echo isset($_POST['last']) ? 'value="'.$_POST['last'].'"' : ''; ?> />
      </div>

      <div class="form-group">
        <label for="emailInput">Email Address</label>
        <input type="text" class="form-control" id="emailInput" name="mail" placeholder="reverend.bayes@likelihood.com" <?php echo isset($_POST['mail']) ? 'value="'.$_POST['mail'].'"' : ''; ?> />
      </div>

      <div class="g-recaptcha" data-sitekey="6LftPNgUAAAAAJN_INwyLN7aunawWrpD4LlQjokD" data-callback="enableSubmitSlack"></div>

    </br>
    <input type="submit" class="btn btn-success btn-send" name="slackSubmit" id="slackSubmitButton" value="Sign me up!" disabled/>

    </form>

    <script>
      function enableSubmitSlack() {
          var bt = document.getElementById('slackSubmitButton');
          bt.disabled = false;
      }
    </script>

  <?php

}

?>
