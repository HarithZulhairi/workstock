<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';

import { ClipboardSignature, Plus, Trash, Wrench, User, Car, CheckCircle } from 'lucide-vue-next';
import { computed, watch, ref } from 'vue';
import InputError from '@/components/InputError.vue';

import { Button } from '@/components/ui/button';
import {
    Field,
    FieldDescription,
    FieldGroup,
    FieldLabel,
    FieldLegend,
    FieldSeparator,
    FieldSet,
} from '@/components/ui/field';

import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogFooter,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';

import { displayJobOrders, storeJobOrder } from '@/routes';

// Define expected props from the controller
const props = defineProps<{
    parts: Array<{ 
        automotive_parts_id: number; 
        name: string; 
        price: number; 
        stock_quantity: number; 
        part_serial_number: string;
        variations?: Array<{
            variation_id: number;
            name: string;
            price: number;
            stock_quantity: number;
        }>;
    }>;
}>();

// Form state
const form = useForm({
    customer_name: '',
    customer_phone_num: '',
    vehicle_plate: '',
    vehicle_brand: '',
    vehicle_model: '',
    vehicle_picture: null as File | null,
    reported_issue: '',
    status: 'Not Started Yet',
    total_cost: 0.00,
    parts_used: [] as Array<{
        automotive_parts_id: string;
        variation_id: string | null;
        quantity: number;
        unit_price: number;
        subtotal: number;
    }>,
});

// Logic for dynamic parts rows
const addPartRow = () => {
    form.parts_used.push({
        automotive_parts_id: '',
        variation_id: null,
        quantity: 1,
        unit_price: 0,
        subtotal: 0
    });
};

const removePartRow = (index: number) => {
    form.parts_used.splice(index, 1);
    calculateTotal();
};

// Get available variations for a selected part
const getVariationsForPart = (partId: string) => {
    if (!partId) return [];
    const part = props.parts.find(p => p.automotive_parts_id.toString() === partId);
    return part?.variations || [];
};

// Get maximum available stock for a part row
const getMaxStock = (index: number) => {
    const row = form.parts_used[index];
    if (!row.automotive_parts_id) return 0;
    
    const part = props.parts.find(p => p.automotive_parts_id.toString() === row.automotive_parts_id);
    if (!part) return 0;
    
    // If variation is selected, return variation stock
    if (row.variation_id) {
        const variation = part.variations?.find(v => v.variation_id.toString() === row.variation_id);
        return variation?.stock_quantity || 0;
    }
    
    // Otherwise return base part stock
    return part.stock_quantity || 0;
};

// Validate and enforce stock limits
const validateQuantity = (index: number) => {
    const row = form.parts_used[index];
    const maxStock = getMaxStock(index);
    
    if (row.quantity > maxStock) {
        row.quantity = maxStock;
    }
    
    if (row.quantity < 1) {
        row.quantity = 1;
    }
    
    updateRowSubtotal(index);
};

// Auto-fill price when a part is selected
const onPartSelect = (index: number) => {
    const selectedPartId = form.parts_used[index].automotive_parts_id;
    if (!selectedPartId) return;
    
    const part = props.parts.find(p => p.automotive_parts_id.toString() === selectedPartId);
    
    if (part) {
        // Reset variation when part changes
        form.parts_used[index].variation_id = null;
        form.parts_used[index].unit_price = Number(part.price);
        form.parts_used[index].quantity = 1;
        updateRowSubtotal(index);
    }
};

// Auto-fill price when a variation is selected
const onVariationSelect = (index: number) => {
    const row = form.parts_used[index];
    if (!row.automotive_parts_id) return;
    
    const part = props.parts.find(p => p.automotive_parts_id.toString() === row.automotive_parts_id);
    
    if (row.variation_id && part) {
        const variation = part.variations?.find(v => v.variation_id.toString() === row.variation_id);
        if (variation) {
            row.unit_price = Number(variation.price);
        }
    } else if (part) {
        // If no variation selected, use base price
        row.unit_price = Number(part.price);
    }
    
    updateRowSubtotal(index);
};

