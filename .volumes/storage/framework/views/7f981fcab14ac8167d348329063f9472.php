    <div id="sidebar-resizer" class="hidden md:block"></div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const resizer = document.getElementById('sidebar-resizer');
            const sidebar = document.querySelector('.fi-sidebar');
            
            // Ambil ukuran tersimpan atau default 13rem
            let sidebarWidth = localStorage.getItem('filament-sidebar-width') || '20rem';
            
            // Set ukuran awal
            document.documentElement.style.setProperty('--sidebar-width', sidebarWidth);

            function initResize(e) {
                e.preventDefault(); // Mencegah seleksi teks saat drag
                window.addEventListener('mousemove', resize);
                window.addEventListener('mouseup', stopResize);
                document.body.style.cursor = 'col-resize'; // Ubah kursor global
                document.body.classList.add('resizing'); // Opsional: untuk styling saat resizing
            }

            function resize(e) {
                // Batasan min/max (misal min 200px, max 600px)
                if (e.pageX < 200 || e.pageX > 600) return;
                
                const newWidth = e.pageX + 'px';
                document.documentElement.style.setProperty('--sidebar-width', newWidth);
                localStorage.setItem('filament-sidebar-width', newWidth);
            }

            function stopResize() {
                window.removeEventListener('mousemove', resize);
                window.removeEventListener('mouseup', stopResize);
                document.body.style.cursor = '';
                document.body.classList.remove('resizing');
            }

            resizer.addEventListener('mousedown', initResize);
        });
    </script>
    <style>
        /* Handle Resizer */
        #sidebar-resizer {
            width: 6px; /* Area grab mouse */
            background: transparent;
            position: fixed;
            z-index: 49; /* Di atas konten, di bawah modal */
            top: 0;
            bottom: 0;
            left: var(--sidebar-width); /* Selalu mengikuti lebar sidebar */
            cursor: col-resize;
            transition: background 0.2s;
        }

        /* Hover effect pada handle agar user tahu bisa digeser */
        #sidebar-resizer:hover, 
        body.resizing #sidebar-resizer {
            background: rgba(var(--primary-500), 0.5); /* Muncul garis biru samar saat hover/drag */
            /* Atau warna netral: background: rgba(128, 128, 128, 0.2); */
        }

        /* Mencegah seleksi teks aneh saat dragging */
        body.resizing {
            user-select: none;
        }
    </style><?php /**PATH /var/www/storage/framework/views/1ea39af80e550ec6031bc8d8b005c87b.blade.php ENDPATH**/ ?>