import { FileText, Upload, X } from 'lucide-react';
import { useRef } from 'react';
import { cn } from '@/lib/utils';

/**
 * Pemilih beberapa file dokumen dengan daftar file terpilih.
 */
export function PilihFile({
    files,
    onChange,
    ekstensi,
    maksKb,
    errors,
}: {
    files: File[];
    onChange: (files: File[]) => void;
    ekstensi: string[];
    maksKb: number;
    errors?: string[];
}) {
    const input = useRef<HTMLInputElement>(null);

    return (
        <div className="space-y-3">
            <button
                type="button"
                onClick={() => input.current?.click()}
                className="flex w-full flex-col items-center gap-2 rounded-2xl border-2 border-dashed border-navy-200 bg-navy-50/50 px-4 py-6 text-center transition-colors hover:border-navy-400 hover:bg-navy-50"
            >
                <span className="grid size-10 place-items-center rounded-xl bg-card text-navy-600 shadow-xs">
                    <Upload className="size-5" />
                </span>
                <span className="text-sm font-semibold text-navy-900">
                    Pilih dokumen
                </span>
                <span className="text-xs text-muted-foreground">
                    {ekstensi.map((e) => e.toUpperCase()).join(', ')} · maks.{' '}
                    {Math.round(maksKb / 1024)} MB per file
                </span>
            </button>
            <input
                ref={input}
                type="file"
                multiple
                accept={ekstensi.map((e) => `.${e}`).join(',')}
                className="sr-only"
                onChange={(e) => {
                    onChange([...files, ...Array.from(e.target.files ?? [])]);
                    e.target.value = '';
                }}
            />

            {files.length > 0 && (
                <ul className="space-y-1.5">
                    {files.map((file, i) => (
                        <li
                            key={`${file.name}-${i}`}
                            className={cn(
                                'flex items-center gap-3 rounded-xl border bg-card px-3 py-2',
                                errors?.[i] && 'border-destructive',
                            )}
                        >
                            <FileText className="size-4 shrink-0 text-navy-500" />
                            <div className="min-w-0 flex-1">
                                <div className="truncate text-sm font-medium text-navy-900">
                                    {file.name}
                                </div>
                                {errors?.[i] ? (
                                    <div className="text-xs text-destructive">
                                        {errors[i]}
                                    </div>
                                ) : (
                                    <div className="text-xs text-muted-foreground">
                                        {(file.size / 1024 / 1024).toFixed(1)}{' '}
                                        MB
                                    </div>
                                )}
                            </div>
                            <button
                                type="button"
                                onClick={() =>
                                    onChange(files.filter((_, j) => j !== i))
                                }
                                className="grid size-8 place-items-center rounded-lg text-muted-foreground hover:bg-navy-50 hover:text-navy-900"
                                aria-label={`Hapus ${file.name} dari daftar`}
                            >
                                <X className="size-4" />
                            </button>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
