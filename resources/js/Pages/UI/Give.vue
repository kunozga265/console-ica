<script setup>
import UILayout from '@/Layouts/UILayout.vue';
import Icon from '@/Components/UI/Icon.vue';
import { copyText } from '@/Components/UI/helpers';
import { toast } from '@/Components/UI/useMemberActions';

/* Giving options are managed by admins in /admin/giving. */
defineProps({
    banks: { type: Array, default: () => [] }, // { id, name, accountName, accountNumber, branch, swiftCode, steps[] }
    mobile: { type: Array, default: () => [] }, // { id, name, accountName (merchant), accountNumber, steps[] }
});

const copy = (value) => {
    copyText(value);
    toast('Copied to clipboard');
};
const bankRows = (b) =>
    [
        ['Bank', b.name],
        ['Account name', b.accountName],
        ['Account number', b.accountNumber],
        ['Branch', b.branch],
        ['Swift / BIC', b.swiftCode],
    ].filter(([, v]) => v);
const DEFAULT_BANK_STEPS = [
    'Log in to your bank app or visit any branch.',
    'Add ICA using the account details provided.',
    'Use your name + “Tithe” or “Offering” as the reference.',
    'Keep your confirmation — you can request a giving statement anytime.',
];
</script>

<template>
    <UILayout title="Give" page="give">
        <template #feature>
            <div class="card card-pad feature mb-24" style="display: flex; gap: 18px; align-items: center; flex-wrap: wrap">
                <span class="ico" style="width: 48px; height: 48px; border-radius: 14px; background: rgba(255, 255, 255, 0.12); color: #ffdd57; display: grid; place-items: center; flex: none">
                    <Icon name="gift" />
                </span>
                <div class="grow" style="min-width: 220px">
                    <span class="eyebrow" style="color: #ffdd57">Give cheerfully</span>
                    <h3 style="font-size: 23px; margin-top: 4px">“Each of you should give what you have decided in your heart to give.”</h3>
                    <p class="small mt-8" style="color: rgba(255, 255, 255, 0.72)">2 Corinthians 9:7 · Thank you for partnering with the ministry.</p>
                </div>
            </div>
        </template>

        <div v-if="!banks.length && !mobile.length" class="panel mb-24">
            <div class="card card-pad empty-state">
                <Icon name="gift" />
                <h3>Giving details coming soon</h3>
                <p class="small muted">Please ask at the church office how to give in the meantime.</p>
            </div>
        </div>

        <div v-if="banks.length" class="panel mb-24">
            <section id="bank" class="section">
                <div class="section-head"><h2>Bank transfer</h2></div>
                <div v-for="b in banks" :key="b.id" class="grid cols-2 mb-20" style="align-items: start">
                    <div class="card card-pad">
                        <h3 style="font-size: 16px" class="mb-8">{{ b.name }}</h3>
                        <div v-for="[label, value] in bankRows(b)" :key="label" class="copyrow">
                            <div>
                                <div class="k">{{ label }}</div>
                                <div class="v">{{ value }}</div>
                            </div>
                            <button class="btn btn-soft btn-sm btn-icon cpy" aria-label="Copy" @click="copy(value)"><Icon name="copy" /></button>
                        </div>
                    </div>
                    <div class="card card-pad">
                        <h3 style="font-size: 16px" class="mb-12">How to give</h3>
                        <ol class="steps">
                            <li v-for="(s, i) in b.steps.length ? b.steps : DEFAULT_BANK_STEPS" :key="i">{{ s }}</li>
                        </ol>
                    </div>
                </div>
            </section>
        </div>

        <div v-if="mobile.length" class="panel mb-24">
            <section id="mobile" class="section">
                <div class="section-head"><h2>Mobile money</h2></div>
                <div class="grid cols-2" style="align-items: start">
                    <div v-for="m in mobile" :key="m.id" class="card card-pad">
                        <div class="flex" style="gap: 12px">
                            <span class="ico ico-gold" style="width: 40px; height: 40px; border-radius: 12px; display: grid; place-items: center; flex: none"><Icon name="phone" /></span>
                            <div>
                                <h3 style="font-size: 16px">{{ m.name }}</h3>
                                <div v-if="m.accountName" class="small muted">Merchant: {{ m.accountName }}</div>
                            </div>
                        </div>
                        <div class="copyrow">
                            <div>
                                <div class="k">Pay to number</div>
                                <div class="v">{{ m.accountNumber }}</div>
                            </div>
                            <button class="btn btn-soft btn-sm btn-icon cpy" aria-label="Copy" @click="copy(m.accountNumber)"><Icon name="copy" /></button>
                        </div>
                        <ol class="steps mt-16">
                            <template v-if="m.steps.length"><li v-for="(s, i) in m.steps" :key="i">{{ s }}</li></template>
                            <template v-else>
                                <li>Dial your {{ m.name }} menu and choose “Pay merchant / bill”.</li>
                                <li>Enter the number above and the amount.</li>
                                <li>Confirm with your PIN and keep the SMS receipt.</li>
                            </template>
                        </ol>
                    </div>
                </div>
            </section>
        </div>
    </UILayout>
</template>
