<script lang="ts" setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { 
    ArrowLeft, 
    PackageOpen, 
    ShieldCheck, 
    Hash, 
    FolderTree, 
    Eye, 
    Info,
    Tag,
    Shield,
    Ruler,
    Star,
    Layers // Added icon for variations
} from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

import { displayStock } from '@/routes';

const props = defineProps({
    part: {
        type: Object,
        required: true,
    }
});

// State
const activeImageIndex = ref(0);
// Track the currently selected variation (null means no variation selected / using base info)
const selectedVariation = ref<any>(null); 

// --- COMPUTED PROPERTIES FOR DYNAMIC DISPLAY ---

// Compute which price to show
const displayPrice = computed(() => {
    if (selectedVariation.value) {
        return Number(selectedVariation.value.price).toFixed(2);
    }
    return Number(props.part.price).toFixed(2);
});

// Compute which stock quantity to show
const displayStockQty = computed(() => {
    if (selectedVariation.value) {
        return selectedVariation.value.stock_quantity;
    }
    return props.part.stock_quantity;
});

// Compute which image to show in the main box
const mainImageSrc = computed(() => {
    // If a variation is selected AND it has a picture, show that specific picture
    if (selectedVariation.value && selectedVariation.value.picture) {
        return `/storage/${selectedVariation.value.picture}`;
    }
    // Otherwise, show the base part images based on the activeImageIndex
    if (props.part.part_images && props.part.part_images.length > 0) {
        return `/storage/${props.part.part_images[activeImageIndex.value]}`;
    }
    // If nothing exists, return null to show the placeholder
    return null;
});

