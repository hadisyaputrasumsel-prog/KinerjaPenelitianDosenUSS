<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Konfigurasi Penentuan Prodi Dosen
    |--------------------------------------------------------------------------
    |
    | Jika data dari SINTA (atau sumber lain) tidak memiliki prodi atau 
    | bernilai "Unknown" / "Belum Diketahui", sistem akan menggunakan 
    | mapping berdasarkan SINTA ID atau nama di bawah ini.
    | Format mapping menggunakan Sinta ID sebagai key dan Nama Prodi sebagai value.
    |
    */

    '6937789' => 'Ilmu Komputer (S1)', // MUHAMMAD IQBAL
    '6768518' => 'Sistem Informasi (S1)', // MAYAMIN
    
    // Tambahkan SINTA ID => Prodi lainnya di sini...
];
