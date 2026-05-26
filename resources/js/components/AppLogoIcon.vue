<script setup lang="ts">
import type { HTMLAttributes } from 'vue'; 

defineOptions({
    inheritAttrs: false,
});

type Props = {
    class?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <img 
        src="/images/workstock-icon-cropped.png" 
        alt="WorkStock Logo" 
        v-bind="$attrs"
        :class="['object-contain', $props.class]" 
    />
</template>
