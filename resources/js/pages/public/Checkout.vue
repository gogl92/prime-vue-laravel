<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import Card from 'primevue/card';
import Button from 'primevue/button';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import Message from 'primevue/message';
import ProgressSpinner from 'primevue/progressspinner';
import { useToast } from 'primevue/usetoast';
import ShoppingCart from '@/components/checkout/ShoppingCart.vue';
import CheckoutForm from '@/components/checkout/CheckoutForm.vue';
import axios from 'axios';

// Props (received from Inertia)
interface Props {
  slug: string;
}

const props = defineProps<Props>();

const { t } = useI18n();
const toast = useToast();

// State
const loading = ref(true);
const gatewayInfo = ref<any>(null);
const products = ref<any[]>([]);
const services = ref<any[]>([]);
const cartItems = ref<any[]>([]);
const checkoutStep = ref<'browse' | 'payment' | 'success'>('browse');
const clientSecret = ref('');
const orderId = ref<number | null>(null);
const orderNumber = ref('');

// Cart count
const cartCount = computed(() => {
  return cartItems.value.reduce((sum, item) => sum + item.quantity, 0);
});

// Load gateway info
const loadGatewayInfo = async () => {
  try {
    loading.value = true;
    const response = await axios.get(`/api/checkout/${props.slug}/info`);
    gatewayInfo.value = response.data.gateway;
    products.value = response.data.items.products || [];
    services.value = response.data.items.services || [];

    // Apply custom styles
    if (gatewayInfo.value.primary_color) {
      document.documentElement.style.setProperty('--primary-color', gatewayInfo.value.primary_color);
    }
    if (gatewayInfo.value.secondary_color) {
      document.documentElement.style.setProperty('--secondary-color', gatewayInfo.value.secondary_color);
    }
  } catch (error: any) {
    console.error('Error loading gateway:', error);
    toast.add({
      severity: 'error',
      summary: t('Error'),
      detail: error.response?.data?.message || t('Failed to load checkout page'),
      life: 5000,
    });
  } finally {
    loading.value = false;
  }
};

// Add to cart
const addToCart = (item: any) => {
  const existingItem = cartItems.value.find(
    (ci) => ci.id === item.id && ci.type === item.type
  );

  if (existingItem) {
    existingItem.quantity++;
  } else {
    cartItems.value.push({
      ...item,
      quantity: 1,
    });
  }

  toast.add({
    severity: 'success',
    summary: t('Added to Cart'),
    detail: `${item.name} ${t('added to cart')}`,
    life: 2000,
  });
};

// Buy now (skip cart)
const buyNow = (item: any) => {
  cartItems.value = [{
    ...item,
    quantity: 1,
  }];
  proceedToCheckout();
};

// Update quantity
const updateQuantity = (id: number, type: string, quantity: number) => {
  const item = cartItems.value.find((ci) => ci.id === id && ci.type === type);
  if (item) {
    item.quantity = quantity;
  }
};

// Remove from cart
const removeFromCart = (id: number, type: string) => {
  cartItems.value = cartItems.value.filter(
    (item) => !(item.id === id && item.type === type)
  );
  toast.add({
    severity: 'info',
    summary: t('Removed'),
    detail: t('Item removed from cart'),
    life: 2000,
  });
};

// Proceed to checkout
const proceedToCheckout = async () => {
  try {
    loading.value = true;

    const items = cartItems.value.map((item) => ({
      type: item.type,
      id: item.id,
      quantity: item.quantity,
    }));

    // For now, use placeholder customer info - will be collected in checkout form
    const response = await axios.post(`/api/checkout/${props.slug}/payment-intent`, {
      items,
      customer_email: 'temp@example.com', // Will be updated during payment
      customer_name: 'Temporary', // Will be updated during payment
    });

    clientSecret.value = response.data.client_secret;
    orderId.value = response.data.order_id;
    orderNumber.value = response.data.order_number;
    checkoutStep.value = 'payment';
  } catch (error: any) {
    console.error('Error creating payment intent:', error);
    toast.add({
      severity: 'error',
      summary: t('Error'),
      detail: error.response?.data?.message || t('Failed to create payment intent'),
      life: 5000,
    });
  } finally {
    loading.value = false;
  }
};

// Handle payment success
const handlePaymentSuccess = async () => {
  try {
    loading.value = true;

    // Confirm order
    await axios.post(`/api/checkout/${props.slug}/confirm`, {
      order_id: orderId.value,
      payment_intent_id: clientSecret.value.split('_secret_')[0],
    });

    checkoutStep.value = 'success';
    cartItems.value = [];
  } catch (error: any) {
    console.error('Error confirming order:', error);
    toast.add({
      severity: 'error',
      summary: t('Error'),
      detail: error.response?.data?.message || t('Failed to confirm order'),
      life: 5000,
    });
  } finally {
    loading.value = false;
  }
};

// Handle payment error
const handlePaymentError = (message: string) => {
  toast.add({
    severity: 'error',
    summary: t('Payment Failed'),
    detail: message,
    life: 5000,
  });
};

// Back to cart
const backToCart = () => {
  checkoutStep.value = 'browse';
};

// Format price
const formatPrice = (value: number) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
  }).format(value);
};

