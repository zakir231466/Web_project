<?php
// Set base path for assets
if (!isset($basePath)) {
    if (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false || strpos($_SERVER['REQUEST_URI'], '/shop-owner/') !== false || strpos($_SERVER['REQUEST_URI'], '/delivery/') !== false || strpos($_SERVER['REQUEST_URI'], '/customer/') !== false) {
        $basePath = '../';
    } else {
        $basePath = './';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="UIU Canteen - Food Delivery System">
    <title><?php echo isset($pageTitle) ? e($pageTitle) . ' - UIU Canteen' : 'UIU Canteen'; ?></title>
    <link rel="stylesheet" href="<?php echo $basePath; ?>assets/css/style.css">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Ctext y='75' font-size='75'%3E🍽️%3C/text%3E%3C/svg%3E">
</head>
<body>
