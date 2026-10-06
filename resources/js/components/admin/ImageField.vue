<script setup>
import { ref } from 'vue';
import { uploadImage } from '../../admin/api';

const model = defineModel({ type: String, default: '' });
defineProps({
    label: { type: String, required: true },
    help: { type: String, default: '' },
    required: { type: Boolean, default: false },
});
const emit = defineEmits(['error']);

const uploading = ref(false);
const fileInput = ref(null);

async function upload(event) {
    const file = event.target.files?.[0];
    if (!file) return;

    uploading.value = true;
    try {
        model.value = await uploadImage(file);
    } catch (error) {
        emit('error', error.message);
    } finally {
        uploading.value = false;
        event.target.value = '';
    }
}
</script>

<template>
    <div class="admin-field image-field">
        <span>{{ label }}</span>
        <div class="image-field-row">
            <div class="image-field-preview" :class="{ empty: !model }">
                <img v-if="model" :src="model" alt="">
                <span v-else>No image</span>
            </div>
            <div class="image-field-inputs">
                <input v-model="model" type="url" placeholder="https://… or upload a file" :required="required">
                <div class="image-field-actions">
                    <button type="button" class="admin-secondary" :disabled="uploading" @click="fileInput.click()">
                        {{ uploading ? 'Uploading…' : '⇪ Upload image' }}
                    </button>
                    <button v-if="model" type="button" class="text-danger" @click="model = ''">Clear</button>
                </div>
                <input ref="fileInput" type="file" accept="image/jpeg,image/png,image/webp,image/gif" hidden @change="upload">
            </div>
        </div>
        <small v-if="help">{{ help }}</small>
    </div>
</template>
