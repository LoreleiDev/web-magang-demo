# CLAUDE.md — MagangBridge SMK

Dokumen ini adalah acuan utama untuk membangun website **MagangBridge SMK**.
Baca seluruh dokumen ini sebelum menulis kode. Jika ada hal yang tidak tercantum
di sini atau masuk ke bagian 13.1 "Belum Ditentukan", tanyakan dulu ke pemilik
proyek, jangan diasumsikan sendiri.

---

## 1. Ringkasan Proyek

MagangBridge SMK adalah website pendamping siswa SMK selama Praktik Kerja Lapangan
(PKL/magang) di industri.

Masalah yang diselesaikan: kesenjangan (learning gap) antara

1. pengetahuan dan keterampilan yang dipelajari siswa di sekolah, dan
2. pengetahuan, keterampilan, peralatan, prosedur, dan budaya kerja di industri.

Website membantu siswa **mengidentifikasi gap kompetensi** dan memberikan
**pembelajaran penguatan** yang sesuai, didampingi AI Mentor Magang.

### 1.1 Tech Stack

| Bagian                      | Teknologi                                                                   |
| --------------------------- | --------------------------------------------------------------------------- |
| Backend                     | Laravel                                                                     |
| Penghubung frontend–backend | Inertia.js                                                                  |
| Frontend                    | React                                                                       |
| Styling                     | Tailwind CSS                                                                |
| Komponen UI                 | shadcn/ui                                                                   |
| Ikon                        | Lucide React (bawaan shadcn/ui, jangan campur dengan library ikon lain)     |
| Database                    | MySQL (konfigurasi koneksi dari `.env`)                                     |
| Provider AI                 | Google Gemini (API key di `.env`, misal `GEMINI_API_KEY`)                   |
| Email notifikasi            | Laravel Mail via SMTP (konfigurasi `MAIL_*` di `.env`), dikirim lewat queue |

---

## 2. Peran Pengguna (Role)

Ada 4 role. Semua akun dibuat oleh Superadmin (tidak ada registrasi mandiri).

| Role         | Ringkasan tugas                                                                                                                                                                                                                         |
| ------------ | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `superadmin` | Membuat akun guru, siswa, dan industri; mengelola data perusahaan beserta dokumen industrinya; membuat kelompok magang dan menetapkan guru pembimbing serta siswa ke kelompok.                                                          |
| `guru`       | Melihat siswa di kelompok magang yang ia bimbing (bisa lebih dari satu kelompok); input dan edit kompetensi; mengisi level kompetensi siswa; input dan edit materi; mengunggah dokumen sekolah; memantau progress, logbook, assessment. |
| `industri`   | Melihat siswa yang terdaftar magang di perusahaannya; memantau progress dan logbook; **memverifikasi kompetensi**; **memverifikasi materi dan memberi masukan** kepada guru pembuat materi.                                             |
| `siswa`      | Mengikuti pendampingan magang: dashboard, peta kompetensi, learning gap, belajar, AI Mentor, logbook, assessment, progress.                                                                                                             |

### 2.1 Matriks Hak Akses

| Fitur                                            | Superadmin | Guru                           | Industri                                   | Siswa                  |
| ------------------------------------------------ | ---------- | ------------------------------ | ------------------------------------------ | ---------------------- |
| Buat/edit/nonaktifkan akun guru, siswa, industri | ✅         | ❌                             | ❌                                         | ❌                     |
| Kelola data perusahaan                           | ✅         | ❌                             | ❌                                         | ❌                     |
| Unggah dokumen industri (knowledge base)         | ✅         | ❌                             | ❌                                         | ❌                     |
| Buat kelompok magang, tetapkan guru & siswa      | ✅         | ❌                             | ❌                                         | ❌                     |
| Lihat daftar siswa                               | Semua      | Hanya kelompok yang ia bimbing | Hanya siswa di perusahaannya               | Diri sendiri           |
| Input & edit kompetensi                          | ❌         | ✅                             | ❌                                         | ❌                     |
| Isi level kompetensi siswa (1–4)                 | Otomatis dari kuis (tidak ada yang mengisi manual) ||||
| Input & edit materi                              | ❌         | ✅                             | ❌                                         | ❌                     |
| Lihat materi                                     | ✅         | ✅                             | ✅ Program keahlian siswa di perusahaannya | ✅ Program keahliannya |
| Verifikasi materi & beri masukan                 | ❌         | ❌                             | ✅ Program keahlian siswa di perusahaannya | ❌                     |
| Unggah dokumen sekolah (knowledge base)          | ❌         | ✅                             | ❌                                         | ❌                     |
| Lihat logbook & assessment siswa                 | ✅         | Kelompoknya                    | Perusahaannya                              | Miliknya               |
| Verifikasi kompetensi                            | ❌         | ❌                             | ✅ Perusahaannya                           | ❌                     |
| Gunakan AI Mentor                                | ❌         | ❌                             | ❌                                         | ✅                     |

