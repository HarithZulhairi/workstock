<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { PackagePlus, Plus, Trash, ImagePlus } from 'lucide-vue-next';
import { computed } from 'vue';
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

import { displayStock, updateStock } from '@/routes';

const props = defineProps({
    part: { type: Object, required: true },
    categories: { type: Array, required: true }
});

interface Variation {
    variation_id?: number;
    name: string;
    price: number;
    picture?: File | null;
    previewUrl?: string | null;
}

// Map existing variations to our form structure
const initialVariations = props.part.variations ? props.part.variations.map((v: any) => ({
    variation_id: v.variation_id,
    name: v.name,
    price: v.price,
    picture: null,
    previewUrl: v.picture ? `/storage/${v.picture}` : null,
})) : [];

const form = useForm({
    _method: 'put', // CRUCIAL: Tells Laravel this is a PUT request, even though it sends via POST for files
    name: props.part.name,
    part_serial_number: props.part.part_serial_number,
    category_id: props.part.category_id.toString(),
    price: props.part.price, 
    brand: props.part.brand || '',
    warranty: props.part.warranty || '',
    dimensions: props.part.dimensions || '',
    condition: props.part.condition?.toString() || '', 
    part_images: [] as File[], 
    part_description: props.part.part_description || '',
    is_visible_to_public: props.part.is_visible_to_public == 1,
    variations: initialVariations as Variation[],
});

const addVariation = () => {
    form.variations.push({
        name: '',
        price: 0.00,
    });
};

const removeVariation = (index: number) => {
    form.variations.splice(index, 1); 
};

const handleImageUpload = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files) {
        const filesArray = Array.from(target.files);
        if (filesArray.length > 3) {
            alert("You can only upload a maximum of 3 images.");
            target.value = ''; 
            form.part_images = [];
            return;
        }
        form.part_images = filesArray;
    }
};

const handleVariationImageUpload = (event: Event, index: number) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        const file = target.files[0];
        
        if (file.size > 2 * 1024 * 1024) {
            alert(`This picture is too large. The maximum size is 2MB.`);
            target.value = ''; 
            form.variations[index].picture = null;
            return;
        }
        
        form.variations[index].picture = file;
        
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
    if (!form.condition) return ''; 
    if (val >= 8) return '(New)';
    if (val >= 6) return '(Like New)';
    if (val >= 4) return '(Used)';
    if (val >= 2) return '(Heavily used)';
    return '(Poor)';
});

const conditionColor = computed(() => {
    const val = Number(form.condition);
    if (!form.condition) return 'text-gray-500';
    if (val >= 8) return 'text-emerald-600'; 
    if (val >= 6) return 'text-blue-600';    
    if (val >= 4) return 'text-amber-600';   
    if (val >= 2) return 'text-orange-600';  
    return 'text-red-600';                   
});

