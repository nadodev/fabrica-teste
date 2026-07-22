export type User = {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    is_admin: boolean;
    is_super_admin: boolean;
    permissions: string[];
};

export type Auth = {
    user: User | null;
};
