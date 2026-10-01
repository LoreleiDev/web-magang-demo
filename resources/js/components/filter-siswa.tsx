import { Search } from 'lucide-react';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

const SEMUA = 'semua';

/**
 * Filter kelompok + pencarian nama siswa untuk halaman pemantauan.
 */
export function FilterSiswa({
    kelompok,
    nilai,
    ubah,
}: {
    kelompok: { id: number; nama: string }[];
    nilai: { kelompok: number | null; cari: string };
    ubah: {
        (kunci: 'kelompok', v: number | null): void;
        (kunci: 'cari', v: string): void;
    };
}) {
    return (
        <div className="mb-5 flex flex-col gap-2 sm:flex-row">
            <Select
                value={nilai.kelompok ? String(nilai.kelompok) : SEMUA}
                onValueChange={(v) =>
                    ubah('kelompok', v === SEMUA ? null : Number(v))
                }
            >
                <SelectTrigger className="w-full sm:w-72" aria-label="Kelompok">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value={SEMUA}>Semua kelompok</SelectItem>
                    {kelompok.map((k) => (
                        <SelectItem key={k.id} value={String(k.id)}>
                            {k.nama}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
            <div className="relative flex-1 sm:max-w-xs">
                <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                    type="search"
                    value={nilai.cari}
                    onChange={(e) => ubah('cari', e.target.value)}
                    placeholder="Cari nama siswa"
                    className="pl-9"
                    aria-label="Cari siswa"
                />
            </div>
        </div>
    );
}
