<script setup lang="ts">
import { ref } from 'vue';
import { Head as InertiaHead } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Knob from 'primevue/knob';
import Card from 'primevue/card';
import Button from 'primevue/button';
const breadcrumbs = [{ label: 'Dashboard' }];
const value = ref(0);
</script>

<template>
    <InertiaHead title="Dashboard" />

    <AppLayout :breadcrumbs>        
        <Card>
            <template #content>
                    <div class="card flex flex-col items-center gap-2">
                        <Knob v-model="value" :size="150" readonly />
                        <div class="flex gap-2">
                            <Button icon="pi pi-plus" @click="value++" :disabled="value >= 100" />
                            <Button icon="pi pi-minus" @click="value--" :disabled="value <= 0" />
                        </div>
                    </div>
            </template>
        </Card>
    </AppLayout>
</template>
