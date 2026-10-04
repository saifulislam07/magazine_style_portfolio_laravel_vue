<script setup>
defineProps({
    story: {
        type: Object,
        required: true,
    },
    pageNumber: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <div v-if="story.layout === 'image'" class="paper-page image-page">
        <img
            class="feature-image"
            :src="story.image"
            :alt="story.imageAlt"
        >
        <div class="image-shade"></div>
        <div class="image-topline">
            <span>FIELD STUDY / {{ story.number }}</span>
            <span>35° 41' N</span>
        </div>
        <p class="image-caption">{{ story.caption }}</p>
        <span class="image-page-number">{{ pageNumber }}</span>
    </div>

    <div v-else class="paper-page detail-page" :class="`detail-page-${story.layout}`">
        <div class="detail-page-topline">
            <span>F. / FIELDNOTES</span>
            <span>{{ story.note }}</span>
        </div>

        <div v-if="story.layout === 'contact'" class="contact-card">
            <span class="contact-card-mark">F.</span>
            <p class="detail-kicker">GOOD THINGS START WITH A HELLO</p>
            <a class="contact-email" :href="`mailto:${story.email}`">{{ story.email }}</a>
            <span class="contact-divider"></span>
            <p class="contact-location">{{ story.location }}</p>
            <a class="contact-cta" :href="`mailto:${story.email}`">
                START A CONVERSATION
                <span aria-hidden="true">↗</span>
            </a>
        </div>

        <ol v-else class="detail-list">
            <li v-for="(item, index) in story.items" :key="item.title" class="detail-list-item">
                <span class="detail-list-number">0{{ index + 1 }}</span>
                <div>
                    <p class="detail-kicker">{{ item.kicker }}</p>
                    <h3>{{ item.title }}</h3>
                    <p class="detail-description">{{ item.description }}</p>
                </div>
            </li>
        </ol>

        <span class="image-page-number detail-page-number">{{ pageNumber }}</span>
    </div>
</template>
