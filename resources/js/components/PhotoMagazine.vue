<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import { PageFlip } from 'page-flip';
import BookPage from './BookPage.vue';

const stories = [
    {
        label: 'About',
        section: 'ABOUT THE PHOTOGRAPHER',
        title: 'A life seen<br>through <em>light.</em>',
        intro: 'I’m Noor Rahman, a photographer drawn to quiet places, honest portraits, and the stories hidden in ordinary days.',
        image: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=1200&q=85',
        imageAlt: 'Portrait in soft natural light',
        caption: 'Behind the camera, somewhere new',
        note: 'A LITTLE ABOUT ME',
        number: '01',
        layout: 'image',
    },
    {
        label: 'Story 01',
        section: 'STORY 01 / THE QUIET WILD',
        title: 'Where the<br>world grows <em>quiet.</em>',
        intro: 'We walked until the trail disappeared. The mountains did what they always do: made everything else feel beautifully small.',
        image: 'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?auto=format&fit=crop&w=1500&q=85',
        imageAlt: 'Mountain peaks disappearing into a hazy sky',
        caption: 'Alpine air, and nowhere else to be',
        note: 'DOLOMITES, ITALY',
        number: '02',
        layout: 'image',
    },
    {
        label: 'Story 02',
        section: 'STORY 02 / ORDINARY MAGIC',
        title: 'A softer kind<br>of <em>morning.</em>',
        intro: 'The kettle clicks. The curtains breathe. For a little while, the whole day is still only a possibility.',
        image: 'https://images.unsplash.com/photo-1445116572660-236099ec97a0?auto=format&fit=crop&w=1500&q=85',
        imageAlt: 'Sunlight falling across a quiet neighbourhood cafe',
        caption: 'A table by the window, Copenhagen',
        note: 'COPENHAGEN, DENMARK',
        number: '03',
        layout: 'image',
    },
    {
        label: 'Story 03',
        section: 'STORY 03 / THE PEOPLE WE FIND',
        title: 'The warmth<br>between <em>us.</em>',
        intro: 'Some strangers make a place familiar. We shared tea, stories, and an afternoon that refused to hurry.',
        image: 'https://images.unsplash.com/photo-1517841905240-472988babdf9?auto=format&fit=crop&w=1500&q=85',
        imageAlt: 'Portrait of a woman in warm afternoon light',
        caption: 'A face I will remember, Marrakesh',
        note: 'MARRAKESH, MOROCCO',
        number: '04',
        layout: 'image',
    },
    {
        label: 'Story 04',
        section: 'STORY 04 / A LITTLE FURTHER',
        title: 'Take the long<br>way <em>home.</em>',
        intro: 'There is always another road, another blue hour, another reason to keep the camera close.',
        image: 'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?auto=format&fit=crop&w=1500&q=85',
        imageAlt: 'Golden sunlight falling across a winding country road',
        caption: 'The scenic route, always',
        note: 'FIELD NOTES, NO. 05',
        number: '05',
        layout: 'image',
    },
    {
        label: 'Achievements',
        section: 'MILESTONES & RECOGNITION',
        title: 'A few <em>milestones.</em>',
        intro: 'A place to celebrate the exhibitions, awards, and moments that have shaped your practice.',
        note: 'SELECTED HIGHLIGHTS',
        number: '06',
        layout: 'list',
        items: [
            { kicker: 'AWARD / YEAR', title: 'Add your award', description: 'Award name · Category · Year' },
            { kicker: 'EXHIBITION / YEAR', title: 'Add an exhibition', description: 'Exhibition title · Gallery · City' },
            { kicker: 'RECOGNITION / YEAR', title: 'Add a feature or shortlist', description: 'Organization · Project · Year' },
        ],
    },
    {
        label: 'Publications',
        section: 'IN PRINT & ONLINE',
        title: 'Stories that<br><em>travel.</em>',
        intro: 'Selected features, essays, and photo stories published in print and online.',
        note: 'SELECTED PUBLICATIONS',
        number: '07',
        layout: 'list',
        items: [
            { kicker: 'MAGAZINE / YEAR', title: 'Add a magazine feature', description: 'Publication · Story title · Issue' },
            { kicker: 'JOURNAL / YEAR', title: 'Add a photo essay', description: 'Journal or platform · Essay title' },
            { kicker: 'BOOK / YEAR', title: 'Add a book or catalogue', description: 'Book title · Publisher · Role' },
        ],
    },
    {
        label: 'Contact',
        section: 'START A CONVERSATION',
        title: 'Have a story<br>in <em>mind?</em>',
        intro: 'For assignments, collaborations, print enquiries, or simply to say hello.',
        note: 'MY INBOX IS OPEN',
        number: '08',
        layout: 'contact',
        email: 'hello@yourdomain.com',
        location: 'Available for projects worldwide',
    },
];

const bookElement = ref(null);
const currentPageIndex = ref(0);
const isReady = ref(false);
const isTurning = ref(false);
const bookPages = [
    {
        id: 'cover',
        kind: 'cover',
        image: 'https://images.unsplash.com/photo-1470770841072-f978cf4d019e?auto=format&fit=crop&w=1500&q=85',
        imageAlt: 'A quiet alpine lake and mountains on the Fieldnotes book cover',
    },
    { id: 'contents', kind: 'contents', label: 'Contents', title: 'Contents', intro: 'A field guide to the stories, milestones, and people found along the way.' },
    ...stories.map((story) => ({
        ...story,
        id: story.label.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, ''),
        kind: 'chapter',
    })),
    { id: 'colophon', kind: 'colophon', label: 'A note before you go' },
    { id: 'back-cover', kind: 'back-cover' },
];
const contents = bookPages
    .map((page, pageNumber) => ({ ...page, pageNumber }))
    .filter((page) => page.kind === 'chapter');
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
    await nextTick();
    if (!bookElement.value) {
        throw new Error('The book element was not rendered.');
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
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleKeydown);
    pageFlip?.destroy();
    pageFlip = undefined;
});
</script>

<template>
    <main
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
            aria-label="The Fieldnotes photography book"
            @click="handleBookClick"
        >
            <BookPage
                v-for="(page, pageNumber) in bookPages"
                :key="page.id"
                :page="page"
                :page-number="pageNumber"
                :page-count="bookPages.length"
                :contents="contents"
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
