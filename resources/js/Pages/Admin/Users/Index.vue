<script setup>
import RolePermissionPreview from '@/Components/Admin/RolePermissionPreview.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    users: Array,
    roles: Array,
    branches: Array,
    rolePermissions: { type: Object, default: () => ({}) },
});

const page = usePage();
const tenant = page.props.tenant;
const editingId = ref(null);

const roleLabels = {
    tenant_admin: 'Administrador',
    manager: 'Gerente',
    operator: 'Operador',
    viewer: 'Visualizador',
    branch_staff: 'Equipe da filial',
};

const form = useForm({
    name: '',
    email: '',
    phone: '',
    password: '',
    role: 'operator',
    access_all_branches: true,
    branch_ids: [],
});

const editForm = useForm({
    name: '',
    email: '',
    phone: '',
    password: '',
    role: 'operator',
    access_all_branches: true,
    branch_ids: [],
});

const showBranchPicker = (role, accessAll) =>
    role !== 'tenant_admin' && !accessAll && props.branches?.length;

const formShowsBranches = computed(() => showBranchPicker(form.role, form.access_all_branches));
const editShowsBranches = computed(() => showBranchPicker(editForm.role, editForm.access_all_branches));

const toggleBranch = (target, id) => {
    const ids = target.branch_ids.includes(id)
        ? target.branch_ids.filter((x) => x !== id)
        : [...target.branch_ids, id];
    target.branch_ids = ids;
};

const submit = () =>
    form.post(route('tenant.admin.users.store', { tenant: tenant.slug }), {
        onSuccess: () => form.reset(),
    });

const startEdit = (user) => {
    editingId.value = user.id;
    editForm.name = user.name;
    editForm.email = user.email;
    editForm.phone = user.phone ?? '';
    editForm.password = '';
    editForm.role = user.roles[0] ?? 'operator';
    editForm.access_all_branches = user.access_all_branches ?? true;
    editForm.branch_ids = [...(user.branch_ids ?? [])];
};

const submitEdit = (id) =>
    editForm.put(route('tenant.admin.users.update', { tenant: tenant.slug, user: id }), {
        onSuccess: () => {
            editingId.value = null;
            editForm.reset();
        },
    });

const remove = (id) => {
    if (!confirm('Remover este usuário?')) return;
    router.delete(route('tenant.admin.users.destroy', { tenant: tenant.slug, user: id }));
};
</script>

