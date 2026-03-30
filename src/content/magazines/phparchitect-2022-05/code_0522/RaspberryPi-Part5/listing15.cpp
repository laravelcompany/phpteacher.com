// ----------------------------------------------------------------------------
// State: DryerOn
//
class DryerOn : public DryerFSM
{
    void entry() override
    {
        cout << "Dryer: On" << endl;
    }

    void react(GForceOscillationEvent const & e) override
    {
        if (e.gForceOscillation < GFORCE_OSCILLATION_TOLLERANCE)
        {
            transit<DryerGoingOff>();
        }
    }
};