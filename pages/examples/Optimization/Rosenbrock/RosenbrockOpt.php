<?php
$tpl['page_name'] = 'RosenbrockOpt';
$tpl['tab'] = 'Examples';
?>
<h1 id="Minimizing-the-Rosenbrock-function">Minimizing the Rosenbrock function</h1><p>MUQ contsins methods for solving both constrained and unconstrained optimization problems.  Here we demonstrate some of the unconstrained optimization capabilities in MUQ by minimizing the Rosenbrock function.</p>
<p>Let $x\in\mathbb{R}^2$ denote the two dimensional decision variable and let $f(x)$ denote the objective function, which is given by the well known Rosenbrock function</p>
$$
f(x) = \left(1 - x_1\right)^2 + 100(x_2-x_1^2)^2
$$<p>This function has a global minimum at $x=[x_1,x_2] = [1,1]$.</p>
<h2 id="Optimization-in-MUQ">Optimization in MUQ</h2><p>In MUQ, optimization problems are defined as children of the abstract <code>muq::Optimization::OptProbBase</code> class. Thus, to define the Rosenbrock problem, we need inherit from this class and implement the objective function.  An instance of this class can then be passed to an optimization algorithm (defined through children of the <code>muq::Optimization::OptAlgBase</code> class).</p>
<h2 id="Include-the-necessary-header-files">Include the necessary header files</h2><pre class="prettyprint">
#include &lt;Eigen/Dense&gt;

#include "MUQ/Optimization/Problems/OptProbBase.h"
#include "MUQ/Optimization/Algorithms/OptAlgBase.h"

</pre>

<h2 id="Defining-the-objective">Defining the objective</h2><p>Here we define a class, called <code>RoseFunc</code> that inherits from the optimization base class <a href="http://muq.mit.edu/develop-docs/classmuq_1_1Optimization_1_1OptProbBase.html"><code>muq::Optimization::OptProbBase</code></a>.</p>
<h3 id="Constructor">Constructor</h3><p>The constructor of <a href="http://muq.mit.edu/develop-docs/classmuq_1_1Optimization_1_1OptProbBase.html"><code>muq::Optimization::OptProbBase</code></a> accepts the number of decision variables (2 in this case).</p>
<pre class="prettyprint">

class RoseFunc : public muq::Optimization::OptProbBase {
public:

  RoseFunc() : muq::Optimization::OptProbBase(2) {}
</pre>

<h3 id="Objective-function,-i.e.-eval">Objective function, i.e. <code>eval</code></h3><p>All user-defined objective functions must define the objective function by implementing the <a href="http://muq.mit.edu/develop-docs/classmuq_1_1Optimization_1_1OptProbBase.html#a9767e98e98ea39a00761dc26b3257b42"><code>eval</code></a> function.  Here, we create the objective function for the Rosenbrock function.</p>
<pre class="prettyprint">
  virtual double eval(const Eigen::VectorXd&amp; xc) override
  {
    return pow(1 - xc[0], 2) + 100 * pow(xc[1] - xc[0] * xc[0], 2);
  }
  
</pre>

<h3 id="Adding-Gradients-and-Hessians">Adding Gradients and Hessians</h3><p>Some optimizers can take advantage of gradient and Hessian information.  This information is provied by implementing the <a href="http://muq.mit.edu/develop-docs/classmuq_1_1Optimization_1_1OptProbBase.html#a0b4e3e33330be57235c6edd097ecf2b0"><code>grad</code></a> and <a href="http://muq.mit.edu/develop-docs/classmuq_1_1Optimization_1_1OptProbBase.html#a70415b59ca2d61e8a82cdf869294b9c3"><code>applyInvHess</code></a> functions.  Note that both of these functions are optional and will be replaced by finite difference approximations if they are not provided.</p>
<p>The gradient is given by</p>
$$
\nabla f(x) = \left[ \begin{array}{c} -2(1-x_1) - 400x_1\left(x_2-x_1^2\right) \\ 200\left(x_2-x_1^2\right) \end{array}\right]^T .
$$<p>The <a href="http://muq.mit.edu/develop-docs/classmuq_1_1Optimization_1_1OptProbBase.html#a0b4e3e33330be57235c6edd097ecf2b0"><code>grad</code></a> function computes updates the gradient, which is passed by reference, and returns the objective function value.  In some cases, computing these quantities at the same time can be more computationally efficient.</p>
<pre class="prettyprint">

