cmake_minimum_required (VERSION 2.8) 

find_package(MUQ REQUIRED)

include_directories(${MUQ_INCLUDE_DIRS})
set(CMAKE_CXX_FLAGS ${MUQ_CXX_FLAGS})
set(CMAKE_CXX_COMPILER ${MUQ_CXX_COMPILER})

add_executable(SmallMultiscale SmallMultiscale.cpp)

target_link_libraries(SmallMultiscale ${MUQ_LIBRARIES} ${MUQ_LINK_LIBRARIES})
