<script setup>
import { computed, onMounted, ref } from 'vue';

const pages = ref([]);
const settings = ref({});
const activeSection = ref('pages');
const selectedId = ref(null);
const draft = ref(null);
const settingsDraft = ref({});
const loading = ref(true);
const saving = ref(false);
const errorMessage = ref('');
const successMessage = ref('');
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

const chapterPages = computed(() => pages.value.filter((page) => page.kind === 'chapter'));
const selectedIndex = computed(() => chapterPages.value.findIndex((page) => page.id === selectedId.value));

const settingsFields = [
    { key: 'brand', label: 'Book name' },
    { key: 'author', label: 'Photographer name' },
    { key: 'photography_label', label: 'Role shown in the book' },
    { key: 'cover_issue', label: 'Issue and year on the cover' },
    { key: 'cover_kicker', label: 'Cover subtitle' },
    { key: 'cover_title', label: 'Cover title', multiline: true, help: 'Use a new line to break the title.' },
    { key: 'cover_strapline', label: 'Cover footer line' },
    { key: 'cover_open_text', label: 'Cover prompt' },
    { key: 'back_cover_text', label: 'Back cover message', multiline: true },
    { key: 'back_cover_year', label: 'Back cover year' },
    { key: 'contact_kicker', label: 'Contact prompt' },
    { key: 'return_to_cover_text', label: 'Return-to-cover link text' },
    { key: 'site_description', label: 'Search result description', multiline: true },
];

const fieldsByKind = {
    cover: [
        { key: 'image', label: 'Cover image URL', type: 'url' },
        { key: 'imageAlt', label: 'Cover image description' },
    ],
    contents: [
        { key: 'section', label: 'Small heading' },
        { key: 'title', label: 'Page title' },
        { key: 'intro', label: 'Introduction', multiline: true },
    ],
    chapter: [
        { key: 'section', label: 'Small heading' },
        { key: 'title', label: 'Page title', multiline: true, help: 'Use a new line to break the title.' },
        { key: 'intro', label: 'Introduction', multiline: true },
        { key: 'note', label: 'Small note' },
        { key: 'number', label: 'Chapter number' },
        { key: 'layout', label: 'Page layout', type: 'select', options: ['image', 'list', 'contact'] },
        { key: 'image', label: 'Photo URL', type: 'url' },
        { key: 'imageAlt', label: 'Photo description' },
        { key: 'caption', label: 'Photo caption' },
        { key: 'email', label: 'Contact email', type: 'email' },
        { key: 'location', label: 'Contact location' },
    ],
    colophon: [
        { key: 'section', label: 'Small heading' },
        { key: 'title', label: 'Page title', multiline: true },
        { key: 'intro', label: 'Closing note', multiline: true },
        { key: 'signature', label: 'Signature' },
    ],
    'back-cover': [],
};

const activeFields = computed(() => fieldsByKind[draft.value?.kind] ?? []);

async function request(url, options = {}) {
    const response = await fetch(url, {
        credentials: 'same-origin',
        ...options,
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            ...options.headers,
        },
    });

    let body = {};
    try {
        body = await response.json();
    } catch {
        throw new Error('The server returned an unreadable response. Please refresh and try again.');
    }

    if (!response.ok) {
        const validationError = Object.values(body.errors ?? {})[0]?.[0];
        throw new Error(validationError || body.message || 'The request could not be completed.');
    }

    return body;
}

function selectPage(page) {
    selectedId.value = page.id;
    draft.value = JSON.parse(JSON.stringify(page));
    errorMessage.value = '';
    successMessage.value = '';
}

async function loadData() {
    loading.value = true;
    errorMessage.value = '';

    try {
        const data = await request('/admin/api/data');
        pages.value = data.pages;
        settings.value = data.settings;
        settingsDraft.value = { ...data.settings };

        if (selectedId.value) {
            const selected = pages.value.find((page) => page.id === selectedId.value);
            if (selected) selectPage(selected);
        }
    } catch (error) {
        errorMessage.value = error.message;
    } finally {
        loading.value = false;
    }
}

async function savePage() {
    if (!draft.value || saving.value) return;
    saving.value = true;
    errorMessage.value = '';
    successMessage.value = '';

    try {
        const body = {
            label: draft.value.label,
            content: draft.value.content,
        };
        const result = draft.value.isNew
            ? await request('/admin/api/pages', { method: 'POST', body: JSON.stringify(body) })
            : await request(`/admin/api/pages/${encodeURIComponent(draft.value.id)}`, {
                method: 'PUT',
                body: JSON.stringify(body),
            });
        const page = result.page;
        const existingIndex = pages.value.findIndex((item) => item.id === page.id);

        if (existingIndex === -1) pages.value.push(page);
        else pages.value[existingIndex] = page;

        pages.value.sort((a, b) => a.position - b.position);
        selectPage(page);
        successMessage.value = 'Page saved. Your book is now up to date.';
    } catch (error) {
        errorMessage.value = error.message;
    } finally {
        saving.value = false;
    }
}

