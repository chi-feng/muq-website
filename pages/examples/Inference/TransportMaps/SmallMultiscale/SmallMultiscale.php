<?php
$tpl['page_name'] = 'SmallMultiscale';
$tpl['tab'] = 'Examples';
?>
<h1 id="Multiscale-inference-with-transport-maps">Multiscale inference with transport maps</h1><p>This notebook uses the latest version of MUQ to illustrate concepts from <a href="http://arxiv.org/abs/1507.07024">A multiscale strategy for Bayesian inference using transport maps</a> by Matthew Parno, Tarek Moselhy, and Youssef Marzouk.</p>
<h3 id="The-general-multiscale-setting">The general multiscale setting</h3><p>The idea is to take advantage of multiscale structure to decompose the usual Bayesian inference problem into two components: a coarse problem and a fine problem.  To illustrate this mathematically, let $\theta$ be a random variable taking values in $\mathbb{R}^{d_\theta}$ and let $y$ be an observable random variable (e.g., the data) taking values in $\mathbb{R}^{d_y}$.  Our goal is then to generate samples of the Bayesian posterior</p>
$$
\pi(\theta | y) \propto \pi(y|\theta)\pi(\theta).
$$<p>However, as [Parno et al.] outlines, additional structure can be exploited when the relationship between $\theta$ and $y$ exhibits multiscale behavior.  Let $\gamma$ be a intermediate coarse random variable taking values in $\mathbb{R}^{d_\gamma}$ and assume that $\theta$ and $y$ are <em>conditionally</em> independent <em>given</em> $\gamma$.  That is,</p>
$$
\pi(\theta,y|\gamma) = \pi(\theta|\gamma)\pi(y|\gamma).
$$<p>Using this fact for the joint posterior $\pi(\theta,\gamma | y)$, yields</p>
\begin{eqnarray*}
\pi(\theta,\gamma | y) &\propto& \pi(y | \theta,\gamma)\pi(\theta,\gamma)\\
&=& \pi(y|\gamma)\pi(\gamma)\pi(\theta|\gamma)
\end{eqnarray*}<p>Looking at this expression closer, we can see that $\pi(\theta,\gamma | y)$ contains a coarse posterior $\pi(y|\gamma)\pi(\gamma)$ and a coarse-to-fine distribution $\pi(\theta|\gamma)$.  Following [Parno et al.] we will use MCMC to sample the coarse posterior and then "prolongate" those coarse samples back to the fine scale using $\pi(\theta | y)$.</p>
<h3 id="A-solution-strategy">A solution strategy</h3><p>There are two challenges in performing multiscale inference as described above: first, we need to know the coarsescale prior so that we can sample $\pi(\gamma|y) \propto \pi(y|\gamma)\pi(\gamma)$, and second, we need to be able to sample<br>
$\pi(\theta|\gamma)$.  Both of these challenges can be addressed with an appropriately constructed transport map.</p>
<p>Consider the map</p>
$$
S(\gamma,\theta) = \left[\begin{array}{l} S_c(\gamma) \\ S_f(\gamma,\theta)\end{array}\right] = \left[\begin{array}{c}r_c\\r_f\end{array}\right],
$$<p>and its inverse</p>
$$
T(r_c,r_f) = \left[\begin{array}{l} T_c(r_c) \\ T_f(r_c,r_f)\end{array}\right] = \left[\begin{array}{c}\gamma\\\theta\end{array}\right],
$$<p>where $r_c$ and $r_f$ are IID standard normal random variables with the same dimensions as $\gamma$ and $\theta$, respectively.  Using $T_c$, we can sample the posterior $\pi(r_c|y)$ (instead of $\pi(\gamma|y)$) and then use the coarse map $T_f(r_c,r_f)$ to generate samples of $\pi(\theta | r_c)$.  See [Parno et al.] for more details of this approach.</p>
<h3 id="A-specific-example">A specific example</h3><p>In this notebook, we are concerned with a specific example of multiscale, which was first used for illustration in [Parno et al.].  In this example, $\theta$ is two-dimensional, $\gamma$ is one-dimensional, and they are deterministically related through</p>
$$
\gamma =  log\left(\frac{1.0}{\exp(-x_1) + \exp(-x_2)}\right)
$$<p>Also, $\theta$ has a multivariate Gaussian prior defined by</p>
$$
\theta \sim N\left(\left[\begin{array}{c}0 \\ 0\end{array}\right],\left[\begin{array}{cc}2.0 & 0.6 \\ 0.6 & 2.0\end{array}\right]\right)
$$<p>The data, $y$, is related to to $\gamma$ by</p>
$$ 
y = \exp(3\gamma) - 2 + \eta
$$<p>where $\eta\sim N(0,0.1)$ is a scalar Gaussian random variable.  Notice that $y$ does not explicitly depend on $\theta$; if $\gamma$ is known, $\theta$ is not needed to calculate $y$.  Thus, this problem satisfies the conditional independence assumption needed in the derivation of $\pi(\theta,\gamma | y)$ above.</p>
<h3 id="Setting-up-the-problem-in-MUQ">Setting up the problem in MUQ</h3><p>There are three main components of MUQ that are needed to sample the posterior $\pi(\theta|y)$ using this multiscale approach.  First is the modeling module, which we use to define ModPieces and ModGraphs describing the relationships between $\theta$, $\gamma$, and $y$.  Second is the use of transport maps, which are needed to set up the coarse scale inference problem and also to generate finescale samples from coarsescale samples.  And third, is the MCMC model for coarse scale sampling.</p>
<p>Below, we will:</p>
<ol>
<li>Define children of the ModPiece class describing the relationships between $\theta$, $\gamma$, and $y$.</li>
<li>Sample the prior to generate samples of the joint distribution $\pi(\gamma,\theta)$.</li>
<li>Construct a transport map from $(\gamma,\theta)$ to $(r_c,r_f)$ and use regression to approximate its inverse.  This will create the maps $S(\gamma,\theta)$ and $T(r_c,r_f)$.</li>
<li>Set up the coarse reference posterior $\pi(r_c|y)$ and sample it using a MALA MCMC approach.  The result will be a set of coarse posterior samples $\{r_c^{(1)},r_c^{(2)},...,r_c^{(K)}\}$.</li>
<li>Evaluate the fine map $\theta^{(i)}=T_f(r_c^{(i)},r_f)$ at each posterior sample with a random $r_f$ to generate a set of finescale samples $\{\theta^{(1)},\theta^{(2)},...,\theta^{(K)}\}$.</li>
</ol>
<h2 id="Include-the-necessary-header-files">Include the necessary header files</h2><pre class="prettyprint">

