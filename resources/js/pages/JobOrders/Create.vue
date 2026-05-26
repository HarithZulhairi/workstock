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
        part_images?: string[];
        variations?: Array<{
            variation_id: number;
            name: string;
            price: number;
            stock_quantity: number;
            picture?: string;
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
    status: 'Pending',
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

// Get available base stock for a selected part
const getBaseStock = (partId: string) => {
    if (!partId) return [];
    const part = props.parts.find(p => p.automotive_parts_id.toString() === partId);
    return part?.stock_quantity || 0;
};

// Check if a specific variation is already used in another row
const isVariationSelected = (variationId: string | number | null, currentIndex: number) => {
    return form.parts_used.some((row, i) => 
        i !== currentIndex && // Don't check the current row against itself
        row.variation_id == variationId
    );
};

// Check if the BASE part (no variation) is already used in another row
const isBasePartSelected = (partId: string | number, currentIndex: number) => {
    return form.parts_used.some((row, i) => 
        i !== currentIndex && 
        row.automotive_parts_id == partId && 
        row.variation_id == null
    );
};

// Get available variations for a selected part dropdown (Hides variations already selected)
const getVariationsForPart = (partId: string, currentIndex: number) => {
    if (!partId) return [];
    
    const part = props.parts.find(p => p.automotive_parts_id.toString() === partId);
    if (!part || !part.variations) return [];

    // Return only variations that haven't been selected in OTHER rows
    return part.variations.filter(v => !isVariationSelected(v.variation_id, currentIndex));
};

// Determine if a Part should be hidden from the main "Select Part" dropdown
const isPartFullySelected = (part: any, currentIndex: number) => {
    // 1. Is the base part already selected?
    const baseSelected = form.parts_used.some((row, i) => 
        i !== currentIndex && 
        row.automotive_parts_id == part.automotive_parts_id && 
        row.variation_id == null
    );

    // 2. Are ALL variations of this part already selected?
    let allVariationsSelected = false;
    if (part.variations && part.variations.length > 0) {
        allVariationsSelected = part.variations.every((v: any) => 
            isVariationSelected(v.variation_id, currentIndex)
        );
    } else {
        // If it has no variations, and the base is selected, it's fully selected
        allVariationsSelected = baseSelected;
    }

    // A part is "Fully Selected" (and should be hidden) if BOTH the base part is used
    // AND all its variations are used.
    return baseSelected && allVariationsSelected;
};

// Filter the main Parts dropdown to hide fully consumed parts
const getAvailableParts = (currentIndex: number) => {
    return props.parts.filter(part => !isPartFullySelected(part, currentIndex));
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

    
    const part = props.parts.find(p => p.automotive_parts_id == selectedPartId);
    
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

    if (maxStock === 0) {
        return '(Out of stock)';
    }
    
    if (row.variation_id) {
        return `(Max: ${maxStock} available)`;
    }
    
    return `(Max: ${maxStock} available)`;
};

// Get image for the selected part/variation
const getPartImage = (index: number) => {
    const row = form.parts_used[index];
    if (!row.automotive_parts_id) return null;
    
    const part = props.parts.find(p => p.automotive_parts_id.toString() === row.automotive_parts_id);
    if (!part) return null;
    
    // If variation is selected and has an image, use it
    if (row.variation_id) {
        const variation = part.variations?.find(v => v.variation_id.toString() === row.variation_id);
        if (variation?.picture) {
            return `/storage/${variation.picture}`;
        }
    }
    
    // Otherwise, use base part image if available
    if (part.part_images && part.part_images.length > 0) {
        return `/storage/${part.part_images[0]}`;
    }
    
    return null;
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
        <div>
            <img src="/images/application.png" alt="WorkStock Logo" class="h-12 w-12" />
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
                                        <SelectItem value="Pending">Pending</SelectItem>
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
                                class="absolute top-3 right-3 text-red-400 hover:text-red-600 transition-colors p-1 cursor-pointer"
                                title="Remove Part"
                            >
                                <Trash class="w-5 h-5" />
                            </button>

                            <div class="flex items-start gap-4 mb-4">
                                <!-- Part Image -->
                                <div class="flex-shrink-0">
                                    <div v-if="getPartImage(index)" class="w-20 h-20 rounded-lg border-2 border-gray-200 overflow-hidden bg-white shadow-sm">
                                        <img 
                                            :src="getPartImage(index)!" 
                                            :alt="row.automotive_parts_id ? props.parts.find(p => p.automotive_parts_id.toString() === row.automotive_parts_id)?.name : 'Part'" 
                                            class="w-full h-full object-cover"
                                        />
                                    </div>
                                    <div v-else class="w-20 h-20 rounded-lg border-2 border-dashed border-gray-300 bg-gray-100 flex items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                            <circle cx="8.5" cy="8.5" r="1.5"/>
                                            <polyline points="21 15 16 10 5 21"/>
                                        </svg>
                                    </div>
                                </div>

                                <!-- Part Number Label -->
                                <div class="flex-1">
                                    <h4 class="font-medium text-sm text-gray-500">Part #{{ index + 1 }}</h4>
                                    <p v-if="row.automotive_parts_id" class="text-xs text-gray-400 mt-1">
                                        {{ props.parts.find(p => p.automotive_parts_id.toString() === row.automotive_parts_id)?.name }}
                                        <span v-if="row.variation_id" class="text-blue-600">
                                            • {{ props.parts.find(p => p.automotive_parts_id.toString() === row.automotive_parts_id)?.variations?.find(v => v.variation_id.toString() === row.variation_id)?.name }}
                                        </span>
                                    </p>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                                <div class="md:col-span-10">
                                    <FieldLabel>Select Part</FieldLabel>
                                    <Select class="w-100" v-model="row.automotive_parts_id" @update:model-value="() => onPartSelect(index)">
                                        <SelectTrigger class="bg-white w-full">
                                            <SelectValue placeholder="Select part..." />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem 
                                                v-for="part in getAvailableParts(index)" 
                                                :key="part.automotive_parts_id" 
                                                :value="part.automotive_parts_id.toString()"
                                            >
                                                {{ part.name }} ({{ part.part_serial_number }}) - RM {{ part.price }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div class="md:col-span-5" v-if="row.automotive_parts_id && props.parts.find(p => p.automotive_parts_id.toString() == row.automotive_parts_id)?.variations?.length > 0">
                                    <FieldLabel>Variation (Optional)</FieldLabel>
                                    <Select v-model="row.variation_id" @update:model-value="() => onVariationSelect(index)">
                                        <SelectTrigger class="bg-white w-full">
                                            <SelectValue placeholder="Base / Select variation..." />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <!-- Hide Base Part option if another row is already using the Base Part -->
                                            <SelectItem :value="null" :disabled="isBasePartSelected(row.automotive_parts_id, index)">
                                                Base / Default variation (Stock: {{ getBaseStock(row.automotive_parts_id) }})
                                            </SelectItem>
                                            
                                            <!-- Use the new function with the index passed in -->
                                            <SelectItem 
                                                v-for="variation in getVariationsForPart(row.automotive_parts_id, index)"
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
                                        <span 
                                            v-if="row.automotive_parts_id" 
                                            :class="[
                                                'text-xs font-normal ml-1', 
                                                getMaxStock(index) === 0 ? 'text-red-500 font-bold' : 'text-gray-500'
                                            ]"
                                        >
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

                        <Button type="button" variant="outline" @click="addPartRow" class="w-full md:w-auto border-dashed border-2 hover:bg-gray-50 hover:text-primary cursor-pointer">
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