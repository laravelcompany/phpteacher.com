<!DOCTYPE html>
<html>
  <head>
    <title>Lame Raspberry Pi Home Page</title>
    <!-- Latest compiled and minified CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css"
          rel="stylesheet"
          integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3"
          crossorigin="anonymous">
  </head>
  <body>
    <div class='card'>
      <div class='card-body'>
        <h1>This is my Less, but Still Lame Raspberry Pi Home Page</h1>
      </div>
      <div class='card-body'>
        <div class="alert alert-dark" role="alert">
          <h3 class='text-danger'>Wow! If you got here, that means Apache is running. Yay!</h3>
        </div>
      </div>
      <div class='card-body'>
        <div class="alert alert-info" role="alert">
          <? date_default_timezone_set('America/Chicago'); ?>
          <h3>Today is: <?= date('l \t\h\e jS \of F Y') ?></h3>
          <h3>The time is: <?= date('h:i:s A') ?></h3><br/>
        </div>
      </div>
    </div>
    <!-- Lastest compiled and minified bundled JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p"
            crossorigin="anonymous"></script>
  </body>
 </html>