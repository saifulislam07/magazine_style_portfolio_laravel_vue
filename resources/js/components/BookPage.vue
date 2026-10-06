<script setup>
import ContactForm from './ContactForm.vue';

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
    settings: {
        type: Object,
        required: true,
    },
    site: {
        type: Object,
        default: () => ({ contactFormEnabled: false, socialLinks: [] }),
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
                <span>{{ settings.brand }} JOURNAL</span>
                <span>{{ settings.cover_issue }}</span>
            </div>
            <div class="folio-cover-copy">
                <span class="folio-cover-mark">{{ settings.brand.charAt(0) }}.</span>
                <p class="folio-cover-kicker">{{ settings.cover_kicker }}</p>
                <h1>{{ settings.cover_title }}</h1>
                <p class="folio-cover-author">{{ settings.author }}</p>
            </div>
            <div class="folio-cover-bottom">
                <span>{{ settings.cover_strapline }}</span>
                <span>{{ settings.cover_open_text }}</span>
            </div>
        </template>

        <template v-else-if="page.kind === 'endpaper'">
            <div class="folio-endpaper-mark" aria-hidden="true">{{ settings.brand.charAt(0) }}.</div>
        </template>

        <template v-else-if="page.kind === 'back-cover'">
            <div class="folio-back-cover-mark">{{ settings.brand.charAt(0) }}.</div>
            <p class="folio-back-cover-name">{{ settings.brand }}</p>
            <span class="folio-back-cover-rule"></span>
            <p class="folio-back-cover-note">{{ settings.back_cover_text }}</p>
            <span class="folio-back-cover-colophon">{{ settings.author }} &nbsp;·&nbsp; {{ settings.back_cover_year }}</span>
        </template>

        <template v-else>
            <header class="folio-page-header">
                <a href="#folio-cover" class="folio-mini-brand" data-page-target="0">
                    <span>{{ settings.brand.charAt(0) }}.</span> {{ settings.brand }}
                </a>
                <span>{{ settings.author }} &nbsp;·&nbsp; {{ settings.photography_label }}</span>
            </header>

            <main class="folio-page-main">
                <template v-if="page.kind === 'contents'">
                    <p class="folio-eyebrow">{{ page.section }}</p>
                    <h1 class="folio-page-title">{{ page.title }}</h1>
                    <p class="folio-page-intro">{{ page.intro }}</p>
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
                    <p class="folio-eyebrow">{{ page.section }}</p>
                    <h1 class="folio-page-title">{{ page.title }}</h1>
                    <p class="folio-page-intro">{{ page.intro }}</p>
                    <div class="folio-signature">{{ page.signature }}</div>
                    <a class="folio-inline-link" href="#folio-cover" data-page-target="0">{{ settings.return_to_cover_text }} <span>↗</span></a>
                </template>

                <template v-else>
                    <p v-if="page.isContinuation" class="folio-eyebrow">{{ page.section }} &nbsp;·&nbsp; CONTINUED</p>

                    <div v-else class="folio-chapter-heading">
                        <p class="folio-eyebrow">{{ page.section }}</p>
                        <h1 class="folio-page-title">{{ page.title }}</h1>
                        <p class="folio-page-intro">{{ page.intro }}</p>
                    </div>

                    <div
                        v-if="page.layout === 'gallery' && page.photos?.length"
                        class="folio-gallery"
                        :class="{ 'is-continued': page.isContinuation }"
                    >
                        <figure v-for="(photo, index) in page.photos" :key="`${photo.image}-${index}`">
                            <img :src="photo.image" :alt="photo.alt" loading="lazy">
                            <figcaption v-if="photo.caption">{{ photo.caption }}</figcaption>
                        </figure>
                    </div>

                    <div v-else-if="page.image" class="folio-feature">
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

                    <div v-if="page.layout === 'contact'" class="folio-contact" :class="{ 'has-form': site.contactFormEnabled }">
                        <p class="folio-eyebrow">{{ settings.contact_kicker }}</p>
                        <a v-if="page.email" :href="`mailto:${page.email}`">{{ page.email }}</a>
                        <span>{{ page.location }}</span>
                        <ContactForm v-if="site.contactFormEnabled" />
                        <nav v-if="site.socialLinks?.length" class="folio-socials" aria-label="Social profiles">
                            <a
                                v-for="link in site.socialLinks"
                                :key="link.key"
                                :href="link.url"
                                target="_blank"
                                rel="noopener noreferrer me"
                            >{{ link.label }}</a>
                        </nav>
                    </div>
                </template>
            </main>

            <footer class="folio-page-footer">
                <span>{{ page.label }}</span>
                <span>{{ String(pageNumber + 1).padStart(2, '0') }} <i>/</i> {{ String(pageCount).padStart(2, '0') }}</span>
            </footer>
        </template>
        </div>
    </article>
</template>