// std library includes
#include &lt;fstream&gt;
#include &lt;iostream&gt;

// boost includes
#include &lt;Eigen/Core&gt;
#include &lt;Eigen/Sparse&gt;

// muq utilities includes
#include "MUQ/Utilities/HDF5File.h"
#include "MUQ/Utilities/RandomGenerator.h"
#include "MUQ/Utilities/MultiIndex/MultiIndexFactory.h"

// muq inference includes
#include "MUQ/Inference/MCMC/MCMCBase.h"
#include "MUQ/Inference/ProblemClasses/InferenceProblem.h"
#include "MUQ/Inference/MAP/MAPbase.h"
#include "MUQ/Inference/TransportMaps/MapFactory.h"

// muq modelling includes
#include "MUQ/Modelling/ModPieceTemplates.h"
#include "MUQ/Modelling/ModGraphPiece.h"
#include "MUQ/Modelling/GaussianPair.h"
#include "MUQ/Modelling/DensityProduct.h"
#include "MUQ/Modelling/EmpiricalRandVar.h"
#include "MUQ/Modelling/VectorPassthroughModel.h"

// namespaces
using namespace std;
using namespace muq::Modelling;
using namespace muq::Utilities;
using namespace muq::Inference;
</pre>

<h2 id="Define-a-fine-to-coarse-model">Define a fine-to-coarse model</h2><p>Here we define the fine-to-coarse model by creating a child of the <a href="http://muq.mit.edu/develop-docs/classmuq_1_1Modelling_1_1ModPiece.html"><code>ModPiece</code></a> class.  Notice that there is only one input $\theta$, and one output $\gamma$, so we used the template class <a href="http://muq.mit.edu/develop-docs/classmuq_1_1Modelling_1_1OneInputNoDerivModPiece.html"><code>OneInputNoDerivModPiece</code></a>.</p>
<pre class="prettyprint">
class Fine2Coarse : public OneInputNoDerivModPiece {
public:

