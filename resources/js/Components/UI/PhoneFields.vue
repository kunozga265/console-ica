<script setup>
/* Airtel / TNM / international numbers — at least one is required (as in the mobile app). */
defineProps({ form: { type: Object, required: true } });
</script>

<template>
    <div class="field">
        <span>Phone number <span class="hint">· at least one</span></span>
        <div class="phone-set">
            <input v-model="form.phoneNumberAirtel" type="tel" inputmode="tel" placeholder="Airtel e.g. 0991 234 567" autocomplete="tel" />
            <input v-model="form.phoneNumberTnm" type="tel" inputmode="tel" placeholder="TNM e.g. 0881 234 567" />
            <input v-model="form.phoneNumberInternational" type="tel" inputmode="tel" placeholder="International e.g. +44 7700 900123" />
        </div>
        <span v-if="form.errors.phoneNumberAirtel" class="err">{{ form.errors.phoneNumberAirtel }}</span>
    </div>
</template>
