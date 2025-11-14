<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import Card from 'primevue/card';
import Button from 'primevue/button';
import InputNumber from 'primevue/inputnumber';

const { t } = useI18n();

interface CartItem {
  id: number;
  type: 'product' | 'service';
  name: string;
  description?: string | null;
  price: number;
  quantity: number;
  duration?: number | null;
}

interface Props {
  items: CartItem[];
  loading?: boolean;
}

const props = defineProps<Props>();

const emit = defineEmits<{
  updateQuantity: [id: number, type: string, quantity: number];
  removeItem: [id: number, type: string];
  checkout: [];
}>();

// Calculate subtotal
const subtotal = computed(() => {
  return props.items.reduce((sum, item) => sum + item.price * item.quantity, 0);
});

// Format price
const formatPrice = (value: number) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
  }).format(value);
};

// Update quantity
const updateQuantity = (item: CartItem, quantity: number) => {
  if (quantity > 0) {
    emit('updateQuantity', item.id, item.type, quantity);
  }
};

// Remove item
const removeItem = (item: CartItem) => {
  emit('removeItem', item.id, item.type);
};

// Proceed to checkout
const proceedToCheckout = () => {
  emit('checkout');
};
</script>

<template>
  <Card>
    <template #title>
      <div class="flex items-center gap-2">
        <i class="pi pi-shopping-cart" />
        <span>{{ t('Shopping Cart') }}</span>
        <span v-if="items.length > 0" class="text-sm font-normal text-gray-500">
          ({{ items.length }} {{ items.length === 1 ? t('item') : t('items') }})
        </span>
      </div>
    </template>

    <template #content>
      <div v-if="items.length === 0" class="text-center py-8 text-gray-500">
        <i class="pi pi-shopping-cart text-4xl mb-4 block" />
        <p>{{ t('Your cart is empty') }}</p>
      </div>

      <div v-else class="space-y-4">
        <div
          v-for="item in items"
          :key="`${item.type}-${item.id}`"
          class="flex gap-4 p-4 border rounded-lg"
        >
          <div class="flex-1">
            <h3 class="font-semibold">{{ item.name }}</h3>
            <p v-if="item.description" class="text-sm text-gray-600 mt-1">
              {{ item.description }}
            </p>
            <p v-if="item.type === 'service' && item.duration" class="text-sm text-gray-500 mt-1">
              {{ t('Duration') }}: {{ item.duration }} {{ t('minutes') }}
            </p>
            <div class="mt-2">
              <span class="text-lg font-semibold">{{ formatPrice(item.price) }}</span>
              <span class="text-sm text-gray-500"> × {{ item.quantity }}</span>
            </div>
          </div>

          <div class="flex flex-col justify-between items-end">
            <Button
              icon="pi pi-trash"
              severity="danger"
              text
              rounded
              size="small"
              @click="removeItem(item)"
            />
            <div class="flex items-center gap-2">
              <InputNumber
                :model-value="item.quantity"
                :min="1"
                :max="99"
                show-buttons
                button-layout="horizontal"
                :disabled="loading"
                @update:model-value="(value) => updateQuantity(item, value as number)"
              >
                <template #incrementbuttonicon>
                  <span class="pi pi-plus" />
                </template>
                <template #decrementbuttonicon>
                  <span class="pi pi-minus" />
                </template>
              </InputNumber>
            </div>
            <div class="text-right">
              <span class="text-lg font-bold">{{ formatPrice(item.price * item.quantity) }}</span>
            </div>
          </div>
        </div>

        <div class="border-t pt-4">
          <div class="flex justify-between items-center mb-4">
            <span class="text-lg font-semibold">{{ t('Subtotal') }}:</span>
            <span class="text-2xl font-bold">{{ formatPrice(subtotal) }}</span>
          </div>

          <Button
            :label="t('Proceed to Checkout')"
            icon="pi pi-arrow-right"
            icon-pos="right"
            class="w-full"
            size="large"
            :disabled="loading || items.length === 0"
            @click="proceedToCheckout"
          />
        </div>
      </div>
    </template>
  </Card>
</template>

