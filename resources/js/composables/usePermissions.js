import { usePage } from '@inertiajs/vue3';

export function usePermissions() {
    const page = usePage();

    const can = (permission) => {
        const user = page.props.auth?.user;
        if (!user) return false;
        if (user.is_platform_user) return true;
        const permissions = page.props.auth?.permissions ?? [];
        if (permissions.includes('*')) return true;
        return permissions.includes(permission);
    };

    return { can };
}
