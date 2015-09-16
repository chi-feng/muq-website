</div>
<?php
if ($tpl['hide_sidebar'] == false) {
?>
<div class="col-md-3">
<div class="panel panel-default">
<div class="panel-heading">Test Status</div>
<ul class="list-group">
<li class="list-group-item">
<h5>Develop Branch</h5>
<table class="table table-condensed">
<thead>
<tr><th>OS</th>
<th>Compiler</th>
</tr>
</thead>
<tbody>
<tr>
<td>OSX</td>
<td>Clang</td>
<td><a href="https://acdl.mit.edu/csi/job/MUQ_Develop_Nightly/"><img alt="Test Status" src="https://acdl.mit.edu/csi/buildStatus/icon?job=MUQ_Develop_Nightly/builddir=release_clang,buildnode=macys"></a></td>
</tr>
<tr>
<td>Ubuntu</td>
<td>Clang</td>
<td><a href="https://acdl.mit.edu/csi/job/MUQ_Develop_Nightly/"><img alt="Test Status" src="https://acdl.mit.edu/csi/buildStatus/icon?job=MUQ_Develop_Nightly/builddir=release_clang,buildnode=reynolds"></a></td>
</tr>
<tr>
<td>Ubuntu</td>
<td>g++ 4.7</td>
<td><a href="https://acdl.mit.edu/csi/job/MUQ_Develop_Nightly/"><img alt="Test Status" src="https://acdl.mit.edu/csi/buildStatus/icon?job=MUQ_Develop_Nightly/builddir=release_gnu47,buildnode=reynolds"></a></td>
</tr>
<tr>
<td>Ubuntu</td>
<td>g++ 4.8</td>
<td><a href="https://acdl.mit.edu/csi/job/MUQ_Develop_Nightly/"><img alt="Test Status" src="https://acdl.mit.edu/csi/buildStatus/icon?job=MUQ_Develop_Nightly/builddir=release_gnu48,buildnode=reynolds"></a></td>
</tr>
<tr>
<td>Ubuntu</td>
<td>g++ 4.9</td>
<td><a href="https://acdl.mit.edu/csi/job/MUQ_Develop_Nightly/"><img alt="Test Status" src="https://acdl.mit.edu/csi/buildStatus/icon?job=MUQ_Develop_Nightly/builddir=release_gnu49,buildnode=reynolds"></a></td>
</tr>
</tbody>
</table>
</li>
</ul>
</div>
<div class="panel panel-default">
<div class="panel-heading">Links</div>
<div class="panel-body">
<ul>
<li><a href="https://bitbucket.org/mituq/muq">MUQ on Bitbucket</li>
<li><a href="http://uqgroup.mit.edu/">UQ Group Website</li>
</ul>
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
