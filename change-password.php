<?php
// change-password.php
/*
 * OggiInLab
 * Copyright (c) 2025 Sergio Ferraro
 * Licensed under the MIT License
 */
session_start();
include('includes/config.php');
error_reporting(0);
if(strlen($_SESSION['alogin'])==0) {
    header('location:index.php');
} else {
    if(isset($_POST['change'])) {
        $current_password = $_POST['password'];
        $new_password = $_POST['newpassword'];
        $confirm_password = $_POST['confirmpassword'];
        $username = $_SESSION['alogin'];

        // Fetch stored hash from database
        $sql = "SELECT Password FROM admin WHERE userName = :username";
        $query = $dbh->prepare($sql);
        $query->bindParam(':username', $username, PDO::PARAM_STR);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_OBJ);

        if ($query->rowCount() > 0) {
            $stored_hash = $result->Password;
            // Verify current password
            if (password_verify($current_password, $stored_hash)) {
                // Check if new and confirm passwords match (JavaScript also handles this)
                if ($new_password === $confirm_password) {
                    // Hash new password
                    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    // Update password in database
                    $con = "UPDATE admin SET Password = :newpassword WHERE userName = :username";
                    $chngpwd1 = $dbh->prepare($con);
                    $chngpwd1->bindParam(':username', $username, PDO::PARAM_STR);
                    $chngpwd1->bindParam(':newpassword', $new_hash, PDO::PARAM_STR);
                    $chngpwd1->execute();
                    $msg = "Your password has been successfully changed";
                } else {
                    $error = "New Password and Confirm Password do not match!";
                }
            } else {
                $error = "Your current password is wrong";
            }
        } else {
            $error = "User not found"; // Unlikely, since user is logged in
        }
    }
}
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <!--[if IE]>
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <![endif]-->
    <title>OggiInLab | Cambio password</title>
    <!-- Dark theme Bootswatch Cyborg -->
    <link href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.0/dist/cyborg/bootstrap.min.css" rel="stylesheet">

    <!-- FONT AWESOME STYLE  -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- GOOGLE FONT -->
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
    <style>
        body {
            background-color: #1e1e1e;
            color: #f8f9fa;
        }
        .card {
            background-color: #2c2c2c !important;
            border-color: #444;
        }
        .btn-link.text-primary {
            color: #0d6efd !important;
        }
        .bg-dark {
        background-color: #1e1e1e !important;
        }
        .text-white {
            color: #f8f9fa !important;
        }
        .form-control.bg-dark {
            background-color: #1e1e1e;
            color: #f8f9fa;
            border-color: #444;
        }
        .btn.btn-primary {
        background-color: #0d6efd !important; 
        border-color: #0d6efd !important;
        color: white !important;
        }

        .btn.btn-primary:hover {
            background-color: #0a58ca !important; 
            border-color: #0a58ca !important;
            color: white !important;
        }
        .form-label {
        font-weight: bold;
        margin-bottom: 5px;
        }

       
        input[type="file"] {
            padding: 10px !important;
            border-radius: 4px;
        }
        input[type="file"] {
            background-color: #1e1e1e !important;
            color: #f8f9fa !important;
            border: 2px solid #444 !important;
            padding: 10px !important;
            border-radius: 4px !important;
        }
        .btn-link.text-primary {
            font-size: 1.2rem; 
            padding: 0;
        }

        
        form.d-inline.mt-2 {
            margin-top: 15px;
        }
        .form-control {
            background-color: #5c5e62 !important;
            color: white !important;
        }
        .form-select {
            background-color: #5c5e62 !important;
            color: white !important;
        }

    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<script type="text/javascript">
function valid()
{
if(document.chngpwd.newpassword.value!= document.chngpwd.confirmpassword.value)
{
alert("New Password and Confirm Password Field do not match  !!");
document.chngpwd.confirmpassword.focus();
return false;
}
return true;
}
</script>

<body>
    <!------MENU SECTION START-->
<?php include('includes/header.php');?>
<!-- MENU SECTION END-->
<div class="content-wrapper">
<div class="container">
<div class="row pad-botm">
<div class="col-md-12">
<h4 class="header-line">Password utente cambiata</h4>
</div>
</div>
 <?php if($error){?><div class="errorWrap"><strong>ERROR</strong>:<?php echo htmlentities($error); ?> </div><?php } 
        else if($msg){?><div class="succWrap"><strong>SUCCESS</strong>:<?php echo htmlentities($msg); ?> </div><?php }?>            
<!--LOGIN PANEL START-->           
<div class="row">
<div class="col-md-6 col-sm-6 col-xs-12 col-md-offset-3" >
<div class="panel panel-info">
<div class="panel-heading">
Cambia password
</div>
<div class="panel-body">
<form role="form" method="post" onSubmit="return valid();" name="chngpwd">

<div class="form-group">
<label>Password corrente</label>
<input class="form-control" type="password" name="password" autocomplete="off" required  />
</div>

<div class="form-group">
<label>Inserisci la password</label>
<input class="form-control" type="password" name="newpassword" autocomplete="off" required  />
</div>

<div class="form-group">
<label>Conferma la password </label>
<input class="form-control"  type="password" name="confirmpassword" autocomplete="off" required  />
</div>

 <button type="submit" name="change" class="btn btn-info">Cambia </button> 
</form>
 </div>
</div>
</div>
</div>  
<!---LOGIN PABNEL END-->            
             
 
    </div>
    </div>
     <!-- CONTENT-WRAPPER SECTION END-->
 <?php include('includes/footer.php');?>
      <!-- FOOTER SECTION END-->
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.11.6/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous"></script>
</body>
</html>

