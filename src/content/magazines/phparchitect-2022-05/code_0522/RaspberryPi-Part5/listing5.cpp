bool GForceOscillation::isMovingAverageDataAvailable()
{
    bool retVal = false;

    if (m_mv_avg_axis_vec.size() == MV_AVG_VEC_SIZE)
    {
        retVal = true;
    }

    return retVal;
}