  Fine2Coarse() : OneInputNoDerivModPiece(2, 1) {}

  virtual Eigen::VectorXd EvaluateImpl(const Eigen::VectorXd&amp; x) override
  {
    Eigen::VectorXd y(1);

    y(0) = log(1.0 / (exp(-1.0 * x(0)) + exp(-1.0 * x(1))));
    return y;
  }
};
</pre>

<h2 id="Define-a-coarse-to-data-model">Define a coarse-to-data model</h2><p>The following cell creates a child of <code>ModPiece</code> describing the deterministic relationship between the coarse variable $\gamma$ and the data $y$.  The additive error $\eta$ will come into play later on in the likelihood definition.</p>
<pre class="prettyprint">

class Coarse2Data : public OneInputNoDerivModPiece {
public:

  Coarse2Data(double bIn = 1.0) : OneInputNoDerivModPiece(1, 1), b(bIn) {}

  virtual Eigen::VectorXd EvaluateImpl(const Eigen::VectorXd&amp; x) override
  {
    Eigen::VectorXd y(1);

    y(0) = pow(exp(x(0)), 3) - b;
    return y;
  }

private:

  double b;
};
</pre>

<h2 id="A-function-to-generate-fine-and-coarse-samples">A function to generate fine and coarse samples</h2><pre class="prettyprint">

Eigen::MatrixXd GenerateSamples(int numSamps, shared_ptr&lt;RandVar&gt; priorRv, shared_ptr&lt;ModPiece&gt; f2c)
{
   
    Eigen::MatrixXd allSamples(3,numSamps);
    
    // first generate the prior samples
    allSamples.bottomRows(2) = priorRv-&gt;Sample(numSamps);
    
    // now generate the coarse samples
    allSamples.topRows(1) = f2c-&gt;EvaluateMulti(allSamples.bottomRows(2));

    return allSamples;
}
</pre>

<h2 id="A-function-to-construct-the-transport-maps">A function to construct the transport maps</h2><p>A total order multiindex set is used here to construct the transport map.  Notice that the multiindex set returned by <a href="http://muq.mit.edu/develop-docs/classmuq_1_1Utilities_1_1MultiIndexFactory.html#ab330b4895c26312c1573df5ae29fede3"><code>CreateTriTotalOrder</code></a> guarantees that the map is lower triangular, thus allowing us to split it into $T_c$ and $T_f$.  The <a href="http://muq.mit.edu/develop-docs/classmuq_1_1Utilities_1_1MultiIndexFactory.html#a49c69e36687288788ee4b6fa06186e7a"><code>CreateTriHyperbolic</code></a> function is another alternative, which produces more parsimonious multiindex sets.</p>
<p>To construct $T$ from $S$, we use regression.  In this cell, we first build $S$ with a call to <a href=""><code>BuildToNormal</code></a>. Then we evaluate $S$ at all the prior samples with the <a href="http://muq.mit.edu/develop-docs/classmuq_1_1Modelling_1_1ModPiece.html#a016924289bea1f6095f6edc8f900fc73"><code>EvaluateMulti</code></a> member function.  Finally, the <a href="http://muq.mit.edu/develop-docs/classmuq_1_1Inference_1_1MapFactory.html#adb925c3f55f2ebe37ead23c798c928b6"><code>RegressSampsToSamps</code></a> function uses the inputs and outputs of the $S$ in a regression framework to approximate $T$.</p>
<pre class="prettyprint">

