<!doctype html>
<?php
if (!function_exists('__share_plain_text')) {
    function __share_plain_text($value, bool $preserveBreaks = false): string
    {
        $text = html_entity_decode((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $break = $preserveBreaks ? "\n" : ' ';
        $text = preg_replace('/<br\s*\/?>/i', $break, $text);
        $text = preg_replace('/<\/(p|div|h[1-6]|li)>/i', $break, $text);
        $text = preg_replace('/<li\b[^>]*>/i', $preserveBreaks ? "- " : ' ', $text);
        $text = strip_tags($text);
        if ($preserveBreaks) {
            $text = preg_replace("/[ \t]+/u", ' ', $text);
            $text = preg_replace("/\n{3,}/u", "\n\n", $text);
            return trim($text);
        }
        return trim(preg_replace('/\s+/u', ' ', $text));
    }
}

$__meta_description = __share_plain_text($meta_description ?? $description ?? '', false);
if ($__meta_description === '') {
    $__meta_description = 'Info terbaru tersedia di SIS Harmoni.';
}
if (function_exists('mb_strlen') && mb_strlen($__meta_description, 'UTF-8') > 180) {
    $__meta_description = rtrim(mb_substr($__meta_description, 0, 177, 'UTF-8')) . '...';
} elseif (!function_exists('mb_strlen') && strlen($__meta_description) > 180) {
    $__meta_description = rtrim(substr($__meta_description, 0, 177)) . '...';
}

$__body_text = __share_plain_text($body ?? '', true);
?>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= html_escape($title) ?> | SIS Harmoni</title>
    <meta name="description" content="<?= html_escape($__meta_description) ?>">
    <meta property="og:type" content="article">
    <meta property="og:site_name" content="SIS Harmoni">
    <meta property="og:title" content="<?= html_escape($title) ?>">
    <meta property="og:description" content="<?= html_escape($__meta_description) ?>">
    <meta property="og:url" content="<?= html_escape($meta_url) ?>">
    <meta property="og:image" content="<?= html_escape($image) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= html_escape($title) ?>">
    <meta name="twitter:description" content="<?= html_escape($__meta_description) ?>">
    <meta name="twitter:image" content="<?= html_escape($image) ?>">
    <?php if (!empty($app_redirect_url)): ?>
        <link rel="canonical" href="<?= html_escape($app_redirect_url) ?>">
    <?php endif; ?>
    <style>
        :root {
            color-scheme: light;
            --bg: #f8fafc;
            --card: #ffffff;
            --line: rgba(15, 23, 42, 0.08);
            --text: #0f172a;
            --muted: #64748b;
            --brand: #059669;
            --brand-soft: #d1fae5;
            --shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, sans-serif;
            background:
                radial-gradient(circle at top right, rgba(16, 185, 129, 0.10), transparent 28%),
                var(--bg);
            color: var(--text);
        }
        .wrap { max-width: 720px; margin: 0 auto; padding: 32px 20px 40px; }
        .card {
            overflow: hidden;
            border-radius: 24px;
            background: var(--card);
            border: 1px solid var(--line);
            box-shadow: var(--shadow);
        }
        .hero { position: relative; }
        .hero img {
            width: 100%;
            aspect-ratio: 16 / 10;
            object-fit: cover;
            display: block;
            background: #e2e8f0;
        }
        .hero::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(15, 23, 42, 0.36), rgba(15, 23, 42, 0.08), transparent);
        }
        .content { padding: 24px; }
        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: 999px;
            background: var(--brand-soft);
            color: #065f46;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
        }
        h1 { margin: 14px 0 0; font-size: 28px; line-height: 1.2; }
        .meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 14px;
            color: var(--muted);
            font-size: 13px;
        }
        .meta-chip {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            background: #f8fafc;
            border: 1px solid rgba(148, 163, 184, 0.18);
            padding: 7px 10px;
        }
        .body {
            margin-top: 18px;
            color: #475569;
            font-size: 14px;
            line-height: 1.7;
            white-space: pre-line;
        }
        .cta {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
            margin-top: 22px;
        }
        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            padding: 0 16px;
            border-radius: 14px;
            text-decoration: none;
            font-weight: 700;
            font-size: 14px;
        }
        .button-primary {
            background: var(--brand);
            color: #fff;
            box-shadow: 0 8px 16px rgba(5, 150, 105, 0.18);
        }
        .button-secondary {
            border: 1px solid rgba(148, 163, 184, 0.24);
            color: var(--muted);
            background: #fff;
        }
        .helper {
            margin-top: 10px;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.6;
        }
    </style>
    <?php if (!empty($app_redirect_url)): ?>
        <script>
            window.addEventListener('load', function () {
                window.setTimeout(function () {
                    window.location.replace(<?= json_encode($app_redirect_url) ?>);
                }, 120);
            });
        </script>
    <?php endif; ?>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <div class="hero">
                <img src="<?= html_escape($image) ?>" alt="<?= html_escape($title) ?>">
            </div>
            <div class="content">
                <div class="eyebrow"><?= html_escape($eyebrow ?? 'SIS Harmoni') ?></div>
                <h1><?= html_escape($title) ?></h1>
                <?php if (!empty($meta_lines)): ?>
                    <div class="meta">
                        <?php foreach ($meta_lines as $line): ?>
                            <div class="meta-chip"><?= html_escape($line) ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if ($__body_text !== ''): ?>
                    <div class="body"><?= html_escape($__body_text) ?></div>
                <?php endif; ?>
                <div class="cta">
                    <?php if (!empty($app_redirect_url)): ?>
                        <a class="button button-primary" href="<?= html_escape($app_redirect_url) ?>">Buka di aplikasi</a>
                    <?php endif; ?>
                    <a class="button button-secondary" href="<?= html_escape($meta_url) ?>">Refresh halaman</a>
                </div>
                <div class="helper">
                    <?= !empty($app_redirect_url)
                        ? 'Halaman ini sedang meneruskan Anda ke tampilan aplikasi yang lebih lengkap.'
                        : 'Link ini sudah siap dibagikan dan juga aman dibuka langsung dari browser.' ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
