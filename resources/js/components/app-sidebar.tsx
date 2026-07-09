import { Link, usePage } from '@inertiajs/react';
import {
    BookText,
    Boxes,
    ChartColumn,
    Cog,
    FlaskConical,
    LayoutGrid,
    LibraryBig,
    MonitorPlay,
    MonitorSmartphone,
    PackageSearch,
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
        title: 'Learning Materials',
        href: '/app/admin/learning-materials',
        icon: BookText,
        iconClassName: 'text-indigo-600',
        children: [
            {
                title: 'Printed Materials',
                href: '/app/admin/learning-materials',
            },
            {
                title: 'ICT Equipments',
                href: '/app/admin/ict-equipment',
            },
            {
                title: 'Science and Math',
                href: '/app/admin/sme',
            },
            {
                title: 'Digital LMs',
                href: '/app/admin/digital-learning-materials',
            },
            {
                title: 'Other Materials',
                href: '/app/admin/other-equipment',
            },
        ],
    },
    {
        title: 'Catalogs',
        href: '/app/admin/resource-titles',
        icon: Boxes,
        iconClassName: 'text-purple-600',
        children: [
            {
                title: 'Printed Materials',
                href: '/app/admin/resource-titles',
            },
            {
                title: 'ICT Equipments',
                href: '/app/admin/ict-equipment-catalog',
            },
            {
                title: 'Science and Math',
                href: '/app/admin/sme-catalog',
            },
            {
                title: 'Digital LMs',
                href: '/app/admin/digital-learning-materials',
            },
            {
                title: 'Other Materials',
                href: '/app/admin/other-equipment-catalog',
            },
        ],
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
        title: 'Reference Data',
        href: '/app/admin/districts',
        icon: LibraryBig,
        iconClassName: 'text-violet-600',
        children: [
            {
                title: 'Districts',
                href: '/app/admin/districts',
            },
            {
                title: 'Municipalities',
                href: '/app/admin/municipalities',
            },
            {
                title: 'Barangays',
                href: '/app/admin/barangays',
            },
            {
                title: 'Learning Material Types',
                href: '/app/admin/learning-resource-types',
            },
            {
                title: 'School Years',
                href: '/app/admin/school-years',
            },
            {
                title: 'Grade Levels',
                href: '/app/admin/grade-levels',
            },
        ],
    },
    {
        title: 'Settings',
        href: '/app/admin/settings',
        icon: Cog,
        iconClassName: 'text-foreground',
    },
];

const adminWorkspaceRoles = ['admin', 'superadmin', 'sysadmin', 'ito', 'manager', 'librarian', 'supply', 'cidchief', 'asds', 'sds'];
const referenceDataRoles = ['admin', 'superadmin', 'sysadmin', 'ito'];
const catalogRoles = ['admin', 'superadmin', 'sysadmin', 'ito', 'manager', 'librarian', 'supply'];
const systemAdminRoles = ['admin', 'superadmin', 'sysadmin', 'ito'];

function canViewNavItem(item: NavItem, role: string | undefined): boolean {
    if (!role) {
        return false;
    }

    if (item.title === 'Reference Data') {
        return referenceDataRoles.includes(role);
    }

    if (item.title === 'Catalogs') {
        return catalogRoles.includes(role);
    }

    if (['Settings', 'Distributions'].includes(item.title)) {
        return systemAdminRoles.includes(role);
    }

    return true;
}

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

type SchoolShareData = {
    school_id: string;
    is_profile_complete?: boolean;
};

export function AppSidebar() {
    const page = usePage<{ auth: Auth; school?: { data: SchoolShareData } | SchoolShareData }>();
    const { auth } = page.props;
    const role = auth.user?.role;
    const isAdmin = adminWorkspaceRoles.includes(role ?? '');
    const visibleAdminNavItems = adminNavItems.filter((item) => canViewNavItem(item, role));
    const homeHref = isAdmin ? '/app/admin/dashboard' : '/dashboard';

    const getSchoolData = (): SchoolShareData | null => {
        const school = page.props.school as any;

        if (!school) {
            return null;
        }

        return 'data' in school ? school.data : school;
    };

    const schoolData = getSchoolData();
    const schoolId = schoolData?.school_id ?? null;
    const isProfileComplete = schoolData?.is_profile_complete ?? true;
    const updateSchoolHref = schoolId ? `/school/activate/${schoolId}` : null;

    const updateSchoolDetailsItem: NavItem = {
        title: 'Update School Details',
        href: updateSchoolHref ?? '',
        icon: Settings,
        iconClassName: 'text-amber-600',
    };

    const fullSchoolNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: '/dashboard',
            icon: LayoutGrid,
            iconClassName: 'text-muted-foreground',
        },
        {
            title: 'Printed Materials',
            href: '/school/learning-resources',
            icon: BookText,
            iconClassName: 'text-indigo-600',
        },
        {
            title: 'ICT Equipments',
            href: '/school/ict-equipment',
            icon: MonitorSmartphone,
            iconClassName: 'text-teal-600',
        },
        {
            title: 'Science and Math',
            href: '/school/sme',
            icon: FlaskConical,
            iconClassName: 'text-rose-600',
        },
        {
            title: 'Digital LMs',
            href: '/school/digital-learning-materials',
            icon: MonitorPlay,
            iconClassName: 'text-cyan-600',
        },
        {
            title: 'Other Materials',
            href: '/school/other-equipment',
            icon: PackageSearch,
            iconClassName: 'text-orange-600',
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
            title: 'Enrollment',
            href: '/school/enrollment',
            icon: Users,
            iconClassName: 'text-cyan-600',
        },
        ...(updateSchoolHref ? [updateSchoolDetailsItem] : []),
    ];

    const schoolNavItems: NavItem[] =
        !isProfileComplete && updateSchoolHref ? [updateSchoolDetailsItem] : fullSchoolNavItems;

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
                <NavMain items={isAdmin ? visibleAdminNavItems : schoolNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
