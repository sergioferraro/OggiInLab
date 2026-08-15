<?php
// change-password.php
/*
 * OggiInLab
 * Copyright (c) 2026 Sergio Ferraro
 * Licensed under the MIT License
 */
require_once __DIR__ . '/includes/session.php';
include('includes/config.php');
error_reporting(0);
if(strlen($_SESSION['alogin'])==0) {
    header('location:index.php');
} else {
    if(isset($_POST['change'])) {
        // --- CSRF validation ---
        if (empty($_POST['_token']) || !is_string($_POST['_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['_token'])) {
            $error = "Token di sicurezza non valido. Riprova.";
        } else {
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
        } // fine CSRF else
    }
}

$pageTitle = 'OggiInLab | Cambio password';
$pageHeadScripts = '<script type="text/javascript">
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
</script>';
?>
<?php include('includes/header.php');?>
<div class="content-wrapper">
<div class="container">
<div class="row pad-botm">
<div class="col-md-12">
<h4 class="header-line">Gestione password</h4>
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
<input type="hidden" name="_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>" />

<div class="form-group">
<label>Password corrente</label>
<input class="form-control" type="password" name="password" autocomplete="current-password" required  />
</div>

<div class="form-group">
<label>Inserisci la password</label>
<input class="form-control" type="password" name="newpassword" autocomplete="new-password" required  />
</div>

<div class="form-group">
<label>Conferma la password </label>
<input class="form-control"  type="password" name="confirmpassword" autocomplete="new-password" required  />
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

