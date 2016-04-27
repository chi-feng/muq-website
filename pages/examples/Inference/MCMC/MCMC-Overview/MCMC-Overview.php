<?php
$tpl['page_name'] = 'MCMC-Overview';
$tpl['tab'] = 'Examples';
?>
<h1 id="mcmc-overview">MCMC Overview</h1>
<p>In general, MUQ constructs an MCMC algorithm from three components: a chain, a kernel, and a proposal.  The chain stores the states and calls the kernel to move from one state to another.  Metropolis-Hastings based kernels then call the proposal.  </p>
<p>In MUQ, there are two ways to to set up this three component hiearchy: a high level API that constructs the entire MCMC algorithm from a single <code>ptree</code> and lower level API where the user is required to construct each piece manually.  The lower level gives the user more control over the each component but requires a bit more code and is not as easy to drive with a simple XML file.</p>
<p>The goal of this example is to provide several examples of using both the high-level and low-level API&#39;s.  We will use a classic non-Gaussian target density that is sometimes called the &quot;banana&quot;, &quot;boomerang&quot;, or &quot;Rosenbrock&quot; density.</p>
<p>More information about available <code>ptree</code> parameters can be found on MUQ&#39;s <a href="http://muq.mit.edu/develop-docs/parameters.html">doxygen site</a>.</p><h3 id="necessary-includes">Necessary includes</h3><pre class="prettyprint">

#include &lt;Eigen/Core&gt;
#include &lt;boost/property_tree/ptree.hpp&gt;

#include "MUQ/Modelling/ModPiece.h"
#include "MUQ/Modelling/Density.h"

#include "MUQ/Modelling/EmpiricalRandVar.h"
#include "MUQ/Modelling/GaussianDensity.h"

#include "MUQ/Inference/ProblemClasses/SamplingProblem.h"

#include "MUQ/Inference/MCMC/MCMCBase.h"
#include "MUQ/Inference/MCMC/MCMCProposal.h"
#include "MUQ/Inference/ProblemClasses/HessianMetric.h"

#include "MUQ/Inference/MCMC/SingleChainMCMC.h"
#include "MUQ/Inference/MCMC/MHProposal.h"
#include "MUQ/Inference/MCMC/MHKernel.h"
#include "MUQ/Inference/MCMC/PreMALA.h"
#include "MUQ/Inference/MCMC/MMALA.h"

#include "MUQ/Inference/MCMC/TransportMapKernel.h"

#include "MUQ/Utilities/multiIndex/MultiIndexFactory.h"
#include "MUQ/Inference/TransportMaps/TransportMap.h"
#include "MUQ/Inference/TransportMaps/MapFactory.h"

using namespace std;
using namespace Eigen;

using namespace muq::Modelling;
using namespace muq::Inference;
using namespace muq::Utilities;
</pre>

<h3 id="define-the-target-density">Define the target density</h3>
<p>Let $\theta_1$ and $\theta_2$ be our target random variables, which are defined in terms of two standard normal reference random variables $r_1$ and $r_2$.  The transformation is given by
$$
T^{-1}\left(\begin{array}{c}r_1 \\ r_2 \end{array}\right) = \left[\begin{array}{l} \theta_1/a \\  a\theta_2 - ab(\theta_1^2/a^2 + a^2)\end{array}\right],
$$
where $a=b=1$ are fixed scalar parameters.  Notice that the Jacobian of this transformation is always 1.  This means that the target density $\pi(\theta)$ is simply 
$$
\pi(\theta) = \frac{1}{2\pi}\exp{\left[-\frac{1}{2}T(\theta)^T T(\theta)\right]}.
$$</p>
<p>In the following cell, the <code>Boomerang</code> class defines this density as a child of the <code>muq::Modelling::Density</code> class.  Notice that a Gauss-Newton approximation to the Hessian is used.  For this problem, the Gauss-Newton Hessian is guaranteed to be positive definite and can thus be used as the precision matrix in a multivariate Gaussian proposal density.</p><pre class="prettyprint">

class Boomerang : public Density{
  
public:
  Boomerang() : Density(Eigen::VectorXi::Constant(1,2), true, true, false, true){};
  
private:
  
  double LogDensityImpl(std::vector&lt;Eigen::VectorXd&gt; const&amp; x) override{
    
    Eigen::VectorXd r(2);
    r(0) = x[0](0)/a;
    r(1) = a*x[0](1) - a*b*(r(0)*r(0) + a*a);
    
    return -0.5*(r(0)*r(0) + r(1)*r(1));
  };
  
  Eigen::VectorXd GradientImpl(std::vector&lt;Eigen::VectorXd&gt; const&amp; x,
                               Eigen::VectorXd              const&amp; sens,
                               int                          const  inputDimWrt) override{
    
    Eigen::VectorXd grad(2);
    
    grad(0) = -(x[0](0)*(2*pow(a,4.0)*pow(b,2.0) - 2*x[0](1)*a*a*b + 2*b*b*pow(x[0](0),2.0) + 1))/(a*a);
    grad(1) = b*pow(a,4.0) - x[0](1)*a*a + b*pow(x[0](0),2.0);
    
    return grad;
  };
  
  Eigen::MatrixXd HessianImpl(std::vector&lt;Eigen::VectorXd&gt; const&amp; x,
                              Eigen::VectorXd              const&amp; sens,
                              int                          const  inputDimWrt) override{
    
    Eigen::MatrixXd jac =Eigen::MatrixXd::Zero(2,2);
    jac(0.0) = 1.0/a;
    jac(1.0) = -2*b*x[0](0)/a;
    jac(1,1) = a;
    
    return -0.5*jac.transpose()*jac;
  };
  
  const double a = 1.0;
  const double b = 1.0;
  
};
</pre>

