import {
    BookOpen,
    LoaderCircle,
    MessagesSquare,
    RotateCcw,
    SendHorizontal,
    Sparkles,
    X,
} from 'lucide-react';
import type { FormEvent, KeyboardEvent } from 'react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { TeksAi } from '@/components/ai-mentor/teks-ai';
import { Button } from '@/components/ui/button';
import type { KonteksMentor } from '@/lib/ai-mentor';
import { EVENT_BUKA_MENTOR } from '@/lib/ai-mentor';
import { mintaJson } from '@/lib/http-json';
import { cn } from '@/lib/utils';
import siswa from '@/routes/siswa';

type Pesan = {
    peran: 'siswa' | 'mentor';
    isi: string;
    sumber?: string[];
    gagal?: boolean;
};

const PEMBUKA =
    'Halo, saya AI Mentor Magang. Saya dapat membantu menghubungkan apa yang Anda pelajari di sekolah dengan aktivitas yang Anda temui di industri. Apa yang sedang Anda kerjakan hari ini?';

const QUICK_PROMPTS = [
    'Saya tidak memahami pekerjaan ini',
    'Hubungkan pekerjaan saya dengan materi sekolah',
    'Jelaskan istilah industri',
    'Saya mengalami kesulitan',
    'Bantu saya membuat refleksi',
    'Rekomendasikan materi yang harus saya pelajari',
];

/**
 * Floating chatbot "AI Mentor Magang" (CLAUDE.md bagian 6.5 & 9.1) di kanan bawah,
 * di atas bottom navigation pada smartphone.
 */
