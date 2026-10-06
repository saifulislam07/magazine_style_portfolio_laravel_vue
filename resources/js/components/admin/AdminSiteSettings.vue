<script setup>
import { computed, ref, watch } from 'vue';
import { request } from '../../admin/api';
import ImageField from './ImageField.vue';

const props = defineProps({
    group: { type: String, required: true },
    settings: { type: Object, required: true },
    fallbackTitle: { type: String, default: '' },
    fallbackDescription: { type: String, default: '' },
});
const emit = defineEmits(['saved', 'error', 'success']);

const draft = ref({});
const saving = ref(false);
const testEmail = ref('');
const sendingTest = ref(false);

watch(() => [props.group, props.settings], () => {
    draft.value = JSON.parse(JSON.stringify(props.settings[props.group] ?? {}));
}, { immediate: true, deep: true });

/**
 * Each settings group is described here so the form, help text and layout stay in one place.
 */
const groups = {
    seo: {
        intro: 'Control how your site appears in Google, Bing, and when the link is shared.',
        sections: [
            {
                title: 'Search appearance',
                fields: [
                    { key: 'meta_title', label: 'Page title', limit: 60, help: 'Leave blank to use “Book name — Photographer name”.' },
                    { key: 'meta_description', label: 'Search description', multiline: true, limit: 160, help: 'Leave blank to use the description in Book text.' },
                    { key: 'meta_keywords', label: 'Keywords', help: 'Comma separated, e.g. documentary photographer, portraits, Dhaka.' },
                ],
            },
            {
                title: 'Social sharing',
                fields: [
                    { key: 'og_image', label: 'Share image', type: 'image', help: 'Shown on Facebook, WhatsApp, LinkedIn and X. 1200 × 630 works best. Defaults to the cover photo.' },
                    { key: 'twitter_handle', label: 'X (Twitter) username', placeholder: '@username' },
                ],
            },
            {
                title: 'Indexing & verification',
                fields: [
                    { key: 'allow_indexing', label: 'Let search engines index this site', type: 'toggle', help: 'Turn off while the site is still in progress.' },
                    { key: 'canonical_url', label: 'Main website address', type: 'url', placeholder: 'https://yourdomain.com', help: 'Used in the sitemap and canonical tag. Leave blank to use the current address.' },
                    { key: 'google_site_verification', label: 'Google Search Console code', help: 'Only the content value of the meta tag.' },
                    { key: 'bing_site_verification', label: 'Bing Webmaster code' },
                ],
            },
        ],
    },
    analytics: {
        intro: 'Measure who visits. The built-in counter works without any setup; Google tools give deeper reports.',
        sections: [
            {
                title: 'Google',
                fields: [
                    { key: 'ga4_measurement_id', label: 'Google Analytics 4 measurement ID', placeholder: 'G-XXXXXXXXXX', help: 'Analytics → Admin → Data streams → your web stream.' },
                    { key: 'gtm_container_id', label: 'Google Tag Manager container ID', placeholder: 'GTM-XXXXXXX', help: 'Optional. Use either GA4 or Tag Manager, not both for the same tag.' },
                ],
            },
            {
                title: 'Built-in counter',
                fields: [
                    { key: 'track_visits', label: 'Count daily visits on the dashboard', type: 'toggle', help: 'Stores only a daily total — no cookies or personal data.' },
                ],
            },
        ],
    },
    mail: {
        intro: 'Send contact form notifications through your own email account or provider.',
        sections: [
            {
                title: 'Delivery',
                fields: [
                    { key: 'mailer', label: 'Send emails using', type: 'select', options: [{ value: 'log', label: 'Nothing yet — write to the log file' }, { value: 'smtp', label: 'SMTP server' }] },
                ],
            },
            {
                title: 'SMTP server',
                when: (values) => values.mailer === 'smtp',
                fields: [
                    { key: 'host', label: 'SMTP host', placeholder: 'smtp.gmail.com' },
                    { key: 'port', label: 'Port', type: 'number', placeholder: '587' },
                    { key: 'encryption', label: 'Encryption', type: 'select', options: [{ value: 'tls', label: 'TLS / STARTTLS (port 587)' }, { value: 'ssl', label: 'SSL (port 465)' }, { value: 'none', label: 'None' }] },
                    { key: 'username', label: 'Username', autocomplete: 'off' },
                    { key: 'password', label: 'Password / app password', type: 'password' },
                    { key: 'from_address', label: 'Send from address', type: 'email', placeholder: 'studio@yourdomain.com' },
                    { key: 'from_name', label: 'Send from name' },
                ],
            },
        ],
    },
    contact: {
        intro: 'The contact page in your book gets a message form and links to your profiles.',
        sections: [
            {
                title: 'Contact form',
                fields: [
                    { key: 'form_enabled', label: 'Show the contact form', type: 'toggle' },
                    { key: 'recipient_email', label: 'Send new messages to', type: 'email', help: 'Every message is also kept in the Inbox.' },
                    { key: 'success_message', label: 'Thank-you message', multiline: true },
                ],
            },
            {
                title: 'Social profiles',
                fields: ['instagram', 'facebook', 'x', 'linkedin', 'youtube', 'behance', 'website'].map((key) => ({
                    key,
                    label: { x: 'X (Twitter)', linkedin: 'LinkedIn', youtube: 'YouTube' }[key] ?? key.charAt(0).toUpperCase() + key.slice(1),
                    type: 'url',
                    placeholder: 'https://',
                })),
            },
        ],
    },
};

