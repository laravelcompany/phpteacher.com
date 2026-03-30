function plotAccelerometerData () {
...
  let smoothie = new SmoothieChart(
    {
      responsive: true,
      grid:
        {
          strokeStyle: 'rgb(125, 0, 0)',
          fillStyle: 'rgb(60, 0, 0)',
          lineWidth: 1,
          millisPerLine: 250,
          verticalSections: 6
        },
      labels: {fillStyle: 'rgb(255, 255, 0)'}
    });
...
}