import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { useMobileNavigation } from '@/hooks/use-mobile-navigation';

type NavMainProps = {
    items?: NavItem[];
    label?: string;
};

export function NavMain({
    items = [],
    label = 'Menu',
}: NavMainProps) {
    const page = usePage();
    const fecharMenuMobile = useMobileNavigation();

    return (
        <SidebarGroup className="px-2 py-0">
            <SidebarGroupLabel>{label}</SidebarGroupLabel>

            <SidebarMenu>
                {items.map((item) => {
                    const isActive =
                        page.url === item.url ||
                        page.url.startsWith(`${item.url}/`);

                    return (
                        <SidebarMenuItem key={item.title}>
                            <SidebarMenuButton
                                asChild
                                isActive={isActive}
                                className="min-h-11 md:min-h-8"
                            >
                                <Link
                                    href={item.url}
                                    prefetch
                                    onClick={fecharMenuMobile}
                                >
                                    {item.icon && <item.icon />}
                                    <span>{item.title}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    );
                })}
            </SidebarMenu>
        </SidebarGroup>
    );
}
