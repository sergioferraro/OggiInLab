<?php
/*
 * OggiInLab
 * Copyright (c) 2025 Sergio Ferraro
 * Licensed under the MIT License
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "includes/config.php";

if (empty($_SESSION["alogin"])) {
    header("location: index.php");
    exit();
}

$user = $_SESSION['id'];
$pdo = $GLOBALS['dbh'];

// New Post Form Handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['submit_post'])) {
        $content = htmlspecialchars(trim($_POST['content']));
        $image_url = null;

        // Upload image
        if (!empty($_FILES['image']['name'])) {
            $target_dir = "uploads/";
            $target_file = $target_dir . basename($_FILES["image"]["name"]);
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

            // Check file type (JPG, PNG, JPEG)
            if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg") {
                die("Solo immagini JPG, PNG o JPEG.");
            }

            // Resize and save the image
            $uploadOk = 1;
            $newFileName = uniqid() . "." . $imageFileType;
            $target_file = $target_dir . $newFileName;

            if (isset($_FILES["image"]["tmp_name"])) {
                list($width, $height) = getimagesize($_FILES["image"]["tmp_name"]);
                $width = (int)$width;
                $height = (int)$height;

                $maxWidth = 800;
                $maxHeight = 600;
                $ratio = min($maxWidth / $width, $maxHeight / $height);

                $newWidth = (int)($width * $ratio);
                $newHeight = (int)($height * $ratio);

                $src = null;
                if ($imageFileType == "jpg" || $imageFileType == "jpeg") {
                    $src = imagecreatefromjpeg($_FILES["image"]["tmp_name"]);
                } elseif ($imageFileType == "png") {
                    $src = imagecreatefrompng($_FILES["image"]["tmp_name"]);
                }

                if (!$src) {
                    die("Errore nel caricamento dell'immagine.");
                }

                $dst = imagecreatetruecolor($newWidth, $newHeight);
                imagecopyresampled(
                    $dst,
                    $src,
                    0, 0,
                    0, 0,
                    $newWidth, $newHeight,
                    $width, $height
                );

                if ($imageFileType == "jpg" || $imageFileType == "jpeg") {
                    imagejpeg($dst, $target_file);
                } elseif ($imageFileType == "png") {
                    imagepng($dst, $target_file);
                }

                imagedestroy($src);
                imagedestroy($dst);

                $image_url = $target_file;
            }
        }

        // Insert the post into the database
        $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, image_url) VALUES (?, ?, ?)");
        $stmt->execute([$user, $content, $image_url]);
    }  elseif (isset($_POST['delete_post'])) {
        $post_id = intval($_POST['post_id']);
    
        // Verify that the user is the owner of the post
        $stmt_check = $pdo->prepare("SELECT user_id FROM posts WHERE id = ?");
        $stmt_check->execute([$post_id]);
        $row = $stmt_check->fetch();
    
        if ($row && $row['user_id'] == $user) {
            // Delete comments associated with the post
            $stmt_delete_comments = $pdo->prepare("DELETE FROM comments WHERE post_id = ?");
            $stmt_delete_comments->execute([$post_id]);
            // Delete likes associated with the post
            $stmt_delete_likes = $pdo->prepare("DELETE FROM likes WHERE post_id = ?");
            $stmt_delete_likes->execute([$post_id]);
    
            // Delete the post
            $stmt_delete = $pdo->prepare("DELETE FROM posts WHERE id = ?");
            $stmt_delete->execute([$post_id]);
        }
    } elseif (isset($_POST['like_post'])) {
        $post_id = intval($_POST['post_id']);
        
        // Check if the user has already liked the post
        $stmt_check = $pdo->prepare("SELECT * FROM likes WHERE post_id = ? AND user_id = ?");
        $stmt_check->execute([$post_id, $user]);
        
        if ($stmt_check->rowCount() === 0) {
            // Insert like
            $stmt = $pdo->prepare("INSERT INTO likes (post_id, user_id) VALUES (?, ?)");
            $stmt->execute([$post_id, $user]);
        }
    } elseif (isset($_POST['comment_post'])) {
        $post_id = intval($_POST['post_id']);
        $content = htmlspecialchars(trim($_POST['comment']));
        // Insert comment
        $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
        $stmt->execute([$post_id, $user, $content]);
    }
}

// Retrieve all posts with the author's name
$stmt = $pdo->query("SELECT p.*, a.nomeCompleto FROM posts p JOIN admin a ON p.user_id = a.id ORDER BY created_at DESC");
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>OggiInLab | Bacheca</title>
    
    <!-- Dark theme with Bootswatch Cyborg -->
    <link href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.0/dist/cyborg/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css'>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
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
        background-color: #0d6efd !important; /* Light blue color */
        border-color: #0d6efd !important;
        color: white !important;
        }

        .btn.btn-primary:hover {
            background-color: #0a58ca !important; /* Darker blue on hover */
            border-color: #0a58ca !important;
            color: white !important;
        }
        .form-label {
        font-weight: bold;
        margin-bottom: 5px;
        }

        /* Style for file input */
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
            font-size: 1.2rem; /* Emoji size */
            padding: 0;
        }

        /* Distance between image and like button */
        form.d-inline.mt-2 {
            margin-top: 15px;
        }
        .comment-input {
            background-color: #5c5e62 !important;
            color: white !important;
        }

    </style>
