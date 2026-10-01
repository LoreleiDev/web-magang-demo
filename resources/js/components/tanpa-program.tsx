import { GraduationCap } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';

/**
 * Ditampilkan jika akun guru belum punya program keahlian (keputusan 13 no. 16).
 */
export function TanpaProgram() {
    return (
        <EmptyState
            icon={GraduationCap}
            title="Program keahlian Anda belum diatur"
            description="Kompetensi, materi, dan dokumen sekolah dikelola per program keahlian. Minta admin sekolah mengisi program keahlian di akun Anda."
        />
    );
}
