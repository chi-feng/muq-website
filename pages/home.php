<?php
$tpl['page_name'] = 'Home';
$tpl['tab'] = 'Home';
?>
<h2>What is MUQ?</h2>
<p>In a nutshell, MUQ is a collection of tools for constructing models and a collection of UQ-orientated algorithms for working on those model. Our goal is to provide an easy and clean way to set up and efficiently solve uncertainty quantification problems. On the modelling side, we have a suite of tools for:</p>
<ul>
<li>Combining many simple model components into a single sophisticated model.</li>
<li>Propagating derivative information through sophisticated models.</li>
<li>Solving systems of partial differential equations (via <a href="http://libmesh.github.io/">LibMesh</a>)</li>
<li>Integrating ordinary differential equations and differential algebraic equations (via <a href="https://computation.llnl.gov/casc/sundials/main.html">Sundials</a>)</li>
</ul>
<p>Furthermore, on the algorithmic side, we have tools for</p>
<ul>
<li>Performing Markov chain Monte Carlo (MCMC) sampling</li>
<li>Constructing polynomial chaos expansions (PCE)</li>
<li>Computing Karhunen-Loeve expansions</li>
<li>Building optimal transport maps</li>
<li>Solving nonlinear constrained optimization problems (both internally and through <a href="http://ab-initio.mit.edu/wiki/index.php/NLopt">NLOPT</a>)</li>
<li>Solving robust optimization problems</li>
<li>Regression (including Gaussian process regression)</li>
</ul>
<h2>Installation</h2>
<h3>Getting the source</h3>
<p>The first step is to download and install MUQ. Currently, the best way to get started is by checking our our git repository from this bitbucket site. Go to the folder you where you want to keep the MUQ source code, and use:</p>
<pre>git clone https://bitbucket.org/mituq/muq.git</pre>
<p>If you want the latest (but possibly unstable) version of MUQ, you can clone our <code>develop</code> branch:</p>
<pre>git clone -b develop https://bitbucket.org/mituq/muq.git</pre>
<h3>Compiling the source</h3>
<p>Now that you have the source, you need to compile and install MUQ. In a very basic installation of MUQ, all you need to do is specify an installation prefix. To keep all of the installed MUQ files together, we suggest using something other than /usr/local for the prefix. A typically choice may be <code>~/MUQ_INSTALL</code>. For this basic installation of MUQ, change into <code>muq/MUQ/build/</code> and type:</p>
<pre>cmake -DCMAKE_INSTALL_PREFIX=/your/install/directory ../</pre>
<p>For more information on installing MUQ, see our installation guide. During this command, cmake will generate a make file, which can now be run as usual:</p>
<pre>make -j4 install</pre>
<p>The <code>-j4</code> is an option specifying that make can use 4 threads for parallel compilation.</p>
<h2>Learning how to use MUQ</h2>
<p>The quickest way to start MUQ'ing around is to check out our <a href="examples">examples</a>.  We are also working on more extensive tutorials, so stay tuned!</p>
<p>More in depth documentation can also be found in our <a href="documentation">Doxygen generated documentation.</a></p>
<h2>Where can I get help?</h2>
<p>There are currently two ways to learn more about MUQ and get help. The first is to dig into the doxygen documentation listed <a href="documentation">here</a> and the second is to post a question to our Q&A site. A manual is also in the works for future inquiries.</p>
