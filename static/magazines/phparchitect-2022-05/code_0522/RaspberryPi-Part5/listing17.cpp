// ----------------------------------------------------------------------------
// Base state: Default implementations
//

void DryerFSM::react(GForceOscillationEvent const &)
{
    cout << "Call event ignored" << endl;
}

chrono::steady_clock::time_point DryerFSM::startGForceOscillationTolleranceTime
        = std::chrono::steady_clock::now();

// ----------------------------------------------------------------------------
// Initial State Definition
//
FSM_INITIAL_STATE(DryerFSM, DryerOff);