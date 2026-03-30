// -----------------------------
// State: DryerOff
//
class DryerOff : public DryerFSM
{
    void entry() override
    {
        cout << "Dryer: Off" << endl;
    }

    void react(GForceOscillationEvent const & e) override
    {
        if (e.gForceOscillation > GFORCE_OSCILLATION_TOLLERANCE)
        {
        	transit<DryerGoingOn>();
        }
    }
};