<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kartu Nasabah - {{ $nasabah->nama }}</title>
    <style>
        body { margin: 0; background: #eef4ef; color: #183228; font-family: Arial, sans-serif; }
        .page { min-height: 100vh; display: grid; place-items: center; padding: 32px; }
        .card { width: 420px; background: white; border-radius: 24px; padding: 28px; box-shadow: 0 14px 45px rgba(23, 107, 77, .15); border-top: 10px solid #176b4d; }
        .brand { color: #176b4d; font-size: 25px; font-weight: 800; letter-spacing: .4px; }
        .subtitle { color: #628073; margin-top: 4px; }
        .content { display: flex; gap: 20px; align-items: center; margin-top: 24px; }
        .qr { width: 150px; height: 150px; }
        .label { color: #6d8377; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; margin-top: 10px; }
        .value { font-size: 18px; font-weight: 700; margin-top: 3px; }
        .notice { margin-top: 24px; padding: 12px; background: #e5f4ec; border-radius: 12px; color: #176b4d; font-size: 12px; }
        .print { margin-top: 18px; border: 0; background: #176b4d; color: white; padding: 12px 20px; border-radius: 10px; cursor: pointer; font-weight: 700; }
        @media print { body { background: white; } .page { padding: 0; } .print { display: none; } .card { box-shadow: none; } }
    </style>
</head>
<body>
<div class="page"><div class="card">
    <div class="brand">EcoWin</div>
    <div class="subtitle">Kartu Nasabah Bank Sampah</div>
    <div class="content">
        <img class="qr" src="{{ $qrDataUri }}" alt="QR kartu nasabah">
        <div>
            <div class="label">Nama</div><div class="value">{{ $nasabah->nama }}</div>
            <div class="label">No. Nasabah</div><div class="value">{{ $nasabah->nomor_nasabah }}</div>
            <div class="label">RT / RW</div><div class="value">{{ $nasabah->bankSampah->rt }} / {{ $nasabah->bankSampah->rw }}</div>
        </div>
    </div>
    <div class="notice">Tunjukkan QR ini kepada petugas saat setor sampah atau mengambil saldo.</div>
    <button class="print" onclick="window.print()">Cetak kartu</button>
</div></div>
</body>
</html>
