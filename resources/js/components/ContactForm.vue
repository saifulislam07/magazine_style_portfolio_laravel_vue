<script setup>
import { ref } from 'vue';

const emptyForm = () => ({ name: '', email: '', message: '', website: '' });

const form = ref(emptyForm());
const sending = ref(false);
const errorMessage = ref('');
const sentMessage = ref('');

async function send() {
    if (sending.value) return;
    sending.value = true;
    errorMessage.value = '';

    try {
        const response = await fetch('/contact', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
            body: JSON.stringify(form.value),
        });
        const body = await response.json().catch(() => ({}));

        if (response.status === 429) throw new Error('Too many messages — please wait a minute and try again.');
        if (response.status === 419) throw new Error('This page has expired. Please refresh and try again.');
        if (!response.ok) {
            throw new Error(Object.values(body.errors ?? {})[0]?.[0] || body.message || 'Your message could not be sent.');
        }

        sentMessage.value = body.message;
        form.value = emptyForm();
    } catch (error) {
        errorMessage.value = error.message;
    } finally {
        sending.value = false;
    }
}
</script>

<template>
    <!-- Stop pointer events here so the page-flip library does not treat typing as a page drag. -->
    <div class="folio-contact-form" @mousedown.stop @touchstart.stop @keydown.stop>
        <div v-if="sentMessage" class="folio-contact-sent" role="status">
            <p>{{ sentMessage }}</p>
            <button type="button" @click="sentMessage = ''">Write another →</button>
        </div>

        <form v-else @submit.prevent="send">
            <div class="folio-contact-row">
                <label>
                    <span>Name</span>
                    <input v-model="form.name" type="text" autocomplete="name" maxlength="120" required>
                </label>
                <label>
                    <span>Email</span>
                    <input v-model="form.email" type="email" autocomplete="email" maxlength="254" required>
                </label>
            </div>
            <label>
                <span>Message</span>
                <textarea v-model="form.message" rows="3" minlength="5" maxlength="5000" required></textarea>
            </label>
            <label class="folio-contact-trap" aria-hidden="true">
                Website
                <input v-model="form.website" type="text" tabindex="-1" autocomplete="off">
            </label>
            <p v-if="errorMessage" class="folio-contact-error" role="alert">{{ errorMessage }}</p>
            <button class="folio-contact-submit" type="submit" :disabled="sending">
                {{ sending ? 'Sending…' : 'Send message →' }}
            </button>
        </form>
    </div>
</template>
