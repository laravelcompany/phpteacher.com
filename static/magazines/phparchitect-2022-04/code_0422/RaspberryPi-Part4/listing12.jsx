function plotAccelerometerData () {
  $.getJSON('js_get_server_name.php',
    function (data) {
      Server.url = 'http:\/\/'
        + Server.server_name
        + '/accelerometerservice/';
    });
...
}