**Aturan penting:** pembatasan data di atas WAJIB ditegakkan di sisi backend
(Laravel Policy/Gate/Middleware), bukan hanya disembunyikan di tampilan.

---

## 3. Model Data (Entitas Utama)

Nama field bersifat panduan; sesuaikan dengan konvensi Laravel (migration & Eloquent).

```
User
  id, nama, email, password_hash, role (superadmin|guru|industri|siswa),
  status_aktif, created_at

ProfilSiswa
  user_id, id_siswa (NIS), program_keahlian, unit_kerja, kelompok_id,
  kompetensi_fokus_id (dipilih saat Mulai Pendampingan)
  -> perusahaan siswa DIAMBIL dari kelompok (kelompok.perusahaan_id),
     tidak disimpan terpisah
  -> tanggal mulai/selesai magang DIAMBIL dari periode kelompok

ProfilGuru
  user_id, nip (opsional), program_keahlian (opsional)

ProfilIndustri
  user_id, perusahaan_id, jabatan

Perusahaan
  id, nama, alamat, bidang_usaha, daftar_unit_kerja[]

KelompokMagang
  id, nama_kelompok, perusahaan_id, guru_pembimbing_id,
  periode_mulai, periode_selesai
  -> satu kelompok hanya punya SATU guru pembimbing
  -> satu guru boleh membimbing BANYAK kelompok

Kompetensi (diinput guru, umum per program keahlian)
  id, program_keahlian, nama_kompetensi_sekolah,
  aktivitas_kompetensi_industri, target_level (1–4), dibuat_oleh (guru_id)

ProgresKompetensi (per siswa per kompetensi)
  siswa_id, kompetensi_id,
  level_siswa (1–4, otomatis dari kuis), tanggal_level_diisi (kapan level berubah),
  status, diverifikasi_oleh (user industri), tanggal_verifikasi

Materi (umum, berlaku untuk semua siswa dengan program keahlian yang sama)
  id, kompetensi_id, judul, level (1–4), nilai_minimal (bawaan 75),
  dibuat_oleh (guru_id), diubah_terakhir,
  terverifikasi_industri (boolean, menentukan badge)
  langkah[] (8 langkah microlearning, lihat bagian 6.4), tiap langkah berisi:
    konten_teks,
    media[] -> { jenis (youtube|gambar|dokumen), url, keterangan (opsional) }
  -> media hanya berupa LINK, tidak ada upload file (lihat bagian 6.4.1)

VerifikasiMateri (riwayat pemeriksaan materi oleh industri)
  id, materi_id, diperiksa_oleh (user industri), perusahaan_id,
  hasil (diverifikasi|belum_sesuai), masukan (teks),
  email_terkirim (boolean), created_at

Kuis
  id, materi_id, soal[] (pertanyaan, pilihan, jawaban_benar, pembahasan)

HasilAssessment
  siswa_id, kuis_id, skor (%), tanggal, lulus (skor >= nilai_minimal materi)

Logbook
  id, siswa_id, tanggal, aktivitas, peralatan_software, sudah_dipahami,
  baru_ditemui, kesulitan, pengetahuan_sekolah_digunakan, ingin_dipelajari,
  bukti_kegiatan (file, opsional), hasil_analisis_ai (JSON, opsional)

DokumenKnowledgeBase
  id, jenis (sekolah|industri),
  program_keahlian (untuk jenis sekolah) / perusahaan_id (untuk jenis industri),
  nama_file, diunggah_oleh, id_di_layanan_ai
  -> jenis sekolah  : diunggah guru
  -> jenis industri : diunggah superadmin

ChatAIMentor
  id, siswa_id, pesan[], created_at
```

---

## 4. Autentikasi & Alur Masuk

- Login memakai **email + password** yang dibuat Superadmin. "Membuatkan email"
  berarti membuat **akun login berbasis email**, bukan membuat kotak surat email baru.
- Halaman login terpisah per role (keputusan 13 no. 32), tanpa tautan antar
  halaman login: `/login` siswa, `/login/guru`, `/login/industri`, `/login/admin`
  (superadmin). Akun yang masuk di halaman yang salah ditolak.
- Logout selalu meminta konfirmasi (SweetAlert2, keputusan 13 no. 33).
- Setelah login, user diarahkan sesuai role:
    - `superadmin` → Panel Superadmin
    - `guru` → Dashboard Guru
    - `industri` → Dashboard Industri
    - `siswa` → alur siswa (di bawah)

### 4.1 Halaman Awal Siswa (Mulai Pendampingan)

Tampilan seperti portal pembelajaran. Muncul setelah siswa login.

Form pendampingan siswa berisi:

