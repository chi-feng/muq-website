<?php
$tpl['page_name'] = 'example-1-python';
$tpl['tab'] = 'Examples';
?>
<h2 id="Goal:">Goal:</h2><p>MUQ's modelling module provides tools for constructing, combining, and manipulating model components.  In this setting, a model component can be any deterministic or stochastic input-output relationship.  This example creates a single model component to evaluate the right hand side of an ODE describing a classic predator-prey system.</p>
<p>Mathematically, the system of interest is given by
\begin{eqnarray}
\frac{d P}{dt} &amp;=&amp; P \left[r\left(1 - \frac{P}{K}\right) - s \frac{Q}{a + P}\right] \
\frac{d Q}{dt} &amp;=&amp; Q\left[ \frac{vP}{a + P} - u\right]
\end{eqnarray}
where $P$ is the prey population; $Q$ is the predator population; and $r$, $K$, $s$, $a$, $v$, and $u$ are model parameters.  Thie example will define an object that takes $[P,Q]$ as inputs, and uses fixed values of $[r,K,s,a,v,u]$ to compute $dP/dt$ and $dQ/dt$.</p>
<h2 id="ModPieces">ModPieces</h2><p>The ModPiece class provides the basic building block for constructing sophisticated models in MUQ.  To define a model component, MUQ users need to create a child of the ModPiece class with an EvaluateImpl function.  Here we will create a child class that evaluates the right hand side of our ODE system in the EvaluateImpl function.  For simplicity, we inherit from another class called OneInputNoDerivModPiece, which is simple a ModPiece with one input that does not provide any derivative information.</p>
<h3 id="Import-MUQ's-modelling-library">Import MUQ's modelling library</h3><p>First we import the modelling library, which is called libmuqModelling in Python</p>
<pre class="prettyprint">
import libmuqModelling
</pre>

<h3 id="Define-the-model-component">Define the model component</h3><p>Now we are ready to define our model component.  Our new class, PredPreyModel, will evaluate the right hand side of the ODE system above.  The constructor takes the 6 model parameters as inputs and initialized the <a href="http://muq.mit.edu/develop-docs/classmuq_1_1Modelling_1_1OneInputNoDerivModPiece.html">OneInputNoDerivModPiece</a> with the input and output dimensions (both 2).  Note that the input and output dimensions of all models in MUQ are constant, which means that these dimensions must be set in the ModPiece constructor.</p>
<p>The EvaluateImpl function must be defined in all children of the ModPiece class (including children of OneInputNoDerivModPiece).  This function is where all the magic happens; it is where the model computation is performed.</p>
<pre class="prettyprint">
class PredPreyModel(libmuqModelling.OneInputNoDerivModPiece):

    def __init__(self, r, k, s, a, u, v):
        libmuqModelling.OneInputNoDerivModPiece.__init__(self, 2, 2)

        self.preyGrowth      = r
        self.preyCapacity    = k
        self.predationRate   = s
        self.predationHunger = a
        self.predatorLoss    = u
        self.predatorGrowth  = v
    
    def EvaluateImpl(self, ins):
        
        preyPop = ins[0]
        predPop = ins[1]

        output = [None]*2

        output[0] = preyPop * (self.preyGrowth * (1 - preyPop / self.preyCapacity) 
                               - self.predationRate * predPop / (self.predationHunger + preyPop))

        output[1] = predPop * (self.predatorGrowth * preyPop / (self.predationHunger + preyPop) - self.predatorLoss)

        return output
</pre>

<h3 id="Create-the-model-instance">Create the model instance</h3><p>Now that we've defined the model in our PredPreyModel class, lets put it to work.  In the following cell, we create an instance of the PredPreyModel class.</p>
<pre class="prettyprint">
# Before constructing the model, we will set the fixed model values.
preyGrowth      = 0.8
preyCapacity    = 100
predationRate   = 4.0
predationHunger = 15
predatorLoss    = 0.6
predatorGrowth  = 0.8

