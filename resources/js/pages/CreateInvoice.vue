<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue';
import { useToast } from 'primevue/usetoast';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Invoice } from '@/models/Invoice';
import { Product } from '@/models/Product';
import { Payment } from '@/models/Payment';
import { Client } from '@/models/Client';
import { useI18n } from 'vue-i18n';

// Components
import Card from 'primevue/card';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import Textarea from 'primevue/textarea';
import InputNumber from 'primevue/inputnumber';
import Calendar from 'primevue/calendar';
import Checkbox from 'primevue/checkbox';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Divider from 'primevue/divider';

// Toast & i18n
const { t } = useI18n();
const toast = useToast();

// State
const saving = ref(false);
const products = ref<Product[]>([]);
const loadingProducts = ref(false);
const clients = ref<Client[]>([]);
const loadingClients = ref(false);

// Invoice form
const invoiceForm = reactive({
  client_id: null as number | null,
  issuer_id: null as number | null,
  cfdi_type: 'Factura',
  order_number: '',
  invoice_date: new Date(),
  payment_form: 'Transferencia electrónica de fondos',
  send_email: true,
  payment_method: 'Pago en parcialidades o diferido',
  cfdi_use: 'Adquisición de mercancias',
  series: 'F',
  exchange_rate: 18.11,
  currency: 'MXN',
  comments: '',
});

// Products tab
const selectedProducts = ref<Array<{
  product: Product | null;
  quantity: number;
  price: number;
}>>([]);

// Payments tab
const payments = ref<Array<{
  amount: number;
  payment_method: string;
  transaction_id: string;
  status: string;
  notes: string;
  paid_at: Date | null;
}>>([]);

// Form options
const cfdiTypes = ref(['Factura', 'Nota de Crédito', 'Nota de Débito']);
const paymentForms = ref([
  'Transferencia electrónica de fondos',
  'Efectivo',
  'Cheque',
  'Tarjeta de crédito',
  'Tarjeta de débito'
]);
const paymentMethods = ref([
  'Pago en parcialidades o diferido',
  'Pago en una sola exhibición'
]);
const cfdiUses = ref([
  'Adquisición de mercancias',
  'Servicios profesionales',
  'Servicios de hospedaje',
  'Otros'
]);
const series = ref(['F', 'A', 'B', 'C']);
const currencies = ref(['MXN', 'USD', 'EUR']);
const paymentMethodOptions = ref([
  'credit_card',
  'debit_card',
  'paypal',
  'bank_transfer',
  'cash',
  'check'
]);
const statusOptions = ref(['pending', 'completed', 'failed', 'refunded']);

const errors = reactive<Record<string, string>>({});

// Computed properties
const issuerClients = computed(() => {
  return clients.value.filter(client => client.$attributes.is_issuer);
});

// Methods
const loadProducts = async () => {
  try {
    loadingProducts.value = true;
    products.value = await Product.$query().get();
  } catch (error) {
    console.error('Error loading products:', error);
    toast.add({
      severity: 'error',
      summary: t('Error'),
      detail: t('Failed to load products'),
      life: 3000,
    });
  } finally {
    loadingProducts.value = false;
  }
};

const loadClients = async () => {
  try {
    loadingClients.value = true;
    clients.value = await Client.$query().get();
  } catch (error) {
    console.error('Error loading clients:', error);
    toast.add({
      severity: 'error',
      summary: t('Error'),
      detail: t('Failed to load clients'),
      life: 3000,
    });
  } finally {
    loadingClients.value = false;
  }
};

const addProduct = () => {
  selectedProducts.value.push({
    product: null,
    quantity: 1,
    price: 0,
  });
};

const removeProduct = (index: number) => {
  selectedProducts.value.splice(index, 1);
};

const updateProductPrice = (index: number) => {
  const selectedProduct = selectedProducts.value[index];
  if (selectedProduct?.product) {
    selectedProduct.price = selectedProduct.product.$attributes.price;
  }
};

const addPayment = () => {
  payments.value.push({
    amount: 0,
    payment_method: 'credit_card',
    transaction_id: '',
    status: 'pending',
    notes: '',
    paid_at: null,
  });
};

const removePayment = (index: number) => {
  payments.value.splice(index, 1);
};