- Nama siswa → terisi otomatis dari akun, terkunci
- ID siswa → terisi otomatis dari akun, terkunci
- Program keahlian → terisi otomatis dari akun, terkunci
- Nama industri → **terisi otomatis dari kelompok magang siswa, terkunci**
- Unit/bagian kerja → dipilih siswa dari `daftar_unit_kerja` perusahaan tersebut
  (dapat diganti siswa kapan saja dari Dashboard)
- Kompetensi utama → dipilih siswa dari kompetensi program keahliannya
  (keputusan 13 no. 30); dapat diganti nanti dari halaman Peta Kompetensi

Halaman ini hanya muncul sampai unit kerja dan kompetensi utama terisi; setelah
itu siswa yang login langsung masuk ke Dashboard (keputusan 13 no. 18).

Tombol: **"Mulai Pendampingan Magang"**

Setelah masuk tampilkan: **"Selamat datang, [nama siswa]"**

Jika siswa belum dimasukkan ke kelompok magang oleh Superadmin, tampilkan pesan
bahwa siswa belum terdaftar di kelompok magang dan diminta menghubungi sekolah.

---

## 5. Panel Superadmin

### 5.1 Manajemen Akun

- Buat akun **guru**, **siswa**, dan **industri** (nama, email, password awal, role).
- Akun industri wajib dihubungkan ke satu **Perusahaan**.
- Edit dan nonaktifkan akun.

### 5.4 Pemantauan (baca-saja)

- Lihat materi, logbook, dan hasil assessment seluruh siswa (keputusan 13 no. 17).

### 5.2 Data Perusahaan & Dokumen Industri

- Tambah, edit, hapus perusahaan (nama, alamat, bidang usaha, unit kerja).
- Saat mendaftarkan perusahaan, superadmin **mengunggah dokumen industri**
  (misal SOP, panduan kerja) yang menjadi knowledge base AI Mentor untuk
  siswa di perusahaan tersebut. Dokumen dapat ditambah/dihapus lewat edit
  perusahaan.
- Data perusahaan menjadi sumber nama industri dan unit kerja di halaman siswa.

### 5.3 Kelompok Magang

- Buat kelompok magang sesuai **proposal siswa**.
- Tetapkan: perusahaan tujuan, periode magang, satu guru pembimbing, dan daftar siswa.
- Satu guru dapat dimasukkan ke lebih dari satu kelompok.
- Pindahkan siswa antar kelompok jika diperlukan.

---

## 6. Halaman Siswa

Navigasi utama (bottom navigation di smartphone, sidebar di desktop):
Dashboard · Peta Kompetensi · Learning Gap · Belajar · AI Mentor · Logbook ·
Assessment · Progress

### 6.1 Dashboard

- Hari ke-berapa pelaksanaan magang (dihitung dari `periode_mulai` kelompok)
- Persentase progres
- Jumlah kompetensi yang sudah dikuasai
- Kompetensi yang masih memiliki gap
- Aktivitas yang harus dilakukan hari ini
- Rekomendasi materi
- Logbook terakhir

### 6.2 Peta Kompetensi

Tabel perbandingan: **Kompetensi sekolah** vs **Aktivitas/kompetensi industri**.
Kompetensi diinput guru; level siswa diisi guru (bagian 7).

Level:

| Level | Arti                                  |
| ----- | ------------------------------------- |
| 1     | Belum mampu                           |
| 2     | Mampu dengan bimbingan                |
| 3     | Mampu mandiri                         |
| 4     | Mampu mandiri sesuai standar industri |

Warna (gap = target_level − level_siswa):

| Warna  | Arti            | Aturan  |
| ------ | --------------- | ------- |
| Merah  | Gap tinggi      | gap ≥ 2 |
| Kuning | Perlu penguatan | gap = 1 |
| Hijau  | Sesuai target   | gap ≤ 0 |

Di smartphone, tabel ditampilkan sebagai daftar card agar tidak perlu scroll ke samping.

### 6.3 Learning Gap

Judul: **"Kompetensi yang perlu Anda tingkatkan"** (otomatis dari data gap > 0,
diurutkan dari gap terbesar).

Setiap card berisi:

- Nama kompetensi
- Level siswa
- Target industri
- Besarnya gap
- Tombol **"Pelajari"** → membuka materi kompetensi tsb
- Tombol **"Tanya AI Mentor"** → membuka chatbot dengan konteks kompetensi tsb

### 6.4 Belajar (Microlearning)

Alur 8 langkah per materi:

Konsep Dasar → Contoh Industri → Media/Video → Studi Kasus → Latihan → Kuis →
Praktik di Industri → Refleksi

Materi bersifat umum (per program keahlian), diinput dan diedit oleh **guru**.
Tampilkan progress langkah (stepper).

Jika materi sudah diverifikasi industri, tampilkan badge
**"Diverifikasi oleh industri"** di judul materi (di daftar materi dan di halaman
materi). Jika belum atau tidak diverifikasi, **tidak ada badge**. Lihat bagian 11.2.

#### 6.4.1 Media Materi (Berbasis Link)

