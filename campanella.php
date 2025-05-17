<?php
// campanella.php
/*
  Oggi inLab
  Copyright (c) 2025 Sergio Ferraro
  Licensed under the MIT License
*/
session_start();
include('includes/config.php');
error_reporting(0);

if(strlen($_SESSION['alogin'])==0) {
    header('location:index.php');
} else {
    // Update fascia
    if(isset($_POST['update'])) {
        $id = $_POST['id'];
        $inizio = $_POST['inizio'] . ':00';
        $fine = $_POST['fine'] . ':00';

        $sql = "UPDATE fasce SET inizio = :inizio, fine = :fine WHERE id = :id";
        $query = $dbh->prepare($sql);
        $query->bindParam(':inizio', $inizio, PDO::PARAM_STR);
        $query->bindParam(':fine', $fine, PDO::PARAM_STR);
        $query->bindParam(':id', $id, PDO::PARAM_INT);
        $query->execute();

        $msg = "Fascia aggiornata correttamente";
    }

    // Reading
    $sql = "SELECT * FROM fasce ORDER BY id";
    $query = $dbh->prepare($sql);
    $query->execute();
    $fasce = $query->fetchAll(PDO::FETCH_OBJ);
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>OggiInLab | Campanella</title>
    <link href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.0/dist/cyborg/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #1e1e1e;
            color: #f8f9fa;
        }
        .card {
            background-color: #2c2c2c !important;
            border-color: #444;
        }
        .form-control {
            background-color: #5c5e62 !important;
            color: white !important;
        }
        .btn-primary {
            background-color: #0d6efd !important;
            border-color: #0d6efd !important;
        }
        .btn-primary:hover {
            background-color: #0a58ca !important;
            border-color: #0a58ca !important;
        }
    </style>
</head>
<body>

<?php include('includes/header.php');?>

<div class="content-wrapper">
<div class="container">
<div class="row pad-botm">
    <div class="col-md-12">
        <h4 class="header-line">Gestione Fasce Orarie</h4>
    </div>
</div>

<?php if($msg){?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <strong>Successo!</strong> <?php echo htmlentities($msg); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php } ?>

<div class="card p-3">
    <h5 class="card-title">Fasce orarie attive</h5>
    <div class="table-responsive">
        <table class="table table-hover table-bordered text-white">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Inizio</th>
                    <th>Fine</th>
                    <th>Modifica</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($fasce as $fascia) { ?>
                <tr>
                    <form method="post">
                        <td><?php echo htmlentities($fascia->id); ?></td>
                        <td>
                            <input type="time" name="inizio" class="form-control" value="<?php echo substr($fascia->inizio,0,5); ?>" required>
                        </td>
                        <td>
                            <input type="time" name="fine" class="form-control" value="<?php echo substr($fascia->fine,0,5); ?>" required>
                        </td>
                        <td>
                            <input type="hidden" name="id" value="<?php echo $fascia->id; ?>">
                            <button type="submit" name="update" class="btn btn-primary btn-sm"><i class="fa fa-save"></i> Salva</button>
                        </td>
                    </form>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>

</div>
</div>

<?php include('includes/footer.php');?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.11.6/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
</body>
</html>
