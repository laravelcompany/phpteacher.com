function getLatestAccelerometerData (axis_x, axis_y, axis_z) {
  if (AccelerometerData.lastId === 0) {
    $.getJSON(Server.url,
      {},
      function (data, status) {
        if (status === 'success') {
          AccelerometerData.lastId = data.accelerationData.lastMeasurementId;

          // In order to be compatible with the Safari browser, we cannot just pass in
          // the datetime string into the Date() constructor. Instead, we much pass in
          // each datetime component as a parameter to the Date() constructor
          let dateComps = data.accelerationData.accelerationMeasurements[0].dateTime.split(/[^0-9]/);
          let dateTime = new Date(dateComps[0], dateComps[1] - 1, dateComps[2],
            dateComps[3], dateComps[4], dateComps[5], dateComps[6]).getTime();

          let xValue = data.accelerationData.accelerationMeasurements[0].axis.X;
          let yValue = data.accelerationData.accelerationMeasurements[0].axis.Y;
          let zValue = data.accelerationData.accelerationMeasurements[0].axis.Z;

          axis_x.append(dateTime, xValue);
          axis_y.append(dateTime, yValue);
          axis_z.append(dateTime, zValue);
        }
      });
  } else // Send GET using last measured Id
  { ... }
}