<script setup>
import { computed } from 'vue';
import MagazineRightPage from './MagazineRightPage.vue';

const props = defineProps({
    story: {
        type: Object,
        required: true,
    },
    stories: {
        type: Array,
        required: true,
    },
    currentIndex: {
        type: Number,
        required: true,
    },
    isAnimating: {
        type: Boolean,
        default: false,
    },
    isFace: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['turn', 'jump']);
const progress = computed(() => ((props.currentIndex + 1) / props.stories.length) * 100);
</script>

<template>
    <article
        class="spread"
        :class="{ 'spread-face': isFace }"
        :aria-label="`${story.section}: ${story.title.replace(/<[^>]*>/g, ' ')}`"
    >
        <header class="topbar">
            <a class="wordmark" href="#" aria-label="Fieldnotes home" @click.prevent="emit('jump', 0)">
                <span class="wordmark-mark">F.</span>
                <span>FIELDNOTES<span class="wordmark-dot">.</span></span>
            </a>
            <p class="topbar-caption">A photographic journal by Noor Rahman</p>
            <a class="contact-link" :href="`mailto:${stories[stories.length - 1].email}`">
                <span>LET'S TALK</span>
                <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M4 10h11M10 5l5 5-5 5" /></svg>
            </a>
        </header>

        <section class="intro-row" aria-label="Magazine introduction">
            <div>
                <p class="eyebrow"><span class="eyebrow-line"></span> {{ story.label.toUpperCase() }}</p>
                <h1>Somewhere between <em>here</em> and there.</h1>
            </div>
            <p class="intro-aside">A living archive of places, people<br>and everything in between.</p>
        </section>

        <section class="sheet-content" aria-label="Open book pages">
            <div class="paper-page story-page">
                <div class="page-topline">
                    <span class="page-brand">F. / FIELDNOTES</span>
                    <span>{{ story.note }}</span>
                </div>
                <div class="story-copy">
                    <p class="story-section">{{ story.section }}</p>
                    <h2 v-html="story.title"></h2>
                    <span class="tiny-rule"></span>
                    <p class="story-intro">{{ story.intro }}</p>
                </div>
                <div class="page-bottomline">
                    <span>NOOR RAHMAN &nbsp;·&nbsp; PHOTOGRAPHER</span>
                    <span>{{ String(currentIndex * 2 + 2).padStart(2, '0') }}</span>
                </div>
            </div>

            <MagazineRightPage
                :story="story"
                :page-number="String(currentIndex * 2 + 3).padStart(2, '0')"
            />
        </section>

        <nav class="story-nav" aria-label="Jump to a magazine chapter">
            <button
                v-for="(chapter, index) in stories"
                :key="chapter.number"
                type="button"
                :class="{ 'is-current': currentIndex === index }"
                :aria-label="`Open ${chapter.label} chapter`"
                :aria-current="currentIndex === index ? 'page' : undefined"
                :disabled="isAnimating || isFace"
                @click="emit('jump', index)"
            >
                <span>{{ chapter.label }}</span>
                <i></i>
            </button>
        </nav>

        <footer class="reader-footer">
            <div class="issue-label">
                <span class="issue-dot"></span>
                <span>ISSUE 01 <span class="issue-separator">/</span> {{ story.label.toUpperCase() }}</span>
            </div>
            <div class="reader-controls">
                <span class="page-count">0{{ currentIndex + 1 }} <span>/</span> 0{{ stories.length }}</span>
                <div class="progress-track" role="progressbar" :aria-valuenow="currentIndex + 1" :aria-valuemax="stories.length" aria-label="Magazine progress">
                    <span :style="{ width: `${progress}%` }"></span>
                </div>
                <span class="keyboard-hint">USE <kbd>←</kbd> <kbd>→</kbd></span>
            </div>
            <span class="book-colophon">NOOR RAHMAN &nbsp;·&nbsp; EST. 2025</span>
        </footer>

        <button
            class="turn-button turn-button-left"
            type="button"
            aria-label="Go to previous page"
            :disabled="currentIndex === 0 || isAnimating || isFace"
            @click="emit('turn', -1)"
        >
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5m7 7-7-7 7-7" /></svg>
        </button>
        <button
            class="turn-button turn-button-right"
            type="button"
            aria-label="Go to next page"
            :disabled="currentIndex === stories.length - 1 || isAnimating || isFace"
            @click="emit('turn', 1)"
        >
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14m-7-7 7 7-7 7" /></svg>
        </button>
    </article>
</template>
