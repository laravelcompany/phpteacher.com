void GForceOscillation::appendMovingAverageData(tAxis axis)
{
    m_mv_avg_axis_vec.push_back(axis);

    if (m_mv_avg_axis_vec.size() > MV_AVG_VEC_SIZE)
    {
        m_mv_avg_axis_vec.erase(m_mv_avg_axis_vec.begin());
    }
}