Guru tidak mengunggah file media. Semua media dimasukkan sebagai **link**:

| Jenis   | Input dari guru                                                              | Cara ditampilkan                                                                                                  |
| ------- | ---------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------- |
| Video   | Link YouTube (`youtube.com/watch?v=…`, `youtu.be/…`, `youtube.com/shorts/…`) | Ubah otomatis menjadi URL embed lalu tampilkan sebagai **iframe** responsif (rasio 16:9)                          |
| Gambar  | Link Google Drive                                                            | Tampilkan sebagai iframe pratinjau Drive (`drive.google.com/file/d/{ID}/preview`) + tombol "Buka di Google Drive" |
| Dokumen | Link Google Drive (PDF, Docs, Slides, dll.)                                  | Tampilkan sebagai iframe pratinjau Drive + tombol "Buka di Google Drive"                                          |

Aturan:

- Validasi link di backend: video hanya menerima domain YouTube, gambar dan
  dokumen hanya menerima domain Google Drive/Google Docs. Tolak link lain dengan
  pesan error yang jelas.
- Ambil ID video/file dari link saat disimpan, agar link dengan format
  berbeda tetap tampil dengan benar.
- Di form guru, tampilkan pengingat: file Google Drive harus diatur
  **"Siapa saja yang memiliki link dapat melihat"**, jika tidak, media tidak
  akan tampil untuk siswa.
- Tampilkan pratinjau media di form guru sebelum materi disimpan.

### 6.5 AI Mentor

Floating chatbot di **kanan bawah**, tersedia di semua halaman siswa.
Detail lengkap di bagian 9.

### 6.6 Logbook

Form harian:

- Tanggal
- Aktivitas hari ini
- Peralatan/software yang digunakan
- Apa yang sudah saya pahami
- Apa yang baru saya temui
- Kesulitan yang saya alami
- Pengetahuan dari sekolah yang saya gunakan
- Hal yang ingin saya pelajari
- Upload bukti kegiatan (opsional)

Tombol: **"Analisis dengan AI"** → hasil (lihat bagian 9.5):

- Kompetensi yang digunakan
- Kompetensi baru yang ditemukan
- Kemungkinan learning gap
- Rekomendasi materi
- Pertanyaan refleksi

### 6.7 Assessment

- Kuis interaktif setiap selesai materi.
- Tampilkan skor.
- Jika skor **di bawah nilai minimal materi (bawaan 75)** → tampilkan rekomendasi penguatan materi terkait.

### 6.8 Progress

Gunakan grafik dan progress bar:

- Overall progress
- Competency progress
- Learning gap yang sudah diselesaikan
- Jumlah logbook
- Assessment score
- Kompetensi yang telah diverifikasi industri

---

## 7. Dashboard Guru Pembimbing

- Pilih kelompok magang (dropdown/tab) — guru hanya melihat kelompok yang ia bimbing.
- Daftar siswa dalam kelompok terpilih, beserta:
    - progress
    - learning gap
    - aktivitas terakhir
    - logbook
    - hasil assessment
    - status verifikasi kompetensi (hanya dilihat, verifikasi dilakukan industri)
- **Level Kompetensi (lihat saja):** level siswa naik otomatis dari kuis (bagian 11.1).
- **Kompetensi utama & paling dikuasai** tiap siswa tampil di kartu siswa (keputusan 13 no. 30).
- **Rekap Kelompok:** learning gap dan progres semua siswa di kelompok yang dibimbing (keputusan 13 no. 31).
- **Manajemen Kompetensi:** tambah dan edit kompetensi (kompetensi sekolah,
  aktivitas industri, target level) per program keahlian.
- **Manajemen Materi:** tambah dan edit materi (8 langkah) beserta kuisnya.
  Media dimasukkan sebagai link (bagian 6.4.1). Di daftar materi, tampilkan
  badge verifikasi dan masukan terakhir dari industri (bagian 11.2).
- **Dokumen Sekolah:** unggah dan hapus dokumen sekolah untuk knowledge base
  AI Mentor, per program keahlian.

## 8. Dashboard Industri

- Daftar siswa yang terdaftar magang di perusahaannya (semua kelompok yang
  perusahaan_id-nya sama).
- Untuk setiap siswa: progress, learning gap, aktivitas terakhir, logbook,
  hasil assessment.
- Tombol **"Verifikasi Kompetensi"** per kompetensi siswa (lihat bagian 11.1).
- **Verifikasi Materi:** daftar materi untuk program keahlian siswa di
  perusahaannya. Industri dapat membaca materi lalu memverifikasi atau memberi
  masukan (lihat bagian 11.2).

---

## 9. AI Mentor Magang

### 9.1 Tampilan

- Judul: **"AI Mentor Magang"**
- Pesan pembuka:
    > Halo, saya AI Mentor Magang. Saya dapat membantu menghubungkan apa yang Anda
    > pelajari di sekolah dengan aktivitas yang Anda temui di industri. Apa yang
    > sedang Anda kerjakan hari ini?
