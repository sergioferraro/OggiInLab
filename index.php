<?php
// index.php
/*
 * OggiInLab
 * Copyright (c) 2025 Sergio Ferraro
 * Licensed under the MIT License
 */
session_start();
include('includes/config.php');
if(isset($_POST['login'])) {
    $username = $_POST['userName'];
    $password_input = $_POST['password'];
    // Fetch user by userName
    $sql = "SELECT id,userName, Password, nomeCompleto,is_super_admin FROM admin WHERE userName=:username";
    $query = $dbh->prepare($sql);
    $query->bindParam(':username', $username, PDO::PARAM_STR);
    $query->execute();

    if ($row = $query->fetch(PDO::FETCH_ASSOC)) {
        // Compare password using password_verify()
        if (password_verify($password_input, $row['Password'])) {
            // Granted
            $_SESSION['alogin'] = $username;
            $_SESSION['nomeCompleto'] = $row['nomeCompleto'];
            $_SESSION['id'] = $row['id'];
            $_SESSION['is_super_admin'] = $row['is_super_admin'];
            header("Location: dashboard.php");
            exit();
        } else {
            echo "<script>alert('Password non valida');</script>";
        }
    } else {
        echo "<script>alert('Username non trovato');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="it" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>OggiInLab | Indice</title>
    <!-- Dark theme Bootswatch Cyborg -->
    <link href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.0/dist/cyborg/bootstrap.min.css" rel="stylesheet">
    <!-- FONT AWESOME STYLE  -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- GOOGLE FONT -->
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
    <link href='assets/css/custom.css' rel='stylesheet' type='text/css' />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <!------MENU SECTION START-->
<?php include('includes/header.php');?>
<!-- MENU SECTION END-->
<div class="content-wrapper">
<div class="container">
<div class="row pad-botm">
<div class="col-md-12">
<h4 class="header-line">FORM LOGIN AMMINISTRATORE</h4>
</div>
</div>
             
<!--LOGIN PANEL START-->           
<div class="row">
<div class="col-md-6 col-sm-6 col-xs-12 col-md-offset-3" >
<div class="panel panel-info">
<div class="panel-heading">
 FORM DI LOGIN
</div>
<div class="panel-body">
<form role="form" method="post">

<div class="form-group">
<label>Inserire nome utente</label>
<input class="form-control" type="text" name="userName" required />
</div>
<div class="form-group">
<label>Password</label>
<input class="form-control" type="password" name="password" required />
</div>
 <button type="submit" name="login" class="btn btn-info">LOGIN </button>
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