<h3 id="high-level-specification-of-random-walk-metropolis">High-level specification of random walk Metropolis</h3>
<p>The following function uses the high-level API and the <code>MCMCBase::ConstructMCMC</code> factory method to construct and MCMC algorithm.  Notice that all parameters are set in the <code>params</code> <code>ptree</code>.   This limits the information that can be passed to the factory method.  For example, a full proposal covariance matrix cannot be used in this setting.  However, the <code>ptree</code> can be easily read from an xml file, which allows the MCMC to be easily chosen at runtime.</p><pre class="prettyprint">

std::shared_ptr&lt;MCMCBase&gt; MH_Simple_Setup()
{
  
  auto targetDens = make_shared&lt;Boomerang&gt;();
  auto problem = make_shared&lt;SamplingProblem&gt;(targetDens);
  
  boost::property_tree::ptree params;
  params.put("MCMC.Method", "SingleChainMCMC");
  params.put("MCMC.Proposal", "MHProposal");
  params.put("MCMC.Kernel", "MHKernel");
  params.put("MCMC.MHProposal.PropSize", 2.4*2.4/2.0*1.5);
  
  params.put("MCMC.Steps", 10000);
  params.put("MCMC.BurnIn", 1000);
  params.put("Verbose", 3);
  
  auto mcmc = MCMCBase::ConstructMCMC(problem, params);
  
  return mcmc;
}
</pre>

<h3 id="high-level-specification-of-adaptive-metropolis-am-">High-level specification of Adaptive Metropolis (AM)</h3>
<p>The adaptive Metropolis algorithm was first introduced in [Haario et al., 2001].  In this algorithm, the sample covariance from the MCMC chain is scaled and then used as the proposal covariance.  In our implementation the proposal covariance $\Sigma_{\text{prop}}$ is given by 
$$
\Sigma_{\text{prop}} = s_d \hat{\Sigma} + 1e-10 I,
$$
where $s_d$ is a user-specified scaling coefficient, $\hat{\Sigma}$ is the current sample covariance, and $I$ is the identity matrix.  </p>
<p>The following function sets up constructs the adaptive Metropolis algorithm.</p>
<ol>
<li>Haario, Heikki, Eero Saksman, and Johanna Tamminen. &quot;An adaptive Metropolis algorithm.&quot; Bernoulli (2001): 223-242.</li>
</ol><pre class="prettyprint">

std::shared_ptr&lt;MCMCBase&gt; AM_Simple_Setup()
{
  auto targetDens = make_shared&lt;Boomerang&gt;();
  auto problem = make_shared&lt;SamplingProblem&gt;(targetDens);
  
  boost::property_tree::ptree params;
  params.put("MCMC.Method", "SingleChainMCMC");
  params.put("MCMC.Proposal", "AM");
  params.put("MCMC.Kernel", "MHKernel");
  
  params.put("MCMC.AM.AdaptSteps", 2);    // adapt every other step
  params.put("MCMC.AM.AdaptStart", 1000); // start adapting the covariance at 1000 steps
  params.put("MCMC.AM.AdaptScale", 1.5);  // s_d, scale the sample covariance by this quantity
  
  params.put("MCMC.MHProposal.PropSize", 2.0); // used as initial proposal variance before adaptation begins
  
  params.put("MCMC.Steps", 10000);
  params.put("MCMC.BurnIn", 1000);
  params.put("Verbose", 3);
  
  auto mcmc = MCMCBase::ConstructMCMC(problem, params);
  
  return mcmc;
}
</pre>

<h3 id="high-level-specification-of-delayed-rejection-adaptive-metropolis-dram-">High-level specification of Delayed Rejection Adaptive Metropolis (DRAM)</h3>
<p>DRAM is a combination of two or more adaptive Metropolis proposal stages in a delayed rejection framework.  The method was first introduced in [Haario et al., 2006].  Because the delayed rejection acceptance rate is modified to account for multiple stages, MUQ implements delayed rejection at the kernel level.  </p>
<p>The following function sets up a delayed rejection kernel with two adaptive Metropolis stages.  The covariance of each stage is decreased by a factor of 2 in this case.</p>
<ol>
<li>Haario, Heikki, Marko Laine, Antonietta Mira, and Eero Saksman. &quot;DRAM: efficient adaptive MCMC.&quot; Statistics and Computing 16, no. 4 (2006): 339-354.</li>
</ol><pre class="prettyprint">

std::shared_ptr&lt;MCMCBase&gt; DRAM_Simple_Setup()
{
  auto targetDens = make_shared&lt;Boomerang&gt;();
  auto problem = make_shared&lt;SamplingProblem&gt;(targetDens);
  
  boost::property_tree::ptree params;
  params.put("MCMC.Method", "SingleChainMCMC");
  params.put("MCMC.Kernel", "DR");
  params.put("MCMC.DR.ProposalSpecification", "DRAM");
  
  params.put("MCMC.DR.NumSteps", 500000); // Perform all stages of DR for the first NumSteps steps.  
  params.put("MCMC.DR.stages", 2);        // use two stages in the DR kernel
  params.put("MCMC.DR.scale", 2.0);       // halve the proposal variance at each stage

  params.put("MCMC.AM.AdaptSteps", 2);    // adapt every other step
  params.put("MCMC.AM.AdaptStart", 1000); // start adapting the covariance at 1000 steps
  params.put("MCMC.AM.AdaptScale", 1.5);  // scale the sample covariance by this quantity
  
  params.put("MCMC.MHProposal.PropSize", 2.0); // used as initial proposal variance
  
  params.put("MCMC.Steps", 10000);
  params.put("MCMC.BurnIn", 1000);
  params.put("Verbose", 3);
  
  auto mcmc = MCMCBase::ConstructMCMC(problem, params);
  
  return mcmc;
}
</pre>

