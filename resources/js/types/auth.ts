export type Role = 'superadmin' | 'guru' | 'industri' | 'siswa';

/**
 * Data user yang dibagikan dari HandleInertiaRequests (hanya kolom aman).
 */
export type User = {
    id: number;
    name: string;
    email: string;
    role: Role;
    role_label: string;
};

export type Auth = {
    user: User | null;
};
