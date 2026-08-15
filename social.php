<?php
/*
 * OggiInLab
 * Copyright (c) 2026 Sergio Ferraro
 * Licensed under the MIT License
 */

require_once __DIR__ . '/includes/session.php';
error_reporting(E_ALL);
ini_set('display_errors', defined('APP_DEBUG') && APP_DEBUG ? '1' : '0');

include "includes/config.php";

if (empty($_SESSION["alogin"])) {
    header("location: index.php");
    exit();
}

$user = $_SESSION['id'];
$pdo = $GLOBALS['dbh'];

// 1. Initialize errors array
$errors = [];

// New Post Form Handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 2. CSRF validation for all POST handlers
    $postedToken = $_POST['_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $postedToken)) {
        $errors[] = "Token di sicurezza non valido. Riprova.";
    } else {
        if (isset($_POST['submit_post'])) {
            $content = trim($_POST['content']);
            $image_url = null;

            // Upload image
            if (!empty($_FILES['image']['name'])) {
                $target_dir = "uploads/";
                $target_file = $target_dir . basename($_FILES["image"]["name"]);
                $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

                // 3a. Validate MIME type (not just extension)
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime_type = finfo_file($finfo, $_FILES["image"]["tmp_name"]);
                finfo_close($finfo);

                $allowed_mimes = ['image/jpeg', 'image/png'];
                if (!in_array($mime_type, $allowed_mimes)) {
                    $errors[] = "Solo immagini JPG, PNG o JPEG.";
                }
                // 3b. Validate file size (max 500KB)
                elseif ($_FILES["image"]["size"] > 500 * 1024) {
                    $errors[] = "L'immagine supera le 500KB.";
                } else {
                    // Resize and save the image
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
                        if ($mime_type === 'image/jpeg') {
                            $src = imagecreatefromjpeg($_FILES["image"]["tmp_name"]);
                        } elseif ($mime_type === 'image/png') {
                            $src = imagecreatefrompng($_FILES["image"]["tmp_name"]);
                        }

                        // 3c. Use errors array instead of die()
                        if (!$src) {
                            $errors[] = "Errore nel caricamento dell'immagine.";
                        } else {
                            $dst = imagecreatetruecolor($newWidth, $newHeight);
                            imagecopyresampled(
                                $dst,
                                $src,
                                0, 0,
                                0, 0,
                                $newWidth, $newHeight,
                                $width, $height
                            );

                            if ($mime_type === 'image/jpeg') {
                                imagejpeg($dst, $target_file);
                            } elseif ($mime_type === 'image/png') {
                                imagepng($dst, $target_file);
                            }

                            imagedestroy($src);
                            imagedestroy($dst);

                            $image_url = $target_file;
                        }
                    }
                }
            }

            // 3d. Insert only if no errors occurred
            if (empty($errors)) {
                // 3e. Store raw content (no htmlspecialchars here — escape on output only to avoid double-encoding)
                $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, image_url) VALUES (?, ?, ?)");
                $stmt->execute([$user, $content, $image_url]);
            }
        } elseif (isset($_POST['delete_post'])) {
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
            // 4. Store raw comment content (escape on output only to avoid double-encoding)
            $content = trim($_POST['comment']);
            // Insert comment
            $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
            $stmt->execute([$post_id, $user, $content]);
        }
    }
}

// Retrieve all posts with the author's name
$stmt = $pdo->query("SELECT p.*, a.nomeCompleto FROM posts p JOIN admin a ON p.user_id = a.id ORDER BY created_at DESC");
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 5. Pre-fetch all likes in a single query (avoid N+1)
$post_ids = array_column($posts, 'id');
$like_map = [];
$has_liked_map = [];
if (!empty($post_ids)) {
    $placeholders = implode(',', array_fill(0, count($post_ids), '?'));
    $stmt_likes = $pdo->prepare("SELECT post_id, COUNT(*) as cnt FROM likes WHERE post_id IN ($placeholders) GROUP BY post_id");
    $stmt_likes->execute($post_ids);
    foreach ($stmt_likes->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $like_map[$row['post_id']] = $row['cnt'];
    }

    // Also pre-fetch which posts the current user has liked
    $stmt_user_likes = $pdo->prepare("SELECT post_id FROM likes WHERE user_id = ? AND post_id IN ($placeholders)");
    $stmt_user_likes->execute(array_merge([$user], $post_ids));
    foreach ($stmt_user_likes->fetchAll(PDO::FETCH_COLUMN) as $pid) {
        $has_liked_map[$pid] = true;
    }
}

$pageTitle = 'OggiInLab | Bacheca';
?>
<?php include "includes/header.php"; ?>
    <div class="container py-4">

        <!-- Error messages -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php foreach ($errors as $err): ?>
                    <div><?= htmlspecialchars($err) ?></div>
                <?php endforeach; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- New Post Form -->
        <form method="post" enctype="multipart/form-data" class="mb-4 p-3 rounded bg-dark text-white">
            <!-- 7. CSRF token -->
            <input type="hidden" name="_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
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
                <!-- 7. CSRF token -->
                <input type="hidden" name="_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <?php
                // 5. Use pre-fetched data instead of individual queries
                $has_liked = isset($has_liked_map[$post['id']]);
                // 6. Use pre-fetched like count (safe, no SQL injection)
                $like_count = $like_map[$post['id']] ?? 0;
                ?>
                
                <?php if (!$has_liked): ?>
                    <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                    <button type="submit" name="like_post" class="btn btn-link text-primary p-0">
                        👍 <?= $like_count ?>
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
                <!-- 7. CSRF token -->
                <input type="hidden" name="_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                <div class="input-group">
                <input type="text" name="comment" class="form-control comment-input" placeholder="Scrivi un commento..." required>
                    <button type="submit" name="comment_post" class="btn btn-secondary">Invia</button>
                </div>
            </form>

            <!-- "Delete" button only for the author -->
            <?php if ($post['user_id'] == $user): ?>
                <form method="post" class="mt-2">
                    <!-- 7. CSRF token -->
                    <input type="hidden" name="_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                    <button type="submit" name="delete_post" class="btn btn-danger" onclick="return confirm('Sei sicuro di voler eliminare questo post?')">Elimina</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>

    </div>
    <?php include 'includes/footer.php';?>