- Quick prompts:
    - "Saya tidak memahami pekerjaan ini"
    - "Hubungkan pekerjaan saya dengan materi sekolah"
    - "Jelaskan istilah industri"
    - "Saya mengalami kesulitan"
    - "Bantu saya membuat refleksi"
    - "Rekomendasikan materi yang harus saya pelajari"

### 9.2 Arsitektur Integrasi AI

- Provider: **Google Gemini**, **dipanggil dari backend Laravel saja**.
  API key TIDAK BOLEH berada di kode frontend/React maupun di repository.
- Alur: React → route Laravel (misal `POST /mentor/chat`) → backend menyusun
  system prompt + konteks siswa + dokumen knowledge base → Gemini API →
  backend → React.
- Letakkan seluruh pemanggilan Gemini di satu service class
  (misal `App\Services\AiMentorService`) agar tidak tersebar di controller.

### 9.3 Knowledge Base (Dokumen Sekolah & Industri)

Jawaban AI harus memprioritaskan dokumen yang diunggah. Gunakan pola
**RAG (Retrieval-Augmented Generation)** dengan fitur file/file search
dari Gemini API (cek dokumentasi Gemini terbaru untuk cara pemakaiannya):

1. Dokumen diunggah lewat website → backend meneruskannya ke Gemini dan
   menyimpan id-nya di `DokumenKnowledgeBase.id_di_layanan_ai`.
2. Dokumen dikelompokkan per cakupan:
    - **Dokumen sekolah** → diunggah guru, per program keahlian
    - **Dokumen industri** → diunggah superadmin, per perusahaan
3. Saat siswa bertanya, backend hanya memakai dokumen yang sesuai dengan
   **program keahlian** dan **perusahaan** siswa tersebut. Dokumen perusahaan A
   tidak boleh terpakai untuk siswa di perusahaan B.

#### 9.3.1 Catatan Implementasi (Tahap 6)

- Memakai **Gemini File Search**: satu *File Search store* per cakupan
  (`sekolah:{kode_program}`, `industri:{perusahaan_id}`), dicatat di tabel
  `knowledge_base_store`. Siswa hanya dikirimi store program keahlian dan
  perusahaannya sendiri, jadi isolasi antar perusahaan bersifat struktural.
- Unggah ke Gemini lewat queue (`SinkronDokumenKnowledgeBase`); status dokumen
  `menunggu | siap | gagal` ditampilkan di daftar dokumen guru dan superadmin.
  Hapus dokumen di website juga menghapusnya dari Gemini (`HapusDokumenAi`).
- Model diatur di `.env`: `GEMINI_MODEL` (bawaan `gemini-3.5-flash`) dan
  `GEMINI_MODEL_CADANGAN` (bawaan `gemini-3.5-flash-lite`), dipakai jika model
  utama sibuk (429/500/503).
- Teks system prompt 9.4 disimpan di `resources/prompts/ai-mentor.txt`, ditambah
  aturan format (tanpa LaTeX/tabel/heading) agar tampil rapi di chat.
- Batas: 10 pesan AI per menit per siswa (chat + analisis logbook); riwayat chat
  disimpan maks. 40 pesan terakhir, 12 terakhir dikirim sebagai konteks; siswa
  dapat memulai percakapan baru.

### 9.4 System Prompt AI Mentor

Variabel dalam `{...}` diisi backend dari data siswa yang sedang login.

```
Anda adalah AI Mentor Magang untuk siswa SMK.

Data siswa:
- Nama: {nama_siswa}
- Program keahlian: {program_keahlian}
- Tempat magang: {nama_perusahaan}, unit {unit_kerja}
- Hari magang ke-{hari_ke}
- Kompetensi dengan gap terbesar: {daftar_kompetensi_gap}

Tujuan utama Anda bukan menggantikan guru atau pembimbing industri, tetapi
membantu siswa menghubungkan pengetahuan yang diperoleh di sekolah dengan
aktivitas nyata yang ditemui di industri.

Setiap kali siswa menjelaskan suatu aktivitas atau masalah:
1. Identifikasi aktivitas industri yang sedang dilakukan.
2. Identifikasi kompetensi sekolah yang berkaitan.
3. Jelaskan hubungan teori dan praktik.
4. Identifikasi kemungkinan learning gap.
5. Jelaskan konsep yang belum dipahami dengan bahasa sederhana.
6. Rekomendasikan materi yang harus dipelajari. Utamakan materi yang
   tersedia di website: {daftar_judul_materi}.
7. Berikan satu atau dua pertanyaan refleksi.
8. Jika relevan, sarankan siswa mendiskusikannya dengan pembimbing industri.

Sumber informasi:
- Prioritaskan informasi dari dokumen sekolah dan dokumen industri yang
  tersedia pada knowledge base.
- Sebutkan nama dokumen yang menjadi sumber jawaban jika ada.
- Jangan mengarang SOP perusahaan.
- Jika informasi tidak tersedia, katakan bahwa informasi tersebut tidak
  terdapat pada knowledge base dan sarankan siswa mengonfirmasi kepada
  pembimbing industri.

Keselamatan:
Untuk aktivitas yang berkaitan dengan keselamatan, mesin, listrik, bahan
berbahaya, alat berat, atau prosedur industri berisiko: jangan mendorong
siswa mencoba prosedur tanpa supervisi. Utamakan SOP perusahaan, K3, dan
instruksi pembimbing industri.

Gaya bahasa:
Gunakan bahasa Indonesia yang sederhana dan edukatif, sesuai untuk siswa SMK.
Jawaban singkat dan mudah dibaca di layar smartphone.

Struktur jawaban jika relevan:
**Aktivitas Anda**
**Konsep dari Sekolah**
**Praktik di Industri**
**Learning Gap**
**Yang Perlu Dipelajari**
**Pertanyaan Refleksi**
Untuk pertanyaan singkat (misalnya arti satu istilah), jawab langsung tanpa
struktur lengkap.
```