const calculateSubtotal = (): number => {
  return selectedProducts.value.reduce((total, item) => {
    return total + (item.quantity * item.price);
  }, 0);
};

const calculateTax = (): number => {
  const subtotal = calculateSubtotal();
  return subtotal * 0.16; // 16% IVA
};

const calculateTotal = (): number => {
  return calculateSubtotal() + calculateTax();
};

const calculateTotalPayments = (): number => {
  return payments.value.reduce((total, payment) => total + payment.amount, 0);
};

const saveInvoice = async () => {
  try {
    saving.value = true;
    clearErrors();

    // Validate required fields
    if (!invoiceForm.client_id) {
      toast.add({
        severity: 'error',
        summary: t('Error'),
        detail: t('Please select a client'),
        life: 3000,
      });
      return;
    }

    // Create invoice
    const invoiceDate = invoiceForm.invoice_date instanceof Date
      ? invoiceForm.invoice_date.toISOString().split('T')[0]
      : invoiceForm.invoice_date;

    const invoiceData = {
      client_id: invoiceForm.client_id,
      issuer_id: invoiceForm.issuer_id,
      cfdi_type: invoiceForm.cfdi_type,
      order_number: invoiceForm.order_number,
      invoice_date: invoiceDate ?? null,
      payment_form: invoiceForm.payment_form,
      send_email: invoiceForm.send_email,
      payment_method: invoiceForm.payment_method,
      cfdi_use: invoiceForm.cfdi_use,
      series: invoiceForm.series,
      exchange_rate: invoiceForm.exchange_rate,
      currency: invoiceForm.currency,
      comments: invoiceForm.comments,
    };

    const invoice = await Invoice.$query().store(invoiceData);

    const invoiceId = invoice.$attributes.id;
    if (!invoiceId) {
      throw new Error('Invoice ID is undefined');
    }

    // Attach products
    for (const productItem of selectedProducts.value) {
      if (productItem.product) {
        const productId = productItem.product.$attributes.id;
        if (productId) {
          await invoice.products().attachWithFields({
            [productId]: {
                quantity: productItem.quantity,
                price: productItem.price,
            }
          });
        }
      }
    }

    // Create payments
    for (const paymentData of payments.value) {
      const paidAt = paymentData.paid_at instanceof Date
        ? paymentData.paid_at.toISOString().split('T')[0]
        : paymentData.paid_at;

      await Payment.$query().store({
        invoice_id: invoiceId,
        amount: paymentData.amount,
        payment_method: paymentData.payment_method,
        transaction_id: paymentData.transaction_id,
        status: paymentData.status,
        notes: paymentData.notes,
        paid_at: paidAt ?? null,
      });
    }

    toast.add({
      severity: 'success',
      summary: t('Success'),
      detail: t('Invoice created successfully'),
      life: 3000,
    });

    router.visit('/invoices');
  } catch (error: unknown) {
    console.error('Error saving invoice:', error);

    const errorObj = error as {
      response?: { data?: { errors?: Record<string, string>; message?: string } };
    };
    if (errorObj.response?.data?.errors) {
      Object.assign(errors, errorObj.response.data.errors);
    }

    toast.add({
      severity: 'error',
      summary: t('Error'),
      detail: errorObj.response?.data?.message ?? t('Failed to save invoice'),
      life: 3000,
    });
  } finally {
    saving.value = false;
  }
};

const clearErrors = () => {
  Object.keys(errors).forEach(key => {
    delete errors[key as keyof typeof errors];
  });
};

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('es-MX', {
    style: 'currency',
    currency: 'MXN',
  }).format(amount);
};

// Lifecycle
onMounted(() => {
  void loadProducts();
  void loadClients();
});
</script>

