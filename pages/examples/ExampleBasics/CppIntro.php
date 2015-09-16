<?php
$tpl['page_name'] = 'CppIntro';
$tpl['tab'] = 'Examples';
?>
<h2 id="C++-Examples-in-IPython">C++ Examples in IPython<a class="anchor-link" href="#C++-Examples-in-IPython">&#182;</a></h2><p>MUQ is written primarily in c++ with some lightweight python wrappers.  Thus, we have many examples that are written solely in c++.  So how can we use IPython notebooks to create these examples?  We build up a normal c++ file using the IPython <code>%%writefile</code> magic command and then compile and run it with the <code>%%bash</code> magic command.  On the MUQ website, the magic commands are removed for clarity; only the code is displayed.</p>
<h3 id="Hello-world-example">Hello world example<a class="anchor-link" href="#Hello-world-example">&#182;</a></h3><p>To illustrate a c++ example, we use the standard "Hello World" example.</p>
<p>First, we include the <code>iostream</code> header and open up the <code>std</code> namespace.</p>
<pre class="prettyprint">
#include &lt;iostream&gt;
using namespace std;
</pre>

<p>Now we can create the main function, which will print "Hello World!" to stdout.</p>
<pre class="prettyprint">
int main()
{
    cout &lt;&lt; "Hello World" &lt;&lt; endl;
    return 0;
}
</pre>

<h4 id="Compiling-the-source">Compiling the source<a class="anchor-link" href="#Compiling-the-source">&#182;</a></h4><p>We compile this example using a very simple CMake file that simply creates an executable called HelloWorld in the build folder (see the MUQ source repository for details on the CMake file).</p>
<pre class="prettyprint">
cd build; cmake ../ &gt; BuildLog.txt; make; cd ../
</pre>

<pre class="prettyprint lang-bash" style="background-color:#D0D0D0">
Scanning dependencies of target HelloWorld
[100%] Building CXX object CMakeFiles/HelloWorld.dir/HelloWorld.cpp.o
Linking CXX executable HelloWorld
[100%] Built target HelloWorld

</pre>

<h4 id="Running-the-executable">Running the executable<a class="anchor-link" href="#Running-the-executable">&#182;</a></h4><p>Not that we've compiled the executable, we can run the executable and have our computer to greet the world.</p>
<pre class="prettyprint">
build/HelloWorld
</pre>

<pre class="prettyprint lang-bash" style="background-color:#D0D0D0">
Hello World

</pre>

<h2>Completed code:</h2><pre class="prettyprint" style="height:auto;max-height:400px;">
#include &lt;iostream&gt;
using namespace std;

int main()
{
    cout &lt;&lt; "Hello World" &lt;&lt; endl;
    return 0;
}


</pre>