onMounted(async () => {
  await loadGatewayInfo();
});
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
      <div class="container mx-auto px-4 py-4">
        <div class="flex justify-between items-center">
          <div class="flex items-center gap-4">
            <img
              v-if="gatewayInfo?.logo_url"
              :src="gatewayInfo.logo_url"
              :alt="gatewayInfo.business_name"
              class="h-12"
            />
            <h1 class="text-2xl font-bold">
              {{ gatewayInfo?.business_name || t('Online Store') }}
            </h1>
          </div>
          <div v-if="checkoutStep === 'browse'" class="flex items-center gap-2">
            <i class="pi pi-shopping-cart text-xl" />
            <span class="font-semibold">{{ cartCount }}</span>
          </div>
        </div>
      </div>
    </header>

    <!-- Loading State -->
    <div v-if="loading && !gatewayInfo" class="container mx-auto px-4 py-12 text-center">
      <ProgressSpinner />
      <p class="mt-4 text-gray-600">{{ t('Loading checkout...') }}</p>
    </div>

    <!-- Main Content -->
    <div v-else-if="gatewayInfo" class="container mx-auto px-4 py-8">
      <!-- Browse Products/Services -->
      <div v-if="checkoutStep === 'browse'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
          <Card>
            <template #content>
              <TabView>
                <TabPanel :header="t('Products')">
                  <div v-if="products.length === 0" class="text-center py-8 text-gray-500">
                    <p>{{ t('No products available') }}</p>
                  </div>
                  <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <Card v-for="product in products" :key="`product-${product.id}`">
                      <template #title>
                        {{ product.name }}
                      </template>
                      <template #content>
                        <p class="text-gray-600 text-sm mb-4">
                          {{ product.description || '-' }}
                        </p>
                        <div class="flex justify-between items-center">
                          <span class="text-2xl font-bold">{{ formatPrice(product.price) }}</span>
                          <div class="flex gap-2">
                            <Button
                              :label="t('Add to Cart')"
                              icon="pi pi-shopping-cart"
                              size="small"
                              outlined
                              @click="addToCart(product)"
                            />
                            <Button
                              :label="t('Buy Now')"
                              icon="pi pi-bolt"
                              size="small"
                              @click="buyNow(product)"
                            />
                          </div>
                        </div>
                      </template>
                    </Card>
                  </div>
                </TabPanel>

                <TabPanel :header="t('Services')">
                  <div v-if="services.length === 0" class="text-center py-8 text-gray-500">
                    <p>{{ t('No services available') }}</p>
                  </div>
                  <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <Card v-for="service in services" :key="`service-${service.id}`">
                      <template #title>
                        {{ service.name }}
                      </template>
                      <template #content>
                        <p class="text-gray-600 text-sm mb-2">
                          {{ service.description || '-' }}
                        </p>
                        <p v-if="service.duration" class="text-sm text-gray-500 mb-4">
                          <i class="pi pi-clock mr-1" />
                          {{ service.duration }} {{ t('minutes') }}
                        </p>
                        <div class="flex justify-between items-center">
                          <span class="text-2xl font-bold">{{ formatPrice(service.price) }}</span>
                          <div class="flex gap-2">
                            <Button
                              :label="t('Add to Cart')"
                              icon="pi pi-shopping-cart"
                              size="small"
                              outlined
                              @click="addToCart(service)"
                            />
                            <Button
                              :label="t('Buy Now')"
                              icon="pi pi-bolt"
                              size="small"
                              @click="buyNow(service)"
                            />
                          </div>
                        </div>
                      </template>
                    </Card>
                  </div>
                </TabPanel>
              </TabView>
            </template>
          </Card>

          <!-- Terms and Conditions -->
          <Card v-if="gatewayInfo.terms_and_conditions" class="mt-6">
            <template #title>
              {{ t('Terms and Conditions') }}
            </template>
            <template #content>
              <p class="text-sm text-gray-600 whitespace-pre-wrap">
                {{ gatewayInfo.terms_and_conditions }}
              </p>
            </template>
          </Card>
        </div>

        <div class="lg:col-span-1">
          <ShoppingCart
            :items="cartItems"
            :loading="loading"
            @update-quantity="updateQuantity"
            @remove-item="removeFromCart"
            @checkout="proceedToCheckout"
          />
        </div>
      </div>

      <!-- Payment Step -->
      <div v-else-if="checkoutStep === 'payment'" class="max-w-2xl mx-auto">
        <CheckoutForm
          :client-secret="clientSecret"
          :total-amount="cartItems.reduce((sum, item) => sum + item.price * item.quantity, 0)"
          :loading="loading"
          @success="handlePaymentSuccess"
          @error="handlePaymentError"
          @back="backToCart"
        />
      </div>

      <!-- Success Step -->
      <div v-else-if="checkoutStep === 'success'" class="max-w-2xl mx-auto">
        <Card>
          <template #content>
            <div class="text-center py-8">
              <i class="pi pi-check-circle text-6xl text-green-500 mb-4 block" />
              <h2 class="text-3xl font-bold mb-4">{{ t('Payment Successful!') }}</h2>
              <p class="text-gray-600 mb-4">
                {{ gatewayInfo.success_message || t('Thank you for your purchase!') }}
              </p>
              <p class="text-sm text-gray-500 mb-6">
                {{ t('Order Number') }}: <strong>{{ orderNumber }}</strong>
              </p>
              <Button
                :label="t('Continue Shopping')"
                icon="pi pi-shopping-cart"
                @click="checkoutStep = 'browse'"
              />
            </div>
          </template>
        </Card>
      </div>
    </div>

    <!-- Error State -->
    <div v-else class="container mx-auto px-4 py-12">
      <Message severity="error" :closable="false">
        {{ t('Payment gateway not found or is disabled') }}
      </Message>
    </div>

    <!-- Footer -->
    <footer class="bg-white border-t mt-12 py-6">
      <div class="container mx-auto px-4 text-center text-gray-600 text-sm">
        <p>{{ t('Powered by Stripe • Secure Payment Processing') }}</p>
      </div>
    </footer>
  </div>
</template>

<style scoped>
:root {
  --primary-color: #3b82f6;
  --secondary-color: #1e40af;
}
</style>

