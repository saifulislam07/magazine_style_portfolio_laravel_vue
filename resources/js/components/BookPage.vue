<script setup>
defineProps({
    page: {
        type: Object,
        required: true,
    },
    pageNumber: {
        type: Number,
        required: true,
    },
    pageCount: {
        type: Number,
        required: true,
    },
    contents: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <article
        class="folio-sheet"
        :class="`folio-sheet-${page.kind}`"
        :data-density="['cover', 'back-cover'].includes(page.kind) ? 'hard' : 'soft'"
        :id="`folio-${page.id}`"
    >
        <div class="folio-paper">
        <template v-if="page.kind === 'cover'">
            <img class="folio-cover-image" :src="page.image" :alt="page.imageAlt">
            <div class="folio-cover-shade"></div>
            <div class="folio-cover-topline">
                <span>FIELDNOTES JOURNAL</span>
                <span>NO. 01 &nbsp;·&nbsp; 2025</span>
            </div>
            <div class="folio-cover-copy">
                <span class="folio-cover-mark">F.</span>
                <p class="folio-cover-kicker">A PHOTOGRAPHIC JOURNAL</p>
                <h1>Stories in<br><em>stillness.</em></h1>
                <p class="folio-cover-author">NOOR RAHMAN</p>
            </div>
            <div class="folio-cover-bottom">
                <span>PEOPLE &nbsp;·&nbsp; PLACES &nbsp;·&nbsp; THE IN-BETWEEN</span>
                <span>OPEN THE COVER &nbsp;→</span>
            </div>
        </template>

        <template v-else-if="page.kind === 'back-cover'">
            <div class="folio-back-cover-mark">F.</div>
            <p class="folio-back-cover-name">FIELDNOTES</p>
            <span class="folio-back-cover-rule"></span>
            <p class="folio-back-cover-note">THANK YOU FOR<br>TURNING THE PAGES.</p>
            <span class="folio-back-cover-colophon">NOOR RAHMAN &nbsp;·&nbsp; 2025</span>
        </template>

        <template v-else>
            <header class="folio-page-header">
                <a href="#folio-cover" class="folio-mini-brand" data-page-target="0">
                    <span>F.</span> FIELDNOTES
                </a>
                <span>NOOR RAHMAN &nbsp;·&nbsp; PHOTOGRAPHY</span>
            </header>

            <main class="folio-page-main">
                <template v-if="page.kind === 'contents'">
                    <p class="folio-eyebrow">THE FIRST PAGES</p>
                    <h1 class="folio-page-title">Contents<span>.</span></h1>
                    <p class="folio-page-intro">A field guide to the stories, milestones, and people found along the way.</p>
                    <nav class="folio-contents" aria-label="Book contents">
                        <a
                            v-for="item in contents"
                            :key="item.id"
                            :href="`#folio-${item.id}`"
                            :data-page-target="item.pageNumber"
                        >
                            <span class="folio-contents-number">{{ String(item.pageNumber + 1).padStart(2, '0') }}</span>
                            <span>{{ item.label }}</span>
                            <i></i>
                            <span class="folio-contents-arrow">↗</span>
                        </a>
                    </nav>
                </template>

                <template v-else-if="page.kind === 'colophon'">
                    <p class="folio-eyebrow">A NOTE BEFORE YOU GO</p>
                    <h1 class="folio-page-title">Keep looking<br><em>closely.</em></h1>
                    <p class="folio-page-intro">The best stories are often the ones we almost walk past. Thank you for taking the time to see them with me.</p>
                    <div class="folio-signature">Noor <span>Rahman</span></div>
                    <a class="folio-inline-link" href="#folio-cover" data-page-target="0">RETURN TO THE COVER <span>↗</span></a>
                </template>

                <template v-else>
                    <div class="folio-chapter-heading">
                        <p class="folio-eyebrow">{{ page.section }}</p>
                        <h1 class="folio-page-title" v-html="page.title"></h1>
                        <p class="folio-page-intro">{{ page.intro }}</p>
                    </div>

                    <div v-if="page.image" class="folio-feature">
                        <img :src="page.image" :alt="page.imageAlt">
                        <p>{{ page.caption }}</p>
                    </div>

                    <ol v-if="page.items" class="folio-entry-list">
                        <li v-for="(item, index) in page.items" :key="item.title">
                            <span class="folio-entry-index">0{{ index + 1 }}</span>
                            <div>
                                <p class="folio-eyebrow">{{ item.kicker }}</p>
                                <h2>{{ item.title }}</h2>
                                <p>{{ item.description }}</p>
                            </div>
                        </li>
                    </ol>

                    <div v-if="page.kind === 'contact'" class="folio-contact">
                        <p class="folio-eyebrow">GOOD THINGS START WITH A HELLO</p>
                        <a :href="`mailto:${page.email}`">{{ page.email }}</a>
                        <span>{{ page.location }}</span>
                    </div>
                </template>
            </main>

            <footer class="folio-page-footer">
                <span>{{ page.label || 'FIELDNOTES' }}</span>
                <span>{{ String(pageNumber + 1).padStart(2, '0') }} <i>/</i> {{ String(pageCount).padStart(2, '0') }}</span>
            </footer>
        </template>
        </div>
    </article>
</template>