<template>
  <AppLayout :title="t('Generar Factura')">
    <div class="space-y-6">
      <!-- Page Header -->
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-surface-900 dark:text-surface-0">
          {{ t('Generar Factura') }}
        </h1>
        <div class="flex gap-2">
          <Button
            :label="t('Cancelar')"
            severity="secondary"
            icon="pi pi-times"
            @click="router.visit('/invoices')"
          />
          <Button
            :label="t('Generar Pre Factura')"
            severity="success"
            icon="pi pi-check"
            :loading="saving"
            @click="saveInvoice"
          />
        </div>
      </div>

      <!-- Invoice Details Form -->
      <Card>
        <template #title>{{ t('Detalles de la Factura') }}</template>
        <template #content>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Left Column -->
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium mb-2">{{ t('Tipo de CFDI') }}</label>
                <Dropdown
                  v-model="invoiceForm.cfdi_type"
                  :options="cfdiTypes"
                  :placeholder="t('Seleccionar tipo')"
                />
              </div>
              <div>
                <label class="block text-sm font-medium mb-2">{{ t('Número de órden') }}</label>
                <InputText
                  v-model="invoiceForm.order_number"
                  :placeholder="t('Ingrese número de orden')"
                />
              </div>
              <div>
                <label class="block text-sm font-medium mb-2">{{ t('Fecha') }}</label>
                <Calendar
                  v-model="invoiceForm.invoice_date"
                  date-format="yy-mm-dd"
                  :placeholder="t('Seleccionar fecha')"
                />
              </div>
              <div>
                <label class="block text-sm font-medium mb-2">{{ t('Forma Pago') }}</label>
                <Dropdown
                  v-model="invoiceForm.payment_form"
                  :options="paymentForms"
                  :placeholder="t('Seleccionar forma de pago')"
                />
              </div>
            </div>

            <!-- Middle Column -->
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium mb-2">{{ t('Emisor') }}</label>
                <Dropdown
                  v-model="invoiceForm.issuer_id"
                  :options="issuerClients"
                  option-label="$attributes.name"
                  option-value="$attributes.id"
                  :placeholder="t('Seleccionar emisor')"
                  :loading="loadingClients"
                  :class="{ 'p-invalid': errors['issuer_id'] }"
                />
                <small v-if="errors['issuer_id']" class="text-red-500">{{ errors['issuer_id'] }}</small>
              </div>
              <div>
                <label class="block text-sm font-medium mb-2">{{ t('Cliente *') }}</label>
                <Dropdown
                  v-model="invoiceForm.client_id"
                  :options="clients"
                  option-label="$attributes.name"
                  option-value="$attributes.id"
                  :placeholder="t('Seleccionar cliente')"
                  :loading="loadingClients"
                  :class="{ 'p-invalid': errors['client_id'] }"
                  filter
                />
                <small v-if="errors['client_id']" class="text-red-500">{{ errors['client_id'] }}</small>
              </div>
              <div class="flex items-center gap-2">
                <Checkbox
                  v-model="invoiceForm.send_email"
                  binary
                />
                <label class="text-sm font-medium">{{ t('Enviar Correo') }}</label>
              </div>
              <div>
                <label class="block text-sm font-medium mb-2">{{ t('Método de pago') }}</label>
                <Dropdown
                  v-model="invoiceForm.payment_method"
                  :options="paymentMethods"
                  :placeholder="t('Seleccionar método de pago')"
                />
              </div>
            </div>

            <!-- Right Column -->
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium mb-2">{{ t('Uso del CFDI') }}</label>
                <Dropdown
                  v-model="invoiceForm.cfdi_use"
                  :options="cfdiUses"
                  :placeholder="t('Seleccionar uso')"
                />
              </div>
              <div>
                <label class="block text-sm font-medium mb-2">{{ t('Series') }}</label>
                <Dropdown
                  v-model="invoiceForm.series"
                  :options="series"
                  :placeholder="t('Seleccionar serie')"
                />
              </div>
              <div>
                <label class="block text-sm font-medium mb-2">{{ t('Tipo de Cambio') }}</label>
                <InputNumber
                  v-model="invoiceForm.exchange_rate"
                  mode="decimal"
                  :min-fraction-digits="2"
                  :max-fraction-digits="2"
                />
              </div>
              <div>
                <label class="block text-sm font-medium mb-2">{{ t('Moneda') }}</label>
                <Dropdown
                  v-model="invoiceForm.currency"
                  :options="currencies"
                  :placeholder="t('Seleccionar moneda')"
                />
              </div>
            </div>
          </div>

          <!-- Client Information Display -->
          <Divider />
          <div v-if="invoiceForm.client_id" class="mt-6">
            <h3 class="text-lg font-semibold mb-4">{{ t('Información del Cliente') }}</h3>
            <div class="p-4 bg-surface-50 dark:bg-surface-800 rounded-lg">
              <template v-for="client in clients" :key="client.$attributes.id">
                <div v-if="client.$attributes.id === invoiceForm.client_id" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <span class="font-medium">{{ t('Nombre:') }}</span>
                    <span class="ml-2">{{ client.$attributes.name }}</span>
                  </div>
                  <div>
                    <span class="font-medium">{{ t('Email:') }}</span>
                    <span class="ml-2">{{ client.$attributes.email }}</span>
                  </div>
                  <div>
                    <span class="font-medium">{{ t('Teléfono:') }}</span>
                    <span class="ml-2">{{ client.$attributes.phone }}</span>
                  </div>
                  <div v-if="client.$attributes.address">
                    <span class="font-medium">{{ t('Dirección:') }}</span>
                    <span class="ml-2">{{ client.$attributes.address }}</span>
                  </div>
                  <div v-if="client.$attributes.city">
                    <span class="font-medium">{{ t('Ciudad:') }}</span>
                    <span class="ml-2">{{ client.$attributes.city }}, {{ client.$attributes.state }} {{ client.$attributes.zip }}</span>
                  </div>
                  <div v-if="client.$attributes.country">
                    <span class="font-medium">{{ t('País:') }}</span>
                    <span class="ml-2">{{ client.$attributes.country }}</span>
                  </div>
                </div>
              </template>
            </div>
          </div>

          <!-- Comments Section -->
          <Divider />
          <div class="mt-6">
            <h3 class="text-lg font-semibold mb-4">{{ t('Comentarios') }}</h3>
            <Textarea
              v-model="invoiceForm.comments"
              :placeholder="t('Ingrese comentarios adicionales...')"
              rows="4"
              class="w-full"
            />
          </div>
        </template>
      </Card>

      <!-- Tabs for Related Models -->
      <Card>
        <template #content>
          <TabView>
            <!-- Products Tab -->
            <TabPanel :header="t('Conceptos')" value="concepts">
              <div class="space-y-4">
                <div class="flex justify-between items-center">
                  <h3 class="text-lg font-semibold">{{ t('Productos y Servicios') }}</h3>
                  <Button
                    :label="t('+ Agregar Concepto')"
                    icon="pi pi-plus"
                    severity="success"
                    @click="addProduct"
                  />
                </div>

                <DataTable
                  :value="selectedProducts"
                  :table-style="{ minWidth: '50rem' }"
                  class="p-datatable-sm"
                >
                  <Column field="#" :header="t('#')" style="width: 60px">
                    <template #body="{ index }">
                      {{ index + 1 }}
                    </template>
                  </Column>
                  <Column :header="t('NoIdentificacion')" style="width: 200px">
                    <template #body="{ data, index }">
                      <Dropdown
                        v-model="data.product"
                        :options="products"
                        option-label="$attributes.name"
                        :placeholder="t('Seleccionar producto')"
                        class="w-full"
                        @change="updateProductPrice(index)"
                      />
                    </template>
                  </Column>
                  <Column :header="t('Cantidad')" style="width: 120px">
                    <template #body="{ data }">
                      <InputNumber
                        v-model="data.quantity"
                        :min="1"
                        :max="9999"
                        class="w-full"
                      />
                    </template>
                  </Column>
                  <Column :header="t('Precio Unitario')" style="width: 150px">
                    <template #body="{ data }">
                      <InputNumber
                        v-model="data.price"
                        mode="currency"
                        currency="MXN"
                        locale="es-MX"
                        class="w-full"
                      />
                    </template>
                  </Column>
                  <Column :header="t('Total')" style="width: 150px">
                    <template #body="{ data }">
                      {{ formatCurrency(data.quantity * data.price) }}
                    </template>
                  </Column>
                  <Column :header="t('Acciones')" style="width: 100px">
                    <template #body="{ index }">
                      <Button
                        icon="pi pi-trash"
                        severity="danger"
                        text
                        size="small"
                        @click="removeProduct(index)"
                      />
                    </template>
                  </Column>
                </DataTable>

                <div v-if="selectedProducts.length === 0" class="text-center p-8 text-surface-500">
                  <i class="pi pi-box text-4xl mb-4 block" />
                  <p>{{ t('No se encontraron resultados.') }}</p>
                </div>
              </div>
            </TabPanel>

            <!-- Payments Tab -->
            <TabPanel :header="t('Pagos')" value="payments">
              <div class="space-y-4">
                <div class="flex justify-between items-center">
                  <h3 class="text-lg font-semibold">{{ t('Información de Pagos') }}</h3>
                  <Button
                    :label="t('+ Agregar Pago')"
                    icon="pi pi-plus"
                    severity="success"
                    @click="addPayment"
                  />
                </div>

                <div v-if="payments.length > 0" class="space-y-4">
                  <div
                    v-for="(payment, index) in payments"
                    :key="index"
                    class="p-4 border border-surface-200 dark:border-surface-700 rounded-lg"
                  >
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                      <div>
                        <label class="block text-sm font-medium mb-2">{{ t('Monto') }}</label>
                        <InputNumber
                          v-model="payment.amount"
                          mode="currency"
                          currency="MXN"
                          locale="es-MX"
                          class="w-full"
                        />
                      </div>
                      <div>
                        <label class="block text-sm font-medium mb-2">{{ t('Método de Pago') }}</label>
                        <Dropdown
                          v-model="payment.payment_method"
                          :options="paymentMethodOptions"
                          :placeholder="t('Seleccionar método')"
                          class="w-full"
                        />
                      </div>
                      <div>
                        <label class="block text-sm font-medium mb-2">{{ t('Estado') }}</label>
                        <Dropdown
                          v-model="payment.status"
                          :options="statusOptions"
                          :placeholder="t('Seleccionar estado')"
                          class="w-full"
                        />
                      </div>
                      <div>
                        <label class="block text-sm font-medium mb-2">{{ t('ID de Transacción') }}</label>
                        <InputText
                          v-model="payment.transaction_id"
                          :placeholder="t('Ingrese ID de transacción')"
                          class="w-full"
                        />
                      </div>
                      <div>
                        <label class="block text-sm font-medium mb-2">{{ t('Fecha de Pago') }}</label>
                        <Calendar
                          v-model="payment.paid_at"
                          date-format="yy-mm-dd"
                          :placeholder="t('Seleccionar fecha')"
                          class="w-full"
                        />
                      </div>
                      <div class="flex items-end">
                        <Button
                          icon="pi pi-trash"
                          severity="danger"
                          text
                          :label="t('Eliminar')"
                          @click="removePayment(index)"
                        />
                      </div>
                    </div>
                    <div class="mt-4">
                      <label class="block text-sm font-medium mb-2">{{ t('Notas') }}</label>
                      <Textarea
                        v-model="payment.notes"
                        :placeholder="t('Ingrese notas adicionales...')"
                        rows="2"
                        class="w-full"
                      />
                    </div>
                  </div>
                </div>

                <div v-else class="text-center p-8 text-surface-500">
                  <i class="pi pi-credit-card text-4xl mb-4 block" />
                  <p>{{ t('No hay pagos registrados.') }}</p>
                </div>
              </div>
            </TabPanel>
          </TabView>
        </template>
      </Card>

      <!-- Summary -->
      <Card>
        <template #content>
          <div class="flex justify-end">
            <div class="space-y-2 text-right">
              <div class="text-lg">
                <span class="font-semibold">{{ t('Subtotal:') }}</span>
                <span class="ml-4">{{ formatCurrency(calculateSubtotal()) }}</span>
              </div>
              <div class="text-lg">
                <span class="font-semibold">{{ t('IVA:') }}</span>
                <span class="ml-4">{{ formatCurrency(calculateTax()) }}</span>
              </div>
              <div class="text-2xl font-bold text-primary">
                <span>{{ t('Total:') }}</span>
                <span class="ml-4">{{ formatCurrency(calculateTotal()) }}</span>
              </div>
              <div v-if="payments.length > 0" class="text-lg">
                <span class="font-semibold">{{ t('Total Pagos:') }}</span>
                <span class="ml-4">{{ formatCurrency(calculateTotalPayments()) }}</span>
              </div>
            </div>
          </div>
        </template>
      </Card>
    </div>
  </AppLayout>
</template>
