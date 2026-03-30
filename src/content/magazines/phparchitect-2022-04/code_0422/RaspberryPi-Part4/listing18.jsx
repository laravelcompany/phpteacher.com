data.accelerationData.accelerationMeasurements.forEach(function(accelerationMeasurement)
{
    // In order to be compatible with the Safari 
    // browser, we cannot just pass in the datetime 
    // string into the Date() constructor. 
    // Instead, we much pass in each datetime 
    // component as a parameter to the Date() 
    // constructor
    let dateComps = 
        accelerationMeasurement.dateTime.split(/[^0-9]/);
    let dateTime = 
        new Date( 
                  dateComps[0], 
                  dateComps[1] - 1, 
                  dateComps[2],
                  dateComps[3], 
                  dateComps[4], 
                  dateComps[5], 
                  dateComps[6]
        ).getTime();

    let xValue = accelerationMeasurement.axis.X;
    let yValue = accelerationMeasurement.axis.Y;
    let zValue = accelerationMeasurement.axis.Z;

    axis_x.append(dateTime, xValue);
    axis_y.append(dateTime, yValue);
    axis_z.append(dateTime, zValue);
});