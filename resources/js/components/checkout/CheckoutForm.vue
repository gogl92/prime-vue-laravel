<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import Card from 'primevue/card';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Message from 'primevue/message';
import { loadStripe, Stripe, StripeElements, StripeCardElement } from '@stripe/stripe-js';

const { t } = useI18n();

interface Props {
  clientSecret: string;
  totalAmount: number;
  loading?: boolean;
}

const props = defineProps<Props>();

const emit = defineEmits<{
  success: [orderId: number, orderNumber: string];
  error: [message: string];
  back: [];
}>();

// Form state
const customerName = ref('');
const customerEmail = ref('');
const customerPhone = ref('');
const customerNotes = ref('');
const termsAccepted = ref(false);

// Stripe state
const stripe = ref<Stripe | null>(null);
const elements = ref<StripeElements | null>(null);
const cardElement = ref<StripeCardElement | null>(null);
const stripeLoading = ref(true);
const processing = ref(false);
const errorMessage = ref('');

// Form validation
const isFormValid = computed(() => {
  return (
    customerName.value.trim() !== '' &&
    customerEmail.value.trim() !== '' &&
    /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(customerEmail.value) &&
    termsAccepted.value
  );
});

// Format price
const formatPrice = (value: number) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
  }).format(value);
};

// Initialize Stripe
onMounted(async () => {
  try {
    stripeLoading.value = true;
    
    // Load Stripe - use your publishable key
    stripe.value = await loadStripe(import.meta.env.VITE_STRIPE_KEY || '');
    
    if (!stripe.value) {
      throw new Error('Failed to load Stripe');
    }

    // Create card element
    elements.value = stripe.value.elements({
      clientSecret: props.clientSecret,
    });

    cardElement.value = elements.value.create('card', {
      style: {
        base: {
          fontSize: '16px',
          color: '#32325d',
          '::placeholder': {
            color: '#aab7c4',
          },
        },
        invalid: {
          color: '#fa755a',
        },
      },
    });

    cardElement.value.mount('#card-element');

    cardElement.value.on('change', (event) => {
      if (event.error) {
        errorMessage.value = event.error.message;
      } else {
        errorMessage.value = '';
      }
    });

    stripeLoading.value = false;
  } catch (error) {
    console.error('Error initializing Stripe:', error);
    errorMessage.value = t('Failed to initialize payment system');
    stripeLoading.value = false;
  }
});

// Submit payment
const submitPayment = async () => {
  if (!isFormValid.value || !stripe.value || !cardElement.value) {
    return;
  }

  try {
    processing.value = true;
    errorMessage.value = '';

    // Confirm payment
    const { error, paymentIntent } = await stripe.value.confirmCardPayment(
      props.clientSecret,
      {
        payment_method: {
          card: cardElement.value,
          billing_details: {
            name: customerName.value,
            email: customerEmail.value,
            phone: customerPhone.value || undefined,
          },
        },
      }
    );

    if (error) {
      errorMessage.value = error.message || t('Payment failed');
      emit('error', errorMessage.value);
    } else if (paymentIntent && paymentIntent.status === 'succeeded') {
      emit('success', 0, ''); // Order details will be handled by parent
    }
  } catch (error) {
    console.error('Payment error:', error);
    errorMessage.value = t('An unexpected error occurred');
    emit('error', errorMessage.value);
  } finally {
    processing.value = false;
  }
};

// Go back
const goBack = () => {
  emit('back');
};
</script>

<template>
  <Card>
    <template #title>
      <div class="flex items-center gap-2">
        <i class="pi pi-credit-card" />
        <span>{{ t('Payment Information') }}</span>
      </div>
    </template>

    <template #content>
      <div class="space-y-4">
        <!-- Order Summary -->
        <div class="p-4 bg-gray-50 rounded-lg">
          <div class="flex justify-between items-center">
            <span class="font-semibold">{{ t('Total Amount') }}:</span>
            <span class="text-2xl font-bold">{{ formatPrice(totalAmount) }}</span>
          </div>
        </div>

        <!-- Error Message -->
        <Message v-if="errorMessage" severity="error" :closable="false">
          {{ errorMessage }}
        </Message>

        <!-- Customer Information -->
        <div class="space-y-3">
          <h3 class="font-semibold">{{ t('Contact Information') }}</h3>

          <div class="field">
            <label for="name" class="block mb-2">{{ t('Full Name') }} *</label>
            <InputText
              id="name"
              v-model="customerName"
              :placeholder="t('Enter your full name')"
              class="w-full"
              :disabled="processing"
            />
          </div>

          <div class="field">
            <label for="email" class="block mb-2">{{ t('Email Address') }} *</label>
            <InputText
              id="email"
              v-model="customerEmail"
              type="email"
              :placeholder="t('Enter your email')"
              class="w-full"
              :disabled="processing"
            />
          </div>

          <div class="field">
            <label for="phone" class="block mb-2">{{ t('Phone Number') }}</label>
            <InputText
              id="phone"
              v-model="customerPhone"
              type="tel"
              :placeholder="t('Enter your phone number')"
              class="w-full"
              :disabled="processing"
            />
          </div>

          <div class="field">
            <label for="notes" class="block mb-2">{{ t('Order Notes') }}</label>
            <Textarea
              id="notes"
              v-model="customerNotes"
              :placeholder="t('Any special instructions or notes')"
              rows="3"
              class="w-full"
              :disabled="processing"
            />
          </div>
        </div>

        <!-- Card Information -->
        <div class="space-y-3">
          <h3 class="font-semibold">{{ t('Card Information') }}</h3>

          <div class="field">
            <label class="block mb-2">{{ t('Card Details') }} *</label>
            <div
              id="card-element"
              class="p-3 border rounded-lg"
              :class="{ 'opacity-50': stripeLoading || processing }"
            ></div>
            <small class="text-gray-500">{{ t('Powered by Stripe - Secure payment processing') }}</small>
          </div>
        </div>

        <!-- Terms -->
        <div class="field flex items-start gap-2">
          <input
            id="terms"
            v-model="termsAccepted"
            type="checkbox"
            class="mt-1 h-4 w-4"
            :disabled="processing"
          />
          <label for="terms" class="text-sm">
            {{ t('I agree to the terms and conditions') }} *
          </label>
        </div>

        <!-- Actions -->
        <div class="flex gap-2">
          <Button
            :label="t('Back to Cart')"
            icon="pi pi-arrow-left"
            severity="secondary"
            outlined
            class="flex-1"
            :disabled="processing"
            @click="goBack"
          />
          <Button
            :label="processing ? t('Processing...') : t('Pay Now')"
            icon="pi pi-check"
            icon-pos="right"
            class="flex-1"
            :disabled="!isFormValid || stripeLoading || processing"
            :loading="processing"
            @click="submitPayment"
          />
        </div>
      </div>
    </template>
  </Card>
</template>

<style scoped>
#card-element {
  min-height: 40px;
}
</style>

