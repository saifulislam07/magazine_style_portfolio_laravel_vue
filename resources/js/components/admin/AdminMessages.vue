<script setup>
import { computed, onMounted, ref } from 'vue';
import { request } from '../../admin/api';

const emit = defineEmits(['error', 'success', 'unread-changed']);

const messages = ref([]);
const loading = ref(true);
const filter = ref('all');
const openId = ref(null);

const visibleMessages = computed(() => messages.value.filter((message) => (
    filter.value === 'all' || (filter.value === 'unread' ? !message.isRead : message.isRead)
)));
const openMessage = computed(() => messages.value.find((message) => message.id === openId.value) ?? null);
const unreadCount = computed(() => messages.value.filter((message) => !message.isRead).length);

function formatDate(value) {
    return new Date(value).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });
}

function replaceMessage(updated) {
    const index = messages.value.findIndex((message) => message.id === updated.id);
    if (index !== -1) messages.value[index] = updated;
    emit('unread-changed', unreadCount.value);
}

async function setRead(message, isRead) {
    try {
        const result = await request(`/admin/api/messages/${message.id}`, {
            method: 'PATCH',
            body: JSON.stringify({ isRead }),
        });
        replaceMessage(result.message);
    } catch (error) {
        emit('error', error.message);
    }
}

async function open(message) {
    openId.value = message.id;
    if (!message.isRead) await setRead(message, true);
}

async function remove(message) {
    if (!window.confirm(`Delete the message from ${message.name}?`)) return;

    try {
        await request(`/admin/api/messages/${message.id}`, { method: 'DELETE' });
        messages.value = messages.value.filter((item) => item.id !== message.id);
        openId.value = null;
        emit('unread-changed', unreadCount.value);
        emit('success', 'Message deleted.');
    } catch (error) {
        emit('error', error.message);
    }
}

onMounted(async () => {
    try {
        messages.value = (await request('/admin/api/messages')).messages;
        emit('unread-changed', unreadCount.value);
    } catch (error) {
        emit('error', error.message);
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <div v-if="loading" class="admin-loading">Opening your inbox…</div>

    <section v-else class="inbox-layout">
        <aside class="inbox-list">
            <div class="segmented" role="tablist" aria-label="Filter messages">
                <button v-for="option in ['all', 'unread', 'read']" :key="option" type="button" :class="{ active: filter === option }" @click="filter = option">
                    {{ option === 'unread' ? `Unread (${unreadCount})` : option.charAt(0).toUpperCase() + option.slice(1) }}
                </button>
            </div>
            <p v-if="!visibleMessages.length" class="dash-empty">Nothing here yet.</p>
            <button
                v-for="message in visibleMessages"
                :key="message.id"
                type="button"
                class="inbox-item"
                :class="{ unread: !message.isRead, selected: openId === message.id }"
                @click="open(message)"
            >
                <span class="inbox-item-top">
                    <strong>{{ message.name }}</strong>
                    <small>{{ formatDate(message.receivedAt) }}</small>
                </span>
                <span class="inbox-item-subject">{{ message.subject || 'No subject' }}</span>
                <span class="inbox-item-preview">{{ message.message }}</span>
            </button>
        </aside>

        <article v-if="openMessage" class="inbox-reader">
            <header class="page-editor-heading">
                <div>
                    <p class="admin-eyebrow">{{ formatDate(openMessage.receivedAt).toUpperCase() }}</p>
                    <h2>{{ openMessage.subject || 'No subject' }}</h2>
                    <p class="inbox-from">{{ openMessage.name }} &lt;{{ openMessage.email }}&gt;</p>
                </div>
            </header>
            <div class="inbox-body">{{ openMessage.message }}</div>
            <div class="form-actions">
                <button type="button" class="text-danger delete-action" @click="remove(openMessage)">Delete</button>
                <button type="button" class="admin-secondary" @click="setRead(openMessage, !openMessage.isRead)">
                    Mark as {{ openMessage.isRead ? 'unread' : 'read' }}
                </button>
                <a class="admin-primary" :href="`mailto:${openMessage.email}?subject=${encodeURIComponent('Re: ' + (openMessage.subject || 'Your message'))}`">
                    Reply by email <span>→</span>
                </a>
            </div>
        </article>
        <article v-else class="empty-editor">
            <span>✉</span>
            <h2>Select a message</h2>
            <p>Messages sent through the contact form in your book land here.</p>
        </article>
    </section>
</template>
