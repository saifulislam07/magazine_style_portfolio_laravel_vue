<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { csrfToken, request } from '../admin/api';
import AdminDashboard from './admin/AdminDashboard.vue';
import AdminMessages from './admin/AdminMessages.vue';
import AdminSiteSettings from './admin/AdminSiteSettings.vue';
import ImageField from './admin/ImageField.vue';

const pages = ref([]);
const settings = ref({});
const siteSettings = ref(null);
const unreadMessages = ref(0);
const activeSection = ref('dashboard');
const selectedId = ref(null);
const draft = ref(null);
const settingsDraft = ref({});
const loading = ref(true);
const saving = ref(false);
const errorMessage = ref('');
const successMessage = ref('');
const draggedChapterId = ref(null);
const dragOverChapterId = ref(null);
const menuOpen = ref(false);
let successTimer;

const chapterPages = computed(() => pages.value.filter((page) => page.kind === 'chapter'));
const selectedIndex = computed(() => chapterPages.value.findIndex((page) => page.id === selectedId.value));
const brand = computed(() => settings.value.brand || 'FIELDNOTES');

const navigation = [
    {
        caption: 'OVERVIEW',
        items: [{ key: 'dashboard', icon: '◷', label: 'Dashboard', title: 'Good to see you', eyebrow: 'STUDIO OVERVIEW' }],
    },
    {
        caption: 'CONTENT',
        items: [
            { key: 'pages', icon: '▤', label: 'Book pages', title: 'Your book pages', eyebrow: 'CONTENT' },
            { key: 'book', icon: '✎', label: 'Book text', title: 'Cover & book text', eyebrow: 'CONTENT' },
        ],
    },
    {
        caption: 'AUDIENCE',
        items: [
            { key: 'messages', icon: '✉', label: 'Inbox', title: 'Messages', eyebrow: 'AUDIENCE' },
            { key: 'analytics', icon: '↗', label: 'Analytics', title: 'Analytics', eyebrow: 'AUDIENCE' },
        ],
    },
    {
        caption: 'SITE SETTINGS',
        items: [
            { key: 'seo', icon: '⌕', label: 'SEO & sharing', title: 'SEO & social sharing', eyebrow: 'SITE SETTINGS' },
            { key: 'contact', icon: '☏', label: 'Contact & social', title: 'Contact & social links', eyebrow: 'SITE SETTINGS' },
            { key: 'mail', icon: '⇄', label: 'Email (SMTP)', title: 'Email delivery', eyebrow: 'SITE SETTINGS' },
        ],
    },
];
const navItems = navigation.flatMap((group) => group.items);
const currentNav = computed(() => navItems.find((item) => item.key === activeSection.value) ?? navItems[0]);
const siteSettingGroups = ['seo', 'analytics', 'mail', 'contact'];

const bookTextGroups = [
    {
        title: 'Identity',
        description: 'Your name and the name of the book, used on every page.',
        fields: [
            { key: 'brand', label: 'Book name' },
            { key: 'author', label: 'Photographer name' },
            { key: 'photography_label', label: 'Role shown in the book' },
            { key: 'site_description', label: 'Short description', multiline: true, help: 'Used for search results unless you set one under SEO.' },
        ],
    },
    {
        title: 'Front cover',
        description: 'The words printed over the cover photo.',
        fields: [
            { key: 'cover_issue', label: 'Issue and year' },
            { key: 'cover_kicker', label: 'Subtitle' },
            { key: 'cover_title', label: 'Title', multiline: true, help: 'Use a new line to break the title.' },
            { key: 'cover_strapline', label: 'Footer line' },
            { key: 'cover_open_text', label: 'Open prompt' },
        ],
    },
    {
        title: 'Inside & back cover',
        description: 'Small pieces of text used inside the book and on the back.',
        fields: [
            { key: 'contact_kicker', label: 'Contact prompt' },
            { key: 'return_to_cover_text', label: 'Return-to-cover link text' },
            { key: 'back_cover_text', label: 'Back cover message', multiline: true },
            { key: 'back_cover_year', label: 'Back cover year' },
        ],
    },
];

/**
 * Page fields grouped into separate content blocks, shown per page type.
 */
