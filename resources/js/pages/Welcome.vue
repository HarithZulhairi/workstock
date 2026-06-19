<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { dashboard, login } from '@/routes';
import { onMounted, onUnmounted, ref } from 'vue';

import {
    ArrowRight,
    Search,
    ShieldCheck,
    Home,
    PackageSearch,
    Wrench,
    Clock,
    Tag,
    MapPin,
    Phone,
} from 'lucide-vue-next';

withDefaults(
    defineProps<{
        canRegister?: boolean;
    }>(),
    {
        canRegister: true,
    },
);

// Hero entrance: flips true on mount, driving a staggered cascade via CSS.
const heroReady = ref(false);

// Scroll-reveal: elements with [data-reveal] fade/lift into place the first
// time they cross into the viewport. One IntersectionObserver handles all of them.
let observer: IntersectionObserver | null = null;

onMounted(() => {
    // rAF avoids the entrance firing before the browser has painted the initial state.
    requestAnimationFrame(() => {
        heroReady.value = true;
    });

    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const revealEls = document.querySelectorAll<HTMLElement>('[data-reveal]');

    if (prefersReducedMotion) {
        // Skip the observer entirely; just show everything as-is.
        revealEls.forEach((el) => el.classList.add('is-visible'));
        return;
    }

    observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    const el = entry.target as HTMLElement;
                    const delay = el.dataset.revealDelay ?? '0';
                    el.style.transitionDelay = `${delay}ms`;
                    el.classList.add('is-visible');
                    observer?.unobserve(el);
                }
            });
        },
        { threshold: 0.15, rootMargin: '0px 0px -40px 0px' },
    );

    revealEls.forEach((el) => observer?.observe(el));
});

onUnmounted(() => {
    observer?.disconnect();
});
</script>

