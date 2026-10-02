import { useForm } from '@inertiajs/react';
import { LoaderCircle, RefreshCw } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import siswa from '@/routes/siswa';

/**
 * Siswa mengganti unit/bagian kerja kapan saja (keputusan 13 no. 34).
 */
export function GantiUnitKerja({
    sekarang,
    daftar,
}: {
    sekarang: string | null;
    daftar: string[];
}) {
    const [terbuka, setTerbuka] = useState(false);
    const form = useForm({ unit_kerja: sekarang ?? '' });

    const simpan = (e: FormEvent) => {
        e.preventDefault();
        form.put(siswa.unitKerja().url, {
            preserveScroll: true,
            onSuccess: () => setTerbuka(false),
        });
    };

    return (
        <Dialog open={terbuka} onOpenChange={setTerbuka}>
            <DialogTrigger asChild>
                <button
                    type="button"
                    className="inline-flex items-center gap-1 text-xs font-semibold text-navy-600 hover:text-navy-900"
                >
                    <RefreshCw className="size-3.5" /> Ganti
                </button>
            </DialogTrigger>
            <DialogContent className="rounded-3xl">
                <form onSubmit={simpan} className="space-y-5">
                    <DialogHeader>
                        <DialogTitle>Ganti unit kerja</DialogTitle>
                        <DialogDescription>
                            Pilih bagian tempat Anda ditempatkan sekarang.
                        </DialogDescription>
                    </DialogHeader>
                    <div>
                        <Select
                            value={form.data.unit_kerja}
                            onValueChange={(v) => form.setData('unit_kerja', v)}
                        >
                            <SelectTrigger
                                className="w-full"
                                aria-label="Unit kerja"
                            >
                                <SelectValue placeholder="Pilih unit kerja" />
                            </SelectTrigger>
                            <SelectContent>
                                {daftar.map((u) => (
                                    <SelectItem key={u} value={u}>
                                        {u}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {form.errors.unit_kerja && (
                            <p className="mt-1 text-sm font-medium text-destructive">
                                {form.errors.unit_kerja}
                            </p>
                        )}
                    </div>
                    <DialogFooter>
                        <Button
                            type="submit"
                            disabled={
                                form.processing ||
                                form.data.unit_kerja === sekarang
                            }
                        >
                            {form.processing && (
                                <LoaderCircle className="animate-spin" />
                            )}
                            Simpan
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
