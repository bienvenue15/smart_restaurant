<script setup lang="ts">
import type { MenuCategory, MenuItem, OrderSummary } from '~/types/menu';

const route = useRoute();
const qr = route.params.qr as string;
const { t } = useI18n();

const api = useApi();
const toast = useToast();
const session = useCustomerSession();
const tableId = computed(() => session.table.value?.id ?? null);
const { cart, addItem, updateQuantity, clear, total, itemCount } = useCart(tableId);
const tracking = useOrderTracking(tableId);
const runtimeConfig = useRuntimeConfig();
const restaurantLogo = computed(() =>
  resolveMediaUrl(session.restaurant.value?.logoUrl, runtimeConfig.public.apiBaseUrl as string),
);
const restaurantInitial = computed(() => (session.restaurant.value?.name ?? '').trim().charAt(0).toUpperCase() || 'R');
const accent = computed(() => session.restaurant.value?.primaryColor || '#0d3b2e');

const loading = ref(true);
const error = ref<string | null>(null);
const categories = ref<MenuCategory[]>([]);
const searchQuery = ref('');
const activeCategoryId = ref<string | null>(null);
const view = ref<'menu' | 'order' | 'track'>('menu');
const placing = ref(false);
const placedOrder = ref<OrderSummary | null>(null);
const placedOrderWasAddition = ref(false);

const filteredCategories = computed(() => {
  const q = searchQuery.value.trim().toLowerCase();
  return categories.value
    .filter((category) => activeCategoryId.value === null || category.id === activeCategoryId.value)
    .map((category) => ({
      ...category,
      items: q
        ? category.items.filter(
            (item) => item.name.toLowerCase().includes(q) || (item.description ?? '').toLowerCase().includes(q),
          )
        : category.items,
    }))
    .filter((category) => category.items.length > 0);
});

const addableOrder = computed(() => tracking.orders.value.find((o) => o.status === 'PENDING' || o.status === 'CONFIRMED') ?? null);

const activeTrackOrder = computed(
  () =>
    tracking.orders.value.find((o) => ['PENDING', 'CONFIRMED', 'PREPARING', 'READY', 'SERVED'].includes(o.status)) ??
    tracking.orders.value[0] ??
    null,
);

async function loadMenu() {
  try {
    categories.value = await api.get<MenuCategory[]>('/customer/menu');
  } catch {
    // Non-critical — the guest keeps seeing the last-known menu until a poll/SSE tick succeeds.
  }
}

const menuLive = useLiveRefresh('/customer/events', loadMenu, ['menu_updated']);

async function init() {
  loading.value = true;
  error.value = null;
  try {
    await session.scan(qr);
    await loadMenu();
    tracking.start();
    menuLive.start();
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('customer.unableToLoad');
  } finally {
    loading.value = false;
  }
}

async function handleCancelOrder(orderId: string) {
  try {
    await tracking.cancelOrder(orderId);
    toast.success(t('customer.orderCancelled'));
  } catch {
    // Failure popup is shown by useApi.
  }
}

async function handleWaiterCall(requestType: 'ASSISTANCE' | 'BILL') {
  try {
    await api.post(
      '/customer/waiter-calls',
      { requestType },
      {
        successMessage:
          requestType === 'BILL' ? t('customer.billRequested') : t('customer.waiterOnTheWay'),
      },
    );
  } catch {
    // Failure popup is shown by useApi — keep the menu on screen.
  }
}

function handleAdd(item: MenuItem) {
  addItem({ id: item.id, name: item.name, price: Number(item.price) });
  toast.success(t('customer.addedToCart', { name: item.name }));
}

const orderChoiceOpen = ref(false);

function placeOrder() {
  if (!session.table.value) return;
  if (addableOrder.value) {
    orderChoiceOpen.value = true;
    return;
  }
  void submitOrder(false);
}