shared_ptr&lt;TransportMap&gt; BuildMap(int maxOrder, Eigen::MatrixXd const&amp; allSamples)
{

    auto multis = MultiIndexFactory::CreateTriTotalOrder(allSamples.rows(),maxOrder);
    auto invmap = MapFactory::BuildToNormal(allSamples, multis);
    
    Eigen::MatrixXd refSamps = invmap-&gt;EvaluateMulti(allSamples);
    
    return MapFactory::RegressSampsToSamps(refSamps, allSamples, multis);
}
</pre>

<h2 id="Function-to-perform-coarse-MCMC">Function to perform coarse MCMC</h2><p>The following function sets of the coarse inference problem and then samples it using MCMC.</p>
<h3 id="Step-1:-Create-a-ModGraph-to-describe-the-posterior.">Step 1: Create a <a href="http://muq.mit.edu/develop-docs/classmuq_1_1Modelling_1_1ModGraph.html"><code>ModGraph</code></a> to describe the posterior.</h3><p>Notice that the coarse map $T_c$ is separated from the entire map $T$ using the <a href="http://muq.mit.edu/develop-docs/classmuq_1_1Inference_1_1TransportMap.html#aedccdd8da0d9dcd7946bf8bd47f1f291"><code>head</code></a> function.  Like the <code>Eigen::VectorXd</code> class, the <a href="http://muq.mit.edu/develop-docs/classmuq_1_1Inference_1_1TransportMap.html"><code>TransportMap</code></a> class has functions like <code>head</code>, <code>tail</code>, and <code>segment</code>.  All of these slices are performed on the map output.</p>
<pre class="prettyprint">

Eigen::MatrixXd RunCoarseMCMC(Eigen::VectorXd const&amp; data, shared_ptr&lt;TransportMap&gt; jointMap)
{
  auto graph = make_shared&lt;ModGraph&gt;();
  
  graph-&gt;AddNode(make_shared&lt;VectorPassthroughModel&gt;(1),"inferenceTarget");
  graph-&gt;AddNode(jointMap-&gt;head(1),"CoarseMap");
  graph-&gt;AddEdge("inferenceTarget","CoarseMap",0);
  
  graph-&gt;AddNode(make_shared&lt;Coarse2Data&gt;(2.0), "forwardModel");
  graph-&gt;AddEdge("CoarseMap","forwardModel",0);
  
  double dataVar = 0.1;
  graph-&gt;AddNode(make_shared&lt;GaussianDensity&gt;(data,dataVar),"likelihood");
  graph-&gt;AddEdge("forwardModel","likelihood",0);
  
  Eigen::VectorXd priorMu = Eigen::VectorXd::Ones(1);
  graph-&gt;AddNode(make_shared&lt;GaussianDensity&gt;(priorMu,1.0),"prior");
  graph-&gt;AddEdge("inferenceTarget","prior",0);
  
  graph-&gt;AddNode(make_shared&lt;DensityProduct&gt;(2),"posterior");
  graph-&gt;AddEdge("prior","posterior",0);
  graph-&gt;AddEdge("likelihood","posterior",1);
  
</pre>

<h3 id="Step-2:-Create-an-inference-problem">Step 2: Create an inference problem</h3><p>Here the ModGraph is translated into an <a href="http://muq.mit.edu/develop-docs/classmuq_1_1Inference_1_1InferenceProblem.html"><code>InferenceProblem</code></a>, which the MCMC algorithms know how to work with.</p>
<pre class="prettyprint">

  auto problem = make_shared&lt;InferenceProblem&gt;(graph);
</pre>

<h3 id="Step-3:-Define-the-MCMC-parameters">Step 3: Define the MCMC parameters</h3><p>See <a href="http://muq.mit.edu/develop-docs/parameters.html">our list of parameters</a> for a more comprehensive list of options.  Note that these options can also be conveniently stored in an XML or JSON file and read with boost's <a href="http://www.boost.org/doc/libs/1_60_0/doc/html/property_tree/parsers.html#property_tree.parsers.xml_parser">xml parser</a> or <a href="http://www.boost.org/doc/libs/1_60_0/doc/html/property_tree/parsers.html#property_tree.parsers.json_parser">json parser</a></p>
<pre class="prettyprint">

  // define the properties and tuning parameters for preconditioned MALA
  boost::property_tree::ptree params;
  params.put("MCMC.Method", "SingleChainMCMC");
  params.put("MCMC.Kernel", "MHKernel");
  params.put("MCMC.Proposal", "PreMALA");
  params.put("MCMC.Steps", 20000);
  params.put("MCMC.BurnIn", 5000);
  params.put("MCMC.MH.PropSize", 0.01);
  params.put("Verbose", 3);
</pre>

<h3 id="Step-4:-Construct-the-MCMC-sampler">Step 4: Construct the MCMC sampler</h3><p>Use the options defined in the <code>params</code> ptree to construct an MCMC sampler.</p>
<pre class="prettyprint">

  // construct a MCMC sampling task from the parameters and the inference problem.
  auto mcmcSampler = MCMCBase::ConstructMCMC(problem, params);
  
  
</pre>

<h3 id="Step-5:-Sample-the-coarse-posterior">Step 5: Sample the coarse posterior</h3><p>Here is where all the work actually happens.  From an initial starting point of $r_c=0.5$, this cell runs the MCMC chain and returns and <code>Eigen::MatrixXd</code> with the samples.  Each column of the matrix is a sample.</p>
<pre class="prettyprint">

  Eigen::VectorXd startingPoint(1);
  startingPoint &lt;&lt; 0.5;
  
  auto mcmcChain = mcmcSampler-&gt;Sample(startingPoint);

  return mcmcChain-&gt;GetAllSamples();
}
</pre>

