// ----------------------------------------------------------------------------
// State: DryerGoingOff
//
class DryerGoingOff : public DryerFSM
{
    void entry() override
    {
        cout << "Dryer: Going Off" << endl;

        startGForceOscillationTolleranceTime = chrono::steady_clock::now();
    }

    void react(GForceOscillationEvent const & e) override
    {
        chrono::steady_clock::time_point nowTime = chrono::steady_clock::now();

        chrono::steady_clock::duration timeDiff = nowTime - startGForceOscillationTolleranceTime;

        int duration = chrono::duration_cast<chrono::seconds>(timeDiff).count();

        if (e.gForceOscillation > GFORCE_OSCILLATION_TOLLERANCE)
        {
            transit<DryerOn>();
        }
        else if (duration > GFORCE_OSCILLATION_TIMEOUT)
        {
            #ifdef DRYER_STATUS_SEND_TEXT_MSG_ENABLED
						cout << "\tSend notification: Dryer is Off" << endl;
            system("echo \"Dryer is Off.\" | mail -s \"Dryer Status\" 6081234567@mms.att.net");
            #endif // DRYER_STATUS_SEND_TEXT_MSG_ENABLED

            transit<DryerOff>();
        }
    }
};