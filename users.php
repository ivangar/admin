<?php 
include_once($_SERVER['DOCUMENT_ROOT'] . '/admin/lib/accounts.php');


if(!isset($_SESSION['user_is_logged_in']) || (!$_SESSION['user_is_logged_in']))
{
   header("Location: index.php");
}

if(!isset($_SESSION['result'])){
    $user_accounts = new Accounts();
    $user_accounts->Set_Accounts();
    $user_accounts->Get_Total_Users();
    $users = $user_accounts->Print_Total_no_Users();
}

else{ $users = $_GET['users']; }

    /*
  *   Special attention to Generate_Dummy_post() function. It will insert many rows into database. Should be used only in production stage.
  *
    */

  //$user_accounts->Generate_Dummy_users(); 


?>
<!DOCTYPE html>
<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js"> <!--<![endif]-->
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>DxLink Users</title>
        <meta name="description" content="">
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <!-- Place favicon.ico and apple-touch-icon.png in the root directory -->

    <!-- Bootstrap core CSS -->
        <link href="css/bootstrap.css" rel="stylesheet">
        <link rel="stylesheet" href="css/normalize.css">
        <link rel="stylesheet" href="css/main.css">
         <link rel="stylesheet" href="https://code.jquery.com/ui/1.11.0/themes/smoothness/jquery-ui.css">
        <link href="//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css" rel="stylesheet">

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
    <!-- Modal HTML -->

    <div id="dialog-form" title="Confirmation" style="display:none;">
    <p>Are you sure you want to delete the comment from the database?</p>
    </div>
     <!-- Modal HTML -->
    
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
                          <li class="active"><a href="https://<?php echo $_SERVER['HTTP_HOST'] ?>/admin/users.php">Users</a></li>  
                          <li><a href="https://<?php echo $_SERVER['HTTP_HOST'] ?>/admin/forum.php">Discussion Forum</a></li>
                          <li><a href="https://<?php echo $_SERVER['HTTP_HOST'] ?>/admin/program_evaluations.php">Program Evaluations</a></li>
                          <li><a href="https://<?php echo $_SERVER['HTTP_HOST'] ?>/admin/evaluation_feedback.php">Feedback</a></li>
                          <li><a href="https://<?php echo $_SERVER['HTTP_HOST'] ?>/admin/statistics.php">Metrics</a></li>
                      </ul>
                      <ul class="nav navbar-nav navbar-right">

                        <li><a href="index.php?action=logout">Logout</a>
                        </li>
                      </ul>
                       <form class="navbar-form navbar-right" role="form" id='search_form' name='search_form' action='https://<?php echo $_SERVER['HTTP_HOST'] ?>/admin/lib/accounts.php' method='post' accept-charset='UTF-8'>
                          <div class="form-group">
                              <input type="text" class="form-control" name="search_q" placeholder="Search...">
                              <input type="text" class="form-control secondary" name="search_q2">
                          </div>
                          <div class="form-group">
                              <button id="button1id" name="button1id" class="btn btn-success btn-sm">Search</button>
                          </div>
                      </form>

                    </div>

                  </div>
                </div>
            </div>
        </div>

        <div class="container-fluid">
          <div class="row">
              <div class="col-sm-3 col-md-2 sidebar">
                <ul class="nav nav-sidebar">
                  <li class="active"><a href="https://<?php echo $_SERVER['HTTP_HOST'] ?>/admin/users.php"><i class="fa fa-user"></i>&nbsp; Users</a></li> 
                  <li><a href="https://<?php echo $_SERVER['HTTP_HOST'] ?>/admin/forum.php"><i class="fa fa-comment"></i>&nbsp; Discussion Forum</a></li>
                  <li><a href="https://<?php echo $_SERVER['HTTP_HOST'] ?>/admin/program_evaluations.php"><i class="fa fa-table"></i>&nbsp; Program Evaluations</a></li>
                  <li><a href="https://<?php echo $_SERVER['HTTP_HOST'] ?>/admin/evaluation_feedback.php"><i class="fa fa-comment"></i>&nbsp; Evaluation Feedback</a></li>
                  <li><a href="https://<?php echo $_SERVER['HTTP_HOST'] ?>/admin/statistics.php"><i class="fa fa-bar-chart"></i>&nbsp; Metrics</a></li>
                </ul>
              </div>
              <div class="col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main">
                <!--<div class="col-sm-12 col-md-12 main">-->
                 <div class="row">
                   <div class="col-sm-11 ">
                    <h1 class="page-header" style="margin-top: 0; margin-bottom: 40px;"> dxLink Users (total <?php echo $users; ?>)</h1>
                   </div>
                   <div class="col-sm-1 " >
                      <a href="#" class="btn btn-success" id="export"><i class="fa fa-download fa-lg"></i>&nbsp; Export</a>
                    </div>
                </div>
                <div class="table-responsive">
                <?php if(isset($_SESSION['result']) ) {?>
                    <div class="alert alert-success fade in" role="alert" >
                        <button class="close" data-dismiss="alert" type="button">
                            <span aria-hidden="true">×</span>
                            <span class="sr-only">Close</span>
                        </button>
                            <h3>Success!</h3>
                            <p>Your query brings the following result:</p>
                    </div>
                <?php } if(isset($_SESSION['error'])){?>
                    <div class="alert alert-danger fade in" role="alert" >
                        <button class="close" data-dismiss="alert" type="button">
                            <span aria-hidden="true">×</span>
                            <span class="sr-only">Close</span>
                        </button>
                            <h3>Error!</h3>
                            <p><?php echo $_SESSION['error']; ?></p>
                    </div>
                <?php unset($_SESSION['error']); } ?>
                  <div id="content">
                    <table class="table table-striped table-hover" id="sortable">
                      <thead>
                        <tr>
                          <th >User</th>
                          <th >Email</th>
                          <th >Country</th>
                          <th >Province</th>
                          <th >Postal Code</th>
                          <th >Profession</th>
                          <th >Specialty</th>
                          <th style="min-width:90px;">Registered</th>
                          <th >Programs Completed</th>
                          <th style="min-width:90px;">Last Visit</th> 
                        </tr>
                      </thead>
                      <tbody id="AIT-forum">
                         <input type="hidden" name="page_count" id="page_count" />
                        <?php if(isset($_SESSION['result']) && !isset($_SESSION['error'])){
                                echo $_SESSION['result'];
                                unset($_SESSION['result']);
                              }
                              else{ $user_accounts->getRows();}
                        ?>
                      </tbody>
                    </table> 
                  </div>
                </div>
            </div>
          </div>
        </div>

        <script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
        <script>window.jQuery || document.write('<script src="js/vendor/jquery-1.10.2.min.js"><\/script>')</script>
        <script src="https://code.jquery.com/ui/1.11.0/jquery-ui.js"></script>
        <script src="js/plugins.js"></script>
        <script src="js/bootstrap.min.js"></script>
        <script src="js/docs.min.js"></script>
        <script src="js/jquery.fileDownload.js"></script>
        <script src="js/users.js"></script>      
        <script src="js/jquery.tablesorter.min.js"></script>
        <link rel="stylesheet" href="https://code.jquery.com/ui/1.10.4/themes/smoothness/jquery-ui.css">
        <script src="https://code.jquery.com/ui/1.10.4/jquery-ui.js"></script>
        <script src="js/scroll.js"></script>

        <!-- Google Analytics: change UA-XXXXX-X to be your site's ID. -->
    </body>
</html>
 <?php if(isset($user_accounts->con)) $user_accounts->con = null;
       else if(isset($search->con)) $user_accounts->con = null; 
  ?>