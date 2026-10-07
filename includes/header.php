<?php
/**
 * Expects optional $pageTitle to be set before include.
 * Expects $assetPrefix to be '' for root pages or '../' for pages inside /admin.
 */
$assetPrefix = $assetPrefix ?? '';
$pageTitle = $pageTitle ?? 'Chemical Connect';
$cssFile = __DIR__ . '/../assets/css/style.css';
$cssVersion = file_exists($cssFile) ? filemtime($cssFile) : time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo e($pageTitle); ?></title>
<link rel="stylesheet" href="<?php echo $assetPrefix; ?>assets/css/style.css?v=<?php echo $cssVersion; ?>">
<link rel="icon" href="data:,">
</head>
<body class="<?php echo e($bodyClass ?? ''); ?>">
