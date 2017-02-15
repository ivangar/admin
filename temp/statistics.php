<?php 


include_once($_SERVER['DOCUMENT_ROOT'] . '/Admin/lib/metrics.php');

  if(!isset($_SESSION['user_is_logged_in']) || (!$_SESSION['user_is_logged_in']))
  {
     header("Location: index.php");
  }

$metrics = new Metrics();
$results = $metrics->Set_Results();
$results = json_encode($results);

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
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <!-- Place favicon.ico and apple-touch-icon.png in the root directory -->

    <!-- Bootstrap core CSS -->
        <link href="css/bootstrap.css" rel="stylesheet">
        <link rel="stylesheet" href="css/normalize.css">
        <link rel="stylesheet" href="css/main.css">
         <link rel="stylesheet" href="https://code.jquery.com/ui/1.11.0/themes/smoothness/jquery-ui.css">

        <script src="js/vendor/modernizr-2.6.2.min.js"></script>
        <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
        <script src="js/ie10-viewport-bug-workaround.js"></script>
        
        <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
        <!--[if lt IE 9]>
          <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
          <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
        <![endif]-->
        <style>


        </style>
    </head>
    <body>
      <div class="navbar-wrapper">
            <div class"container">
                <div class="navbar navbar-inverse navbar-fixed-top" role="navigation">
                  <div class="container">
                    <div class="navbar-header">
                      <button class="navbar-toggle" data-target=".navbar-collapse" data-toggle="collapse" type="button">
                        <span class="sr-only">Toggle navigation</span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                      </button>
                      <a class="navbar-brand" style="color:#FFFFFF;" href="javascript:void(0)">dxLink Admin</a>
                    </div>
                    <div class="navbar-collapse collapse">
                      <ul class="nav navbar-nav">      
                          <li><a href="https://<?php echo $_SERVER['HTTP_HOST'] ?>/Admin/users.php">Program Users</a></li>    
                          <li><a href="https://<?php echo $_SERVER['HTTP_HOST'] ?>/Admin/forum.php">Discussion Forum</a></li>
                          <li><a href="https://<?php echo $_SERVER['HTTP_HOST'] ?>/Admin/program_tests.php">Test Results</a></li>
                          <li><a href="https://<?php echo $_SERVER['HTTP_HOST'] ?>/Admin/program_evaluations.php">Program Evaluations</a></li>
                          <li class="active"><a href="https://<?php echo $_SERVER['HTTP_HOST'] ?>/Admin/statistics.php">Metrics</a></li>
                      </ul>
                      <ul class="nav navbar-nav navbar-right">
                        <li><a href="index.php?action=logout">Logout</a>
                        </li>
                      </ul>
                    </div>

                  </div>
                </div>
            </div>
        </div>

        <div class="container-fluid">
          <div class="row">
              <div class="col-sm-3 col-md-2 sidebar">
                <ul class="nav nav-sidebar">
                  <li><a href="https://<?php echo $_SERVER['HTTP_HOST'] ?>/Admin/users.php">Program Users</a></li>
                  <li><a href="https://<?php echo $_SERVER['HTTP_HOST'] ?>/Admin/forum.php">Discussion Forum</a></li>
                  <li><a href="https://<?php echo $_SERVER['HTTP_HOST'] ?>/Admin/program_tests.php">Test Results</a></li>
                  <li><a href="https://<?php echo $_SERVER['HTTP_HOST'] ?>/Admin/program_evaluations.php">Program Evaluations</a></li>
                  <li class="active"><a href="https://<?php echo $_SERVER['HTTP_HOST'] ?>/Admin/statistics.php">Metrics</a></li>
                </ul>
              </div>
              <div class="col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main">
              <!--<div class="col-sm-12 col-md-12 main">-->
              
              <div class="row"> 
                  <h1 class="page-header" style="margin-top: 0;"> Program Metrics </h1>
              </div>
              <?php  echo $metrics->output; ?>
            </div>
          </div>
        </div>
        <ul>

        <script> 
            var results = <?php if(isset($results)) {echo $results;} else echo "false"; ?>;
            var tests = [];
            
            for (programs = 0; programs < results.length; programs++) {
                //Insert in each program an array holding pretest results, and another with postest
                tests[programs] = [results[programs][0], results[programs][1]];
            }

        </script>
        <script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
        <script>window.jQuery || document.write('<script src="js/vendor/jquery-1.10.2.min.js"><\/script>')</script>
        <script src="https://code.jquery.com/ui/1.11.0/jquery-ui.js"></script>
        <script src="js/plugins.js"></script>
        <script src="js/bootstrap.min.js"></script>
        <script src="js/docs.min.js"></script>
        <script src="js/jquery.fileDownload.js"></script>
        <script src="js/stats.js"></script>
        <script src="js/Chart.js"></script>
        <script src="js/jquery.tablesorter.min.js"></script>
        <link rel="stylesheet" href="https://code.jquery.com/ui/1.10.4/themes/smoothness/jquery-ui.css">
        <script src="https://code.jquery.com/ui/1.10.4/jquery-ui.js"></script>

        <!-- Google Analytics: change UA-XXXXX-X to be your site's ID. -->
    </body>
</html>
