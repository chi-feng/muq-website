<?php
$tpl['page_name'] = 'CppIntro';
$tpl['tab'] = 'Examples';
?>
<h2 id="C++-Examples-in-IPython">C++ Examples in IPython</h2><p>MUQ is written primarily in c++ with some lightweight python wrappers.  Thus, we have many examples that are written solely in c++.  Unfortunately, this makes it slightly more difficult to use IPython notebooks to document our examples.  Instead of running the code in the notebook itself (like we do for Python examples), we construct a normal c++ file by concatenating code cells in the notebook.  This is facilitated by the IPython <a href="https://ipython.org/ipython-doc/dev/interactive/magics.html#cellmagic-writefile">%%writefile</a> magic command.  Once the file is created, we then compile and run it as you would with any c++ code.  This is possible using the <a href="https://ipython.org/ipython-doc/dev/interactive/magics.html#cellmagic-bash">%%bash</a> magic command.  These magic commands are visible in the example source, but in the MUQ website, they are removed for clarity; only the code is displayed on the website.</p>
<h3 id="Hello-world-example">Hello world example</h3><p>To illustrate a c++ example, we use the standard "Hello World" example.</p>
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

<h4 id="Compiling-the-source">Compiling the source</h4><p>We compile this example using a very simple CMake file that simply creates an executable called HelloWorld in the build folder (see the MUQ source repository for details on the CMake file).</p>
<pre class="prettyprint">
cd build; cmake ../ &gt; BuildLog.txt; make; cd ../
</pre>

<pre class="prettyprint lang-bash" style="background-color:#D0D0D0">
Scanning dependencies of target HelloWorld
[100%] Building CXX object CMakeFiles/HelloWorld.dir/HelloWorld.cpp.o
Linking CXX executable HelloWorld
[100%] Built target HelloWorld

</pre>

<h4 id="Running-the-executable">Running the executable</h4><p>Not that we've compiled the executable, we can run the executable to greet the world!</p>
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

