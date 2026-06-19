<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { dashboard, createStock, createJobOrder, displayJobOrders } from '@/routes';
import { 
    Warehouse, 
    Briefcase, 
    AlertTriangle, 
    ArrowUpRight, 
    Download, 
    Wrench,
    Clock,
    Plus
} from 'lucide-vue-next';

// Import Shadcn Card Components
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

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


const props = defineProps({
    stats: Object,
    recentOrders: Array,
    charts: Object
});

console.log('Dashboard Stats:', props.stats?.category?.name); // Debug log to check low stock items data

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Overview',
                href: dashboard(),
            },
        ],
    },
});


const isLowStockPreviewOpen = ref(false);

const isPreviewOpen = ref(false);
const previewImageUrl = ref('');

// Helper for status colors
const getStatusColor = (status: string) => {
    switch(status) {
        case 'Arrived': return 'bg-emerald-100 text-emerald-700 border-emerald-200';
        default: return 'bg-amber-100 text-amber-700 border-amber-200';
    }
};

const getStatusBarColor = (label: string) => {
    switch(label) {
        case 'Arrived': return 'bg-emerald-500';
        default: return 'bg-amber-500';
    }
};

const openLowStockPreview = () => {
    isLowStockPreviewOpen.value = true;
};

const openPreview = (url: string) => {
    previewImageUrl.value = url;
    isPreviewOpen.value = true;
};