async function submitOrder(asAddition: boolean) {
  if (!session.table.value) return;
  orderChoiceOpen.value = false;
  placing.value = true;
  error.value = null;
  try {
    const items = cart.value.map((line) => ({ menuItemId: line.menuItemId, quantity: line.quantity }));
    const order = asAddition && addableOrder.value
      ? await api.post<OrderSummary>(`/customer/orders/${addableOrder.value.id}/items`, { items }, { successMessage: false })
      : await api.post<OrderSummary>('/customer/orders', { tableId: session.table.value.id, items }, { successMessage: false });
    placedOrder.value = order;
    placedOrderWasAddition.value = asAddition && Boolean(addableOrder.value);
    toast.success(
      placedOrderWasAddition.value
        ? t('customer.itemsAdded', { order: order.orderNumber })
        : t('customer.orderPlaced', { order: order.orderNumber }),
    );
    clear();
    await tracking.refresh();
    view.value = 'track';
  } catch {
    // Failure popup is shown by useApi — keep the menu on screen.
  } finally {
    placing.value = false;
  }
}

function trackStepClass(status: string, step: 'PENDING' | 'CONFIRMED' | 'PREPARING' | 'SERVED') {
  const rank: Record<string, number> = { PENDING: 0, CONFIRMED: 1, PREPARING: 2, READY: 3, SERVED: 4, COMPLETED: 5 };
  const stepRank = { PENDING: 0, CONFIRMED: 1, PREPARING: 2, SERVED: 4 };
  const current = rank[status] ?? -1;
  const target = stepRank[step];
  if (current > target || (step === 'SERVED' && current >= 4)) return 'done';
  if (current === target || (step === 'PREPARING' && current === 3)) return 'now';
  return 'todo';
}

onMounted(init);
</script>

