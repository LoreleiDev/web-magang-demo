<x-mail::message>
# Masukan untuk materi Anda

Materi **{{ $judul }}** telah diperiksa oleh pembimbing industri.

- **Perusahaan:** {{ $perusahaan }}
- **Pemeriksa:** {{ $pemeriksa }}
- **Hasil:** {{ $hasil }}

**Masukan dari industri:**

<x-mail::panel>
{{ $masukan ?? 'Tidak ada masukan tertulis.' }}
</x-mail::panel>

<x-mail::button :url="$urlEdit">
Buka halaman edit materi
</x-mail::button>

Masukan ini juga tersimpan di menu Materi pada dashboard guru.

Salam,<br>
{{ config('app.name') }}
</x-mail::message>
