void GForceOscillation::appendMinMaxData(tAxis axis)
{
    m_min_max_axis_vec.push_back(axis);

    if (m_min_max_axis_vec.size() > MV_AVG_VEC_SIZE)
    {
        m_min_max_axis_vec.erase(m_min_max_axis_vec.begin());
    }
}