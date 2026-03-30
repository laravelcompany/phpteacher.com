...
// Forever Loop
while(1)
{
		...
		g_force_oscillation.appendMovingAverageData(axis);

		if (g_force_oscillation.isMovingAverageDataAvailable())
    {
        g_force_oscillation.appendMinMaxData(g_force_oscillation.getMovingAverage());

        if (g_force_oscillation.isMinMaxDataAvailable())
        {
            tAxis min_max_diff = g_force_oscillation.getMinMaxDiff();

            // We are only concerned with the Z axis difference for driving
            // our state machine
            g_force_oscillation_event.gForceOscillation = min_max_diff.Z;

            send_event(g_force_oscillation_event);

            /*
            cout << "Min/Max Diff: X: " << min_max_diff.X
                    << ", Y: " << min_max_diff.Y
                    << ", Z: " << min_max_diff.Z
                    << endl;
            */
        }
    }

    usleep(100000); // 100 milliseconds} // End forever loop
} // End forever loop
...