<h2 id="A-function-to-generate-finescale-samples">A function to generate finescale samples</h2><p>After running the coarsescale MCMC, this function will generate realizations of the finescale posterior $\pi(\theta|y)$ using $T_f$.</p>
<pre class="prettyprint">

Eigen::MatrixXd SampleFinescale(Eigen::MatrixXd const&amp; coarseSamples, shared_ptr&lt;TransportMap&gt; jointMap)
{
  int numSamps = coarseSamples.cols();
  Eigen::MatrixXd refSamps(3,numSamps);
  
  // set r_c to come from the MCMC samples
  refSamps.row(0) = coarseSamples;
  
  // generate independent samples of r_f
  refSamps.bottomRows(2) = RandomGenerator::GetNormalRandomMatrix(2,numSamps);
  
  return jointMap-&gt;EvaluateMulti(refSamps);
}
</pre>

<h2 id="Put-all-the-functions-together">Put all the functions together</h2><p>Here is where the rubber meets the road!  First we define the prior $\pi(\theta)$.  Then we use the classes and functions above to generate samples of $\pi(\theta|y)$.</p>
<pre class="prettyprint">

int main(){
  
  // Define the prior
  Eigen::VectorXd mu = Eigen::VectorXd::Zero(2);
  Eigen::MatrixXd cov(2,2);
  cov &lt;&lt; 2.0, 0.6,
         0.6, 2.0;
  auto priorRv = make_shared&lt;GaussianRV&gt;(mu,cov);
  
</pre>

<pre class="prettyprint">

  auto f2c = make_shared&lt;Fine2Coarse&gt;();
</pre>

<pre class="prettyprint">

  int numSamps = 5e4;
  Eigen::MatrixXd allSamps = GenerateSamples(numSamps, priorRv, f2c);
</pre>

<pre class="prettyprint">

  int maxOrder = 7;
  auto map = BuildMap(maxOrder, allSamps);
</pre>

<pre class="prettyprint">

  Eigen::VectorXd data(1);
  data &lt;&lt; -1.8;
  Eigen::MatrixXd coarsePostSamps = RunCoarseMCMC(data, map);
</pre>

<pre class="prettyprint">

  Eigen::MatrixXd finePostSamps = SampleFinescale(coarsePostSamps, map);
</pre>

<h3 id="Save-the-samples">Save the samples</h3><p>The following cell uses an instance of the <code>HDF5File</code> class to store the samples.  Note that older version of MUQ only had the <code>HDF5Wrapper</code> class, which creates a persistent interface to a single global HDF5 file.  The <code>HDF5File</code>, on the other hand, allows opening and closing several HDF5 files simultaneously within a single scope.</p>
<pre class="prettyprint">

  HDF5File fout("MultiscaleResults.h5");
  fout.WriteMatrix("/Training/Samples",allSamps);
  fout.WriteScalarAttribute("/Training", "Number of Samples", numSamps);
  fout.WriteScalarAttribute("/Training", "Maximum Polynomial Order", maxOrder);
  
  fout.WriteMatrix("/Posterior/CoarseSamples", coarsePostSamps);
  fout.WriteMatrix("/Posterior/FineSamples",finePostSamps);
  
  fout.CloseFile();
  return 0;
}
</pre>