<template>
    <Head title="WorkStock — Check Parts Availability" />

    <div class="min-h-screen bg-slate-900 font-sans text-slate-900 selection:bg-blue-500 selection:text-white flex flex-col">

        <!-- ===== HEADER ===== -->
        <header class="sticky top-0 z-50 w-full border-b border-slate-200 bg-white/90 backdrop-blur-md">
            <div class="mx-auto flex h-20 max-w-7xl items-center justify-between px-6 lg:px-8">

                <div class="flex items-center gap-1">
                    <img src="/images/workstock-icon-sidebar.png" alt="WorkStock Logo" class="h-20 w-auto object-contain" />
                    <span class="text-2xl font-black tracking-tight text-blue-900">Work<span class="text-2xl font-black tracking-tight text-slate-900">Stock</span></span>
                </div>

                <nav class="hidden md:flex items-center gap-10 font-medium text-sm">
                    <Link
                        href="/"
                        class="text-blue-600 transition-colors text-lg font-semibold flex items-center gap-1.5"
                    >
                        <Home class="w-6 h-6" />
                        Home
                    </Link>

                    <Link
                        href="/catalog"
                        class="group text-slate-600 hover:text-blue-600 transition-colors text-lg font-semibold flex items-center gap-1.5"
                    >
                        <Search class="w-6 h-6 transition-transform duration-300 group-hover:scale-110" />
                        Browse Catalog
                    </Link>
                </nav>

                <div class="flex items-center gap-4">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200"
                    >
                        Go to Dashboard <ArrowRight class="w-4 h-4 transition-transform duration-200 group-hover:translate-x-0.5" />
                    </Link>
                    <Link
                        v-else
                        :href="login()"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800 hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200"
                    >
                        <ShieldCheck class="w-4 h-4" />
                        Staff Login
                    </Link>
                </div>
            </div>
        </header>

        <!-- ===== HERO ===== -->
        <main
            class="hero-bg flex-grow flex flex-col items-center justify-center relative overflow-hidden pt-20 pb-32 bg-cover bg-center"
            style="background-image: url('/images/bg-workshop.jpg');"
        >

            <div class="absolute inset-0 bg-slate-900/80 backdrop-blur-[2px]"></div>

            <div class="relative z-10 mx-auto max-w-7xl px-6 text-center lg:px-8">
                <div class="mx-auto max-w-3xl">
                    <div
                        class="mb-6 flex justify-center hero-cascade"
                        :class="{ 'hero-cascade--in': heroReady }"
                        style="transition-delay: 0ms"
                    >
                        <span class="inline-flex items-center gap-2 rounded-full bg-blue-500/20 px-3 py-1 text-sm font-semibold text-blue-200 ring-1 ring-inset ring-blue-500/40 backdrop-blur-md">
                            <MapPin class="w-4 h-4" /> Trusted Automotive Workshop
                        </span>
                    </div>
                    <h1
                        class="text-5xl font-black tracking-tight text-white sm:text-7xl hero-cascade"
                        :class="{ 'hero-cascade--in': heroReady }"
                        style="transition-delay: 90ms"
                    >
                        Wondering if we have <span class="text-blue-400">your part</span> in stock?
                    </h1>
                    <p
                        class="mt-6 text-lg leading-8 text-slate-300 hero-cascade"
                        :class="{ 'hero-cascade--in': heroReady }"
                        style="transition-delay: 180ms"
                    >
                        WorkStock is a full-service automotive workshop. Before you make the trip, check our live parts catalog to see what's on the shelf, compare prices, and plan your visit with confidence.
                    </p>

                    <div
                        class="mt-10 flex items-center justify-center gap-x-6 hero-cascade"
                        :class="{ 'hero-cascade--in': heroReady }"
                        style="transition-delay: 270ms"
                    >
                        <Link
                            href="/catalog"
                            class="group rounded-xl bg-blue-600 px-6 py-3.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 hover:shadow-lg hover:shadow-blue-500/30 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 flex items-center gap-2 transition-all duration-200 hover:scale-105 active:scale-100"
                        >
                            <Search class="w-4 h-4" /> Check Stock Availability
                        </Link>
                        <Link
                            :href="login()"
                            class="group text-sm font-semibold leading-6 text-white flex items-center gap-2 hover:text-blue-300 transition-colors duration-200"
                        >
                            Staff Portal
                            <span aria-hidden="true" class="inline-block transition-transform duration-200 group-hover:translate-x-1">→</span>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- ===== WHY CHECK WITH US (company profile angle) ===== -->
            <div class="mx-auto mt-24 max-w-7xl px-6 lg:px-8 relative z-10">
                <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">

                    <div
                        data-reveal
                        data-reveal-delay="0"
                        class="reveal-up rounded-2xl border border-slate-100 bg-white/95 backdrop-blur-sm p-8 shadow-xl transition-all duration-300 hover:-translate-y-1.5 hover:shadow-2xl"
                    >
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-blue-600 transition-transform duration-300 group-hover:scale-110">
                            <PackageSearch class="h-6 w-6" />
                        </div>
                        <h3 class="mb-2 text-lg font-bold text-slate-900">Live Stock Status</h3>
                        <p class="text-sm text-slate-600 leading-relaxed">
                            See real-time availability for every part before you drive over — no more wasted trips or phone calls just to ask "do you have this?"
                        </p>
                    </div>

                    <div
                        data-reveal
                        data-reveal-delay="120"
                        class="reveal-up rounded-2xl border border-slate-100 bg-white/95 backdrop-blur-sm p-8 shadow-xl transition-all duration-300 hover:-translate-y-1.5 hover:shadow-2xl"
                    >
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                            <Tag class="h-6 w-6" />
                        </div>
                        <h3 class="mb-2 text-lg font-bold text-slate-900">Transparent Pricing</h3>
                        <p class="text-sm text-slate-600 leading-relaxed">
                            Browse our catalog with up-to-date prices listed upfront, so you know what to expect before your repair even begins.
                        </p>
                    </div>

                    <div
                        data-reveal
                        data-reveal-delay="240"
                        class="reveal-up rounded-2xl border border-slate-100 bg-white/95 backdrop-blur-sm p-8 shadow-xl transition-all duration-300 hover:-translate-y-1.5 hover:shadow-2xl"
                    >
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                            <Wrench class="h-6 w-6" />
                        </div>
                        <h3 class="mb-2 text-lg font-bold text-slate-900">Skilled On-Site Team</h3>
                        <p class="text-sm text-slate-600 leading-relaxed">
                            Once your part is confirmed in stock, our technicians can get your vehicle booked in and back on the road without delay.
                        </p>
                    </div>

                </div>

                <!-- ===== VISIT US STRIP ===== -->
                <div
                    data-reveal
                    data-reveal-delay="0"
                    class="reveal-up mt-16 rounded-2xl bg-white/95 backdrop-blur-sm border border-slate-100 shadow-xl px-8 py-6 flex flex-col sm:flex-row items-center justify-between gap-6"
                >
                    <div class="flex items-center gap-3 text-slate-700">
                        <Clock class="h-5 w-5 text-blue-600" />
                        <span class="text-sm font-semibold">Open Mon–Sat, 9:00 AM – 6:00 PM</span>
                    </div>
                    <div class="flex items-center gap-3 text-slate-700">
                        <Phone class="h-5 w-5 text-blue-600" />
                        <span class="text-sm font-semibold">Call ahead to confirm a part before you visit</span>
                    </div>
                    <Link
                        href="/catalog"
                        class="group inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200"
                    >
                        View Full Catalog
                        <ArrowRight class="w-4 h-4 transition-transform duration-200 group-hover:translate-x-1" />
                    </Link>
                </div>
            </div>
        </main>

        <!-- ===== FOOTER ===== -->
        <footer class="border-t border-slate-800 bg-slate-950 py-8 text-center text-sm text-slate-400">
            <p>&copy; {{ new Date().getFullYear() }} WorkStock Automotive Workshop. All rights reserved.</p>
        </footer>
    </div>
</template>

<style scoped>
/* ---- Hero entrance cascade ----
   Each hero element starts slightly below + transparent, then settles
   into place. transition-delay (set inline per element) staggers them
   into one orchestrated sequence instead of everything popping at once. */
.hero-cascade {
    opacity: 0;
    transform: translateY(16px);
    transition: opacity 600ms ease, transform 600ms ease;
}
.hero-cascade--in {
    opacity: 1;
    transform: translateY(0);
}

/* ---- Scroll reveal ----
   Cards/strip start lowered + transparent; IntersectionObserver in the
   script adds .is-visible the first time each one enters the viewport. */
.reveal-up {
    opacity: 0;
    transform: translateY(24px);
    transition: opacity 550ms ease, transform 550ms ease;
}
.reveal-up.is-visible {
    opacity: 1;
    transform: translateY(0);
}

/* ---- Ambient hero background ----
   A slow, subtle zoom on the background image so the hero doesn't feel
   static. Very small scale change, long duration — ambience, not motion. */
.hero-bg {
    background-size: 110%;
    animation: hero-drift 24s ease-in-out infinite alternate;
}
@keyframes hero-drift {
    from { background-size: 110%; background-position: center 48%; }
    to   { background-size: 116%; background-position: center 52%; }
}

/* ---- Respect reduced motion ---- */
@media (prefers-reduced-motion: reduce) {
    .hero-cascade,
    .reveal-up {
        opacity: 1;
        transform: none;
        transition: none;
    }
    .hero-bg {
        animation: none;
    }
}
</style>