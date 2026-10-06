<script setup>
import { computed, onMounted, ref } from 'vue';
import { request } from '../../admin/api';

const emit = defineEmits(['navigate', 'error']);

const dashboard = ref(null);
const hoveredDay = ref(null);

const peakViews = computed(() => Math.max(1, ...(dashboard.value?.visits ?? []).map((day) => day.views)));
const completedSteps = computed(() => dashboard.value?.checklist.filter((step) => step.done).length ?? 0);

const statCards = computed(() => {
    const stats = dashboard.value?.stats ?? {};

    return [
        { key: 'visitsToday', label: 'Visits today', value: stats.visitsToday, note: `${stats.visitsMonth ?? 0} in the last 30 days` },
        { key: 'visitsTotal', label: 'All-time visits', value: stats.visitsTotal, note: 'Counted on this server' },
        { key: 'unread', label: 'Unread messages', value: stats.unreadMessages, note: `${stats.messages ?? 0} received in total`, section: 'messages' },
        { key: 'pages', label: 'Book pages', value: stats.pages, note: `${stats.chapters ?? 0} chapters · ${stats.photos ?? 0} photos`, section: 'pages' },
    ];
});

function formatDay(date) {
    return new Date(`${date}T00:00:00`).toLocaleDateString(undefined, { day: 'numeric', month: 'short' });
}

function formatDate(value) {
    return value ? new Date(value).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' }) : '';
}

onMounted(async () => {
    try {
        dashboard.value = await request('/admin/api/dashboard');
    } catch (error) {
        emit('error', error.message);
    }
});
</script>

<template>
    <div v-if="!dashboard" class="admin-loading">Gathering your numbers…</div>

    <div v-else class="dashboard">
        <section class="stat-grid">
            <component
                :is="card.section ? 'button' : 'div'"
                v-for="card in statCards"
                :key="card.key"
                class="stat-card"
                :class="{ 'is-link': card.section }"
                @click="card.section && emit('navigate', card.section)"
            >
                <span class="admin-eyebrow">{{ card.label.toUpperCase() }}</span>
                <strong>{{ (card.value ?? 0).toLocaleString() }}</strong>
                <small>{{ card.note }}</small>
            </component>
        </section>

        <section class="dashboard-grid">
            <article class="dash-card chart-card">
                <header class="dash-card-head">
                    <div>
                        <p class="admin-eyebrow">ANALYTICS</p>
                        <h2>Visits, last 30 days</h2>
                    </div>
                    <span class="chart-readout">
                        <template v-if="hoveredDay">{{ formatDay(hoveredDay.date) }} · <b>{{ hoveredDay.views }}</b> views</template>
                        <template v-else>Peak <b>{{ peakViews }}</b> / day</template>
                    </span>
                </header>
                <div class="visit-chart" role="img" :aria-label="`Daily visits for the last 30 days, ${dashboard.stats.visitsMonth} in total`">
                    <div
                        v-for="day in dashboard.visits"
                        :key="day.date"
                        class="visit-bar"
                        :class="{ active: hoveredDay?.date === day.date }"
                        @mouseenter="hoveredDay = day"
                        @mouseleave="hoveredDay = null"
                    >
                        <i :style="{ height: `${Math.max(2, (day.views / peakViews) * 100)}%` }"></i>
                    </div>
                </div>
                <div class="chart-axis">
                    <span>{{ formatDay(dashboard.visits[0].date) }}</span>
                    <span>Today</span>
                </div>
                <p class="dash-card-foot">
                    Built-in counter for quick checks. For audiences, sources and devices, connect
                    <button type="button" class="inline-link" @click="emit('navigate', 'analytics')">Google Analytics</button>.
                </p>
            </article>

            <article class="dash-card">
                <header class="dash-card-head">
                    <div>
                        <p class="admin-eyebrow">SETUP · {{ completedSteps }}/{{ dashboard.checklist.length }}</p>
                        <h2>Make the site complete</h2>
                    </div>
                </header>
                <div class="progress-track"><i :style="{ width: `${(completedSteps / dashboard.checklist.length) * 100}%` }"></i></div>
                <ul class="checklist">
                    <li v-for="step in dashboard.checklist" :key="step.key" :class="{ done: step.done }">
                        <span class="check-dot">{{ step.done ? '✓' : '' }}</span>
                        <span>{{ step.label }}</span>
                        <button v-if="!step.done" type="button" class="inline-link" @click="emit('navigate', step.section)">Set up →</button>
                    </li>
                </ul>
            </article>

            <article class="dash-card">
                <header class="dash-card-head">
                    <div>
                        <p class="admin-eyebrow">INBOX</p>
                        <h2>Latest messages</h2>
                    </div>
                    <button type="button" class="admin-secondary" @click="emit('navigate', 'messages')">Open inbox</button>
                </header>
                <p v-if="!dashboard.recentMessages.length" class="dash-empty">No messages yet. They will appear here when visitors use the contact form.</p>
                <ul v-else class="mini-inbox">
                    <li v-for="message in dashboard.recentMessages" :key="message.id" :class="{ unread: !message.isRead }">
                        <strong>{{ message.name }}</strong>
                        <span>{{ message.subject || message.message }}</span>
                        <small>{{ formatDate(message.receivedAt) }}</small>
                    </li>
                </ul>
            </article>

            <article class="dash-card">
                <header class="dash-card-head">
                    <div>
                        <p class="admin-eyebrow">FOR SEARCH ENGINES</p>
                        <h2>Public links</h2>
                    </div>
                </header>
                <ul class="link-list">
                    <li><span>Website</span><a :href="dashboard.links.site" target="_blank" rel="noreferrer">{{ dashboard.links.site }} ↗</a></li>
                    <li><span>Sitemap</span><a :href="dashboard.links.sitemap" target="_blank" rel="noreferrer">{{ dashboard.links.sitemap }} ↗</a></li>
                    <li><span>Robots</span><a :href="dashboard.links.robots" target="_blank" rel="noreferrer">{{ dashboard.links.robots }} ↗</a></li>
                </ul>
                <p class="dash-card-foot">Submit the sitemap in Google Search Console and Bing Webmaster Tools.</p>
            </article>
        </section>
    </div>
</template>
