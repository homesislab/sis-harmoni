<!doctype html>
<?php
$summary = $summary ?? [];
$items = $items ?? [];
$filters = $filters ?? ['status' => 'all', 'q' => ''];
$queryBase = [];
if (!empty($_GET['key'])) {
    $queryBase['key'] = (string)$_GET['key'];
}
$exportQuery = array_merge($queryBase, [
    'status' => $filters['status'] ?? 'all',
    'q' => $filters['q'] ?? '',
]);
$allBlastRows = array_values(array_filter($items, fn ($row) => empty($row['is_registered']) && !empty($row['whatsapp_number'])));
?>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Data Offline Warga | SIS Harmoni</title>
    <style>
        :root {
            --bg: #f7f9fb;
            --panel: #ffffff;
            --text: #17202a;
            --muted: #667085;
            --line: #d8dee8;
            --green: #0f8f6d;
            --green-soft: #e4f6ef;
            --red: #c2413a;
            --red-soft: #fde9e7;
            --yellow: #a15c00;
            --yellow-soft: #fff1d6;
            --ink: #26364a;
            --shadow: 0 14px 34px rgba(25, 36, 55, 0.08);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: var(--bg);
            color: var(--text);
        }
        a { color: inherit; }
        .wrap { width: min(1180px, calc(100% - 32px)); margin: 0 auto; padding: 28px 0 48px; }
        .topbar {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 22px;
        }
        .eyebrow {
            margin: 0 0 8px;
            color: var(--green);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
        }
        h1 { margin: 0; font-size: clamp(28px, 5vw, 44px); line-height: 1.05; letter-spacing: 0; }
        .lead { max-width: 720px; margin: 12px 0 0; color: var(--muted); font-size: 15px; line-height: 1.7; }
        .button, button {
            border: 0;
            min-height: 40px;
            border-radius: 8px;
            padding: 0 14px;
            font-size: 14px;
            font-weight: 800;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            white-space: nowrap;
        }
        .button-primary { color: #fff; background: var(--green); }
        .button-secondary { color: var(--ink); background: #fff; border: 1px solid var(--line); }
        .button-danger { color: var(--red); background: var(--red-soft); }
        .grid { display: grid; gap: 14px; }
        .stats { grid-template-columns: repeat(4, minmax(0, 1fr)); margin-bottom: 14px; }
        .stat {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 16px;
            box-shadow: var(--shadow);
        }
        .stat span { display: block; color: var(--muted); font-size: 13px; line-height: 1.35; }
        .stat strong { display: block; margin-top: 8px; font-size: 28px; line-height: 1; }
        .progress {
            height: 12px;
            border-radius: 8px;
            background: #e9edf3;
            overflow: hidden;
            border: 1px solid var(--line);
            margin: 6px 0 20px;
        }
        .progress div { height: 100%; background: var(--green); width: <?= (float)($summary['registered_percent'] ?? 0) ?>%; }
        .panel {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 8px;
            box-shadow: var(--shadow);
        }
        .tools { padding: 16px; margin-bottom: 14px; }
        .tools form {
            display: grid;
            grid-template-columns: minmax(180px, 1fr) 170px auto auto;
            gap: 10px;
            align-items: end;
        }
        label { display: grid; gap: 6px; color: var(--muted); font-size: 12px; font-weight: 800; }
        input, select, textarea {
            width: 100%;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: #fff;
            color: var(--text);
            font: inherit;
            outline: none;
        }
        input, select { min-height: 40px; padding: 0 12px; }
        textarea {
            min-height: 320px;
            padding: 12px;
            line-height: 1.55;
            resize: vertical;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace;
            font-size: 13px;
        }
        input:focus, select:focus, textarea:focus { border-color: var(--green); box-shadow: 0 0 0 3px rgba(15, 143, 109, 0.14); }
        .blast {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 280px;
            gap: 14px;
            margin-bottom: 14px;
        }
        .blast .panel { padding: 16px; }
        .blast h2, .list h2 { margin: 0 0 8px; font-size: 18px; }
        .hint { color: var(--muted); font-size: 13px; line-height: 1.6; margin: 0; }
        .format-toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin: 12px 0;
        }
        .format-toolbar button {
            min-height: 34px;
            border: 1px solid var(--line);
            background: #fff;
            color: var(--ink);
            font-size: 12px;
            padding: 0 10px;
        }
        .placeholder {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 12px;
        }
        .chip {
            border-radius: 999px;
            border: 1px solid var(--line);
            padding: 6px 10px;
            color: var(--ink);
            background: #f9fafb;
            font-size: 12px;
            font-weight: 800;
        }
        .blast-actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 12px; }
        .send-status {
            margin-top: 12px;
            min-height: 20px;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.5;
        }
        .send-status.ok { color: var(--green); font-weight: 800; }
        .send-status.fail { color: var(--red); font-weight: 800; }
        .wa-preview {
            margin-top: 14px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: #f6fbf8;
            padding: 14px;
        }
        .wa-preview-title {
            margin: 0 0 10px;
            color: var(--muted);
            font-size: 12px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        .wa-bubble {
            max-height: 360px;
            overflow: auto;
            border-radius: 8px;
            background: #fff;
            border: 1px solid rgba(15, 143, 109, 0.18);
            padding: 13px;
            color: #17202a;
            font-size: 14px;
            line-height: 1.6;
            white-space: pre-wrap;
        }
        .blast-side {
            display: grid;
            align-content: start;
            gap: 10px;
        }
        .queue-count {
            font-size: 36px;
            font-weight: 900;
            line-height: 1;
            color: var(--red);
        }
        .list { overflow: hidden; }
        .list-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 16px;
            border-bottom: 1px solid var(--line);
        }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 980px; }
        th, td {
            padding: 13px 14px;
            border-bottom: 1px solid var(--line);
            text-align: left;
            vertical-align: top;
            font-size: 13px;
        }
        th {
            color: var(--muted);
            background: #f4f6f8;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        tr.is-contacted td { background: #f3fbf7; }
        .unit { font-weight: 900; font-size: 15px; }
        .muted { color: var(--muted); }
        .name { font-weight: 800; }
        .badge {
            display: inline-flex;
            align-items: center;
            min-height: 26px;
            border-radius: 999px;
            padding: 0 10px;
            font-size: 12px;
            font-weight: 900;
        }
        .badge-ok { background: var(--green-soft); color: var(--green); }
        .badge-miss { background: var(--red-soft); color: var(--red); }
        .badge-warn { background: var(--yellow-soft); color: var(--yellow); }
        .row-actions { display: flex; flex-wrap: wrap; gap: 8px; }
        .mini {
            min-height: 32px;
            padding: 0 10px;
            font-size: 12px;
        }
        .empty { padding: 34px 16px; text-align: center; color: var(--muted); }
        .footer-note { margin-top: 14px; color: var(--muted); font-size: 12px; line-height: 1.6; }
        @media (max-width: 860px) {
            .topbar, .blast, .list-head { display: block; }
            .stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .tools form { grid-template-columns: 1fr; }
            .topbar .button { margin-top: 14px; }
            .blast-side { margin-top: 14px; }
        }
        @media (max-width: 520px) {
            .wrap { width: min(100% - 20px, 1180px); padding-top: 18px; }
            .stats { grid-template-columns: 1fr; }
            .button, button { width: 100%; }
            .blast-actions, .row-actions { display: grid; }
        }
    </style>
</head>
<body>
    <main class="wrap">
        <section class="topbar">
            <div>
                <p class="eyebrow">System admin</p>
                <h1>Data offline warga</h1>
                <p class="lead">
                    Pantau data warga dari sumber offline seperti data pemilu dan rekap manual, cocokkan dengan kepala keluarga di database, lalu kirim WhatsApp untuk warga yang belum punya akun SIS Harmoni.
                </p>
            </div>
            <a class="button button-secondary" href="<?= html_escape($export_url . '?' . http_build_query($exportQuery)) ?>">Export CSV</a>
        </section>

        <section class="grid stats" aria-label="Ringkasan">
            <div class="stat"><span>Total data CSV</span><strong><?= (int)($summary['total'] ?? 0) ?></strong></div>
            <div class="stat"><span>Sudah daftar</span><strong><?= (int)($summary['registered'] ?? 0) ?> <small class="muted"><?= html_escape((string)($summary['registered_percent'] ?? 0)) ?>%</small></strong></div>
            <div class="stat"><span>Belum daftar</span><strong><?= (int)($summary['unregistered'] ?? 0) ?> <small class="muted"><?= html_escape((string)($summary['unregistered_percent'] ?? 0)) ?>%</small></strong></div>
            <div class="stat"><span>Unit belum ketemu</span><strong><?= (int)($summary['not_found'] ?? 0) ?></strong></div>
        </section>
        <div class="progress" title="Persentase sudah daftar"><div></div></div>

        <section class="panel tools">
            <form method="get" action="<?= html_escape($page_url) ?>">
                <?php if (!empty($_GET['key'])): ?>
                    <input type="hidden" name="key" value="<?= html_escape((string)$_GET['key']) ?>">
                <?php endif; ?>
                <label>
                    Cari unit, nama, atau nomor WA
                    <input type="search" name="q" value="<?= html_escape((string)($filters['q'] ?? '')) ?>" placeholder="Contoh: A-22, Ubaid, 62817">
                </label>
                <label>
                    Status
                    <select name="status">
                        <?php foreach (['all' => 'Semua', 'unregistered' => 'Belum daftar', 'registered' => 'Sudah daftar', 'not_found' => 'Unit belum ketemu'] as $value => $label): ?>
                            <option value="<?= $value ?>" <?= (($filters['status'] ?? 'all') === $value) ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button class="button-primary" type="submit">Terapkan</button>
                <a class="button button-secondary" href="<?= html_escape($page_url . (!empty($queryBase) ? '?' . http_build_query($queryBase) : '')) ?>">Reset</a>
            </form>
        </section>

        <section class="blast">
            <div class="panel">
                <h2>Template WhatsApp</h2>
                <p class="hint">WhatsApp memakai format teks: *tebal*, _miring_, ~coret~, dan paragraf dipisah baris kosong. Placeholder akan diganti otomatis sebelum dikirim.</p>
                <div class="format-toolbar" aria-label="Toolbar format WhatsApp">
                    <button type="button" data-format="bold">*Tebal*</button>
                    <button type="button" data-format="italic">_Miring_</button>
                    <button type="button" data-format="strike">~Coret~</button>
                    <button type="button" data-format="bullet">Bullet</button>
                    <button type="button" data-format="quote">Quote</button>
                    <button type="button" id="useDefaultTemplate">Pakai template aktivasi</button>
                </div>
                <textarea id="waTemplate"><?= html_escape($template ?? '') ?></textarea>
                <div class="placeholder">
                    <span class="chip">{nama}</span>
                    <span class="chip">{unit}</span>
                    <span class="chip">{unit_db}</span>
                    <span class="chip">{suami}</span>
                    <span class="chip">{istri}</span>
                    <span class="chip">{kk}</span>
                </div>
                <div class="blast-actions">
                    <button class="button-secondary" type="button" id="saveTemplate">Simpan template</button>
                    <button class="button-secondary" type="button" id="resetTemplate">Reset template</button>
                    <button class="button-primary" type="button" id="sendAllWa">Kirim semua via wabot</button>
                </div>
                <div class="send-status" id="sendStatus">Wabot akan mengirim ke nomor yang belum terdaftar dari hasil filter saat ini.</div>
                <div class="wa-preview">
                    <p class="wa-preview-title">Preview pesan</p>
                    <div class="wa-bubble" id="waPreview"></div>
                </div>
            </div>
            <div class="panel blast-side">
                <div>
                    <p class="hint">Siap dikirimi WA dari hasil filter saat ini</p>
                    <div class="queue-count"><?= count($allBlastRows) ?></div>
                </div>
                <p class="hint">Gunakan bertahap kalau browser menahan popup. Tombol per baris selalu aman untuk kontrol manual.</p>
                <button class="button-danger" type="button" id="clearContacted">Hapus tanda dihubungi</button>
            </div>
        </section>

        <section class="panel list">
            <div class="list-head">
                <div>
                    <h2>Daftar unit</h2>
                    <p class="hint"><?= count($items) ?> baris tampil dari CSV. Data dibuat pada <?= html_escape((string)($generated_at ?? '')) ?>.</p>
                </div>
                <span class="chip"><?= html_escape((string)($csv_path ?? '')) ?></span>
            </div>
            <?php if (empty($items)): ?>
                <div class="empty">Tidak ada data untuk filter ini.</div>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Unit</th>
                                <th>Data CSV</th>
                                <th>Database</th>
                                <th>Status</th>
                                <th>WhatsApp</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $row): ?>
                                <?php $rowKey = 'offline-resident-contacted-' . ($row['unit_key'] ?? ''); ?>
                                <tr data-row-key="<?= html_escape($rowKey) ?>">
                                    <td>
                                        <div class="unit"><?= html_escape($row['unit_code']) ?></div>
                                        <div class="muted">No CSV <?= html_escape($row['no']) ?></div>
                                    </td>
                                    <td>
                                        <div class="name"><?= html_escape($row['csv_display_name']) ?></div>
                                        <div class="muted">Suami: <?= html_escape($row['csv_suami'] ?: '-') ?></div>
                                        <div class="muted">Istri: <?= html_escape($row['csv_istri'] ?: '-') ?></div>
                                    </td>
                                    <td>
                                        <div class="name"><?= html_escape($row['db_head_name'] ?: 'Belum ada kepala keluarga aktif') ?></div>
                                        <div class="muted">KK: <?= html_escape($row['db_kk_number'] ?: '-') ?></div>
                                        <div class="muted">Anggota: <?= (int)$row['member_count'] ?>, akun aktif: <?= (int)$row['active_user_count'] ?></div>
                                    </td>
                                    <td>
                                        <span class="badge <?= !empty($row['is_registered']) ? 'badge-ok' : 'badge-miss' ?>"><?= html_escape($row['status_label']) ?></span>
                                        <span class="badge <?= !empty($row['db_house']) ? 'badge-ok' : 'badge-warn' ?>"><?= html_escape($row['match_label']) ?></span>
                                        <?php if (!empty($row['matched_unit_code']) && $row['matched_unit_code'] !== str_replace('-', ' ', $row['unit_code'])): ?>
                                            <div class="muted">Cocok ke DB: <?= html_escape($row['matched_unit_code']) ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($row['registered_usernames'])): ?>
                                            <div class="muted">Akun: <?= html_escape(implode(', ', $row['registered_usernames'])) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="name"><?= html_escape($row['whatsapp_number'] ?: '-') ?></div>
                                        <?php if ($row['raw_whatsapp_number'] !== $row['whatsapp_number']): ?>
                                            <div class="muted">Asli: <?= html_escape($row['raw_whatsapp_number']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="row-actions">
                                            <?php if (!empty($row['whatsapp_number'])): ?>
                                                <button class="mini button-primary js-send-wa" type="button" data-unit="<?= html_escape($row['unit_key']) ?>">
                                                    <?= !empty($row['is_registered']) ? 'Kirim test' : 'Kirim wabot' ?>
                                                </button>
                                            <?php endif; ?>
                                            <button class="mini button-secondary js-copy" type="button" data-phone="<?= html_escape($row['whatsapp_number']) ?>">Copy nomor</button>
                                            <button class="mini button-secondary js-contacted" type="button">Tandai dihubungi</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <p class="footer-note">
            Tidak ada perubahan database dari halaman ini. Template dan tanda dihubungi disimpan di browser lokal, sedangkan status terdaftar dihitung dari akun user aktif yang terhubung ke anggota KK pada unit rumah aktif.
        </p>
    </main>

    <script>
        const DEFAULT_TEMPLATE = <?= json_encode($template ?? '', JSON_UNESCAPED_UNICODE) ?>;
        const ROWS = <?= json_encode($items, JSON_UNESCAPED_UNICODE) ?>;
        const SEND_URL = <?= json_encode($send_url ?? '', JSON_UNESCAPED_UNICODE) ?>;
        const CURRENT_STATUS = <?= json_encode($filters['status'] ?? 'all', JSON_UNESCAPED_UNICODE) ?>;
        const CURRENT_Q = <?= json_encode($filters['q'] ?? '', JSON_UNESCAPED_UNICODE) ?>;
        const STORAGE_TEMPLATE = 'sis-harmoni-offline-resident-wa-template';
        const templateEl = document.getElementById('waTemplate');
        const sendStatus = document.getElementById('sendStatus');
        const previewEl = document.getElementById('waPreview');
        const savedTemplate = localStorage.getItem(STORAGE_TEMPLATE);

        if (savedTemplate) {
            templateEl.value = savedTemplate;
        }

        function rowName(row) {
            return row.csv_display_name || row.csv_suami || row.csv_istri || row.db_head_name || ('Unit ' + row.unit_code);
        }

        function messageFor(row) {
            return templateEl.value
                .replaceAll('{nama}', rowName(row))
                .replaceAll('{unit}', row.unit_code || '')
                .replaceAll('{unit_db}', row.matched_unit_code || row.unit_code || '')
                .replaceAll('{suami}', row.csv_suami || '')
                .replaceAll('{istri}', row.csv_istri || '')
                .replaceAll('{kk}', row.db_kk_number || '');
        }

        function escapeHtml(value) {
            return String(value)
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }

        function renderWhatsAppPreview(text) {
            let html = escapeHtml(text);
            html = html.replace(/\*([^*\n][^*]*?)\*/g, '<strong>$1</strong>');
            html = html.replace(/_([^_\n][^_]*?)_/g, '<em>$1</em>');
            html = html.replace(/~([^~\n][^~]*?)~/g, '<s>$1</s>');
            return html;
        }

        function updatePreview() {
            const sample = ROWS.find((row) => row.whatsapp_number) || ROWS[0] || null;
            previewEl.innerHTML = renderWhatsAppPreview(sample ? messageFor(sample) : templateEl.value);
        }

        function replaceSelection(before, after, fallback) {
            const start = templateEl.selectionStart;
            const end = templateEl.selectionEnd;
            const selected = templateEl.value.slice(start, end) || fallback;
            const replacement = before + selected + after;
            templateEl.setRangeText(replacement, start, end, 'select');
            templateEl.focus();
            updatePreview();
        }

        function prefixSelection(prefix) {
            const start = templateEl.selectionStart;
            const end = templateEl.selectionEnd;
            const selected = templateEl.value.slice(start, end) || 'Tulis poin di sini';
            const replacement = selected.split('\n').map((line) => prefix + line).join('\n');
            templateEl.setRangeText(replacement, start, end, 'select');
            templateEl.focus();
            updatePreview();
        }

        function findRow(unitKey) {
            return ROWS.find((row) => row.unit_key === unitKey);
        }

        function setSendStatus(text, type) {
            sendStatus.textContent = text;
            sendStatus.classList.remove('ok', 'fail');
            if (type) {
                sendStatus.classList.add(type);
            }
        }

        async function sendViaWabot(unitKeys, options) {
            const payload = {
                template: templateEl.value,
                unit_keys: unitKeys,
                status: CURRENT_STATUS,
                q: CURRENT_Q,
                limit: options && options.limit ? options.limit : 100,
                include_registered: !!(options && options.includeRegistered)
            };

            const response = await fetch(SEND_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await response.json();
            if (!response.ok) {
                throw new Error(data.message || 'Request wabot gagal.');
            }
            return data;
        }

        function markContacted(tr) {
            if (!tr) return;
            const key = tr.getAttribute('data-row-key');
            if (!key) return;
            localStorage.setItem(key, new Date().toISOString());
            tr.classList.add('is-contacted');
        }

        document.querySelectorAll('tr[data-row-key]').forEach((tr) => {
            if (localStorage.getItem(tr.getAttribute('data-row-key'))) {
                tr.classList.add('is-contacted');
            }
        });

        document.querySelectorAll('[data-format]').forEach((button) => {
            button.addEventListener('click', () => {
                const type = button.dataset.format;
                if (type === 'bold') replaceSelection('*', '*', 'teks tebal');
                if (type === 'italic') replaceSelection('_', '_', 'teks miring');
                if (type === 'strike') replaceSelection('~', '~', 'teks coret');
                if (type === 'bullet') prefixSelection('- ');
                if (type === 'quote') prefixSelection('> ');
            });
        });

        document.getElementById('useDefaultTemplate').addEventListener('click', () => {
            templateEl.value = DEFAULT_TEMPLATE;
            localStorage.setItem(STORAGE_TEMPLATE, templateEl.value);
            updatePreview();
        });

        document.querySelectorAll('.js-send-wa').forEach((button) => {
            button.addEventListener('click', async () => {
                const row = findRow(button.dataset.unit);
                if (!row) return;
                button.disabled = true;
                button.textContent = 'Mengirim...';
                setSendStatus('Mengirim pesan ke ' + row.unit_code + ' via wabot...', '');
                try {
                    const result = await sendViaWabot([row.unit_key], { limit: 1, includeRegistered: true });
                    if (result.sent_count > 0) {
                        markContacted(button.closest('tr'));
                        button.textContent = 'Terkirim';
                        setSendStatus('Pesan terkirim ke ' + row.unit_code + '.', 'ok');
                    } else {
                        button.disabled = false;
                        button.textContent = row.is_registered ? 'Kirim test' : 'Kirim wabot';
                        setSendStatus('Wabot belum berhasil mengirim ke ' + row.unit_code + '.', 'fail');
                    }
                } catch (error) {
                    button.disabled = false;
                    button.textContent = row.is_registered ? 'Kirim test' : 'Kirim wabot';
                    setSendStatus(error.message || 'Request wabot gagal.', 'fail');
                }
            });
        });

        document.querySelectorAll('.js-copy').forEach((button) => {
            button.addEventListener('click', async () => {
                if (!button.dataset.phone) return;
                await navigator.clipboard.writeText(button.dataset.phone);
                button.textContent = 'Tersalin';
                window.setTimeout(() => button.textContent = 'Copy nomor', 1200);
            });
        });

        document.querySelectorAll('.js-contacted').forEach((button) => {
            button.addEventListener('click', () => markContacted(button.closest('tr')));
        });

        document.getElementById('saveTemplate').addEventListener('click', () => {
            localStorage.setItem(STORAGE_TEMPLATE, templateEl.value);
            updatePreview();
        });

        document.getElementById('resetTemplate').addEventListener('click', () => {
            templateEl.value = DEFAULT_TEMPLATE;
            localStorage.removeItem(STORAGE_TEMPLATE);
            updatePreview();
        });

        templateEl.addEventListener('input', updatePreview);

        document.getElementById('clearContacted').addEventListener('click', () => {
            Object.keys(localStorage)
                .filter((key) => key.indexOf('offline-resident-contacted-') === 0)
                .forEach((key) => localStorage.removeItem(key));
            document.querySelectorAll('.is-contacted').forEach((tr) => tr.classList.remove('is-contacted'));
        });

        document.getElementById('sendAllWa').addEventListener('click', async () => {
            const rows = ROWS.filter((row) => !row.is_registered && row.whatsapp_number);
            if (!rows.length) {
                setSendStatus('Tidak ada target belum daftar dengan nomor WhatsApp dari filter ini.', 'fail');
                return;
            }
            const ok = window.confirm('Kirim otomatis via wabot ke ' + rows.length + ' nomor belum daftar dari filter ini?');
            if (!ok) {
                return;
            }

            const button = document.getElementById('sendAllWa');
            button.disabled = true;
            button.textContent = 'Mengirim...';
            setSendStatus('Mengirim ' + rows.length + ' pesan via wabot. Tunggu sebentar...', '');
            try {
                const result = await sendViaWabot(rows.map((row) => row.unit_key), { limit: rows.length });
                (result.sent || []).forEach((sent) => {
                    const tr = document.querySelector('tr[data-row-key="offline-resident-contacted-' + sent.unit_key + '"]');
                    markContacted(tr);
                });
                const message = 'Terkirim: ' + result.sent_count + '. Gagal: ' + result.failed_count + '.';
                setSendStatus(message, result.failed_count > 0 ? 'fail' : 'ok');
            } catch (error) {
                setSendStatus(error.message || 'Request wabot gagal.', 'fail');
            } finally {
                button.disabled = false;
                button.textContent = 'Kirim semua via wabot';
            }
        });

        updatePreview();
    </script>
</body>
</html>