const submit = () => {
    form.post(updateStock(props.part.automotive_parts_id));
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Display Stock', href: displayStock() },
            { title: 'Edit Stock', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Edit Automotive Part" />

    <div class="my-6 px-12 flex items-start gap-4">
      <div class="p-3 bg-primary/10 text-primary rounded-xl">
        <Pencil class="w-8 h-8" />
      </div>
      <div>
        <h1 class="text-2xl font-bold tracking-tight">Edit Part: {{ part.name }}</h1>
        <p class="text-gray-500 mt-1">Update specifications, pricing, and variations.</p>
      </div>
    </div>

    <div class="mb-10 px-4 w-full">
        <form class="max-w-6xl mx-auto p-8 rounded-xl shadow-sm border bg-card text-card-foreground" @submit.prevent="submit">
            <FieldGroup>
              
              <FieldSet>
                <FieldLegend>Part Information</FieldLegend>
                <FieldGroup>
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                      <Field>
                        <FieldLabel for="name">Part Name <span class="text-red-500">*</span></FieldLabel>
                        <Input id="name" v-model="form.name" required />
                        <InputError :message="form.errors.name" class="mt-2" />
                      </Field>
                      <Field>
                        <FieldLabel for="part_serial_number">Serial Number <span class="text-red-500">*</span></FieldLabel>
                        <Input id="part_serial_number" v-model="form.part_serial_number" required />
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
                            <SelectItem v-for="cat in categories" :key="cat.category_id" :value="cat.category_id.toString()">
                                {{ cat.name }}
                            </SelectItem>
                          </SelectContent>
                        </Select>
                        <InputError :message="form.errors.category_id" class="mt-2" />
                      </Field>
                      <Field>
                        <FieldLabel for="brand">Brand</FieldLabel>
                        <Input id="brand" v-model="form.brand" />
                        <InputError :message="form.errors.brand" class="mt-2" />
                      </Field>
                  </div>
                </FieldGroup>
              </FieldSet>

              <FieldSeparator />

              <FieldSet>
                <FieldLegend>Specifications</FieldLegend>
                <FieldGroup>
                  <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                      <Field>
                        <FieldLabel for="warranty">Warranty</FieldLabel>
                        <Input id="warranty" v-model="form.warranty" />
                      </Field>
                      <Field>
                        <FieldLabel for="dimensions">Dimensions</FieldLabel>
                        <Input id="dimensions" v-model="form.dimensions" />
                      </Field>
                      <Field>
                        <FieldLabel for="condition" class="flex items-center">
                            Condition (1-10) 
                            <span class="ml-2 text-sm font-semibold transition-colors duration-200" :class="conditionColor">
                                {{ conditionText }}
                            </span>
                        </FieldLabel>
                        <Input @input="changeCondition" id="condition" v-model="form.condition" type="number" min="1" max="10" />
                      </Field>
                  </div>
                </FieldGroup>
              </FieldSet>

              <FieldSeparator />

              <FieldSet>
                <FieldLegend>Base Pricing</FieldLegend>
                <FieldDescription>
                  Update the default selling price. (Stock is managed via the Inventory Dashboard).
                </FieldDescription>
                <FieldGroup>
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                      <Field>
                        <FieldLabel for="price">Base Price (RM) <span class="text-red-500">*</span></FieldLabel>
                        <Input id="price" v-model="form.price" type="number" step="0.01" min="0" required />
                        <InputError :message="form.errors.price" class="mt-2" />
                      </Field>
                  </div>
                </FieldGroup>
              </FieldSet>

              <FieldSeparator />

              <FieldSet>
                <FieldLegend>Product Variations (Optional)</FieldLegend>
                <FieldDescription>
                  Modify specific versions of this part. Stock quantities are managed via the dashboard.
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
                    >
                        <Trash class="w-5 h-5" />
                    </button>

                    <h4 class="font-medium text-sm text-gray-500 mb-4">Variation #{{ index + 1 }}</h4>
                    
                    <div class="flex flex-col md:flex-row gap-6">
                        <div class="w-full md:w-32 flex flex-col gap-2 items-center">
                            <div class="w-32 h-32 rounded-lg border border-gray-200 bg-white flex items-center justify-center overflow-hidden">
                                <img v-if="variation.previewUrl" :src="variation.previewUrl" class="object-cover w-full h-full" />
                                <ImagePlus v-else class="w-8 h-8 text-gray-300" />
                            </div>
                            <label :for="'var_pic_' + index" class="cursor-pointer text-xs font-semibold text-primary hover:text-primary/80 bg-black/10 px-2 py-1 rounded-full">
                                {{ variation.previewUrl ? 'Change Picture' : 'Add Picture' }}
                            </label>
                            <Input 
                                :id="'var_pic_' + index" 
                                type="file" 
                                accept="image/*" 
                                class="hidden" 
                                @change="(e) => handleVariationImageUpload(e, index)"
                            />
                        </div>

                        <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-6">
                            <Field>
                                <FieldLabel :for="'var_name_' + index">Variation Name <span class="text-red-500">*</span></FieldLabel>
                                <Input :id="'var_name_' + index" v-model="variation.name" required class="bg-white" />
                            </Field>
                            <Field>
                                <FieldLabel :for="'var_price_' + index">Price (RM) <span class="text-red-500">*</span></FieldLabel>
                                <Input :id="'var_price_' + index" v-model="variation.price" type="number" step="0.01" min="0" required class="bg-white" />
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
                  Upload up to 3 images. Note: Uploading new images will replace the existing ones.
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
                    <div v-if="form.part_images.length === 0 && part.part_images && part.part_images.length > 0" class="mt-4 flex gap-2">
                        <img v-for="(img, idx) in part.part_images" :key="idx" :src="`/storage/${img}`" class="w-16 h-16 object-cover rounded-md border" />
                    </div>
                    <div v-if="form.part_images.length > 0" class="mt-2 text-sm text-blue-600 font-medium">
                        {{ form.part_images.length }} new image(s) selected (Will replace existing).
                    </div>
                  </Field>
                  <Field>
                    <FieldLabel for="part_description">Description & Specifications</FieldLabel>
                    <Textarea id="part_description" v-model="form.part_description" class="resize-y min-h-[100px]" />
                  </Field>
                </FieldGroup>
              </FieldSet>

              <FieldSeparator />

              <FieldSet>
                <FieldGroup>
                  <Field orientation="horizontal" class="items-start space-x-3 bg-muted/40 p-4 rounded-lg border">
                    <Checkbox
                      id="is_visible_to_public"
                      :checked="form.is_visible_to_public"
                      @update:checked="val => form.is_visible_to_public = !!val"
                      class="mt-1"
                    />
                    <div class="space-y-1 leading-none">
                        <FieldLabel for="is_visible_to_public" class="font-medium cursor-pointer text-base">
                          Visible to Public Catalog
                        </FieldLabel>
                    </div>
                  </Field>
                </FieldGroup>
              </FieldSet>

              <div class="flex items-center justify-end gap-4 mt-8 pt-6 border-t">
                <Button variant="outline" class="w-full sm:w-auto" as-child>
                    <Link :href="displayStock()">Cancel Edit</Link>
                </Button>
                <Button type="submit" class="w-full sm:w-auto" :disabled="form.processing">
                    {{ form.processing ? 'Saving...' : 'Update Automotive Part' }}
                </Button>
              </div>

            </FieldGroup>
        </form>
    </div>
</template>