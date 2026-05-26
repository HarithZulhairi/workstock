<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { dashboard, createStock, createJobOrder } from '@/routes';
import { 
    Warehouse, 
    Briefcase, 
    AlertTriangle, 
    ArrowUpRight, 
    Download, 
    Wrench,
    Clock
} from 'lucide-vue-next';

// Import Shadcn Card Components
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

const props = defineProps({
    stats: Object,
    recentOrders: Array,
    charts: Object
});

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
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-col gap-8 p-6 lg:p-10 max-w-7xl mx-auto w-full">
        
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white">Workspace Overview</h1>
                <p class="text-sm text-gray-500 mt-1">Monitor your daily operations, inventory health, and job progress.</p>
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

            <Card class="relative overflow-hidden group border-red-100 shadow-sm">
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
                    <CardTitle class="text-lg font-bold text-gray-900">Job Orders Pipeline</CardTitle>
                    <CardDescription>Current distribution of all tracked jobs.</CardDescription>
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
                    <Link :href="dashboard()" class="p-2 text-gray-400 hover:text-gray-900 hover:bg-gray-50 rounded-lg transition-colors">
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
</template>