### 9.5 Prompt Analisis Logbook

Dipanggil saat tombol "Analisis dengan AI" diklik. Memakai aturan sumber
informasi dan keselamatan yang sama dengan 9.4. Minta output **JSON** agar
mudah ditampilkan sebagai card dan disimpan ke `Logbook.hasil_analisis_ai`.

```
Analisis logbook harian siswa berikut berdasarkan daftar kompetensi
{daftar_kompetensi} dan dokumen knowledge base.

Logbook:
{isi_logbook}

Balas HANYA dengan JSON:
{
  "kompetensi_digunakan": ["..."],
  "kompetensi_baru": ["..."],
  "kemungkinan_learning_gap": ["..."],
  "rekomendasi_materi": ["..."],
  "pertanyaan_refleksi": ["...", "..."]
}
```

Backend wajib memvalidasi JSON sebelum disimpan dan menampilkan pesan error
yang ramah jika analisis gagal.

---

## 10. Status Kompetensi

Setiap `ProgresKompetensi` memiliki satu status:

1. Belum dipelajari
2. Sedang dipelajari
3. Sedang dipraktikkan
4. Menunggu verifikasi
5. Terverifikasi

Tampilkan sebagai status indicator / badge berwarna.

Perpindahan status berjalan **otomatis** (keputusan 13 no. 13):

| Dari → Ke                            | Pemicu                                             |
| ------------------------------------ | -------------------------------------------------- |
| Belum dipelajari → Sedang dipelajari | Siswa membuka materi kompetensi tsb                |
| → Sedang dipraktikkan                | Siswa lulus kuis materi kompetensi tsb (skor ≥ nilai minimal materi) |
| → Menunggu verifikasi                | Level siswa (otomatis dari kuis) ≥ `target_level`  |
| → Terverifikasi                      | Industri menekan "Verifikasi Kompetensi"           |

Status tidak pernah mundur. Level juga tidak pernah turun (lihat 11.1).

## 11. Verifikasi

### 11.1 Pengisian Level & Verifikasi Kompetensi

- **Level siswa (1–4) naik otomatis** (revisi pemilik proyek, keputusan 13 no. 6):
  - Setiap materi diberi **level 1–4** dan **nilai minimal lulus** (bawaan 75) oleh guru.
  - Jika siswa lulus kuis materi level N, level siswa pada kompetensi materi
    itu menjadi N bila lebih tinggi dari level sekarang (level tidak pernah turun).
  - Belum lulus kuis apa pun = level 1 (Belum mampu).
  - Simpan kapan level terakhir berubah. Guru tidak mengisi level secara manual;
    guru hanya melihat.
- Status **"Terverifikasi"** hanya bisa diberikan oleh **pembimbing industri**
  lewat tombol **"Verifikasi Kompetensi"**, hanya untuk siswa di perusahaannya.
  Simpan siapa yang memverifikasi dan kapan.
- Guru dapat melihat status verifikasi, tetapi tidak dapat memverifikasi.

### 11.2 Verifikasi Materi oleh Industri

Alur:

1. Industri membuka materi dari menu **Verifikasi Materi** (bagian 8).
2. Industri memilih salah satu:
    - **"Verifikasi Materi"** → kolom masukan opsional.
    - **"Belum Sesuai"** → kolom masukan **wajib** diisi.
3. Sistem menyimpan hasil ke `VerifikasiMateri`.
4. Sistem mengirim **email ke guru pembuat materi** (`Materi.dibuat_oleh`).

Isi email:

- Subjek: `[MagangBridge] Masukan untuk materi "{judul_materi}"`
- Judul materi, nama perusahaan dan nama pemeriksa, hasil
  (Diverifikasi / Belum Sesuai), dan **isi masukan dari industri**.
