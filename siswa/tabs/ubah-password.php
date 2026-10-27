<?php
// siswa/tabs/ubah-password.php
// (Proses POST ditangani di dashboard.php sebelum HTML output)
$siswaId = $siswaData['id'];
?>


<style>
  .ubah-pw-wrap {
    max-width: 520px;
    margin: 0 auto;
  }
  .pw-card {
    background: var(--putih);
    border: 1px solid var(--abu-muda);
    border-radius: var(--radius);
    overflow: hidden;
  }
  .pw-card-header {
    background: linear-gradient(135deg, #1a3a5c 0%, #1a5c3a 100%);
    padding: 1.75rem 2rem;
    color: #fff;
    display: flex;
    align-items: center;
    gap: 1rem;
  }
  .pw-card-header-icon {
    width: 52px;
    height: 52px;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
    border: 2px solid rgba(255,255,255,0.3);
    backdrop-filter: blur(4px);
  }
  .pw-card-header h3 { font-size: 1.15rem; margin-bottom: .2rem; }
  .pw-card-header p  { font-size: .85rem; opacity: .8; }
  .pw-card-body { padding: 2rem; }

  .pw-form-grup {
    margin-bottom: 1.25rem;
    position: relative;
  }
  .pw-form-grup label {
    display: block;
    font-size: .85rem;
    font-weight: 600;
    color: var(--abu-gelap);
    margin-bottom: .4rem;
  }
  .pw-input-wrap {
    position: relative;
  }
  .pw-input-wrap input {
    width: 100%;
    padding: .7rem 2.8rem .7rem .95rem;
    border: 1.5px solid var(--abu-muda);
    border-radius: var(--radius-sm);
    font-size: .95rem;
    transition: border-color .2s, box-shadow .2s;
    background: var(--putih);
    box-sizing: border-box;
  }
  .pw-input-wrap input:focus {
    outline: none;
    border-color: var(--hijau);
    box-shadow: 0 0 0 3px rgba(26,92,58,.1);
  }
  .pw-toggle-btn {
    position: absolute;
    right: .75rem;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
    font-size: 1rem;
    color: var(--abu);
    padding: 0;
    line-height: 1;
    transition: color .2s;
  }
  .pw-toggle-btn:hover { color: var(--hijau); }

  .pw-strength {
    margin-top: .4rem;
    display: none;
  }
  .pw-strength.tampil { display: block; }
  .pw-strength-bar-wrap {
    height: 5px;
    background: var(--abu-muda);
    border-radius: 3px;
    overflow: hidden;
    margin-bottom: .3rem;
  }
  .pw-strength-bar {
    height: 100%;
    border-radius: 3px;
    transition: width .4s ease, background .4s ease;
    width: 0%;
  }
  .pw-strength-teks { font-size: .78rem; font-weight: 600; }

  .pw-tips {
    background: #f0f7f4;
    border: 1px solid #c3e6d4;
    border-radius: var(--radius-sm);
    padding: 1rem 1.25rem;
    margin-bottom: 1.5rem;
  }
  .pw-tips h5 {
    font-size: .82rem;
    font-weight: 700;
    color: var(--hijau);
    margin-bottom: .5rem;
    text-transform: uppercase;
    letter-spacing: .05em;
  }
  .pw-tips ul {
    margin: 0;
    padding-left: 1.25rem;
    font-size: .82rem;
    color: var(--abu-gelap);
    line-height: 1.7;
  }

  .pw-alert-error {
    background: #fdf0ef;
    border: 1px solid #f5c6c2;
    border-left: 4px solid var(--merah);
    border-radius: var(--radius-sm);
    padding: .85rem 1.1rem;
    color: #c0392b;
    font-size: .9rem;
    margin-bottom: 1.25rem;
    display: flex;
    align-items: center;
    gap: .5rem;
  }

  .pw-submit-btn {
    width: 100%;
    padding: .85rem;
    background: linear-gradient(135deg, #1a5c3a, #0d7a4e);
    color: #fff;
    border: none;
    border-radius: var(--radius-sm);
    font-size: .95rem;
    font-weight: 700;
    cursor: pointer;
    transition: transform .2s, box-shadow .2s, opacity .2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: .5rem;
    margin-top: .5rem;
  }
  .pw-submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(26,92,58,.35);
  }
  .pw-submit-btn:active { transform: translateY(0); }

  .pw-match-ok  { color: #1a5c3a; font-size: .78rem; font-weight: 600; margin-top: .3rem; display: none; }
  .pw-match-err { color: var(--merah); font-size: .78rem; font-weight: 600; margin-top: .3rem; display: none; }
  .pw-match-ok.tampil, .pw-match-err.tampil { display: block; }
</style>

<!-- UBAH PASSWORD -->
<div class="siswa-hero">
  <div class="siswa-hero-avatar">🔐</div>
  <div class="siswa-hero-info">
    <h2>Ubah Password</h2>
    <p>Kelola keamanan akun Anda</p>
    <div class="siswa-hero-badges">
      <span class="siswa-badge">👤 <?= e($siswaData['nama']) ?></span>
      <span class="siswa-badge">🔑 <?= e($siswaData['username']) ?></span>
    </div>
  </div>
</div>

<div class="ubah-pw-wrap">
  <div class="pw-card">
    <div class="pw-card-header">
      <div class="pw-card-header-icon">🔒</div>
      <div>
        <h3>Ganti Password Akun</h3>
        <p>Pastikan password baru Anda kuat dan mudah diingat</p>
      </div>
    </div>
    <div class="pw-card-body">

      <?php if ($flash && $flash['type'] === 'gagal'): ?>
      <div class="pw-alert-error">
        <span>❌</span> <?= e($flash['msg']) ?>
      </div>
      <?php endif; ?>

      <div class="pw-tips">
        <h5>💡 Tips Password Aman</h5>
        <ul>
          <li>Gunakan minimal 6 karakter</li>
          <li>Kombinasikan huruf besar, huruf kecil, dan angka</li>
        </ul>
      </div>

      <form method="POST" action="?tab=ubah-password" id="formUbahPassword" novalidate>
        <input type="hidden" name="aksi" value="ubah_password">

        <!-- Password Lama -->
        <div class="pw-form-grup">
          <label for="password_lama">🔑 Password Lama</label>
          <div class="pw-input-wrap">
            <input type="password" id="password_lama" name="password_lama"
                   placeholder="Masukkan password saat ini" required autocomplete="current-password">
            <button type="button" class="pw-toggle-btn" onclick="togglePw('password_lama', this)" title="Tampilkan/sembunyikan">👁️</button>
          </div>
        </div>

        <!-- Password Baru -->
        <div class="pw-form-grup">
          <label for="password_baru">🔏 Password Baru</label>
          <div class="pw-input-wrap">
            <input type="password" id="password_baru" name="password_baru"
                   placeholder="Masukkan password baru" required autocomplete="new-password"
                   oninput="cekKekuatan(this.value); cekKesesuaian()">
            <button type="button" class="pw-toggle-btn" onclick="togglePw('password_baru', this)" title="Tampilkan/sembunyikan">👁️</button>
          </div>
          <div class="pw-strength tampil" id="pwStrength">
            <div class="pw-strength-bar-wrap">
              <div class="pw-strength-bar" id="pwStrengthBar"></div>
            </div>
            <span class="pw-strength-teks" id="pwStrengthTeks"></span>
          </div>
        </div>

        <!-- Konfirmasi Password -->
        <div class="pw-form-grup">
          <label for="konfirmasi">✅ Konfirmasi Password Baru</label>
          <div class="pw-input-wrap">
            <input type="password" id="konfirmasi" name="konfirmasi"
                   placeholder="Ulangi password baru" required autocomplete="new-password"
                   oninput="cekKesesuaian()">
            <button type="button" class="pw-toggle-btn" onclick="togglePw('konfirmasi', this)" title="Tampilkan/sembunyikan">👁️</button>
          </div>
          <div class="pw-match-ok"  id="matchOk">✅ Password cocok!</div>
          <div class="pw-match-err" id="matchErr">❌ Password tidak cocok</div>
        </div>

        <button type="submit" class="pw-submit-btn" id="submitBtn">
          <span>🔐</span> Simpan Password Baru
        </button>
      </form>

    </div>
  </div>
</div>

<script>
function togglePw(fieldId, btn) {
  const input = document.getElementById(fieldId);
  if (input.type === 'password') {
    input.type = 'text';
    btn.textContent = '🙈';
  } else {
    input.type = 'password';
    btn.textContent = '👁️';
  }
}


function cekKesesuaian() {
  const baru      = document.getElementById('password_baru').value;
  const konfirm   = document.getElementById('konfirmasi').value;
  const matchOk   = document.getElementById('matchOk');
  const matchErr  = document.getElementById('matchErr');

  if (!konfirm) {
    matchOk.classList.remove('tampil');
    matchErr.classList.remove('tampil');
    return;
  }
  if (baru === konfirm) {
    matchOk.classList.add('tampil');
    matchErr.classList.remove('tampil');
  } else {
    matchErr.classList.add('tampil');
    matchOk.classList.remove('tampil');
  }
}

// Cegah submit jika konfirmasi tidak cocok
document.getElementById('formUbahPassword').addEventListener('submit', function(e) {
  const baru    = document.getElementById('password_baru').value;
  const konfirm = document.getElementById('konfirmasi').value;
  if (baru !== konfirm) {
    e.preventDefault();
    document.getElementById('matchErr').classList.add('tampil');
    document.getElementById('matchOk').classList.remove('tampil');
  }
});
</script>
