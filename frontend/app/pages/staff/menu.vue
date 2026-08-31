<script setup lang="ts">
import type { MenuCategory } from '~/types/menu';

definePageMeta({ middleware: 'staff-auth', layout: 'staff', permissions: ['view_menu'] });

const { t } = useI18n();
const { can, canAny } = usePermissions();
const canManageMenu = computed(() => can('manage_menu'));
const canToggleAvailability = computed(() => canAny('manage_menu', 'edit_menu'));
const api = useApi();
const categories = ref<MenuCategory[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);

const searchQuery = ref('');

// If the search matches the category name itself, show all its items —
// otherwise narrow to items whose own name matches. Keeps a long menu
// navigable without hiding a whole section just because the query happened
// to match an item name instead of the category label.
const filteredCategories = computed(() => {
  const q = searchQuery.value.trim().toLowerCase();
  if (!q) return categories.value;
  return categories.value
    .map((category) => {
      const categoryMatches = category.name.toLowerCase().includes(q);
      return {
        ...category,
        items: categoryMatches ? category.items : category.items.filter((item) => item.name.toLowerCase().includes(q)),
      };
    })
    .filter((category) => category.name.toLowerCase().includes(q) || category.items.length > 0);
});

const newCategoryName = ref('');
const newItem = ref<{ categoryId: string; name: string; price: number | null; preparationTime: number }>({
  categoryId: '',
  name: '',
  price: null,
  preparationTime: 15,
});

async function loadMenu() {
  loading.value = true;
  error.value = null;
  try {
    categories.value = await api.get<MenuCategory[]>('/staff/menu');
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('staff.menu.loadFailed');
  } finally {
    loading.value = false;
  }
}

async function addCategory() {
  if (!newCategoryName.value.trim()) return;
  try {
    await api.post('/staff/menu/categories', { name: newCategoryName.value });
    newCategoryName.value = '';
    await loadMenu();
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('staff.menu.createCategoryFailed');
  }
}

async function removeCategory(id: string) {
  try {
    await api.del(`/staff/menu/categories/${id}`);
    await loadMenu();
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('staff.menu.deleteCategoryFailed');
  }
}

async function addItem() {
  if (!newItem.value.categoryId || !newItem.value.name.trim() || newItem.value.price == null) return;
  try {
    await api.post('/staff/menu/items', { ...newItem.value });
    newItem.value = { categoryId: newItem.value.categoryId, name: '', price: null, preparationTime: 15 };
    await loadMenu();
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('staff.menu.createItemFailed');
  }
}

async function toggleAvailability(itemId: string, isAvailable: boolean) {
  try {
    await api.patch(`/staff/menu/items/${itemId}/availability`, { isAvailable: !isAvailable });
    await loadMenu();
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('staff.menu.updateItemFailed');
  }
}

const uploadingItemId = ref<string | null>(null);

async function handleImageChange(itemId: string, event: Event) {
  const file = (event.target as HTMLInputElement).files?.[0];
  if (!file) return;
  uploadingItemId.value = itemId;
  try {
    await api.upload(`/staff/menu/items/${itemId}/image`, file);
    await loadMenu();
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('staff.menu.uploadImageFailed');
  } finally {
    uploadingItemId.value = null;
  }
}

const runtimeConfig = useRuntimeConfig();
function imageSrc(imageUrl: string | null): string | null {
  return resolveMediaUrl(imageUrl, runtimeConfig.public.apiBaseUrl as string);
}

const liveRefresh = useLiveRefresh('/staff/events', loadMenu, ['menu_updated', 'menu_availability']);
onMounted(() => {
  loadMenu();
  liveRefresh.start();
});
</script>

<template>
  <div>
    <StaffPageHeader :title="$t('staff.nav.menu')" :subtitle="$t('staff.pages.menuSub')" />

    <form v-if="canManageMenu" class="staff-card mb-5 flex flex-wrap items-end gap-3" @submit.prevent="addCategory">
      <div>
        <label class="mb-1 block text-xs font-medium text-ink-muted">{{ $t('staff.menu.newCategoryLabel') }}</label>
        <input v-model="newCategoryName" class="staff-input" :placeholder="$t('staff.menu.categoryPlaceholder')" />
      </div>
      <BaseButton type="submit">{{ $t('staff.menu.addCategory') }}</BaseButton>
    </form>

    <div v-if="categories.length > 0" class="mb-4">
      <input
        v-model="searchQuery"
        type="search"
        class="staff-input max-w-xs"
        :placeholder="$t('staff.menu.searchPlaceholder')"
      />
    </div>

    <p v-if="error" class="staff-error">{{ error }}</p>
    <div v-if="loading" class="staff-empty">{{ $t('staff.menu.loading') }}</div>
    <div v-else-if="categories.length === 0" class="staff-empty">{{ $t('staff.menu.noCategories') }}</div>
    <div v-else-if="filteredCategories.length === 0" class="staff-empty">{{ $t('staff.menu.noSearchResults') }}</div>

    <div v-else class="space-y-4">
      <section v-for="category in filteredCategories" :key="category.id" class="staff-card">
        <div class="mb-3 flex items-center justify-between gap-2">
          <h2 class="font-display font-bold text-ink">{{ category.name }}</h2>
          <BaseButton v-if="canManageMenu" variant="danger" @click="removeCategory(category.id)">{{ $t('staff.menu.deleteCategory') }}</BaseButton>
        </div>

        <ul class="mb-3 space-y-2">
          <li
            v-for="item in category.items"
            :key="item.id"
            class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-gray-100 px-3 py-2 text-sm"
          >
            <div class="flex items-center gap-2">
              <img v-if="imageSrc(item.imageUrl)" :src="imageSrc(item.imageUrl)!" alt="" class="h-8 w-8 rounded-lg object-cover" />
              <span class="text-ink">{{ item.name }} — {{ Number(item.price).toLocaleString() }}</span>
            </div>
            <div class="flex items-center gap-2">
              <label v-if="canManageMenu" class="cursor-pointer text-xs font-semibold text-brand hover:text-brand-dark">
                {{ uploadingItemId === item.id ? $t('staff.menu.uploading') : $t('staff.menu.uploadImage') }}
                <input type="file" accept="image/*" class="hidden" @change="handleImageChange(item.id, $event)" />
              </label>
              <BaseButton v-if="canToggleAvailability" variant="secondary" @click="toggleAvailability(item.id, item.isAvailable)">
                {{ item.isAvailable ? $t('staff.menu.markUnavailable') : $t('staff.menu.markAvailable') }}
              </BaseButton>
            </div>
          </li>
          <li v-if="category.items.length === 0" class="text-sm text-ink-muted">{{ $t('staff.menu.noItems') }}</li>
        </ul>

        <form
          v-if="canManageMenu"
          class="flex flex-wrap items-end gap-2"
          @submit.prevent="
            newItem.categoryId = category.id;
            addItem();
          "
        >
          <input v-model="newItem.name" class="staff-input max-w-xs" :placeholder="$t('staff.menu.itemNamePlaceholder')" />
          <input v-model.number="newItem.price" type="number" step="0.01" class="staff-input w-28" :placeholder="$t('staff.menu.pricePlaceholder')" />
          <BaseButton type="submit" variant="secondary">{{ $t('staff.menu.addItem') }}</BaseButton>
        </form>
      </section>
    </div>
  </div>
</template>
