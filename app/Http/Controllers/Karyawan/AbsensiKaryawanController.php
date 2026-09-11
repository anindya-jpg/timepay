<?php

namespace App\Http\Controllers\Karyawan;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\HariLibur;
use App\Models\PengaturanJamKerja;
use App\Models\PengaturanLokasi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class AbsensiKaryawanController extends Controller
{
    protected string $timezone = 'Asia/Jakarta';

//batas waktu absen masuk
    protected string $batasAbsenMasuk = '13:00';

    public function createMasuk(): Response
    {
        $karyawan = Auth::guard('karyawan')->user();

        $sekarang = Carbon::now(
            $this->timezone
        );

        $hariIni = $sekarang->toDateString();

        $hariLibur = $this->hariLiburHariIni();

        $absenHariIni = Absensi::where(
            'id_karyawan',
            $karyawan->id_karyawan
        )
            ->whereDate(
                'tanggal',
                $hariIni
            )
            ->first();

        $sudahAbsenMasuk =
            $absenHariIni?->jam_masuk !== null;

        $jamKerja = PengaturanJamKerja::first();

        $lokasiKantor = PengaturanLokasi::first();

        return Inertia::render(
            'karyawan/absen/masuk',
            [
                'sudahAbsenMasuk' =>
                    $sudahAbsenMasuk,

                'hariLibur' =>
                    $hariLibur,

                /*
                 * Batas waktu absen masuk hanya berlaku
                 * kalau karyawan belum absen masuk hari ini.
                 * Kalau sudah absen, biarkan tetap tampil
                 * status "Sudah Absen Masuk" seperti biasa.
                 */
                'batasWaktuTerlewat' =>
                    ! $sudahAbsenMasuk &&
                    $this->sudahLewatBatasAbsenMasuk(
                        $sekarang
                    ),

                'batasAbsenMasuk' =>
                    $this->batasAbsenMasuk,

                'absenHariIni' =>
                    $absenHariIni,

                'jamMasukStandar' =>
                    $jamKerja?->jam_masuk_standar
                        ? substr(
                            (string) $jamKerja->jam_masuk_standar,
                            0,
                            5
                        )
                        : null,

                'lokasiKantor' =>
                    $lokasiKantor
                        ? [
                            'nama_lokasi' =>
                                $lokasiKantor->nama_lokasi,

                            'latitude' =>
                                $lokasiKantor->latitude,

                            'longitude' =>
                                $lokasiKantor->longitude,

                            'radius_meter' =>
                                $lokasiKantor->radius_meter,
                        ]
                        : null,
            ]
        );
    }

    // =====================================================
    // SIMPAN ABSEN MASUK
    // =====================================================

    public function storeMasuk(
        Request $request
    ): RedirectResponse {
        $karyawan = Auth::guard(
            'karyawan'
        )->user();

        /*
         * Gunakan waktu Jakarta/WIB.
         */
        $sekarang = Carbon::now(
            $this->timezone
        );

        $tanggalHariIni =
            $sekarang->toDateString();

        /*
         * =================================================
         * CEK HARI LIBUR
         * =================================================
         *
         * Termasuk:
         * - Sabtu
         * - Minggu
         * - Hari libur khusus dari database
         */

        $hariLibur =
            $this->hariLiburHariIni();

        if ($hariLibur) {
            return back()->withErrors([
                'foto' =>
                    'Hari ini adalah hari libur. Absensi tidak tersedia.',
            ]);
        }

        /*
         * =================================================
         * CEK ABSEN MASUK
         * =================================================
         */

        $absenHariIni = Absensi::where(
            'id_karyawan',
            $karyawan->id_karyawan
        )
            ->whereDate(
                'tanggal',
                $tanggalHariIni
            )
            ->first();

        if (
            $absenHariIni &&
            $absenHariIni->jam_masuk
        ) {
            return back()->withErrors([
                'foto' =>
                    'Kamu sudah absen masuk hari ini.',
            ]);
        }

        /*
         * =================================================
         * CEK BATAS WAKTU ABSEN MASUK
         * =================================================
         *
         * Lewat dari batasAbsenMasuk (13:00), absen masuk
         * tidak bisa dilakukan lagi. Pengaman ini penting
         * karena halaman bisa saja sudah kebuka duluan
         * sebelum jam 13:00, lalu di-submit setelah lewat.
         */

        if (
            $this->sudahLewatBatasAbsenMasuk(
                $sekarang
            )
        ) {
            return back()->withErrors([
                'foto' =>
                    "Sudah melewati batas waktu absen masuk ({$this->batasAbsenMasuk}). Absen masuk tidak dapat dilakukan lagi hari ini.",
            ]);
        }

        /*
         * =================================================
         * VALIDASI
         * =================================================
         */

        $data = $request->validate([
            'foto' => [
                'required',
                'image',
                'max:5120',
            ],

            'latitude' => [
                'nullable',
                'numeric',
            ],

            'longitude' => [
                'nullable',
                'numeric',
            ],
        ]);

        /*
         * =================================================
         * VALIDASI FILE FOTO
         * =================================================
         */

        $foto =
            $request->file('foto');

        if (
            ! $foto instanceof UploadedFile
        ) {
            return back()->withErrors([
                'foto' =>
                    'Foto tidak berhasil diterima oleh server. Silakan ambil foto ulang.',
            ]);
        }

        if (! $foto->isValid()) {
            return back()->withErrors([
                'foto' =>
                    'File foto tidak valid. Silakan ambil foto ulang.',
            ]);
        }

        /*
         * =================================================
         * SIMPAN FOTO
         * =================================================
         */

        $path =
            $this->simpanFoto(
                $foto,
                'absensi/masuk'
            );

        if (! $path) {
            return back()->withErrors([
                'foto' =>
                    'Foto gagal disimpan ke server. Silakan coba lagi.',
            ]);
        }

        /*
         * =================================================
         * TENTUKAN STATUS MASUK
         * =================================================
         *
         * Contoh:
         *
         * Standar : 09:00
         *
         * 08:59 -> Hadir
         * 09:00 -> Hadir
         * 09:01 -> Telat 1 menit
         * 09:07 -> Telat 7 menit
         */

        [
            $status,
            $menitTelat
        ] = $this->tentukanStatusMasuk(
            $sekarang
        );

        /*
         * =================================================
         * HITUNG JARAK DARI KANTOR
         * =================================================
         */

        $jarakMeter = null;

        if (
            isset($data['latitude']) &&
            isset($data['longitude'])
        ) {
            $lokasiKantor =
                PengaturanLokasi::first();

            if ($lokasiKantor) {
                $jarakMeter =
                    $this->hitungJarakMeter(
                        (float) $data['latitude'],
                        (float) $data['longitude'],
                        (float) $lokasiKantor->latitude,
                        (float) $lokasiKantor->longitude
                    );
            }
        }

        /*
         * =================================================
         * SIMPAN ABSENSI
         * =================================================
         */

        if ($absenHariIni) {
            $absenHariIni->update([
                'jam_masuk' =>
                    $sekarang->format('H:i:s'),

                'foto_masuk' =>
                    $path,

                'lokasi_masuk_lat' =>
                    $data['latitude'] ?? null,

                'lokasi_masuk_lng' =>
                    $data['longitude'] ?? null,

                'jarak_masuk_meter' =>
                    $jarakMeter,

                'status' =>
                    $status,

                'menit_telat' =>
                    $menitTelat,
            ]);
        } else {
            Absensi::create([
                'id_karyawan' =>
                    $karyawan->id_karyawan,

                'tanggal' =>
                    $tanggalHariIni,

                'jam_masuk' =>
                    $sekarang->format('H:i:s'),

                'foto_masuk' =>
                    $path,

                'lokasi_masuk_lat' =>
                    $data['latitude'] ?? null,

                'lokasi_masuk_lng' =>
                    $data['longitude'] ?? null,

                'jarak_masuk_meter' =>
                    $jarakMeter,

                'status' =>
                    $status,

                'menit_telat' =>
                    $menitTelat,
            ]);
        }

        return redirect()
            ->route('karyawan.dashboard')
            ->with(
                'success',
                'Absen masuk berhasil dicatat.'
            );
    }

    // =====================================================
    // HALAMAN ABSEN PULANG
    // =====================================================

    public function createPulang(): Response
    {
        $karyawan =
            Auth::guard('karyawan')->user();

        $hariIni =
            Carbon::now(
                $this->timezone
            )->toDateString();

        $hariLibur =
            $this->hariLiburHariIni();

        $absenHariIni =
            Absensi::where(
                'id_karyawan',
                $karyawan->id_karyawan
            )
                ->whereDate(
                    'tanggal',
                    $hariIni
                )
                ->first();

        $jamKerja =
            PengaturanJamKerja::first();

        $lokasiKantor =
            PengaturanLokasi::first();

        return Inertia::render(
            'karyawan/absen/pulang',
            [
                'sudahAbsenPulang' =>
                    $absenHariIni?->jam_pulang !== null,

                'sudahAbsenMasuk' =>
                    $absenHariIni?->jam_masuk !== null,

                'hariLibur' =>
                    $hariLibur,

                'absenHariIni' =>
                    $absenHariIni,

                'jamPulangStandar' =>
                    $jamKerja?->jam_pulang_standar
                        ? substr(
                            (string) $jamKerja->jam_pulang_standar,
                            0,
                            5
                        )
                        : null,

                'lokasiKantor' =>
                    $lokasiKantor
                        ? [
                            'nama_lokasi' =>
                                $lokasiKantor->nama_lokasi,

                            'latitude' =>
                                $lokasiKantor->latitude,

                            'longitude' =>
                                $lokasiKantor->longitude,

                            'radius_meter' =>
                                $lokasiKantor->radius_meter,
                        ]
                        : null,
            ]
        );
    }

    // =====================================================
    // SIMPAN ABSEN PULANG
    // =====================================================

    public function storePulang(
        Request $request
    ): RedirectResponse {
        $karyawan =
            Auth::guard(
                'karyawan'
            )->user();

        /*
         * Waktu Jakarta/WIB.
         */
        $sekarang =
            Carbon::now(
                $this->timezone
            );

        $tanggalHariIni =
            $sekarang->toDateString();

        /*
         * =================================================
         * CEK HARI LIBUR
         * =================================================
         */

        $hariLibur =
            $this->hariLiburHariIni();

        if ($hariLibur) {
            return back()->withErrors([
                'foto' =>
                    'Hari ini adalah hari libur. Absensi tidak tersedia.',
            ]);
        }

        /*
         * =================================================
         * AMBIL ABSENSI HARI INI
         * =================================================
         */

        $absenHariIni =
            Absensi::where(
                'id_karyawan',
                $karyawan->id_karyawan
            )
                ->whereDate(
                    'tanggal',
                    $tanggalHariIni
                )
                ->first();

        /*
         * =================================================
         * WAJIB ABSEN MASUK
         * =================================================
         */

        if (
            ! $absenHariIni ||
            ! $absenHariIni->jam_masuk
        ) {
            return back()->withErrors([
                'foto' =>
                    'Kamu belum melakukan absen masuk hari ini.',
            ]);
        }

        /*
         * =================================================
         * CEK SUDAH ABSEN PULANG
         * =================================================
         */

        if ($absenHariIni->jam_pulang) {
            return back()->withErrors([
                'foto' =>
                    'Kamu sudah absen pulang hari ini.',
            ]);
        }

        /*
         * =================================================
         * VALIDASI
         * =================================================
         */

        $data = $request->validate([
            'foto' => [
                'required',
                'image',
                'max:5120',
            ],

            'latitude' => [
                'nullable',
                'numeric',
            ],

            'longitude' => [
                'nullable',
                'numeric',
            ],

            'keterangan_lembur' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        /*
         * =================================================
         * VALIDASI FILE FOTO
         * =================================================
         */

        $foto =
            $request->file('foto');

        if (
            ! $foto instanceof UploadedFile
        ) {
            return back()->withErrors([
                'foto' =>
                    'Foto tidak berhasil diterima oleh server. Silakan ambil foto ulang.',
            ]);
        }

        if (! $foto->isValid()) {
            return back()->withErrors([
                'foto' =>
                    'File foto tidak valid. Silakan ambil foto ulang.',
            ]);
        }

        /*
         * =================================================
         * SIMPAN FOTO
         * =================================================
         */

        $path =
            $this->simpanFoto(
                $foto,
                'absensi/pulang'
            );

        if (! $path) {
            return back()->withErrors([
                'foto' =>
                    'Foto gagal disimpan ke server. Silakan coba lagi.',
            ]);
        }

        /*
         * =================================================
         * HITUNG JARAK DARI KANTOR
         * =================================================
         */

        $jarakMeter = null;

        if (
            isset($data['latitude']) &&
            isset($data['longitude'])
        ) {
            $lokasiKantor =
                PengaturanLokasi::first();

            if ($lokasiKantor) {
                $jarakMeter =
                    $this->hitungJarakMeter(
                        (float) $data['latitude'],
                        (float) $data['longitude'],
                        (float) $lokasiKantor->latitude,
                        (float) $lokasiKantor->longitude
                    );
            }
        }

        /*
         * =================================================
         * TENTUKAN STATUS PULANG (PULANG CEPAT)
         * =================================================
         *
         * Aturan:
         *
         * Standar : 17:00
         *
         * Pulang cepat jika absen pulang dilakukan SEBELUM
         * jam standar, dengan selisih LEBIH DARI 3 jam
         * (180 menit) dari jam standar.
         *
         * Kalau selisihnya 3 jam atau kurang (masih dekat
         * dengan jam standar), TIDAK dianggap pulang cepat.
         *
         * Contoh (standar 17:00):
         * 13:59 -> selisih 181 menit (>3 jam) -> Pulang Cepat
         * 14:00 -> selisih tepat 3 jam        -> bukan pulang cepat
         * 15:00 -> selisih 2 jam              -> bukan pulang cepat
         * 17:00 / lebih -> tepat waktu        -> bukan pulang cepat
         *
         * Kolom status di database hanya menerima satu nilai
         * (enum: Hadir, Telat, Pulang Cepat, Alpha, Sakit,
         * Izin), jadi tidak bisa gabungan. Kalau pagi harinya
         * "Telat" dan sore ini juga pulang cepat, statusnya
         * DITIMPA menjadi "Pulang Cepat".
         *
         * Nominal potongan tetap dihitung terpisah pada
         * proses payroll (tabel potongan_rule). Di sini
         * hanya menandai status kehadiran.
         */

        $statusBaru = $this->tentukanStatusPulang(
            $sekarang,
            $absenHariIni->status
        );

        /*
         * =================================================
         * HITUNG LEMBUR
         * =================================================
         *
         * Lembur dihitung dari MENIT PERTAMA setelah jam
         * pulang standar (tanpa toleransi). Disimpan dalam
         * bentuk desimal jam ke kolom jam_lembur (menit / 60).
         *
         * Tarif per jam (Rupiah) dihitung terpisah pada
         * proses payroll dari tabel tarif_lembur, tidak
         * dihitung di sini.
         *
         * Contoh (standar 17:00):
         * 17:00 atau lebih awal -> jam_lembur = 0
         * 17:30 -> 30 menit     -> jam_lembur = 0.50
         * 18:15 -> 75 menit     -> jam_lembur = 1.25
         *
         * Approval lembur default "pending", menunggu
         * admin approve/reject secara manual.
         */

        $jamLembur = $this->hitungJamLembur(
            $sekarang
        );

        /*
         * =================================================
         * WAJIB ISI KETERANGAN LEMBUR
         * =================================================
         *
         * Kalau ternyata lembur (jam_lembur > 0), karyawan
         * wajib mengisi keterangan_lembur (dikirim dari form
         * frontend). Kalau kosong, tolak dan minta diisi
         * ulang (foto & lokasi harus diambil ulang juga).
         */

        if (
            $jamLembur > 0 &&
            empty(
                $data['keterangan_lembur'] ?? null
            )
        ) {
            return back()->withErrors([
                'keterangan_lembur' =>
                    'Kamu melebihi jam pulang standar. Silakan isi keterangan lembur terlebih dahulu.',
            ]);
        }

        /*
         * =================================================
         * SIMPAN ABSEN PULANG
         * =================================================
         *
         * Nominal potongan (pulang cepat) dan nominal
         * lembur (Rupiah) tidak dihitung di sini. Di sini
         * hanya menyimpan status kehadiran dan jumlah jam
         * lembur (desimal); nominalnya dihitung terpisah
         * pada proses payroll dari tabel potongan_rule dan
         * tarif_lembur.
         */

        $absenHariIni->update([
            'jam_pulang' =>
                $sekarang->format('H:i:s'),

            'foto_pulang' =>
                $path,

            'lokasi_pulang_lat' =>
                $data['latitude'] ?? null,

            'lokasi_pulang_lng' =>
                $data['longitude'] ?? null,

            'jarak_pulang_meter' =>
                $jarakMeter,

            'status' =>
                $statusBaru,

            'jam_lembur' =>
                $jamLembur,

            'keterangan_lembur' =>
                $jamLembur > 0
                    ? $data['keterangan_lembur']
                    : null,

            'status_approval_lembur' =>
                $jamLembur > 0
                    ? 'pending'
                    : null,
        ]);

        return redirect()
            ->route('karyawan.dashboard')
            ->with(
                'success',
                'Absen pulang berhasil dicatat.'
            );
    }

    // =====================================================
    // HALAMAN IZIN / SAKIT
    // =====================================================

    public function createIzinSakit(): Response
    {
        $karyawan =
            Auth::guard('karyawan')->user();

        $hariIni =
            Carbon::now(
                $this->timezone
            )->toDateString();

        $hariLibur =
            $this->hariLiburHariIni();

        $absenHariIni =
            Absensi::where(
                'id_karyawan',
                $karyawan->id_karyawan
            )
                ->whereDate(
                    'tanggal',
                    $hariIni
                )
                ->first();

        /*
         * Sudah ada catatan hari ini kalau:
         * - sudah absen masuk (jam_masuk terisi), atau
         * - sudah pernah mengajukan Izin/Sakit hari ini.
         */
        $sudahAdaCatatanHariIni =
            $absenHariIni &&
            (
                $absenHariIni->jam_masuk !== null ||
                in_array(
                    $absenHariIni->status,
                    ['Sakit', 'Izin'],
                    true
                )
            );

        return Inertia::render(
            'karyawan/absen/izin-sakit',
            [
                'hariLibur' =>
                    $hariLibur,

                'sudahAdaCatatanHariIni' =>
                    $sudahAdaCatatanHariIni,

                'absenHariIni' =>
                    $absenHariIni,
            ]
        );
    }

    // =====================================================
    // SIMPAN IZIN / SAKIT
    // =====================================================

    public function storeIzinSakit(
        Request $request
    ): RedirectResponse {
        $karyawan =
            Auth::guard(
                'karyawan'
            )->user();

        $sekarang =
            Carbon::now(
                $this->timezone
            );

        $tanggalHariIni =
            $sekarang->toDateString();

        /*
         * =================================================
         * CEK HARI LIBUR
         * =================================================
         */

        $hariLibur =
            $this->hariLiburHariIni();

        if ($hariLibur) {
            return back()->withErrors([
                'jenis' =>
                    'Hari ini adalah hari libur. Pengajuan izin/sakit tidak tersedia.',
            ]);
        }

        /*
         * =================================================
         * AMBIL ABSENSI HARI INI (kalau ada)
         * =================================================
         */

        $absenHariIni =
            Absensi::where(
                'id_karyawan',
                $karyawan->id_karyawan
            )
                ->whereDate(
                    'tanggal',
                    $tanggalHariIni
                )
                ->first();

        /*
         * =================================================
         * CEK SUDAH ADA CATATAN HARI INI
         * =================================================
         *
         * Tidak boleh mengajukan Izin/Sakit kalau:
         * - sudah absen masuk hari ini, atau
         * - sudah pernah mengajukan Izin/Sakit hari ini.
         */

        if (
            $absenHariIni &&
            (
                $absenHariIni->jam_masuk !== null ||
                in_array(
                    $absenHariIni->status,
                    ['Sakit', 'Izin'],
                    true
                )
            )
        ) {
            return back()->withErrors([
                'jenis' =>
                    'Kamu sudah punya catatan absensi untuk hari ini.',
            ]);
        }

        /*
         * =================================================
         * VALIDASI
         * =================================================
         */

        $data = $request->validate([
            'jenis' => [
                'required',
                'in:Sakit,Izin',
            ],

            'keterangan' => [
                'required',
                'string',
                'max:500',
            ],

            'surat' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'max:5120',
            ],
        ]);

        /*
         * =================================================
         * SIMPAN FILE PENDUKUNG (kalau ada)
         * =================================================
         */

        $pathFilePendukung = null;

        $suratFile =
            $request->file('surat');

        if ($suratFile instanceof UploadedFile) {
            if (! $suratFile->isValid()) {
                return back()->withErrors([
                    'surat' =>
                        'File surat tidak valid. Silakan unggah ulang.',
                ]);
            }

            $pathFilePendukung =
                $this->simpanFilePendukung(
                    $suratFile,
                    'absensi/surat'
                );

            if (! $pathFilePendukung) {
                return back()->withErrors([
                    'surat' =>
                        'File surat gagal disimpan ke server. Silakan coba lagi.',
                ]);
            }
        }

        /*
         * =================================================
         * SIMPAN / UPDATE ABSENSI
         * =================================================
         *
         * Auto-approved: begitu diajukan, langsung tercatat
         * sebagai status Sakit/Izin, tidak perlu approval
         * admin lebih lanjut.
         */

        Absensi::updateOrCreate(
            [
                'id_karyawan' =>
                    $karyawan->id_karyawan,

                'tanggal' =>
                    $tanggalHariIni,
            ],
            [
                'status' =>
                    $data['jenis'],

                'keterangan' =>
                    $data['keterangan'],

                'file_pendukung' =>
                    $pathFilePendukung,
            ]
        );

        return redirect()
            ->route('karyawan.dashboard')
            ->with(
                'success',
                "Pengajuan {$data['jenis']} berhasil dicatat."
            );
    }

    // =====================================================
    // CEK HARI LIBUR HARI INI
    // =====================================================

    protected function hariLiburHariIni(): ?array
    {
        $hariIni =
            Carbon::now(
                $this->timezone
            );

        /*
         * =================================================
         * SABTU
         * =================================================
         */

        if ($hariIni->isSaturday()) {
            return [
                'tanggal' =>
                    $hariIni->format('Y-m-d'),

                'keterangan' =>
                    'Hari Libur Mingguan (Sabtu)',
            ];
        }

        /*
         * =================================================
         * MINGGU
         * =================================================
         */

        if ($hariIni->isSunday()) {
            return [
                'tanggal' =>
                    $hariIni->format('Y-m-d'),

                'keterangan' =>
                    'Hari Libur Mingguan (Minggu)',
            ];
        }

        /*
         * =================================================
         * HARI LIBUR KHUSUS DATABASE
         * =================================================
         */

        $hariLibur =
            HariLibur::whereDate(
                'tanggal',
                $hariIni->toDateString()
            )->first();

        if (! $hariLibur) {
            return null;
        }

        return [
            'tanggal' =>
                $hariLibur->tanggal
                    ? Carbon::parse(
                        $hariLibur->tanggal
                    )->format('Y-m-d')
                    : $hariIni->format('Y-m-d'),

            'keterangan' =>
                $hariLibur->keterangan
                ?: 'Hari Libur',
        ];
    }

    // =====================================================
    // CEK BATAS WAKTU ABSEN MASUK
    // =====================================================
    //
    // Dipakai bersama antara createMasuk() dan storeMasuk()
    // supaya konsisten. Bandingkan waktu sekarang dengan
    // $this->batasAbsenMasuk (contoh: '13:00').

    protected function sudahLewatBatasAbsenMasuk(
        Carbon $sekarang
    ): bool {
        if (
            ! preg_match(
                '/^\d{2}:\d{2}$/',
                $this->batasAbsenMasuk
            )
        ) {
            return false;
        }

        [$jam, $menit] =
            array_map(
                'intval',
                explode(
                    ':',
                    $this->batasAbsenMasuk
                )
            );

        $totalMenitBatas =
            ($jam * 60) + $menit;

        $totalMenitSekarang =
            ($sekarang->hour * 60)
            + $sekarang->minute;

        return $totalMenitSekarang >
            $totalMenitBatas;
    }

    // =====================================================
    // SIMPAN FOTO
    // =====================================================

    protected function simpanFoto(
        UploadedFile $foto,
        string $folder
    ): ?string {
        try {
            if (! $foto->isValid()) {
                return null;
            }

            $realPath =
                $foto->getRealPath();

            if (
                ! $realPath ||
                ! file_exists($realPath)
            ) {
                return null;
            }

            $isiFile =
                file_get_contents(
                    $realPath
                );

            if (
                $isiFile === false ||
                $isiFile === ''
            ) {
                return null;
            }

            $disk =
                Storage::disk('public');

            $namaFile =
                Carbon::now(
                    $this->timezone
                )->format('Ymd_His')
                . '_'
                . uniqid()
                . '.jpg';

            $path =
                $folder
                . '/'
                . $namaFile;

            $berhasil =
                $disk->put(
                    $path,
                    $isiFile
                );

            if (! $berhasil) {
                return null;
            }

            return $path;

        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    // =====================================================
    // SIMPAN FILE PENDUKUNG (SURAT IZIN / SAKIT)
    // =====================================================
    //
    // Mirip simpanFoto(), tapi ekstensi file mengikuti
    // file aslinya (jpg/jpeg/png/pdf), tidak dipaksa .jpg.

    protected function simpanFilePendukung(
        UploadedFile $file,
        string $folder
    ): ?string {
        try {
            if (! $file->isValid()) {
                return null;
            }

            $realPath =
                $file->getRealPath();

            if (
                ! $realPath ||
                ! file_exists($realPath)
            ) {
                return null;
            }

            $isiFile =
                file_get_contents(
                    $realPath
                );

            if (
                $isiFile === false ||
                $isiFile === ''
            ) {
                return null;
            }

            $disk =
                Storage::disk('public');

            $ekstensi =
                strtolower(
                    (string) $file->getClientOriginalExtension()
                );

            if (
                ! in_array(
                    $ekstensi,
                    ['jpg', 'jpeg', 'png', 'pdf'],
                    true
                )
            ) {
                $ekstensi = 'pdf';
            }

            $namaFile =
                Carbon::now(
                    $this->timezone
                )->format('Ymd_His')
                . '_'
                . uniqid()
                . '.'
                . $ekstensi;

            $path =
                $folder
                . '/'
                . $namaFile;

            $berhasil =
                $disk->put(
                    $path,
                    $isiFile
                );

            if (! $berhasil) {
                return null;
            }

            return $path;

        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }


    // TENTUKAN STATUS MASUK
    protected function tentukanStatusMasuk(
        Carbon $sekarang
    ): array {
        $jamKerja =
            PengaturanJamKerja::first();

        if (
            ! $jamKerja ||
            empty(
                $jamKerja->jam_masuk_standar
            )
        ) {
            return [
                'Hadir',
                0,
            ];
        }
        $jamStandar =
            substr(
                (string) $jamKerja->jam_masuk_standar,
                0,
                5
            );

        if (
            ! preg_match(
                '/^\d{2}:\d{2}$/',
                $jamStandar
            )
        ) {
            return [
                'Hadir',
                0,
            ];
        }

        [$jam, $menit] =
            array_map(
                'intval',
                explode(
                    ':',
                    $jamStandar
                )
            );

      
        $totalMenitStandar =
            ($jam * 60) + $menit;

       
        $totalMenitSekarang =
            ($sekarang->hour * 60)
            + $sekarang->minute;

        //telat

        if (
            $totalMenitSekarang >
            $totalMenitStandar
        ) {
            $menitTelat =
                $totalMenitSekarang
                - $totalMenitStandar;

            return [
                'Telat',
                $menitTelat,
            ];
        }

        //hadir

        return [
            'Hadir',
            0,
        ];
    }


    // TENTUKAN STATUS PULANG
    protected function tentukanStatusPulang(
        Carbon $sekarang,
        ?string $statusSebelumnya
    ): string {
        $statusSebelumnya =
            $statusSebelumnya
            ?: 'Hadir';

        $jamKerja =
            PengaturanJamKerja::first();

        if (
            ! $jamKerja ||
            empty(
                $jamKerja->jam_pulang_standar
            )
        ) {
            return $statusSebelumnya;
        }

        $jamStandar =
            substr(
                (string) $jamKerja->jam_pulang_standar,
                0,
                5
            );

        if (
            ! preg_match(
                '/^\d{2}:\d{2}$/',
                $jamStandar
            )
        ) {
            return $statusSebelumnya;
        }
        [$jam, $menit] =
            array_map(
                'intval',
                explode(
                    ':',
                    $jamStandar
                )
            );

        $totalMenitStandar =
            ($jam * 60) + $menit;

        $totalMenitSekarang =
            ($sekarang->hour * 60)
            + $sekarang->minute;

        $selisihMenit =
            $totalMenitStandar
            - $totalMenitSekarang;

        $pulangCepat =
            $selisihMenit > 180;

        if (! $pulangCepat) {
            return $statusSebelumnya;
        }
        return 'Pulang Cepat';
    }


    // HITUNG LEMBUR
    protected function hitungJamLembur(
        Carbon $sekarang
    ): float {
        $jamKerja =
            PengaturanJamKerja::first();

        if (
            ! $jamKerja ||
            empty(
                $jamKerja->jam_pulang_standar
            )
        ) {
            return 0;
        }

        $jamStandar =
            substr(
                (string) $jamKerja->jam_pulang_standar,
                0,
                5
            );

        if (
            ! preg_match(
                '/^\d{2}:\d{2}$/',
                $jamStandar
            )
        ) {
            return 0;
        }

        [$jam, $menit] =
            array_map(
                'intval',
                explode(
                    ':',
                    $jamStandar
                )
            );

        $totalMenitStandar =
            ($jam * 60) + $menit;

        $totalMenitSekarang =
            ($sekarang->hour * 60)
            + $sekarang->minute;

        //Lembur dihitung dari menit pertama setelah jam pulang
        $menitLembur =
            $totalMenitSekarang
            - $totalMenitStandar;

        if ($menitLembur <= 0) {
            return 0;
        }

        return round(
            $menitLembur / 60,
            2
        );
    }


    // HITUNG JARAK GPS
    protected function hitungJarakMeter(
        float $lat1,
        float $lon1,
        float $lat2,
        float $lon2
    ): float {
        $radiusBumi =
            6371000;

        $dLat =
            deg2rad(
                $lat2 - $lat1
            );

        $dLon =
            deg2rad(
                $lon2 - $lon1
            );

        $a =
            sin($dLat / 2) ** 2
            +
            cos(
                deg2rad($lat1)
            )
            *
            cos(
                deg2rad($lat2)
            )
            *
            sin($dLon / 2) ** 2;

        $c =
            2 *
            atan2(
                sqrt($a),
                sqrt(1 - $a)
            );

        return round(
            $radiusBumi * $c,
            2
        );
    }
}