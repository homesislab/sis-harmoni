<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>SIS Harmoni</title>

	<style type="text/css">
	::selection { background-color: #0F766E; color: #ffffff; }
	::-moz-selection { background-color: #0F766E; color: #ffffff; }

	body {
		background-color: #f9fafb;
		margin: 0;
		font: 14px/22px -apple-system, BlinkMacSystemFont, "Segoe UI",
		      Roboto, Helvetica, Arial, sans-serif;
		color: #374151;
	}

	h1 {
		color: #0F766E;
		font-size: 22px;
		font-weight: 600;
		margin: 0;
		padding: 18px 20px;
		border-bottom: 1px solid #e5e7eb;
		background: #ffffff;
	}

	h2 {
		font-size: 16px;
		margin: 24px 0 8px;
		color: #111827;
	}

	p {
		margin: 0 0 10px;
	}

	code {
		font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
		font-size: 13px;
		background-color: #ffffff;
		border: 1px solid #e5e7eb;
		color: #1f2937;
		display: block;
		padding: 12px 14px;
		margin: 10px 0;
		border-radius: 6px;
	}

	#container {
		max-width: 920px;
		margin: 40px auto;
		background: #ffffff;
		border: 1px solid #e5e7eb;
		border-radius: 10px;
		box-shadow: 0 10px 25px rgba(0,0,0,.04);
	}

	#body {
		padding: 20px;
	}

	.badge {
		display: inline-block;
		padding: 4px 10px;
		font-size: 12px;
		border-radius: 999px;
		background: #ecfeff;
		color: #0F766E;
		font-weight: 500;
	}

	.footer {
		text-align: right;
		font-size: 12px;
		color: #6b7280;
		border-top: 1px solid #e5e7eb;
		padding: 12px 16px;
		background: #f9fafb;
		border-radius: 0 0 10px 10px;
	}
	</style>
</head>
<body>

<div id="container">
	<h1>SIS Harmoni</h1>

	<div id="body">
		<p>
			<span class="badge">API Backend</span>
		</p>

		<h2>📌 Tentang</h2>
		<p>
			Ini adalah <strong>API resmi SIS Harmoni</strong> untuk pengelolaan data warga,
			keuangan, konten, polling, dan aktivitas Paguyuban & DKM
			di lingkungan <strong>Sharia Islamic Soreang</strong>.
		</p>

		<h2>🔐 Akses API</h2>
		<p>
			Seluruh endpoint API berada di bawah prefix:
		</p>
		<code>/api/v1/*</code>

		<p>
			Endpoint membutuhkan autentikasi <strong>Bearer Token (JWT)</strong>,
			kecuali endpoint aktivasi & login.
		</p>

		<h2>📖 Dokumentasi</h2>
		<p>
			Referensi kontrak API tersedia melalui:
		</p>
		<code>api_openapi.json</code>

		<p>
			Disarankan menggunakan <strong>Postman Collection</strong>
			yang telah disediakan untuk pengujian endpoint.
		</p>

		<h2>⚠️ Catatan</h2>
		<p>
			Halaman ini hanya sebagai <em>health entry point</em>.
			Akses langsung via browser <strong>bukan</strong> cara penggunaan API.
		</p>
	</div>

	<div class="footer">
		Rendered in <strong>{elapsed_time}</strong> seconds
		<?php if (ENVIRONMENT === 'development'): ?>
			• CodeIgniter <strong><?php echo CI_VERSION; ?></strong>
		<?php endif; ?>
	</div>
</div>

</body>
</html>