const sectionsByKind = {
    cover: [
        { title: 'Cover photo', fields: [{ key: 'image', label: 'Cover image', type: 'image' }, { key: 'imageAlt', label: 'Image description', help: 'Describe the photo for screen readers and search engines.' }] },
    ],
    contents: [
        { title: 'Text', fields: [{ key: 'section', label: 'Small heading' }, { key: 'title', label: 'Page title' }, { key: 'intro', label: 'Introduction', multiline: true }] },
    ],
    chapter: [
        {
            title: 'Text',
            fields: [
                { key: 'section', label: 'Small heading' },
                { key: 'number', label: 'Chapter number' },
                { key: 'title', label: 'Page title', multiline: true, help: 'Use a new line to break the title.' },
                { key: 'intro', label: 'Introduction', multiline: true },
                { key: 'note', label: 'Small note' },
            ],
        },
        {
            title: 'Layout',
            fields: [{ key: 'layout', label: 'Page layout', type: 'select', options: [
                { value: 'image', label: 'Single photo' },
                { value: 'gallery', label: 'Photo gallery' },
                { value: 'list', label: 'List of entries' },
                { value: 'contact', label: 'Contact page' },
            ] }],
        },
        {
            title: 'Photo',
            when: (content) => content.layout !== 'gallery',
            fields: [{ key: 'image', label: 'Photo', type: 'image' }, { key: 'imageAlt', label: 'Photo description' }, { key: 'caption', label: 'Caption' }],
        },
        {
            title: 'Contact details',
            when: (content) => content.layout === 'contact',
            fields: [{ key: 'email', label: 'Contact email', type: 'email' }, { key: 'location', label: 'Location' }],
            note: 'The message form and social links are managed under Contact & social.',
        },
    ],
    colophon: [
        { title: 'Text', fields: [{ key: 'section', label: 'Small heading' }, { key: 'title', label: 'Page title', multiline: true }, { key: 'intro', label: 'Closing note', multiline: true }, { key: 'signature', label: 'Signature' }] },
    ],
    'back-cover': [],
};

const editorSections = computed(() => (sectionsByKind[draft.value?.kind] ?? [])
    .filter((section) => !section.when || section.when(draft.value.content)));

function showError(message) {
    successMessage.value = '';
    errorMessage.value = message;
}

function showSuccess(message) {
    errorMessage.value = '';
    successMessage.value = message;
    window.clearTimeout(successTimer);
    successTimer = window.setTimeout(() => { successMessage.value = ''; }, 4000);
}

function clearMessages() {
    errorMessage.value = '';
    successMessage.value = '';
}

function navigate(section) {
    activeSection.value = navItems.some((item) => item.key === section) ? section : 'dashboard';
    menuOpen.value = false;
    clearMessages();
    if (window.location.hash !== `#${activeSection.value}`) {
        history.replaceState(null, '', `#${activeSection.value}`);
    }
}

function syncFromHash() {
    const section = window.location.hash.slice(1);
    if (section && section !== activeSection.value) navigate(section);
}

function selectPage(page) {
    selectedId.value = page.id;
    draft.value = JSON.parse(JSON.stringify(page));
    clearMessages();
}

async function loadData() {
    loading.value = true;
    clearMessages();

    try {
        const [data, site, inbox] = await Promise.all([
            request('/admin/api/data'),
            request('/admin/api/site-settings'),
            request('/admin/api/dashboard'),
        ]);
        pages.value = data.pages;
        settings.value = data.settings;
        settingsDraft.value = { ...data.settings };
        siteSettings.value = site.settings;
        unreadMessages.value = inbox.stats.unreadMessages;
    } catch (error) {
        showError(error.message);
    } finally {
        loading.value = false;
    }
}

async function savePage() {
    if (!draft.value || saving.value) return;
    saving.value = true;

    try {
        const body = { label: draft.value.label, content: draft.value.content };
        const result = draft.value.isNew
            ? await request('/admin/api/pages', { method: 'POST', body: JSON.stringify(body) })
            : await request(`/admin/api/pages/${encodeURIComponent(draft.value.id)}`, { method: 'PUT', body: JSON.stringify(body) });
        const page = result.page;
        const existingIndex = pages.value.findIndex((item) => item.id === page.id);

        if (existingIndex === -1) pages.value.push(page);
        else pages.value[existingIndex] = page;

        pages.value.sort((a, b) => a.position - b.position);
        selectPage(page);
        showSuccess('Page saved. Your book is now up to date.');
    } catch (error) {
        showError(error.message);
    } finally {
        saving.value = false;
    }
}

