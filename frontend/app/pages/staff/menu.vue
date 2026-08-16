<script setup lang="ts">
import type { MenuCategory } from '~/types/menu';

definePageMeta({ middleware: 'staff-auth', layout: 'staff' });

const api = useApi();
const categories = ref<MenuCategory[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);

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
    error.value = e instanceof Error ? e.message : 'Failed to load menu';
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
    error.value = e instanceof Error ? e.message : 'Failed to create category';
  }
}

async function removeCategory(id: string) {
  try {
    await api.del(`/staff/menu/categories/${id}`);
    await loadMenu();
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Failed to delete category';
  }
}

async function addItem() {
  if (!newItem.value.categoryId || !newItem.value.name.trim() || newItem.value.price == null) return;
  try {
    await api.post('/staff/menu/items', { ...newItem.value });
    newItem.value = { categoryId: newItem.value.categoryId, name: '', price: null, preparationTime: 15 };
    await loadMenu();
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Failed to create item';
  }
}

async function toggleAvailability(itemId: string, isAvailable: boolean) {
  try {
    await api.patch(`/staff/menu/items/${itemId}/availability`, { isAvailable: !isAvailable });
    await loadMenu();
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Failed to update item';
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
    error.value = e instanceof Error ? e.message : 'Failed to upload image';
  } finally {
    uploadingItemId.value = null;
  }
}

const runtimeConfig = useRuntimeConfig();
function imageSrc(imageUrl: string | null): string | null {
  if (!imageUrl) return null;
  // imageUrl is a backend-relative path (e.g. /uploads/menu/...) — resolve
  // against the API origin, not the frontend's, since Express serves it.
  const apiOrigin = new URL(runtimeConfig.public.apiBaseUrl).origin;
  return `${apiOrigin}${imageUrl}`;
}

onMounted(loadMenu);
</script>

<template>
  <div class="p-6">
    <h1 class="mb-4 text-xl font-semibold text-gray-900">Menu</h1>

    <form class="mb-6 flex items-end gap-2" @submit.prevent="addCategory">
      <div>
        <label class="block text-xs text-gray-500">New category</label>
        <input v-model="newCategoryName" class="rounded-md border border-gray-300 px-2 py-1.5 text-sm" placeholder="Starters" />
      </div>
      <BaseButton type="submit">Add category</BaseButton>
    </form>

    <p v-if="error" class="mb-4 text-sm text-red-600">{{ error }}</p>
    <p v-if="loading" class="text-sm text-gray-500">Loading…</p>

    <div v-else class="space-y-6">
      <section v-for="category in categories" :key="category.id" class="rounded-lg border border-gray-200 bg-white p-4">
        <div class="mb-3 flex items-center justify-between">
          <h2 class="font-semibold text-gray-900">{{ category.name }}</h2>
          <BaseButton variant="danger" @click="removeCategory(category.id)">Delete category</BaseButton>
        </div>

        <ul class="mb-3 space-y-2">
          <li v-for="item in category.items" :key="item.id" class="flex items-center justify-between rounded border border-gray-100 p-2 text-sm">
            <div class="flex items-center gap-2">
              <img v-if="imageSrc(item.imageUrl)" :src="imageSrc(item.imageUrl)!" alt="" class="h-8 w-8 rounded object-cover" />
              <span>{{ item.name }} — {{ Number(item.price).toLocaleString() }}</span>
            </div>
            <div class="flex items-center gap-2">
              <label class="cursor-pointer text-xs text-blue-600 hover:underline">
                {{ uploadingItemId === item.id ? 'Uploading…' : 'Upload image' }}
                <input type="file" accept="image/*" class="hidden" @change="handleImageChange(item.id, $event)" />
              </label>
              <BaseButton variant="secondary" @click="toggleAvailability(item.id, item.isAvailable)">
                {{ item.isAvailable ? 'Mark unavailable' : 'Mark available' }}
              </BaseButton>
            </div>
          </li>
          <li v-if="category.items.length === 0" class="text-sm text-gray-400">No items yet.</li>
        </ul>

        <form
          class="flex flex-wrap items-end gap-2"
          @submit.prevent="
            newItem.categoryId = category.id;
            addItem();
          "
        >
          <input v-model="newItem.name" class="rounded-md border border-gray-300 px-2 py-1.5 text-sm" placeholder="Item name" />
          <input v-model.number="newItem.price" type="number" step="0.01" class="w-28 rounded-md border border-gray-300 px-2 py-1.5 text-sm" placeholder="Price" />
          <BaseButton type="submit" variant="secondary">Add item</BaseButton>
        </form>
      </section>
    </div>
  </div>
</template>
