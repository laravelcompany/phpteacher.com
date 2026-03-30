#ifndef DRYERFSM__HPP
#define DRYERFSM__HPP

#include "tinyfsm.hpp"
#include <chrono>

// ------------------------------------------
// Event declarations
//

struct GForceOscillationEvent : tinyfsm::Event
{
    float gforceOscillation;
};

// ------------------------------------------
// DryerFSM (FSM base class) declaration
//
class DryerFSM : public tinyfsm::Fsm<DryerFSM>
{
public:
    // Default reaction for unhandled events
    void react(tinyfsm::Event const &) {};

    virtual void react(GForceOscillationEvent   const &);
    virtual void entry(void) {}; // entry actions in some states
    virtual void exit(void) {}; // Maybe exit actions in some states

protected:
    static const float GFORCE_OSCILLATION_TOLLERANCE;
    static const int GFORCE_OSCILLATION_TIMEOUT; // In seconds
    static std::chrono::steady_clock::time_point startGForceOscillationTolleranceTime;
    // Use: std::chrono::steady_clock::now(); // to set current time
};

#endif // DRYERFSM__HPP