- Link ke halaman edit materi tersebut.

Badge:

- Hasil "Diverifikasi" → `terverifikasi_industri = true` → badge
  **"Diverifikasi oleh industri"** tampil.
- Belum diperiksa atau hasil "Belum Sesuai" → tidak ada badge.

Teknis:

- Kirim email lewat queue Laravel agar halaman industri tidak menunggu.
- Jika email gagal terkirim, verifikasi tetap tersimpan; catat di
  `email_terkirim = false`.
- Masukan juga tersimpan di sistem, jadi guru tetap bisa membacanya di
  Manajemen Materi walaupun email tidak sampai.

---

## 12. Panduan Desain & UI

- **Mobile-first**: siswa umumnya mengakses lewat smartphone di tempat magang.
  Responsive untuk smartphone, tablet, dan desktop.
- Warna utama: **biru tua, biru muda, putih**, dengan **aksen hijau**.
  Merah dan kuning hanya untuk indikator gap/peringatan.
- Komponen: card interface, progress bar, competency badge, status indicator,
  dashboard.
- Desain modern, sederhana, profesional, mudah digunakan siswa SMK.
- Seluruh teks antarmuka dalam **bahasa Indonesia yang sederhana**.
- Floating chatbot tidak boleh menutupi bottom navigation di smartphone.

---

## 13. Keputusan Proyek

| No  | Topik                                          | Keputusan                                                                                                                       |
| --- | ---------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------- |
| 1   | Tech stack                                     | Laravel, Inertia.js, React, Tailwind CSS, shadcn/ui, Lucide React, MySQL (sesuai `.env`)                                        |
| 2   | Provider AI                                    | Google Gemini                                                                                                                   |
| 3   | Industri siswa                                 | Otomatis terpilih dari kelompok magang yang ditetapkan Superadmin                                                               |
| 4   | Verifikasi kompetensi                          | Hanya pembimbing industri                                                                                                       |
| 5   | Input data kompetensi                          | Hanya guru                                                                                                                      |
| 6   | Pengisi level kompetensi siswa                 | Otomatis: lulus kuis materi level N -> level siswa N (tidak pernah turun); guru hanya melihat |
| 7   | Dokumen knowledge base sekolah                 | Diunggah guru                                                                                                                   |
| 8   | Dokumen knowledge base industri                | Diunggah superadmin saat mendaftarkan perusahaan                                                                                |
| 9   | Guru per kelompok                              | Satu kelompok hanya satu guru; satu guru boleh membimbing lebih dari satu kelompok                                              |
| 10  | Cakupan materi                                 | Umum (per program keahlian), bukan per kelompok                                                                                 |
| 11  | Verifikasi materi                              | Oleh industri; hasil dan masukan dikirim via email ke guru pembuat materi; badge "Diverifikasi oleh industri" jika diverifikasi |
| 12  | Media materi                                   | Hanya link: YouTube ditampilkan sebagai iframe, gambar & dokumen memakai link Google Drive                                      |
| 13  | Perpindahan status kompetensi                  | Otomatis, sesuai tabel di bagian 10                                                                                             |
| 14  | Syarat verifikasi kompetensi                   | Tombol "Verifikasi Kompetensi" hanya aktif saat status "Menunggu verifikasi"; industri tidak dapat membatalkan verifikasi       |
| 15  | Program keahlian                               | Dikelola superadmin (tabel `program_keahlian`: kode + nama), dipilih dari daftar di semua form dan dipakai untuk pembatasan data; kode tidak bisa diubah, program yang masih dipakai tidak bisa dihapus |
| 16  | Hak edit kompetensi & materi                   | Guru hanya menambah/mengedit untuk program keahliannya sendiri (`ProfilGuru.program_keahlian`); belum ada fitur hapus           |
| 17  | Superadmin melihat materi, logbook, assessment | Halaman baca-saja (read-only) di Panel Superadmin                                                                               |
| 18  | Mulai Pendampingan siswa                       | Hanya sekali sampai unit kerja & kompetensi utama terisi; pilihan disimpan permanen (tidak dipilih ulang setiap login) |
| 19  | Persentase progres siswa                       | Persentase kompetensi (program keahlian siswa) yang `level_siswa` ≥ `target_level`                                              |
| 20  | "Aktivitas yang harus dilakukan hari ini"      | Otomatis: isi logbook hari ini jika belum; lanjutkan materi untuk gap terbesar; ulangi kuis yang skornya < 75                   |
| 21  | Logbook                                        | Satu logbook per tanggal per siswa, dapat diedit; bukti kegiatan berupa gambar/PDF maks. 5 MB                                   |
| 22  | Kuis                                           | Boleh diulang; semua percobaan disimpan; skor yang ditampilkan adalah skor terbaik                                              |
| 29  | Nilai lulus kuis                               | Ditentukan guru per materi (nilai minimal, bawaan 75); menggantikan batas tetap 75 |
| 23  | Autentikasi                                    | Starter kit React "blank" + login buatan sendiri (email + password, rate limit); tanpa registrasi, reset password, atau 2FA     |
| 24  | Level awal siswa                               | Level 1 (Belum mampu) sampai lulus kuis materi yang lebih tinggi |
| 25  | Akun                                           | Role tidak bisa diubah setelah dibuat; tidak ada hapus akun (cukup dinonaktifkan) |
| 26  | Hapus data                                     | Kelompok tidak bisa dihapus; perusahaan yang masih dipakai kelompok/akun industri dan unit kerja yang masih dipilih siswa tidak bisa dihapus |
| 27  | Unit kerja siswa                               | Dikosongkan (siswa memilih ulang) jika siswa pindah ke kelompok di perusahaan lain, dikeluarkan dari kelompok, atau perusahaan kelompok diganti |
| 28  | Dokumen knowledge base                         | PDF, DOCX, atau TXT, maks. 20 MB per file |
| 30  | Kompetensi utama & paling dikuasai             | Kompetensi utama dipilih siswa, materinya tampil paling atas di Belajar, dapat diganti di Peta Kompetensi. "Paling dikuasai" dihitung otomatis (level tertinggi > sudah terverifikasi > naik level paling akhir); keduanya tampil di dashboard guru & industri |
| 31  | Rekap kelompok guru                            | Guru melihat learning gap (siswa × kompetensi, berwarna) dan progres semua siswa di kelompok yang ia bimbing |
| 32  | URL login                                      | `/login` siswa, `/login/guru`, `/login/industri`, `/login/admin`; tidak ada tautan ke halaman login lain |
| 33  | Konfirmasi logout                              | SweetAlert2 (diminta pemilik proyek), diberi gaya sesuai tema |
| 34  | Unit kerja siswa                               | Dipilih siswa sendiri dari daftar unit kerja perusahaan dan dapat diganti kapan saja |
| 35  | Hari magang                                    | Dihitung dalam hari kalender dari `periode_mulai` sampai `periode_selesai` kelompok |

