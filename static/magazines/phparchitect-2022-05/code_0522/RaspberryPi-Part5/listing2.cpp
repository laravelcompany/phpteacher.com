#ifndef GFORCE_OSCILLATION__HPP
#define GFORCE_OSCILLATION__HPP
#include <vector>

using namespace std;

// accelerometer axis structure
typedef struct axis
{
    float X = 0.0;
    float Y = 0.0;
    float Z = 0.0;
}tAxis;

typedef vector<tAxis> tAxisVec;

// ---------------------------------------
// GForceOscillation (class) declaration
//
class GForceOscillation
{
public:
    GForceOscillation();
    ~GForceOscillation();

    void appendMovingAverageData(tAxis axis);

    bool isMovingAverageDataAvailable();
    tAxis getMovingAverage();

    void appendMinMaxData(tAxis axis);

    bool isMinMaxDataAvailable();
    tAxis getMinMaxDiff();
protected:
    tAxisVec m_mv_avg_axis_vec;
    tAxisVec m_min_max_axis_vec;
    static const int MV_AVG_VEC_SIZE;
};

#endif // GFORCE_OSCILLATION__HPP