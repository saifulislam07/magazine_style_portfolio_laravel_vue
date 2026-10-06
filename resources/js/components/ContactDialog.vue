<script setup>
import { nextTick, ref, watch } from 'vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    kicker: { type: String, default: '' },
});
const emit = defineEmits(['close']);

const dialog = ref(null);
const form = ref({ name: '', email: '', subject: '', message: '', website: '' });
const sending = ref(false);
const errorMessage = ref('');
const sentMessage = ref('');

watch(() => props.open, async (isOpen) => {
    await nextTick();
    if (isOpen) {
        sentMessage.value = '';
        errorMessage.value = '';
        dialog.value?.showModal();
    } else {
        dialog.value?.close();
    }
});

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
        if (!response.ok) {
            throw new Error(Object.values(body.errors ?? {})[0]?.[0] || body.message || 'Your message could not be sent.');
        }

        sentMessage.value = body.message;
        form.value = { name: '', email: '', subject: '', message: '', website: '' };
    } catch (error) {
        errorMessage.value = error.message;
    } finally {
        sending.value = false;
    }
}
</script>

<template>
    <dialog ref="dialog" class="contact-dialog" aria-labelledby="contact-dialog-title" @close="emit('close')" @click.self="emit('close')">
        <div class="contact-dialog-card">
            <button type="button" class="contact-dialog-close" aria-label="Close" @click="emit('close')">✕</button>
            <p class="folio-eyebrow">{{ kicker }}</p>
            <h2 id="contact-dialog-title">Write to me.</h2>

            <div v-if="sentMessage" class="contact-dialog-sent" role="status">
                <p>{{ sentMessage }}</p>
                <button type="button" class="contact-dialog-submit" @click="emit('close')">Back to the book</button>
            </div>

            <form v-else @submit.prevent="send">
                <div class="contact-dialog-row">
                    <label>
                        <span>Your name</span>
                        <input v-model="form.name" type="text" autocomplete="name" maxlength="120" required>
                    </label>
                    <label>
                        <span>Email</span>
                        <input v-model="form.email" type="email" autocomplete="email" maxlength="254" required>
                    </label>
                </div>
                <label>
                    <span>Subject</span>
                    <input v-model="form.subject" type="text" maxlength="160">
                </label>
                <label>
                    <span>Message</span>
                    <textarea v-model="form.message" rows="5" minlength="5" maxlength="5000" required></textarea>
                </label>
                <label class="contact-dialog-trap" aria-hidden="true">
                    Website
                    <input v-model="form.website" type="text" tabindex="-1" autocomplete="off">
                </label>
                <p v-if="errorMessage" class="contact-dialog-error" role="alert">{{ errorMessage }}</p>
                <button class="contact-dialog-submit" type="submit" :disabled="sending">
                    {{ sending ? 'Sending…' : 'Send message' }} <span>→</span>
                </button>
            </form>
        </div>
    </dialog>
</template>
