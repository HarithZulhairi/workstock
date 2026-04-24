<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { PackagePlus, Plus, Trash, ImagePlus } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';

import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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

import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';

import { Textarea } from '@/components/ui/textarea';

import { Switch } from '@/components/ui/switch';

import { createStock, displayStock, storeStock } from '@/routes';

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

// Define a TypeScript interface for our variation object structure
interface Variation {
    name: string;
    price: number;
    stock_quantity: number;
}

// Alert dialog state
const showErrorAlert = ref(false);
const errorAlertMessage = ref('');
const partImagePreviews = ref<string[]>([]);

// Separate ref for checkbox to work with v-model
const isVisibleToPublic = ref(true);

const form = useForm({
    name: '',
    part_serial_number: '',
    category_id: '',
    price: 0.00, // Base price
    stock_quantity: 1, // Base stock
    brand: '',
    warranty: '',
    dimensions: '',
    condition: '', 
    part_images: [] as File[], 
    part_description: '',
    is_visible_to_public: isVisibleToPublic.value,
    variations: [] as Variation[],
});

// Watch for checkbox changes and sync with form
watch(isVisibleToPublic, (newVal) => {
    console.log('Checkbox changed to:', newVal, 'type:', typeof newVal);
    form.is_visible_to_public = newVal;
});

// Logic to handle adding and removing dynamic variation rows
const addVariation = () => {
    form.variations.push({
        name: '',
        price: 0.00,
        stock_quantity: 1
    });
};

const removeVariation = (index: number) => {
    form.variations.splice(index, 1); 
};

// Handle multiple image selection and enforce the limit of 3
const handleImageUpload = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files) {
        const filesArray = Array.from(target.files);
        
        if (filesArray.length > 3) {
            errorAlertMessage.value = "You can only upload a maximum of 3 images.";
            showErrorAlert.value = true;
            target.value = ''; // Reset input
            form.part_images = [];
            partImagePreviews.value = []; // Reset previews
            return;
        }
        
        // Check each file size (2MB limit)
        for (let i = 0; i < filesArray.length; i++) {
            if (filesArray[i].size > 2 * 1024 * 1024) {
                errorAlertMessage.value = `Image "${filesArray[i].name}" is too large. Maximum size is 2MB per image.`;
                showErrorAlert.value = true;
                target.value = ''; // Reset input
                form.part_images = [];
                partImagePreviews.value = []; // Reset previews
                return;
            }
        }
        
        form.part_images = filesArray;

        // Generate preview URLs
        partImagePreviews.value = [];
        filesArray.forEach(file => {
            const reader = new FileReader();
            reader.onload = (e) => {
                if (e.target?.result) {
                    partImagePreviews.value.push(e.target.result as string);
                }
            };
            reader.readAsDataURL(file);
        });
    } else {
        // If the user cancels the file dialog
        form.part_images = [];
        partImagePreviews.value = [];
    }
};

const handleVariationImageUpload = (event: Event, index: number) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        const file = target.files[0];
        
        // Check file size (2MB) - existing validation on server too
        if (file.size > 2 * 1024 * 1024) {
            errorAlertMessage.value = `This picture is too large. The maximum size is 2MB.`;
            showErrorAlert.value = true;
            target.value = ''; // Reset input
            form.variations[index].picture = null;
            form.variations[index].previewUrl = null;
            return;
        }
        
        // Store the file for upload
        form.variations[index].picture = file;
        
        // Create a data URL for preview - Inertia post handles previews URLs automatically
        const reader = new FileReader();
        reader.onload = (e) => {
            form.variations[index].previewUrl = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    }
};

const changeCondition = () => {
    if (form.condition) {
        const value = parseInt(form.condition.toString(), 10);
        if (isNaN(value) || value < 1) {
            form.condition = '1';
        } else if (value > 10) {
            form.condition = '10';
        }
    }
};

const conditionText = computed(() => {
    const val = Number(form.condition);
    if (!form.condition) return ''; // Show nothing if empty
    if (val >= 8) return '(New)';
    if (val >= 6) return '(Like New)';
    if (val >= 4) return '(Used)';
    if (val >= 2) return '(Heavily used)';
    return '(Poor)';
});

