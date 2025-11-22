@php
    $profile = \App\Models\ProfilePerusahaan::first();
    $companyLocation = $profile
        ? [
            'lat' => $profile->lat_perusahaan,
            'long' => $profile->long_perusahaan,
        ]
        : null;
@endphp

<script>
    (function () {
        const geofenceRadiusMeters = 100;
        const companyLocation = @json($companyLocation);

        let coordinatesFilled = false;
        let submitGuardRegistered = false;

        const haveCompanyLocation =
            companyLocation &&
            Number.isFinite(parseFloat(companyLocation.lat)) &&
            Number.isFinite(parseFloat(companyLocation.long));

        const toRad = (deg) => (deg * Math.PI) / 180;
        const distanceInMeters = (lat1, lon1, lat2, lon2) => {
            const R = 6371000; // mean Earth radius, meters
            const φ1 = toRad(lat1);
            const φ2 = toRad(lat2);
            const Δφ = toRad(lat2 - lat1);
            const Δλ = toRad(lon2 - lon1);

            const a =
                Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
                Math.cos(φ1) * Math.cos(φ2) * Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

            return R * c;
        };

        // const alertUser = (message) => {
        //     // Filament notification would be nicer, but this hook runs before Livewire is ready
        //     // so we fall back to a simple alert.
        //     alert(message);
        // };

        const notify = (message, status = 'danger') => {
            window.dispatchEvent(
                new CustomEvent('filament-notify', {
                    detail: {
                        status,
                        message,
                    },
                }),
            );
        };

        const attachSubmitGuard = (form, latitudeInput, longitudeInput) => {
            if (submitGuardRegistered || !form) {
                return;
            }

            form.addEventListener('submit', (event) => {
                if (!haveCompanyLocation) {
                    event.preventDefault();
                    // alertUser('Koordinat kantor belum diatur. Hubungi admin.');
                    notify('Koordinat kantor belum diatur. Hubungi Admin');
                    return;
                }

                const userLat = parseFloat(latitudeInput.value);
                const userLong = parseFloat(longitudeInput.value);

                if (!Number.isFinite(userLat) || !Number.isFinite(userLong)) {
                    event.preventDefault();
                    // alertUser('Lokasi Anda belum tersedia. Izinkan akses lokasi dan coba lagi.');
                    notify('Lokasi Anda belum tersedia. Izinkan akses lokasi dan coba lagi.');
                    return;
                }

                const targetLat = parseFloat(companyLocation.lat);
                const targetLong = parseFloat(companyLocation.long);
                const distance = distanceInMeters(targetLat, targetLong, userLat, userLong);

                if (distance > geofenceRadiusMeters) {
                    event.preventDefault();
                    // alertUser(
                    //     `Jarak Anda ${distance.toFixed(0)}m dari titik kantor. Maksimum ${geofenceRadiusMeters}m untuk absensi.`
                    // );
                    notify(
                        `Jarak Anda ${distance.toFixed(0)}m dari titik kantor. Maksimum ${geofenceRadiusMeters}m untuk absensi.`
                    );
                }
            });

            submitGuardRegistered = true;
        };

        const requestCoordinates = (latitudeInput, longitudeInput) => {
            if (!navigator.geolocation) {
                console.warn('Browser tidak mendukung geolocation.');
                return;
            }

            coordinatesFilled = true;

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    latitudeInput.value = position.coords.latitude;
                    longitudeInput.value = position.coords.longitude;

                    // Paksa Livewire mengetahui bahwa nilai field berubah
                    latitudeInput.dispatchEvent(new Event('input', { bubbles: true }));
                    longitudeInput.dispatchEvent(new Event('input', { bubbles: true }));
                },
                (err) => {
                    coordinatesFilled = false;
                    console.error('Gagal ambil lokasi', err);
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                }
            );
        };

        const waitForInputsAndFill = () => {
            if (coordinatesFilled) {
                return;
            }

            let attempts = 0;
            const maxAttempts = 20;

            const tryFill = () => {
                const latitudeInput = document.querySelector('input#lat_absen');
                const longitudeInput = document.querySelector('input#long_absen');

                if (!latitudeInput || !longitudeInput) {
                    attempts += 1;

                    if (attempts <= maxAttempts) {
                        setTimeout(tryFill, 300);
                    }

                    return;
                }

                const form = latitudeInput.closest('form');
                attachSubmitGuard(form, latitudeInput, longitudeInput);
                requestCoordinates(latitudeInput, longitudeInput);
            };

            tryFill();
        };

        const bootstrap = () => {
            coordinatesFilled = false;
            submitGuardRegistered = false;
            waitForInputsAndFill();
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', bootstrap);
        } else {
            bootstrap();
        }

        document.addEventListener('livewire:navigated', bootstrap);
    })();
</script>