<h3 id="high-level-specification-of-delayed-rejection-dr-with-general-proposals">High-level specification of Delayed Rejection (DR) with general proposals</h3>
<p>While most often used for the DRAM algorithm, the basic delayed rejection algorithm introduced in [Mira, 2001] is much more general.  In this function we show how MUQ can be used to define a delayed rejection method with arbitrary proposal densities in each stage.  Simple random walk propopsals are used here for illustration, but other proposal mechanism such as MALA could also be used here. </p>
<ol>
<li>Mira, Antonietta. &quot;On Metropolis-Hastings algorithms with delayed rejection.&quot; Metron 59, no. 3-4 (2001): 231-241.</li>
</ol><pre class="prettyprint">

std::shared_ptr&lt;MCMCBase&gt; DR_Simple_Setup()
{
  auto targetDens = make_shared&lt;Boomerang&gt;();
  auto problem = make_shared&lt;SamplingProblem&gt;(targetDens);
  
  boost::property_tree::ptree params;
  params.put("MCMC.Method", "SingleChainMCMC");
  params.put("MCMC.Kernel", "DR");
  
  // general DR settings
  params.put("MCMC.DR.ProposalSpecification", "Prop1,Prop2");
  params.put("MCMC.DR.NumSteps", 500000); // Perform all stages of DR for the first NumSteps steps.  
  
  // first DR stage
  params.put("MCMC.DR.Prop1.Proposal","MHProposal");
  params.put("MCMC.DR.Prop1.MHProposal.PropSize",3.0); // first stage proposal variance
  
  // second DR stage
  params.put("MCMC.DR.Prop2.Proposal","MHProposal");
  params.put("MCMC.DR.Prop2.MHProposal.PropSize",1.0); // second stage proposal variance
  
  // general MCMC settings
  params.put("MCMC.Steps", 10000);
  params.put("MCMC.BurnIn", 1000);
  params.put("Verbose", 3);
  
  auto mcmc = MCMCBase::ConstructMCMC(problem, params);
  
  return mcmc;
}
</pre>

<h3 id="high-level-specification-of-adaptive-mala-amala-">High-level specification of adaptive MALA (AMALA)</h3>
<p>Like AM, AMALA uses a scaled version of the posterior sample covariance for the proposal covariance.  However, like other Langevin proposals, the proposal mean is shifted using the gradient of the target density.  AMALA was introduced in [Atchade, 2006].</p>
<ol>
<li>Atchadé, Yves F. &quot;An adaptive version for the Metropolis adjusted Langevin algorithm with a truncated drift.&quot; Methodology and Computing in applied Probability 8, no. 2 (2006): 235-254.
Harvard    </li>
</ol><pre class="prettyprint">

std::shared_ptr&lt;MCMCBase&gt; AMALA_Simple_Setup()
{
  auto targetDens = make_shared&lt;Boomerang&gt;();
  auto problem = make_shared&lt;SamplingProblem&gt;(targetDens);
  
  boost::property_tree::ptree params;
  params.put("MCMC.Method", "SingleChainMCMC");
  params.put("MCMC.Proposal", "AMALA");
  params.put("MCMC.Kernel", "MHKernel");
  
  params.put("MCMC.AMALA.AdaptSteps", 2);    // adapt every other step
  params.put("MCMC.AMALA.AdaptStart", 1000); // start adapting the covariance at 1000 steps
  params.put("MCMC.AMALA.AdaptScale", 1.5);  // s_d, scale the sample covariance by this quantity
  params.put("MCMC.AMALA.MaxDrift", 2.0);    // maximum drift allowed in proposal mean
  
  params.put("MCMC.MHProposal.PropSize", 2.0); // used as initial proposal variance before adaptation begins
  
  params.put("MCMC.Steps", 10000);
  params.put("MCMC.BurnIn", 1000);
  params.put("Verbose", 3);
  
  auto mcmc = MCMCBase::ConstructMCMC(problem, params);
  
  return mcmc;
}
</pre>

<h3 id="high-level-specification-of-the-no-u-turn-sampler-nuts-">High-level specification of the No-U-Turn Sampler (NUTS)</h3>
<p>We link to <a href="http://mc-stan.org/">Stan</a> implementation of the No-U-Turn sampler of [Homan et al., 2014].  This method is an adaptive Hamiltonian Monte Carlo method that requires gradient information from the target density.</p>
<p>The StanNUTS kernel links to NUTS.  It is also possible to use the StanHMC kernel to use Stan&#39;s more basic adaptive HMC routine.</p>
<ol>
<li>Homan, Matthew D., and Andrew Gelman. &quot;The no-U-turn sampler: Adaptively setting path lengths in Hamiltonian Monte Carlo.&quot; The Journal of Machine Learning Research 15, no. 1 (2014): 1593-1623.</li>
</ol><pre class="prettyprint">

std::shared_ptr&lt;MCMCBase&gt; NUTS_Simple_Setup()
{
  
  auto targetDens = make_shared&lt;Boomerang&gt;();
  auto problem = make_shared&lt;SamplingProblem&gt;(targetDens);
  
  boost::property_tree::ptree params;
  params.put("MCMC.Method", "SingleChainMCMC");
  params.put("MCMC.Kernel", "StanNUTS");
  
  params.put("MCMC.Steps", 10000);
  params.put("MCMC.BurnIn", 1000);
  params.put("Verbose", 3);
  
  auto mcmc = MCMCBase::ConstructMCMC(problem, params);
  
  return mcmc;
}
</pre>

<h3 id="low-level-specification-of-random-walk-metropolis">Low-level specification of random walk Metropolis</h3>
<p>This function creates a random walk Metropolis algorithm like above.  However, in this function the proposal and kernel are manually set.  This allows us to choose an arbitrary proposal covariance.  </p>
<p>Notice that a scalar, vector, or matrix can be used to specify the covariance.  The Gaussian class employs more efficient methods for handling the vector and scalar cases than the full covariance.  A precision matrix (inverse of covariance matrix) can also be specified.  See the <a href="http://muq.mit.edu/develop-docs/classmuq_1_1Modelling_1_1GaussianSpecification.html">GaussianSpecification class</a> for more details.</p><pre class="prettyprint">

