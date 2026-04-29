Perbedaan antara Major, Minor, dan Patch mengikuti standar Semantic Versioning (SemVer). Formatnya adalah MAJOR.MINOR.PATCH (Contoh: 1.2.3).

Berikut penjelasannya:

1. PATCH (Angka Terakhir: 1.0.1)
Kapan digunakan? Saat Anda melakukan perbaikan bug (bug fixes) yang tidak merubah cara kerja fitur yang sudah ada.
Sifat: Backwards Compatible (Aplikasi tetap berjalan normal seperti sebelumnya).
Contoh: Memperbaiki salah ketik, memperbaiki error kecil di kalkulasi, atau menutup celah keamanan.
2. MINOR (Angka Tengah: 1.1.0)
Kapan digunakan? Saat Anda menambahkan fitur baru (new feature) atau fungsionalitas tambahan.
Sifat: Backwards Compatible (Fitur lama masih bisa digunakan tanpa perubahan kode).
Contoh: Menambah halaman laporan baru, menambah integrasi pembayaran baru, atau menambah tombol "Download PDF".
3. MAJOR (Angka Pertama: 2.0.0)
Kapan digunakan? Saat Anda melakukan perubahan besar yang tidak kompatibel dengan versi sebelumnya (breaking changes).
Sifat: Incompatible (User atau sistem lain mungkin perlu merubah cara mereka menggunakan aplikasi Anda).
Contoh: Merubah total struktur database, mengganti framework, atau menghapus fitur inti yang lama.