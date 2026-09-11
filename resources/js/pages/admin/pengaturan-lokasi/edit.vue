<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Search } from '@lucide/vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';
import { onMounted, onBeforeUnmount, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

// Perbaikan default: ikon marker Leaflet nggak kebaca otomatis kalau di-bundle
// lewat Vite, jadi kita set manual path-nya.
delete (L.Icon.Default.prototype as unknown as { _getIconUrl?: unknown })._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
});

type Lokasi = {
    id_lokasi: number;
    nama_lokasi: string;
    latitude: string;
    longitude: string;
    radius_meter: number;
} | null;

const props = defineProps<{
    lokasi: Lokasi;
}>();

const DEFAULT_LAT = -6.2088; // Jakarta, dipakai kalau belum ada lokasi tersimpan
const DEFAULT_LNG = 106.8456;

const form = useForm({
    nama_lokasi: props.lokasi?.nama_lokasi ?? 'Kantor Pusat',
    latitude: props.lokasi?.latitude ?? String(DEFAULT_LAT),
    longitude: props.lokasi?.longitude ?? String(DEFAULT_LNG),
    radius_meter: props.lokasi?.radius_meter ?? 150,
});

// --- Pencarian lokasi lewat alamat / nama tempat (geocoding) ---
type HasilGeocode = {
    display_name: string;
    lat: string;
    lon: string;
};

const alamatQuery = ref('');
const loadingAlamat = ref(false);
const errorAlamat = ref('');
const hasilPencarian = ref<HasilGeocode[]>([]);

// Photon (Komoot) - fuzzy/typeahead matching, lebih toleran untuk nama
// jalan gabungan atau nama tempat yang tidak persis sama dengan tag OSM.
async function fetchPhoton(query: string, biasLat: number, biasLon: number): Promise<HasilGeocode[]> {
    const url = new URL('https://photon.komoot.io/api/');
    url.searchParams.set('q', query);
    url.searchParams.set('limit', '5');

    // Bias hasil ke sekitar posisi peta saat ini biar lebih relevan
    url.searchParams.set('lat', String(biasLat));
    url.searchParams.set('lon', String(biasLon));

    const res = await fetch(url.toString());
    if (!res.ok) return [];

    const data = await res.json();
    const features = data?.features ?? [];

    return features.map((f: any) => {
        const p = f.properties ?? {};
        const [lon, lat] = f.geometry?.coordinates ?? [null, null];

        // Susun nama tampilan dari bagian-bagian yang tersedia
        const bagian = [p.name, p.street, p.district, p.city, p.state, p.country].filter(Boolean);

        return {
            display_name: bagian.join(', '),
            lat: String(lat),
            lon: String(lon),
        };
    }).filter((item: HasilGeocode) => item.lat !== 'null' && item.lon !== 'null');
}

// Nominatim - dipakai sebagai fallback kedua
async function fetchNominatim(query: string, withCountryFilter: boolean): Promise<HasilGeocode[]> {
    const url = new URL('https://nominatim.openstreetmap.org/search');
    url.searchParams.set('q', query);
    url.searchParams.set('format', 'json');
    url.searchParams.set('limit', '5');
    url.searchParams.set('addressdetails', '1');
    if (withCountryFilter) {
        url.searchParams.set('countrycodes', 'id');
    }

    const res = await fetch(url.toString(), {
        headers: { 'Accept-Language': 'id' },
    });

    if (!res.ok) return [];
    return res.json();
}

