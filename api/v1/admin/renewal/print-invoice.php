<?php
// api/v1/admin/renewal/print-invoice.php

require_once __DIR__ . '/../../../../config/cors.php';
session_init();

$invoiceId = $_GET['invoice_id'] ?? null;
if (empty($invoiceId) || !is_numeric($invoiceId)) {
    die("ID Invoice tidak valid.");
}

require_once __DIR__ . '/../../../../models/admin/renewal/Renewal.php';

$renewalModel = new Renewal();
$inv = $renewalModel->getInvoiceDetail((int)$invoiceId);

if (!$inv) {
    die("Invoice perpanjangan tidak ditemukan.");
}

$invoiceNumber = htmlspecialchars($inv['invoice_number'] ?? '-');
$clientName = htmlspecialchars($inv['username'] ?? 'Klien');
$clientEmail = htmlspecialchars($inv['email'] ?? '-');
$serviceName = htmlspecialchars($inv['domain_name'] ?? 'Layanan');
$category = htmlspecialchars($inv['service_category'] ?? 'Hosting/Layanan');
$billingPeriod = htmlspecialchars($inv['billing_period'] ?? '1 Tahun');
$totalAmount = (float)($inv['total'] ?? 0);
$formattedTotal = 'Rp ' . number_format($totalAmount, 0, ',', '.');
$invoiceDate = !empty($inv['invoice_date']) ? date('d F Y', strtotime($inv['invoice_date'])) : date('d F Y');
$dueDate = !empty($inv['due_date']) ? date('d F Y', strtotime($inv['due_date'])) : date('d F Y', strtotime('+7 days'));
$status = strtolower($inv['invoice_status'] ?? 'unpaid');
$isPaid = ($status === 'paid');

