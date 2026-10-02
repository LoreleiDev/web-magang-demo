import type { Auth } from '@/types/auth';
import type { HasilKuis } from '@/types/siswa';

declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            programKeahlian: Record<string, string>;
            [key: string]: unknown;
        };
        flashDataType: {
            toast?: { type: 'success' | 'error'; message: string };
            hasilKuis?: HasilKuis;
        };
    }
}
