// ----------------------------------------------------------------------------
// State: DryerGoingOn
//
class DryerGoingOn : public DryerFSM
{
		void entry() override // Start timer
    {
        cout << "Dryer: Going On" << endl;

        startGForceOscillationTolleranceTime = chrono::steady_clock::now();
    }

		void react(GForceOscillationEvent 		const & e) override
    {
        chrono::steady_clock::time_point nowTime = chrono::steady_clock::now();

        chrono::steady_clock::duration timeDiff = nowTime - startGForceOscillationTolleranceTime;

        int duration = chrono::duration_cast<chrono::seconds>(timeDiff).count();

        if (e.gForceOscillation < GFORCE_OSCILLATION_TOLLERANCE)
        {
	        	transit<DryerOff>();
        }
        else if (duration > GFORCE_OSCILLATION_TIMEOUT)
        {
		        transit<DryerOn>();
        }
    }
};