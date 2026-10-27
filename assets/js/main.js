// assets/js/main.js — JavaScript Utama Web Sekolah

// ===== NOTIFIKASI TOAST =====
function tampilNotif(pesan, tipe = 'sukses') {
  const el = document.getElementById('notifToast');
  if (!el) return;
  const ikon = { sukses: '✅', error: '❌', info: 'ℹ️' }[tipe] || 'ℹ️';
  el.innerHTML = ikon + ' ' + pesan;
  el.className = 'notif-toast notif-' + tipe + ' muncul';
  setTimeout(() => el.classList.remove('muncul'), 4000);
}

// ===== MODAL =====
function bukaModal(id) {
  const el = document.getElementById(id);
  if (el) el.classList.add('terbuka');
}
function tutupModal(id) {
  const el = document.getElementById(id);
  if (el) el.classList.remove('terbuka');
}
// Tutup modal saat klik luar
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.modal-bg').forEach(m => {
    m.addEventListener('click', e => {
      if (e.target === m) m.classList.remove('terbuka');
    });
  });

  // Tutup flash message
  document.querySelectorAll('.flash-msg').forEach(el => {
    setTimeout(() => el.style.opacity = '0', 4000);
    setTimeout(() => el.remove(), 4500);
  });
});

// ===== FILTER TABEL =====
function filterTabel(input, tabelId) {
  const q = input.value.toLowerCase();
  const rows = document.querySelectorAll('#' + tabelId + ' tbody tr');
  rows.forEach(tr => {
    tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
}

// ===== PREVIEW FOTO UPLOAD =====
function previewFoto(input, previewId) {
  const file = input.files[0];
  if (!file) return;
  if (file.size > 5 * 1024 * 1024) {
    alert('Ukuran file terlalu besar! Maksimum 5MB.');
    input.value = '';
    return;
  }
  const allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
  if (!allowed.includes(file.type)) {
    alert('Tipe file tidak diizinkan. Gunakan JPG, PNG, atau WEBP.');
    input.value = '';
    return;
  }
  const reader = new FileReader();
  reader.onload = e => {
    const img = document.getElementById(previewId);
    if (img) { img.src = e.target.result; img.style.display = 'block'; }
  };
  reader.readAsDataURL(file);
}

// ===== DRAG & DROP UPLOAD =====
function initDropZone(zoneId, inputId, previewId) {
  const zone = document.getElementById(zoneId);
  if (!zone) return;
  zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('dragover'); });
  zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
  zone.addEventListener('drop', e => {
    e.preventDefault();
    zone.classList.remove('dragover');
    const file = e.dataTransfer.files[0];
    if (!file) return;
    const input = document.getElementById(inputId);
    if (!input) return;
    const dt = new DataTransfer();
    dt.items.add(file);
    input.files = dt.files;
    previewFoto(input, previewId);
  });
}

// ===== HITUNG NILAI RATA-RATA (Inline) =====
function hitungRataRow(siswaId) {
  const row = document.getElementById('row_' + siswaId);
  if (!row) return;
  const inputs = row.querySelectorAll('.nilai-input');
  const vals   = Array.from(inputs).map(i => i.value).filter(v => v !== '').map(Number);
  const rataEl = document.getElementById('rata_' + siswaId);
  const predEl = document.getElementById('pred_' + siswaId);
  if (!rataEl) return;

  if (vals.length === 0) {
    rataEl.innerHTML = '<span style="color:var(--abu)">-</span>';
    if (predEl) predEl.innerHTML = '<span style="color:var(--abu)">-</span>';
    return;
  }
  const rata = (vals.reduce((a, b) => a + b, 0) / vals.length).toFixed(1);
  const cls  = rata >= 80 ? 'nilai-bagus' : rata >= 70 ? 'nilai-cukup' : 'nilai-kurang';
  rataEl.innerHTML = `<strong class="${cls}">${rata}</strong>`;

  if (predEl) {
    let pred = 'E';
    if      (rata >= 90) pred = 'A';
    else if (rata >= 80) pred = 'B';
    else if (rata >= 70) pred = 'C';
    else if (rata >= 60) pred = 'D';
    const bMap = { A: 'badge-hijau', B: 'badge-biru', C: 'badge-orange', D: 'badge-merah', E: 'badge-merah' };
    predEl.innerHTML = `<span class="badge ${bMap[pred] || 'badge-abu'}">${pred}</span>`;
  }
}

// ===== WARNA BARIS ABSENSI =====
function warnaBaris(sel) {
  const tr = sel.closest('tr');
  if (!tr) return;
  const colors = { Hadir: '', Sakit: '#fffde7', Izin: '#e8f0fb', Alpa: '#fdecea' };
  tr.style.background = colors[sel.value] || '';
}

// ===== ISI SEMUA STATUS ABSENSI =====
function isiSemua(status) {
  document.querySelectorAll('.status-select').forEach(sel => {
    sel.value = status;
    warnaBaris(sel);
  });
}

// ===== KONFIRMASI HAPUS =====
function konfirmasiHapus(url, namaItem) {
  if (confirm(`Yakin ingin menghapus "${namaItem}"? Tindakan ini tidak dapat dibatalkan.`)) {
    window.location.href = url;
  }
}

// ===== PRINT / CETAK =====
function cetakHalaman() {
  window.print();
}

// ===== AUTO-SUBMIT FORM FILTER =====
document.addEventListener('DOMContentLoaded', () => {
  // Warna awal baris absensi
  document.querySelectorAll('.status-select').forEach(sel => warnaBaris(sel));

  // Init drop zones
  initDropZone('dropZoneGaleri', 'fotoGaleri', 'previewGaleri');
  initDropZone('dropZoneGuru', 'fotoGuru', 'previewTambahGuru');
});

// ===== SMOOTH SCROLL =====
document.querySelectorAll('a[href^="#"]').forEach(a => {
  a.addEventListener('click', e => {
    const target = document.querySelector(a.getAttribute('href'));
    if (target) {
      e.preventDefault();
      target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  });
});