### 13.1 Keputusan Badge Verifikasi Materi (sudah dijawab)

1. Beberapa perusahaan memberi hasil berbeda: hanya hasil **terakhir dari
   tiap perusahaan sejak materi terakhir diedit** yang dihitung. Badge tampil
   jika minimal satu perusahaan memverifikasi **dan** tidak ada perusahaan yang
   hasil terakhirnya "Belum Sesuai". (Usulan Claude, disetujui pemilik proyek.)
2. Guru mengedit materi yang sudah diverifikasi: badge hilang dan materi perlu
   diverifikasi ulang. Riwayat pemeriksaan lama tetap tersimpan.

---

## 14. Aturan Kerja untuk Claude

- Jangan menambahkan fitur di luar dokumen ini tanpa persetujuan.
- Jangan pernah menaruh API key AI maupun kredensial email (SMTP) di frontend
  atau di repository.
- Tegakkan hak akses (bagian 2.1) di backend.
- Semua teks UI dalam bahasa Indonesia.
- Uji tampilan di lebar layar smartphone (±360–400px) untuk setiap halaman baru.

## 15. Aturan Tampilan

Bertindaklah sebagai desainer UI/UX senior yang memiliki selera estetika tinggi
(tastemaker). Buat kode frontend untuk **seluruh halaman MagangBridge SMK** dengan
aturan ketat untuk menghindari gaya generik AI (anti-slop):

1. **Tipografi:** Jangan gunakan font Inter, Roboto, Arial, atau Helvetica. Gunakan
   alternatif yang lebih berkarakter seperti Geist, Manrope, Plus Jakarta Sans,
   atau Poppins.
2. **Warna & Gradien:** DILARANG KERAS menggunakan gradien ungu ke biru
   (purple-to-indigo gradient). Gunakan warna latar belakang solid yang bersih atau
   kombinasikan warna netral yang dalam dengan satu warna aksen yang berani
   (sesuai bagian 12: biru tua sebagai warna dalam, hijau sebagai aksen).
3. **Kedalaman & Tekstur:** Hindari tampilan yang terlalu datar. Jika butuh dimensi,
   gunakan tekstur halus, garis batas tipis yang presisi, atau bayangan yang diberi
   warna (colored shadows), bukan gradien biasa.
4. **Bentuk & Radius:** Gunakan konsistensi radius sudut secara matematis dan
   konsentris (hindari angka acak yang membuat sudut elemen saling bertabrakan).
5. **Komponen:** Buat tata letak yang bernapas (whitespace yang longgar). Card tetap
   digunakan sesuai bagian 12, tetapi beri variasi ukuran, hierarki, dan ruang
   kosong yang cukup agar tidak terlihat menumpuk secara repetitif dan membosankan.
