<script setup lang="ts">
const showContact = ref(false);
const mobileOpen = ref(false);

function openContact() {
  showContact.value = true;
  mobileOpen.value = false;
}
</script>

<template>
  <div class="min-h-screen bg-card font-body text-ink">
    <div class="zigzag-band" />

    <header class="sticky top-0 z-40 border-b border-line bg-card/95 backdrop-blur-md">
      <nav class="mx-auto flex max-w-[1140px] flex-wrap items-center gap-3 px-4 py-4 sm:px-6 lg:gap-5" aria-label="Main">
        <NuxtLink to="/" class="flex items-center gap-2.5 font-extrabold text-forest" @click="mobileOpen = false">
          <BrandMark :size="34" />
          <span class="text-[19px] leading-none">Smart Restaurant</span>
        </NuxtLink>

        <div class="ml-auto hidden items-center gap-5 text-sm font-semibold text-ink-muted md:flex">
          <a href="/#features" class="transition hover:text-forest">{{ $t('marketing.nav.features') }}</a>
          <a href="/#how-it-works" class="transition hover:text-forest">{{ $t('marketing.nav.how') }}</a>
          <NuxtLink to="/pricing" class="transition hover:text-forest">{{ $t('marketing.nav.pricing') }}</NuxtLink>
          <NuxtLink to="/faq" class="transition hover:text-forest">{{ $t('marketing.nav.faq') }}</NuxtLink>
          <a href="/#demo" class="transition hover:text-forest">{{ $t('marketing.nav.demo') }}</a>
        </div>

        <div class="hidden items-center gap-2.5 md:flex">
          <LanguageSwitcher />
          <NuxtLink
            to="/staff/login"
            class="rounded-full border-[1.5px] border-forest px-5 py-2.5 text-sm font-bold text-forest transition hover:bg-forest hover:text-white"
          >
            {{ $t('marketing.nav.signIn') }}
          </NuxtLink>
          <NuxtLink
            to="/register"
            class="rounded-full bg-brand px-5 py-2.5 text-sm font-bold text-[#3a2a05] transition hover:bg-brand-dark hover:text-white"
          >
            {{ $t('marketing.nav.trial') }}
          </NuxtLink>
        </div>

        <button
          type="button"
          class="ml-auto inline-flex h-10 w-10 items-center justify-center rounded-xl border border-line text-ink md:hidden"
          :aria-expanded="mobileOpen"
          aria-label="Toggle menu"
          @click="mobileOpen = !mobileOpen"
        >
          <span class="sr-only">Menu</span>
          <svg v-if="!mobileOpen" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
          <svg v-else class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </nav>

      <div v-if="mobileOpen" class="border-t border-line bg-card px-4 py-4 md:hidden">
        <div class="flex flex-col gap-3 text-sm font-semibold">
          <a href="/#features" class="py-1" @click="mobileOpen = false">{{ $t('marketing.nav.features') }}</a>
          <a href="/#how-it-works" class="py-1" @click="mobileOpen = false">{{ $t('marketing.nav.how') }}</a>
          <NuxtLink to="/pricing" class="py-1" @click="mobileOpen = false">{{ $t('marketing.nav.pricing') }}</NuxtLink>
          <NuxtLink to="/faq" class="py-1" @click="mobileOpen = false">{{ $t('marketing.nav.faq') }}</NuxtLink>
          <a href="/#demo" class="py-1" @click="mobileOpen = false">{{ $t('marketing.nav.demo') }}</a>
          <button type="button" class="py-1 text-left" @click="openContact">{{ $t('marketing.nav.contact') }}</button>
          <LanguageSwitcher />
          <NuxtLink to="/staff/login" class="py-1" @click="mobileOpen = false">{{ $t('marketing.nav.signIn') }}</NuxtLink>
          <NuxtLink
            to="/register"
            class="mt-1 rounded-full bg-brand px-5 py-2.5 text-center font-bold text-[#3a2a05]"
            @click="mobileOpen = false"
          >
            {{ $t('marketing.nav.trial') }}
          </NuxtLink>
        </div>
      </div>
    </header>

    <slot />

    <footer class="bg-surface-deep text-[#9dbcac]">
      <div class="mx-auto flex max-w-[1140px] flex-wrap items-start justify-between gap-6 px-4 py-9 text-[13px] sm:px-6">
        <div class="max-w-sm">
          <div class="mb-3 flex items-center gap-2.5 text-white">
            <BrandMark :size="30" tone="onDark" />
            <span class="text-base font-extrabold">Smart Restaurant</span>
          </div>
          <p class="leading-relaxed">{{ $t('marketing.footer.blurb') }}</p>
        </div>
        <div class="flex flex-wrap gap-x-4 gap-y-2 font-bold">
          <NuxtLink to="/pricing" class="text-[#d8b671] hover:underline">{{ $t('marketing.nav.pricing') }}</NuxtLink>
          <NuxtLink to="/faq" class="text-[#d8b671] hover:underline">{{ $t('marketing.nav.faq') }}</NuxtLink>
          <a href="/#demo" class="text-[#d8b671] hover:underline">{{ $t('marketing.nav.demo') }}</a>
          <NuxtLink to="/staff/login" class="text-[#d8b671] hover:underline">{{ $t('marketing.footer.portal') }}</NuxtLink>
          <NuxtLink to="/privacy" class="text-[#d8b671] hover:underline">{{ $t('legal.privacyTitle') }}</NuxtLink>
          <NuxtLink to="/terms" class="text-[#d8b671] hover:underline">{{ $t('legal.termsTitle') }}</NuxtLink>
          <a href="mailto:info@inovasiyo.rw" class="text-[#d8b671] hover:underline">info@inovasiyo.rw</a>
          <a href="tel:+250781612134" class="text-[#d8b671] hover:underline">+250 781 612 134</a>
        </div>
      </div>
      <div class="border-t border-white/10 px-4 py-4 text-center text-xs sm:px-6">
        © {{ new Date().getFullYear() }} Smart Restaurant by
        <strong class="text-white/80">Inovasiyo Ltd</strong>. {{ $t('marketing.footer.legal') }}
      </div>
    </footer>
    <div class="zigzag-band" />

    <MarketingContactModal v-model="showContact" />
    <MarketingCookieBanner />
  </div>
</template>