</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-col gap-10 my-6 px-12 max-w-7xl  w-full">
        
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            
            <div class="flex items-center gap-4">
                <img src="/images/dashboard-ws.png" alt="WorkStock Logo" class="h-12 w-12" />
                <div>
                    <h1 class="text-2xl font-extrabold tracking-tight text-gray-900 dark:text-white">Workspace Overview</h1>
                    <p class="text-sm text-gray-500 mt-1">Monitor company's daily operations, inventory health, and job progress.</p>
                </div>
            </div>
            <a 
                href="/dashboard/report/download" 
                target="_blank"
                class="inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold text-white bg-blue-500 rounded-xl hover:bg-blue-600 shadow-sm hover:shadow transition-all "
            >
                <Download class="w-4 h-4" />
                Export PDF Report
            </a>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <Card class="relative overflow-hidden group border-gray-100 shadow-sm">
                <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                    <Warehouse class="w-24 h-24 text-blue-600 -mr-6 -mt-6 transform rotate-12" />
                </div>
                <CardContent class="p-6">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="p-3 bg-blue-50 text-blue-600 rounded-xl">
                            <Warehouse class="w-6 h-6" />
                        </div>
                        <span class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Total Inventory</span>
                    </div>
                    <div class="text-4xl font-black text-gray-900">{{ stats?.totalParts || 0 }}</div>
                    <div class="mt-2 text-sm text-gray-500 flex items-center gap-1">
                        <span class="text-emerald-600 font-medium flex items-center">Live Database</span>
                    </div>
                </CardContent>
            </Card>

            <Card class="relative overflow-hidden group border-gray-100 shadow-sm">
                <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                    <Briefcase class="w-24 h-24 text-indigo-600 -mr-6 -mt-6 transform rotate-12" />
                </div>
                <CardContent class="p-6">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="p-3 bg-indigo-50 text-indigo-600 rounded-xl">
                            <Briefcase class="w-6 h-6" />
                        </div>
                        <span class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Total Jobs</span>
                    </div>
                    <div class="text-4xl font-black text-gray-900">{{ stats?.totalJobs || 0 }}</div>
                    <div class="mt-2 text-sm text-gray-500 flex items-center gap-1">
                        <span class="text-blue-600 font-medium">Recorded in system</span>
                    </div>
                </CardContent>
            </Card>

            <Card class="relative overflow-hidden group border-red-100 hover:border-red-300 transition-colors shadow-sm cursor-pointer" @click="openLowStockPreview()">
                <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                    <AlertTriangle class="w-24 h-24 text-red-600 -mr-6 -mt-6 transform rotate-12" />
                </div>
                <CardContent class="p-6">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="p-3 bg-red-50 text-red-600 rounded-xl">
                            <AlertTriangle class="w-6 h-6" />
                        </div>
                        <span class="text-sm font-semibold text-red-500 uppercase tracking-wider">Low Stock Alerts</span>
                    </div>
                    <div class="text-4xl font-black text-gray-900">{{ stats?.lowStock || 0 }}</div>
                    <div class="mt-2 text-sm text-gray-500 flex items-center gap-1">
                        <span class="text-red-600 font-medium">Items under 5 units</span>
                    </div>
                </CardContent>
            </Card>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <Card class="flex flex-col py-5 shadow-sm border-gray-100">
                <CardHeader>
                    <CardTitle class="text-lg font-bold text-gray-900">Job Orders Status Pipeline</CardTitle>
                    <CardDescription>Current status distribution of all tracked jobs.</CardDescription>
                </CardHeader>
                <CardContent class="flex-1 flex flex-col mt-2">
                    
                    <div class="flex-1 flex justify-around gap-4 mt-8 h-48">
    
                        <div 
                            v-for="item in charts?.jobOrders" 
                            :key="item.label"
                            class="flex flex-col items-center w-full h-full group" 
                        >
                            <div class="relative w-full flex-1 flex items-end justify-center group-hover:-translate-y-1 transition-transform">
                                
                                <div 
                                    :class="['w-12 sm:w-16 rounded-t-lg transition-all duration-500 relative', getStatusBarColor(item.label)]"
                                    :style="{ height: `${(item.value / (charts?.maxJobOrders || 1)) * 100}%`, minHeight: '4px' }"
                                >
                                    <span class="absolute -top-6 left-1/2 -translate-x-1/2 text-xs font-bold text-gray-700">
                                        {{ item.value }}
                                    </span>
                                </div>

                            </div>
                            
                            <span class="text-xs font-medium text-gray-500 mt-3 truncate flex-none">{{ item.label }}</span>
                        </div>

                    </div>
                </CardContent>
            </Card>

            <Card class="flex flex-col py-5 shadow-sm border-gray-100">
                <CardHeader>
                    <CardTitle class="text-lg font-bold text-gray-900">Top Inventory Categories</CardTitle>
                    <CardDescription>Highest stock count grouped by category.</CardDescription>
                </CardHeader>
                <CardContent class="flex-1 flex flex-col mt-2 pb-1">
                    <div class="flex-1 flex flex-col justify-center gap-4">
                        <div v-for="item in charts?.inventory" :key="item.label" class="w-full">
                            <div class="flex justify-between text-sm mb-1.5">
                                <span class="font-medium text-gray-700">{{ item.label }}</span>
                                <span class="font-bold text-gray-900">{{ item.value }}</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-2.5 overflow-hidden">
                                <div 
                                    class="bg-gray-900 h-2.5 rounded-full transition-all duration-500" 
                                    :style="{ width: `${(item.value / (charts?.maxInventory || 1)) * 100}%` }"
                                ></div>
                            </div>
                        </div>
                        
                        <div v-if="!charts?.inventory || charts.inventory.length === 0" class="text-center text-gray-400 py-6 text-sm">
                            No category data available yet.
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <Card class="shadow-sm py-5 border-gray-100 grid grid-cols-1 md:grid-cols-3">
            <CardHeader class="md:col-span-1 pb-4 md:pb-6">
                <CardTitle class="text-lg font-bold">Quick Actions</CardTitle>
                <CardDescription>Manage your workspace efficiently.</CardDescription>
            </CardHeader>
            
            <CardContent class="md:col-span-2 flex sm:flex-row gap-4 items-center ">
                <Link :href="createJobOrder()" class="flex-1 flex items-center gap-4 p-4 rounded-xl hover:bg-gray-50 border border-gray-200 transition-colors group">
                    <div class="p-2 bg-sky-500 rounded-full group-hover:scale-110 text-white transition-transform">
                        <Briefcase class="w-5 h-5" />
                    </div>
                    <div>
                        <div class="font-semibold">Create Job Order</div>
                        <div class="text-sm text-gray-400">Register new client work</div>
                    </div>
                </Link>

                <Link :href="createStock()" class="flex-1 flex items-center gap-4 p-4 rounded-xl hover:bg-gray-50 border border-gray-200 transition-colors group">
                    <div class="p-2 bg-orange-600 rounded-full group-hover:scale-110 text-white transition-transform">
                        <Wrench class="w-5 h-5" />
                    </div>
                    <div>
                        <div class="font-semibold">Add Inventory Part</div>
                        <div class="text-sm text-gray-400">Register new stock items</div>
                    </div>
                </Link>
            </CardContent>
        </Card>

        <div>
            <Card class="shadow-sm border-gray-100 overflow-hidden flex flex-col">
                <CardHeader class="flex flex-row items-center justify-between border-b border-gray-50 bg-white p-6">
                    <div class="space-y-1">
                        <CardTitle class="text-lg font-bold text-gray-900">Recent Job Orders</CardTitle>
                        <CardDescription>Latest client vehicle tasks generated.</CardDescription>
                    </div>
                    <Link :href="displayJobOrders()" class="p-2 text-gray-400 hover:text-gray-900 hover:bg-gray-50 rounded-lg transition-colors">
                        <ArrowUpRight class="w-5 h-5" />
                    </Link>
                </CardHeader>
                
                <CardContent class="p-0 overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-gray-500 uppercase bg-gray-50/50">
                            <tr>
                                <th scope="col" class="px-6 py-4 font-semibold">Client</th>
                                <th scope="col" class="px-6 py-4 font-semibold">Vehicle Plate</th>
                                <th scope="col" class="px-6 py-4 font-semibold">Status</th>
                                <th scope="col" class="px-6 py-4 font-semibold text-right">Cost (RM)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="order in recentOrders" :key="order.job_orders_id" class="hover:bg-gray-50/50 transition-colors group">
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-gray-900">{{ order.customer_name }}</div>
                                    <div class="text-xs text-gray-500 mt-0.5">{{ new Date(order.created_at).toLocaleDateString() }}</div>
                                </td>
                                <td class="px-6 py-4 font-medium text-gray-600">
                                    {{ order.vehicle_plate }}
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border', getStatusColor(order.status)]">
                                        {{ order.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right font-bold text-gray-900">
                                    {{ Number(order.total_cost).toFixed(2) }}
                                </td>
                            </tr>
                            <tr v-if="!recentOrders || recentOrders.length === 0">
                                <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                                    <Clock class="w-8 h-8 mx-auto text-gray-300 mb-3" />
                                    No recent job orders found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </CardContent>
            </Card>
        </div>
    </div>

    <!-- Modal for Low Stock Alert Details -->
    <Dialog v-model:open="isLowStockPreviewOpen" :closeable="true">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>Low Stock Alert Details</DialogTitle>
                <DialogDescription>Review inventory items that are running low on stock.</DialogDescription>
            </DialogHeader>
            <div class="mt-4">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-gray-500 uppercase bg-gray-50">
                        <tr>
                            <th scope="col" class="px-3 py-3 font-semibold">Part Name</th>
                            <th scope="col" class="px-3 py-3 font-semibold text-right">Current Stock</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="item in stats?.lowStockItems || []" :key="item.id" class="hover:bg-gray-50/50 transition-colors">
                            <td class="flex px-3 py-4 font-medium text-gray-900">
                                <img 
                                    v-if="item.image" 
                                    :src="item.image"
                                    @click="openPreview(`${item.image}`)" 
                                    alt="Part Image Thumbnail" 
                                    class="object-cover w-16 h-16 border rounded transition-transform hover:scale-110 cursor-pointer flex-shrink-0" 
                                />
                                <div v-else class="w-16 h-16 bg-gray-100 border rounded flex items-center justify-center text-gray-400 flex-shrink-0">
                                    <Wrench class="w-6 h-6" />
                                </div>

                                <div class="ml-4 flex flex-col justify-center">
                                    <p class="text-sm font-bold text-gray-900 leading-tight">{{ item.name }} ({{ item.var_name }})</p>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="text-xs text-gray-500">{{ item.category }}</span>
                                        <span class="text-[10px] bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded border border-gray-200">
                                            {{ item.type }}
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right font-bold text-red-600 text-lg">
                                {{ item.stock_quantity }}
                            </td>
                        </tr>
                        <tr v-if="!stats?.lowStockItems || stats.lowStockItems.length === 0">
                            <td colspan="2" class="px-6 py-12 text-center text-gray-500">
                                No low stock items at the moment.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </DialogContent>
    </Dialog>

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