std::shared_ptr&lt;MCMCBase&gt; MH_Setup()
{
  
  auto targetDens = make_shared&lt;Boomerang&gt;();
  auto problem = make_shared&lt;SamplingProblem&gt;(targetDens);
  
  boost::property_tree::ptree params;
  params.put("MCMC.Steps", 10000);
  params.put("MCMC.BurnIn", 1000);
  params.put("Verbose", 3);
  
  Eigen::VectorXd propMu = Eigen::VectorXd::Zero(2);
  Eigen::MatrixXd propCov(2,2);
  propCov &lt;&lt; 1.0, 0,
             0.0, 3.0;
  
  auto propPair = make_shared&lt;GaussianPair&gt;(propMu,propCov);
  auto proposal = make_shared&lt;MHProposal&gt;(problem,params, propPair);
  auto kernel = make_shared&lt;MHKernel&gt;(problem,params,proposal);
  auto mcmc = make_shared&lt;SingleChainMCMC&gt;(kernel,params);

  return mcmc;
}
</pre>

<h3 id="low-level-specification-of-metropolis-adjusted-langevin-algorithm-mala-">Low-level specification of Metropolis adjusted Langevin algorithm (MALA).</h3>
<p>The specification of the this MALA algorithm is nearly identical to the RWM algorithm above.  However, in this case, the PreMALA proposal will set the proposal mean using the gradient of the target density.  </p>
<p>Notice that the kernel <code>MHKernel</code> is the same as the RWM algorithm.  The MH kernel has nothing to do with the actual proposal, it merely implements the Metropolis-Hastings accept/reject rule.</p><pre class="prettyprint">

std::shared_ptr&lt;MCMCBase&gt; PreMALA_Setup()
{
  
  auto targetDens = make_shared&lt;Boomerang&gt;();
  auto problem = make_shared&lt;SamplingProblem&gt;(targetDens);
  
  boost::property_tree::ptree params;
  params.put("MCMC.Steps", 10000);
  params.put("MCMC.BurnIn", 1000);
  params.put("Verbose", 3);
  
  Eigen::VectorXd propMu = Eigen::VectorXd::Zero(2);
  Eigen::MatrixXd propCov(2,2);
  propCov &lt;&lt; 1.0, 0,
             0.0, 3.0;
  
  auto proposal = make_shared&lt;PreMALA&gt;(problem,params, make_shared&lt;GaussianPair&gt;(propMu,propCov));
  auto kernel = make_shared&lt;MHKernel&gt;(problem,params,proposal);
  auto mcmc = make_shared&lt;SingleChainMCMC&gt;(kernel,params);
  
  return mcmc;
}
</pre>

<h3 id="low-level-specification-of-the-simplified-manifold-mala-algorithm-smmala-">Low-level specification of the simplified manifold MALA algorithm (sMMALA)</h3>
<p>[Girolami and Calderhead, 2011] introduced a family of MCMC methods that exploit concepts of differential geometry to more efficiently explore the target distribution.  The <code>MMALA</code> propoal in MUQ implements their simplified MALA algorithm.  </p>
<p>To construct this method, we first need to define a position-specific metric that will be used as a preconditioning matrix in the proposal.  See equation 10 and its simplification in [Girolami and Calderhead, 2011] for details on how the metric is used in the proposal.</p>
<p>Here we will use the <code>HessianMetric</code> class, which simply uses the Hessian of the target density as the position-dependent metric.  For Bayesian inference problems, the <a href="http://muq.mit.edu/develop-docs/classmuq_1_1Inference_1_1GaussianFisherInformationMetric.html">Gaussian Fisher Information Metric</a> may be a better choice.  It is also straightforward to make a child of the <a href="http://muq.mit.edu/develop-docs/classmuq_1_1Inference_1_1AbstractMetric.html">AbstractMetric</a> class to define you&#39;re own metric.</p>
<ol>
<li>Girolami, Mark, and Ben Calderhead. &quot;Riemann manifold langevin and hamiltonian monte carlo methods.&quot; Journal of the Royal Statistical Society: Series B (Statistical Methodology) 73, no. 2 (2011): 123-214.</li>
</ol><pre class="prettyprint">

std::shared_ptr&lt;MCMCBase&gt; MMALA_Setup()
{
  
  auto targetDens = make_shared&lt;Boomerang&gt;();
  auto metric = make_shared&lt;HessianMetric&gt;(targetDens);
  auto problem = make_shared&lt;SamplingProblem&gt;(targetDens,metric);
  
  boost::property_tree::ptree params;
  params.put("MCMC.Steps", 10000);
  params.put("MCMC.BurnIn", 1000);
  params.put("Verbose", 3);
  
  auto proposal = make_shared&lt;MMALA&gt;(problem,params);
  auto kernel = make_shared&lt;MHKernel&gt;(problem,params,proposal);
  auto mcmc = make_shared&lt;SingleChainMCMC&gt;(kernel,params);
  
  return mcmc;
}
</pre>