export function AiMentorChatbot() {
    const [terbuka, setTerbuka] = useState(false);
    const [pesan, setPesan] = useState<Pesan[]>([]);
    const [dimuat, setDimuat] = useState(false);
    const [masukan, setMasukan] = useState('');
    const [mengirim, setMengirim] = useState(false);
    const [konteks, setKonteks] = useState<KonteksMentor | null>(null);
    const bawahRef = useRef<HTMLDivElement>(null);
    const inputRef = useRef<HTMLTextAreaElement>(null);

    const muatRiwayat = useCallback(async () => {
        if (dimuat) {
            return;
        }

        try {
            const data = await mintaJson<{ pesan: Pesan[] }>(
                'GET',
                siswa.mentor.riwayat().url,
            );
            setPesan(data.pesan);
        } catch {
            // Riwayat gagal dimuat: chat tetap bisa dipakai dari awal.
        }

        setDimuat(true);
    }, [dimuat]);

    useEffect(() => {
        const buka = (e: Event) => {
            const detail = (e as CustomEvent<KonteksMentor | undefined>).detail;
            setTerbuka(true);

            if (detail) {
                setKonteks(detail);
                setMasukan(
                    `Saya ingin meningkatkan kompetensi "${detail.nama}". Apa yang perlu saya pelajari dan praktikkan di tempat magang?`,
                );
            }
        };

        window.addEventListener(EVENT_BUKA_MENTOR, buka);

        return () => window.removeEventListener(EVENT_BUKA_MENTOR, buka);
    }, []);

    useEffect(() => {
        if (terbuka) {
            void muatRiwayat();
            setTimeout(() => inputRef.current?.focus(), 150);
        }
    }, [terbuka, muatRiwayat]);

    useEffect(() => {
        bawahRef.current?.scrollIntoView({ behavior: 'smooth', block: 'end' });
    }, [pesan, mengirim, terbuka]);

    const kirim = async (teks: string) => {
        const isi = teks.trim();

        if (isi === '' || mengirim) {
            return;
        }

        setPesan((p) => [...p, { peran: 'siswa', isi }]);
        setMasukan('');
        setMengirim(true);

        try {
            const data = await mintaJson<{ balasan: Pesan }>(
                'POST',
                siswa.mentor.kirim().url,
                { pesan: isi, kompetensi_id: konteks?.kompetensiId ?? null },
            );
            setPesan((p) => [...p, data.balasan]);
        } catch (e) {
            setPesan((p) => [
                ...p,
                {
                    peran: 'mentor',
                    isi:
                        e instanceof Error
                            ? e.message
                            : 'AI Mentor sedang tidak dapat dihubungi.',
                    gagal: true,
                },
            ]);
        } finally {
            setMengirim(false);
        }
    };

    const kirimForm = (e: FormEvent) => {
        e.preventDefault();
        void kirim(masukan);
    };

    const tombolEnter = (e: KeyboardEvent<HTMLTextAreaElement>) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            void kirim(masukan);
        }
    };

    const percakapanBaru = async () => {
        try {
            await mintaJson('DELETE', siswa.mentor.hapus().url);
        } catch {
            // Abaikan; tampilan tetap dikosongkan.
        }

        setPesan([]);
        setKonteks(null);
    };

    return (
        <>
            {/* Tombol mengambang: di atas bottom nav pada smartphone. */}
            <button
                type="button"
                onClick={() => setTerbuka((t) => !t)}
                aria-label={
                    terbuka ? 'Tutup AI Mentor' : 'Buka AI Mentor Magang'
                }
                aria-expanded={terbuka}
                className={cn(
                    'fixed right-4 z-40 flex h-14 items-center gap-2 rounded-full bg-hijau-500 pr-5 pl-4 font-bold text-white shadow-hijau transition-transform hover:bg-hijau-600 active:scale-95',
                    'bottom-[calc(4rem+env(safe-area-inset-bottom)+0.75rem)] lg:right-8 lg:bottom-8',
                    terbuka && 'hidden lg:flex',
                )}
            >
                {terbuka ? (
                    <X className="size-5" />
                ) : (
                    <MessagesSquare className="size-5" />
                )}
                <span className="text-sm">AI Mentor</span>
            </button>

            {terbuka && (
                <section
                    role="dialog"
                    aria-label="AI Mentor Magang"
                    className="fixed inset-0 z-50 flex flex-col bg-background lg:inset-auto lg:right-8 lg:bottom-28 lg:h-[min(640px,calc(100dvh-9rem))] lg:w-[400px] lg:overflow-hidden lg:rounded-3xl lg:border lg:shadow-lg"
                >
                    <header className="flex items-center gap-3 bg-navy-900 tekstur-titik px-4 py-3 text-white">
                        <span className="grid size-10 place-items-center rounded-xl bg-white/10">
                            <Sparkles className="size-5 text-hijau-500" />
                        </span>
                        <div className="min-w-0 flex-1">
                            <h2 className="font-bold">AI Mentor Magang</h2>
                            <p className="truncate text-xs text-navy-300">
                                Jawaban AI dapat keliru. Konfirmasi ke
                                pembimbing.
                            </p>
                        </div>
                        <button
                            type="button"
                            onClick={() => void percakapanBaru()}
                            className="grid size-9 place-items-center rounded-lg text-navy-200 hover:bg-white/10 hover:text-white"
                            aria-label="Mulai percakapan baru"
                            title="Percakapan baru"
                        >
                            <RotateCcw className="size-4" />
                        </button>
                        <button
                            type="button"
                            onClick={() => setTerbuka(false)}
                            className="grid size-9 place-items-center rounded-lg text-navy-200 hover:bg-white/10 hover:text-white"
                            aria-label="Tutup AI Mentor"
                        >
                            <X className="size-5" />
                        </button>
                    </header>

                    <div className="flex-1 space-y-3 overflow-y-auto px-4 py-4">
                        <Gelembung peran="mentor">
                            <p>{PEMBUKA}</p>
                        </Gelembung>

                        {pesan.length === 0 && dimuat && (
                            <div className="flex flex-wrap gap-1.5 pt-1">
                                {QUICK_PROMPTS.map((q) => (
                                    <button
                                        key={q}
                                        type="button"
                                        onClick={() => void kirim(q)}
                                        className="rounded-full border bg-card px-3 py-1.5 text-left text-xs font-semibold text-navy-800 transition-colors hover:border-navy-900 hover:bg-navy-50"
                                    >
                                        {q}
                                    </button>
                                ))}
                            </div>
                        )}

                        {pesan.map((p, i) => (
                            <Gelembung key={i} peran={p.peran} gagal={p.gagal}>
                                {p.peran === 'mentor' && !p.gagal ? (
                                    <TeksAi teks={p.isi} />
                                ) : (
                                    <p className="whitespace-pre-line">
                                        {p.isi}
                                    </p>
                                )}
                                {p.sumber && p.sumber.length > 0 && (
                                    <div className="mt-2 border-t pt-2 text-[11px] text-navy-600">
                                        <span className="font-semibold">
                                            Sumber:
                                        </span>{' '}
                                        {p.sumber.map((s) => (
                                            <span
                                                key={s}
                                                className="mr-1 inline-flex items-center gap-1 rounded-md bg-navy-50 px-1.5 py-0.5"
                                            >
                                                <BookOpen className="size-3" />
                                                {s}
                                            </span>
                                        ))}
                                    </div>
                                )}
                            </Gelembung>
                        ))}

                        {mengirim && (
                            <Gelembung peran="mentor">
                                <span className="flex items-center gap-2 text-navy-600">
                                    <LoaderCircle className="size-4 animate-spin" />
                                    AI Mentor sedang menyusun jawaban…
                                </span>
                            </Gelembung>
                        )}
                        <div ref={bawahRef} />
                    </div>

                    <form
                        onSubmit={kirimForm}
                        className="border-t bg-card px-3 pt-3 pb-[calc(0.75rem+env(safe-area-inset-bottom))] lg:pb-3"
                    >
                        {konteks && (
                            <div className="mb-2 flex items-center gap-2 rounded-xl bg-navy-50 px-3 py-1.5 text-xs text-navy-800">
                                <span className="min-w-0 flex-1 truncate">
                                    Tentang: <strong>{konteks.nama}</strong>
                                </span>
                                <button
                                    type="button"
                                    onClick={() => setKonteks(null)}
                                    aria-label="Hapus konteks kompetensi"
                                    className="text-navy-500 hover:text-navy-900"
                                >
                                    <X className="size-3.5" />
                                </button>
                            </div>
                        )}
                        <div className="flex items-end gap-2">
                            <textarea
                                ref={inputRef}
                                value={masukan}
                                onChange={(e) => setMasukan(e.target.value)}
                                onKeyDown={tombolEnter}
                                rows={1}
                                maxLength={2000}
                                placeholder="Tulis pertanyaan Anda…"
                                aria-label="Pesan untuk AI Mentor"
                                className="max-h-32 min-h-11 flex-1 resize-none rounded-2xl border border-input bg-background px-4 py-2.5 text-sm outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            />
                            <Button
                                type="submit"
                                size="icon"
                                className="size-11 shrink-0 rounded-2xl"
                                disabled={mengirim || masukan.trim() === ''}
                                aria-label="Kirim"
                            >
                                <SendHorizontal />
                            </Button>
                        </div>
                    </form>
                </section>
            )}
        </>
    );
}

function Gelembung({
    peran,
    gagal,
    children,
}: {
    peran: 'siswa' | 'mentor';
    gagal?: boolean;
    children: React.ReactNode;
}) {
    return (
        <div className={cn('flex', peran === 'siswa' && 'justify-end')}>
            <div
                className={cn(
                    'max-w-[88%] rounded-2xl px-4 py-2.5 text-sm leading-relaxed',
                    peran === 'siswa'
                        ? 'rounded-br-md bg-navy-900 text-white'
                        : gagal
                          ? 'rounded-bl-md border border-gap-tinggi/30 bg-gap-tinggi-soft text-navy-900'
                          : 'rounded-bl-md border bg-card text-navy-900 shadow-xs',
                )}
            >
                {children}
            </div>
        </div>
    );
}
