export type CatalogProduct = {
    id: string;
    sku: string;
    name: string;
    description: string;
    category: string;
    priceAmount: number;
    priceCurrency: string;
    imageUrl: string | null;
    galleryImages: string[];
    variations: {
        id: string;
        name: string;
        value: string;
        sku: string;
        stock: number;
        lowStockThreshold: number;
        purchasable: boolean;
        lowStock: boolean;
    }[];
    status: 'draft' | 'active' | 'archived';
    stockAvailable: number;
    canSellWithoutStock: boolean;
    showStockAlerts: boolean;
};

export const formatMoney = (amount: number, currency: string) =>
    new Intl.NumberFormat('pt-BR', { style: 'currency', currency }).format(
        amount / 100,
    );