<h3 id="low-level-specification-of-transport-map-mcmc">Low-level specification of transport map MCMC</h3>
<p>Some MCMC methods can perform poorly on target densities with changing correlation structure.  The work of [Parno and Marzouk, 2014] used nonlinear transformations, called transport maps, to adaptively transform the target density into a standard Gaussian reference space, which is then easier to sample. </p>
<p>In the function below, the transport map is represnted by a finite polynomial expansion.  The terms used in this expansion are represented with a multiindex set.  See [Parno and Marzouk, 2014] or [Marzouk et al, 2016] for more details.</p>
<p>The transport map kernel uses another kernel to sample the reference space.  This reference-domain proposal is defined in the <code>MCMC.TransportMap.SubMethod.MCMC</code> field of the <code>ptree</code>.  Any proposal from MUQ can be specified, including advanced DR methods with a mix of global and local proposals.</p>
<ol>
<li>Parno, Matthew, and Youssef Marzouk. <a href="http://arxiv.org/abs/1412.5492">&quot;Transport map accelerated Markov chain Monte Carlo.&quot;</a> arXiv preprint arXiv:1412.5492 (2014).</li>
<li>Marzouk, Youssef, Tarek Moselhy, Matthew Parno, and Alessio Spantini. <a href="http://arxiv.org/abs/1602.05023">&quot;An introduction to sampling via measure transport.&quot;</a> arXiv preprint arXiv:1602.05023 (2016).</li>
</ol><pre class="prettyprint">

std::shared_ptr&lt;MCMCBase&gt; TransportMap_Setup()
{
  auto targetDens = make_shared&lt;Boomerang&gt;();
  auto problem = make_shared&lt;SamplingProblem&gt;(targetDens);

  // Define the terms in the transport map by creating a set of multiindices
  auto multis   = MultiIndexFactory::CreateTriTotalOrder(2, 1); // (dimension, order)
  multis.at(1) += MultiIndexFactory::CreateSingleTerm(2, 0, 2); // (dimension, index, order)

  // Initialize the map to an identity transformation
  auto initialMap = MapFactory::CreateIdentity(multis);
  
  // set up the MCMC parameters
  boost::property_tree::ptree params;
  params.put("MCMC.Method", "SingleChainMCMC");
  params.put("MCMC.Steps", 10000);
  params.put("MCMC.BurnIn", 1000);
  
  params.put("MCMC.TransportMap.AdaptStart", 500);
  params.put("MCMC.TransportMap.AdaptStop", 200000);
  params.put("MCMC.TransportMap.AdaptGap", 10);
  params.put("MCMC.TransportMap.AdaptScale", 1e6);

  params.put("MCMC.TransportMap.SubMethod.MCMC.Kernel", "MHKernel");
  params.put("MCMC.TransportMap.SubMethod.MCMC.Proposal", "MHProposal");
  params.put("MCMC.TransportMap.SubMethod.MCMC.MHProposal.PropSize", 1.8);

  params.put("Verbose", 3);
  params.put("MCMC.TransportMap.SubMethod.Verbose", 3);
  
  auto kernel = make_shared&lt;TransportMapKernel&gt;(problem, params, initialMap);
  auto mcmc = make_shared&lt;SingleChainMCMC&gt;(kernel, params);
  
  return mcmc;
}
</pre>

<h3 id="run-the-mcmc-algorithm">Run the MCMC algorithm</h3>
<p>Here we use one of the MCMC algorithms to estimate the mean and covariance of the boomerang target density.</p><pre class="prettyprint">

int main()
{ 

  auto mcmc = MH_Simple_Setup();
  //auto mcmc = AM_Simple_Setup();
  //auto mcmc = AMALA_Simple_Setup();
  //auto mcmc = DRAM_Simple_Setup();
  //auto mcmc = DR_Simple_Setup();
  //auto mcmc = NUTS_Simple_Setup();
  
  //auto mcmc = MH_Setup();
  //auto mcmc = PreMALA_Setup();
  //auto mcmc = MMALA_Setup();
  //auto mcmc = TransportMap_Setup();
  
  Eigen::VectorXd startingPoint(2);
  startingPoint &lt;&lt; 0, 1.0;
  
  EmpRvPtr mcmcChain = mcmc-&gt;Sample(startingPoint);

  Eigen::VectorXd mean = mcmcChain-&gt;getMean();
  Eigen::MatrixXd cov  = mcmcChain-&gt;getCov();
  
  cout &lt;&lt; "\nSample Mean:\n";
  cout &lt;&lt; mean &lt;&lt; endl &lt;&lt; endl;
  
  cout &lt;&lt; "Sample Covariance:\n";
  cout &lt;&lt; cov &lt;&lt; endl &lt;&lt; endl;

  Eigen::VectorXd ess = mcmcChain-&gt;getEss();
  
  // find the minimum and maximum ess
  cout &lt;&lt; "Minimum ESS: " &lt;&lt; ess.minCoeff() &lt;&lt; endl;
  cout &lt;&lt; "Maximum ESS: " &lt;&lt; ess.maxCoeff() &lt;&lt; endl &lt;&lt; endl;
}

</pre>

<h1 id="build-and-run-the-code">Build and run the code</h1><pre class="prettyprint">
if [ ! -d build ]; then
  mkdir build;
fi

cd build; cmake ../; make; cd ../
</pre>

<pre class="prettyprint lang-bash" style="background-color:#D0D0D0">
-- The C compiler identification is AppleClang 6.0.0.6000057
-- The CXX compiler identification is AppleClang 6.0.0.6000057
-- Check for working C compiler: /Applications/Xcode.app/Contents/Developer/Toolchains/XcodeDefault.xctoolchain/usr/bin/cc
-- Check for working C compiler: /Applications/Xcode.app/Contents/Developer/Toolchains/XcodeDefault.xctoolchain/usr/bin/cc -- works
-- Detecting C compiler ABI info
-- Detecting C compiler ABI info - done
-- Detecting C compile features
-- Detecting C compile features - done
-- Check for working CXX compiler: /Applications/Xcode.app/Contents/Developer/Toolchains/XcodeDefault.xctoolchain/usr/bin/c++
-- Check for working CXX compiler: /Applications/Xcode.app/Contents/Developer/Toolchains/XcodeDefault.xctoolchain/usr/bin/c++ -- works
-- Detecting CXX compiler ABI info
-- Detecting CXX compiler ABI info - done
-- Detecting CXX compile features
-- Detecting CXX compile features - done
-- Configuring done
-- Generating done
-- Build files have been written to: /Users/mparno/Documents/Repositories/MUQ/muq/MUQ/examples/Inference/MCMC-Overview/build
Scanning dependencies of target overview
[100%] Building CXX object CMakeFiles/overview.dir/MCMC-Overview.cpp.o
Linking CXX executable overview
[100%] Built target overview

