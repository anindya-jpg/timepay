<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { box-sizing: border-box; }
        body { font-family: sans-serif; font-size: 12px; color: #1e293b; margin: 0; padding: 24px; }
        table { border-collapse: collapse; }

        .header-table { width: 100%; margin-bottom: 6px; }
        .nama-perusahaan { font-size: 20px; font-weight: bold; color: #dc2626; }
        .judul-slip { font-size: 34px; font-weight: bold; text-align: right; color: #0f172a; }

        .garis-tebal { border-top: 3px solid #0f172a; margin: 8px 0 10px 0; }
        .garis-tipis { border-top: 1px solid #cbd5e1; margin: 8px 0; }

        table.info-karyawan { width: 100%; margin-bottom: 4px; }
        table.info-karyawan td { padding: 2px 0; font-size: 11.5px; }
        .info-label { color: #1e293b; width: 110px; vertical-align: top; }
        .info-value { font-weight: 600; }

        table.section-header { width: 100%; margin-top: 4px; }
        table.section-header td { background: #e2e8f0; font-weight: bold; font-size: 12.5px; padding: 5px 8px; }

        table.dua-kolom { width: 100%; }
        table.dua-kolom > tr > td { width: 50%; vertical-align: top; padding: 0; }

        table.list-item { width: 100%; margin-top: 6px; }
        table.list-item td { padding: 3px 8px; font-size: 11.5px; }
        table.list-item td.nominal { text-align: right; }

        table.total-row { width: 100%; margin-top: 4px; }
        table.total-row td { padding: 6px 8px; font-weight: bold; font-size: 12px; border-top: 1px solid #94a3b8; }
        table.total-row td.nominal { text-align: right; }

        .kolom-kiri-pad { padding-right: 12px; }
        .kolom-kanan-pad { padding-left: 12px; }
    </style>
</head>
<body>

    @php
        $karyawan = $penggajian->karyawan;

        // Nomor rekening ditampilkan penuh (tidak di-mask)
        $noRekeningMasked = $karyawan->no_rekening
            ? preg_replace('/\s/', '', $karyawan->no_rekening)
            : '-';

        // Ganti fallback 'BCA' ini kalau nanti setiap karyawan bisa beda bank
        $namaBank = $karyawan->nama_bank ?? 'BCA';
    @endphp

    <table class="header-table">
        <tr>
            <td class="nama-perusahaan">PT. XXX</td>
            <td class="judul-slip">Slip Gaji</td>
        </tr>
    </table>

    <div class="garis-tebal"></div>

    <table class="info-karyawan">
        <tr>
            <td class="info-label">Nama / NIK</td>
            <td class="info-value" style="text-align: right;">
                {{ $karyawan->nama }} ({{ $karyawan->nip }})
            </td>
        </tr>
        <tr>
            <td class="info-label">Jabatan</td>
            <td class="info-value" style="text-align: right;">
                {{ $karyawan->jabatan->nama_jabatan ?? '-' }}
            </td>
        </tr>
        <tr>
            <td class="info-label">Periode Gaji</td>
            <td class="info-value" style="text-align: right;">
                {{ $penggajian->tahun }}/{{ str_pad($penggajian->bulan, 2, '0', STR_PAD_LEFT) }}
            </td>
        </tr>
        <tr>
            <td class="info-label">Sudah Dibayar</td>
            <td class="info-value" style="text-align: right;">
                Pada BANK {{ strtoupper($namaBank) }} No. Rekening {{ $noRekeningMasked }}
            </td>
        </tr>
    </table>

    <div class="garis-tipis"></div>

    <table class="section-header">
        <tr>
            <td style="width: 50%;">Pendapatan</td>
            <td style="width: 50%;">Potongan</td>
        </tr>
    </table>

    <table class="dua-kolom">
        <tr>
            <td class="kolom-kiri-pad">
                <table class="list-item">
                    <tr>
                        <td>Gaji Pokok</td>
                        <td class="nominal">{{ number_format($penggajian->gaji_pokok, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>Lembur</td>
                        <td class="nominal">{{ number_format($penggajian->total_tunjangan_lembur, 0, ',', '.') }}</td>
                    </tr>
                    @foreach ($rincianTunjangan as $tunjangan)
                        <tr>
                            <td>{{ $tunjangan['nama'] }}</td>
                            <td class="nominal">{{ number_format($tunjangan['nominal'], 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </table>
            </td>

            <td class="kolom-kanan-pad">
                <table class="list-item">
                    @forelse ($rincianPotongan as $potongan)
                        <tr>
                            <td>{{ $potongan['nama'] }}</td>
                            <td class="nominal">{{ number_format($potongan['nominal'], 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" style="color:#94a3b8;">Tidak ada potongan</td>
                        </tr>
                    @endforelse
                </table>
            </td>
        </tr>
    </table>

    <table class="dua-kolom">
        <tr>
            <td class="kolom-kiri-pad">
                <table class="total-row">
                    <tr>
                        <td>Total Pendapatan</td>
                        <td class="nominal">{{ number_format($totalPendapatan, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </td>
            <td class="kolom-kanan-pad">
                <table class="total-row">
                    <tr>
                        <td>Total Potongan</td>
                        <td class="nominal">{{ number_format($penggajian->total_potongan, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="total-row" style="margin-top: 6px;">
        <tr>
            <td style="background: #0f172a; color: #ffffff; border-top: none; font-size: 14px;">
                Gaji Bersih
            </td>
            <td class="nominal" style="background: #0f172a; color: #2dd4bf; border-top: none; font-size: 14px;">
                {{ number_format($penggajian->gaji_bersih, 0, ',', '.') }}
            </td>
        </tr>
    </table>

    <div class="garis-tipis"></div>

    <table class="section-header">
        <tr>
            <td colspan="2">Rangkuman Informasi Kehadiran</td>
        </tr>
    </table>

    <table class="dua-kolom">
        <tr>
            <td class="kolom-kiri-pad">
                <table class="list-item">
                    <tr>
                        <td>Kehadiran</td>
                        <td class="nominal">{{ $penggajian->jumlah_hadir }} Hari</td>
                    </tr>
                    <tr>
                        <td>Ketidak Hadiran</td>
                        <td class="nominal">{{ $penggajian->jumlah_alpha }} Hari</td>
                    </tr>
                    <tr>
                        <td>Izin</td>
                        <td class="nominal">{{ $jumlahIzin }} Hari</td>
                    </tr>
                    <tr>
                        <td>Sakit</td>
                        <td class="nominal">{{ $jumlahSakit }} Hari</td>
                    </tr>
                </table>
            </td>

            <td class="kolom-kanan-pad">
                <table class="list-item">
                    <tr>
                        <td>Terlambat</td>
                        <td class="nominal">{{ $penggajian->jumlah_telat }} Kali</td>
                    </tr>
                    <tr>
                        <td>Pulang Lebih Dulu</td>
                        <td class="nominal">{{ $jumlahPulangCepat }} Kali</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

</body>
</html>