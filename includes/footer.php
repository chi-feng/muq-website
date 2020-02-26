<script src="https://www.google.com/recaptcha/api.js" async defer></script>

</div>
<?php
if ($tpl['hide_sidebar'] == false) {
?>
<div class="col-md-3">
  <div class="panel panel-default">
  <div class="panel-heading"><img height=30px src="images/Slack_Mark_Web.png" alt="Slack LOGO"> MUQ is on Slack! </div>

  <ul class="list-group">
  <li class="list-group-item">
  Join our slack workspace to connect with other users, get help, and discuss new features.</br>
  <!-- Trigger the modal with a button -->
  <button type="button" class="btn btn-info btn-md btn-block" data-toggle="modal" data-target="#slackModal">Join Us</button>
  </li>
  </ul>

  <!-- Slack signup Modal -->
  <div id="slackModal" class="modal fade" role="dialog">
  <div class="modal-dialog">

    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Join the MUQ Slack Workspace</h4>
      </div>
      <div class="modal-body">

  			<?php
          require_once ('pages/sendSlackInvite.php');
  				showForm();
  			?>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
      </div>

    </div>
  </div>
</div>

<!-- Slack duplicate email -->
<div id="slackDuplicateEmailModal" class="modal fade" role="dialog">
<div class="modal-dialog">

  <!-- Modal content-->
  <div class="modal-content">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal">&times;</button>
      <h4 class="modal-title">Error:</h4>
      <p>Could not send Slack invite because the email already exists in the MUQ workspace.</p>
    </div>

    <div class="modal-footer">
      <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
    </div>

  </div>
</div>
</div>

<!-- Slack duplicate email -->
<div id="slackSuccessfulModal" class="modal fade" role="dialog">
<div class="modal-dialog">

  <!-- Modal content-->
  <div class="modal-content">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal">&times;</button>
      <h4 class="modal-title">Success!</h4>
      <p>You should see a slack invitation in your email soon.</p>
    </div>

    <div class="modal-footer">
      <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
    </div>

  </div>
</div>
</div>

<!-- Slack duplicate email -->
<div id="slackUnsuccessfulModal" class="modal fade" role="dialog">
<div class="modal-dialog">

  <!-- Modal content-->
  <div class="modal-content">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal">&times;</button>
      <h4 class="modal-title">Error:</h4>
      <p>Oops...  Something happened and we weren't able to send the Slack invitation.</p>
    </div>

    <div class="modal-footer">
      <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
    </div>

  </div>
</div>
</div>

  </div>

<div class="panel panel-default">
<div class="panel-heading">Test Status</div>
<ul class="list-group">
<li class="list-group-item">
<a href='https://acdl.mit.edu/csi/job/MUQ2_Nightly/'><img src='https://acdl.mit.edu/csi/buildStatus/icon?job=MUQ2_Nightly'></a>
</li>
</ul>
</div>
<div class="panel panel-default">
<div class="panel-heading">Links</div>
<div class="panel-body">
<ul>
<li><a href="https://bitbucket.org/mituq/muq">MUQ on Bitbucket</a></li>
<li><a href="http://uqgroup.mit.edu/">UQ Group Website</a></li>
</ul>
</div>
</div>
<div class="panel panel-default">
  <div class="panel-heading">Acknowledgments</div>
  <div class="panel-body">
    <img src="images/nsf_logo.gif" alt="NSF Logo" hspace="5" vspace="15" align="left" height="80" width="80"> This material is based upon work supported by the National Science Foundation under Grant No. 1550487.</br></br>
    <img src="images/doe_logo.png" alt="DOE Logo" hspace="7" vspace="5" align="left" height="75" width="75"> This work was supported by the DOE Office of Science through the <a href="https://fastmath-scidac.llnl.gov/">QUEST and FastMath SciDAC Institutes.</a></br></br>

    Any opinions, findings, and conclusions or recommendations expressed in this material are those of the author(s) and do not necessarily reflect the views of the National Science Foundation.
  </div>
</div>

</div>
<?php } ?>
</div> <!-- .row -->
</div> <!-- .container -->
<footer class="footer">
<div class="container">
<div class="row">
<div class="col-md-5 hidden-sm hidden-xs">
<p><small>Designed by </small></p>
</div>
<div class="col-md-7">
<p class="pull-right"><small> &copy;2015 <a href="http://uqgroup.mit.edu">MIT Uncertainty Quantification Group</a></small></p>
</div>
</div>
</div>
</footer>
</body>
</html>
