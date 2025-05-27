<?php
include "./auth_check.php"; // Check if user is logged in

$uploadDir = '../uploads/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$response = ['success' => false];

if (isset($_FILES['image'])) {
    $image = $_FILES['image'];
    $ext = pathinfo($image['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $ext;
    $targetPath = $uploadDir . $filename;

    if (move_uploaded_file($image['tmp_name'], $targetPath)) {
        $response['success'] = true;
        $response['url'] = $targetPath;
    }
}

header('Content-Type: application/json');
echo json_encode($response);

?>