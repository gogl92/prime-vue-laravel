<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Head as InertiaHead, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Chart from 'primevue/chart';
import { Invoice } from '@/models/Invoice';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const breadcrumbs = [{ label: t('Dashboard') }];
const loading = ref<boolean>(false);

// Chart data
const chartData = ref();
const chartOptions = ref();

// Load invoice statistics
const loadInvoiceStats = async () => {
  try {
    loading.value = true;
    const invoices = await Invoice.$query().get();

    // Extract invoice amounts and format labels
    const invoiceData = invoices
      .map((invoice: Invoice) => ({
        amount: invoice.$attributes.import ?? 0,
        label: invoice.$attributes.sender_name ?? 'N/A' + '$' + (invoice.$attributes.import ?? 0).toFixed(2),
      }))
      .sort((a, b) => a.amount - b.amount) // Sort by amount for better visualization
      .slice(0, 20); // Limit to top 20 invoices for readability

    const labels = invoiceData.map(d => d.label);
    const amounts = invoiceData.map(d => d.amount);

    chartData.value = {
      labels: labels,
      datasets: [
        {
          label: t('Amount'),
          data: amounts,
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
          text: t('Invoice Distribution by Amount'),
        },
        tooltip: {
          backgroundColor: 'rgba(255, 255, 255, 0.95)',
          titleColor: '#000',
          bodyColor: '#000',
          borderColor: '#ddd',
          borderWidth: 1,
          padding: 12,
          boxPadding: 6,
          usePointStyle: true,
          pointStyle: 'circle',
          callbacks: {
            label: function(context: any) {
              let label = context.dataset.label || '';
              if (label) {
                label += ': ';
              }
              if (context.parsed.y !== null) {
                label += new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(context.parsed.y);
              }
              return label;
            }
          }
        }
      },
      scales: {
        x: {
          ticks: {
            maxRotation: 45,
            minRotation: 45,
          },
        },
        y: {
          beginAtZero: true,
          ticks: {
            callback: function(value: any) {
              return '$' + value.toLocaleString('es-MX');
            }
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
          <span>{{ t('Invoices Overview') }}</span>
          <Button
            :label="t('Manage Invoices')"
            icon="pi pi-arrow-right"
            icon-pos="right"
            @click="goToInvoices"
          />
        </div>
      </template>
      <template #content>
        <div v-if="loading" class="flex justify-center items-center h-[400px]">
          <i class="pi pi-spin pi-spinner text-4xl" />
        </div>
        <div v-else-if="chartData && chartOptions" class="h-[400px]">
          <Chart 
            type="bar" 
            :data="chartData" 
            :options="chartOptions" 
            :key="JSON.stringify(chartData)"
            style="height: 400px; width: 100%;"
          />
        </div>
        <div v-else class="flex justify-center items-center h-[400px]">
          <span class="text-gray-500">{{ t('No data available') }}</span>
        </div>
      </template>
    </Card>
  </AppLayout>
</template>
