<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    modelValue?: string | { start?: string; end?: string };
    editable?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: '',
    editable: true,
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();

// Parsing value modelValue (bisa string "09:00 - 12:00" atau JSON object)
const timeValues = computed<{ start: string; end: string }>(() => {
    if (!props.modelValue) return { start: '', end: '' };
    if (typeof props.modelValue === 'object') {
        return {
            start: props.modelValue.start || '',
            end: props.modelValue.end || '',
        };
    }
    const str = String(props.modelValue);
    // Format bisa "09:00 - 12:00" atau "09.00 – 12.00 WIB"
    const match = str.match(/(\d{1,2}[:.]\d{2})\s*(?:-|–|s\/d|to)\s*(\d{1,2}[:.]\d{2})/i);
    if (match) {
        return {
            start: match[1].replace('.', ':'),
            end: match[2].replace('.', ':'),
        };
    }
    return { start: '', end: '' };
});

function updateStart(val: string) {
    const end = timeValues.value.end;
    emitValue(val, end);
}

function updateEnd(val: string) {
    const start = timeValues.value.start;
    emitValue(start, val);
}

function emitValue(start: string, end: string) {
    if (!start && !end) {
        emit('update:modelValue', '');
        return;
    }
    const s = start || '00:00';
    const e = end || '00:00';
    emit('update:modelValue', `${s} - ${e}`);
}
</script>

<template>
    <div class="time-range-wrapper" v-if="editable">
        <div class="time-box">
            <span class="time-label">From</span>
            <input
                type="time"
                class="time-input"
                :value="timeValues.start"
                @input="updateStart(($event.target as HTMLInputElement).value)"
            />
        </div>

        <span class="time-separator">to</span>

        <div class="time-box">
            <span class="time-label">To</span>
            <input
                type="time"
                class="time-input"
                :value="timeValues.end"
                @input="updateEnd(($event.target as HTMLInputElement).value)"
            />
        </div>
    </div>
    <div class="time-range-display" v-else>
        <span class="clock-icon">&#128339;</span>
        <strong>{{ timeValues.start || '-' }}</strong> to <strong>{{ timeValues.end || '-' }}</strong>
    </div>
</template>

<style scoped>
.time-range-wrapper {
    display: flex;
    align-items: center;
    gap: 0.8rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    padding: 0.6rem 0.8rem;
    width: fit-content;
}

.time-box {
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
}

.time-label {
    font-size: 0.72rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.time-input {
    padding: 0.4rem 0.6rem;
    border: 1px solid #cbd5e1;
    border-radius: 4px;
    font-size: 0.95rem;
    font-family: inherit;
    background: #ffffff;
    color: #0f172a;
    outline: none;
}

.time-input:focus {
    border-color: var(--orange-600);
    box-shadow: 0 0 0 2px rgba(217, 107, 0, 0.15);
}

.time-separator {
    font-size: 0.85rem;
    font-weight: 600;
    color: #94a3b8;
    margin-top: 1rem;
}

.time-range-display {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.95rem;
    color: #334155;
    padding: 0.4rem 0;
}

.clock-icon {
    font-size: 1.1rem;
}
</style>
