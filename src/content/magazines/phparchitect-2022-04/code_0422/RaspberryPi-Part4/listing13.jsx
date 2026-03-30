function plotAccelerometerData () {
...
  setInterval(function () {
    getLatestAccelerometerData(x, y, z);
  }, 1000);
...
}