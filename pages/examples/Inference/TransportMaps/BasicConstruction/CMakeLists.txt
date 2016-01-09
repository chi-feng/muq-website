cmake_minimum_required (VERSION 2.8) 

# After bulding and installing MUQ, cmake will cache the directory where MUQ was installed.  This makes it easy to find the MUQ installation with the cmake find_package() command.  Note that we have NOT provided a FindMUQ.cmake file, when a library is installed with cmake, cmake automatically provides the find_package() functionality.
find_package(MUQ REQUIRED)

# after the find_package call, the cmake variables MUQ_INCLUDE_DIRS, MUQ_LIBRARIES, and MUQ_LINK_LIBRARIES are defined.  The MUQ_INCLUDE_DIRS contains the directories where the MUQ header files were installed.  The MUQ_LIBRARIES variable holds all of the MUQ libraries that were built, and the MUQ_LINK_LIBRARIES contains all the external libraries used by MUQ.  In this line, we just make sure cmake knows where the MUQ header files are, but later we will use the MUQ_LIBRARIES and MUQ_LINK_LIBRARIES variables.
include_directories(${MUQ_INCLUDE_DIRS})

# In addition to these cmake variables, the find_package command defines variables related to the MUQ build.  These include the compiler and compiler flags.  These lines make sure the compiler and compile flags used to compile this example are the same as the compiler and flags used to build MUQ.
set(CMAKE_CXX_FLAGS ${MUQ_CXX_FLAGS})
set(CMAKE_CXX_COMPILER ${MUQ_CXX_COMPILER})

# This line defines the executable name and source to include
add_executable(BasicConstruction BasicConstruction.cpp)

# This line tells cmake to link the example executable to the MUQ libraries as well as the external libraries that MUQ requires.
target_link_libraries(BasicConstruction ${MUQ_LIBRARIES} ${MUQ_LINK_LIBRARIES})
