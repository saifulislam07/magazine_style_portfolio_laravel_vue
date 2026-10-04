<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import { PageFlip } from 'page-flip';
import BookPage from './BookPage.vue';

const bookElement = ref(null);
const currentPageIndex = ref(0);
const isReady = ref(false);
const isTurning = ref(false);
const isLoading = ref(true);
const loadError = ref('');
const bookPages = ref([]);
const bookSettings = ref({});
const contents = computed(() => bookPages.value
    .map((page, pageNumber) => ({ ...page, pageNumber }))
    .filter((page) => page.kind === 'chapter'));
let pageFlip;

function turnPage(direction) {
    if (!pageFlip) return;

    if (direction > 0) {
        pageFlip.flipNext('bottom');
    } else {
        pageFlip.flipPrev('bottom');
    }
}

function handleBookClick(event) {
    const target = event.target.closest('[data-page-target]');
    if (!target || !bookElement.value?.contains(target)) return;

    event.preventDefault();
    const pageNumber = Number(target.dataset.pageTarget);
    if (Number.isInteger(pageNumber) && pageNumber >= 0 && pageNumber < bookPages.length) {
        pageFlip?.flip(pageNumber, 'bottom');
    }
}

function handleKeydown(event) {
    if (event.key === 'ArrowRight') turnPage(1);
    if (event.key === 'ArrowLeft') turnPage(-1);
}

onMounted(async () => {
    try {
        const response = await fetch('/book-data', {
            headers: { Accept: 'application/json' },
        });
        if (!response.ok) {
            throw new Error('The book could not be loaded. Please refresh and try again.');
        }

        const data = await response.json();
        if (!Array.isArray(data.pages) || data.pages.length < 4) {
            throw new Error('The book has not been set up yet. Please run the database seed and refresh.');
        }

        bookPages.value = data.pages;
        bookSettings.value = data.settings ?? {};
        document.title = `${bookSettings.value.brand} — ${bookSettings.value.author}`;
        document.querySelector('meta[name="description"]')?.setAttribute(
            'content',
            bookSettings.value.site_description,
        );
        isLoading.value = false;
        await nextTick();

        if (!bookElement.value) {
            throw new Error('The book could not be displayed. Please refresh and try again.');
        }

        pageFlip = new PageFlip(bookElement.value, {
            width: 560,
            height: 800,
            size: 'stretch',
            minWidth: 320,
            maxWidth: 800,
            minHeight: 460,
            maxHeight: 1100,
            drawShadow: true,
            flippingTime: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 1 : 1400,
            usePortrait: true,
            startPage: 0,
            autoSize: false,
            maxShadowOpacity: 0.42,
            showCover: true,
            mobileScrollSupport: false,
            clickEventForward: true,
            useMouseEvents: true,
            swipeDistance: 35,
            disableFlipByClick: false,
        });
        pageFlip.on('flip', (event) => {
            currentPageIndex.value = Number(event.data);
        });
        pageFlip.on('changeState', (event) => {
            isTurning.value = event.data === 'flipping';
        });
        pageFlip.loadFromHTML(bookElement.value.querySelectorAll('.folio-sheet'));
        window.addEventListener('keydown', handleKeydown);
        isReady.value = true;
    } catch (error) {
        loadError.value = error.message;
    } finally {
        isLoading.value = false;
    }
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleKeydown);
    pageFlip?.destroy();
    pageFlip = undefined;
});
</script>

<template>
    <main v-if="isLoading" class="folio-stage folio-loading" aria-live="polite">
        <p>Opening the book…</p>
    </main>

    <main v-else-if="loadError" class="folio-stage folio-loading" role="alert">
        <p>{{ loadError }}</p>
        <a href="/">Try again</a>
        <a href="/admin/login">Admin sign in</a>
    </main>

    <main
        v-else
        class="folio-stage"
        :class="{ 'is-ready': isReady, 'is-cover': currentPageIndex === 0 && !isTurning }"
    >
        <button
            class="folio-arrow folio-arrow-previous"
            type="button"
            aria-label="Turn to the previous page"
            :disabled="!isReady || currentPageIndex === 0"
            @click="turnPage(-1)"
        >←</button>

        <div
            ref="bookElement"
            class="folio-book"
            :aria-label="`The ${bookSettings.brand} photography book`"
            @click="handleBookClick"
        >
            <BookPage
                v-for="(page, pageNumber) in bookPages"
                :key="page.id"
                :page="page"
                :page-number="pageNumber"
                :page-count="bookPages.length"
                :contents="contents"
                :settings="bookSettings"
            />
        </div>

        <button
            class="folio-arrow folio-arrow-next"
            type="button"
            aria-label="Turn to the next page"
            :disabled="!isReady || currentPageIndex >= bookPages.length - 1"
            @click="turnPage(1)"
        >→</button>

    </main>
</template>
