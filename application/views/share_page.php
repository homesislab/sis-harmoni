<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= html_escape($title) ?> | SIS Paguyuban</title>
    <meta name="description" content="<?= html_escape($description) ?>">
    <meta property="og:type" content="article">
    <meta property="og:site_name" content="SIS Paguyuban">
    <meta property="og:title" content="<?= html_escape($title) ?>">
    <meta property="og:description" content="<?= html_escape($description) ?>">
    <meta property="og:url" content="<?= html_escape($meta_url) ?>">
    <meta property="og:image" content="<?= html_escape($image) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= html_escape($title) ?>">
    <meta name="twitter:description" content="<?= html_escape($description) ?>">
    <meta name="twitter:image" content="<?= html_escape($image) ?>">
    <style>
        body { font-family: Arial, sans-serif; background:#f9fafb; color:#111827; margin:0; }
        .wrap { max-width:680px; margin:0 auto; padding:40px 20px; }
        .card { background:#fff; border-radius:20px; box-shadow:0 6px 20px rgba(15,23,42,.06); overflow:hidden; }
        .hero img { width:100%; aspect-ratio:16/10; object-fit:cover; display:block; background:#e5e7eb; }
        .content { padding:24px; }
        .eyebrow { font-size:12px; color:#059669; font-weight:700; letter-spacing:.04em; text-transform:uppercase; }
        h1 { margin:10px 0 0; font-size:28px; line-height:1.2; }
        .meta { margin-top:14px; color:#64748b; font-size:14px; }
        .meta div + div { margin-top:6px; }
        .body { margin-top:20px; color:#475569; line-height:1.7; white-space:pre-line; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <div class="hero">
                <img src="<?= html_escape($image) ?>" alt="<?= html_escape($title) ?>">
            </div>
            <div class="content">
                <div class="eyebrow"><?= html_escape($eyebrow ?? 'SIS Paguyuban') ?></div>
                <h1><?= html_escape($title) ?></h1>
                <?php if (!empty($meta_lines)): ?>
                    <div class="meta">
                        <?php foreach ($meta_lines as $line): ?>
                            <div><?= html_escape($line) ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($body)): ?>
                    <div class="body"><?= html_escape($body) ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
