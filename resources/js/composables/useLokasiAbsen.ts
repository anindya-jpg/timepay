import { ref } from 'vue';

type LokasiKantor = {
    latitude: string;
    longitude: string;
    radius_meter: number;
};

export function useLokasiAbsen(lokasiKantor: LokasiKantor) {
    const lokasiSaatIni = ref<{ lat: number; lng: number } | null>(null);
    const jarakMeter = ref<number | null>(null);
    const loadingLokasi = ref(false);
    const errorLokasi = ref('');
    const diLuarRadius = ref(false);

    // Rumus Haversine: hitung jarak antara 2 titik koordinat (dalam meter)
    function hitungJarakMeter(lat1: number, lng1: number, lat2: number, lng2: number): number {
        const R = 6371000; // radius bumi dalam meter
        const toRad = (deg: number) => (deg * Math.PI) / 180;

        const dLat = toRad(lat2 - lat1);
        const dLng = toRad(lng2 - lng1);

        const a =
            Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLng / 2) * Math.sin(dLng / 2);

        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

        return R * c;
    }

    function deteksiLokasi(): Promise<void> {
        return new Promise((resolve) => {
            if (!navigator.geolocation) {
                errorLokasi.value = 'Browser kamu tidak mendukung deteksi lokasi.';
                resolve();
                return;
            }

            loadingLokasi.value = true;
            errorLokasi.value = '';

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;

                    lokasiSaatIni.value = { lat, lng };

                    const kantorLat = parseFloat(lokasiKantor.latitude);
                    const kantorLng = parseFloat(lokasiKantor.longitude);

                    const jarak = hitungJarakMeter(lat, lng, kantorLat, kantorLng);
                    jarakMeter.value = Math.round(jarak);
                    diLuarRadius.value = jarak > lokasiKantor.radius_meter;

                    loadingLokasi.value = false;
                    resolve();
                },
                (error) => {
                    errorLokasi.value = `Gagal ambil lokasi: ${error.message}. Pastikan izin lokasi diizinkan di browser.`;
                    loadingLokasi.value = false;
                    resolve();
                },
                { enableHighAccuracy: true, timeout: 10000 },
            );
        });
    }

    return {
        lokasiSaatIni,
        jarakMeter,
        loadingLokasi,
        errorLokasi,
        diLuarRadius,
        deteksiLokasi,
    };
}