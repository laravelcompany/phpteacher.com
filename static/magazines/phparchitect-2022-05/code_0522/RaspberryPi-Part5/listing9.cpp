tAxis GForceOscillation::getMinMaxDiff()
{
    tAxis retVal;
    tAxis min;
    tAxis max;

    vector<tAxis>::const_iterator iter;

    for (iter = m_min_max_axis_vec.begin(); iter != m_min_max_axis_vec.end(); iter++)
    {
        if (iter == m_min_max_axis_vec.begin()) {
            min.X = max.X = iter->X;
            min.Y = max.Y = iter->Y;
            min.Z = max.Z = iter->Z;
        } // END IF 1st iteration
        else {
            if (iter->X < min.X) {
                min.X = iter->X;
            } else if (iter->X > max.X) {
                max.X = iter->X;
            }

            if (iter->Y < min.Y)
            {
                min.Y = iter->Y;
            } else if (iter->Y > max.Y) {
                max.Y = iter->Y;
            }

            if (iter->Z < min.Z) {
                min.Z = iter->Z;
            } else if (iter->Z > max.Z) {
                max.Z = iter->Z;
            }
        } // END ELSE all other iterations except the 1st
    } // END FOR EACH min_max_axis_vec item

    retVal.X = max.X - min.X;
    retVal.Y = max.Y - min.Y;
    retVal.Z = max.Z - min.Z;

    return retVal;
}

#endif // GFORCE_OSCILLATION__CPP