</pre>

<pre class="prettyprint">
build/overview
</pre>

<pre class="prettyprint lang-bash" style="background-color:#D0D0D0">
10% Complete
  MH: Acceptance rate =  26.4%
20.0% Complete
  MH: Acceptance rate =  25.8%
30.0% Complete
  MH: Acceptance rate =  24.9%
40.0% Complete
  MH: Acceptance rate =  24.0%
50.0% Complete
  MH: Acceptance rate =  23.8%
60.0% Complete
  MH: Acceptance rate =  23.9%
70.0% Complete
  MH: Acceptance rate =  23.9%
80.0% Complete
  MH: Acceptance rate =  24.0%
90.0% Complete
  MH: Acceptance rate =  24.0%
100.0% Complete
  MH: Acceptance rate =  24.0%

Sample Mean:
-0.0
1.9

Sample Covariance:
      0.9      -0.2
     -0.2       2.6

Minimum ESS: 344.1
Maximum ESS: 463.1


</pre>

<h2>Completed code:</h2><h3>MCMC-Overview.cpp</h3>

<pre class="prettyprint" style="height:auto;max-height:400px;">

#include &lt;Eigen/Core&gt;
#include &lt;boost/property_tree/ptree.hpp&gt;

#include "MUQ/Modelling/ModPiece.h"
#include "MUQ/Modelling/Density.h"

#include "MUQ/Modelling/EmpiricalRandVar.h"
#include "MUQ/Modelling/GaussianDensity.h"

#include "MUQ/Inference/ProblemClasses/SamplingProblem.h"

#include "MUQ/Inference/MCMC/MCMCBase.h"
#include "MUQ/Inference/MCMC/MCMCProposal.h"
#include "MUQ/Inference/ProblemClasses/HessianMetric.h"

#include "MUQ/Inference/MCMC/SingleChainMCMC.h"
#include "MUQ/Inference/MCMC/MHProposal.h"
#include "MUQ/Inference/MCMC/MHKernel.h"
#include "MUQ/Inference/MCMC/PreMALA.h"
#include "MUQ/Inference/MCMC/MMALA.h"

#include "MUQ/Inference/MCMC/TransportMapKernel.h"

#include "MUQ/Utilities/multiIndex/MultiIndexFactory.h"
#include "MUQ/Inference/TransportMaps/TransportMap.h"
#include "MUQ/Inference/TransportMaps/MapFactory.h"

using namespace std;
using namespace Eigen;

using namespace muq::Modelling;
using namespace muq::Inference;
using namespace muq::Utilities;


class Boomerang : public Density{
  
public:
  Boomerang() : Density(Eigen::VectorXi::Constant(1,2), true, true, false, true){};
  
private:
  
  double LogDensityImpl(std::vector&lt;Eigen::VectorXd&gt; const&amp; x) override{
    
    Eigen::VectorXd r(2);
    r(0) = x[0](0)/a;
    r(1) = a*x[0](1) - a*b*(r(0)*r(0) + a*a);
    
    return -0.5*(r(0)*r(0) + r(1)*r(1));
  };
  
  Eigen::VectorXd GradientImpl(std::vector&lt;Eigen::VectorXd&gt; const&amp; x,
                               Eigen::VectorXd              const&amp; sens,
                               int                          const  inputDimWrt) override{
    
    Eigen::VectorXd grad(2);
    
    grad(0) = -(x[0](0)*(2*pow(a,4.0)*pow(b,2.0) - 2*x[0](1)*a*a*b + 2*b*b*pow(x[0](0),2.0) + 1))/(a*a);
    grad(1) = b*pow(a,4.0) - x[0](1)*a*a + b*pow(x[0](0),2.0);
    
    return grad;
  };
  
  Eigen::MatrixXd HessianImpl(std::vector&lt;Eigen::VectorXd&gt; const&amp; x,
                              Eigen::VectorXd              const&amp; sens,
                              int                          const  inputDimWrt) override{
    
    Eigen::MatrixXd jac =Eigen::MatrixXd::Zero(2,2);
    jac(0.0) = 1.0/a;
    jac(1.0) = -2*b*x[0](0)/a;
    jac(1,1) = a;
    
    return -0.5*jac.transpose()*jac;
  };
  
  const double a = 1.0;
  const double b = 1.0;
  
};


std::shared_ptr&lt;MCMCBase&gt; MH_Simple_Setup()
{
  
  auto targetDens = make_shared&lt;Boomerang&gt;();
  auto problem = make_shared&lt;SamplingProblem&gt;(targetDens);
  
  boost::property_tree::ptree params;
  params.put("MCMC.Method", "SingleChainMCMC");
  params.put("MCMC.Proposal", "MHProposal");
  params.put("MCMC.Kernel", "MHKernel");
  params.put("MCMC.MHProposal.PropSize", 2.4*2.4/2.0*1.5);
  
  params.put("MCMC.Steps", 10000);
  params.put("MCMC.BurnIn", 1000);
  params.put("Verbose", 3);
  
  auto mcmc = MCMCBase::ConstructMCMC(problem, params);
  
  return mcmc;
}