async function cariLokasiByAlamat() {
    if (!alamatQuery.value.trim()) {
        errorAlamat.value = 'Ketik alamat atau nama tempat dulu sebelum mencari.';
        return;
    }

    loadingAlamat.value = true;
    errorAlamat.value = '';
    hasilPencarian.value = [];

    // Bias pencarian ke posisi peta saat ini (kalau belum pernah diisi, fallback ke Jakarta)
    const biasLat = parseFloat(form.latitude) || DEFAULT_LAT;
    const biasLon = parseFloat(form.longitude) || DEFAULT_LNG;

    try {
        // 1) Coba Photon dulu - lebih toleran untuk nama jalan/tempat yang tidak persis
        let hasil = await fetchPhoton(alamatQuery.value, biasLat, biasLon);

        // 2) Kalau kosong, coba Nominatim dengan filter Indonesia
        if (!hasil.length) {
            hasil = await fetchNominatim(alamatQuery.value, true);
        }

        // 3) Kalau masih kosong, coba Nominatim tanpa filter negara
        if (!hasil.length) {
            hasil = await fetchNominatim(alamatQuery.value, false);
        }

        if (!hasil.length) {
            errorAlamat.value =
                'Lokasi tidak ditemukan di ketiga sumber pencarian. Coba kata kunci lebih pendek/umum (misal nama jalan utama atau nama kecamatan saja), atau klik/geser marker langsung di peta.';
            return;
        }

        hasilPencarian.value = hasil;
    } catch (err) {
        errorAlamat.value = err instanceof Error ? err.message : 'Terjadi kesalahan saat mencari alamat.';
    } finally {
        loadingAlamat.value = false;
    }
}

function pilihHasilPencarian(item: HasilGeocode) {
    const lat = parseFloat(item.lat);
    const lon = parseFloat(item.lon);

    form.latitude = lat.toFixed(7);
    form.longitude = lon.toFixed(7);
    map?.setView([lat, lon], 17);

    hasilPencarian.value = [];
    alamatQuery.value = item.display_name;
}

// --- Peta ---
const mapContainer = ref<HTMLDivElement | null>(null);
let map: L.Map | null = null;
let marker: L.Marker | null = null;
let lingkaranRadius: L.Circle | null = null;
let sedangUpdateDariPeta = false;

onMounted(() => {
    if (!mapContainer.value) return;

    const lat = parseFloat(form.latitude) || DEFAULT_LAT;
    const lng = parseFloat(form.longitude) || DEFAULT_LNG;

    map = L.map(mapContainer.value).setView([lat, lng], 16);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19,
    }).addTo(map);

    marker = L.marker([lat, lng], { draggable: true }).addTo(map);
    lingkaranRadius = L.circle([lat, lng], {
        radius: Number(form.radius_meter) || 150,
        color: '#10b981',
        fillColor: '#10b981',
        fillOpacity: 0.15,
    }).addTo(map);

    // Klik di peta -> pindahin marker + titik lokasi ke situ
    map.on('click', (e: L.LeafletMouseEvent) => {
        pindahkanTitik(e.latlng.lat, e.latlng.lng);
    });

    // Geser marker manual -> update titik lokasi
    marker.on('dragend', () => {
        const pos = marker!.getLatLng();
        pindahkanTitik(pos.lat, pos.lng);
    });
});

onBeforeUnmount(() => {
    map?.remove();
});

function pindahkanTitik(lat: number, lng: number) {
    sedangUpdateDariPeta = true;
    form.latitude = lat.toFixed(7);
    form.longitude = lng.toFixed(7);
    sedangUpdateDariPeta = false;
}

// Kalau field latitude/longitude berubah dari luar peta (misal isi manual
// atau lewat hasil pencarian alamat), ikut geser marker + peta.
watch([() => form.latitude, () => form.longitude], ([lat, lng]) => {
    if (sedangUpdateDariPeta || !map || !marker) return;

    const latNum = parseFloat(lat);
    const lngNum = parseFloat(lng);
    if (isNaN(latNum) || isNaN(lngNum)) return;

    const posisi: L.LatLngExpression = [latNum, lngNum];
    marker.setLatLng(posisi);
    lingkaranRadius?.setLatLng(posisi);
    map.setView(posisi);
});

// Radius berubah -> perbesar/perkecil lingkaran di peta
watch(
    () => form.radius_meter,
    (radius) => {
        lingkaranRadius?.setRadius(Number(radius) || 0);
    },
);

function submit() {
    form.put('/admin/pengaturan-lokasi');
}
</script>

