<?php
$tpl['page_name'] = 'Intro';
$tpl['tab'] = 'Examples';
?>
<h2 id="Getting-started-with-MUQ-examples">Getting started with MUQ examples</h2><p>MUQ contains many usage examples, in both c++ and python, that are based on IPython notebooks.  If you are reading this on the MUQ website, this web page was actually created from an IPython notebook.  The goal of this example is to acquaint you with the formatting of our IPython examples.</p>
<p>We should point out that all examples hosted on the MUQ website (including this one) are produced from notebooks in the MUQ/examples/ folder of the MUQ source.</p>
<h3 id="Cells">Cells</h3><p>IPython notebooks are composed of many cells that each contain a short segment of code or documentation.  When code cells containing Python are run, they can also produce output cells, which are placed immediately following the code.  On the MUQ website, code cells have a light gray background and output cells have a dark gray background.  The following "Hello World" example illustrates this in Python.</p>
<pre class="prettyprint">
print "Hello World!"
</pre>

<pre class="prettyprint lang-bash" style="background-color:#D0D0D0">
Hello World!

</pre>

<p>Variables persists between cells.  Thus, a variable defined in one cell can be used in any subsequent cells.  The following two cells illustrate this fact.</p>
<pre class="prettyprint">
myGreeting = "Hello World!"
</pre>

<pre class="prettyprint">
print myGreeting
</pre>

<pre class="prettyprint lang-bash" style="background-color:#D0D0D0">
Hello World!

</pre>

<h3 id="Full-example-files">Full example files</h3><p>On each example page, the code snippets are concatenated into a complete listing at the bottom of the page.  This completed code can then be copied and used to build up your own applications.</p>
<h3 id="Handling-c++">Handling c++</h3><p>MUQ is primarily written in c++, but IPython (and Jupyter) notebooks are designed for interpreted languages like Python, Julia, and R.  To overcome this, we perform a few tricks with IPython magic commands.  See <a href="http://knudsen.mit.edu/examples/ExampleBasics/CppIntro">this example</a> for details.</p>
<h2>Completed code:</h2><pre class="prettyprint" style="height:auto;max-height:400px;">
print "Hello World!"

myGreeting = "Hello World!"

print myGreeting


</pre>