std::shared_ptr&lt;MCMCBase&gt; AM_Simple_Setup()
{
  auto targetDens = make_shared&lt;Boomerang&gt;();
  auto problem = make_shared&lt;SamplingProblem&gt;(targetDens);
  
  boost::property_tree::ptree params;
  params.put("MCMC.Method", "SingleChainMCMC");
  params.put("MCMC.Proposal", "AM");
  params.put("MCMC.Kernel", "MHKernel");
  
  params.put("MCMC.AM.AdaptSteps", 2);    // adapt every other step
  params.put("MCMC.AM.AdaptStart", 1000); // start adapting the covariance at 1000 steps
  params.put("MCMC.AM.AdaptScale", 1.5);  // s_d, scale the sample covariance by this quantity
  
  params.put("MCMC.MHProposal.PropSize", 2.0); // used as initial proposal variance before adaptation begins
  
  params.put("MCMC.Steps", 10000);
  params.put("MCMC.BurnIn", 1000);
  params.put("Verbose", 3);
  
  auto mcmc = MCMCBase::ConstructMCMC(problem, params);
  
  return mcmc;
}


std::shared_ptr&lt;MCMCBase&gt; DRAM_Simple_Setup()
{
  auto targetDens = make_shared&lt;Boomerang&gt;();
  auto problem = make_shared&lt;SamplingProblem&gt;(targetDens);
  
  boost::property_tree::ptree params;
  params.put("MCMC.Method", "SingleChainMCMC");
  params.put("MCMC.Kernel", "DR");
  params.put("MCMC.DR.ProposalSpecification", "DRAM");
  
  params.put("MCMC.DR.NumSteps", 500000); // Perform all stages of DR for the first NumSteps steps.  
  params.put("MCMC.DR.stages", 2);        // use two stages in the DR kernel
  params.put("MCMC.DR.scale", 2.0);       // halve the proposal variance at each stage

  params.put("MCMC.AM.AdaptSteps", 2);    // adapt every other step
  params.put("MCMC.AM.AdaptStart", 1000); // start adapting the covariance at 1000 steps
  params.put("MCMC.AM.AdaptScale", 1.5);  // scale the sample covariance by this quantity
  
  params.put("MCMC.MHProposal.PropSize", 2.0); // used as initial proposal variance
  
  params.put("MCMC.Steps", 10000);
  params.put("MCMC.BurnIn", 1000);
  params.put("Verbose", 3);
  
  auto mcmc = MCMCBase::ConstructMCMC(problem, params);
  
  return mcmc;
}


std::shared_ptr&lt;MCMCBase&gt; DR_Simple_Setup()
{
  auto targetDens = make_shared&lt;Boomerang&gt;();
  auto problem = make_shared&lt;SamplingProblem&gt;(targetDens);
  
  boost::property_tree::ptree params;
  params.put("MCMC.Method", "SingleChainMCMC");
  params.put("MCMC.Kernel", "DR");
  
  // general DR settings
  params.put("MCMC.DR.ProposalSpecification", "Prop1,Prop2");
  params.put("MCMC.DR.NumSteps", 500000); // Perform all stages of DR for the first NumSteps steps.  
  
  // first DR stage
  params.put("MCMC.DR.Prop1.Proposal","MHProposal");
  params.put("MCMC.DR.Prop1.MHProposal.PropSize",3.0); // first stage proposal variance
  
  // second DR stage
  params.put("MCMC.DR.Prop2.Proposal","MHProposal");
  params.put("MCMC.DR.Prop2.MHProposal.PropSize",1.0); // second stage proposal variance
  
  // general MCMC settings
  params.put("MCMC.Steps", 10000);
  params.put("MCMC.BurnIn", 1000);
  params.put("Verbose", 3);
  
  auto mcmc = MCMCBase::ConstructMCMC(problem, params);
  
  return mcmc;
}


std::shared_ptr&lt;MCMCBase&gt; AMALA_Simple_Setup()
{
  auto targetDens = make_shared&lt;Boomerang&gt;();
  auto problem = make_shared&lt;SamplingProblem&gt;(targetDens);
  
  boost::property_tree::ptree params;
  params.put("MCMC.Method", "SingleChainMCMC");
  params.put("MCMC.Proposal", "AMALA");
  params.put("MCMC.Kernel", "MHKernel");
  
  params.put("MCMC.AMALA.AdaptSteps", 2);    // adapt every other step
  params.put("MCMC.AMALA.AdaptStart", 1000); // start adapting the covariance at 1000 steps
  params.put("MCMC.AMALA.AdaptScale", 1.5);  // s_d, scale the sample covariance by this quantity
  params.put("MCMC.AMALA.MaxDrift", 2.0);    // maximum drift allowed in proposal mean
  
  params.put("MCMC.MHProposal.PropSize", 2.0); // used as initial proposal variance before adaptation begins
  
  params.put("MCMC.Steps", 10000);
  params.put("MCMC.BurnIn", 1000);
  params.put("Verbose", 3);
  
  auto mcmc = MCMCBase::ConstructMCMC(problem, params);
  
  return mcmc;
}


std::shared_ptr&lt;MCMCBase&gt; NUTS_Simple_Setup()
{
  
  auto targetDens = make_shared&lt;Boomerang&gt;();
  auto problem = make_shared&lt;SamplingProblem&gt;(targetDens);
  
  boost::property_tree::ptree params;
  params.put("MCMC.Method", "SingleChainMCMC");
  params.put("MCMC.Kernel", "StanNUTS");
  
  params.put("MCMC.Steps", 10000);
  params.put("MCMC.BurnIn", 1000);
  params.put("Verbose", 3);
  
  auto mcmc = MCMCBase::ConstructMCMC(problem, params);
  
  return mcmc;
}