// Calculate row subtotal
const updateRowSubtotal = (index: number) => {
    const row = form.parts_used[index];
    const quantity = Number(row.quantity) || 0;
    const unitPrice = Number(row.unit_price) || 0;
    row.subtotal = Number((quantity * unitPrice).toFixed(2));
    calculateTotal();
};

// Get stock display text
const getStockDisplay = (index: number) => {
    const maxStock = getMaxStock(index);
    const row = form.parts_used[index];
    
    if (!row.automotive_parts_id) return '';
    
    if (row.variation_id) {
        return `(Max: ${maxStock} available)`;
    }
    
    return `(Max: ${maxStock} available)`;
};

// Calculate grand total
const calculateTotal = () => {
    const partsTotal = form.parts_used.reduce((sum, row) => {
        return sum + (Number(row.subtotal) || 0);
    }, 0);
    form.total_cost = Number(partsTotal.toFixed(2));
};

// Check if form has any stock validation errors
const hasStockErrors = computed(() => {
    return form.parts_used.some((row, index) => {
        if (!row.automotive_parts_id) return false;
        const maxStock = getMaxStock(index);
        return row.quantity > maxStock || maxStock === 0;
    });
});

// Handle vehicle picture upload
const vehiclePicturePreview = ref<string | null>(null);

const handleVehiclePictureUpload = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    
    if (file) {
        // Check file size (max 5MB)
        if (file.size > 5 * 1024 * 1024) {
            alert('Vehicle picture is too large. Maximum size is 5MB.');
            target.value = '';
            return;
        }
        
        form.vehicle_picture = file;
        
        // Create preview
        const reader = new FileReader();
        reader.onload = (e) => {
            vehiclePicturePreview.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    }
};

const removeVehiclePicture = () => {
    form.vehicle_picture = null;
    vehiclePicturePreview.value = null;
};

const submit = () => {
    form.post(storeJobOrder().url); 
};
</script>

