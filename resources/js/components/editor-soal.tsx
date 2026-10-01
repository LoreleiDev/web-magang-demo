import { Check, Plus, Trash2, X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';

export type SoalForm = {
    pertanyaan: string;
    pilihan: string[];
    jawaban_benar: number | null;
    pembahasan: string;
};

const MAKS_PILIHAN = 5;

/**
 * Satu soal pilihan ganda: pertanyaan, 2–5 pilihan, tanda jawaban benar, pembahasan.
 */
export function EditorSoal({
    nomor,
    soal,
    onChange,
    onHapus,
    bisaDihapus,
    errors,
}: {
    nomor: number;
    soal: SoalForm;
    onChange: (soal: SoalForm) => void;
    onHapus: () => void;
    bisaDihapus: boolean;
    errors: (kunci: string) => string | undefined;
}) {
    const ubahPilihan = (i: number, nilai: string) =>
        onChange({
            ...soal,
            pilihan: soal.pilihan.map((p, j) => (j === i ? nilai : p)),
        });

    const hapusPilihan = (i: number) =>
        onChange({
            ...soal,
            pilihan: soal.pilihan.filter((_, j) => j !== i),
            jawaban_benar:
                soal.jawaban_benar === i
                    ? null
                    : soal.jawaban_benar !== null && soal.jawaban_benar > i
                      ? soal.jawaban_benar - 1
                      : soal.jawaban_benar,
        });

    const errorPilihan = soal.pilihan
        .map((_, i) => errors(`pilihan.${i}`))
        .find(Boolean);

    return (
        <div className="rounded-3xl border bg-card p-4 shadow-xs sm:p-5">
            <div className="flex items-center justify-between gap-3">
                <span className="rounded-full bg-navy-900 px-2.5 py-1 text-xs font-bold text-white">
                    Soal {nomor}
                </span>
                {bisaDihapus && (
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        onClick={onHapus}
                    >
                        <Trash2 />
                        Hapus soal
                    </Button>
                )}
            </div>

            <Textarea
                className="mt-3"
                rows={2}
                value={soal.pertanyaan}
                onChange={(e) =>
                    onChange({ ...soal, pertanyaan: e.target.value })
                }
                placeholder="Tulis pertanyaan"
                aria-label={`Pertanyaan soal ${nomor}`}
                aria-invalid={!!errors('pertanyaan')}
            />
            {errors('pertanyaan') && (
                <p className="mt-1 text-sm font-medium text-destructive">
                    {errors('pertanyaan')}
                </p>
            )}

            <p className="mt-4 text-xs font-semibold text-navy-500">
                Pilihan jawaban — tekan huruf untuk menandai jawaban benar
            </p>
            <ul className="mt-2 space-y-2">
                {soal.pilihan.map((pilihan, i) => {
                    const benar = soal.jawaban_benar === i;

                    return (
                        <li key={i} className="flex items-center gap-2">
                            <button
                                type="button"
                                onClick={() =>
                                    onChange({ ...soal, jawaban_benar: i })
                                }
                                aria-pressed={benar}
                                aria-label={`Tandai pilihan ${String.fromCharCode(65 + i)} sebagai jawaban benar`}
                                className={cn(
                                    'grid size-10 shrink-0 place-items-center rounded-lg border text-sm font-bold transition-colors',
                                    benar
                                        ? 'border-hijau-500 bg-hijau-500 text-white'
                                        : 'bg-card text-navy-700 hover:bg-navy-50',
                                )}
                            >
                                {benar ? (
                                    <Check className="size-4" />
                                ) : (
                                    String.fromCharCode(65 + i)
                                )}
                            </button>
                            <Input
                                value={pilihan}
                                onChange={(e) => ubahPilihan(i, e.target.value)}
                                placeholder={`Pilihan ${String.fromCharCode(65 + i)}`}
                                aria-label={`Pilihan ${String.fromCharCode(65 + i)}`}
                                aria-invalid={!!errors(`pilihan.${i}`)}
                            />
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                disabled={soal.pilihan.length <= 2}
                                onClick={() => hapusPilihan(i)}
                                aria-label={`Hapus pilihan ${String.fromCharCode(65 + i)}`}
                            >
                                <X />
                            </Button>
                        </li>
                    );
                })}
            </ul>
            {(errors('pilihan') ?? errorPilihan ?? errors('jawaban_benar')) && (
                <p className="mt-1 text-sm font-medium text-destructive">
                    {errors('pilihan') ??
                        errorPilihan ??
                        errors('jawaban_benar')}
                </p>
            )}
            {soal.pilihan.length < MAKS_PILIHAN && (
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    className="mt-2"
                    onClick={() =>
                        onChange({ ...soal, pilihan: [...soal.pilihan, ''] })
                    }
                >
                    <Plus />
                    Tambah pilihan
                </Button>
            )}

            <Textarea
                className="mt-4"
                rows={2}
                value={soal.pembahasan}
                onChange={(e) =>
                    onChange({ ...soal, pembahasan: e.target.value })
                }
                placeholder="Pembahasan (opsional) — ditampilkan setelah siswa menjawab"
                aria-label={`Pembahasan soal ${nomor}`}
            />
        </div>
    );
}