const conditionColor = computed(() => {
    const val = Number(form.condition);
    if (!form.condition) return 'text-gray-500';
    if (val >= 8) return 'text-emerald-600'; // Green for New
    if (val >= 6) return 'text-blue-600';    // Blue for Like New
    if (val >= 4) return 'text-amber-600';   // Yellow for Used
    if (val >= 2) return 'text-orange-600';  // Orange for Heavily Used
    return 'text-red-600';                   // Red for Poor
});

const submit = () => {
    form.post(storeStock(), {
        onSuccess: () => {
            console.log('Part created successfully');
        }
    });
};

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Create Stock',
                href: createStock(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Add Automotive Part" />

    <div class="my-6 px-12 flex items-start gap-4">
      <div class="p-3 bg-primary/10 text-primary rounded-xl">
        <PackagePlus class="w-8 h-8" />
      </div>
      <div>
        <h1 class="text-2xl font-bold tracking-tight">Add Automotive Part</h1>
        <p class="text-gray-500 mt-1">Register a new spare part or accessory into the workshop inventory.</p>
      </div>
    </div>

    <div class="mb-10 px-4 w-full">
        <form class="max-w-6xl mx-auto p-8 rounded-xl shadow-sm border bg-card text-card-foreground" @submit.prevent>
            <FieldGroup>
              
              <FieldSet>
                <FieldLegend>Part Information</FieldLegend>
                <FieldDescription>
                  Basic identification details for the automotive part.
                </FieldDescription>
                <FieldGroup>
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                      <Field>
                        <FieldLabel for="name">Part Name <span class="text-red-500">*</span></FieldLabel>
                        <Input id="name" v-model="form.name" placeholder="e.g. Ceramic Brake Pads (Front)" required />
                        <InputError :message="form.errors.name" class="mt-2" />
                      </Field>
                      <Field>
                        <FieldLabel for="part_serial_number">Serial Number <span class="text-red-500">*</span></FieldLabel>
                        <Input id="part_serial_number" v-model="form.part_serial_number" placeholder="e.g. BRK-FD-001" required />
                        <InputError :message="form.errors.part_serial_number" class="mt-2" />
                      </Field>
                  </div>

                  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                      <Field>
                        <FieldLabel for="category_id">Category <span class="text-red-500">*</span></FieldLabel>
                        <Select v-model="form.category_id">
                          <SelectTrigger id="category_id">
                            <SelectValue placeholder="Select a category" />
                          </SelectTrigger>
                          <SelectContent>
                            <SelectItem value="1">Engine Components</SelectItem>
                            <SelectItem value="2">Brakes & Suspension</SelectItem>
                            <SelectItem value="3">Fluids & Lubricants</SelectItem>
                            <SelectItem value="4">Tools & Accessories</SelectItem>
                          </SelectContent>
                        </Select>
                        <InputError :message="form.errors.category_id" class="mt-2" />
                      </Field>
                      <Field>
                        <FieldLabel for="brand">Brand</FieldLabel>
                        <Input id="brand" v-model="form.brand" placeholder="e.g. Bosch, OEM" />
                        <InputError :message="form.errors.brand" class="mt-2" />
                      </Field>
                  </div>
                </FieldGroup>
              </FieldSet>

              <FieldSeparator />

              <FieldSet>
                <FieldLegend>Specifications</FieldLegend>
                <FieldDescription>
                  Additional details regarding warranty, dimensions, and condition.
                </FieldDescription>
                <FieldGroup>
                  <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                      <Field>
                        <FieldLabel for="warranty">Warranty</FieldLabel>
                        <Input id="warranty" v-model="form.warranty" placeholder="e.g. 6 Months" />
                        <InputError :message="form.errors.warranty" class="mt-2" />
                      </Field>
                      <Field>
                        <FieldLabel for="dimensions">Dimensions</FieldLabel>
                        <Input id="dimensions" v-model="form.dimensions" placeholder="e.g. 10x5x2 cm" />
                        <InputError :message="form.errors.dimensions" class="mt-2" />
                      </Field>
                      <Field>
                        <FieldLabel for="condition" class="flex items-center">
                            Condition (1-10) 
                            <span class="ml-2 text-sm font-semibold transition-colors duration-200" :class="conditionColor">
                                {{ conditionText }}
                            </span>
                        </FieldLabel>
                        <Input 
                            @input="changeCondition" 
                            id="condition" 
                            v-model="form.condition" 
                            type="number" 
                            min="1" 
                            max="10" 
                            placeholder="10" 
                        />
                        <InputError :message="form.errors.condition" class="mt-2" />
                      </Field>
                  </div>
                </FieldGroup>
              </FieldSet>

              <FieldSeparator />

              <FieldSet>
                <FieldLegend>Base Inventory & Pricing</FieldLegend>
                <FieldDescription>
                  Set the default selling price and stock. (Used if there are no variations).
                </FieldDescription>
                <FieldGroup>
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                      <Field>
                        <FieldLabel for="price">Base Price (RM) <span class="text-red-500">*</span></FieldLabel>
                        <Input id="price" v-model="form.price" type="number" step="0.01" min="0" placeholder="0.00" required />
                        <InputError :message="form.errors.price" class="mt-2" />
                      </Field>
                      <Field>
                        <FieldLabel for="stock_quantity">Base Stock Quantity <span class="text-red-500">*</span></FieldLabel>
                        <Input id="stock_quantity" v-model="form.stock_quantity" type="number" min="1" placeholder="1" required />
                        <InputError :message="form.errors.stock_quantity" class="mt-2" />
                      </Field>
                  </div>
                </FieldGroup>
              </FieldSet>

              <FieldSeparator />

              <FieldSet>
                <FieldLegend>Product Variations (Optional)</FieldLegend>
                <FieldDescription>
                  Add specific versions of this part. Each variation can have its own picture. Leave blank if not needed.
                </FieldDescription>
                <FieldGroup>
                  <div 
                    v-for="(variation, index) in form.variations" 
                    :key="index" 
                    class="p-5 bg-gray-50/80 rounded-xl border border-gray-200 relative mb-4"
                  >
                    <button 
                        @click="removeVariation(index)" 
                        type="button" 
                        class="absolute top-3 right-3 text-red-400 hover:text-red-600 transition-colors p-1"
                        title="Remove Variation"
                    >
                        <Trash class="w-5 h-5" />
                    </button>

                    <h4 class="font-medium text-sm text-gray-500 mb-4">Variation #{{ index + 1 }}</h4>
                    
                    <div class="flex flex-col md:flex-row gap-6">
                        
                        <div class="w-full md:w-32 flex flex-col gap-2 items-center">
                            <div class="w-32 h-32 rounded-lg border border-gray-200 bg-white flex items-center justify-center overflow-hidden">
                                <img v-if="variation.previewUrl" :src="variation.previewUrl" class="object-cover w-full h-full" alt="Variation preview" />
                                <ImagePlus v-else class="w-8 h-8 text-gray-300" />
                            </div>
                            <label :for="'var_pic_' + index" class="cursor-pointer text-xs font-semibold text-primary hover:text-primary/80 bg-black/10 px-2 py-1 rounded-full">
                                {{ variation.picture ? 'Change Picture' : 'Add Picture' }}
                            </label>
                            <Input 
                                :id="'var_pic_' + index" 
                                type="file" 
                                accept="image/*" 
                                class="hidden" 
                                @change="(e) => handleVariationImageUpload(e, index)"
                            />
                        </div>

                        <div class="flex-1 grid grid-cols-1 md:grid-cols-3 gap-6">
                            <Field>
                                <FieldLabel :for="'var_name_' + index">Variation Name <span class="text-red-500">*</span></FieldLabel>
                                <Input :id="'var_name_' + index" v-model="variation.name" placeholder="e.g. Red, Metallic" required class="bg-white" />
                            </Field>
                            <Field>
                                <FieldLabel :for="'var_price_' + index">Price (RM) <span class="text-red-500">*</span></FieldLabel>
                                <Input :id="'var_price_' + index" v-model="variation.price" type="number" step="0.01" min="0" required class="bg-white" />
                            </Field>
                            <Field>
                                <FieldLabel :for="'var_stock_' + index">Stock Quantity <span class="text-red-500">*</span></FieldLabel>
                                <Input :id="'var_stock_' + index" v-model="variation.stock_quantity" type="number" min="0" required class="bg-white" />
                            </Field>
                        </div>
                    </div>
                  </div>

                  <Button type="button" variant="outline" @click="addVariation" class="w-full md:w-auto border-dashed border-2 hover:bg-gray-50 hover:text-primary">
                      <Plus class="w-4 h-4 mr-2" /> Add Variation
                  </Button>
                </FieldGroup>
              </FieldSet>

              <FieldSeparator />

              <FieldSet>
                <FieldLegend>Media & Description</FieldLegend>
                <FieldDescription>
                  Upload up to 3 images and provide technical specifications.
                </FieldDescription>
                <FieldGroup>
                  <Field>
                    <FieldLabel for="part_images">Part Images (Max 3)</FieldLabel>
                    <Input 
                      id="part_images" 
                      type="file" 
                      accept="image/*" 
                      multiple
                      @change="handleImageUpload"
                      class="cursor-pointer file:text-primary file:font-semibold" 
                    />
                    <InputError :message="form.errors.part_images" class="mt-2" />

                    <div v-if="partImagePreviews.length > 0" class="mt-3">
                        <p class="text-sm text-gray-500 mb-2">Selected {{ partImagePreviews.length }} image(s) for upload:</p>
                        <div class="flex gap-2 flex-wrap">
                            <img 
                                v-for="(preview, idx) in partImagePreviews" 
                                :key="idx" 
                                :src="preview" 
                                class="w-20 h-20 object-cover rounded-md border border-gray-200 shadow-sm" 
                                :alt="`Part image preview ${idx + 1}`"
                            />
                        </div>
                    </div>
                  </Field>
                  <Field>
                    <FieldLabel for="part_description">Description & Specifications</FieldLabel>
                    <Textarea
                      id="part_description"
                      v-model="form.part_description"
                      placeholder="Enter vehicle compatibility, or technical notes..."
                      class="resize-y min-h-[100px]"
                    />
                    <InputError :message="form.errors.part_description" class="mt-2" />
                  </Field>
                </FieldGroup>
              </FieldSet>

              <FieldSeparator />

              <FieldSet>
                <FieldGroup>
                  <Field orientation="horizontal" class="items-center justify-between bg-muted/40 p-4 rounded-lg border">
                    <div class="space-y-0.5">
                        <FieldLabel for="is_visible_to_public" class="font-medium text-base">
                          Visible to Public Catalog
                        </FieldLabel>
                        <FieldDescription class="text-sm">
                          Make this part visible in the public catalog
                        </FieldDescription>
                    </div>
                    <Switch
                      id="is_visible_to_public"
                      v-model="isVisibleToPublic"
                    />
                  </Field>
                </FieldGroup>
              </FieldSet>

              <div class="flex items-center justify-end gap-4 mt-8 pt-6 border-t">
                <Button variant="outline" class="w-full sm:w-auto" as-child>
                    <Link :href="displayStock()">
                        Back to Stock List
                    </Link>
                </Button>
                <AlertDialog>
                  <AlertDialogTrigger as-child>
                    <Button type="button" class="w-full sm:w-auto cursor-pointer">
                      Save Automotive Part
                    </Button>
                  </AlertDialogTrigger>
                  <AlertDialogContent>
                    <AlertDialogHeader>
                      <AlertDialogTitle>Are you sure you want to save this automotive part?</AlertDialogTitle>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                      <AlertDialogCancel class="cursor-pointer" >Cancel</AlertDialogCancel>
                      <AlertDialogAction class="cursor-pointer" @click="submit">Yes</AlertDialogAction>
                    </AlertDialogFooter>
                  </AlertDialogContent>
                </AlertDialog>
              </div>

            </FieldGroup>
        </form>
    </div>

    <!-- Error Alert Dialog -->
    <AlertDialog :open="showErrorAlert" @update:open="showErrorAlert = $event">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>Upload Error</AlertDialogTitle>
          <AlertDialogDescription>
            {{ errorAlertMessage }}
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogAction @click="showErrorAlert = false">OK</AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
</template>