function addPage() {
    navigate('pages');
    selectedId.value = null;
    draft.value = {
        id: '',
        kind: 'chapter',
        label: 'New chapter',
        isNew: true,
        content: {
            section: 'NEW CHAPTER',
            title: 'A new story.',
            intro: '',
            note: '',
            number: String(chapterPages.value.length + 1).padStart(2, '0'),
            layout: 'image',
            image: '',
            imageAlt: '',
            caption: '',
            items: [],
        },
    };
}

async function removePage(page = draft.value) {
    if (!page || page.kind !== 'chapter' || page.isNew || saving.value) return;
    if (!window.confirm(`Delete “${page.label}” from the book? This cannot be undone.`)) return;

    saving.value = true;
    try {
        await request(`/admin/api/pages/${encodeURIComponent(page.id)}`, { method: 'DELETE' });
        pages.value = pages.value
            .filter((item) => item.id !== page.id)
            .map((item, index) => ({ ...item, position: index }));
        if (draft.value?.id === page.id) {
            selectedId.value = null;
            draft.value = null;
        }
        showSuccess(`“${page.label}” deleted.`);
    } catch (error) {
        showError(error.message);
    } finally {
        saving.value = false;
    }
}

async function saveChapterOrder(reordered) {
    if (saving.value) return;
    saving.value = true;

    try {
        const result = await request('/admin/api/pages/reorder', {
            method: 'PUT',
            body: JSON.stringify({ ids: reordered.map((page) => page.id) }),
        });
        pages.value = result.pages;
        const selected = pages.value.find((page) => page.id === selectedId.value);
        if (selected) selectPage(selected);
        showSuccess('Chapter order updated. The contents page follows this order.');
    } catch (error) {
        showError(error.message);
    } finally {
        saving.value = false;
    }
}

function moveChapterAt(index, offset) {
    const target = index + offset;
    if (index < 0 || target < 0 || target >= chapterPages.value.length || saving.value) return;

    const reordered = [...chapterPages.value];
    [reordered[index], reordered[target]] = [reordered[target], reordered[index]];
    saveChapterOrder(reordered);
}

function moveChapter(offset) {
    moveChapterAt(selectedIndex.value, offset);
}

function startChapterDrag(page) {
    draggedChapterId.value = page.id;
}

function dragOverChapter(event, page) {
    if (page.kind !== 'chapter' || !draggedChapterId.value) return;

    event.preventDefault();
    dragOverChapterId.value = page.id;
}

function dropChapterOn(page) {
    const fromIndex = chapterPages.value.findIndex((chapter) => chapter.id === draggedChapterId.value);
    const toIndex = chapterPages.value.findIndex((chapter) => chapter.id === page.id);
    draggedChapterId.value = null;
    dragOverChapterId.value = null;
    if (fromIndex < 0 || toIndex < 0 || fromIndex === toIndex) return;

    const reordered = [...chapterPages.value];
    const [moved] = reordered.splice(fromIndex, 1);
    reordered.splice(toIndex, 0, moved);
    saveChapterOrder(reordered);
}

function endChapterDrag() {
    draggedChapterId.value = null;
    dragOverChapterId.value = null;
}

async function saveBookText() {
    if (saving.value) return;
    saving.value = true;

    try {
        const result = await request('/admin/api/settings', {
            method: 'PUT',
            body: JSON.stringify({ settings: settingsDraft.value }),
        });
        settings.value = result.settings;
        settingsDraft.value = { ...result.settings };
        showSuccess('Book text saved.');
    } catch (error) {
        showError(error.message);
    } finally {
        saving.value = false;
    }
}

function addEntry() {
    draft.value.content.items ??= [];
    draft.value.content.items.push({ kicker: '', title: '', description: '' });
}

function addPhoto() {
    draft.value.content.photos ??= [];
    if (draft.value.content.photos.length >= 60) return;
    draft.value.content.photos.push({ image: '', alt: '', caption: '' });
}

function kindLabel(kind) {
    return kind.replace('-', ' ').toUpperCase();
}

watch(activeSection, () => window.scrollTo({ top: 0 }));

onMounted(async () => {
    window.addEventListener('hashchange', syncFromHash);
    await loadData();
    syncFromHash();
});

onBeforeUnmount(() => {
    window.removeEventListener('hashchange', syncFromHash);
    window.clearTimeout(successTimer);
});
</script>

