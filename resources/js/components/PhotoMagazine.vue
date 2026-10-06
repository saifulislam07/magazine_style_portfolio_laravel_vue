<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import { PageFlip } from 'page-flip';
import BookPage from './BookPage.vue';
import ContactDialog from './ContactDialog.vue';

const bookElement = ref(null);
const currentPageIndex = ref(0);
const isReady = ref(false);
const isTurning = ref(false);
const isLoading = ref(true);
const loadError = ref('');
const bookPages = ref([]);
const bookSettings = ref({});
const siteInfo = ref({ contactFormEnabled: false, socialLinks: [] });
const contactOpen = ref(false);
const contents = computed(() => bookPages.value
    .map((page, pageNumber) => ({ ...page, pageNumber }))
    .filter((page) => page.kind === 'chapter' && !page.isContinuation));
const isBackCoverAlone = computed(() => bookPages.value.length % 2 === 0
    && currentPageIndex.value === bookPages.value.length - 1);
const galleryPhotosOnFirstPage = 1;
const galleryPhotosPerPage = 2;

/**
 * Split a gallery chapter across as many book pages as its photos need.
 */
function paginateGalleries(pages) {
    return pages.flatMap((page) => {
        const photos = page.photos ?? [];
        if (page.layout !== 'gallery' || photos.length <= galleryPhotosOnFirstPage) return [page];

        const sheets = [{ ...page, photos: photos.slice(0, galleryPhotosOnFirstPage) }];
        for (let start = galleryPhotosOnFirstPage; start < photos.length; start += galleryPhotosPerPage) {
            sheets.push({
                ...page,
                id: `${page.id}-${sheets.length + 1}`,
                isContinuation: true,
                photos: photos.slice(start, start + galleryPhotosPerPage),
            });
        }

        return sheets;
    });
}

const defaultFlippingTime = window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 1 : 1400;
let pageFlip;
let targetPageNumber = null;

function turnPage(direction) {
    if (!pageFlip) return;

    if (direction > 0) {
        pageFlip.flipNext('bottom');
    } else {
        pageFlip.flipPrev('bottom');
    }
}

/**
 * Pages that share the current spread with the visible page, so the walk stops once the target is in view.
 */
function visiblePageNumbers() {
    const current = pageFlip.getCurrentPageIndex();
    if (pageFlip.getOrientation() === 'portrait') return [current];
    if (current === 0) return [0];

    const left = current % 2 === 1 ? current : current - 1;

    return [left, left + 1];
}

function walkToTargetPage() {
    if (!pageFlip || targetPageNumber === null) return;

    const visible = visiblePageNumbers();
    if (visible.includes(targetPageNumber)) {
        targetPageNumber = null;
        pageFlip.getSettings().flippingTime = defaultFlippingTime;

        return;
    }

    if (targetPageNumber > visible[visible.length - 1]) {
        pageFlip.flipNext('bottom');
    } else {
        pageFlip.flipPrev('bottom');
    }
}

function flipThroughTo(pageNumber) {
    if (!pageFlip || isTurning.value) return;

    targetPageNumber = pageNumber;
    const spreadsToTurn = Math.abs(pageNumber - pageFlip.getCurrentPageIndex()) / 2;
    pageFlip.getSettings().flippingTime = spreadsToTurn > 1
        ? Math.max(380, Math.min(defaultFlippingTime, 2400 / spreadsToTurn))
        : defaultFlippingTime;
    walkToTargetPage();
}

function handleBookClick(event) {
    if (event.target.closest('[data-contact-open]')) {
        event.preventDefault();
        event.stopPropagation();
        contactOpen.value = true;

        return;
    }

    const target = event.target.closest('[data-page-target]');
    if (!target || !bookElement.value?.contains(target)) return;

    event.preventDefault();
    event.stopPropagation();
    const pageNumber = Number(target.dataset.pageTarget);
    if (Number.isInteger(pageNumber) && pageNumber >= 0 && pageNumber < bookPages.value.length) {
        flipThroughTo(pageNumber);
    }
}

function handleKeydown(event) {
    if (contactOpen.value) return;
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

        bookPages.value = paginateGalleries(data.pages);
        bookSettings.value = data.settings ?? {};
        siteInfo.value = data.site ?? siteInfo.value;
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
            flippingTime: defaultFlippingTime,
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
            if (event.data === 'read' && targetPageNumber !== null) {
                window.setTimeout(walkToTargetPage, 60);
            }
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
        :class="{
            'is-ready': isReady,
            'is-cover': currentPageIndex === 0 && !isTurning,
            'is-back-cover': isBackCoverAlone && !isTurning,
        }"
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
                :site="siteInfo"
            />
        </div>

        <button
            class="folio-arrow folio-arrow-next"
            type="button"
            aria-label="Turn to the next page"
            :disabled="!isReady || currentPageIndex >= bookPages.length - 1"
            @click="turnPage(1)"
        >→</button>

        <ContactDialog
            :open="contactOpen"
            :kicker="bookSettings.contact_kicker"
            @close="contactOpen = false"
        />
    </main>
</template>