<template>
  <div class="pb-4" :style="{ '--brand': accent, '--forest': accent }">
    <div class="zigzag-band" />
    <div class="app mx-auto flex min-h-[calc(100vh-32px)] max-w-[520px] flex-col bg-[#fdfdfb] shadow-[0_0_60px_-20px_rgba(70,40,15,.35)] sm:my-6 sm:min-h-0 sm:overflow-hidden sm:rounded-[28px]">
      <header class="px-[18px] pb-3 pt-3.5 text-white" :style="{ background: accent }">
        <div class="flex items-center gap-2.5">
          <img
            v-if="restaurantLogo"
            :src="restaurantLogo"
            alt=""
            class="h-10 w-10 shrink-0 rounded-xl object-cover"
          />
          <div
            v-else
            class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-white/15 text-lg font-bold"
          >
            {{ restaurantInitial }}
          </div>
          <div class="min-w-0">
            <p class="truncate font-display text-[17px] font-extrabold">{{ session.restaurant.value?.name }}</p>
            <p class="text-[11px] font-semibold text-[#bcd6ca]">{{ $t('customer.qrSubtitle') }}</p>
          </div>
          <span class="ml-auto rounded-full border border-white/25 bg-white/15 px-3 py-1 text-xs font-bold">
            {{ $t('customer.table', { number: session.table.value?.tableNumber }) }}
          </span>
        </div>
        <div class="mt-2.5">
          <LanguageSwitcher tone="onDark" />
        </div>
      </header>

      <div v-if="loading" class="py-16 text-center text-sm text-ink-muted">{{ $t('customer.loadingMenu') }}</div>

      <div v-else-if="error" class="m-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
        {{ error }}
      </div>

      <template v-else>
        <div v-if="view === 'menu'" class="flex gap-2 overflow-x-auto px-4 pb-1.5 pt-3.5 [scrollbar-width:none]">
          <button
            type="button"
            class="shrink-0 rounded-full border px-3.5 py-1.5 text-[12.5px] font-bold"
            :class="activeCategoryId === null ? 'border-forest bg-forest text-white' : 'border-line bg-white text-ink-muted'"
            @click="activeCategoryId = null"
          >
            {{ $t('customer.allCategories') }}
          </button>
          <button
            v-for="category in categories"
            :key="category.id"
            type="button"
            class="shrink-0 rounded-full border px-3.5 py-1.5 text-[12.5px] font-bold"
            :class="activeCategoryId === category.id ? 'border-forest bg-forest text-white' : 'border-line bg-white text-ink-muted'"
            @click="activeCategoryId = category.id"
          >
            {{ category.name }}
          </button>
        </div>

        <main class="flex-1 px-4 pb-24 pt-2">
          <CustomerAnnouncementBanner />

          <section v-if="view === 'menu'">
            <input
              v-model="searchQuery"
              type="search"
              :placeholder="$t('customer.searchPlaceholder')"
              class="mb-3 w-full rounded-full border border-line bg-white px-4 py-2 text-sm outline-none focus:border-forest"
            />

            <div v-if="placedOrder" class="mb-3 rounded-[14px] border border-emerald/30 bg-mint px-3.5 py-2.5 text-sm text-forest">
              {{
                placedOrderWasAddition
                  ? $t('customer.itemsAdded', { order: placedOrder.orderNumber })
                  : $t('customer.orderPlaced', { order: placedOrder.orderNumber })
              }}
            </div>

            <p v-if="categories.length > 0 && filteredCategories.length === 0" class="py-10 text-center text-sm text-ink-muted">
              {{ $t('customer.noMenuResults') }}
            </p>

            <section v-for="category in filteredCategories" :key="category.id" class="mb-5">
              <h2 class="mb-2 font-display text-lg text-forest">{{ category.name }}</h2>
              <div>
                <MenuItemCard v-for="item in category.items" :key="item.id" :item="item" @add="handleAdd" />
              </div>
            </section>

            <button
              v-if="itemCount > 0"
              type="button"
              class="sticky bottom-3.5 mt-2 flex w-full justify-between rounded-2xl bg-forest px-[18px] py-3.5 text-sm font-extrabold text-white shadow-[0_18px_40px_-14px_rgba(13,59,46,.5)]"
              @click="view = 'order'"
            >
              <span>{{ $t('customer.cartItems', { count: itemCount }) }}</span>
              <span>{{ total.toLocaleString() }} RWF →</span>
            </button>
          </section>

          <section v-else-if="view === 'order'">
            <h3 class="mb-1 mt-2 font-display text-[19px] font-extrabold text-forest">{{ $t('customer.yourOrder') }}</h3>
            <p v-if="cart.length === 0" class="py-8 text-sm text-ink-muted">{{ $t('customer.cartEmpty') }}</p>
            <div
              v-for="line in cart"
              :key="line.menuItemId"
              class="mb-2 flex items-center gap-2.5 rounded-[14px] border border-line bg-white p-2.5 text-[13px]"
            >
              <b class="min-w-0 flex-1 truncate">{{ line.name }}</b>
              <div class="ml-auto flex items-center gap-2 font-extrabold">
                <button
                  type="button"
                  class="grid h-6 w-6 place-items-center rounded-lg bg-mint text-forest"
                  @click="updateQuantity(line.menuItemId, line.quantity - 1)"
                >
                  –
                </button>
                {{ line.quantity }}
                <button
                  type="button"
                  class="grid h-6 w-6 place-items-center rounded-lg bg-mint text-forest"
                  @click="updateQuantity(line.menuItemId, line.quantity + 1)"
                >
                  +
                </button>
              </div>
            </div>
            <div class="mt-3 flex justify-between px-1 text-[13px] text-ink-muted">
              <span>{{ $t('customer.subtotal') }}</span>
              <span>{{ total.toLocaleString() }} RWF</span>
            </div>
            <div class="mt-1.5 flex justify-between px-1 text-[13px] text-ink-muted">
              <span>{{ $t('customer.service') }}</span>
              <span>—</span>
            </div>
            <div class="mt-2 flex justify-between border-t border-dashed border-line px-1 pt-2.5 text-[15px] font-extrabold text-forest">
              <span>{{ $t('common.total') }}</span>
              <span>{{ total.toLocaleString() }} RWF</span>
            </div>
            <button
              type="button"
              class="mt-3.5 w-full rounded-[14px] bg-brand py-3.5 text-[15px] font-extrabold text-[#3a2a05] disabled:opacity-50"
              :disabled="cart.length === 0 || placing"
              @click="placeOrder"
            >
              {{ placing ? $t('customer.placingOrder') : $t('customer.sendToKitchen') }}
            </button>
          </section>

          <section v-else>
            <template v-if="!activeTrackOrder">
              <h3 class="mb-1 mt-2 font-display text-[19px] font-extrabold text-forest">{{ $t('customer.myOrders') }}</h3>
              <p class="text-sm text-ink-muted">{{ $t('customer.noOrders') }}</p>
            </template>
            <template v-else>
              <h3 class="mb-1 mt-2 font-display text-[19px] font-extrabold text-forest">
                {{ $t(`customer.status.${activeTrackOrder.status}`) }}
              </h3>
              <p class="mb-4 text-[12.5px] text-ink-muted">
                {{ $t('customer.trackEta', { order: activeTrackOrder.orderNumber }) }}
              </p>

              <div
                class="flex gap-3"
                :class="trackStepClass(activeTrackOrder.status, 'PENDING')"
              >
                <div class="flex flex-col items-center">
                  <span
                    class="grid h-[26px] w-[26px] place-items-center rounded-full text-xs"
                    :class="trackStepClass(activeTrackOrder.status, 'PENDING') === 'done' || trackStepClass(activeTrackOrder.status, 'PENDING') === 'now' ? 'bg-emerald text-white' : 'bg-[#eee] text-[#999]'"
                  >✓</span>
                  <span class="w-0.5 min-h-[22px] flex-1" :class="['CONFIRMED','PREPARING','READY','SERVED','COMPLETED'].includes(activeTrackOrder.status) ? 'bg-emerald' : 'bg-line'" />
                </div>
                <div>
                  <h5 class="pt-0.5 text-sm font-bold">{{ $t('customer.stepPlaced') }}</h5>
                  <p class="pb-3.5 text-[11.5px] text-ink-muted">{{ $t('customer.stepPlacedD') }}</p>
                </div>
              </div>
              <div class="flex gap-3">
                <div class="flex flex-col items-center">
                  <span
                    class="grid h-[26px] w-[26px] place-items-center rounded-full text-xs"
                    :class="['CONFIRMED','PREPARING','READY','SERVED','COMPLETED'].includes(activeTrackOrder.status) ? 'bg-emerald text-white' : trackStepClass(activeTrackOrder.status, 'CONFIRMED') === 'now' ? 'bg-brand text-white' : 'bg-[#eee] text-[#999]'"
                  >✓</span>
                  <span class="w-0.5 min-h-[22px] flex-1" :class="['PREPARING','READY','SERVED','COMPLETED'].includes(activeTrackOrder.status) ? 'bg-emerald' : 'bg-line'" />
                </div>
                <div>
                  <h5 class="pt-0.5 text-sm font-bold">{{ $t('customer.stepConfirmed') }}</h5>
                  <p class="pb-3.5 text-[11.5px] text-ink-muted">{{ $t('customer.stepConfirmedD') }}</p>
                </div>
              </div>
              <div class="flex gap-3">
                <div class="flex flex-col items-center">
                  <span
                    class="grid h-[26px] w-[26px] place-items-center rounded-full text-xs"
                    :class="trackStepClass(activeTrackOrder.status, 'PREPARING') === 'now' ? 'bg-brand text-white shadow-[0_0_0_6px_rgba(217,142,43,.2)]' : ['READY','SERVED','COMPLETED'].includes(activeTrackOrder.status) ? 'bg-emerald text-white' : 'bg-[#eee] text-[#999]'"
                  >●</span>
                  <span class="w-0.5 min-h-[22px] flex-1" :class="['READY','SERVED','COMPLETED'].includes(activeTrackOrder.status) ? 'bg-emerald' : 'bg-line'" />
                </div>
                <div>
                  <h5 class="pt-0.5 text-sm font-bold">{{ $t('customer.stepPreparing') }}</h5>
                  <p class="pb-3.5 text-[11.5px] text-ink-muted">{{ $t('customer.stepPreparingD') }}</p>
                </div>
              </div>
              <div class="flex gap-3">
                <div class="flex flex-col items-center">
                  <span
                    class="grid h-[26px] w-[26px] place-items-center rounded-full text-xs"
                    :class="['SERVED','COMPLETED'].includes(activeTrackOrder.status) ? 'bg-emerald text-white' : 'bg-[#eee] text-[#999]'"
                  >4</span>
                </div>
                <div>
                  <h5 class="pt-0.5 text-sm font-bold">{{ $t('customer.stepServed') }}</h5>
                  <p class="pb-3.5 text-[11.5px] text-ink-muted">{{ $t('customer.stepServedD') }}</p>
                </div>
              </div>

              <ul class="mt-2 space-y-1 text-xs text-ink-muted">
                <li v-for="item in activeTrackOrder.items ?? []" :key="item.id" class="flex justify-between">
                  <span>{{ item.quantity }}× {{ item.menuItem.name }}</span>
                  <span>{{ Number(item.subtotal).toLocaleString() }}</span>
                </li>
              </ul>
              <p class="mt-2 text-sm font-extrabold text-forest">
                {{ $t('common.total') }} {{ Number(activeTrackOrder.totalAmount).toLocaleString() }} RWF
              </p>

              <button
                v-if="activeTrackOrder.status === 'PENDING' && Date.now() - new Date(activeTrackOrder.createdAt).getTime() <= 60_000"
                type="button"
                class="mt-3 w-full rounded-[14px] border border-clay py-3 text-sm font-extrabold text-clay"
                @click="handleCancelOrder(activeTrackOrder.id)"
              >
                {{ $t('customer.cancelOrder') }}
              </button>
            </template>

            <button
              type="button"
              class="mt-2.5 w-full rounded-[14px] border-2 border-forest bg-white py-3 text-center text-sm font-extrabold text-forest"
              @click="handleWaiterCall('ASSISTANCE')"
            >
              {{ $t('customer.callWaiter') }}
            </button>
            <button
              type="button"
              class="mt-2.5 w-full rounded-[14px] bg-sand py-3 text-center text-sm font-extrabold text-ink"
              @click="handleWaiterCall('BILL')"
            >
              {{ $t('customer.requestBill') }}
            </button>
          </section>
        </main>

        <nav class="sticky bottom-0 z-10 flex border-t border-line bg-white">
          <button
            type="button"
            class="flex-1 px-1 pb-3 pt-2.5 text-[11.5px] font-bold"
            :class="view === 'menu' ? 'text-forest' : 'text-ink-muted'"
            @click="view = 'menu'"
          >
            <span class="mb-0.5 block text-[19px]" :class="view === 'menu' ? 'scale-110' : ''">🍽️</span>
            {{ $t('customer.tabMenu') }}
          </button>
          <button
            type="button"
            class="flex-1 px-1 pb-3 pt-2.5 text-[11.5px] font-bold"
            :class="view === 'order' ? 'text-forest' : 'text-ink-muted'"
            @click="view = 'order'"
          >
            <span class="mb-0.5 block text-[19px]">🛒</span>
            {{ $t('customer.tabOrder') }}
            <span v-if="itemCount > 0" class="ml-0.5 inline-block rounded-full bg-clay px-1.5 py-px text-[9.5px] text-white">{{ itemCount }}</span>
          </button>
          <button
            type="button"
            class="flex-1 px-1 pb-3 pt-2.5 text-[11.5px] font-bold"
            :class="view === 'track' ? 'text-forest' : 'text-ink-muted'"
            @click="view = 'track'"
          >
            <span class="mb-0.5 block text-[19px]">⏱️</span>
            {{ $t('customer.tabTrack') }}
          </button>
        </nav>
      </template>
    </div>
    <div class="zigzag-band" />

    <HeardAboutPrompt :orders="tracking.orders.value" />

    <BaseModal v-model="orderChoiceOpen">
      <p class="font-display font-bold text-forest">{{ $t('customer.orderChoiceTitle') }}</p>
      <p class="mt-2 text-sm text-ink-muted">
        {{ $t('customer.orderChoiceBody', { order: addableOrder?.orderNumber }) }}
      </p>
      <div class="mt-4 flex flex-col gap-2">
        <BaseButton :disabled="placing" @click="submitOrder(true)">
          {{ $t('customer.addToOrder', { order: addableOrder?.orderNumber }) }}
        </BaseButton>
        <BaseButton variant="secondary" :disabled="placing" @click="submitOrder(false)">
          {{ $t('customer.startNewOrder') }}
        </BaseButton>
      </div>
    </BaseModal>
  </div>
</template>
