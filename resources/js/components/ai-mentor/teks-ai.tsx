import type { ReactNode } from 'react';
import { Fragment } from 'react';

/**
 * Tampilkan jawaban AI dengan format sederhana: paragraf, **tebal**, daftar "- " dan "1. ".
 * Tidak memakai HTML mentah dari AI (aman dari injeksi).
 */
export function TeksAi({ teks }: { teks: string }) {
    const blok: ReactNode[] = [];
    let daftar: { jenis: 'ul' | 'ol'; isi: string[] } | null = null;

    const tutupDaftar = () => {
        if (daftar) {
            const Tag = daftar.jenis;
            blok.push(
                <Tag
                    key={blok.length}
                    className={
                        Tag === 'ul'
                            ? 'list-disc space-y-1 pl-5'
                            : 'list-decimal space-y-1 pl-5'
                    }
                >
                    {daftar.isi.map((b, i) => (
                        <li key={i}>{sebaris(b)}</li>
                    ))}
                </Tag>,
            );
            daftar = null;
        }
    };

    for (const baris of teks.split('\n')) {
        const t = baris.trim();
        const butir = t.match(/^[-*•]\s+(.*)$/);
        const nomor = t.match(/^\d+[.)]\s+(.*)$/);

        if (butir || nomor) {
            const jenis = butir ? 'ul' : 'ol';

            if (daftar && daftar.jenis !== jenis) {
                tutupDaftar();
            }

            daftar ??= { jenis, isi: [] };
            daftar.isi.push((butir ?? nomor)![1]);
            continue;
        }

        tutupDaftar();

        if (t !== '') {
            blok.push(<p key={blok.length}>{sebaris(t)}</p>);
        }
    }

    tutupDaftar();

    return <div className="space-y-2">{blok}</div>;
}

function sebaris(teks: string): ReactNode {
    return teks.split(/(\*\*[^*]+\*\*)/g).map((bagian, i) =>
        bagian.startsWith('**') && bagian.endsWith('**') ? (
            <strong key={i} className="font-bold text-navy-900">
                {bagian.slice(2, -2)}
            </strong>
        ) : (
            <Fragment key={i}>{bagian}</Fragment>
        ),
    );
}
