/** Hasil impor akun dari file Excel/CSV, dikirim lewat flash. */
export type HasilImporAkun =
    | {
          berhasil: true;
          role: 'guru' | 'siswa' | 'industri';
          total: number;
          akun: {
              nama: string;
              email: string;
              password: string;
              sumber_password: 'NIS' | 'NIP' | 'Acak';
          }[];
      }
    | {
          berhasil: false;
          role: 'guru' | 'siswa' | 'industri';
          total: number;
          error: { baris: number; pesan: string[] }[];
      };
