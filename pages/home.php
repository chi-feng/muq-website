<?php
$tpl['page_name'] = 'Home';
$tpl['tab'] = 'Home';

if (isset($_GET['message'])) {
  // Here, we use single quotes for PHP and double quotes for JavaScript
echo '<script type="text/javascript"> console.log("Hello World!")</script>';
//echo '<script type="text/javascript"> $(document).ready(function(){ $("#slackDuplicateEmailModal").modal('show'); });</script>';

}else{
  echo '<script type="text/javascript"> console.log("Message not set!")</script>';
}



?>

<?php
if (isset($_GET['message'])){

  $message = $_GET['message'];
  if(strcmp($message, 'DuplicateEmail')==0){
?>

<script>
    $(document).ready(function(){
        $("#slackDuplicateEmailModal").modal('show');
    });
</script>

<?php
}else if(strcmp($message, 'SuccessfulInvite')==0){
?>

<script>
    $(document).ready(function(){
        $("#slackSuccessfulModal").modal('show');
    });
</script>

<?php
}else{
?>
<script>
    $(document).ready(function(){
        $("#slackUnsuccessfulModal").modal('show');
    });
</script>

<?php
}
}
?>




<h2>What is MUQ?</h2>
<p>In a nutshell, MUQ is a collection of tools for constructing models and a collection of uncertainty quantification (UQ)–focused algorithms for working on those models. Our goal is to provide an easy and clean way to set up and efficiently solve UQ problems. On the modelling side, we have a suite of tools for:</p>
<ul>
<li>Combining many simple model components into a single sophisticated model.</li>
<li>Propagating derivative information through sophisticated models.</li>
<li>Integrating ordinary differential equations and differential algebraic equations (via <a href="https://computation.llnl.gov/casc/sundials/main.html">Sundials</a>)</li>
</ul>
<p>Furthermore, on the algorithmic side, we have tools for</p>
<ul>
<li>Performing Markov chain Monte Carlo (MCMC) sampling</li>
<li>Constructing polynomial chaos expansions (PCE)</li>
<li>Computing Karhunen-Loeve expansions</li>
<li>Building optimal transport maps</li>
<li>Solving nonlinear constrained optimization problems (both internally and through <a href="http://ab-initio.mit.edu/wiki/index.php/NLopt">NLOPT</a>)</li>
<li>Regression (including Gaussian process regression)</li>
</ul>
<h2>Installation</h2>
<h3>Using Docker</h3>
<pre>docker pull mparno/muq2</pre>
<pre>docker run -it --rm mparno/muq2 bash</pre>
<h3>From source</h3>
<p>Currently, the best way to get started is by checking out our git repository on bitbucket. Go to the folder you where you want to keep the MUQ source code, and use:</p>
<pre>git clone https://bitbucket.org/mituq/muq2.git</pre>
<h3>Compiling the source</h3>
<p>Now that you have the source, you need to compile and install MUQ. In a very basic installation of MUQ, all you need to do is specify an installation prefix. To keep all of the installed MUQ files together, we suggest using something other than /usr/local for the prefix. A typically choice may be <code>~/Installations/MUQ_INSTALL</code>. For this basic installation of MUQ, cd into <code>muq2/build/</code> and type:</p>
<pre>cmake -DCMAKE_INSTALL_PREFIX=/your/install/directory ../</pre>
<p>During this command, cmake will generate a make file, which can now be run as usual:</p>
<pre>make -j4 install</pre>
<p>The <code>-j4</code> is an option specifying that make can use 4 threads for parallel compilation.</p>
<h2>How can I learn how to use MUQ?</h2>
<ul>
<li> Look at the examples listed on our <a href="examples">examples page</a>. </li>
<li> Try out some of our examples by launching a temporary <a href=http://muq.mit.edu:8000>interactive MUQ session</a>. No installation necessary!</li>
<li> Check out the doxygen documentation <a href="https://mituq.bitbucket.io/">here</a>.  </li>
<li> Join our slack workspace and ask a question. </li>
</ul>
