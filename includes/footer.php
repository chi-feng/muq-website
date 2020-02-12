</div>
<?php
if ($tpl['hide_sidebar'] == false) {
?>
<div class="col-md-3">
<div class="panel panel-default">
<div class="panel-heading">Test Status</div>
<ul class="list-group">
<li class="list-group-item">
<h5>Master Branch</h5>
<a href="https://bitbucket.org/mituq/muq2/addon/pipelines/home#!/results/branch/master/page/1"><img alt="Test Status" src="https://img.shields.io/bitbucket/pipelines/mituq/muq2/master.svg"></a>
<h5>CI Branch</h5>
<a href="https://bitbucket.org/mituq/muq2/addon/pipelines/home#!/results/branch/ci/page/1"><img alt="Test Status" src="https://img.shields.io/bitbucket/pipelines/mituq/muq2/ci.svg"></a>
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
    <img src="images/doe_logo.png" alt="DOE Logo" hspace="7" vspace="5" align="left" height="75" width="75"> This work was supported by the DOE Office of Science through the <a href="http://www.quest-scidac.org/">QUEST SciDAC Institute.</a></br></br>

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