<template>
    <Head title="Usuários" />

    <h1 class="admin-page-title">Usuários e permissões</h1>
    <p class="mt-1 text-sm text-stone-600">
        Gerencie quem acessa o painel. Use <strong>Equipe da filial</strong> para limitar pedidos, KDS e chat a unidades
        específicas.
    </p>

    <div class="mt-8 grid gap-8 lg:grid-cols-2">
        <div class="admin-card">
            <h2 class="font-semibold text-stone-900">Novo usuário</h2>
            <form class="mt-4 space-y-3" @submit.prevent="submit">
                <input v-model="form.name" class="admin-input" placeholder="Nome" required />
                <input v-model="form.email" type="email" class="admin-input" placeholder="E-mail" required />
                <input v-model="form.phone" class="admin-input" placeholder="Telefone (opcional)" />
                <input v-model="form.password" type="password" class="admin-input" placeholder="Senha" required />
                <select v-model="form.role" class="admin-input">
                    <option v-for="role in roles" :key="role" :value="role">
                        {{ roleLabels[role] || role }}
                    </option>
                </select>
                <RolePermissionPreview
                    :role="form.role"
                    :role-permissions="rolePermissions"
                    :role-labels="roleLabels"
                />
                <label
                    v-if="form.role !== 'tenant_admin'"
                    class="flex cursor-pointer items-center gap-2 text-sm text-stone-700"
                >
                    <input v-model="form.access_all_branches" type="checkbox" class="rounded border-stone-300" />
                    Acesso a todas as filiais
                </label>
                <div v-if="formShowsBranches" class="rounded-xl border border-stone-200 bg-stone-50 p-3">
                    <p class="mb-2 text-xs font-medium text-stone-600">Filiais permitidas</p>
                    <label
                        v-for="b in branches"
                        :key="b.id"
                        class="flex cursor-pointer items-center gap-2 py-1 text-sm"
                    >
                        <input
                            type="checkbox"
                            :checked="form.branch_ids.includes(b.id)"
                            @change="toggleBranch(form, b.id)"
                        />
                        {{ b.name }}
                    </label>
                </div>
                <button type="submit" class="admin-btn-primary w-full" :disabled="form.processing">Criar usuário</button>
            </form>
        </div>

        <div class="admin-card lg:col-span-1">
            <h2 class="font-semibold text-stone-900">Equipe ({{ users.length }})</h2>
            <ul class="mt-4 divide-y divide-stone-100">
                <li v-for="user in users" :key="user.id" class="py-4">
                    <template v-if="editingId !== user.id">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <p class="font-medium text-stone-900">{{ user.name }}</p>
                                <p class="text-sm text-stone-500">{{ user.email }}</p>
                                <span class="mt-1 inline-block rounded-full bg-orange-50 px-2 py-0.5 text-xs font-medium text-orange-800">
                                    {{ roleLabels[user.roles[0]] || user.roles[0] }}
                                </span>
                                <span v-if="user.is_protected_admin" class="ml-1 text-xs text-stone-400">(protegido)</span>
                                <p v-if="!user.access_all_branches && user.branch_names?.length" class="mt-1 text-xs text-stone-500">
                                    Filiais: {{ user.branch_names.join(', ') }}
                                </p>
                            </div>
                            <div class="flex shrink-0 gap-2">
                                <button type="button" class="text-sm text-stone-600 hover:text-orange-600" @click="startEdit(user)">
                                    Editar
                                </button>
                                <button
                                    v-if="!user.is_protected_admin"
                                    type="button"
                                    class="text-sm text-red-600 hover:text-red-700"
                                    @click="remove(user.id)"
                                >
                                    Remover
                                </button>
                            </div>
                        </div>
                    </template>
                    <form v-else class="space-y-2" @submit.prevent="submitEdit(user.id)">
                        <input v-model="editForm.name" class="admin-input" required />
                        <input v-model="editForm.email" type="email" class="admin-input" required />
                        <input v-model="editForm.phone" class="admin-input" />
                        <input v-model="editForm.password" type="password" class="admin-input" placeholder="Nova senha (opcional)" />
                        <select v-model="editForm.role" class="admin-input" :disabled="user.is_protected_admin">
                            <option v-for="role in roles" :key="role" :value="role">
                                {{ roleLabels[role] || role }}
                            </option>
                        </select>
                        <RolePermissionPreview
                            v-if="!user.is_protected_admin"
                            :role="editForm.role"
                            :role-permissions="rolePermissions"
                            :role-labels="roleLabels"
                        />
                        <label
                            v-if="editForm.role !== 'tenant_admin' && !user.is_protected_admin"
                            class="flex cursor-pointer items-center gap-2 text-sm"
                        >
                            <input v-model="editForm.access_all_branches" type="checkbox" class="rounded border-stone-300" />
                            Acesso a todas as filiais
                        </label>
                        <div v-if="editShowsBranches" class="rounded-xl border border-stone-200 bg-stone-50 p-3">
                            <p class="mb-2 text-xs font-medium text-stone-600">Filiais permitidas</p>
                            <label
                                v-for="b in branches"
                                :key="b.id"
                                class="flex cursor-pointer items-center gap-2 py-1 text-sm"
                            >
                                <input
                                    type="checkbox"
                                    :checked="editForm.branch_ids.includes(b.id)"
                                    @change="toggleBranch(editForm, b.id)"
                                />
                                {{ b.name }}
                            </label>
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" class="admin-btn-primary" :disabled="editForm.processing">Salvar</button>
                            <button type="button" class="rounded-xl border px-4 py-2 text-sm" @click="editingId = null">Cancelar</button>
                        </div>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</template>
