export type EquippedTitle = {
    id: number;
    name: string;
    color: string | null;
} | null;

export type EquippedAvatar = {
    id: number;
    name: string;
    image_url: string | null;
} | null;

export type EquippedItems = {
    title: EquippedTitle;
    avatar: EquippedAvatar;
};

export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    equipped?: EquippedItems;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
};

export type TwoFactorConfigContent = {
    title: string;
    description: string;
    buttonText: string;
};
