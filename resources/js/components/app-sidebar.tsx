import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import {
    CreditCard,
    LayoutGrid,
    Tags,
    Users,
    WalletCards,
} from 'lucide-react';
import AppLogo from './app-logo';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        url: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Movimentações',
        url: '/movimentacoes',
        icon: WalletCards,
    },
    {
        title: 'Categorias',
        url: '/categorias',
        icon: Tags,
    },
    {
        title: 'Formas de pagamento',
        url: '/formas-pagamento',
        icon: CreditCard,
    },
];

const adminNavItems: NavItem[] = [
    {
        title: 'Usuários',
        url: '/admin/usuarios',
        icon: Users,
    },
];

export function AppSidebar() {
    const { auth } = usePage<SharedData>().props;

    const podeAcessarAdministracao =
        auth.user.is_admin && auth.user.is_active;

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/dashboard" prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} label="Menu" />

                {podeAcessarAdministracao && (
                    <NavMain
                        items={adminNavItems}
                        label="Administração"
                    />
                )}
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
