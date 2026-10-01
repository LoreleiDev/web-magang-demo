import { CheckCircle2 } from 'lucide-react';
import { MediaEmbed } from '@/components/media-embed';
import { cn } from '@/lib/utils';
import type { MateriDetail, SoalKuis } from '@/types/materi';

/**
 * Tampilan baca-saja seluruh materi: 8 langkah berurutan beserta media dan kuis
 * (dengan kunci jawaban). Dipakai superadmin, dan nanti guru & industri.
 */
export function MateriBaca({ materi }: { materi: MateriDetail }) {
    return (
        <ol className="relative space-y-4">
            {materi.langkah.map((langkah) => (
                <li
                    key={langkah.urutan}
                    className="rounded-3xl border bg-card p-2 shadow-xs"
                >
                    <div className="flex items-center gap-3 px-3 pt-2 pb-3">
                        <span className="grid size-8 shrink-0 place-items-center rounded-xl bg-navy-900 text-sm font-bold text-white">
                            {langkah.urutan}
                        </span>
                        <h3 className="text-base font-bold text-navy-900">
                            {langkah.judul}
                        </h3>
                    </div>

                    <div className="space-y-4 rounded-2xl bg-navy-50/60 p-4 sm:p-5">
                        {langkah.konten_teks ? (
                            <p className="text-[15px] leading-relaxed whitespace-pre-line text-navy-900">
                                {langkah.konten_teks}
                            </p>
                        ) : (
                            <p className="text-sm text-muted-foreground italic">
                                Belum ada isi.
                            </p>
                        )}

                        {langkah.media.length > 0 && (
                            <div
                                className={cn(
                                    'grid gap-3',
                                    langkah.media.length > 1 &&
                                        'lg:grid-cols-2',
                                )}
                            >
                                {langkah.media.map((media, i) => (
                                    <MediaEmbed key={i} media={media} />
                                ))}
                            </div>
                        )}

                        {langkah.jenis === 'kuis' && (
                            <DaftarSoal soal={materi.soal} />
                        )}
                    </div>
                </li>
            ))}
        </ol>
    );
}

function DaftarSoal({ soal }: { soal: SoalKuis[] }) {
    if (soal.length === 0) {
        return (
            <p className="text-sm text-muted-foreground italic">
                Belum ada soal kuis.
            </p>
        );
    }

    return (
        <ol className="space-y-3">
            {soal.map((s, i) => (
                <li key={s.id} className="rounded-2xl border bg-card p-4">
                    <p className="text-sm font-semibold text-navy-900">
                        {i + 1}. {s.pertanyaan}
                    </p>
                    <ul className="mt-3 grid gap-1.5 sm:grid-cols-2">
                        {s.pilihan.map((pilihan, j) => {
                            const benar = s.jawaban_benar === j;

                            return (
                                <li
                                    key={j}
                                    className={cn(
                                        'flex items-start gap-2 rounded-lg px-3 py-2 text-sm',
                                        benar
                                            ? 'bg-hijau-50 font-semibold text-hijau-700'
                                            : 'bg-navy-50/70 text-navy-800',
                                    )}
                                >
                                    <span className="font-bold">
                                        {String.fromCharCode(65 + j)}.
                                    </span>
                                    <span className="flex-1">{pilihan}</span>
                                    {benar && (
                                        <CheckCircle2 className="mt-0.5 size-4 shrink-0" />
                                    )}
                                </li>
                            );
                        })}
                    </ul>
                    {s.pembahasan && (
                        <p className="mt-3 text-xs leading-relaxed text-muted-foreground">
                            <span className="font-semibold text-navy-700">
                                Pembahasan:
                            </span>{' '}
                            {s.pembahasan}
                        </p>
                    )}
                </li>
            ))}
        </ol>
    );
}
