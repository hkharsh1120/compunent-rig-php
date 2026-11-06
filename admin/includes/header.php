
<?php

$adminBase = '/compunent-rig/admin';
$adminTitle = $pageTitle ?? 'Admin Dashboard';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($adminTitle) ?> | Compunent Rig Admin</title>
    <link rel="stylesheet" href="/compunent-rig/assets/css/style.css?v=4">
</head>
<body class="admin-body">

<div class="admin-wrapper">

    <?php require __DIR__ . '/sidebar.php'; ?>

    <div class="admin-content">