// Template Pesan WhatsApp
$waMessage = urlencode(
    "Halo kak *{$clientName}*,\n\n" .
    "Berikut adalah Tagihan Invoice Perpanjangan Layanan dari *HostingWebID*:\n\n" .
    "📄 *No. Invoice:* {$invoiceNumber}\n" .
    "🌐 *Layanan:* {$serviceName}\n" .
    "⏱️ *Periode:* {$billingPeriod}\n" .
    "💰 *Total Tagihan:* {$formattedTotal}\n" .
    "📅 *Jatuh Tempo:* {$dueDate}\n\n" .
    "Pembayaran dapat ditransfer ke rekening resmi kami:\n" .
    "🏦 *BCA:* 1234567890 a.n Hosting Web ID\n" .
    "🏦 *Mandiri:* 0987654321 a.n Hosting Web ID\n\n" .
    "Setelah transfer, mohon kirimkan bukti pembayaran untuk langsung kami aktifkan perpanjangannya. Terima kasih! 🙏"
);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Perpanjangan #<?= $invoiceNumber ?> - HostingWebID</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; }
        body { background-color: #f1f5f9; color: #1e293b; padding: 30px 15px; }
        .container { max-width: 800px; margin: 0 auto; background: #ffffff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); overflow: hidden; }
        .actions-bar { max-width: 800px; margin: 0 auto 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; border-radius: 8px; font-weight: 600; font-size: 14px; text-decoration: none; cursor: pointer; border: none; transition: all 0.2s; }
        .btn-primary { background: #ea580c; color: #ffffff; }
        .btn-primary:hover { background: #c2410c; }
        .btn-wa { background: #22c55e; color: #ffffff; }
        .btn-wa:hover { background: #16a34a; }
        .btn-secondary { background: #e2e8f0; color: #334155; }
        .btn-secondary:hover { background: #cbd5e1; }

        .invoice-header { padding: 36px 40px; border-bottom: 2px solid #f1f5f9; display: flex; justify-content: space-between; align-items: flex-start; }
        .logo-box h1 { font-size: 24px; font-weight: 800; color: #ea580c; letter-spacing: -0.5px; }
        .logo-box p { font-size: 13px; color: #64748b; margin-top: 4px; }
        .inv-meta { text-align: right; }
        .inv-meta h2 { font-size: 20px; font-weight: 700; color: #0f172a; }
        .inv-meta .number { font-size: 14px; font-weight: 600; color: #ea580c; margin-top: 4px; }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; text-transform: uppercase; margin-top: 8px; }
        .status-paid { background: #dcfce7; color: #15803d; }
        .status-unpaid { background: #fee2e2; color: #b91c1c; }

        .invoice-body { padding: 36px 40px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 36px; }
        .info-col h3 { font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; color: #94a3b8; margin-bottom: 8px; font-weight: 700; }
        .info-col p { font-size: 14px; color: #334155; line-height: 1.6; }
        .info-col strong { color: #0f172a; font-weight: 600; }

        .table-box { border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { background: #f8fafc; padding: 12px 16px; font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; }
        td { padding: 16px; font-size: 14px; border-bottom: 1px solid #f1f5f9; color: #334155; }
        tr:last-child td { border-bottom: none; }

        .total-box { display: flex; justify-content: flex-end; margin-bottom: 36px; }
        .total-table { width: 300px; }
        .total-table .row { display: flex; justify-content: space-between; padding: 8px 0; font-size: 14px; color: #64748b; }
        .total-table .row.final { border-top: 2px solid #0f172a; padding-top: 12px; margin-top: 6px; font-size: 18px; font-weight: 800; color: #0f172a; }
        .total-table .row.final span:last-child { color: #ea580c; }

        .payment-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px 24px; }
        .payment-box h4 { font-size: 14px; font-weight: 700; color: #0f172a; margin-bottom: 10px; }
        .payment-box p { font-size: 13px; color: #475569; line-height: 1.6; }
        .bank-list { display: flex; gap: 20px; margin-top: 12px; flex-wrap: wrap; }
        .bank-item { background: #ffffff; padding: 10px 16px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 13px; }
        .bank-item strong { color: #0f172a; }

        .invoice-footer { padding: 24px 40px; background: #fafafa; border-top: 1px solid #f1f5f9; text-align: center; font-size: 12px; color: #94a3b8; }

        @media print {
            body { background: #ffffff; padding: 0; }
            .container { box-shadow: none; border-radius: 0; max-width: 100%; }
            .actions-bar { display: none; }
        }
    </style>
</head>
<body>

    <div class="actions-bar">
        <a href="javascript:history.back()" class="btn btn-secondary">← Kembali</a>
        <div style="display: flex; gap: 10px;">
            <a href="https://wa.me/?text=<?= $waMessage ?>" target="_blank" class="btn btn-wa">
                💬 Kirim ke WhatsApp Klien
            </a>
            <button onclick="window.print()" class="btn btn-primary">
                🖨️ Cetak / Simpan PDF
            </button>
        </div>
    </div>

    <div class="container" id="invoice">
        <div class="invoice-header">
            <div class="logo-box">
                <h1>hostingwebid</h1>
                <p>Solusi Hosting, Domain, Iklan & Website Murah Berkualitas</p>
                <p style="margin-top: 2px;">https://hostingwebid.com | info@hostingwebid.com</p>
            </div>
            <div class="inv-meta">
                <h2>FAKTUR PERPANJANGAN</h2>
                <div class="number">#<?= $invoiceNumber ?></div>
                <div class="status-badge <?= $isPaid ? 'status-paid' : 'status-unpaid' ?>">
                    <?= $isPaid ? 'LUNAS / PAID' : 'BELUM DIBAYAR / UNPAID' ?>
                </div>
            </div>
        </div>

        <div class="invoice-body">
            <div class="info-grid">
                <div class="info-col">
                    <h3>Ditagihkan Kepada:</h3>
                    <p>
                        <strong><?= $clientName ?></strong><br>
                        Email: <?= $clientEmail ?><br>
                        ID Klien: #<?= $inv['user_id'] ?>
                    </p>
                </div>
                <div class="info-col" style="text-align: right;">
                    <h3>Informasi Faktur:</h3>
                    <p>
                        Tanggal Faktur: <strong><?= $invoiceDate ?></strong><br>
                        Jatuh Tempo: <strong><?= $dueDate ?></strong><br>
                        Metode: <strong>Transfer Bank / QRIS</strong>
                    </p>
                </div>
            </div>

            <div class="table-box">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 55%;">Deskripsi Layanan</th>
                            <th style="width: 20%;">Kategori</th>
                            <th style="width: 25%; text-align: right;">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <strong>Perpanjangan: <?= $serviceName ?></strong><br>
                                <span style="font-size: 12px; color: #64748b;">
                                    Periode Perpanjangan: <?= $billingPeriod ?>
                                </span>
                            </td>
                            <td>
                                <span style="text-transform: capitalize; font-size: 13px; font-weight: 600; color: #475569;">
                                    <?= $category ?>
                                </span>
                            </td>
                            <td style="text-align: right; font-weight: 700;">
                                <?= $formattedTotal ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="total-box">
                <div class="total-table">
                    <div class="row">
                        <span>Subtotal:</span>
                        <span><?= $formattedTotal ?></span>
                    </div>
                    <div class="row">
                        <span>PPN (0%):</span>
                        <span>Rp 0</span>
                    </div>
                    <div class="row final">
                        <span>Total Tagihan:</span>
                        <span><?= $formattedTotal ?></span>
                    </div>
                </div>
            </div>

            <div class="payment-box">
                <h4>Informasi Pembayaran:</h4>
                <p>Silakan melakukan pembayaran tagihan perpanjangan ini ke salah satu rekening resmi HostingWebID berikut:</p>
                <div class="bank-list">
                    <div class="bank-item">
                        <strong>Bank BCA:</strong> 1234567890<br>
                        <span>a.n Hosting Web ID</span>
                    </div>
                    <div class="bank-item">
                        <strong>Bank Mandiri:</strong> 0987654321<br>
                        <span>a.n Hosting Web ID</span>
                    </div>
                    <div class="bank-item">
                        <strong>Bank BRI:</strong> 5544332211<br>
                        <span>a.n Hosting Web ID</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="invoice-footer">
            <p>Terima kasih telah mempercayakan layanan web & digital Anda kepada <strong>HostingWebID</strong>.</p>
            <p style="margin-top: 4px;">Faktur ini sah dan diproses otomatis oleh sistem komputer.</p>
        </div>
    </div>

</body>
</html>
