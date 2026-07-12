import { Link, router, usePage } from "@inertiajs/react";
import { Boxes, ExternalLink, Image, LayoutDashboard, LogOut, MapPin, PackagePlus, Settings, Tags, Text } from "lucide-react";
import type { ReactNode } from "react";

export function AdminLayout({ title, children }: { title: string; children: ReactNode }) {
  const page = usePage<{ auth: { user: { name: string } }; siteSettings: { storeName: string; logoUrl: string } }>().props;
  const user = page.auth.user;
  const settings = page.siteSettings;

  return (
    <div className="min-h-screen bg-bg-soft lg:grid lg:grid-cols-[250px_1fr]">
      <aside className="bg-navy p-5 text-white">
        <Link href="/admin" className="flex items-center gap-3 font-display text-xl font-black"><span className="inline-flex rounded bg-white p-1"><img src={settings.logoUrl} alt={settings.storeName} className="h-8 w-auto" /></span> Admin</Link>
        <nav className="mt-8 space-y-2">
          <Link href="/admin" className="flex items-center gap-3 rounded-lg bg-white/10 px-3 py-2.5 text-sm font-bold"><LayoutDashboard className="h-4 w-4" /> Dashboard</Link>
          <Link href="/admin/produtos" className="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold hover:bg-white/10"><Boxes className="h-4 w-4" /> Produtos</Link>
          <Link href="/admin/produtos/novo" className="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold hover:bg-white/10"><PackagePlus className="h-4 w-4" /> Novo produto</Link>
          <Link href="/admin/conteudo/categorias" className="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold hover:bg-white/10"><Tags className="h-4 w-4" /> Categorias</Link>
          <Link href="/admin/conteudo/banners" className="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold hover:bg-white/10"><Image className="h-4 w-4" /> Banners</Link>
          <Link href="/admin/conteudo/lojas" className="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold hover:bg-white/10"><MapPin className="h-4 w-4" /> Lojas</Link>
          <Link href="/admin/conteudo/historia" className="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold hover:bg-white/10"><Text className="h-4 w-4" /> Nossa historia</Link>
          <Link href="/admin/configuracoes" className="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold hover:bg-white/10"><Settings className="h-4 w-4" /> Configuracoes</Link>
          <Link href="/produtos" className="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold hover:bg-white/10"><ExternalLink className="h-4 w-4" /> Ver loja</Link>
        </nav>
      </aside>
      <div>
        <header className="flex items-center justify-between border-b border-border bg-white px-5 py-4 md:px-8">
          <div><div className="text-xs text-text-muted">Painel administrativo</div><h1 className="font-display text-xl font-black text-navy">{title}</h1></div>
          <div className="flex items-center gap-3 text-sm"><span className="hidden text-text-muted sm:inline">{user.name}</span><button onClick={() => router.post("/admin/logout")} className="rounded-md p-2 text-navy hover:bg-bg-soft" aria-label="Sair"><LogOut className="h-4 w-4" /></button></div>
        </header>
        <main className="p-5 md:p-8">{children}</main>
      </div>
    </div>
  );
}