<template>
    <Head title="Create Job Order" />

    <div class="my-6 px-12 flex items-start gap-4">
        <div class="p-3 bg-blue-500/10 text-blue-600 rounded-xl">
            <ClipboardSignature class="w-8 h-8" />
        </div>
        <div>
            <h1 class="text-2xl font-bold tracking-tight">Create Job Order</h1>
            <p class="text-gray-500 mt-1">Register a new customer vehicle and assign maintenance tasks.</p>
        </div>
    </div>

    <div class="mb-10 px-4 w-full">
        <form class="max-w-6xl mx-auto p-8 rounded-xl shadow-sm border bg-card text-card-foreground" @submit.prevent>
            <FieldGroup>
              
                <FieldSet>
                    <FieldLegend>Client & Vehicle Details</FieldLegend>
                    <FieldDescription>Information about the customer and their vehicle.</FieldDescription>
                    <FieldGroup>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <Field>
                                <FieldLabel for="customer_name">Customer Name <span class="text-red-500">*</span></FieldLabel>
                                <Input id="customer_name" v-model="form.customer_name" placeholder="e.g. Ahmad Ali" required />
                                <InputError :message="form.errors.customer_name" class="mt-2" />
                            </Field>
                            <Field>
                                <FieldLabel for="customer_phone_num">Phone Number <span class="text-red-500">*</span></FieldLabel>
                                <Input id="customer_phone_num" v-model="form.customer_phone_num" placeholder="e.g. 012-3456789" required />
                                <InputError :message="form.errors.customer_phone_num" class="mt-2" />
                            </Field>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <Field>
                                <FieldLabel for="vehicle_plate">Vehicle Plate <span class="text-red-500">*</span></FieldLabel>
                                <Input id="vehicle_plate" v-model="form.vehicle_plate" placeholder="e.g. JBC 1234" class="uppercase" required />
                                <InputError :message="form.errors.vehicle_plate" class="mt-2" />
                            </Field>
                            <Field>
                                <FieldLabel for="vehicle_brand">Vehicle Brand <span class="text-red-500">*</span></FieldLabel>
                                <Input id="vehicle_brand" v-model="form.vehicle_brand" placeholder="e.g. Toyota, Honda, Perodua" required />
                                <InputError :message="form.errors.vehicle_brand" class="mt-2" />
                            </Field>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <Field>
                                <FieldLabel for="vehicle_model">Vehicle Model <span class="text-red-500">*</span></FieldLabel>
                                <Input id="vehicle_model" v-model="form.vehicle_model" placeholder="e.g. Camry, Civic, Myvi" required />
                                <InputError :message="form.errors.vehicle_model" class="mt-2" />
                            </Field>
                            <Field>
                                <FieldLabel for="status">Status <span class="text-red-500">*</span></FieldLabel>
                                <Select v-model="form.status">
                                    <SelectTrigger id="status">
                                        <SelectValue placeholder="Select status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="Not Started Yet">Not Started Yet</SelectItem>
                                        <!-- <SelectItem value="Fixing in Progress">Fixing in Progress</SelectItem>
                                        <SelectItem value="Waiting for Orders">Waiting for Orders</SelectItem>
                                        <SelectItem value="Completed">Completed</SelectItem> -->
                                    </SelectContent>
                                </Select>
                                <InputError :message="form.errors.status" class="mt-2" />
                            </Field>
                        </div>

                        <Field>
                            <FieldLabel for="vehicle_picture">Vehicle Picture (Optional)</FieldLabel>
                            <FieldDescription>Upload a photo of the vehicle (max 5MB)</FieldDescription>
                            <Input 
                                id="vehicle_picture" 
                                type="file" 
                                accept="image/jpeg,image/png,image/jpg,image/gif"
                                @change="handleVehiclePictureUpload"
                                class="cursor-pointer"
                            />
                            <InputError :message="form.errors.vehicle_picture" class="mt-2" />
                            
                            <!-- Image Preview -->
                            <div v-if="vehiclePicturePreview" class="mt-4 relative inline-block">
                                <img :src="vehiclePicturePreview" alt="Vehicle preview" class="w-48 h-32 object-cover rounded-lg border" />
                                <button 
                                    @click="removeVehiclePicture" 
                                    type="button"
                                    class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600 transition-colors"
                                    title="Remove image"
                                >
                                    <Trash class="w-4 h-4" />
                                </button>
                            </div>
                        </Field>
                    </FieldGroup>
                </FieldSet>

                <FieldSeparator />

                <FieldSet>
                    <FieldLegend>Reported Issue</FieldLegend>
                    <FieldDescription>Describe the problems reported by the customer or diagnosis.</FieldDescription>
                    <FieldGroup>
                        <Field>
                            <Textarea
                                id="reported_issue"
                                v-model="form.reported_issue"
                                placeholder="e.g. Brakes making a squealing noise, oil change required..."
                                class="resize-y min-h-[100px]"
                                required
                            />
                            <InputError :message="form.errors.reported_issue" class="mt-2" />
                        </Field>
                    </FieldGroup>
                </FieldSet>

                <FieldSeparator />

                <FieldSet>
                    <FieldLegend>Estimated Parts Needed</FieldLegend>
                    <FieldDescription>Add parts required for this job to calculate the total cost.</FieldDescription>
                    <FieldGroup>
                        <div 
                            v-for="(row, index) in form.parts_used" 
                            :key="index" 
                            class="p-5 bg-gray-50/80 rounded-xl border border-gray-200 relative mb-4"
                        >
                            <button 
                                @click="removePartRow(index)" 
                                type="button" 
                                class="absolute top-3 right-3 text-red-400 hover:text-red-600 transition-colors p-1"
                                title="Remove Part"
                            >
                                <Trash class="w-5 h-5" />
                            </button>

                            <h4 class="font-medium text-sm text-gray-500 mb-4">Part #{{ index + 1 }}</h4>
                            
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                                <div class="md:col-span-10">
                                    <FieldLabel>Select Part</FieldLabel>
                                    <Select class="w-100" v-model="row.automotive_parts_id" @update:model-value="() => onPartSelect(index)">
                                        <SelectTrigger class="bg-white w-full">
                                            <SelectValue placeholder="Search part..." />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="part in parts" :key="part.automotive_parts_id" :value="part.automotive_parts_id.toString()">
                                                {{ part.name }} ({{ part.part_serial_number }}) - RM {{ part.price }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div class="md:col-span-5" v-if="row.automotive_parts_id && getVariationsForPart(row.automotive_parts_id).length > 0">
                                    <FieldLabel>Variation (Optional)</FieldLabel>
                                    <Select v-model="row.variation_id" @update:model-value="() => onVariationSelect(index)">
                                        <SelectTrigger class="bg-white w-full">
                                            <SelectValue placeholder="Base / Select variation..." />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem :value="null">Base / Default variation (No Variation)</SelectItem>
                                            <SelectItem 
                                                v-for="variation in getVariationsForPart(row.automotive_parts_id)" 
                                                :key="variation.variation_id" 
                                                :value="variation.variation_id.toString()"
                                            >
                                                {{ variation.name }} - RM {{ variation.price }} (Stock: {{ variation.stock_quantity }})
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div :class="row.automotive_parts_id && getVariationsForPart(row.automotive_parts_id).length > 0 ? 'md:col-span-2' : 'md:col-span-3'">
                                    <FieldLabel>Unit Price (RM)</FieldLabel>
                                    <Input :value="row.unit_price.toFixed(2)" type="text" class="bg-gray-100" readonly />
                                </div>
                                <div class="md:col-span-3">
                                    <FieldLabel>
                                        Quantity 
                                        <span v-if="row.automotive_parts_id" class="text-xs text-gray-500 font-normal ml-1">
                                            {{ getStockDisplay(index) }}
                                        </span>
                                    </FieldLabel>
                                    <Input 
                                        v-model.number="row.quantity" 
                                        @input="() => validateQuantity(index)" 
                                        @blur="() => validateQuantity(index)"
                                        type="number" 
                                        min="1" 
                                        :max="getMaxStock(index)"
                                        class="bg-white" 
                                        required 
                                    />
                                    <!-- <p v-if="row.quantity > getMaxStock(index)" class="text-xs text-red-500 mt-1">
                                        Exceeds available stock!
                                    </p> -->
                                </div>
                                <div class="md:col-span-2">
                                    <FieldLabel>Subtotal (RM)</FieldLabel>
                                    <Input :value="row.subtotal.toFixed(2)" type="text" class="bg-emerald-50 text-emerald-900 font-semibold" readonly />
                                </div>
                            </div>
                        </div>

                        <Button type="button" variant="outline" @click="addPartRow" class="w-full md:w-auto border-dashed border-2 hover:bg-gray-50 hover:text-primary">
                            <Plus class="w-4 h-4 mr-2" /> Add Part
                        </Button>
                    </FieldGroup>
                </FieldSet>

                <FieldSeparator />

                <div class="flex justify-end bg-muted/40 p-6 rounded-lg border">
                    <div class="flex flex-col items-end gap-2">
                        <span class="text-sm font-medium text-gray-500 uppercase tracking-wide">Estimated Total Cost</span>
                        <div class="text-4xl font-bold text-gray-900">
                            RM {{ form.total_cost.toFixed(2) }}
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-4 mt-8 pt-6 border-t">
                    <Button variant="outline" class="w-full sm:w-auto" as-child>
                        <Link :href="displayJobOrders()">
                            Cancel
                        </Link>
                    </Button>
                    <AlertDialog>
                        <AlertDialogTrigger as-child>
                            <Button 
                                type="button" 
                                class="w-full sm:w-auto cursor-pointer gap-2"
                                :disabled="hasStockErrors || form.parts_used.length === 0"
                            >
                                <CheckCircle class="w-4 h-4" /> Save Job Order
                            </Button>
                        </AlertDialogTrigger>
                        <AlertDialogContent>
                            <AlertDialogHeader>
                                <AlertDialogTitle>Create Job Order?</AlertDialogTitle>
                            </AlertDialogHeader>
                            <AlertDialogFooter>
                                <AlertDialogCancel class="cursor-pointer">Cancel</AlertDialogCancel>
                                <AlertDialogAction class="cursor-pointer" @click="submit">Yes, Create</AlertDialogAction>
                            </AlertDialogFooter>
                        </AlertDialogContent>
                    </AlertDialog>
                </div>

            </FieldGroup>
        </form>
    </div>
</template>