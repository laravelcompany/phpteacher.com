tAxis GForceOscillation::getMovingAverage()
{
    tAxis total;

    tAxis retVal;

    vector<tAxis>::const_iterator iter;

    for (iter = m_mv_avg_axis_vec.begin(); iter != m_mv_avg_axis_vec.end(); iter++)
    {
        total.X += iter->X;
        total.Y += iter->Y;
        total.Z += iter->Z;
    }

    if (total.X != 0.0 && total.Y != 0.0 && total.Z != 0.0)
    {
        retVal.X = total.X / m_mv_avg_axis_vec.size();
        retVal.Y = total.Y / m_mv_avg_axis_vec.size();
        retVal.Z = total.Z / m_mv_avg_axis_vec.size();
    }

    return retVal;
}