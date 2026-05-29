<script setup>
import AdminListSearch from '@/Components/Admin/AdminListSearch.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';

defineOptions({ layout: AdminLayout });

defineProps({ languages: Array });

const page = usePage();
const tenant = page.props.tenant;

const form = useForm({ code: '', name: '', flag: '' });
const importForm = useForm({ code: '', file: null });

const submit = () => form.post(route('tenant.admin.languages.store', { tenant: tenant.slug }));

const importTranslations = () =>
    importForm.post(route('tenant.admin.languages.import', { tenant: tenant.slug }), {
        forceFormData: true,
    });
</script>

<template>
    <Head title="Idiomas" />
    <h1 class="admin-page-title mb-6">Idiomas</h1>
    <div class="grid gap-6 lg:grid-cols-2">
        <div class="admin-card">
            <h2 class="mb-3 font-semibold">Cadastrar idioma</h2>
            <form class="space-y-3" @submit.prevent="submit">
                <input v-model="form.code" placeholder="Código (ex: en)" class="admin-input" />
                <input v-model="form.name" placeholder="Nome" class="admin-input" />
                <input v-model="form.flag" placeholder="Bandeira (emoji)" class="admin-input" />
                <button type="submit" class="admin-btn-primary">Salvar</button>
            </form>
        </div>
        <div class="admin-card">
            <h2 class="mb-3 font-semibold">Importar traduções</h2>
            <a
                :href="route('tenant.admin.languages.export', { tenant: tenant.slug })"
                class="mb-3 block text-sm text-orange-600 underline"
            >
                Baixar template pt_BR
            </a>
            <form class="space-y-3" @submit.prevent="importTranslations">
                <input v-model="importForm.code" placeholder="Código do idioma" class="admin-input" />
                <input type="file" class="w-full" @change="importForm.file = $event.target.files[0]" />
                <button type="submit" class="admin-btn-secondary">Importar JSON</button>
            </form>
        </div>
    </div>
    <AdminListSearch
        :href="route('tenant.admin.languages.index', { tenant: tenant.slug })"
        :filters="filters"
        placeholder="Buscar idioma..."
    />

    <ul class="mt-6 space-y-2">
        <li v-for="lang in languages" :key="lang.id" class="admin-card flex items-center gap-2 py-3">
            <span>{{ lang.flag }}</span>
            <span>{{ lang.name }} ({{ lang.code }})</span>
            <span v-if="lang.is_default" class="rounded bg-orange-100 px-2 py-0.5 text-xs text-orange-800">Padrão</span>
        </li>
    </ul>
</template>
