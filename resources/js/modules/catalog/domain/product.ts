export type CatalogProduct = {
  id: string;
  sku: string;
  name: string;
  description: string;
  priceAmount: number;
  priceCurrency: string;
  imageUrl: string | null;
  status: "draft" | "active" | "archived";
};

export const formatMoney = (amount: number, currency: string) =>
  new Intl.NumberFormat("pt-BR", { style: "currency", currency }).format(amount / 100);
