<script setup lang="ts">
import { computed } from 'vue';
import { Building2 } from 'lucide-vue-next';
import { useBranchSelector } from '@/composables/useBranchSelector';

const {
  branches,
  currentBranch,
  currentBranchId,
  isLoading,
  formatBranchName,
  selectBranch,
} = useBranchSelector();

// Format branches for dropdown
const branchOptions = computed(() => {
  return branches.value.map(branch => ({
    label: formatBranchName(branch as any),
    value: (branch as any).id || branch.$attributes.id,
  }));
});

const selectedBranchId = computed({
  get: () => currentBranchId.value,
  set: (value: number | null) => {
    if (value !== null) {
      selectBranch(value);
    }
  },
});

// Get current branch display name
const currentBranchName = computed(() => {
  if (!currentBranch.value) return '';
  return formatBranchName(currentBranch.value as any);
});
</script>

<template>
  <div class="branch-selector">
    <!-- Show loading state -->
    <div v-if="isLoading" class="flex items-center gap-2 px-3 py-2">
      <Building2 class="size-4 animate-pulse" />
      <span class="text-sm">Loading branches...</span>
    </div>
    
    <!-- Show selector when branches are available -->
    <Select
      v-else-if="branches.length > 0"
      v-model="selectedBranchId"
      :options="branchOptions"
      option-label="label"
      option-value="value"
      :loading="isLoading"
      :disabled="isLoading"
      placeholder="Select Branch"
      class="w-full md:w-56"
      show-clear
    >
      <template #value="slotProps">
        <div v-if="slotProps.value" class="flex items-center gap-2">
          <Building2 class="size-4" />
          <span class="text-sm">{{ currentBranchName }}</span>
        </div>
        <div v-else class="flex items-center gap-2">
          <Building2 class="size-4" />
          <span class="text-sm">Select Branch</span>
        </div>
      </template>
      <template #option="slotProps">
        <div class="flex items-center gap-2">
          <Building2 class="size-4" />
          <span>{{ slotProps.option.label }}</span>
        </div>
      </template>
    </Select>
    
    <!-- Show message when no branches available (for debugging) -->
    <div v-else class="flex items-center gap-2 px-3 py-2 text-sm text-muted">
      <Building2 class="size-4" />
      <span>No branches available</span>
    </div>
  </div>
</template>

<style scoped>
.branch-selector {
  display: flex;
  align-items: center;
}
</style>