<template>
    <div class="admin-shell" :class="{ 'menu-open': menuOpen }">
        <aside class="admin-sidebar">
            <div class="admin-sidebar-top">
                <a class="admin-brand" href="/" target="_blank" rel="noreferrer">
                    <span>{{ brand.charAt(0) }}.</span> {{ brand }} <small>STUDIO</small>
                </a>
                <button class="menu-toggle" type="button" :aria-expanded="menuOpen" aria-label="Toggle menu" @click="menuOpen = !menuOpen">
                    {{ menuOpen ? '✕' : '☰' }}
                </button>
            </div>

            <nav class="admin-nav" aria-label="Admin sections">
                <div v-for="group in navigation" :key="group.caption" class="admin-nav-group">
                    <p class="admin-sidebar-caption">{{ group.caption }}</p>
                    <button
                        v-for="item in group.items"
                        :key="item.key"
                        type="button"
                        :class="{ active: activeSection === item.key }"
                        :aria-current="activeSection === item.key ? 'page' : undefined"
                        @click="navigate(item.key)"
                    >
                        <span class="nav-icon">{{ item.icon }}</span> {{ item.label }}
                        <span v-if="item.key === 'pages'" class="nav-count">{{ pages.length }}</span>
                        <span v-if="item.key === 'messages' && unreadMessages" class="nav-badge">{{ unreadMessages }}</span>
                    </button>
                </div>
            </nav>

            <div class="admin-sidebar-bottom">
                <a href="/" target="_blank" rel="noreferrer">View published book <span>↗</span></a>
                <form method="POST" action="/admin/logout">
                    <input type="hidden" name="_token" :value="csrfToken()">
                    <button type="submit"><span class="nav-icon">↪</span> Sign out</button>
                </form>
            </div>
        </aside>

        <main class="admin-main">
            <header class="admin-topbar">
                <div>
                    <p class="admin-eyebrow">{{ brand }} / {{ currentNav.eyebrow }}</p>
                    <h1>{{ currentNav.title }}</h1>
                </div>
                <button v-if="activeSection === 'pages'" class="admin-primary add-page-button" type="button" @click="addPage">
                    <span>＋</span> Add chapter
                </button>
                <a v-else-if="activeSection === 'dashboard'" class="admin-secondary" href="/" target="_blank" rel="noreferrer">View site ↗</a>
            </header>

            <div class="admin-toasts" aria-live="polite">
                <div v-if="errorMessage" class="admin-alert" role="alert">
                    {{ errorMessage }}
                    <button type="button" aria-label="Dismiss" @click="errorMessage = ''">✕</button>
                </div>
                <div v-if="successMessage" class="admin-success" role="status">{{ successMessage }}</div>
            </div>

            <div v-if="loading" class="admin-loading">Opening your studio…</div>

            <AdminDashboard
                v-else-if="activeSection === 'dashboard'"
                @navigate="navigate"
                @error="showError"
            />

            <AdminMessages
                v-else-if="activeSection === 'messages'"
                @error="showError"
                @success="showSuccess"
                @unread-changed="unreadMessages = $event"
            />

            <AdminSiteSettings
                v-else-if="siteSettingGroups.includes(activeSection) && siteSettings"
                :group="activeSection"
                :settings="siteSettings"
                :fallback-title="`${settings.brand ?? ''} — ${settings.author ?? ''}`"
                :fallback-description="settings.site_description ?? ''"
                @saved="siteSettings = $event"
                @error="showError"
                @success="showSuccess"
            />

            <form v-else-if="activeSection === 'book'" class="settings-stack" @submit.prevent="saveBookText">
                <fieldset v-for="group in bookTextGroups" :key="group.title" class="settings-panel settings-section">
                    <legend class="panel-heading">
                        <h2>{{ group.title }}</h2>
                        <p>{{ group.description }}</p>
                    </legend>
                    <div class="settings-form">
                        <label v-for="field in group.fields" :key="field.key" class="admin-field" :class="{ 'span-all': field.multiline }">
                            <span>{{ field.label }}</span>
                            <textarea v-if="field.multiline" v-model="settingsDraft[field.key]" rows="3"></textarea>
                            <input v-else v-model="settingsDraft[field.key]" type="text">
                            <small v-if="field.help">{{ field.help }}</small>
                        </label>
                    </div>
                </fieldset>
                <div class="form-actions sticky-actions">
                    <button class="admin-primary" type="submit" :disabled="saving">
                        {{ saving ? 'Saving…' : 'Save book text' }} <span>→</span>
                    </button>
                </div>
            </form>

            <section v-else class="editor-layout">
                <aside class="page-list-panel">
                    <div class="page-list-sticky">
                        <div class="page-list-title">
                            <span>BOOK STRUCTURE</span>
                            <span>{{ pages.length }} pages</span>
                        </div>
                        <p class="page-list-hint">Drag chapters, or use ↑ ↓, to change their order in the book and on the contents page.</p>
                        <div
                            v-for="page in pages"
                            :key="page.id"
                            class="page-list-row"
                            :class="{
                                'is-draggable': page.kind === 'chapter',
                                'is-dragging': draggedChapterId === page.id,
                                'is-drop-target': dragOverChapterId === page.id && draggedChapterId !== page.id,
                            }"
                            :draggable="page.kind === 'chapter' && !saving"
                            @dragstart="page.kind === 'chapter' && startChapterDrag(page)"
                            @dragover="dragOverChapter($event, page)"
                            @drop.prevent="page.kind === 'chapter' && dropChapterOn(page)"
                            @dragend="endChapterDrag"
                        >
                            <button
                                type="button"
                                class="page-list-item"
                                :class="{ selected: selectedId === page.id || draft?.id === page.id }"
                                @click="selectPage(page)"
                            >
                                <span class="page-list-number">{{ String(page.position + 1).padStart(2, '0') }}</span>
                                <span class="page-list-label">
                                    <strong>{{ page.label || page.id }}</strong>
                                    <small>{{ page.kind === 'chapter' ? (page.content.layout ?? 'image').toUpperCase() : kindLabel(page.kind) }}</small>
                                </span>
                                <span v-if="page.kind === 'chapter'" class="drag-mark" aria-hidden="true">⠿</span>
                                <span v-else class="lock-mark">◆</span>
                            </button>
                            <div v-if="page.kind === 'chapter'" class="page-list-order">
                                <button
                                    type="button"
                                    :aria-label="`Move ${page.label} up`"
                                    :disabled="saving || chapterPages.indexOf(page) <= 0"
                                    @click="moveChapterAt(chapterPages.indexOf(page), -1)"
                                >↑</button>
                                <button
                                    type="button"
                                    :aria-label="`Move ${page.label} down`"
                                    :disabled="saving || chapterPages.indexOf(page) >= chapterPages.length - 1"
                                    @click="moveChapterAt(chapterPages.indexOf(page), 1)"
                                >↓</button>
                            </div>
                            <button
                                v-if="page.kind === 'chapter'"
                                type="button"
                                class="page-list-delete"
                                :aria-label="`Delete ${page.label}`"
                                title="Delete this chapter"
                                :disabled="saving"
                                @click="removePage(page)"
                            >🗑</button>
                            <span v-else class="page-list-locked" title="Fixed page — it can be edited but not deleted">🔒</span>
                        </div>
                    </div>
                </aside>

                <article v-if="draft" class="page-editor">
                    <div class="page-editor-heading">
                        <div>
                            <p class="admin-eyebrow">{{ draft.isNew ? 'NEW PAGE' : kindLabel(draft.kind) }}</p>
                            <h2>{{ draft.isNew ? 'Add a chapter' : draft.label }}</h2>
                        </div>
                        <div v-if="draft.kind === 'chapter' && !draft.isNew" class="reorder-actions">
                            <button type="button" aria-label="Move chapter up" :disabled="selectedIndex <= 0 || saving" @click="moveChapter(-1)">↑</button>
                            <button type="button" aria-label="Move chapter down" :disabled="selectedIndex < 0 || selectedIndex >= chapterPages.length - 1 || saving" @click="moveChapter(1)">↓</button>
                            <button type="button" class="danger-button" :disabled="saving" @click="removePage()">🗑 Delete</button>
                        </div>
                        <p v-else-if="!draft.isNew" class="fixed-page-hint">Fixed page · can be edited, not deleted</p>
                    </div>

                    <form class="page-form" @submit.prevent="savePage">
                        <section class="editor-block">
                            <h3 class="editor-block-title">Page</h3>
                            <label class="admin-field">
                                <span>Page label</span>
                                <input v-model="draft.label" type="text" maxlength="80" required>
                                <small>Shown on the contents page and in the page footer.</small>
                            </label>
                        </section>

                        <section v-for="section in editorSections" :key="section.title" class="editor-block">
                            <h3 class="editor-block-title">{{ section.title }}</h3>
                            <p v-if="section.note" class="editor-block-note">{{ section.note }}</p>
                            <template v-for="field in section.fields" :key="field.key">
                                <ImageField
                                    v-if="field.type === 'image'"
                                    v-model="draft.content[field.key]"
                                    :label="field.label"
                                    @error="showError"
                                />
                                <label v-else class="admin-field">
                                    <span>{{ field.label }}</span>
                                    <select v-if="field.type === 'select'" v-model="draft.content[field.key]">
                                        <option v-for="option in field.options" :key="option.value" :value="option.value">{{ option.label }}</option>
                                    </select>
                                    <textarea
                                        v-else-if="field.multiline"
                                        v-model="draft.content[field.key]"
                                        :rows="field.key === 'intro' ? 4 : 2"
                                    ></textarea>
                                    <input v-else v-model="draft.content[field.key]" :type="field.type || 'text'">
                                    <small v-if="field.help">{{ field.help }}</small>
                                </label>
                            </template>
                        </section>

                        <section v-if="draft.kind === 'chapter' && (draft.content.layout === 'list' || draft.content.items?.length)" class="editor-block">
                            <div class="entry-editor-heading">
                                <div>
                                    <h3 class="editor-block-title">List entries</h3>
                                    <p>Awards, exhibitions, publications — up to 12.</p>
                                </div>
                                <button type="button" class="admin-secondary" :disabled="(draft.content.items?.length ?? 0) >= 12" @click="addEntry">＋ Add entry</button>
                            </div>
                            <div v-for="(entry, index) in draft.content.items ?? []" :key="index" class="entry-card">
                                <div class="entry-card-title">
                                    <strong>Entry {{ String(index + 1).padStart(2, '0') }}</strong>
                                    <button type="button" class="text-danger" @click="draft.content.items.splice(index, 1)">Remove</button>
                                </div>
                                <label class="admin-field">
                                    <span>Category / year</span>
                                    <input v-model="entry.kicker" type="text">
                                </label>
                                <label class="admin-field">
                                    <span>Title</span>
                                    <input v-model="entry.title" type="text" required>
                                </label>
                                <label class="admin-field">
                                    <span>Description</span>
                                    <textarea v-model="entry.description" rows="2"></textarea>
                                </label>
                            </div>
                        </section>

                        <section v-if="draft.kind === 'chapter' && draft.content.layout === 'gallery'" class="editor-block">
                            <div class="entry-editor-heading">
                                <div>
                                    <h3 class="editor-block-title">Gallery photos · {{ draft.content.photos?.length ?? 0 }}/60</h3>
                                    <p>The first sits under the heading; the rest continue on the following pages, 2 per page.</p>
                                </div>
                                <button
                                    type="button"
                                    class="admin-secondary"
                                    :disabled="(draft.content.photos?.length ?? 0) >= 60"
                                    @click="addPhoto"
                                >＋ Add photo</button>
                            </div>
                            <div v-for="(photo, index) in draft.content.photos ?? []" :key="index" class="entry-card">
                                <div class="entry-card-title">
                                    <strong>Photo {{ String(index + 1).padStart(2, '0') }}</strong>
                                    <button type="button" class="text-danger" @click="draft.content.photos.splice(index, 1)">Remove</button>
                                </div>
                                <ImageField v-model="photo.image" label="Photo" required @error="showError" />
                                <label class="admin-field">
                                    <span>Photo description</span>
                                    <input v-model="photo.alt" type="text">
                                </label>
                                <label class="admin-field">
                                    <span>Caption</span>
                                    <input v-model="photo.caption" type="text">
                                </label>
                            </div>
                        </section>

                        <div v-if="draft.kind === 'back-cover'" class="fixed-page-note">
                            Back-cover text is edited in
                            <button type="button" class="inline-link" @click="navigate('book')">Book text</button>.
                        </div>

                        <div class="form-actions sticky-actions">
                            <button v-if="draft.kind === 'chapter' && !draft.isNew" type="button" class="danger-button delete-action" :disabled="saving" @click="removePage()">
                                🗑 Delete chapter
                            </button>
                            <button class="admin-primary" type="submit" :disabled="saving">
                                {{ saving ? 'Saving…' : 'Save page' }} <span>→</span>
                            </button>
                        </div>
                    </form>
                </article>
                <article v-else class="empty-editor">
                    <span>✳</span>
                    <h2>Select a page to edit</h2>
                    <p>Choose a page on the left, or create a new chapter.</p>
                </article>
            </section>
        </main>
    </div>
</template>