<h2 id="If-you-build-it,-the-samples-will-come...">If you build it, the samples will come...</h2><p>See the <a href="http://http://muq.mit.edu/examples/Inference/TransportMaps/SmallMultiscale/CMakeLists.txt"><code>cmake</code></a> file for more details.</p>
<pre class="prettyprint">
cd build; cmake ..; make; cd ../
</pre>

<pre class="prettyprint lang-bash" style="background-color:#D0D0D0">
-- Configuring done
-- Generating done
-- Build files have been written to: /Users/mparno/Documents/Repositories/MUQ/MUQ/MUQ/examples/Inference/TransportMaps/MultiscaleInference/build
Scanning dependencies of target SmallMultiscale
[100%] Building CXX object CMakeFiles/SmallMultiscale.dir/SmallMultiscale.cpp.o
Linking CXX executable SmallMultiscale
[100%] Built target SmallMultiscale

</pre>

<h2 id="Run-the-code">Run the code</h2><pre class="prettyprint">
build/SmallMultiscale
</pre>

<pre class="prettyprint lang-bash" style="background-color:#D0D0D0">
10% Complete
  MH: Acceptance rate =  47.9%
20.0% Complete
  MH: Acceptance rate =  48.5%
30.0% Complete
  MH: Acceptance rate =  48.1%
40.0% Complete
  MH: Acceptance rate =  50.9%
50.0% Complete
  MH: Acceptance rate =  51.7%
60.0% Complete
  MH: Acceptance rate =  53.1%
70.0% Complete
  MH: Acceptance rate =  54.2%
80.0% Complete
  MH: Acceptance rate =  54.7%
90.0% Complete
  MH: Acceptance rate =  54.8%
100.0% Complete
  MH: Acceptance rate =  55.0%

</pre>

<h1 id="Analyze-the-results">Analyze the results</h1><p>Now we switch over to Python to plot the results.  The finescale samples are read from the HDF5 file created in c++ and matplotlib is used for plotting.</p>
<pre class="prettyprint">
import h5py
import matplotlib.pyplot as plt

f = h5py.File('MultiscaleResults.h5')
postSamps = f['/Posterior/FineSamples']

plt.plot(postSamps[1,0:15000:2],postSamps[2,0:15000:2],'.')
plt.xlim([-2,2])
plt.ylim([-2,2])
plt.show()
</pre>

<img src="data:image/png;base64,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
"
>

<h2>Completed code:</h2><h3>Python</h3>

<pre class="prettyprint" style="height:auto;max-height:400px;">
import h5py
import matplotlib.pyplot as plt

f = h5py.File('MultiscaleResults.h5')
postSamps = f['/Posterior/FineSamples']

