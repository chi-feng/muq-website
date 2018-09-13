<?php
$tpl['page_name'] = 'Examples';
$tpl['tab'] = 'Examples';

$examples = json_decode(file_get_contents('json/examples.json'), true);

// helper function for deciding whether to hide a section
function count_examples($topic) {
  global $examples;
  $count = 0;
  foreach($examples as $ex) {
    if ($ex['topic'] == $topic) 
      $count++;
  }
  return $count;
}

function example_filter($topic) {
  echo '<div class="list-group">';
  
  global $examples;
  $count = 0;
  foreach($examples as $ex) {
    if ($ex['topic'] == $topic) {
      echo '<a href="'.$ex['url'].'" class="list-group-item">';
      echo '<span class="badge">'.$ex['language'].'</span>';
      echo '<h4 class="list-group-item-heading">'.$ex['title'].'</h4>';
      echo '<p class="list-group-item-text">'.$ex['desc'].'</p>';
      echo '</a>';
    }
  }
  echo '</div>';

}

?>

<div>

<h2>Interactive Examples</h2>
<p>We are using <a href="https://jupyterhub.readthedocs.io/en/stable/">JupyterHub</a> to provide temporary <a href="https://jupyterlab.readthedocs.io/en/stable/">JupyterLab</a> sessions where you can explore MUQ's capabilities without having to install MUQ on your local machine.  Click the button below to try it out!</p>

<button type="button" class="btn btn-primary btn-block" onclick="location.href='http://muq.mit.edu:8000';">Click here to launch a new session.</button>

<p> Note that MUQ is in the midst of a significant refactor into a new "MUQ2" library that is more user friendly and powerful.  These JupyterLab sessions are using the new MUQ2 library whereas the examples below are for MUQ1.  We are in the process of updating this page.</p> 
<br>
<br>
<p>
<h4>A few other notes about the interactive sessions:</h4>
<ul>
<li>All of the examples listed below (and more) are available in our interactive MUQ sessions.</li>
<li>Many of the examples employ <a href="http://jupyter.org/">Jupyter</a> notebooks which provide a mix of documentation and code.  However, it is also possible to run the other examples or test your own MUQ code by creating new files and opening a terminal window.  </li>
<li>The interactive sessions use the current develop branch from our <a href="https://bitbucket.org/mituq/muq">bitbucket site.</a></li>
<li>Anything you do in your interactive session will be lost when you close your browser. </li>

</p>
<hr>
</div>



	<h2>Introductory Examples</h2>
	<?php example_filter('intro'); ?>

	<h2>Modelling Examples</h2>
	<?php example_filter('model'); ?>

	<h2>Markov chain Monte Carlo (MCMC) Examples</h2>
	<?php example_filter('mcmc'); ?>

        <h2>Optimization Examples</h2>
        <?php example_filter('optimization'); ?>	
	
        <h2>Transport Map Examples</h2>
	<?php example_filter('transport_maps'); ?>