std::shared_ptr&lt;MCMCBase&gt; MH_Setup()
{
  
  auto targetDens = make_shared&lt;Boomerang&gt;();
  auto problem = make_shared&lt;SamplingProblem&gt;(targetDens);
  
  boost::property_tree::ptree params;
  params.put("MCMC.Steps", 10000);
  params.put("MCMC.BurnIn", 1000);
  params.put("Verbose", 3);
  
  Eigen::VectorXd propMu = Eigen::VectorXd::Zero(2);
  Eigen::MatrixXd propCov(2,2);
  propCov &lt;&lt; 1.0, 0,
             0.0, 3.0;
  
  auto propPair = make_shared&lt;GaussianPair&gt;(propMu,propCov);
  auto proposal = make_shared&lt;MHProposal&gt;(problem,params, propPair);
  auto kernel = make_shared&lt;MHKernel&gt;(problem,params,proposal);
  auto mcmc = make_shared&lt;SingleChainMCMC&gt;(kernel,params);

  return mcmc;
}


std::shared_ptr&lt;MCMCBase&gt; PreMALA_Setup()
{
  
  auto targetDens = make_shared&lt;Boomerang&gt;();
  auto problem = make_shared&lt;SamplingProblem&gt;(targetDens);
  
  boost::property_tree::ptree params;
  params.put("MCMC.Steps", 10000);
  params.put("MCMC.BurnIn", 1000);
  params.put("Verbose", 3);
  
  Eigen::VectorXd propMu = Eigen::VectorXd::Zero(2);
  Eigen::MatrixXd propCov(2,2);
  propCov &lt;&lt; 1.0, 0,
             0.0, 3.0;
  
  auto proposal = make_shared&lt;PreMALA&gt;(problem,params, make_shared&lt;GaussianPair&gt;(propMu,propCov));
  auto kernel = make_shared&lt;MHKernel&gt;(problem,params,proposal);
  auto mcmc = make_shared&lt;SingleChainMCMC&gt;(kernel,params);
  
  return mcmc;
}


std::shared_ptr&lt;MCMCBase&gt; MMALA_Setup()
{
  
  auto targetDens = make_shared&lt;Boomerang&gt;();
  auto metric = make_shared&lt;HessianMetric&gt;(targetDens);
  auto problem = make_shared&lt;SamplingProblem&gt;(targetDens,metric);
  
  boost::property_tree::ptree params;
  params.put("MCMC.Steps", 10000);
  params.put("MCMC.BurnIn", 1000);
  params.put("Verbose", 3);
  
  auto proposal = make_shared&lt;MMALA&gt;(problem,params);
  auto kernel = make_shared&lt;MHKernel&gt;(problem,params,proposal);
  auto mcmc = make_shared&lt;SingleChainMCMC&gt;(kernel,params);
  
  return mcmc;
}


std::shared_ptr&lt;MCMCBase&gt; TransportMap_Setup()
{
  auto targetDens = make_shared&lt;Boomerang&gt;();
  auto problem = make_shared&lt;SamplingProblem&gt;(targetDens);

  // Define the terms in the transport map by creating a set of multiindices
  auto multis   = MultiIndexFactory::CreateTriTotalOrder(2, 1); // (dimension, order)
  multis.at(1) += MultiIndexFactory::CreateSingleTerm(2, 0, 2); // (dimension, index, order)

  // Initialize the map to an identity transformation
  auto initialMap = MapFactory::CreateIdentity(multis);
  
  // set up the MCMC parameters
  boost::property_tree::ptree params;
  params.put("MCMC.Method", "SingleChainMCMC");
  params.put("MCMC.Steps", 10000);
  params.put("MCMC.BurnIn", 1000);
  
  params.put("MCMC.TransportMap.AdaptStart", 500);
  params.put("MCMC.TransportMap.AdaptStop", 200000);
  params.put("MCMC.TransportMap.AdaptGap", 10);
  params.put("MCMC.TransportMap.AdaptScale", 1e6);

  params.put("MCMC.TransportMap.SubMethod.MCMC.Kernel", "MHKernel");
  params.put("MCMC.TransportMap.SubMethod.MCMC.Proposal", "MHProposal");
  params.put("MCMC.TransportMap.SubMethod.MCMC.MHProposal.PropSize", 1.8);

  params.put("Verbose", 3);
  params.put("MCMC.TransportMap.SubMethod.Verbose", 3);
  
  auto kernel = make_shared&lt;TransportMapKernel&gt;(problem, params, initialMap);
  auto mcmc = make_shared&lt;SingleChainMCMC&gt;(kernel, params);
  
  return mcmc;
}


int main()
{ 

  auto mcmc = MH_Simple_Setup();
  //auto mcmc = AM_Simple_Setup();
  //auto mcmc = AMALA_Simple_Setup();
  //auto mcmc = DRAM_Simple_Setup();
  //auto mcmc = DR_Simple_Setup();
  //auto mcmc = NUTS_Simple_Setup();
  
  //auto mcmc = MH_Setup();
  //auto mcmc = PreMALA_Setup();
  //auto mcmc = MMALA_Setup();
  //auto mcmc = TransportMap_Setup();
  
  Eigen::VectorXd startingPoint(2);
  startingPoint &lt;&lt; 0, 1.0;
  
  EmpRvPtr mcmcChain = mcmc-&gt;Sample(startingPoint);

  Eigen::VectorXd mean = mcmcChain-&gt;getMean();
  Eigen::MatrixXd cov  = mcmcChain-&gt;getCov();
  
  cout &lt;&lt; "\nSample Mean:\n";
  cout &lt;&lt; mean &lt;&lt; endl &lt;&lt; endl;
  
  cout &lt;&lt; "Sample Covariance:\n";
  cout &lt;&lt; cov &lt;&lt; endl &lt;&lt; endl;

  Eigen::VectorXd ess = mcmcChain-&gt;getEss();
  
  // find the minimum and maximum ess
  cout &lt;&lt; "Minimum ESS: " &lt;&lt; ess.minCoeff() &lt;&lt; endl;
  cout &lt;&lt; "Maximum ESS: " &lt;&lt; ess.maxCoeff() &lt;&lt; endl &lt;&lt; endl;
}



</pre>

