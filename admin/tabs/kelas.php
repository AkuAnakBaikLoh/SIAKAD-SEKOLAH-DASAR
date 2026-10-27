<?php
// admin/tabs/kelas.php
$db = $db ?? getDB();

$kelasList = $db->query("
    SELECT k.*, COUNT(DISTINCT s.id) as jml_siswa, g.nama as wali_nama
    FROM kelas k
    LEFT JOIN siswa s ON s.kelas_id = k.id AND s.aktif = 1
    LEFT JOIN guru g ON g.id = k.wali_kelas
    GROUP BY k.id ORDER BY k.tingkat, k.nama_kelas
")->fetchAll();

$guruList = $db->query("SELECT id, nama FROM guru WHERE aktif = 1 ORDER BY nama")->fetchAll();

// Ambil semua penugasan guru-mapel per kelas
$guruKelasAll = $db->query("
    SELECT gk.kelas_id, gk.guru_id, gk.mapel, g.nama as nama_guru
    FROM guru_kelas gk
    JOIN guru g ON g.id = gk.guru_id
    ORDER BY g.nama, gk.mapel
")->fetchAll();

// Index per kelas_id
$guruKelasMap = [];
foreach ($guruKelasAll as $gk) {
    $guruKelasMap[$gk['kelas_id']][] = $gk;
}

// Hitung statistik per tingkat
$statTingkat = [];
$tingkatMap  = ['I'=>'1','II'=>'2','III'=>'3','IV'=>'4','V'=>'5','VI'=>'6'];
foreach ($kelasList as $k) {
    $t = $k['tingkat'];
    if (!isset($statTingkat[$t])) $statTingkat[$t] = ['kelas' => 0, 'siswa' => 0];
    $statTingkat[$t]['kelas']++;
    $statTingkat[$t]['siswa'] += $k['jml_siswa'];
}
?>

<div class="dash-header">
  <div class="dash-breadcrumb">Admin <span>/ Manajemen Kelas</span></div>
  <h2>🏫 Manajemen Kelas</h2>
  <p>Buat kelas baru, tetapkan wali kelas, dan atur guru pengajar serta mata pelajaran setiap kelas</p>
</div>

<!-- TABEL DETAIL -->
<div class="tabel-wrap" style="margin-top:1rem;">
  <div class="tabel-header">
    <h3>Rekap Semua Kelas</h3>
    <div class="tabel-aksi">
      <input class="input-cari" placeholder="🔍 Cari kelas..." oninput="filterTabel(this,'tabelKelas')">
      <button class="btn btn-orange" onclick="bukaModal_TambahKelas()">➕ Tambah Kelas</button>
    </div>
  </div>
  <div class="tabel-scroll">
    <table id="tabelKelas">
      <thead>
        <tr>
          <th>Kelas</th><th>Tingkat</th><th>Siswa</th><th>Wali Kelas</th>
          <th>Guru Pengajar & Mapel</th><th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($kelasList as $k):
          $penugasan = $guruKelasMap[$k['id']] ?? [];
        ?>
        <tr>
          <td><strong><?= e($k['nama_kelas']) ?></strong></td>
          <td><span class="badge badge-hijau">Kelas <?= $tingkatMap[$k['tingkat']] ?? e($k['tingkat']) ?></span></td>
          <td><?= $k['jml_siswa'] ?> siswa</td>
          <td><?= $k['wali_nama'] ? e($k['wali_nama']) : '<span style="color:var(--abu)">—</span>' ?></td>
        <td>
            <?php if(empty($penugasan)): ?>
              <span style="color:var(--abu);font-size:12px;">— Belum diatur —</span>
            <?php else: ?>
              <div style="display:flex;flex-wrap:wrap;gap:4px;">
                <?php foreach(array_slice($penugasan,0,4) as $p): ?>
                <span style="background:var(--hijau-muda);color:var(--hijau);font-size:11px;padding:2px 7px;border-radius:10px;white-space:nowrap;"
                      title="<?= e($p['nama_guru']) ?>">
                  <?= e(explode(' ',$p['nama_guru'])[0]) ?> — <?= e($p['mapel']) ?>
                </span>
                <?php endforeach; ?>
                <?php if(count($penugasan)>4): ?>
                <span style="background:var(--abu-muda);color:var(--abu);font-size:11px;padding:2px 7px;border-radius:10px;">+<?= count($penugasan)-4 ?> lagi</span>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </td>
          <td style="white-space:nowrap;">
            <button class="btn btn-outline btn-sm" onclick='bukaEditKelas(<?= json_encode($k) ?>)'>✏️ Edit</button>
            <button class="btn btn-sm" style="background:var(--hijau-muda);color:var(--hijau);border:none;"
                    onclick='bukaAturGuru(<?= json_encode(['id'=>$k['id'],'nama_kelas'=>$k['nama_kelas'],'penugasan'=>$penugasan]) ?>)'>
              👩‍🏫 Guru
            </button>
            <a href="actions/hapus-kelas.php?id=<?= $k['id'] ?>" 
               class="btn btn-merah btn-sm"
               onclick="return konfirmasiHapusKelas('<?= e($k['nama_kelas']) ?>',<?= $k['jml_siswa'] ?>)">🗑️</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ============================================================ -->
<!-- MODAL TAMBAH KELAS -->
<!-- ============================================================ -->
<div class="modal-bg" id="modalTambahKelas">
  <div class="modal" style="max-width:480px;">
    <div class="modal-judul">
      <h3>➕ Tambah Kelas Baru</h3>
      <button class="modal-tutup" onclick="tutupModal('modalTambahKelas')">✕</button>
    </div>
    <form action="actions/simpan-kelas.php" method="POST">
      <input type="hidden" name="aksi" value="tambah">
      <div class="form-grid-2">
        <div class="form-grup">
          <label>Tingkat Kelas <span style="color:var(--merah)">*</span></label>
          <select name="tingkat" id="tambahTingkat" class="form-kontrol" onchange="generateNamaKelas()" required>
            <option value="">— Pilih Tingkat —</option>
            <option value="I">1 (Satu)</option>
            <option value="II">2 (Dua)</option>
            <option value="III">3 (Tiga)</option>
            <option value="IV">4 (Empat)</option>
            <option value="V">5 (Lima)</option>
            <option value="VI">6 (Enam)</option>
          </select>
        </div>
        <div class="form-grup">
          <label>Nama Kelas <span style="color:var(--merah)">*</span></label>
          <div style="display:flex;gap:6px;align-items:center;">
            <input type="text" name="nama_kelas" id="tambahNamaKelas" class="form-kontrol" 
                   placeholder="Contoh: 1-A" required maxlength="20">
            <button type="button" class="btn btn-sm btn-outline" onclick="generateNamaKelas()" title="Generate otomatis">🔄</button>
          </div>
          <div style="font-size:11px;color:var(--abu);margin-top:4px;">Atau ketik manual (contoh: 1-A, 2-B)</div>
        </div>
        <div class="form-grup form-full">
          <label>Wali Kelas</label>
          <select name="wali_kelas" class="form-kontrol">
            <option value="">— Belum ada wali kelas —</option>
            <?php foreach($guruList as $g): ?>
            <option value="<?= $g['id'] ?>"><?= e($g['nama']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

      </div>
      <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:1rem;">
        <button type="button" class="btn btn-abu" onclick="tutupModal('modalTambahKelas')">Batal</button>
        <button type="submit" class="btn btn-hijau">🏫 Buat Kelas</button>
      </div>
    </form>
  </div>
</div>

<!-- ============================================================ -->
<!-- MODAL EDIT KELAS (Nama + Wali Kelas) -->
<!-- ============================================================ -->
<div class="modal-bg" id="modalEditKelas">
  <div class="modal" style="max-width:480px;">
    <div class="modal-judul">
      <h3>✏️ Edit Kelas</h3>
      <button class="modal-tutup" onclick="tutupModal('modalEditKelas')">✕</button>
    </div>
    <form action="actions/simpan-kelas.php" method="POST">
      <input type="hidden" name="aksi" value="edit">
      <input type="hidden" name="id" id="editKelasId">
      <div class="form-grid-2">
        <div class="form-grup">
          <label>Tingkat</label>
          <input type="text" id="editKelasTingkat" class="form-kontrol" readonly style="background:var(--abu-muda);">
        </div>
        <div class="form-grup">
          <label>Nama Kelas <span style="color:var(--merah)">*</span></label>
          <input type="text" name="nama_kelas" id="editKelasNama" class="form-kontrol" required maxlength="20">
        </div>
        <div class="form-grup form-full">
          <label>Wali Kelas</label>
          <select name="wali_kelas" id="editKelasWali" class="form-kontrol">
            <option value="">— Belum ada wali kelas —</option>
            <?php foreach($guruList as $g): ?>
            <option value="<?= $g['id'] ?>"><?= e($g['nama']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

      </div>
      <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:1rem;">
        <button type="button" class="btn btn-abu" onclick="tutupModal('modalEditKelas')">Batal</button>
        <button type="submit" class="btn btn-hijau">💾 Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>

<!-- ============================================================ -->
<!-- MODAL ATUR GURU PENGAJAR (Multi Guru + Mapel) -->
<!-- ============================================================ -->
<div class="modal-bg" id="modalAturGuru">
  <div class="modal" style="max-width:640px;">
    <div class="modal-judul">
      <h3>👩‍🏫 Atur Guru & Mata Pelajaran — <span id="labelNamaKelasGuru" style="color:var(--orange);"></span></h3>
      <button class="modal-tutup" onclick="tutupModal('modalAturGuru')">✕</button>
    </div>
    <form action="actions/atur-guru-kelas.php" method="POST" id="formAturGuru" onsubmit="return validasiFormGuru()">
      <input type="hidden" name="kelas_id" id="aturGuruKelasId">

      <div style="background:var(--hijau-muda);border-left:4px solid var(--hijau);border-radius:6px;padding:.6rem .9rem;margin-bottom:.8rem;font-size:12px;color:var(--hijau);">
        💡 <strong>Satu guru boleh mengajar beberapa mata pelajaran.</strong> Tambah baris baru dan pilih guru yang sama dengan mata pelajaran berbeda.
      </div>

      <!-- Daftar baris penugasan -->
      <div id="listPenugasan" style="margin-bottom:1rem;display:flex;flex-direction:column;gap:.5rem;">
        <!-- Diisi oleh JavaScript -->
      </div>

      <!-- Tombol tambah baris -->
      <button type="button" class="btn btn-sm" style="background:var(--hijau-muda);color:var(--hijau);border:1px dashed var(--hijau);width:100%;margin-bottom:1rem;"
              onclick="tambahBarisPenugasan()">
        ➕ Tambah Guru & Mata Pelajaran
      </button>

      <!-- Ringkasan -->
      <div style="padding:.6rem .8rem;background:var(--hijau-muda);border-radius:8px;font-size:13px;color:var(--hijau);margin-bottom:1rem;">
        <span id="ringkasanPenugasan">0 penugasan</span>
      </div>

      <div style="display:flex;gap:8px;justify-content:flex-end;">
        <button type="button" class="btn btn-abu" onclick="tutupModal('modalAturGuru')">Batal</button>
        <button type="submit" class="btn btn-hijau">💾 Simpan Pengaturan Guru</button>
      </div>
    </form>
  </div>
</div>

<style>
.kelas-kartu { transition: transform .2s, box-shadow .2s; }
.kelas-kartu:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(26,92,58,.12); }

.baris-penugasan {
  display: flex;
  gap: 8px;
  align-items: center;
  background: var(--abu-muda);
  border: 1px solid var(--border);
  border-radius: 8px;
  padding: .5rem .75rem;
}
.baris-penugasan select,
.baris-penugasan input {
  flex: 1;
  padding: 6px 10px;
  border: 1px solid var(--border);
  border-radius: 6px;
  font-size: 13px;
  background: var(--putih);
  color: var(--teks);
}
.baris-penugasan select { min-width: 160px; max-width: 200px; }
.baris-penugasan .inp-mapel { min-width: 120px; max-width: 160px; }
.btn-hapus-baris {
  background: none;
  border: none;
  color: var(--merah);
  cursor: pointer;
  font-size: 18px;
  padding: 0 4px;
  line-height: 1;
  flex-shrink: 0;
}
.btn-hapus-baris:hover { opacity: .7; }
</style>

<script>
// Data guru untuk dropdown
const daftarGuru = <?= json_encode(array_map(fn($g) => ['id'=>$g['id'],'nama'=>$g['nama']], $guruList)) ?>;
let barisCounter = 0;

// =========================================================
// FILTER TABEL
// =========================================================
function filterTabel(input, tabelId) {
  const q = input.value.toLowerCase();
  document.querySelectorAll('#' + tabelId + ' tbody tr').forEach(tr => {
    tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
}



// =========================================================
// TAMBAH KELAS — Auto-generate nama kelas
// =========================================================
function generateNamaKelas() {
  const tingkat = document.getElementById('tambahTingkat').value;
  if (!tingkat) return;

  const existingKelas = <?= json_encode(array_column($kelasList, 'nama_kelas')) ?>;
  const kelasTingkat = existingKelas.filter(n => n.startsWith(tingkat + '-'));
  
  const huruf = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.split('');
  let suggested = '';
  for (const h of huruf) {
    const calonNama = tingkat + '-' + h;
    if (!kelasTingkat.includes(calonNama)) { suggested = calonNama; break; }
  }
  document.getElementById('tambahNamaKelas').value = suggested;
}

// =========================================================
// EDIT KELAS
// =========================================================
function bukaModal_TambahKelas() {
  bukaModal('modalTambahKelas');
}

function bukaEditKelas(k) {
  document.getElementById('editKelasId').value      = k.id;
  document.getElementById('editKelasNama').value    = k.nama_kelas;
  document.getElementById('editKelasTingkat').value = k.tingkat;
  document.getElementById('editKelasWali').value    = k.wali_kelas || '';
  bukaModal('modalEditKelas');
}

// =========================================================
// ATUR GURU PENGAJAR (Multi Guru + Mapel)
// =========================================================
function bukaAturGuru(data) {
  document.getElementById('aturGuruKelasId').value = data.id;
  document.getElementById('labelNamaKelasGuru').textContent = data.nama_kelas;

  // Bersihkan list
  const list = document.getElementById('listPenugasan');
  list.innerHTML = '';
  barisCounter = 0;

  // Isi dari data penugasan yang ada
  if (data.penugasan && data.penugasan.length > 0) {
    data.penugasan.forEach(p => {
      tambahBarisPenugasan(p.guru_id, p.mapel);
    });
  } else {
    // Baris kosong pertama
    tambahBarisPenugasan();
  }

  updateRingkasan();
  bukaModal('modalAturGuru');
}

function tambahBarisPenugasan(guruIdAwal = '', mapelAwal = '') {
  const idx = barisCounter++;
  const list = document.getElementById('listPenugasan');

  // Build option guru
  let opsiGuru = '<option value="">— Pilih Guru —</option>';
  daftarGuru.forEach(g => {
    const sel = String(g.id) === String(guruIdAwal) ? ' selected' : '';
    opsiGuru += `<option value="${g.id}"${sel}>${escHtml(g.nama)}</option>`;
  });

  const div = document.createElement('div');
  div.className = 'baris-penugasan';
  div.dataset.idx = idx;
  div.innerHTML = `
    <select name="guru_mapel[${idx}][guru_id]" onchange="cekDuplikatBaris()" style="flex:1;">
      ${opsiGuru}
    </select>
    <input type="text" name="guru_mapel[${idx}][mapel]" class="inp-mapel"
           value="${escHtml(mapelAwal)}" placeholder="Nama mata pelajaran"
           style="flex:1;" oninput="cekDuplikatBaris()">
    <button type="button" class="btn-hapus-baris" onclick="hapusBaris(this)" title="Hapus baris">✕</button>
  `;
  list.appendChild(div);
  cekDuplikatBaris();
  updateRingkasan();
}

function isiMapelOtomatis(sel, idx) {
  cekDuplikatBaris();
  updateRingkasan();
}

function hapusBaris(btn) {
  btn.closest('.baris-penugasan').remove();
  cekDuplikatBaris();
  updateRingkasan();
}

// Deteksi duplikat: guru + mapel sama persis (case-insensitive)
function cekDuplikatBaris() {
  const rows = Array.from(document.querySelectorAll('#listPenugasan .baris-penugasan'));
  // Kumpulkan semua nilai
  const kunci = rows.map(r => {
    const guruId = r.querySelector('select').value;
    const mapel  = r.querySelector('input[type="text"]').value.trim().toLowerCase();
    return guruId && mapel ? guruId + '|' + mapel : '';
  });

  rows.forEach((r, i) => {
    const sel   = r.querySelector('select');
    const inp   = r.querySelector('input[type="text"]');
    const isDup = kunci[i] !== '' && kunci.indexOf(kunci[i]) !== i;

    // Visual: border merah pada baris duplikat
    r.style.borderColor    = isDup ? 'var(--merah)' : '';
    r.style.background     = isDup ? '#fff5f5' : '';
    inp.style.borderColor  = isDup ? 'var(--merah)' : '';
    inp.title              = isDup ? '⚠️ Kombinasi guru & mata pelajaran ini sudah ada di baris lain!' : '';
  });
}

// Validasi sebelum submit form
function validasiFormGuru() {
  const rows = Array.from(document.querySelectorAll('#listPenugasan .baris-penugasan'));
  const kunci = [];
  let adaKosong = false;
  let adaDuplikat = false;

  rows.forEach(r => {
    const guruId = r.querySelector('select').value;
    const mapel  = r.querySelector('input[type="text"]').value.trim();

    // Cek baris yang sebagian terisi (guru ada tapi mapel kosong atau sebaliknya)
    if ((guruId && !mapel) || (!guruId && mapel)) {
      adaKosong = true;
    }

    if (guruId && mapel) {
      const k = guruId + '|' + mapel.toLowerCase();
      if (kunci.includes(k)) {
        adaDuplikat = true;
      } else {
        kunci.push(k);
      }
    }
  });

  if (adaKosong) {
    alert('⚠️ Lengkapi semua baris: setiap guru harus memiliki mata pelajaran, dan setiap mata pelajaran harus memiliki guru.');
    return false;
  }
  if (adaDuplikat) {
    alert('❌ Terdapat kombinasi guru dan mata pelajaran yang sama. Hapus atau ubah baris yang duplikat sebelum menyimpan.');
    return false;
  }
  return true;
}

function updateRingkasan() {
  const rows = document.querySelectorAll('#listPenugasan .baris-penugasan');
  let valid = 0;
  let duplikat = 0;
  const seen = new Set();
  rows.forEach(r => {
    const guruId = r.querySelector('select').value;
    const mapel  = r.querySelector('input[type="text"]').value.trim();
    if (guruId && mapel) {
      const k = guruId + '|' + mapel.toLowerCase();
      if (seen.has(k)) { duplikat++; } else { seen.add(k); valid++; }
    }
  });
  let teks = valid === 0
    ? '0 penugasan — menyimpan akan menghapus semua guru kelas ini'
    : `${valid} penugasan guru-mapel`;
  if (duplikat > 0) teks += ` ⚠️ (${duplikat} duplikat akan diabaikan)`;
  document.getElementById('ringkasanPenugasan').textContent = teks;
}

function escHtml(str) {
  if (!str) return '';
  return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// =========================================================
// KONFIRMASI HAPUS KELAS
// =========================================================
function konfirmasiHapusKelas(namaKelas, jmlSiswa) {
  if (jmlSiswa > 0) {
    alert(`❌ Kelas "${namaKelas}" tidak dapat dihapus karena masih memiliki ${jmlSiswa} siswa aktif.\n\nPindahkan atau nonaktifkan siswa terlebih dahulu.`);
    return false;
  }
  return confirm(`Hapus kelas "${namaKelas}"?\n\nSemua data guru pengajar di kelas ini juga akan dihapus.`);
}
</script>