  virtual double grad(const Eigen::VectorXd&amp; xc, Eigen::VectorXd&amp; gradient) override
  {
    gradient.resize(2);
    
    gradient[0] = -400 * (xc[1] - xc[0] * xc[0]) * xc[0] - 2 * (1 - xc[0]);
    gradient[1] = 200 * (xc[1] - xc[0] * xc[0]);

    return eval(xc);
  }
  
</pre>

<p>The Hessian matrix of the Rosenbrock function is given by</p>
$$
H(x) = \left[ \begin{array}{cc} 2 - 400x_2 + 1200x_1^2 & -400x_1 \\ -400x_1 & 200\end{array}\right].
$$<p>Notice that the <a href="http://muq.mit.edu/develop-docs/classmuq_1_1Optimization_1_1OptProbBase.html#a70415b59ca2d61e8a82cdf869294b9c3"><code>applyInvHess</code></a> function applies the inverse Hessian to a matrix and does not return the actual Hessian matrix.  This allows for flexibility in how the inverse action is computed (e.g., adjoint methods with iterative solvers).  However, for some users it may be more convenient to simply return the Hessian or inverse Hessian.  In these situations, users can instead overload the <code>getHess</code> and <a href="http://muq.mit.edu/develop-docs/classmuq_1_1Optimization_1_1OptProbBase.html#af870584a59f511648c31263be3ab2391"><code>getInvHess</code></a> functions.  Note that the <a href="http://muq.mit.edu/develop-docs/classmuq_1_1Optimization_1_1OptProbBase.html#a83ac19fa761198ab1bbdf5622c56c095"><code>getHess</code></a> or <code>getInvHess</code> functions will not be used if the <a href="http://muq.mit.edu/develop-docs/classmuq_1_1Optimization_1_1OptProbBase.html#a70415b59ca2d61e8a82cdf869294b9c3"><code>applyInvHess</code></a> function is implemented.</p>
<pre class="prettyprint">
  virtual Eigen::VectorXd applyInvHess(const Eigen::VectorXd&amp; xc, const Eigen::VectorXd&amp; vecIn)
  {
    Eigen::Matrix&lt;double, 2, 2&gt; Hess = Eigen::Matrix&lt;double, 2, 2&gt;::Zero(2, 2);

    Hess(0, 0) = 1200 * pow(xc[0], 2.0) - 400 * xc[1] + 2;
    Hess(0, 1) = -400 * xc[0];
    Hess(1, 0) = -400 * xc[0];
    Hess(1, 1) = 200;

    return Hess.lu().solve(vecIn);
  }
  
}; // End of RoseFunc class

</pre>

<h2 id="Solving-the-problem">Solving the problem</h2><p>Now that we've defined our problem in the <code>RoseFunc</code> class, we can set up an optimizer and minimize the objective function.</p>
<h3 id="Create-an-instance-of-the-objective">Create an instance of the objective</h3><p>We begin the main function here by creating an instance of the Rosenbrock optimization function defined above.</p>
<pre class="prettyprint">


