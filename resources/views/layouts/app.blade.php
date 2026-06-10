<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>USS - Kinerja Penelitian Dosen</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="icon" href="/favicon.png" type="image/png">
    <style>
        :root {
            --primary: #177a8c;
            --primary-glow: rgba(23, 122, 140, 0.2);
            --secondary: #e2f1f4;
            --bg-dark: #f0f4f8;
            --bg-card: rgba(255, 255, 255, 0.85);
            --text-main: #1f2937;
            --text-muted: #64748b;
            --border-glass: rgba(15, 46, 90, 0.15);
            --glass-blur: blur(12px);
        }
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-dark);
            background-image: 
                radial-gradient(at 0% 0%, rgba(215, 172, 124, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(15, 46, 90, 0.1) 0px, transparent 50%);
            color: var(--text-main);
            min-height: 100vh;
        }
        .glass {
            background: var(--bg-card);
            backdrop-filter: var(--glass-blur);
            -webkit-backdrop-filter: var(--glass-blur);
            border: 1px solid var(--border-glass);
            border-radius: 16px;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.1);
        }
        #app { display: grid; grid-template-columns: 260px 1fr; min-height: 100vh; }
        aside { padding: 2rem; background: var(--primary); color: white; border-right: 1px solid var(--border-glass); }
        main { padding: 2rem; overflow-y: auto; }
        .logo { margin-bottom: 2rem; text-align: center; }
        .logo img { max-width: 150px; }
        nav ul { list-style: none; }
        nav li { margin-bottom: 0.5rem; }
        nav a { display: flex; align-items: center; padding: 0.75rem 1rem; color: rgba(255, 255, 255, 0.7); text-decoration: none; border-radius: 10px; transition: all 0.2s; }
        nav a.active, nav a:hover { background: var(--secondary); color: #0f2e5a; font-weight: 600; }
        nav a i { margin-right: 1rem; }
        .realtime-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; background: rgba(34, 197, 94, 0.1); color: #4ade80; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .dot { width: 8px; height: 8px; background: #22c55e; border-radius: 50%; animation: pulse 2s infinite; }
        @keyframes pulse { 0% { transform: scale(0.95); } 70% { transform: scale(1); } 100% { transform: scale(0.95); } }
        
        /* Modal Styling */
        .modal-overlay {
          position: fixed;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          background: rgba(0, 0, 0, 0.7);
          backdrop-filter: blur(8px);
          display: flex;
          align-items: center;
          justify-content: center;
          z-index: 1000;
          padding: 1rem;
        }

        .modal-content {
          width: 100%;
          max-width: 800px;
          max-height: 90vh;
          padding: 2.5rem;
          position: relative;
          overflow-y: auto;
          border: 1px solid rgba(255, 255, 255, 0.2);
          background: var(--bg-card);
          border-radius: 16px;
        }

        .close-btn {
          position: absolute;
          top: 1rem;
          right: 1.5rem;
          background: none;
          border: none;
          color: var(--text-muted);
          font-size: 2rem;
          cursor: pointer;
          transition: color 0.3s;
        }

        .close-btn:hover {
          color: var(--primary);
        }

        .detail-grid {
          display: grid;
          grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
          gap: 1.5rem;
          margin-top: 2rem;
        }

        .detail-card {
          padding: 1.5rem;
          background: rgba(255, 255, 255, 0.03);
          border: 1px solid var(--border-glass);
          border-radius: 12px;
        }

        .detail-label {
          font-size: 0.8rem;
          color: var(--text-muted);
          margin-bottom: 0.5rem;
        }

        .detail-value {
          font-size: 1.5rem;
          font-weight: 700;
        }
    </style>
</head>
<body>
    <div id="app">
        <aside>
            <div class="logo">
                <div class="logo-container" style="text-align: center;">
                    <img src="/logo.png" alt="Logo Universitas Sumatera Selatan" onerror="this.style.display='none'; document.getElementById('logo-text').style.display='block';" style="max-width: 100%; width: 190px; background: white; padding: 12px; border-radius: 12px; box-shadow: 0 4px 8px rgba(0,0,0,0.15);">
                    <div id="logo-text" style="display: none;">
                        <h2 style="color: white; margin: 0; font-size: 2rem; text-align: center; font-weight: 800; letter-spacing: 2px;">USS</h2>
                        <div style="font-size: 0.75rem; color: rgba(255,255,255,0.8); text-align: center; margin-top: 5px;">Universitas Sumatera Selatan</div>
                    </div>
                </div>
            </div>
            <nav>
                <ul>
                    <li><a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                    <li><a href="{{ route('lecturers') }}" class="{{ request()->routeIs('lecturers') ? 'active' : '' }}"><i class="fas fa-users"></i> Data Dosen</a></li>
                    <li><a href="{{ route('crawl') }}" class="{{ request()->routeIs('crawl') ? 'active' : '' }}"><i class="fas fa-spider"></i> Crawl Engine</a></li>
                    <li><a href="{{ route('analytics') }}" class="{{ request()->routeIs('analytics') ? 'active' : '' }}"><i class="fas fa-project-diagram"></i> Analytics</a></li>
                    <li><a href="{{ route('accreditation') }}" class="{{ request()->routeIs('accreditation') ? 'active' : '' }}"><i class="fas fa-file-alt"></i> Laporan Akreditasi</a></li>
                </ul>
            </nav>
        </aside>
        
        <main>
            <header style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <div>
                    <h1 style="font-size: 2.2rem; font-weight: 800; color: #0f2e5a; margin-bottom: 0.5rem;">Dashboard Kinerja Penelitian & Pengabdian</h1>
                    <p style="color: var(--text-main); font-size: 1.1rem; font-weight: 600;">Monitoring & Pelaporan Kinerja Penelitian dan Pengabdian Kepada Masyarakat Dosen Universitas Sumatera Selatan.</p>
                </div>
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <div class="realtime-badge">
                        <div class="dot"></div>
                        REAL-TIME UPDATING
                    </div>
                </div>
            </header>

            @yield('content')
        </main>
    </div>
    <div id="modal-container" class="modal-overlay" style="display: none;">
        <div class="modal-content glass">
            <button id="close-modal" class="close-btn">&times;</button>
            <div id="modal-body"></div>
        </div>
    </div>

    <script>
        // Modal close logic
        document.getElementById('close-modal')?.addEventListener('click', () => {
            document.getElementById('modal-container').style.display = 'none';
        });

        window.addEventListener('click', (e) => {
            const modalContainer = document.getElementById('modal-container');
            if (e.target === modalContainer) {
                modalContainer.style.display = 'none';
            }
        });

        // Global functions for SINTA search
        window.showLecturerDetail = function(lecturer) {
            const modalBody = document.getElementById('modal-body');
            const modalContainer = document.getElementById('modal-container');
            
            const sintaId = lecturer.sintaId || null;
            const currentStatus = lecturer.status || 'Aktif';

            modalBody.innerHTML = `
                <div class="lecturer-header" style="margin-bottom: 2rem;">
                    <div class="avatar" style="width: 80px; height: 80px; font-size: 2rem;">${lecturer.name.charAt(0)}</div>
                    <div>
                        <h2 style="font-size: 1.8rem; display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                            ${lecturer.name} 
                            ${sintaId ? `<span class="pill" style="font-size: 0.8rem; background: var(--secondary); color: var(--primary); font-weight: 700; border: none; padding: 0.3rem 0.8rem;">SINTA ID: ${sintaId}</span>` : ''}
                            <span class="pill" style="font-size: 0.8rem; background: ${currentStatus === 'Aktif' ? '#22c55e' : (currentStatus === 'Pensiun' ? '#f59e0b' : '#ef4444')}; color: white; font-weight: 700; border: none; padding: 0.3rem 0.8rem;">${currentStatus}</span>
                        </h2>
                        <p style="color: var(--text-muted); font-size: 1rem;">${lecturer.prodi}</p>
                    </div>
                </div>

                <div class="detail-grid">
                    <div class="detail-card">
                        <div class="detail-label">SINTA Overall</div>
                        <div class="detail-value" style="color: var(--primary);">${lecturer.sintaOverall}</div>
                    </div>
                    <div class="detail-card">
                        <div class="detail-label">SINTA 3Yr</div>
                        <div class="detail-value" style="color: #0ea5e9;">${lecturer.sinta3Yr}</div>
                    </div>
                    <div class="detail-card">
                        <div class="detail-label">Scholar Citations</div>
                        <div class="detail-value" style="color: #d7ac7c;">${lecturer.scholar}</div>
                    </div>
                    <div class="detail-card">
                        <div class="detail-label">Scopus Documents</div>
                        <div class="detail-value" style="color: #0ea5e9;">${lecturer.scopus}</div>
                    </div>
                    <div class="detail-card">
                        <div class="detail-label">h-Index</div>
                        <div class="detail-value" style="color: #8b5cf6;">${lecturer.hIndex}</div>
                    </div>
                </div>

                <div style="margin-top: 2rem; display: flex; flex-direction: column; gap: 1rem;">
                    ${sintaId ? `
                        <button onclick="window.open('https://sinta.kemdiktisaintek.go.id/authors/profile/${sintaId}', '_blank')" class="glass glass-hover" style="width: 100%; padding: 1rem; text-align: center; background: #22c55e; color: white; border-radius: 10px; font-weight: 600; font-size: 0.9rem; border: none; cursor: pointer;">
                            <i class="fas fa-external-link-alt"></i> Buka Profil SINTA
                        </button>
                        <button onclick="window.handleSintaSearch('${lecturer.id}', '${lecturer.name}', true)" class="glass glass-hover" style="width: 100%; padding: 1rem; text-align: center; background: var(--primary); color: white; border-radius: 10px; font-weight: 600; font-size: 0.9rem; border: none; cursor: pointer;">
                            <i class="fas fa-search"></i> Cari Ulang & Update ID SINTA
                        </button>
                    ` : `
                        <button id="sinta-action-btn" onclick="window.handleSintaSearch('${lecturer.id}', '${lecturer.name}')" class="glass glass-hover" style="width: 100%; padding: 1rem; text-align: center; background: var(--primary); color: white; border-radius: 10px; font-weight: 600; font-size: 0.9rem; border: none; cursor: pointer;">
                            <i class="fas fa-search"></i> Cari & Deteksi ID SINTA
                        </button>
                    `}
                    <div id="sinta-confirm-container" style="display: none; padding: 1rem; background: rgba(0,0,0,0.03); border-radius: 10px; border: 1px solid var(--border-glass);">
                        <div id="sinta-detection-status" style="font-size: 0.85rem; color: var(--text-main); margin-bottom: 0.8rem; font-weight: 500;">
                            <i class="fas fa-spinner fa-spin"></i> Mencari profil...
                        </div>
                        <div id="sinta-confirm-actions" style="display: none; gap: 0.5rem;">
                            <button onclick="window.saveSintaId('${lecturer.id}')" style="background: #22c55e; color: white; border: none; border-radius: 8px; padding: 0.6rem 1rem; flex: 1; cursor: pointer; font-weight: 700; font-size: 0.85rem;">Ya, Benar (Simpan)</button>
                            <button onclick="window.showManualInput('${lecturer.id}')" style="background: #ef4444; color: white; border: none; border-radius: 8px; padding: 0.6rem 1rem; flex: 1; cursor: pointer; font-weight: 700; font-size: 0.85rem;">Bukan</button>
                        </div>
                    </div>
                    
                    <div style="margin-top: 0.5rem; display: flex; flex-direction: column; gap: 0.5rem;">
                        <div style="font-size: 0.85rem; color: var(--text-muted); font-weight: 500;">Update Status Dosen:</div>
                        <div style="display: flex; gap: 0.5rem;">
                            <button onclick="window.updateStatus('${lecturer.id}', 'Pensiun')" style="background: #f59e0b; color: white; border: none; border-radius: 8px; padding: 0.6rem 1rem; flex: 1; cursor: pointer; font-weight: 700; font-size: 0.85rem;">Pensiun</button>
                            <button onclick="window.updateStatus('${lecturer.id}', 'Berhenti')" style="background: #ef4444; color: white; border: none; border-radius: 8px; padding: 0.6rem 1rem; flex: 1; cursor: pointer; font-weight: 700; font-size: 0.85rem;">Berhenti</button>
                            <button onclick="window.updateStatus('${lecturer.id}', 'Aktif')" style="background: #22c55e; color: white; border: none; border-radius: 8px; padding: 0.6rem 1rem; flex: 1; cursor: pointer; font-weight: 700; font-size: 0.85rem;">Aktif</button>
                        </div>
                    </div>
                </div>
            `;

            modalContainer.style.display = 'flex';
        };

        window.handleSintaSearch = function(id, name, force = false) {
            const searchUrl = `https://sinta.kemdiktisaintek.go.id/authors?aff={{ env('SINTA_AFFILIATION_ID', '8263') }}&q=${encodeURIComponent(name)}`;
            window.open(searchUrl, '_blank');
                
                const confirmContainer = document.getElementById('sinta-confirm-container');
                const statusEl = document.getElementById('sinta-detection-status');
                const actionsEl = document.getElementById('sinta-confirm-actions');
                
                confirmContainer.style.display = 'block';
                statusEl.innerHTML = `<i class="fas fa-search fa-spin"></i> Sedang menghubungkan ke SINTA untuk deteksi otomatis...`;
                actionsEl.style.display = 'none';

                fetch(`/sinta-proxy?name=${encodeURIComponent(name)}`)
                    .then(r => r.json())
                    .then(data => {
                        if (data.success && data.id) {
                            window.detectedSintaId = data.id;
                            statusEl.innerHTML = `
                                <div style="color: #22c55e; margin-bottom: 0.5rem; font-weight: 700;"><i class="fas fa-check-circle"></i> Berhasil mendeteksi ID Dosen!</div>
                                <div style="background: var(--secondary); color: var(--primary); display: inline-block; padding: 0.5rem 1rem; border-radius: 8px; font-size: 1.2rem; font-weight: 800; margin-bottom: 0.8rem;">${data.id}</div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">Jika profil di tab baru benar milik <b>${name}</b>, silakan konfirmasi:</div>
                            `;
                            actionsEl.style.display = 'flex';
                        } else {
                            statusEl.innerHTML = `
                                <div style="color: var(--primary); margin-bottom: 0.5rem;"><i class="fas fa-info-circle"></i> ID belum terdeteksi otomatis karena proteksi SINTA.</div>
                                <div style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.5rem;">Silakan masukkan 7 digit ID yang ada di URL tab baru:</div>
                                <input type="number" id="sinta-id-input-manual" style="width:100%; padding:0.5rem; border:1px solid var(--border-glass); border-radius:8px; margin-bottom:0.5rem;" placeholder="Contoh: 5973273">
                                <button onclick="window.saveSintaIdManual('${id}')" style="width:100%; background:var(--primary); color:white; border:none; padding:0.6rem; border-radius:8px; font-weight:600; cursor:pointer;">Simpan Manual</button>
                            `;
                        }
                    })
                    .catch(() => {
                        statusEl.innerHTML = `
                            <div style="color: var(--primary); margin-bottom: 0.5rem;"><i class="fas fa-info-circle"></i> Gagal menghubungi proxy.</div>
                            <div style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.5rem;">Silakan masukkan 7 digit ID yang ada di URL tab baru:</div>
                            <input type="number" id="sinta-id-input-manual" style="width:100%; padding:0.5rem; border:1px solid var(--border-glass); border-radius:8px; margin-bottom:0.5rem;" placeholder="Contoh: 5973273">
                            <button onclick="window.saveSintaIdManual('${id}')" style="width:100%; background:var(--primary); color:white; border:none; padding:0.6rem; border-radius:8px; font-weight:600; cursor:pointer;">Simpan Manual</button>
                        `;
                    });
        };

        window.saveSintaId = function(id) {
            if (!window.detectedSintaId) return;
            
            const savedSintaIds = JSON.parse(localStorage.getItem('sintaIds') || '{}');
            savedSintaIds[id] = window.detectedSintaId;
            localStorage.setItem('sintaIds', JSON.stringify(savedSintaIds));
            
            fetch('/update-lecturer', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ id: id, sintaId: window.detectedSintaId })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('ID SINTA berhasil disimpan di server!');
                    location.reload();
                } else {
                    alert('Gagal menyimpan ID SINTA di server.');
                }
            })
            .catch(() => {
                alert('Terjadi kesalahan saat menghubungi server.');
            });
        };

        window.saveSintaIdManual = function(id) {
            const manualId = document.getElementById('sinta-id-input-manual').value;
            if (!manualId) return;
            
            const savedSintaIds = JSON.parse(localStorage.getItem('sintaIds') || '{}');
            savedSintaIds[id] = manualId;
            localStorage.setItem('sintaIds', JSON.stringify(savedSintaIds));
            
            fetch('/update-lecturer', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ id: id, sintaId: manualId })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('ID SINTA berhasil disimpan di server!');
                    location.reload();
                } else {
                    alert('Gagal menyimpan ID SINTA di server.');
                }
            })
            .catch(() => {
                alert('Terjadi kesalahan saat menghubungi server.');
            });
        };

        window.showManualInput = function(id) {
            const statusEl = document.getElementById('sinta-detection-status');
            const actionsEl = document.getElementById('sinta-confirm-actions');
            
            statusEl.innerHTML = `
                <div style="color: var(--primary); margin-bottom: 0.5rem;"><i class="fas fa-info-circle"></i> Silakan masukkan ID Dosen secara manual.</div>
                <div style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.5rem;">Silakan masukkan 7 digit ID yang ada di URL tab baru:</div>
                <input type="number" id="sinta-id-input-manual" style="width:100%; padding:0.5rem; border:1px solid var(--border-glass); border-radius:8px; margin-bottom:0.5rem;" placeholder="Contoh: 5973273">
                <button onclick="window.saveSintaIdManual('${id}')" style="width:100%; background:var(--primary); color:white; border:none; padding:0.6rem; border-radius:8px; font-weight:600; cursor:pointer;">Simpan Manual</button>
            `;
            actionsEl.style.display = 'none';
        };

        window.updateStatus = function(id, status) {
            const savedStatuses = JSON.parse(localStorage.getItem('lecturerStatuses') || '{}');
            savedStatuses[id] = status;
            localStorage.setItem('lecturerStatuses', JSON.stringify(savedStatuses));
            
            fetch('/update-lecturer', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ id: id, status: status })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('Status dosen berhasil diperbarui di server!');
                    location.reload();
                } else {
                    alert('Gagal memperbarui status di server.');
                }
            })
            .catch(() => {
                alert('Terjadi kesalahan saat menghubungi server.');
            });
        };
    </script>
</body>
</html>