plt.plot(postSamps[1,0:15000:2],postSamps[2,0:15000:2],'.')
plt.xlim([-2,2])
plt.ylim([-2,2])
plt.show()


</pre>

<h3>SmallMultiscale.cpp</h3>

<pre class="prettyprint" style="height:auto;max-height:400px;">

// std library includes
#include &lt;fstream&gt;
#include &lt;iostream&gt;

// boost includes
#include &lt;Eigen/Core&gt;
#include &lt;Eigen/Sparse&gt;

// muq utilities includes
#include "MUQ/Utilities/HDF5File.h"
#include "MUQ/Utilities/RandomGenerator.h"
#include "MUQ/Utilities/MultiIndex/MultiIndexFactory.h"

// muq inference includes
#include "MUQ/Inference/MCMC/MCMCBase.h"
#include "MUQ/Inference/ProblemClasses/InferenceProblem.h"
#include "MUQ/Inference/MAP/MAPbase.h"
#include "MUQ/Inference/TransportMaps/MapFactory.h"

// muq modelling includes
#include "MUQ/Modelling/ModPieceTemplates.h"
#include "MUQ/Modelling/ModGraphPiece.h"
#include "MUQ/Modelling/GaussianPair.h"
#include "MUQ/Modelling/DensityProduct.h"
#include "MUQ/Modelling/EmpiricalRandVar.h"
#include "MUQ/Modelling/VectorPassthroughModel.h"

// namespaces
using namespace std;
using namespace muq::Modelling;
using namespace muq::Utilities;
using namespace muq::Inference;

class Fine2Coarse : public OneInputNoDerivModPiece {
public:

  Fine2Coarse() : OneInputNoDerivModPiece(2, 1) {}

  virtual Eigen::VectorXd EvaluateImpl(const Eigen::VectorXd&amp; x) override
  {
    Eigen::VectorXd y(1);

    y(0) = log(1.0 / (exp(-1.0 * x(0)) + exp(-1.0 * x(1))));
    return y;
  }
};


class Coarse2Data : public OneInputNoDerivModPiece {
public:

  Coarse2Data(double bIn = 1.0) : OneInputNoDerivModPiece(1, 1), b(bIn) {}

  virtual Eigen::VectorXd EvaluateImpl(const Eigen::VectorXd&amp; x) override
  {
    Eigen::VectorXd y(1);

    y(0) = pow(exp(x(0)), 3) - b;
    return y;
  }

private:

  double b;
};


Eigen::MatrixXd GenerateSamples(int numSamps, shared_ptr&lt;RandVar&gt; priorRv, shared_ptr&lt;ModPiece&gt; f2c)
{
   
    Eigen::MatrixXd allSamples(3,numSamps);
    
    // first generate the prior samples
    allSamples.bottomRows(2) = priorRv-&gt;Sample(numSamps);
    
    // now generate the coarse samples
    allSamples.topRows(1) = f2c-&gt;EvaluateMulti(allSamples.bottomRows(2));

    return allSamples;
}


shared_ptr&lt;TransportMap&gt; BuildMap(int maxOrder, Eigen::MatrixXd const&amp; allSamples)
{

    auto multis = MultiIndexFactory::CreateTriTotalOrder(allSamples.rows(),maxOrder);
    auto invmap = MapFactory::BuildToNormal(allSamples, multis);
    
    Eigen::MatrixXd refSamps = invmap-&gt;EvaluateMulti(allSamples);
    
    return MapFactory::RegressSampsToSamps(refSamps, allSamples, multis);
}


