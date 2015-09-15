<?php
$tpl['page_name'] = 'Home';
$tpl['tab'] = 'Home';
?>
<h3>What is MUQ?</h3>
<p>In a nutshell, MUQ is a collection of tools for constructing models and a collection of UQ-orientated algorithms for working on those model. Our goal is to provide an easy and clean way to set up and efficiently solve uncertainty quantification problems. On the modelling side, we have a suite of tools for:</p>
<ul>
<li>Combining many simple model components into a single sophisticated model.</li>
<li>Propagating derivative information through sophisticated models.</li>
<li>Solving systems of partial differential equations (via LibMesh)</li>
<li>Integrating ordinary differential equations and differential algebraic equations (via Sundials)</li>
</ul>
<p>Furthermore, on the algorithmic side, we have tools for</p>
<ul>
<li>Performing Markov chain Monte Carlo (MCMC) sampling</li>
<li>Constructing polynomial chaos expansions (PCE)</li>
<li>Computing Karhunen-Loeve expansions</li>
<li>Building optimal transport maps</li>
<li>Solving nonlinear constrained optimization problems (both internally and through NLOPT)</li>
<li>Solving robust optimization problems</li>
<li>Regression (including Gaussian process regression)</li>
</ul>
<h3>How do I get started?</h3>
<p>The first step is to download and install MUQ. Currently, the best way to get started is by checking our our git repository from this bitbucket site. Go to the folder you where you want to keep the MUQ source code, and use:</p>
<pre>git clone https://bitbucket.org/mituq/muq.git</pre>
<p>Now that you have the MUQ source, you need to compile and install MUQ. In a very basic installation of MUQ, all you need to do is specify an installation prefix. To keep all of the installed MUQ files together, we suggest using something other than /usr/local for the prefix. A typically choice may be <code>~/MUQ_INSTALL</code>. For this basic installation of MUQ, change into <code>muq/MUQ/build/</code> and type:</p>
<pre>cmake -DCMAKE_INSTALL_PREFIX=/your/install/directory ../</pre>
<p>For more information on installing MUQ, see our installation guide. During this command, cmake will generate a make file, which can now be run as usual:</p>
<pre>make -j4 install</pre>
<p>The <code>-j4</code> is an option specifying that make can use 4 threads for parallel compilation. 3. After installing MUQ, check out the examples in the <code>muq/MUQ/examples</code> to start MUQ'ing around! Additional Doxygen generated documentation can also be found here.</p>
<h3>Where can I get help?</h3>
<p>There are currently two ways to learn more about MUQ and get help. The first is to dig into the doxygen documentation found here and the second is to post a question to our Q&A site. A manual is also in the works for future inquiries.</p>
