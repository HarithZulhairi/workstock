<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { toast } from 'vue-sonner';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import debounce from 'lodash/debounce'; 
import { Warehouse, Pencil, Trash, PackageOpen, Eye, EyeOff, Search, FilterX, Plus, CircleAlert, TriangleAlert, ArrowUp, ArrowDown, ArrowUpDown, HelpCircle } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge'; 
import { Button } from '@/components/ui/button';
import { Switch } from '@/components/ui/switch';

import {
  HoverCard,
  HoverCardContent,
  HoverCardTrigger,
} from '@/components/ui/hover-card'

import {
  Dialog,
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog'

import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'; 

import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'

import { createStock, displayStock, viewStock, addStock, destroyStock, editStock } from '@/routes';

import {
  Pagination,
  PaginationContent,
  PaginationEllipsis,
  PaginationItem,
  PaginationNext,
  PaginationPrevious,
} from '@/components/ui/pagination'

import {
  NumberField,
  NumberFieldContent,
  NumberFieldDecrement,
  NumberFieldIncrement,
  NumberFieldInput,
} from '@/components/ui/number-field'

import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from '@/components/ui/alert-dialog';



const props = defineProps({
    parts: { type: Object, required: true },
    categories: { type: Array, required: true }, // Receive categories from backend
    filters: { type: Object, default: () => ({}) }, // Receive active filters
});

console.log('Initial Filters from Props:', props.parts.data);

// Use Inertia's usePage to access globally shared flash messages
const page = usePage();

const isPreviewOpen = ref(false);
const previewImageUrl = ref('');

// We'll use local refs so we can control when it appears and disappears
const showSuccessToast = ref(false);
const toastMessage = ref('');
const flashSuccess = computed(() => page.props.flash?.success);

watch(flashSuccess, (newVal) => {
    if (newVal) {
        toastMessage.value = newVal;
        showSuccessToast.value = true;
        
        // Clear the global Inertia state so it doesn't fire again on navigation
        page.props.flash.success = null; 

        // Auto-hide the toast after 3 seconds
        setTimeout(() => {
            showSuccessToast.value = false;
        }, 3000);
    }
}, { immediate: true });

// Setup Reactive Form State initialized with URL parameters
const filterForm = ref({
    search: props.filters.search || '',
    category_id: props.filters.category_id || 'all',
    min_price: props.filters.min_price || '',
    max_price: props.filters.max_price || '',
    min_stock: props.filters.min_stock || '',
    max_stock: props.filters.max_stock || '',
    low_stock: props.filters.low_stock === 'true' || props.filters.low_stock === true,
    no_stock: props.filters.no_stock === 'true' || props.filters.no_stock === true,
    visibility: props.filters.visibility || 'all',
    
    sort_by: props.filters.sort_by || '',
    sort_direction: props.filters.sort_direction || '',
});

// Watch for any changes in the filterForm, then ping the server
watch(filterForm, debounce((newFilters) => {
    router.get(displayStock(), newFilters, {
        preserveState: true,
        preserveScroll: true,
        replace: true, // Replaces the current history state so the back button doesn't break
    });
}, 300), { deep: true }); // 300ms delay, deep watch for nested object changes

// Function to handle clicking a table header
const toggleSort = (column: string) => {
    if (filterForm.value.sort_by === column) {
        // Toggle direction: asc -> desc -> reset
        if (filterForm.value.sort_direction === 'asc') {
            filterForm.value.sort_direction = 'desc';
        } else {
            filterForm.value.sort_by = '';
            filterForm.value.sort_direction = '';
        }
    } else {
        // Set new sort column
        filterForm.value.sort_by = column;
        filterForm.value.sort_direction = 'asc';
    }
};

const clearFilters = () => {
    filterForm.value = {
        search: '',
        category_id: 'all',
        min_price: '',
        max_price: '',
        min_stock: '',
        max_stock: '',
        low_stock: false,
        no_stock: false,
        visibility: 'all',
        sort_by: '',
        sort_direction: '',
    };
};

const handlePageChange = (pageNumber: number) => {
    router.get(displayStock(), { ...filterForm.value, page: pageNumber }, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Calculate the total combined stock of base + variations
const getTotalStock = (part: any) => {
    let total = Number(part.stock_quantity) || 0;
    if (part.variations && part.variations.length > 0) {
        total += part.variations.reduce((sum: number, v: any) => sum + (Number(v.stock_quantity)), 0);
    }
    return total;
};

// Determine the worst-case status for the color indicator FOR INDEX
const getStockLevelStatus = (part: any) => {

    // 1. Check if ANY item (base or variation) is completely out of stock
    const hasOutOfStock = part.stock_quantity === 0 || 
                         (part.variations && part.variations.some((v: any) => v.stock_quantity === 0));
    
    if (hasOutOfStock) return 'danger';

    // 2. Check if ANY item (base or variation) is running low (1 to 5 units)
    const hasLowStock = part.stock_quantity <= 5 || 
                       (part.variations && part.variations.some((v: any) => v.stock_quantity <= 5));
    
    if (hasLowStock) return 'warning';

    // 3. Everything is healthy
    return 'healthy';
};

// Determine the projected stock status based on current stock + un-saved input FOR ADJUST STOCK DIALOG
const getProjectedStockLevelStatus = (part: any) => {

    if (part.temp_base_add) {
        const projectedBaseStock = Number(part.stock_quantity) + (Number(part.temp_base_add) || 0);
        if (projectedBaseStock <= 0) return 'danger';
        if (projectedBaseStock <= 5) return 'warning';
        return 'healthy';
    }

    const projectedVarStock = Number(part.stock_quantity) + (Number(part.temp_add_quantity) || 0);
    if (projectedVarStock <= 0) return 'danger';
    if (projectedVarStock <= 5) return 'warning';
    return 'healthy';

};


const submitRowStock = (partId: number, baseStockAdd: number | string, variationsData: any[]) => {
    const baseAdd = Number(baseStockAdd) || 0;
    
    const formattedVariations = (variationsData || []).map(v => ({
        variation_id: v.variation_id,
        add_quantity: Number(v.temp_add_quantity) || 0
    }));

    router.post(addStock(partId), {
        base_stock_add: baseAdd,
        variations: formattedVariations
    }, {
        preserveScroll: true,
    });
};

const deletePart = (id: number) => {
    router.post(destroyStock(id), { },{
        preserveScroll: true,
    });
};

const openPreview = (url: string) => {
    previewImageUrl.value = url;
    isPreviewOpen.value = true;
};

console.log(filterForm.value.low_stock);
console.log(filterForm.value.no_stock);


defineOptions({ 
    layout: { breadcrumbs: 
        [ 
            { 
                title: 'Stock Inventory', 
                href: displayStock() 
            } 
        ] 
    } 
});

</script>

<template>
    <Head title="Display Stock" />

    <div class="my-6 px-12 max-w-7xl flex items-start justify-between gap-4">
      <div class="flex items-center gap-4">
          <div>
            <img src="/images/inventory.png" alt="WorkStock Logo" class="h-12 w-12" />
          </div>
          <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">Inventory Dashboard</h1>
            <p class="text-sm text-gray-500 mt-1">Manage, update, and monitor your automotive parts.</p>
          </div>
      </div>
      
      <Button as-child class="shadow-sm bg-green-600 hover:bg-green-700 text-white">
        <Link :href="createStock()">
            <Plus class="w-4 h-4" />
            Add New Part
        </Link>
      </Button>
    </div>

    <Transition
        enter-active-class="transform ease-out duration-300 transition"
        enter-from-class="-translate-y-4 opacity-0 scale-95"
        enter-to-class="translate-y-0 opacity-100 scale-100"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="-translate-y-4 opacity-0 scale-95"
    >
        <div 
            v-if="showSuccessToast" 
            class="fixed top-6 left-1/2 -translate-x-1/2 z-50 flex items-center gap-3 p-4 w-full min-w-[300px] max-w-sm rounded-xl shadow-lg border bg-emerald-50 border-emerald-200 text-emerald-900"
        >
            <div class="flex-shrink-0">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            
            <p class="font-medium text-sm flex-1">{{ toastMessage }}</p>
            
            <button @click="showSuccessToast = false" class="text-emerald-500 hover:text-emerald-700 transition-colors focus:outline-none">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    </Transition>

    <div class="pb-12 pt-4 px-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl">
                
                <div class="bg-gray-50/50 border-b border-gray-100 p-4 flex flex-col gap-4">
                    <div class="flex gap-3">
                        <div class="relative flex-1">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                            <Input v-model="filterForm.search" placeholder="Search by part name or serial number..." class="pl-9 bg-white" />
                        </div>
                        <Button variant="outline" @click="clearFilters" class="text-gray-500 gap-2">
                            <FilterX class="w-4 h-4" /> Clear Filters
                        </Button>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-6 gap-3">
                        <Select v-model="filterForm.category_id">
                            <SelectTrigger class="bg-white"><SelectValue placeholder="Category" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Categories</SelectItem>
                                <SelectItem v-for="cat in categories" :key="cat.category_id" :value="cat.category_id.toString()">
                                    {{ cat.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>

                        <Select v-model="filterForm.visibility">
                            <SelectTrigger class="bg-white"><SelectValue placeholder="Visibility" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Visibility</SelectItem>
                                <SelectItem value="public">Public Only</SelectItem>
                                <SelectItem value="hidden">Hidden Only</SelectItem>
                            </SelectContent>
                        </Select>

                        <Input v-model="filterForm.min_price" type="number" placeholder="Min Price (RM)" class="bg-white" />
                        <Input v-model="filterForm.max_price" type="number" placeholder="Max Price (RM)" class="bg-white" />

                        <div class="flex items-center justify-between h-10 px-3 bg-white border border-gray-200 rounded-md shadow-sm transition-colors hover:border-gray-300">
                            <Label for="filter-low-stock" class="text-sm font-normal text-gray-500 cursor-pointer select-none">
                                Low Stock
                                <HoverCard :open-delay="200">
                                    <HoverCardTrigger as-child>
                                        <button @click.stop class="outline-none cursor-help mt-0.5">
                                            <HelpCircle class="h-3.5 w-3.5 text-muted-foreground hover:text-primary transition-colors" />
                                        </button>
                                    </HoverCardTrigger>
                                    <HoverCardContent side="top" class="w-54 text-sm font-normal normal-case text-center bg-white">
                                        <p class=" text-gray-900">Stocks that is under 5 units</p>
                                    </HoverCardContent>
                                </HoverCard>
                            </Label>
                            <Switch id="filter-low-stock" v-model="filterForm.low_stock" />
                        </div>

                        <div class="flex items-center justify-between h-10 px-3 bg-white border border-gray-200 rounded-md shadow-sm transition-colors hover:border-gray-300">
                            <Label for="filter-no-stock" class="text-sm font-normal text-gray-500 cursor-pointer select-none">
                                Out of Stock
                                <HoverCard :open-delay="200">
                                    <HoverCardTrigger as-child>
                                        <button @click.stop class="outline-none cursor-help mt-0.5">
                                            <HelpCircle class="h-3.5 w-3.5 text-muted-foreground hover:text-primary transition-colors" />
                                        </button>
                                    </HoverCardTrigger>
                                    <HoverCardContent side="top" class="w-48 text-sm font-normal normal-case text-center bg-white">
                                        <p class=" text-gray-900">Stocks that have 0 unit</p>
                                    </HoverCardContent>
                                </HoverCard>
                            </Label>
                            <Switch id="filter-no-stock" v-model="filterForm.no_stock" />
                        </div>
                    </div>
                </div>
                <Table>
                  <TableHeader class="bg-gray-50/80 border-b border-gray-100">
                    <TableRow class="hover:bg-transparent">
                        <TableHead class="font-semibold text-gray-700 w-[30%] pl-6">Part Details</TableHead>
                        <TableHead class="font-semibold text-gray-700">Category</TableHead>
                        
                        <TableHead class="font-semibold text-gray-700 cursor-pointer hover:bg-gray-200 select-none transition-colors group" @click="toggleSort('price')">
                            <div class="flex items-center gap-1.5">
                                Price (RM)
                                <ArrowUp v-if="filterForm.sort_by === 'price' && filterForm.sort_direction === 'asc'" class="w-3.5 h-3.5 text-gray-900" />
                                <ArrowDown v-else-if="filterForm.sort_by === 'price' && filterForm.sort_direction === 'desc'" class="w-3.5 h-3.5 text-gray-900" />
                                <ArrowUpDown v-else class="w-3.5 h-3.5 text-gray-400 group-hover:text-gray-600" />
                            </div>
                        </TableHead>

                        <TableHead class="font-semibold text-gray-700 cursor-pointer hover:bg-gray-200 select-none transition-colors group" @click="toggleSort('stock')">
                            <div class="flex items-center gap-1.5">
                                Stock Status
                                <ArrowUp v-if="filterForm.sort_by === 'stock' && filterForm.sort_direction === 'asc'" class="w-3.5 h-3.5 text-gray-900" />
                                <ArrowDown v-else-if="filterForm.sort_by === 'stock' && filterForm.sort_direction === 'desc'" class="w-3.5 h-3.5 text-gray-900" />
                                <ArrowUpDown v-else class="w-3.5 h-3.5 text-gray-400 group-hover:text-gray-600" />
                            </div>
                        </TableHead>
                        
                        <TableHead class="font-semibold text-gray-700">Visibility</TableHead>
                        <TableHead class="font-semibold text-gray-700 text-right pr-6">Actions</TableHead>
                    </TableRow>
                    </TableHeader>
                  <TableBody>
                    
                    <TableRow 
                        v-for="part in parts.data" 
                        :key="part.automotive_parts_id"
                        class="group hover:bg-gray-50/50 transition-colors"
                    >
                      <TableCell class="pl-6 py-4">
                        <div class="flex items-center gap-4">
                            <div 
                                @click="openPreview(`/storage/${part.part_images[0]}`)"
                                class="h-12 w-12 flex-shrink-0 rounded-lg overflow-hidden bg-gray-50 border border-gray-200 shadow-sm flex items-center justify-center relative group-hover:border-gray-300 transition-colors"
                                >
                              <img 
                                v-if="part.part_images && part.part_images.length > 0" 
                                :src="`/storage/${part.part_images[0]}`" 
                                alt="Part Image Thumbnail" 
                                class="object-cover w-full h-full transition-transform hover:scale-110 cursor-pointer" 
                              />
                              <PackageOpen v-else class="w-5 h-5 text-gray-300" />
                            </div>

                            <div class="flex flex-col">
                                <span class="font-semibold text-gray-900 leading-tight flex items-center gap-2">
                                    {{ part.name }}
                                    <span v-if="part.brand" class="text-xs font-medium bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded uppercase tracking-wider border border-gray-200">
                                        {{ part.brand }}
                                    </span>
                                    <HoverCard :open-delay="200">
                                        <HoverCardTrigger as-child>
                                            <TriangleAlert 
                                                v-if="part.stock_quantity <= 5 || (part.variations && part.variations.some(v => v.stock_quantity <= 5))" 
                                                class="w-6 h-6 hover:opacity-70 transition-colors cursor-help"
                                                :class="{
                                                    'text-amber-500': getStockLevelStatus(part) === 'warning',
                                                    'text-red-500': getStockLevelStatus(part) === 'danger'
                                                }" 
                                            />
                                        </HoverCardTrigger>
                                        <HoverCardContent side="top" class="w-64 text-sm font-normal normal-case bg-white">
                                            <!-- <div class="flex justify-between space-x-4">
                                                <div class="space-y-1">
                                                    <h4 class="text-sm font-semibold">
                                                        Some 
                                                    </h4>
                                                    <p class="text-sm">
                                                        The Vue Framework – The Progressive Web Framework.
                                                    </p>
                                                    <div class="flex items-center pt-2">
                                                        <CalendarDaysIcon class="mr-2 h-4 w-4 opacity-70" />
                                                        <span class="text-xs text-muted-foreground">
                                                            Joined December 2021
                                                        </span>
                                                    </div>
                                                </div>
                                            </div> -->

                                            <div v-if="getStockLevelStatus(part) === 'warning'" class="space-y-1 text-left">
                                                <p class="font-semibold text-gray-900">Stocks under 5 units</p>
                                                <p class="text-xs text-muted-foreground leading-relaxed">
                                                    This part has low stock levels. Adjust Availability to add more stock soon.
                                                </p>
                                            </div>
                                            <div v-else-if="getStockLevelStatus(part) === 'danger'" class="space-y-1 text-left">
                                                <p class="font-semibold text-gray-900">Out of Stocks</p>
                                                <p class="text-xs text-muted-foreground leading-relaxed">
                                                    This part has currently out of stock. Adjust Availability to add more stock.
                                                </p>
                                            </div>
                                        </HoverCardContent>
                                    </HoverCard>
                                </span>
                                <span class="text-xs font-medium text-gray-500 mt-0.5 tracking-wide uppercase">{{ part.part_serial_number }}</span>
                            </div>
                        </div>
                      </TableCell>

                      <TableCell>
                          {{ part.category ? part.category.name : 'Uncategorized' }}
                      </TableCell>

                      <TableCell class="font-medium text-gray-700">
                          {{ Number(part.price).toFixed(2) }}
                      </TableCell>

                      <TableCell>
                        <div class="flex flex-col gap-1">
                            <div class="flex items-center gap-2">
                                <div 
                                    class="w-2 h-2 rounded-full flex-shrink-0"
                                    :class="{
                                        'bg-emerald-500': getStockLevelStatus(part) === 'healthy',
                                        'bg-amber-500': getStockLevelStatus(part) === 'warning',
                                        'bg-red-500': getStockLevelStatus(part) === 'danger'
                                    }"
                                ></div>
                                
                                <span 
                                    class="font-medium"
                                    :class="{
                                        'text-emerald-700': getStockLevelStatus(part) === 'healthy',
                                        'text-amber-700': getStockLevelStatus(part) === 'warning',
                                        'text-red-700': getStockLevelStatus(part) === 'danger'
                                    }"
                                >
                                    {{ getTotalStock(part) }} total in stock
                                </span>
                            </div>

                            <span v-if="part.variations && part.variations.length > 0" class="text-[10px] text-gray-500 ml-4 leading-tight">
                               Variation: {{ part.variations.length }}
                                <span v-if="getStockLevelStatus(part) !== 'healthy'" class="text-amber-600 font-medium block">
                                    (Some variations low/empty)
                                </span>
                            </span>
                        </div>
                    </TableCell>

                      <TableCell>
                          <Badge 
                            :variant="part.is_visible_to_public ? 'default' : 'secondary'"
                            class="flex w-fit items-center gap-1.5 text-xs font-medium"
                          >
                            <Eye v-if="part.is_visible_to_public" class="w-3 h-3" />
                            <EyeOff v-else class="w-3 h-3 text-gray-500" />
                            {{ part.is_visible_to_public ? 'Public' : 'Hidden' }}
                          </Badge>
                      </TableCell>

                      <TableCell class="text-right pr-6">
                        <div class="flex items-center justify-end gap-2">
                            <Dialog>
                                <DialogTrigger as-child>
                                    <Button variant="ghost" size="icon" title="Adjust Availability" class="h-8 w-8 text-gray-500 hover:text-green-600 hover:bg-green-50 cursor-pointer">
                                        <Plus class="w-4 h-4" />
                                    </Button>
                                </DialogTrigger>

                                <DialogContent class="sm:max-w-[425px] text-left">
                                    <form @submit.prevent="submitRowStock(part.automotive_parts_id, part.temp_base_add, part.variations)">
                                        <DialogHeader>
                                            <DialogTitle class="flex items-center gap-2">
                                                <div class="p-2 bg-green-100/80 text-green-500 border-1 border-green-200 rounded-xl">
                                                    <Plus class="w-4 h-4" />
                                                </div>
                                                <div>
                                                    Adjust Availability
                                                </div>
                                            </DialogTitle>
                                            <DialogDescription>
                                                Add or subtract the amount of stock for this part. Use negative numbers to correct over-added stock.
                                            </DialogDescription>
                                        </DialogHeader>
                                        
                                        <div class="grid gap-6 py-4">
                                            
                                            <div class="grid gap-2 bg-gray-50 p-4 rounded-lg border border-gray-100">
                                                <NumberField 
                                                    id="base-stock-add" 
                                                    v-model="part.temp_base_add" 
                                                    :default-value="0" 
                                                    :min="-part.stock_quantity"
                                                >
                                                    <Label for="base-stock-add" class="font-semibold text-gray-900">
                                                        {{ part.name }} ({{ part.base_var_name || 'Default' }})
                                                        <TriangleAlert 
                                                            v-if="(Number(part.temp_base_add) || 0) + Number(part.stock_quantity) <= 5" 
                                                            class="w-6 h-6"
                                                            :class="{
                                                                'text-amber-500': getProjectedStockLevelStatus(part) === 'warning',
                                                                'text-red-500': getProjectedStockLevelStatus(part) === 'danger'
                                                            }" 
                                                        />                           
                                                    </Label>
                                                    <span class="text-xs text-gray-500 block mb-2">Current Base / Default variation stock: <b>{{ part.stock_quantity }}</b></span>
                                                    <NumberFieldContent>
                                                        <NumberFieldDecrement />
                                                        <NumberFieldInput class="text-center bg-white" />
                                                        <NumberFieldIncrement />
                                                    </NumberFieldContent>
                                                </NumberField>
                                            </div>

                                            <div v-if="part.variations && part.variations.length > 0" class="grid gap-3">
                                                <Label class="font-semibold text-gray-900 border-b pb-2">Variations Stock</Label>
                                                
                                                <div v-for="variation in part.variations" :key="variation.variation_id" class="flex items-center justify-between gap-4 py-2 border-b border-gray-50 last:border-0">
                                                    <div class="flex flex-col text-left">
                                                        <span class="flex font-medium gap-1 text-gray-700">
                                                            {{ variation.name }}
                                                            <TriangleAlert 
                                                                v-if="(Number(variation.temp_add_quantity) || 0) + Number(variation.stock_quantity) <= 5" 
                                                                class="w-6 h-6"
                                                                :class="{
                                                                    'text-amber-500': getProjectedStockLevelStatus(variation) === 'warning',
                                                                    'text-red-500': getProjectedStockLevelStatus(variation) === 'danger'
                                                                }" 
                                                            />
                                                        </span>
                                                        <span class="text-xs text-gray-500">Current: <b>{{ variation.stock_quantity }}</b></span>
                                                    </div>
                                                    <div class="flex items-center gap-2 w-32">
                                                        <span class="text-gray-400 font-bold">+/-</span>
                                                        
                                                        <NumberField 
                                                            v-model="variation.temp_add_quantity" 
                                                            :default-value="0" 
                                                            :min="-variation.stock_quantity" 
                                                            class="w-full" 
                                                        >
                                                            <NumberFieldContent>
                                                                <NumberFieldDecrement />
                                                                <NumberFieldInput class="text-center bg-white" />
                                                                <NumberFieldIncrement />
                                                            </NumberFieldContent>
                                                        </NumberField>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <DialogFooter>
                                            <DialogClose as-child>
                                                <Button class="cursor-pointer" type="button" variant="outline">Cancel</Button>
                                            </DialogClose>
                                            <DialogClose as-child>
                                                <Button class="cursor-pointer bg-green-600 hover:bg-green-700 text-white" type="submit" >
                                                    Save changes
                                                </Button>
                                            </DialogClose>
                                        </DialogFooter>
                                    </form>
                                </DialogContent>
                            </Dialog>
                            <Button variant="ghost" size="icon" title="View Part" class="h-8 w-8 text-gray-500 hover:text-blue-600 hover:bg-blue-50" as-child>
                                <Link :href="viewStock(part.automotive_parts_id)">
                                    <Eye class="w-4 h-4" />
                                </Link>
                            </Button>
                            <Button variant="ghost" size="icon" title="Edit Part" class="h-8 w-8 text-gray-500 hover:text-yellow-600 hover:bg-yellow-50">
                                <Link :href="editStock(part.automotive_parts_id)">
                                    <Pencil class="w-4 h-4" />
                                </Link>
                            </Button>
                            <AlertDialog>
                                <AlertDialogTrigger as-child>
                                    <Button variant="ghost" size="icon" title="Delete Part" class="h-8 w-8 text-gray-500 hover:text-red-600 hover:bg-red-50 cursor-pointer" >
                                        <Trash class="w-4 h-4" />
                                    </Button>
                                </AlertDialogTrigger>
                                <AlertDialogContent>
                                    <AlertDialogHeader>
                                        <AlertDialogTitle class="flex items-center gap-2">
                                            <div class="p-2 bg-red-100/80 text-red-500 border border-red-200 rounded-xl">
                                                <Trash class="w-4 h-4" />
                                            </div>
                                            <div>
                                                Delete
                                            </div>
                                        </AlertDialogTitle>
                                        <AlertDialogDescription>
                                            Are you sure you want to delete this automotive part?
                                            <div class="mt-2 rounded-lg border bg-muted/30 p-3">
                                                <div class="flex items-start gap-2 text-sm font-medium">
                                                    <div class="items-center">
                                                        <span class="text-muted-foreground">#{{ part.automotive_parts_id }}</span>
                                                    </div>
                                                    <div>
                                                        <span>{{ part.name }}</span>
                                                        <p class="text-muted-foreground">{{ part.part_serial_number }}</p>
                                                    </div>
                                                    
                                                </div>
                                            </div> 
                                            <div class="mt-2 rounded-lg border border-red-200 bg-red-50 p-3 ">
                                                <div class="flex flex-col gap-2 text-sm font-medium text-red-900">
                                                    <div class="flex items-center gap-1 text-red-600">
                                                        <CircleAlert class="w-4 h-4"/>Note:
                                                    </div>
                                                    
                                                    <div>
                                                        Past Job Orders that used this part will remain completely unaffected to preserve historical billing accuracy.
                                                    </div>

                                                </div>
                                            </div> 
                                        </AlertDialogDescription>
                                    </AlertDialogHeader>
                                    <AlertDialogFooter>
                                        <AlertDialogCancel class="cursor-pointer" >Cancel</AlertDialogCancel>
                                        <AlertDialogAction class="cursor-pointer bg-red-600 hover:bg-red-700 text-white" @click="deletePart(part.automotive_parts_id)">Delete</AlertDialogAction>
                                    </AlertDialogFooter>
                                </AlertDialogContent>
                            </AlertDialog>
                        </div>
                      </TableCell>
                    </TableRow>

                    <TableRow v-if="parts.data.length === 0">
                        <TableCell colspan="6" class="h-64">
                            <div class="flex flex-col items-center justify-center text-center">
                                <div class="bg-gray-50 p-4 rounded-full mb-4 ring-8 ring-gray-50/50">
                                    <Search class="w-8 h-8 text-gray-400" />
                                </div>
                                <h3 class="font-semibold text-gray-900">No parts found</h3>
                                <p class="text-sm text-gray-500 mt-1 max-w-sm">
                                    Try adjusting your search or filter parameters to find what you're looking for.
                                </p>
                                <Button variant="outline" class="mt-4" @click="clearFilters">Clear All Filters</Button>
                            </div>
                        </TableCell>
                    </TableRow>

                  </TableBody>
                </Table>
                
                <div v-if="parts.total > 0" class="bg-gray-50/50 border-t border-gray-100 p-4 px-6 ">

                    <span class="text-sm text-gray-500">

                        Showing <span class="font-medium text-gray-900">{{ parts.from }}</span> to <span class="font-medium text-gray-900">{{ parts.to }}</span> of <span class="font-medium text-gray-900">{{ parts.total }}</span> results

                    </span>

                   

                    <Pagination

                        v-slot="{ page }"

                        :total="parts.total"

                        :items-per-page="parts.per_page"

                        :default-page="parts.current_page"

                        :sibling-count="1"

                        :boundary-count="1"

                        show-edges

                    >

                        <PaginationContent v-slot="{ items }">

                            <PaginationPrevious

                                @click="parts.prev_page_url ? handlePageChange(parts.current_page - 1) : null"

                                :class="!parts.prev_page_url ? 'opacity-50 cursor-not-allowed hover:bg-transparent' : 'cursor-pointer'"

                            />



                            <template v-for="(item, index) in items" :key="index">

                                <PaginationItem

                                    v-if="item.type === 'page'"

                                    :value="item.value"

                                    :is-active="item.value === page"

                                    @click="handlePageChange(item.value)"

                                    class="w-10 h-10 p-0"

                                >

                                    {{ item.value }}

                                </PaginationItem>

                            </template>



                            <PaginationNext

                                @click="parts.next_page_url ? handlePageChange(parts.current_page + 1) : null"

                                :class="!parts.next_page_url ? 'opacity-50 cursor-not-allowed hover:bg-transparent' : 'cursor-pointer'"

                            />

                        </PaginationContent>

                    </Pagination>

                </div>

            </div>
        </div>
    </div>

    <!-- Image Preview Modal -->
    <Dialog v-model:open="isPreviewOpen">
        <DialogContent :show-close-button="false"
            class="max-w-4xl p-0 overflow-hidden border-none bg-transparent shadow-none flex items-center justify-center sm:max-w-[90vw]">
            <div class="relative w-full h-full flex items-center justify-center p-4">
                <img :src="previewImageUrl"
                    class="max-h-[90vh] w-auto rounded-lg shadow-2xl object-contain bg-white/10 backdrop-blur-sm" />

                <!-- Highly Visible Close Button -->
                <button @click="isPreviewOpen = false"
                    class="absolute top-8 right-8 z-[60] flex h-10 w-10 items-center justify-center rounded-full bg-white shadow-2xl ring-4 ring-black/10 transition-all hover:scale-110 active:scale-95 cursor-pointer">
                    <Plus class="h-6 w-6 rotate-45 text-black" />
                </button>
            </div>
        </DialogContent>
    </Dialog>
</template>