<template>
    <Head title="Pengaturan Lokasi" />

    <div class="flex flex-col gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-foreground">Pengaturan Lokasi Kantor</h1>
            <p class="text-sm text-muted-foreground">
                Cari lokasi lewat alamat/nama tempat, klik di peta, atau geser marker buat nentuin titik
                lokasi kantor. Ini dipakai buat validasi jarak saat karyawan absen lewat GPS — karyawan
                tetap bisa absen walau di luar radius, tapi jaraknya akan tercatat sebagai catatan buat
                admin.
            </p>
        </div>

        <div class="grid gap-4 lg:grid-cols-5">
            <Card class="lg:col-span-3">
                <CardHeader>
                    <CardTitle class="text-base">Peta Lokasi</CardTitle>
                </CardHeader>
                <CardContent>
                    <div ref="mapContainer" class="h-[420px] w-full overflow-hidden rounded-lg border border-border" />
                    <p class="mt-2 text-xs text-muted-foreground">
                        Lingkaran hijau menunjukkan radius toleransi yang sudah diatur di sebelah kanan.
                    </p>
                </CardContent>
            </Card>

            <Card class="lg:col-span-2">
                <CardHeader>
                    <CardTitle class="text-base">Detail Lokasi</CardTitle>
                </CardHeader>
                <CardContent>
                    <form class="grid gap-4" @submit.prevent="submit">
                        <div class="grid gap-2">
                            <Label for="nama_lokasi">Nama Lokasi *</Label>
                            <Input id="nama_lokasi" v-model="form.nama_lokasi" placeholder="Contoh: Kantor Pusat" />
                            <InputError :message="form.errors.nama_lokasi" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="alamat_lokasi">Cari Lokasi (alamat, nama sekolah, kantor, dll)</Label>
                            <div class="flex gap-2">
                                <Input
                                    id="alamat_lokasi"
                                    v-model="alamatQuery"
                                    placeholder="Contoh: SDN 5 Banjar atau Jl. Sudirman No. 1"
                                    @keydown.enter.prevent="cariLokasiByAlamat"
                                />
                                <Button type="button" :disabled="loadingAlamat" @click="cariLokasiByAlamat">
                                    <Search class="size-4" />
                                    {{ loadingAlamat ? 'Mencari...' : 'Cari' }}
                                </Button>
                            </div>

                            <!-- Daftar hasil pencarian, muncul kalau ada kandidat lokasi -->
                            <ul
                                v-if="hasilPencarian.length"
                                class="mt-1 max-h-48 divide-y divide-border overflow-y-auto rounded-md border border-border text-sm"
                            >
                                <li
                                    v-for="(item, idx) in hasilPencarian"
                                    :key="idx"
                                    class="cursor-pointer px-3 py-2 hover:bg-muted"
                                    @click="pilihHasilPencarian(item)"
                                >
                                    {{ item.display_name }}
                                </li>
                            </ul>

                            <p v-if="errorAlamat" class="text-sm text-destructive">{{ errorAlamat }}</p>
                            <p class="text-xs text-muted-foreground">
                                Pilih salah satu hasil pencarian di atas, atau geser marker/klik di peta
                                untuk menyesuaikan posisi persisnya. Kalau nama tempat tidak muncul, coba
                                kata kunci yang lebih pendek/umum.
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="grid gap-2">
                                <Label for="latitude">Latitude *</Label>
                                <Input id="latitude" v-model="form.latitude" />
                                <InputError :message="form.errors.latitude" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="longitude">Longitude *</Label>
                                <Input id="longitude" v-model="form.longitude" />
                                <InputError :message="form.errors.longitude" />
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label for="radius_meter">Radius Toleransi (meter) *</Label>
                            <Input id="radius_meter" type="number" min="10" max="5000" v-model="form.radius_meter" />
                            <p class="text-xs text-muted-foreground">
                                Disarankan minimal 100-150 meter, karena GPS di laptop/HP bisa meleset
                                dari lokasi asli.
                            </p>
                            <InputError :message="form.errors.radius_meter" />
                        </div>

                        <Button type="submit" class="mt-2" :disabled="form.processing">Simpan Lokasi</Button>
                    </form>
                </CardContent>
            </Card>
        </div>
    </div>
</template>