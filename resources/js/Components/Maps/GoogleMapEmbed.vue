<script setup>
import { loadGoogleMaps } from '@/composables/useGoogleMapsLoader';
import { onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    apiKey: { type: String, required: true },
    center: { type: Object, required: true },
    zoom: { type: Number, default: 15 },
    markers: { type: Array, default: () => [] },
    height: { type: String, default: '280px' },
});

const containerRef = ref(null);
const loadError = ref('');
let map = null;
let markerInstances = [];

const render = async () => {
    if (!containerRef.value || !props.center?.lat || !props.center?.lng) {
        return;
    }

    loadError.value = '';

    try {
        const maps = await loadGoogleMaps(props.apiKey);
        const center = { lat: Number(props.center.lat), lng: Number(props.center.lng) };

        if (!map) {
            map = new maps.Map(containerRef.value, {
                center,
                zoom: props.zoom,
                mapTypeControl: false,
                streetViewControl: false,
                fullscreenControl: true,
            });
        } else {
            map.setCenter(center);
            map.setZoom(props.zoom);
        }

        markerInstances.forEach((m) => m.setMap(null));
        markerInstances = [];

        const list = props.markers?.length
            ? props.markers
            : [{ lat: center.lat, lng: center.lng, title: '' }];

        for (const item of list) {
            if (item.lat == null || item.lng == null) {
                continue;
            }
            markerInstances.push(
                new maps.Marker({
                    map,
                    position: { lat: Number(item.lat), lng: Number(item.lng) },
                    title: item.title ?? '',
                }),
            );
        }
    } catch (e) {
        loadError.value = e?.message ?? 'Não foi possível carregar o mapa.';
    }
};

onMounted(() => render());
watch(() => [props.center, props.markers, props.zoom], () => render(), { deep: true });
onUnmounted(() => {
    markerInstances.forEach((m) => m.setMap(null));
    markerInstances = [];
    map = null;
});
</script>

<template>
    <div>
        <div
            v-if="!loadError"
            ref="containerRef"
            class="w-full overflow-hidden rounded-xl ring-1 ring-stone-200"
            :style="{ height }"
        />
        <p v-else class="rounded-xl bg-amber-50 px-3 py-2 text-sm text-amber-900">{{ loadError }}</p>
    </div>
</template>
