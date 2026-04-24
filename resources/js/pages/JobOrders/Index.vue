<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { toast } from 'vue-sonner';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import debounce from 'lodash/debounce'; 
import { ClipboardSignature, Pencil, Trash, Search, FilterX, Plus, Eye, CarFront, User as UserIcon, Phone, Wrench, DollarSign, Calendar } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge'; 
import { Button } from '@/components/ui/button';
import { Toaster } from '@/components/ui/sonner';
import { Card, CardContent } from '@/components/ui/card';

import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

import {
    Pagination,
    PaginationContent,
    PaginationItem,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination'

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

import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from '@/components/ui/dialog';

import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

// Import your route helpers (adjust these if your route names differ)
import { createJobOrder, displayJobOrders, editJobOrder } from '@/routes';

const props = defineProps({
    jobOrders: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

// Use Inertia's usePage to access globally shared flash messages
const page = usePage();

const successMessage = computed(() => page.props.flash?.success);
const errorMessage = computed(() => page.props.flash?.error);

watch(successMessage, (newVal) => {
    if (newVal) {
        toast.success(newVal, {
            duration: 4000,
        });
        setTimeout(() => {
            if (page.props.flash) {
                page.props.flash.success = null;
            }
        }, 100);
    }
}, { immediate: true });

watch(errorMessage, (newVal) => {
    if (newVal) {
        toast.error(newVal, {
            duration: 4000,
        });
        setTimeout(() => {
            if (page.props.flash) {
                page.props.flash.error = null;
            }
        }, 100);
    }
}, { immediate: true });

// Setup Reactive Form State
const filterForm = ref({
    search: props.filters.search || '',
    status: props.filters.status || 'all',
});

// Watch for any changes in the filterForm, then ping the server
watch(filterForm, debounce((newFilters: any) => {
    router.get(displayJobOrders().url, newFilters, {
        preserveState: true,
        preserveScroll: true,
        replace: true, 
    });
}, 300), { deep: true });

const clearFilters = () => {
    filterForm.value = {
        search: '',
        status: 'all',
    };
};

const handlePageChange = (pageNumber: number) => {
    router.get(displayJobOrders().url, { ...filterForm.value, page: pageNumber }, {
        preserveState: true,
        preserveScroll: true,
    });
};

// const deleteJobOrder = (id: number) => {
//     // Replace with your actual delete route
//     router.delete(route('job-orders.destroy', id), {
//         preserveScroll: true,
//     });
// };

const statusColor = (status: string) => {
    switch(status) {
        case 'Completed': return 'bg-emerald-100 text-emerald-700 hover:bg-emerald-100/80';
        case 'Fixing in Progress': return 'bg-blue-100 text-blue-700 hover:bg-blue-100/80';
        case 'Waiting for Orders': return 'bg-purple-100 text-purple-700 hover:bg-purple-100/80';
        case 'Not Started Yet': return 'bg-amber-100 text-amber-700 hover:bg-amber-100/80';
        default: return 'bg-gray-100 text-gray-700 hover:bg-gray-100/80';
    }
};

const getVehicleImage = (order: any) => {
    if (order.vehicle_picture) {
        return `/storage/${order.vehicle_picture}`;
    }
    return undefined;
};

// View Modal State
const viewModalOpen = ref(false);
const selectedOrder = ref<any>(null);

const openViewModal = (order: any) => {
    selectedOrder.value = order;
    viewModalOpen.value = true;
};

const closeViewModal = () => {
    viewModalOpen.value = false;
    setTimeout(() => {
        selectedOrder.value = null;
    }, 300);
};

const handleModalOpenChange = (value: boolean) => {
    if (!value) {
        closeViewModal();
    }
};

const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-MY', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

// Get part image - prioritize variation image if variation exists, otherwise use base part image
const getPartImage = (part: any) => {
    // If variation exists and has an image, use it
    if (part.variation?.picture) {
        return `/storage/${part.variation.picture}`;
    }
    
    // Otherwise, use base part image if available
    if (part.automotive_part?.part_images && part.automotive_part.part_images.length > 0) {
        return `/storage/${part.automotive_part.part_images[0]}`;
    }
    
    return undefined;
};

defineOptions({ 
    layout: { breadcrumbs: 
        [ 
            { 
                title: 'Job Orders', 
                href: displayJobOrders().url
            } 
        ] 
    } 
});
</script>

<template>
    <Head title="Job Orders Dashboard" />

    <div class="my-6 px-12 max-w-7xl flex items-start justify-between gap-4">
      <div class="flex items-center gap-4">
          <div class="p-3 bg-blue-500/10 text-blue-600 rounded-xl shadow-sm border border-blue-500/20">
            <ClipboardSignature class="w-7 h-7" />
          </div>
          <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">Job Orders Dashboard</h1>
            <p class="text-sm text-gray-500 mt-1">Manage client vehicles, tasks, and service statuses.</p>
          </div>
      </div>
      
      <Button as-child class="shadow-sm">
        <Link :href="createJobOrder().url"><Plus class="w-4 h-4 mr-2"/> New Job Order</Link>
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
            <!-- Filters Section -->
            <div class="bg-white overflow-hidden shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl mb-6">
                <div class="bg-gray-50/50 border-b border-gray-100 p-4 flex flex-col gap-4">
                    <div class="flex gap-3">
                        <div class="relative flex-1">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                            <Input v-model="filterForm.search" placeholder="Search customer name, phone, or plate..." class="pl-9 bg-white" />
                        </div>
                        <Select v-model="filterForm.status">
                            <SelectTrigger class="bg-white w-[220px]"><SelectValue placeholder="Status" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Statuses</SelectItem>
                                <SelectItem value="Not Started Yet">Not Started Yet</SelectItem>
                                <SelectItem value="Fixing in Progress">Fixing in Progress</SelectItem>
                                <SelectItem value="Waiting for Orders">Waiting for Orders</SelectItem>
                                <SelectItem value="Completed">Completed</SelectItem>
                            </SelectContent>
                        </Select>
                        <Button variant="outline" @click="clearFilters" class="text-gray-500 gap-2">
                            <FilterX class="w-4 h-4" /> Clear
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Job Orders Grid -->
            <div v-if="jobOrders.data.length > 0" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <Card 
                    v-for="order in jobOrders.data" 
                    :key="order.job_orders_id"
                    class="overflow-hidden hover:shadow-lg transition-shadow duration-200 border-gray-200"
                >
                    <CardContent class="p-0">
                        <div class="flex flex-col md:flex-row">
                            <!-- Vehicle Image Section -->
                            <div class="md:w-48 h-48 md:h-auto bg-gradient-to-br from-blue-50 to-blue-100 flex items-center justify-center relative overflow-hidden">
                                <img 
                                    v-if="getVehicleImage(order)" 
                                    :src="getVehicleImage(order)" 
                                    :alt="`${order.vehicle_brand} ${order.vehicle_model}`"
                                    class="w-full h-full object-cover"
                                />
                                <div v-else class="flex flex-col items-center justify-center text-blue-400">
                                    <CarFront class="w-16 h-16 mb-2" />
                                    <span class="text-xs font-medium text-blue-600">No Image</span>
                                </div>
                                
                                <!-- Status Badge Overlay -->
                                <div class="absolute top-3 right-3">
                                    <Badge :class="['border-none shadow-md', statusColor(order.status)]">
                                        {{ order.status }}
                                    </Badge>
                                </div>
                            </div>

                            <!-- Content Section -->
                            <div class="flex-1 p-5">
                                <!-- Vehicle Info Header -->
                                <div class="mb-4">
                                    <div class="flex items-start justify-between mb-2">
                                        <div>
                                            <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                                                <span class="text-xs font-bold text-gray-700 bg-gray-100 px-2 py-1 rounded border">
                                                    {{ order.vehicle_plate }}
                                                </span>
                                            </h3>
                                            <p v-if="order.vehicle_brand && order.vehicle_model" class="text-sm font-semibold text-gray-700 mt-1">
                                                {{ order.vehicle_brand }} {{ order.vehicle_model }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Customer Info -->
                                <div class="space-y-2 mb-4 pb-4 border-b border-gray-100">
                                    <div class="flex items-center gap-2 text-sm">
                                        <UserIcon class="w-4 h-4 text-gray-400" />
                                        <span class="font-medium text-gray-900">{{ order.customer_name }}</span>
                                    </div>
                                    <div class="flex items-center gap-2 text-sm">
                                        <Phone class="w-4 h-4 text-gray-400" />
                                        <span class="text-gray-600">{{ order.customer_phone_num }}</span>
                                    </div>
                                </div>

                                <!-- Issue Description -->
                                <div class="mb-4">
                                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Reported Issue</p>
                                    <p class="text-sm text-gray-700 line-clamp-2" :title="order.reported_issue">
                                        {{ order.reported_issue }}
                                    </p>
                                </div>

                                <!-- Footer Info -->
                                <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                                    <div class="flex items-center gap-4">
                                        <div class="flex items-center gap-1.5">
                                            <Wrench class="w-4 h-4 text-gray-400" />
                                            <span class="text-xs text-gray-600">{{ order.handler?.full_name || 'N/A' }}</span>
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <DollarSign class="w-4 h-4 text-emerald-600" />
                                            <span class="text-sm font-bold text-emerald-700">RM {{ Number(order.total_cost).toFixed(2) }}</span>
                                        </div>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="flex items-center gap-1">
                                        <Button 
                                            variant="ghost" 
                                            size="icon" 
                                            title="View Order" 
                                            class="h-8 w-8 text-gray-500 hover:text-blue-600 hover:bg-blue-50 cursor-pointer"
                                            @click="openViewModal(order)"
                                        >
                                            <Eye class="w-4 h-4" />
                                        </Button>
                                        
                                        <Button variant="ghost" size="icon" title="Edit Order" class="h-8 w-8 text-gray-500 hover:text-yellow-600 hover:bg-yellow-50" as-child>
                                            <Link :href="editJobOrder(order.job_orders_id).url">
                                                <Pencil class="w-4 h-4" />
                                            </Link>
                                        </Button>

                                        <AlertDialog>
                                            <AlertDialogTrigger as-child>
                                                <Button variant="ghost" size="icon" title="Delete Order" class="h-8 w-8 text-gray-500 hover:text-red-600 hover:bg-red-50 cursor-pointer" >
                                                    <Trash class="w-4 h-4" />
                                                </Button>
                                            </AlertDialogTrigger>
                                            <AlertDialogContent>
                                                <AlertDialogHeader>
                                                    <AlertDialogTitle>Delete Job Order for {{ order.vehicle_plate }}?</AlertDialogTitle>
                                                </AlertDialogHeader>
                                                <AlertDialogFooter>
                                                    <AlertDialogCancel class="cursor-pointer">Cancel</AlertDialogCancel>
                                                    <AlertDialogAction class="cursor-pointer" @click="deleteJobOrder(order.job_orders_id)">Yes, Delete</AlertDialogAction>
                                                </AlertDialogFooter>
                                            </AlertDialogContent>
                                        </AlertDialog>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Empty State -->
            <div v-else class="bg-white overflow-hidden shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl">
                <div class="h-96 flex flex-col items-center justify-center text-center p-8">
                    <div class="bg-gray-50 p-6 rounded-full mb-4 ring-8 ring-gray-50/50">
                        <Search class="w-12 h-12 text-gray-400" />
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">No job orders found</h3>
                    <p class="text-sm text-gray-500 max-w-sm mb-6">
                        Try adjusting your search or filters to find what you're looking for, or create a new job order to get started.
                    </p>
                    <div class="flex gap-3">
                        <Button variant="outline" @click="clearFilters">Clear Filters</Button>
                        <Button as-child>
                            <Link :href="createJobOrder().url">
                                <Plus class="w-4 h-4 mr-2"/> New Job Order
                            </Link>
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="jobOrders.total > 0" class="mt-6 bg-white overflow-hidden shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl">
                <div class="bg-gray-50/50 border-t border-gray-100 p-4 px-6 flex items-center justify-between">
                    <span class="text-sm text-gray-500">
                        Showing <span class="font-medium text-gray-900">{{ jobOrders.from }}</span> to <span class="font-medium text-gray-900">{{ jobOrders.to }}</span> of <span class="font-medium text-gray-900">{{ jobOrders.total }}</span> results
                    </span>

                    <Pagination
                        v-slot="{ page }"
                        :total="jobOrders.total"
                        :items-per-page="jobOrders.per_page"
                        :default-page="jobOrders.current_page"
                        :sibling-count="1"
                        :boundary-count="1"
                        show-edges
                    >
                        <PaginationContent v-slot="{ items }">
                            <PaginationPrevious
                                @click="jobOrders.prev_page_url ? handlePageChange(jobOrders.current_page - 1) : null"
                                :class="!jobOrders.prev_page_url ? 'opacity-50 cursor-not-allowed hover:bg-transparent' : 'cursor-pointer'"
                            />

                            <template v-for="(item, index) in items" :key="index">
                                <PaginationItem
                                    v-if="item.type === 'page'"
                                    :value="item.value"
                                    :is-active="item.value === page"
                                    @click="handlePageChange(item.value)"
                                    class="w-10 h-10 p-0 cursor-pointer"
                                >
                                    {{ item.value }}
                                </PaginationItem>
                            </template>

                            <PaginationNext
                                @click="jobOrders.next_page_url ? handlePageChange(jobOrders.current_page + 1) : null"
                                :class="!jobOrders.next_page_url ? 'opacity-50 cursor-not-allowed hover:bg-transparent' : 'cursor-pointer'"
                            />
                        </PaginationContent>
                    </Pagination>
                </div>
            </div>

        </div>
    </div>

    <!-- View Job Order Modal -->
    <Dialog :open="viewModalOpen" @update:open="handleModalOpenChange">
        <DialogContent class="max-w-6xl w-full max-h-[85vh] overflow-hidden flex flex-col">
            <DialogHeader class="flex-shrink-0">
                <DialogTitle class="flex items-center gap-3 text-xl">
                    <div class="p-2 bg-blue-500/10 text-blue-600 rounded-lg">
                        <ClipboardSignature class="w-5 h-5" />
                    </div>
                    Job Order Details
                </DialogTitle>
                <DialogDescription>
                    Complete information about this job order
                </DialogDescription>
            </DialogHeader>

            <div v-if="selectedOrder" class="overflow-y-auto flex-1 pr-2 space-y-4">
                <!-- Status Badge -->
                <div class="flex items-center justify-between sticky top-0 bg-white z-10 pb-2">
                    <Badge :class="['text-sm px-3 py-1 border-none', statusColor(selectedOrder.status)]">
                        {{ selectedOrder.status }}
                    </Badge>
                    <span class="text-xs text-gray-500">
                        Created: {{ formatDate(selectedOrder.created_at) }}
                    </span>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <!-- Vehicle Information Section -->
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-4 border border-blue-200">
                        <h3 class="text-base font-bold text-gray-900 mb-3 flex items-center gap-2">
                            <CarFront class="w-4 h-4 text-blue-600" />
                            Vehicle Information
                        </h3>
                        
                        <!-- Vehicle Image -->
                        <div v-if="getVehicleImage(selectedOrder)" class="mb-3">
                            <img 
                                :src="getVehicleImage(selectedOrder)" 
                                :alt="`${selectedOrder.vehicle_brand} ${selectedOrder.vehicle_model}`"
                                class="w-full h-40 object-cover rounded-lg border-2 border-white shadow-sm"
                            />
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="col-span-2">
                                <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1">Plate</p>
                                <p class="text-sm font-bold text-gray-900 bg-white px-2 py-1 rounded border inline-block">
                                    {{ selectedOrder.vehicle_plate }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1">Brand</p>
                                <p class="text-sm font-semibold text-gray-900">{{ selectedOrder.vehicle_brand || 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1">Model</p>
                                <p class="text-sm font-semibold text-gray-900">{{ selectedOrder.vehicle_model || 'N/A' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Information Section -->
                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                        <h3 class="text-base font-bold text-gray-900 mb-3 flex items-center gap-2">
                            <UserIcon class="w-4 h-4 text-gray-600" />
                            Customer Information
                        </h3>
                        
                        <div class="space-y-2">
                            <div class="flex items-center gap-2 bg-white p-3 rounded-lg border">
                                <div class="p-1.5 bg-blue-100 rounded">
                                    <UserIcon class="w-4 h-4 text-blue-600" />
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Customer Name</p>
                                    <p class="text-sm font-semibold text-gray-900">{{ selectedOrder.customer_name }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 bg-white p-3 rounded-lg border">
                                <div class="p-1.5 bg-emerald-100 rounded">
                                    <Phone class="w-4 h-4 text-emerald-600" />
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Phone Number</p>
                                    <p class="text-sm font-semibold text-gray-900">{{ selectedOrder.customer_phone_num }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 bg-white p-3 rounded-lg border">
                                <div class="p-1.5 bg-purple-100 rounded">
                                    <Wrench class="w-4 h-4 text-purple-600" />
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Handled By</p>
                                    <p class="text-sm font-semibold text-gray-900">{{ selectedOrder.handler?.full_name || 'N/A' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reported Issue Section -->
                <div class="bg-amber-50 rounded-lg p-4 border border-amber-200">
                    <h3 class="text-base font-bold text-gray-900 mb-2 flex items-center gap-2">
                        <Wrench class="w-4 h-4 text-amber-600" />
                        Reported Issue
                    </h3>
                    <p class="text-sm text-gray-700 leading-relaxed">{{ selectedOrder.reported_issue }}</p>
                </div>

                <!-- Parts Used Section -->
                <div v-if="selectedOrder.job_order_parts && selectedOrder.job_order_parts.length > 0" class="bg-white rounded-lg p-4 border border-gray-200">
                    <h3 class="text-base font-bold text-gray-900 mb-3 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                            <line x1="3" y1="6" x2="21" y2="6"/>
                            <path d="M16 10a4 4 0 0 1-8 0"/>
                        </svg>
                        Parts Used
                    </h3>
                    
                    <div class="space-y-2">
                        <div 
                            v-for="part in selectedOrder.job_order_parts" 
                            :key="part.job_order_parts_id"
                            class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg border hover:bg-gray-100 transition-colors"
                        >
                            <!-- Part Image -->
                            <div class="w-16 h-16 flex-shrink-0 bg-white rounded-lg border overflow-hidden">
                                <img 
                                    v-if="getPartImage(part)"
                                    :src="getPartImage(part)"
                                    :alt="part.automotive_part?.name"
                                    class="w-full h-full object-cover"
                                />
                                <div v-else class="w-full h-full flex items-center justify-center bg-gray-100">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                                        <line x1="3" y1="6" x2="21" y2="6"/>
                                        <path d="M16 10a4 4 0 0 1-8 0"/>
                                    </svg>
                                </div>
                            </div>

                            <!-- Part Details -->
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-sm text-gray-900 truncate">
                                    {{ part.automotive_part?.name || 'N/A' }}
                                </p>
                                <p class="text-xs text-gray-600">
                                    {{ part.variation?.name || 'Base / Default' }}
                                </p>
                                <div class="flex items-center gap-3 mt-1">
                                    <span class="text-xs text-gray-500">Qty: <span class="font-semibold text-gray-900">{{ part.quantity_used }}</span></span>
                                    <span class="text-xs text-gray-500">×</span>
                                    <span class="text-xs text-gray-500">RM {{ Number(part.unit_price).toFixed(2) }}</span>
                                </div>
                            </div>

                            <!-- Subtotal -->
                            <div class="text-right flex-shrink-0">
                                <p class="text-xs text-gray-500 mb-0.5">Subtotal</p>
                                <p class="text-sm font-bold text-emerald-700">
                                    RM {{ Number(part.subtotal).toFixed(2) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Cost Section -->
                <div class="bg-gradient-to-br from-emerald-50 to-emerald-100 rounded-lg p-4 border border-emerald-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="p-2 bg-white rounded-lg shadow-sm">
                                <DollarSign class="w-5 h-5 text-emerald-600" />
                            </div>
                            <div>
                                <p class="text-xs text-gray-600 uppercase tracking-wide">Total Cost</p>
                                <p class="text-2xl font-bold text-emerald-700">RM {{ Number(selectedOrder.total_cost).toFixed(2) }}</p>
                            </div>
                        </div>
                        <div class="text-right text-xs text-gray-500">
                            <p>Updated: {{ formatDate(selectedOrder.updated_at) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t flex-shrink-0">
                <Button class="cursor-pointer" variant="outline" @click="closeViewModal">
                    Close
                </Button>
                <Button variant="default" as-child>
                    <Link :href="editJobOrder(selectedOrder.job_orders_id).url">
                        <Pencil class="w-4 h-4 mr-2" />
                        Edit Job Order
                    </Link>
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>