<script setup lang="ts">
import { useThemePreset } from '@/composables/useThemePreset';

const { presets, selectedPreset, setPreset } = useThemePreset();
</script>

<template>
    <Select
        v-model="selectedPreset"
        :options="presets"
        option-label="label"
        option-value="value"
        @change="setPreset(selectedPreset)"
    />
</template>