int main()
{

    // create an instance of the optimization problem
    auto prob = std::make_shared&lt;RoseFunc&gt;();
    
</pre>

<h3 id="Set-up-the-optimizer">Set up the optimizer</h3><p>Now we create an optimizer.  MUQ uses the factory method <a href="http://muq.mit.edu/develop-docs/classmuq_1_1Optimization_1_1OptAlgBase.html#a95bb02137929d87b1fd3bbd1b81f190e"><code>muq::Optimization::OptAlgBase::Create</code></a> to construct an optimizer based on the problem and other optimization parameters defined in a <a href="http://www.boost.org/doc/libs/1_57_0/doc/html/property_tree.html"><code>boost::property_tree::ptree</code></a>.</p>
<p>The optimization algorithm, set by the <code>Opt.Method</code> parameter, can be one of</p>
<ul>
<li><code>SD_Line</code>, which will create a steepest descent solver</li>
<li><code>BFGS_Line</code>, which will create a BFGS solver</li>
<li><code>Newton</code>, which will create a Newton solver</li>
</ul>
<p>If MUQ was compiled with NLOPT.  It is also possible to set <code>Opt.Method</code> to <code>NLOPT</code>.   The specific optimization algorithm is then specified by the <code>Opt.NLOPT.Method</code> key.   See the parameter list <a href="http://muq.mit.edu/develop-docs/parameters.html">here</a> for more options.</p>
<pre class="prettyprint">

    // set the initial condition
    Eigen::VectorXd x0(2);
    x0 &lt;&lt; -1, 3;

    boost::property_tree::ptree params;

    // set some of the optimization parameters
    params.put("Opt.MaxIts", 10000);
    params.put("Opt.ftol", 1e-8);
    params.put("Opt.xtol", 1e-8);
    params.put("Opt.gtol", 1e-8);
    params.put("Opt.LineSearch.LineIts", 100);
    params.put("Opt.StepLength", 1);
    params.put("Opt.verbosity", 3);
    
    //params.put("Opt.Method", "SD_Line");    // Use the steepest descent algorithm
    //params.put("Opt.Method", "BFGS_Line");  // Use the BFGS algorithm
    params.put("Opt.Method", "Newton");     // Use the Newton algorithm

    // set up the solver
    auto Solver = muq::Optimization::OptAlgBase::Create(prob, params);
</pre>

<h3 id="Run-the-optimizer">Run the optimizer</h3><p>The <a href="http://muq.mit.edu/develop-docs/classmuq_1_1Optimization_1_1OptAlgBase.html#aecf927c9593b1116604dd7b9087478d2"><code>OptAlgBase::solve</code></a> function runs the optimization algorithm and, upon successful completion, returns the optimal point.</p>
<p>The termination status of the solver can be checked with the <a href="http://muq.mit.edu/develop-docs/classmuq_1_1Optimization_1_1OptAlgBase.html#abfc9e5df1faf77d244fc1f2466ba2ade"><code>OptAlgBase::GetStatus</code></a> function.  Note that MUQ's termination codes are identical to NLOPT: positive numbers indicate successful termination and negative numbers indicate that an error occured.</p>
<pre class="prettyprint">

    // solve the optimization problem
    Eigen::VectorXd xOpt = Solver-&gt;solve(x0);

    // Get the termination status
    int optStat = Solver-&gt;GetStatus();

    std::cout &lt;&lt; "Optimal solution = " &lt;&lt; xOpt.transpose() &lt;&lt; std::endl;
    
    return 0;
} // End of "int main()"
</pre>

<h2 id="Build-the-executable">Build the executable</h2><pre class="prettyprint">
cd build; cmake ../ &gt; BuildLog.txt; make; cd ../
</pre>

<pre class="prettyprint lang-bash" style="background-color:#D0D0D0">
Scanning dependencies of target RosenbrockOpt
[100%] Building CXX object CMakeFiles/RosenbrockOpt.dir/RosenbrockOpt.cpp.o
Linking CXX executable RosenbrockOpt
[100%] Built target RosenbrockOpt

</pre>

<h2 id="Run-the-executable">Run the executable</h2><pre class="prettyprint">
build/RosenbrockOpt
</pre>

