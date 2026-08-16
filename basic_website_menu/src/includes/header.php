<!DOCTYPE html>
<!--
  Project: json-editor
  Description: A browser-based workspace for exploring, validating, and editing JSON files.
  Version: 3.1.0
  Last updated: 2026-08-16
  Author: LBETTERO
  Repository: https://github.com/lbettero/json-editor
-->
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title ?? 'JSON Explorer') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/main.css">
</head>
<body>
<header class="site-header">
    <a class="brand" href="/menu-manager.php" aria-label="JSON Explorer home">
        <span class="brand-mark">{ }</span>
        <span>JSON Explorer</span>
    </a>
    <span class="privacy-note">Processed locally</span>
</header>
