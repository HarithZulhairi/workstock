<script setup lang="ts">
import { ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import debounce from 'lodash/debounce'; 
import { Warehouse, Pencil, Trash, PackageOpen, Eye, EyeOff, Search, FilterX, Plus } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge'; 
import { Button } from '@/components/ui/button';

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
} from '@/components/ui/select'; // <-- Import Select components

import { Toaster } from '@/components/ui/sonner';

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

// Setup Reactive Form State initialized with URL parameters
const filterForm = ref({
    search: props.filters.search || '',
    category_id: props.filters.category_id || 'all',
    min_price: props.filters.min_price || '',
    max_price: props.filters.max_price || '',
    min_stock: props.filters.min_stock || '',
    max_stock: props.filters.max_stock || '',
    visibility: props.filters.visibility || 'all',
});

// Watch for any changes in the filterForm, then ping the server
watch(filterForm, debounce((newFilters) => {
    router.get(displayStock(), newFilters, {
        preserveState: true,
        preserveScroll: true,
        replace: true, // Replaces the current history state so the back button doesn't break
    });
}, 300), { deep: true }); // 300ms delay, deep watch for nested object changes

const clearFilters = () => {
    filterForm.value = {
        search: '',
        category_id: 'all',
        min_price: '',
        max_price: '',
        min_stock: '',
        max_stock: '',
        visibility: 'all',
    };
};

const handlePageChange = (pageNumber: number) => {
    router.get(displayStock(), { ...filterForm.value, page: pageNumber }, {
        preserveState: true,
        preserveScroll: true,
    });
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
        onSuccess: () => {
            toast.success(' Stock added successfully!');
        },
        onError: () => {
            toast.error('Failed to add stock. Please check your inputs.');
        },
        preserveScroll: true,
    });
};

const deletePart = (id: number) => {
    router.post(destroyStock(id), { },{
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Part deleted successfully!');
        },
        onError: () => {
            toast.error('Failed to delete part.');
        }
    });
};

