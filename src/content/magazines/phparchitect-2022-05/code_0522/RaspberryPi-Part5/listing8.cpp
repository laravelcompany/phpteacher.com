bool GForceOscillation::isMinMaxDataAvailable()
{
    bool retVal = false;

    if (m_min_max_axis_vec.size() == MV_AVG_VEC_SIZE)
    {
        retVal = true;
    }

    return retVal;
}