const config = computed(() => groups[props.group]);
const visibleSections = computed(() => config.value.sections.filter((section) => !section.when || section.when(draft.value)));
const previewTitle = computed(() => draft.value.meta_title || props.fallbackTitle);
const previewDescription = computed(() => draft.value.meta_description || props.fallbackDescription);
const previewUrl = computed(() => draft.value.canonical_url || window.location.origin);

async function save() {
    if (saving.value) return;
    saving.value = true;

    try {
        const payload = { ...draft.value };
        delete payload.has_password;
        const result = await request(`/admin/api/site-settings/${props.group}`, {
            method: 'PUT',
            body: JSON.stringify({ settings: payload }),
        });
        emit('saved', result.settings);
        emit('success', 'Settings saved.');
    } catch (error) {
        emit('error', error.message);
    } finally {
        saving.value = false;
    }
}

async function sendTest() {
    sendingTest.value = true;

    try {
        const result = await request('/admin/api/mail/test', {
            method: 'POST',
            body: JSON.stringify({ email: testEmail.value || null }),
        });
        emit('success', result.message);
    } catch (error) {
        emit('error', error.message);
    } finally {
        sendingTest.value = false;
    }
}
</script>

<template>
    <section class="settings-stack">
        <p class="settings-intro">{{ config.intro }}</p>

        <article v-if="group === 'seo'" class="dash-card serp-preview" aria-label="Search result preview">
            <p class="admin-eyebrow">GOOGLE PREVIEW</p>
            <span class="serp-url">{{ previewUrl }}</span>
            <strong class="serp-title">{{ previewTitle }}</strong>
            <span class="serp-description">{{ previewDescription || 'Add a description so people know what they will find.' }}</span>
        </article>

        <form class="settings-stack" @submit.prevent="save">
            <fieldset v-for="section in visibleSections" :key="section.title" class="settings-panel settings-section">
                <legend class="panel-heading"><h2>{{ section.title }}</h2></legend>
                <div class="settings-form">
                    <template v-for="field in section.fields" :key="field.key">
                        <ImageField
                            v-if="field.type === 'image'"
                            v-model="draft[field.key]"
                            class="span-all"
                            :label="field.label"
                            :help="field.help"
                            @error="emit('error', $event)"
                        />
                        <label v-else-if="field.type === 'toggle'" class="toggle-field span-all">
                            <input v-model="draft[field.key]" type="checkbox">
                            <span class="toggle-switch" aria-hidden="true"></span>
                            <span>
                                <strong>{{ field.label }}</strong>
                                <small v-if="field.help">{{ field.help }}</small>
                            </span>
                        </label>
                        <label v-else class="admin-field" :class="{ 'span-all': field.multiline }">
                            <span>
                                {{ field.label }}
                                <em v-if="field.limit" class="char-count" :class="{ over: (draft[field.key]?.length ?? 0) > field.limit }">
                                    {{ draft[field.key]?.length ?? 0 }}/{{ field.limit }}
                                </em>
                            </span>
                            <select v-if="field.type === 'select'" v-model="draft[field.key]">
                                <option v-for="option in field.options" :key="option.value" :value="option.value">{{ option.label }}</option>
                            </select>
                            <textarea v-else-if="field.multiline" v-model="draft[field.key]" rows="3"></textarea>
                            <input
                                v-else
                                v-model="draft[field.key]"
                                :type="field.type || 'text'"
                                :placeholder="field.type === 'password' && settings.mail?.has_password ? 'Saved — leave blank to keep it' : field.placeholder"
                                :autocomplete="field.type === 'password' ? 'new-password' : field.autocomplete"
                            >
                            <small v-if="field.help">{{ field.help }}</small>
                        </label>
                    </template>
                </div>
            </fieldset>

            <div class="form-actions sticky-actions">
                <button class="admin-primary" type="submit" :disabled="saving">
                    {{ saving ? 'Saving…' : 'Save settings' }} <span>→</span>
                </button>
            </div>
        </form>

        <article v-if="group === 'mail'" class="settings-panel settings-section">
            <div class="panel-heading">
                <h2>Send a test email</h2>
                <p>Save first, then check that delivery works. Gmail needs an app password, not your normal password.</p>
            </div>
            <form class="test-mail-row" @submit.prevent="sendTest">
                <input v-model="testEmail" class="admin-input" type="email" placeholder="Send to (defaults to the contact recipient)">
                <button class="admin-secondary" type="submit" :disabled="sendingTest">
                    {{ sendingTest ? 'Sending…' : 'Send test' }}
                </button>
            </form>
        </article>
    </section>
</template>
