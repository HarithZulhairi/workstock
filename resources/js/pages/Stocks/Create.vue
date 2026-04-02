<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { PackagePlus } from 'lucide-vue-next';
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

import { createStock, displayStock, storeStock } from '@/routes';

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

const form = useForm({
    name: '',
    part_serial_number: '',
    category_id: '',
    price: 0.00,
    stock_quantity: 1,
    // New fields added here
    brand: '',
    warranty: '',
    dimensions: '',
    condition: '', 
    // Changed to an array for multiple images
    part_images: [] as File[], 
    part_description: '',
    is_visible_to_public: true,
});

// Handle multiple image selection and enforce the limit of 3
const handleImageUpload = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files) {
        const filesArray = Array.from(target.files);
        
        if (filesArray.length > 3) {
            alert("You can only upload a maximum of 3 images.");
            target.value = ''; // Reset the input
            form.part_images = [];
            return;
        }
        
        form.part_images = filesArray;
    }
};

const submit = () => {
    form.post(storeStock());
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
                        <FieldLabel for="condition">Condition (1-10)</FieldLabel>
                        <Input id="condition" v-model="form.condition" type="number" min="1" max="10" placeholder="10" />
                        <InputError :message="form.errors.condition" class="mt-2" />
                      </Field>
                  </div>
                </FieldGroup>
              </FieldSet>

              <FieldSeparator />

              <FieldSet>
                <FieldLegend>Inventory & Pricing</FieldLegend>
                <FieldDescription>
                  Set the selling price and current physical stock count.
                </FieldDescription>
                <FieldGroup>
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                      <Field>
                        <FieldLabel for="price">Price (RM) <span class="text-red-500">*</span></FieldLabel>
                        <Input id="price" v-model="form.price" type="number" step="0.01" min="0" placeholder="0.00" required />
                        <InputError :message="form.errors.price" class="mt-2" />
                      </Field>
                      <Field>
                        <FieldLabel for="stock_quantity">Initial Stock Quantity <span class="text-red-500">*</span></FieldLabel>
                        <Input id="stock_quantity" v-model="form.stock_quantity" type="number" min="1" placeholder="1" required />
                        <InputError :message="form.errors.stock_quantity" class="mt-2" />
                      </Field>
                  </div>
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
                    <div v-if="form.part_images.length > 0" class="mt-2 text-sm text-gray-500">
                        Selected {{ form.part_images.length }} image(s).
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
                    <Link :href="displayStock()">
                        Back to Stock List
                    </Link>
                </Button>
                <AlertDialog>
                  <AlertDialogTrigger as-child>
                    <Button type="button" class="w-full sm:w-auto">
                      Save Automotive Part
                    </Button>
                  </AlertDialogTrigger>
                  <AlertDialogContent>
                    <AlertDialogHeader>
                      <AlertDialogTitle>Are you sure you want to save this automotive part?</AlertDialogTitle>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                      <AlertDialogCancel>Cancel</AlertDialogCancel>
                      <AlertDialogAction @click="submit">Yes</AlertDialogAction>
                    </AlertDialogFooter>
                  </AlertDialogContent>
                </AlertDialog>
              </div>

            </FieldGroup>
        </form>
    </div>
</template>