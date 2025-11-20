<script>
    (function () {
        let coordinatesFilled = false;

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

                requestCoordinates(latitudeInput, longitudeInput);
            };

            tryFill();
        };

        const bootstrap = () => {
            coordinatesFilled = false;
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
