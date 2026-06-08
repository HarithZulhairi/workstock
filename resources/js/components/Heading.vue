<script setup lang="ts">
type Props = {
    title: string;
    description?: string;
    imageSrc?: string;
    variant?: 'default' | 'small';
};

withDefaults(defineProps<Props>(), {
    variant: 'default',
});
</script>

<template>
    <header :class="variant === 'small' ? '' : 'mb-8 space-y-0.5 flex gap-4'">
        <div>
            <img
                v-if="imageSrc"
                :src="imageSrc"
                alt="Heading Illustration"
                class="h-12 w-auto"
            />
        </div>
        <div>
            <h2
                :class="
                    variant === 'small'
                        ? 'mb-0.5 text-base font-medium'
                        : 'text-2xl font-semibold tracking-tight'
                "
            >
                {{ title }}
            </h2>
            <p v-if="description" class="mt-1 text-sm text-muted-foreground">
                {{ description }}
            </p>
        </div>

        
    </header>
</template>
