<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Head as InertiaHead, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Chart from 'primevue/chart';
import { Invoice } from '@/models/Invoice';

const breadcrumbs = [{ label: 'Dashboard' }];
const loading = ref<boolean>(false);

// Chart data
const chartData = ref();
const chartOptions = ref();

// Load invoice statistics
const loadInvoiceStats = async () => {
    try {
        loading.value = true;
        const response = await Invoice.$query().get();

        // Group invoices by country
        const countryGroups = response.reduce((acc: Record<string, number>, invoice: Invoice) => {
            const country = invoice.$attributes.country ?? 'Unknown';
            acc[country] = (acc[country] ?? 0) + 1;
            return acc;
        }, {});

        // Prepare chart data
        const countries = Object.keys(countryGroups);
        const counts = Object.values(countryGroups);

        chartData.value = {
            labels: countries,
            datasets: [
                {
                    label: 'Invoices by Country',
                    data: counts,
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.2)',
                        'rgba(255, 99, 132, 0.2)',
                        'rgba(255, 206, 86, 0.2)',
                        'rgba(75, 192, 192, 0.2)',
                        'rgba(153, 102, 255, 0.2)',
                        'rgba(255, 159, 64, 0.2)',
                    ],
                    borderColor: [
                        'rgb(54, 162, 235)',
                        'rgb(255, 99, 132)',
                        'rgb(255, 206, 86)',
                        'rgb(75, 192, 192)',
                        'rgb(153, 102, 255)',
                        'rgb(255, 159, 64)',
                    ],
                    borderWidth: 1,
                },
            ],
        };

        chartOptions.value = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Invoice Distribution by Country',
                },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                    },
                },
            },
        };
    } catch (error) {
        console.error('Error loading invoice stats:', error);
    } finally {
        loading.value = false;
    }
};

const goToInvoices = () => {
    router.visit(route('invoices.example'));
};

onMounted(() => {
    void loadInvoiceStats();
});
</script>

<template>
    <InertiaHead title="Dashboard" />

    <AppLayout :breadcrumbs>
        <Card>
            <template #title>
                <div class="flex justify-between items-center">
                    <span>Invoices Overview</span>
                    <Button
                        label="Manage Invoices"
                        icon="pi pi-arrow-right"
                        icon-pos="right"
                        @click="goToInvoices"
                    />
                </div>
            </template>
            <template #content>
                <div
                    v-if="loading"
                    class="flex justify-center items-center h-[400px]"
                >
                    <i class="pi pi-spin pi-spinner text-4xl" />
                </div>
                <div
                    v-else
                    class="h-[400px]"
                >
                    <Chart
                        type="bar"
                        :data="chartData"
                        :options="chartOptions"
                        class="h-full"
                    />
                </div>
            </template>
        </Card>
    </AppLayout>
</template>
