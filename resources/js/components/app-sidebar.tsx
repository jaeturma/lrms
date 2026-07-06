import { Link, usePage } from '@inertiajs/react';
import {
    BookOpen,
    BookText,
    Boxes,
    CalendarRange,
    ChartColumn,
    Cog,
    GraduationCap,
    LayoutGrid,
    MapPinned,
    Map,
    MapPin,
    MonitorSmartphone,
    School,
    Settings,
    Truck,
    Users,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
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
import type { Auth, NavItem } from '@/types';

const adminNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/app/admin/dashboard',
        icon: LayoutGrid,
        iconClassName: 'text-muted-foreground',
    },
    {
        title: 'Schools',
        href: '/app/admin/schools',
        icon: School,
        iconClassName: 'text-blue-600',
    },
    {
        title: 'Districts',
        href: '/app/admin/districts',
        icon: MapPinned,
        iconClassName: 'text-amber-600',
    },
    {
        title: 'Municipalities',
        href: '/app/admin/municipalities',
        icon: Map,
        iconClassName: 'text-emerald-600',
    },
    {
        title: 'Barangays',
        href: '/app/admin/barangays',
        icon: MapPin,
        iconClassName: 'text-rose-600',
    },
    {
        title: 'Learning Materials',
        href: '/app/admin/learning-materials',
        icon: BookText,
        iconClassName: 'text-indigo-600',
    },
    {
        title: 'Resource Catalog',
        href: '/app/admin/resource-titles',
        icon: BookOpen,
        iconClassName: 'text-sky-600',
    },
    {
        title: 'Equipment',
        href: '/app/admin/equipment',
        icon: MonitorSmartphone,
        iconClassName: 'text-teal-600',
    },
    {
        title: 'Distributions',
        href: '/app/admin/distributions',
        icon: Truck,
        iconClassName: 'text-lime-600',
    },
    {
        title: 'Reports',
        href: '/app/admin/reports',
        icon: ChartColumn,
        iconClassName: 'text-orange-600',
    },
    {
        title: 'Learning Material Types',
        href: '/app/admin/learning-resource-types',
        icon: BookText,
        iconClassName: 'text-violet-600',
    },
    {
        title: 'School Years',
        href: '/app/admin/school-years',
        icon: CalendarRange,
        iconClassName: 'text-cyan-600',
    },
    {
        title: 'Grade Levels',
        href: '/app/admin/grade-levels',
        icon: GraduationCap,
        iconClassName: 'text-fuchsia-600',
    },
    {
        title: 'Settings',
        href: '/app/admin/settings',
        icon: Cog,
        iconClassName: 'text-foreground',
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Support',
        href: '/support',
        icon: BookText,
        iconClassName: 'text-blue-600',
    },
    {
        title: 'About the App',
        href: '/about',
        icon: BookText,
        iconClassName: 'text-emerald-600',
    },
];

export function AppSidebar() {
    const page = usePage<{ auth: Auth; school?: { data: { school_id: string } } | { school_id: string } }>();
    const { auth } = page.props;
    const isAdmin = auth.user?.role === 'admin';
    const homeHref = isAdmin ? '/app/admin/dashboard' : '/dashboard';

    const getSchoolId = (): string | null => {
        const school = page.props.school as any;

        if (!school) {
            return null;
        }

        return 'data' in school ? school.data.school_id : school.school_id;
    };

    const schoolId = getSchoolId();
    const updateSchoolHref = schoolId ? `/school/activate/${schoolId}` : null;

    const schoolNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: '/dashboard',
            icon: LayoutGrid,
            iconClassName: 'text-muted-foreground',
        },
        {
            title: 'Learning Resources',
            href: '/school/learning-resources',
            icon: BookText,
            iconClassName: 'text-indigo-600',
        },
        {
            title: 'Inventory',
            href: '/school/inventory',
            icon: Boxes,
            iconClassName: 'text-emerald-600',
        },
        {
            title: 'Deliveries',
            href: '/school/distributions',
            icon: Truck,
            iconClassName: 'text-lime-600',
        },
        {
            title: 'Equipment',
            href: '/school/equipment',
            icon: MonitorSmartphone,
            iconClassName: 'text-teal-600',
        },
        {
            title: 'Enrollment',
            href: '/school/enrollment',
            icon: Users,
            iconClassName: 'text-cyan-600',
        },
        ...(updateSchoolHref
            ? [
                  {
                      title: 'Update School Details',
                      href: updateSchoolHref,
                      icon: Settings,
                      iconClassName: 'text-amber-600',
                  },
              ]
            : []),
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={homeHref} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={isAdmin ? adminNavItems : schoolNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
