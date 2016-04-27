
#include <Eigen/Core>
#include <boost/property_tree/ptree.hpp>

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
  
  double LogDensityImpl(std::vector<Eigen::VectorXd> const& x) override{
    
    Eigen::VectorXd r(2);
    r(0) = x[0](0)/a;
    r(1) = a*x[0](1) - a*b*(r(0)*r(0) + a*a);
    
    return -0.5*(r(0)*r(0) + r(1)*r(1));
  };
  
  Eigen::VectorXd GradientImpl(std::vector<Eigen::VectorXd> const& x,
                               Eigen::VectorXd              const& sens,
                               int                          const  inputDimWrt) override{
    
    Eigen::VectorXd grad(2);
    
    grad(0) = -(x[0](0)*(2*pow(a,4.0)*pow(b,2.0) - 2*x[0](1)*a*a*b + 2*b*b*pow(x[0](0),2.0) + 1))/(a*a);
    grad(1) = b*pow(a,4.0) - x[0](1)*a*a + b*pow(x[0](0),2.0);
    
    return grad;
  };
  
  Eigen::MatrixXd HessianImpl(std::vector<Eigen::VectorXd> const& x,
                              Eigen::VectorXd              const& sens,
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
std::shared_ptr<MCMCBase> MH_Simple_Setup()
{
  
  auto targetDens = make_shared<Boomerang>();
  auto problem = make_shared<SamplingProblem>(targetDens);
  
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
std::shared_ptr<MCMCBase> AM_Simple_Setup()
{
  auto targetDens = make_shared<Boomerang>();
  auto problem = make_shared<SamplingProblem>(targetDens);
  
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
std::shared_ptr<MCMCBase> DRAM_Simple_Setup()
{
  auto targetDens = make_shared<Boomerang>();
  auto problem = make_shared<SamplingProblem>(targetDens);
  
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
std::shared_ptr<MCMCBase> DR_Simple_Setup()
{
  auto targetDens = make_shared<Boomerang>();
  auto problem = make_shared<SamplingProblem>(targetDens);
  
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
std::shared_ptr<MCMCBase> AMALA_Simple_Setup()
{
  auto targetDens = make_shared<Boomerang>();
  auto problem = make_shared<SamplingProblem>(targetDens);
  
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
std::shared_ptr<MCMCBase> NUTS_Simple_Setup()
{
  
  auto targetDens = make_shared<Boomerang>();
  auto problem = make_shared<SamplingProblem>(targetDens);
  
  boost::property_tree::ptree params;
  params.put("MCMC.Method", "SingleChainMCMC");
  params.put("MCMC.Kernel", "StanNUTS");
  
  params.put("MCMC.Steps", 10000);
  params.put("MCMC.BurnIn", 1000);
  params.put("Verbose", 3);
  
  auto mcmc = MCMCBase::ConstructMCMC(problem, params);
  
  return mcmc;
}
std::shared_ptr<MCMCBase> MH_Setup()
{
  
  auto targetDens = make_shared<Boomerang>();
  auto problem = make_shared<SamplingProblem>(targetDens);
  
  boost::property_tree::ptree params;
  params.put("MCMC.Steps", 10000);
  params.put("MCMC.BurnIn", 1000);
  params.put("Verbose", 3);
  
  Eigen::VectorXd propMu = Eigen::VectorXd::Zero(2);
  Eigen::MatrixXd propCov(2,2);
  propCov << 1.0, 0,
             0.0, 3.0;
  
  auto propPair = make_shared<GaussianPair>(propMu,propCov);
  auto proposal = make_shared<MHProposal>(problem,params, propPair);
  auto kernel = make_shared<MHKernel>(problem,params,proposal);
  auto mcmc = make_shared<SingleChainMCMC>(kernel,params);

  return mcmc;
}
std::shared_ptr<MCMCBase> PreMALA_Setup()
{
  
  auto targetDens = make_shared<Boomerang>();
  auto problem = make_shared<SamplingProblem>(targetDens);
  
  boost::property_tree::ptree params;
  params.put("MCMC.Steps", 10000);
  params.put("MCMC.BurnIn", 1000);
  params.put("Verbose", 3);
  
  Eigen::VectorXd propMu = Eigen::VectorXd::Zero(2);
  Eigen::MatrixXd propCov(2,2);
  propCov << 1.0, 0,
             0.0, 3.0;
  
  auto proposal = make_shared<PreMALA>(problem,params, make_shared<GaussianPair>(propMu,propCov));
  auto kernel = make_shared<MHKernel>(problem,params,proposal);
  auto mcmc = make_shared<SingleChainMCMC>(kernel,params);
  
  return mcmc;
}
std::shared_ptr<MCMCBase> MMALA_Setup()
{
  
  auto targetDens = make_shared<Boomerang>();
  auto metric = make_shared<HessianMetric>(targetDens);
  auto problem = make_shared<SamplingProblem>(targetDens,metric);
  
  boost::property_tree::ptree params;
  params.put("MCMC.Steps", 10000);
  params.put("MCMC.BurnIn", 1000);
  params.put("Verbose", 3);
  
  auto proposal = make_shared<MMALA>(problem,params);
  auto kernel = make_shared<MHKernel>(problem,params,proposal);
  auto mcmc = make_shared<SingleChainMCMC>(kernel,params);
  
  return mcmc;
}
std::shared_ptr<MCMCBase> TransportMap_Setup()
{
  auto targetDens = make_shared<Boomerang>();
  auto problem = make_shared<SamplingProblem>(targetDens);

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
  
  auto kernel = make_shared<TransportMapKernel>(problem, params, initialMap);
  auto mcmc = make_shared<SingleChainMCMC>(kernel, params);
  
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
  startingPoint << 0, 1.0;
  
  EmpRvPtr mcmcChain = mcmc->Sample(startingPoint);

  Eigen::VectorXd mean = mcmcChain->getMean();
  Eigen::MatrixXd cov  = mcmcChain->getCov();
  
  cout << "\nSample Mean:\n";
  cout << mean << endl << endl;
  
  cout << "Sample Covariance:\n";
  cout << cov << endl << endl;

  Eigen::VectorXd ess = mcmcChain->getEss();
  
  // find the minimum and maximum ess
  cout << "Minimum ESS: " << ess.minCoeff() << endl;
  cout << "Maximum ESS: " << ess.maxCoeff() << endl << endl;
}