defineOptions({ 
    layout: { breadcrumbs: 
        [ 
            { 
                title: 'Display Stock', 
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
          <div class="p-3 bg-primary/10 text-primary rounded-xl shadow-sm border border-primary/20">
            <Warehouse class="w-7 h-7" />
          </div>
          <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">Inventory Dashboard</h1>
            <p class="text-sm text-gray-500 mt-1">Manage, update, and monitor your automotive parts.</p>
          </div>
      </div>
      
      <Button as-child class="shadow-sm">
        <Link :href="createStock()">Add New Part</Link>
      </Button>
    </div>

    <Toaster 
        class="px-12"
        position="top-right" 
        :toastOptions="{
            classes: {
                toast: 'group flex w-full items-center p-4 rounded-xl shadow-lg border',
                success: 'bg-emerald-50 border-emerald-200 text-emerald-900',
                error: 'bg-red-50 border-red-200 text-red-900',
                info: 'bg-blue-50 border-blue-200 text-blue-900',
            }
        }" 
    />

    <div class="pb-12 pt-4 px-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl">
                
                <div class="bg-gray-50/50 border-b border-gray-100 p-4 flex flex-col gap-4">
                    <div class="flex gap-3">
                        <div class="relative flex-1">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                            <Input v-model="filterForm.search" placeholder="Search by part name..." class="pl-9 bg-white" />
                        </div>
                        <Button variant="outline" @click="clearFilters" class="text-gray-500 gap-2">
                            <FilterX class="w-4 h-4" /> Clear Filters
                        </Button>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-3">
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

                        <Input v-model="filterForm.min_stock" type="number" placeholder="Min Stock" class="bg-white" />
                        <Input v-model="filterForm.max_stock" type="number" placeholder="Max Stock" class="bg-white" />
                    </div>
                </div>
                <Table>
                  <TableHeader class="bg-gray-50/80 border-b border-gray-100">
                    <TableRow class="hover:bg-transparent">
                      <TableHead class="font-semibold text-gray-700 w-[30%] pl-6">Part Details</TableHead>
                      <TableHead class="font-semibold text-gray-700">Category</TableHead>
                      <TableHead class="font-semibold text-gray-700">Price (RM)</TableHead>
                      <TableHead class="font-semibold text-gray-700">Stock Status</TableHead>
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
                            <div class="h-12 w-12 flex-shrink-0 rounded-lg overflow-hidden bg-gray-50 border border-gray-200 shadow-sm flex items-center justify-center relative group-hover:border-gray-300 transition-colors">
                              <img 
                                v-if="part.part_images && part.part_images.length > 0" 
                                :src="`/storage/${part.part_images[0]}`" 
                                alt="Part Image Thumbnail" 
                                class="object-cover w-full h-full" 
                              />
                              <PackageOpen v-else class="w-5 h-5 text-gray-300" />
                            </div>

                            <div class="flex flex-col">
                                <span class="font-semibold text-gray-900 leading-tight flex items-center gap-2">
                                    {{ part.name }}
                                    <span v-if="part.brand" class="text-[10px] font-medium bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded uppercase tracking-wider border border-gray-200">
                                        {{ part.brand }}
                                    </span>
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
                        <div class="flex items-center gap-2">
                            <div 
                                class="w-2 h-2 rounded-full"
                                :class="part.stock_quantity > 5 ? 'bg-emerald-500' : (part.stock_quantity > 0 ? 'bg-amber-500' : 'bg-red-500')"
                            ></div>
                            <span 
                                class="font-medium"
                                :class="part.stock_quantity > 5 ? 'text-emerald-700' : (part.stock_quantity > 0 ? 'text-amber-700' : 'text-red-700')"
                            >
                                {{ part.stock_quantity }} in stock
                            </span>
                        </div>
                      </TableCell>

                      <TableCell>
                          <Badge 
                            :variant="part.is_visible_to_public ? 'default' : 'secondary'"
                            class="flex w-fit items-center gap-1.5"
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
                                    <Button variant="ghost" size="icon" title="Add Availability" class="h-8 w-8 text-gray-500 hover:text-green-600 hover:bg-green-50 cursor-pointer">
                                        <Plus class="w-4 h-4" />
                                    </Button>
                                </DialogTrigger>
                                
                                <DialogContent class="sm:max-w-[425px] text-left">
                                    <form @submit.prevent="submitRowStock(part.automotive_parts_id, part.temp_base_add, part.variations)">
                                        <DialogHeader>
                                            <DialogTitle>Add Availability</DialogTitle>
                                            <DialogDescription>
                                                Add the amount of stock received for this part.
                                            </DialogDescription>
                                        </DialogHeader>
                                        
                                        <div class="grid gap-6 py-4">
                                            
                                            <div class="grid gap-2 bg-gray-50 p-4 rounded-lg border border-gray-100">
                                                <NumberField id="base-stock-add" v-model="part.temp_base_add" :default-value="0" :min="0">
                                                    <Label for="base-stock-add" class="font-semibold text-gray-900">{{ part.name }}</Label>
                                                    <span class="text-xs text-gray-500 block mb-2">Current Base Stock: {{ part.stock_quantity }}</span>
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
                                                        <span class="font-medium text-gray-700">{{ variation.name }}</span>
                                                        <span class="text-xs text-gray-500">Current: {{ variation.stock_quantity }}</span>
                                                    </div>
                                                    <div class="flex items-center gap-2 w-32">
                                                        <span class="text-gray-400 font-bold">+</span>
                                                        
                                                        <NumberField v-model="variation.temp_add_quantity" :default-value="0" :min="0" class="w-full">
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
                                                <Button class="cursor-pointer" type="submit" >
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
                                        <AlertDialogTitle>Are you sure you want to delete {{ part.name }}?</AlertDialogTitle>
                                    </AlertDialogHeader>
                                    <AlertDialogFooter>
                                        <AlertDialogCancel class="cursor-pointer" >Cancel</AlertDialogCancel>
                                        <AlertDialogAction class="cursor-pointer" @click="deletePart(part.automotive_parts_id)">Yes</AlertDialogAction>
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
</template>