<pre class="prettyprint lang-bash" style="background-color:#D0D0D0">
Using optimization method: Newton
Iteration: 1   fval: 404
Iteration: 2   fval: 4.02008
Iteration: 3   fval: 3.46927
Iteration: 4   fval: 2.66982
Iteration: 5   fval: 2.25626
Iteration: 6   fval: 1.71473
Iteration: 7   fval: 1.40064
Iteration: 8   fval: 0.943349
Iteration: 9   fval: 0.754829
Iteration: 10   fval: 0.488025
Iteration: 11   fval: 0.362813
Iteration: 12   fval: 0.223237
Iteration: 13   fval: 0.133336
Iteration: 14   fval: 0.0661269
Iteration: 15   fval: 0.0353271
Iteration: 16   fval: 0.0098764
Iteration: 17   fval: 0.00387419
Iteration: 18   fval: 0.000141491
Iteration: 19   fval: 1.87429e-06
Iteration: 20   fval: 1.63594e-19
Terminating with status: 3
Optimal solution = 1 1

</pre>

<h2>Completed code:</h2><h3>RosenbrockOpt.cpp</h3>

<pre class="prettyprint" style="height:auto;max-height:400px;">
#include &lt;Eigen/Dense&gt;

#include "MUQ/Optimization/Problems/OptProbBase.h"
#include "MUQ/Optimization/Algorithms/OptAlgBase.h"



class RoseFunc : public muq::Optimization::OptProbBase {
public:

  RoseFunc() : muq::Optimization::OptProbBase(2) {}

  virtual double eval(const Eigen::VectorXd&amp; xc) override
  {
    return pow(1 - xc[0], 2) + 100 * pow(xc[1] - xc[0] * xc[0], 2);
  }
  


  virtual double grad(const Eigen::VectorXd&amp; xc, Eigen::VectorXd&amp; gradient) override
  {
    gradient.resize(2);
    
    gradient[0] = -400 * (xc[1] - xc[0] * xc[0]) * xc[0] - 2 * (1 - xc[0]);
    gradient[1] = 200 * (xc[1] - xc[0] * xc[0]);

    return eval(xc);
  }
  

  virtual Eigen::VectorXd applyInvHess(const Eigen::VectorXd&amp; xc, const Eigen::VectorXd&amp; vecIn)
  {
    Eigen::Matrix&lt;double, 2, 2&gt; Hess = Eigen::Matrix&lt;double, 2, 2&gt;::Zero(2, 2);

    Hess(0, 0) = 1200 * pow(xc[0], 2.0) - 400 * xc[1] + 2;
    Hess(0, 1) = -400 * xc[0];
    Hess(1, 0) = -400 * xc[0];
    Hess(1, 1) = 200;

    return Hess.lu().solve(vecIn);
  }
  
}; // End of RoseFunc class




int main()
{

    // create an instance of the optimization problem
    auto prob = std::make_shared&lt;RoseFunc&gt;();
    


    // set the initial condition
    Eigen::VectorXd x0(2);
    x0 &lt;&lt; -1, 3;

    boost::property_tree::ptree params;

    // set some of the optimization parameters
    params.put("Opt.MaxIts", 10000);
    params.put("Opt.ftol", 1e-8);
    params.put("Opt.xtol", 1e-8);
    params.put("Opt.gtol", 1e-8);
    params.put("Opt.LineSearch.LineIts", 100);
    params.put("Opt.StepLength", 1);
    params.put("Opt.verbosity", 3);
    
    //params.put("Opt.Method", "SD_Line");    // Use the steepest descent algorithm
    //params.put("Opt.Method", "BFGS_Line");  // Use the BFGS algorithm
    params.put("Opt.Method", "Newton");     // Use the Newton algorithm

    // set up the solver
    auto Solver = muq::Optimization::OptAlgBase::Create(prob, params);


    // solve the optimization problem
    Eigen::VectorXd xOpt = Solver-&gt;solve(x0);

    // Get the termination status
    int optStat = Solver-&gt;GetStatus();

    std::cout &lt;&lt; "Optimal solution = " &lt;&lt; xOpt.transpose() &lt;&lt; std::endl;
    
    return 0;
} // End of "int main()"


</pre>