# create an instance of the ModPiece defining the predator prey model.
predatorPreyModel = PredPreyModel(preyGrowth,
                                  preyCapacity,
                                  predationRate,
                                  predationHunger,
                                  predatorLoss,
                                  predatorGrowth)
</pre>

<h3 id="Evaluate-the-model">Evaluate the model</h3><p>To evaluate our model, we can simply call the Evaluate function of the PredPreyModel.  Note that this function is inherited from the ModPiece and is distinct from the EvaluateImpl function defined above.  Calling Evaluate instead of EvaluateImpl allows MUQ to perform some additional error correcting and caching that is not present in EvaluateImpl.</p>
<pre class="prettyprint">
populations = [50.0, 5.0]

growthRates = predatorPreyModel.Evaluate(populations)
</pre>

<h3 id="Display-the-model-output">Display the model output</h3><p>Print the results.</p>
<pre class="prettyprint">
print "The prey growth rate evaluated at     (", populations[0], ",", populations[1], ") is ", growthRates[0]
print "The predator growth rate evaluated at (", populations[0], ",", populations[1], ") is ", growthRates[1]
</pre>

<pre class="prettyprint lang-bash" style="background-color:#D0D0D0">
The prey growth rate evaluated at     ( 50.0 , 5.0 ) is  4.61538461538
The predator growth rate evaluated at ( 50.0 , 5.0 ) is  0.0769230769231

</pre>

<h3 id="Get-information-about-the-model">Get information about the model</h3><p>We can also ask the model component for it's input and output sizes.  Every Model in MUQ has a constant vector, "inputSizes", that contains the length of each vector valued input.  Since our Model only has one input, inputSizes only has one entry.  The output dimension of the model is stored in the "outputSize" variable.  Since MUQ models can only have one output, "outputSize" is simply an integer.</p>
<pre class="prettyprint">
print "The input size is  ", predatorPreyModel.inputSizes[0] 
print "The output size is ", predatorPreyModel.outputSize
</pre>

<pre class="prettyprint lang-bash" style="background-color:#D0D0D0">
The input size is   2
The output size is  2

</pre>

<h2>Completed code:</h2><pre class="prettyprint" style="height:auto;max-height:400px;">
import libmuqModelling

class PredPreyModel(libmuqModelling.OneInputNoDerivModPiece):

    def __init__(self, r, k, s, a, u, v):
        libmuqModelling.OneInputNoDerivModPiece.__init__(self, 2, 2)

        self.preyGrowth      = r
        self.preyCapacity    = k
        self.predationRate   = s
        self.predationHunger = a
        self.predatorLoss    = u
        self.predatorGrowth  = v
    
    def EvaluateImpl(self, ins):
        
        preyPop = ins[0]
        predPop = ins[1]

        output = [None]*2

        output[0] = preyPop * (self.preyGrowth * (1 - preyPop / self.preyCapacity) 
                               - self.predationRate * predPop / (self.predationHunger + preyPop))

        output[1] = predPop * (self.predatorGrowth * preyPop / (self.predationHunger + preyPop) - self.predatorLoss)

        return output

# Before constructing the model, we will set the fixed model values.
preyGrowth      = 0.8
preyCapacity    = 100
predationRate   = 4.0
predationHunger = 15
predatorLoss    = 0.6
predatorGrowth  = 0.8

# create an instance of the ModPiece defining the predator prey model.
predatorPreyModel = PredPreyModel(preyGrowth,
                                  preyCapacity,
                                  predationRate,
                                  predationHunger,
                                  predatorLoss,
                                  predatorGrowth)

populations = [50.0, 5.0]

growthRates = predatorPreyModel.Evaluate(populations)

print "The prey growth rate evaluated at     (", populations[0], ",", populations[1], ") is ", growthRates[0]
print "The predator growth rate evaluated at (", populations[0], ",", populations[1], ") is ", growthRates[1]

print "The input size is  ", predatorPreyModel.inputSizes[0] 
print "The output size is ", predatorPreyModel.outputSize


</pre>

