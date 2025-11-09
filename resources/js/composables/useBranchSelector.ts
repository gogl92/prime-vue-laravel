import { ref, computed, onMounted } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import { Branch } from '@/models/Branch';
import { User } from '@/models/User';
import { FilterOperator } from '@tailflow/laravel-orion/lib/drivers/default/enums/filterOperator';
import { SortDirection } from '@tailflow/laravel-orion/lib/drivers/default/enums/sortDirection';

export function useBranchSelector() {
  const page = usePage();
  const branches = ref<Branch[]>([]);
  const currentBranchId = ref<number | null>(null);
  const isLoading = ref(false);
  const error = ref<string | null>(null);

  // Get current user from page props
  const currentUser = computed(() => page.props.auth?.user);

  // Get current branch from user data
  const currentBranch = computed(() => {
    if (!currentBranchId.value) return null;
    return branches.value.find(branch => {
      const branchId = (branch as any).id || branch.$attributes.id;
      return branchId === currentBranchId.value;
    }) || null;
  });

  // Format branch display name
  const formatBranchName = (branch: any): string => {
    const name = branch.name || branch.$attributes?.name || '';
    const code = branch.code || branch.$attributes?.code || null;
    
    if (code) {
      return `${name} (${code})`;
    }
    return name;
  };

  // Load available branches from API using Orion
  const loadBranches = async () => {
    isLoading.value = true;
    error.value = null;

    try {
      // Get the current user's company ID
      const companyId = currentUser.value?.current_company_id;

      console.log('[BranchSelector] Current user:', currentUser.value);
      console.log('[BranchSelector] Company ID:', companyId);

      if (!companyId) {
        console.warn('[BranchSelector] No company ID found for user');
        branches.value = [];
        currentBranchId.value = currentUser.value?.current_branch_id || null;
        return;
      }

      // Query branches using Orion
      const query = Branch.$query()
        .filter('company_id', FilterOperator.Equal, companyId)
        .filter('is_active', FilterOperator.Equal, true)
        .sortBy('name', SortDirection.Asc);

      console.log('[BranchSelector] Fetching branches for company:', companyId);

      // Execute the query
      const response = await query.search();

      console.log('[BranchSelector] Response:', response);

      // Handle response
      if (Array.isArray(response)) {
        branches.value = response as Branch[];
      } else if (response && typeof response === 'object' && 'data' in response) {
        branches.value = (response as { data: Branch[] }).data;
      } else {
        branches.value = [];
      }

      console.log('[BranchSelector] Loaded branches:', branches.value);

      // Set current branch ID from user data
      currentBranchId.value = currentUser.value?.current_branch_id || null;
      console.log('[BranchSelector] Current branch ID:', currentBranchId.value);
    } catch (err) {
      console.error('[BranchSelector] Error loading branches:', err);
      error.value = 'Failed to load branches';
      branches.value = [];
    } finally {
      isLoading.value = false;
    }
  };

  // Update current branch using Orion
  const selectBranch = async (branchId: number) => {
    if (branchId === currentBranchId.value) {
      return; // Already selected
    }

    isLoading.value = true;
    error.value = null;

    try {
      const userId = currentUser.value?.id;
      if (!userId) {
        throw new Error('User not authenticated');
      }

      // Verify that the branch belongs to the user's company
      const selectedBranch = branches.value.find(b => b.id === branchId);
      if (!selectedBranch) {
        throw new Error('Branch not found or does not belong to your company');
      }

      // Load the user using Orion
      const user = await User.$query().find(userId);
      
      // Update the user's current branch
      await user.$save({ current_branch_id: branchId });

      currentBranchId.value = branchId;

      // Reload the page to reflect changes across the application
      router.reload({
        only: ['auth'],
        preserveScroll: true,
      });
    } catch (err) {
      console.error('Error updating branch:', err);
      error.value = 'Failed to update branch';
    } finally {
      isLoading.value = false;
    }
  };

  // Load branches on mount
  onMounted(() => {
    loadBranches();
  });

  return {
    branches,
    currentBranch,
    currentBranchId,
    isLoading,
    error,
    formatBranchName,
    loadBranches,
    selectBranch,
  };
}