Eigen::MatrixXd RunCoarseMCMC(Eigen::VectorXd const&amp; data, shared_ptr&lt;TransportMap&gt; jointMap)
{
  auto graph = make_shared&lt;ModGraph&gt;();
  
  graph-&gt;AddNode(make_shared&lt;VectorPassthroughModel&gt;(1),"inferenceTarget");
  graph-&gt;AddNode(jointMap-&gt;head(1),"CoarseMap");
  graph-&gt;AddEdge("inferenceTarget","CoarseMap",0);
  
  graph-&gt;AddNode(make_shared&lt;Coarse2Data&gt;(2.0), "forwardModel");
  graph-&gt;AddEdge("CoarseMap","forwardModel",0);
  
  double dataVar = 0.1;
  graph-&gt;AddNode(make_shared&lt;GaussianDensity&gt;(data,dataVar),"likelihood");
  graph-&gt;AddEdge("forwardModel","likelihood",0);
  
  Eigen::VectorXd priorMu = Eigen::VectorXd::Ones(1);
  graph-&gt;AddNode(make_shared&lt;GaussianDensity&gt;(priorMu,1.0),"prior");
  graph-&gt;AddEdge("inferenceTarget","prior",0);
  
  graph-&gt;AddNode(make_shared&lt;DensityProduct&gt;(2),"posterior");
  graph-&gt;AddEdge("prior","posterior",0);
  graph-&gt;AddEdge("likelihood","posterior",1);
  


  auto problem = make_shared&lt;InferenceProblem&gt;(graph);


  // define the properties and tuning parameters for preconditioned MALA
  boost::property_tree::ptree params;
  params.put("MCMC.Method", "SingleChainMCMC");
  params.put("MCMC.Kernel", "MHKernel");
  params.put("MCMC.Proposal", "PreMALA");
  params.put("MCMC.Steps", 20000);
  params.put("MCMC.BurnIn", 5000);
  params.put("MCMC.MH.PropSize", 0.01);
  params.put("Verbose", 3);


  // construct a MCMC sampling task from the parameters and the inference problem.
  auto mcmcSampler = MCMCBase::ConstructMCMC(problem, params);
  
  


  Eigen::VectorXd startingPoint(1);
  startingPoint &lt;&lt; 0.5;
  
  auto mcmcChain = mcmcSampler-&gt;Sample(startingPoint);

  return mcmcChain-&gt;GetAllSamples();
}


Eigen::MatrixXd SampleFinescale(Eigen::MatrixXd const&amp; coarseSamples, shared_ptr&lt;TransportMap&gt; jointMap)
{
  int numSamps = coarseSamples.cols();
  Eigen::MatrixXd refSamps(3,numSamps);
  
  // set r_c to come from the MCMC samples
  refSamps.row(0) = coarseSamples;
  
  // generate independent samples of r_f
  refSamps.bottomRows(2) = RandomGenerator::GetNormalRandomMatrix(2,numSamps);
  
  return jointMap-&gt;EvaluateMulti(refSamps);
}


int main(){
  
  // Define the prior
  Eigen::VectorXd mu = Eigen::VectorXd::Zero(2);
  Eigen::MatrixXd cov(2,2);
  cov &lt;&lt; 2.0, 0.6,
         0.6, 2.0;
  auto priorRv = make_shared&lt;GaussianRV&gt;(mu,cov);
  


  auto f2c = make_shared&lt;Fine2Coarse&gt;();


  int numSamps = 5e4;
  Eigen::MatrixXd allSamps = GenerateSamples(numSamps, priorRv, f2c);


  int maxOrder = 7;
  auto map = BuildMap(maxOrder, allSamps);


  Eigen::VectorXd data(1);
  data &lt;&lt; -1.8;
  Eigen::MatrixXd coarsePostSamps = RunCoarseMCMC(data, map);


  Eigen::MatrixXd finePostSamps = SampleFinescale(coarsePostSamps, map);


  HDF5File fout("MultiscaleResults.h5");
  fout.WriteMatrix("/Training/Samples",allSamps);
  fout.WriteScalarAttribute("/Training", "Number of Samples", numSamps);
  fout.WriteScalarAttribute("/Training", "Maximum Polynomial Order", maxOrder);
  
  fout.WriteMatrix("/Posterior/CoarseSamples", coarsePostSamps);
  fout.WriteMatrix("/Posterior/FineSamples",finePostSamps);
  
  fout.CloseFile();
  return 0;
}


</pre>