// Action to select a variation
const selectVariation = (variation: any) => {
    // If clicking the already selected variation, deselect it
    if (selectedVariation.value && selectedVariation.value.variation_id === variation.variation_id) {
        selectedVariation.value = null;
    } else {
        selectedVariation.value = variation;
    }
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Display Stock', href: displayStock() },
            { title: 'View Part', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="part.name" />

    <div class="my-6 px-14 max-w-7xl ">
        <Button variant="ghost" class="text-gray-500 hover:text-gray-900 -ml-4" as-child>
            <Link :href="displayStock()">
                <ArrowLeft class="w-4 h-4 mr-2" />
                Back to Inventory
            </Link>
        </Button>
    </div>

    <div class="pb-12 px-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm ring-1 ring-gray-900/5 sm:rounded-2xl p-6 md:p-10">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
                    
                    <div class="lg:col-span-5 flex flex-col gap-4">
                        
                        <div class="aspect-square bg-gray-50 border border-gray-200 rounded-xl overflow-hidden flex items-center justify-center relative shadow-inner">
                            <img 
                                v-if="mainImageSrc" 
                                :src="mainImageSrc" 
                                :alt="part.name" 
                                class="object-contain w-full h-full p-4 transition-opacity duration-300"
                            />
                            <div v-else class="flex flex-col items-center justify-center text-gray-400">
                                <PackageOpen class="w-20 h-20 mb-4 opacity-50" />
                                <span class="text-sm font-medium">No Image Provided</span>
                            </div>
                        </div>

                        <div v-if="part.part_images && part.part_images.length > 1 && (!selectedVariation || !selectedVariation.picture)" class="flex gap-3 justify-center mt-2">
                            <button
                                v-for="(img, index) in part.part_images"
                                :key="index"
                                @click="activeImageIndex = index"
                                class="w-16 h-16 rounded-lg overflow-hidden border-2 transition-all duration-200 focus:outline-none"
                                :class="activeImageIndex === index ? 'border-primary ring-2 ring-primary/20' : 'border-transparent opacity-60 hover:opacity-100 hover:border-gray-200'"
                            >
                                <img :src="`/storage/${img}`" class="object-cover w-full h-full" />
                            </button>
                        </div>
                    </div>

                    <div class="lg:col-span-7 flex flex-col">
                        
                        <div class="flex items-start justify-between gap-4">
                            <h1 class="text-3xl font-bold text-gray-900 leading-tight">
                                {{ part.name }}
                                <span v-if="selectedVariation" class="text-xl text-primary block mt-1">
                                    - {{ selectedVariation.name }}
                                </span>
                            </h1>
                        </div>

                        <div class="mt-3 flex items-center gap-2">
                            <Badge :variant="part.is_visible_to_public ? 'default' : 'secondary'">
                                <Eye v-if="part.is_visible_to_public" class="w-3 h-3 mr-1" />
                                {{ part.is_visible_to_public ? 'Published to Catalog' : 'Hidden internally' }}
                            </Badge>
                        </div>

                        <div class="mt-6 bg-gray-50 border border-gray-100 rounded-xl p-6 flex flex-col gap-1">
                            <span class="text-sm text-gray-500 font-medium">Selling Price</span>
                            <div class="flex items-baseline gap-1 transition-all duration-200">
                                <span class="text-2xl font-bold text-primary">RM</span>
                                <span class="text-4xl font-extrabold text-primary tracking-tight">
                                    {{ displayPrice }}
                                </span>
                            </div>
                        </div>

                        <div v-if="part.variations && part.variations.length > 0" class="mt-8 flex flex-col gap-3">
                            <div class="flex items-center gap-2 text-gray-900 font-semibold text-lg">
                                <Layers class="w-5 h-5 text-gray-500" />
                                Select Variation
                            </div>
                            <div class="flex flex-wrap gap-3">
                                <button
                                    v-for="variation in part.variations"
                                    :key="variation.variation_id"
                                    @click="selectVariation(variation)"
                                    class="flex items-center gap-2 px-4 py-2 rounded-lg border-2 transition-all duration-200 focus:outline-none"
                                    :class="selectedVariation && selectedVariation.variation_id === variation.variation_id 
                                        ? 'border-primary bg-primary/5 text-primary font-semibold shadow-sm' 
                                        : 'border-gray-200 bg-white text-gray-700 hover:border-gray-300 hover:bg-gray-50'"
                                >
                                    <div v-if="variation.picture" class="w-6 h-6 rounded overflow-hidden border border-gray-200 flex-shrink-0">
                                        <img :src="`/storage/${variation.picture}`" class="object-cover w-full h-full" />
                                    </div>
                                    {{ variation.name }}
                                </button>
                            </div>
                        </div>

                        <div class="mt-8 grid grid-cols-1 sm:grid-cols-2 gap-y-6 gap-x-8">
                            
                            <div class="flex items-start gap-3">
                                <div class="p-2 bg-gray-50 rounded-lg text-gray-500">
                                    <ShieldCheck class="w-5 h-5" />
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Availability</p>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <div class="w-2 h-2 rounded-full" :class="displayStockQty > 0 ? 'bg-emerald-500' : 'bg-red-500'"></div>
                                        <p class="font-semibold text-gray-900 transition-all duration-200">
                                            {{ displayStockQty > 0 ? `${displayStockQty} Units in Stock` : 'Out of Stock' }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <div class="p-2 bg-gray-50 rounded-lg text-gray-500">
                                    <Hash class="w-5 h-5" />
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Serial Number</p>
                                    <p class="font-semibold text-gray-900 mt-0.5 uppercase">{{ part.part_serial_number }}</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <div class="p-2 bg-gray-50 rounded-lg text-gray-500">
                                    <FolderTree class="w-5 h-5" />
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Category</p>
                                    <p class="font-semibold text-gray-900 mt-0.5">{{ part.category?.name }}</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3" v-if="part.brand">
                                <div class="p-2 bg-gray-50 rounded-lg text-gray-500">
                                    <Tag class="w-5 h-5" />
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Brand</p>
                                    <p class="font-semibold text-gray-900 mt-0.5 uppercase">{{ part.brand }}</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3" v-if="part.warranty">
                                <div class="p-2 bg-gray-50 rounded-lg text-gray-500">
                                    <Shield class="w-5 h-5" />
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Warranty</p>
                                    <p class="font-semibold text-gray-900 mt-0.5">{{ part.warranty }}</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3" v-if="part.dimensions">
                                <div class="p-2 bg-gray-50 rounded-lg text-gray-500">
                                    <Ruler class="w-5 h-5" />
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Dimensions</p>
                                    <p class="font-semibold text-gray-900 mt-0.5">{{ part.dimensions }}</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3" v-if="part.condition">
                                <div class="p-2 bg-gray-50 rounded-lg text-gray-500">
                                    <Star class="w-5 h-5" />
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Condition</p>
                                    <p class="font-semibold text-gray-900 mt-0.5">
                                        {{ part.condition }} / 10
                                        <span v-if="part.condition >= 8">(New)</span>
                                        <span v-if="part.condition < 8 && part.condition >= 6">(Like New)</span>
                                        <span v-if="part.condition < 6 && part.condition >= 4">(Used)</span>
                                        <span v-if="part.condition < 4 && part.condition >= 2">(Heavily used)</span>
                                        <span v-if="part.condition < 2">(Poor)</span>
                                    </p>
                                </div>
                            </div>

                        </div>

                        <hr class="my-8 border-gray-100" />

                        <div class="flex flex-col gap-3">
                            <div class="flex items-center gap-2 text-gray-900 font-semibold text-lg">
                                <Info class="w-5 h-5 text-gray-500" />
                                Product Description
                            </div>
                            <div class="bg-gray-50/50 p-5 rounded-xl border border-gray-100">
                                <p v-if="part.part_description" class="text-gray-600 leading-relaxed whitespace-pre-line text-sm">
                                    {{ part.part_description }}
                                </p>
                                <p v-else class="text-gray-400 italic text-sm">
                                    No description provided for this automotive part.
                                </p>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</template>