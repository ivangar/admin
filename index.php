<?php 
if(!isset($_SESSION)){session_start();} 
include_once($_SERVER['DOCUMENT_ROOT'] . '/admin/lib/login.php');

if (isset($_GET["action"]) && $_GET["action"] == "logout") {
    $application = new OneFileLoginApplication();
}

?>
<!DOCTYPE html>
<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js"> <!--<![endif]-->
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title></title>
        <meta name="description" content="">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link href="css/bootstrap.css" rel="stylesheet">
        
        <link href="css/cover.css" rel="stylesheet">
        <link rel="stylesheet" href="css/normalize.css">
        <link rel="stylesheet" href="css/signin.css">
        <script src="js/vendor/modernizr-2.6.2.min.js"></script>
        <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
        <script src="js/ie10-viewport-bug-workaround.js"></script>
        
        <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
        <!--[if lt IE 9]>
          <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
          <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
        <![endif]-->
    </head>
    <body>
      <div class="site-wrapper">
        <div class="site-wrapper-inner">
          <div class="cover-container">

            <form class="form-signin" role="form" id='login_form' name='login_form' action='' method='post' accept-charset='UTF-8'>
              <input type='hidden' name='login_submitted' id='login_submitted' value='1' />
              <input type='hidden' name='remember_submitted' id='remember_submitted' value='0' />
              <h2 class="form-signin-heading">Please sign in</h2>
              <div class="form-group">
                <input type="email" class="form-control" name="user_name" id="user_name" placeholder="admin" required autofocus>
              </div>
              <div class="form-group">
                <input type="password" class="form-control" name="user_password" id="user_password" placeholder="Password" required>
              </div>
              <?php if(isset($_SESSION['message'])) { echo "<div class='alert alert-danger alert-error'><a href='#' class='close' data-dismiss='alert'>&times;</a>" . $_SESSION['message'] . "</div>"; unset($_SESSION['message']);}?>
              <a href="#" class="button">Sign in</a>
              <div class="btn btn-primary active form-control">
                <label>
                  <input class="checkbox" type='checkbox' id='remember_me' name='remember_me' value='1'> Remember me
                </label>
              </div>
            </form>

          </div>
        </div>
      </div>
        <script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
        <script>window.jQuery || document.write('<script src="js/vendor/jquery-1.10.2.min.js"><\/script>')</script>
        <script src="js/plugins.js"></script>
        <script src="js/bootstrap.min.js"></script>
        <script src="js/docs.min.js"></script>
        <script src="js/login.js"> </script>
        <!-- Google Analytics: change UA-XXXXX-X to be your site's ID. -->
    </body>
</html>