function addPage() {
    activeSection.value = 'pages';
    const newPage = {
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
    selectedId.value = null;
    draft.value = newPage;
    errorMessage.value = '';
    successMessage.value = '';
}

async function removePage() {
    if (!draft.value || draft.value.kind !== 'chapter') return;
    if (!window.confirm(`Delete “${draft.value.label}” from the book?`)) return;

    saving.value = true;
    errorMessage.value = '';
    successMessage.value = '';

    try {
        await request(`/admin/api/pages/${encodeURIComponent(draft.value.id)}`, { method: 'DELETE' });
        pages.value = pages.value.filter((page) => page.id !== draft.value.id);
        selectedId.value = null;
        draft.value = null;
        successMessage.value = 'Chapter deleted.';
    } catch (error) {
        errorMessage.value = error.message;
    } finally {
        saving.value = false;
    }
}

async function moveChapter(offset) {
    const index = selectedIndex.value;
    const target = index + offset;
    if (index < 0 || target < 0 || target >= chapterPages.value.length || saving.value) return;

    const reordered = [...chapterPages.value];
    [reordered[index], reordered[target]] = [reordered[target], reordered[index]];
    saving.value = true;
    errorMessage.value = '';
    successMessage.value = '';

    try {
        const result = await request('/admin/api/pages/reorder', {
            method: 'PUT',
            body: JSON.stringify({ ids: reordered.map((page) => page.id) }),
        });
        pages.value = result.pages;
        selectPage(pages.value.find((page) => page.id === selectedId.value));
        successMessage.value = 'Chapter order updated.';
    } catch (error) {
        errorMessage.value = error.message;
    } finally {
        saving.value = false;
    }
}

async function saveSettings() {
    if (saving.value) return;
    saving.value = true;
    errorMessage.value = '';
    successMessage.value = '';

    try {
        const result = await request('/admin/api/settings', {
            method: 'PUT',
            body: JSON.stringify({ settings: settingsDraft.value }),
        });
        settings.value = result.settings;
        settingsDraft.value = { ...result.settings };
        successMessage.value = 'Book settings saved.';
    } catch (error) {
        errorMessage.value = error.message;
    } finally {
        saving.value = false;
    }
}

function addEntry() {
    draft.value.content.items ??= [];
    draft.value.content.items.push({ kicker: '', title: '', description: '' });
}

onMounted(loadData);
</script>

<template>
    <div class="admin-shell">
        <aside class="admin-sidebar">
            <a class="admin-brand" href="/">
                <span>F.</span> FIELDNOTES <small>STUDIO</small>
            </a>
            <p class="admin-sidebar-caption">YOUR PUBLICATION</p>
            <nav class="admin-nav" aria-label="Admin sections">
                <button :class="{ active: activeSection === 'pages' }" @click="activeSection = 'pages'">
                    <span class="nav-icon">▤</span> Book pages
                    <span class="nav-count">{{ pages.length }}</span>
                </button>
                <button :class="{ active: activeSection === 'settings' }" @click="activeSection = 'settings'">
                    <span class="nav-icon">⚙</span> Book settings
                </button>
            </nav>
            <div class="admin-sidebar-bottom">
                <a href="/" target="_blank" rel="noreferrer">View published book <span>↗</span></a>
                <form method="POST" action="/admin/logout">
                    <input type="hidden" name="_token" :value="csrfToken">
                    <button type="submit"><span class="nav-icon">↪</span> Sign out</button>
                </form>
            </div>
        </aside>

        <main class="admin-main">
            <header class="admin-topbar">
                <div>
                    <p class="admin-eyebrow">FIELDNOTES / STUDIO</p>
                    <h1>{{ activeSection === 'settings' ? 'Book settings' : 'Your book pages' }}</h1>
                </div>
                <button v-if="activeSection === 'pages'" class="admin-primary add-page-button" @click="addPage">
                    <span>＋</span> Add chapter
                </button>
            </header>

            <div v-if="errorMessage" class="admin-alert" role="alert">{{ errorMessage }}</div>
            <div v-if="successMessage" class="admin-success" role="status">{{ successMessage }}</div>
            <div v-if="loading" class="admin-loading">Opening your studio…</div>

            <section v-else-if="activeSection === 'settings'" class="settings-panel">
                <div class="panel-heading">
                    <div>
                        <p class="admin-eyebrow">THE DETAILS THAT TIE IT TOGETHER</p>
                        <h2>Publication identity</h2>
                        <p>These details appear on the cover, back cover, and inside pages.</p>
                    </div>
                </div>
                <form class="settings-form" @submit.prevent="saveSettings">
                    <label v-for="field in settingsFields" :key="field.key" class="admin-field">
                        <span>{{ field.label }}</span>
                        <textarea v-if="field.multiline" v-model="settingsDraft[field.key]" rows="3"></textarea>
                        <input v-else v-model="settingsDraft[field.key]" type="text">
                        <small v-if="field.help">{{ field.help }}</small>
                    </label>
                    <div class="form-actions">
                        <button class="admin-primary" type="submit" :disabled="saving">
                            {{ saving ? 'Saving…' : 'Save settings' }} <span>→</span>
                        </button>
                    </div>
                </form>
            </section>

            <section v-else-if="!loading" class="editor-layout">
                <aside class="page-list-panel">
                    <div class="page-list-title">
                        <span>BOOK STRUCTURE</span>
                        <span>{{ pages.length }} pages</span>
                    </div>
                    <button
                        v-for="page in pages"
                        :key="page.id"
                        class="page-list-item"
                        :class="{ selected: selectedId === page.id || draft?.id === page.id }"
                        @click="selectPage(page)"
                    >
                        <span class="page-list-number">{{ String(page.position + 1).padStart(2, '0') }}</span>
                        <span class="page-list-label">
                            <strong>{{ page.label || page.id }}</strong>
                            <small>{{ page.kind === 'chapter' ? 'CHAPTER' : page.kind.replace('-', ' ').toUpperCase() }}</small>
                        </span>
                        <span v-if="page.kind === 'cover' || page.kind === 'back-cover'" class="lock-mark">◆</span>
                    </button>
                </aside>

                <article v-if="draft" class="page-editor">
                    <div class="page-editor-heading">
                        <div>
                            <p class="admin-eyebrow">{{ draft.isNew ? 'NEW PAGE' : draft.kind.replace('-', ' ').toUpperCase() }}</p>
                            <h2>{{ draft.isNew ? 'Add a chapter' : draft.label }}</h2>
                        </div>
                        <div v-if="draft.kind === 'chapter' && !draft.isNew" class="reorder-actions">
                            <button aria-label="Move chapter up" :disabled="selectedIndex <= 0 || saving" @click="moveChapter(-1)">↑</button>
                            <button aria-label="Move chapter down" :disabled="selectedIndex < 0 || selectedIndex >= chapterPages.length - 1 || saving" @click="moveChapter(1)">↓</button>
                        </div>
                    </div>
                    <form class="page-form" @submit.prevent="savePage">
                        <label class="admin-field">
                            <span>Page label</span>
                            <input v-model="draft.label" type="text" maxlength="80" required>
                        </label>
                        <label v-for="field in activeFields" :key="field.key" class="admin-field">
                            <span>{{ field.label }}</span>
                            <select v-if="field.type === 'select'" v-model="draft.content[field.key]">
                                <option v-for="option in field.options" :key="option" :value="option">{{ option }}</option>
                            </select>
                            <textarea
                                v-else-if="field.multiline"
                                v-model="draft.content[field.key]"
                                :rows="field.key === 'intro' ? 4 : 2"
                            ></textarea>
                            <input
                                v-else
                                v-model="draft.content[field.key]"
                                :type="field.type || 'text'"
                            >
                            <small v-if="field.help">{{ field.help }}</small>
                        </label>

                        <section v-if="draft.kind === 'chapter' && draft.content.items" class="entry-editor">
                            <div class="entry-editor-heading">
                                <div>
                                    <span class="admin-eyebrow">LIST ENTRIES</span>
                                    <p>Used by achievements and publications pages.</p>
                                </div>
                                <button type="button" class="admin-secondary" @click="addEntry">＋ Add entry</button>
                            </div>
                            <div v-for="(entry, index) in draft.content.items" :key="index" class="entry-card">
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

                        <div v-if="draft.kind === 'back-cover'" class="fixed-page-note">
                            Back-cover text and publication details are edited in Book settings.
                        </div>

                        <div class="form-actions">
                            <button v-if="draft.kind === 'chapter' && !draft.isNew" type="button" class="text-danger delete-action" :disabled="saving" @click="removePage">
                                Delete chapter
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