</head>
<body>
<?php include "includes/header.php"; ?>
    <div class="container py-4">
        <!-- New Post Form -->
        <form method="post" enctype="multipart/form-data" class="mb-4 p-3 rounded bg-dark text-white">
            <div class="mb-3">
                <label for="content" class="form-label">Scrivi un messaggio:</label>
                <textarea name="content" id="content" rows="2" 
                        class="form-control bg-dark text-white border-primary"
                        style="border-width: 2px; padding: 10px;"></textarea>
            </div>

            <!-- Image label and input field -->
            <div class="mb-3">
                <label for="image" class="form-label">Carica un'immagine (max 500KB):</label>
                <input type="file" name="image" id="image" accept="image/*"
               class="bg-dark text-white" style="border-width: 2px; padding: 10px;">

            </div>

            <button type="submit" name="submit_post" class="btn btn-primary">Pubblica</button>
        </form>

        <!-- Post view -->
        <?php foreach ($posts as $post): ?>
    <div class="card mb-3 shadow-sm">
        <div class="card-body">
            <!-- Author name -->
            <h5 class="card-title"><?= htmlspecialchars($post['nomeCompleto']) ?></h5>
            <p><?= htmlspecialchars($post['content']) ?></p>

            <?php if (!empty($post['image_url'])): ?>
                <img src="<?= htmlspecialchars($post['image_url']) ?>" alt="Immagine" style="max-width: 100%; height: auto;">
            <?php endif; ?>

            <!-- Like -->
            <form method="post" class="d-inline mt-2">
                <?php
                $like_exists = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ? AND user_id = ?");
                $like_exists->execute([$post['id'], $user]);
                $has_liked = $like_exists->fetchColumn() > 0;
                ?>
                
                <?php if (!$has_liked): ?>
                    <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                    <button type="submit" name="like_post" class="btn btn-link text-primary p-0">
                        👍 <?= $pdo->query("SELECT COUNT(*) FROM likes WHERE post_id = " . $post['id'])->fetchColumn() ?>
                    </button>
                <?php else: ?>
                    <p>Già mi piace</p>
                <?php endif; ?>
            </form>

            <!-- Comments -->
            <div class="mt-2">
                <h6>Commenti:</h6>
                <?php
                // Retrieve comments with author's name
                $stmt = $pdo->prepare("SELECT c.*, a.nomeCompleto FROM comments c JOIN admin a ON c.user_id = a.id WHERE c.post_id = ?");
                $stmt->execute([$post['id']]);
                $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($comments as $comment): ?>
                    <div class="card bg-light mb-2 p-2">
                        <!-- Author name -->
                        <strong><?= htmlspecialchars($comment['nomeCompleto']) ?></strong><p><?= htmlspecialchars($comment['content']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Comment form -->
            <form method="post" class="mt-2">
                <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                <div class="input-group">
                <input type="text" name="comment" class="form-control comment-input" placeholder="Scrivi un commento..." required>
                    <button type="submit" name="comment_post" class="btn btn-secondary">Invia</button>
                </div>
            </form>

            <!-- "Delete" button only for the author -->
            <?php if ($post['user_id'] == $user): ?>
                <form method="post" class="mt-2">
                    <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                    <button type="submit" name="delete_post" class="btn btn-danger" onclick="return confirm('Sei sicuro di voler eliminare questo post?')">Elimina</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>

    </div>
    <?php include 'includes/footer.php';?>

<!-- SCRIPTS -->
    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.11.6/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" ></script>
</body>
</html>
