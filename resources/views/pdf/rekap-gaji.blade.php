<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #1e293b; }
        h1 { text-align: center; font-size: 16px; margin-bottom: 4px; }
        p.sub { text-align: center; margin-top: 0; margin-bottom: 16px; color: #64748b; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #cbd5e1; padding: 6px 8px; }
        th { background-color: #1e293b; color: #fff; text-align: left; }
        td.number { text-align: right; }
        tfoot td { font-weight: bold; background-color: #f1f5f9; }
    </style>
</head>
<body>
    <h1>REKAP GAJI {{ strtoupper($bulan) }} {{ $tahun }}</h1>
    <p class="sub">TimePay &mdash; Sistem Manajemen Absensi dan Penggajian</p>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama</th>
                <th>NIP</th>
                <th>Jabatan</th>
                <th>Gaji Pokok</th>
                <th>Tunjangan</th>
                <th>Lembur</th>
                <th>Gaji Kotor</th>
                <th>Total Potongan</th>
                <th>Gaji Bersih</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalPokok = 0; $totalTunjangan = 0; $totalLembur = 0;
                $totalKotor = 0; $totalPotongan = 0; $totalBersih = 0;
            @endphp
            @forelse ($penggajians as $i => $p)
                @php
                    $kotor = $p->gaji_pokok + $p->total_tunjangan + $p->total_tunjangan_lembur;
                    $totalPokok += $p->gaji_pokok;
                    $totalTunjangan += $p->total_tunjangan;
                    $totalLembur += $p->total_tunjangan_lembur;
                    $totalKotor += $kotor;
                    $totalPotongan += $p->total_potongan;
                    $totalBersih += $p->gaji_bersih;
                @endphp
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $p->karyawan->nama }}</td>
                    <td>{{ $p->karyawan->nip }}</td>
                    <td>{{ $p->karyawan->jabatan->nama_jabatan ?? '-' }}</td>
                    <td class="number">{{ number_format($p->gaji_pokok, 0, ',', '.') }}</td>
                    <td class="number">{{ number_format($p->total_tunjangan, 0, ',', '.') }}</td>
                    <td class="number">{{ number_format($p->total_tunjangan_lembur, 0, ',', '.') }}</td>
                    <td class="number">{{ number_format($kotor, 0, ',', '.') }}</td>
                    <td class="number">{{ number_format($p->total_potongan, 0, ',', '.') }}</td>
                    <td class="number">{{ number_format($p->gaji_bersih, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" style="text-align:center;">Tidak ada data penggajian untuk periode ini.</td>
                </tr>
            @endforelse
        </tbody>
        @if ($penggajians->count() > 0)
            <tfoot>
                <tr>
                    <td colspan="4">TOTAL</td>
                    <td class="number">{{ number_format($totalPokok, 0, ',', '.') }}</td>
                    <td class="number">{{ number_format($totalTunjangan, 0, ',', '.') }}</td>
                    <td class="number">{{ number_format($totalLembur, 0, ',', '.') }}</td>
                    <td class="number">{{ number_format($totalKotor, 0, ',', '.') }}</td>
                    <td class="number">{{ number_format($totalPotongan, 0, ',', '.') }}</td>
                    <td class="number">{{ number_format($totalBersih, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        @endif
    </table>
</body>
</html>