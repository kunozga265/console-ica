<template>
    <AppLayout title="Dashboard">
        <template #heading>Dashboard</template>
        <template #breadcrumbs>

        </template>
        <template #actions>

            <div class="flex justify-center">
                <button @click="registerDialog = true"
                    class="block text-white bg_green_500 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center   "
                    type="button">
                    New Register +
                </button>
            </div>

            <DialogModal :show="registerDialog" @close="registerDialog = false">
                <template #title @close="newMemberDialog = false">
                    Create New Register
                </template>


                <template #content>
                    <form class="p-4 md:p-5">
                        <div class="">

                            <div class="mb-4">
                                <label for="category"
                                    class="block mb-2 text-sm font-medium text-gray-900 ">
                                    Service Name
                                </label>
                                <TextInput id="name" ref="textInput" v-model="form.name" type="text"
                                    class="mt-1 block w-full" autocomplete="new-service-name" />
                                <InputError :message="form.errors.name" class="mt-2" />
                            </div>
                            <div class="mb-4">
                                <label for="category"
                                    class="block mb-2 text-sm font-medium text-gray-900 ">Register
                                    is Under?</label>
                                <select v-model="form.ministryId" id="category"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5  ">
                                    <!--                                            <option selected="">Select ministry</option>-->
                                    <option v-for="(ministry, index) in ministries" :key="index" :value="ministry.id">{{
                                        ministry.name }}
                                    </option>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label for="date"
                                    class="block mb-2 text-sm font-medium text-gray-900 ">Service
                                    Date</label>
                                <div class="relative">

                                    <input v-model="form.date" type="date"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5"
                                        placeholder="Select date" required>
                                    <!--                                            <input @changedate="console.log('1')" v-model="date" datepicker id="default-datepicker" type="text" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5      " placeholder="Select date">-->
                                </div>
                            </div>


                        </div>

                    </form>
                </template>

                <template #footer>
                    <!-- <SecondaryButton @click="registerDialog = false">
                        Cancel
                    </SecondaryButton> -->

                    <!-- <PrimaryButton class="ms-3" :class="{ 'opacity-25': form.processing }" :disabled="form.processing"
                        @click="deleteUser">
                        Delete Account
                    </PrimaryButton> -->
                    <button @click="submit" type="button"
                        class="text-white inline-flex items-center bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center   ">
                        <svg class="me-1 -ms-1 w-5 h-5" fill="currentColor" viewBox="0 0 20 20"
                            xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd"
                                d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z"
                                clip-rule="evenodd"></path>
                        </svg>
                        Add new register
                    </button>
                </template>
            </DialogModal>





        </template>

        <div class="px-6 sm:p-8 md:px-10 lg:px-12">
            <div class=" sm:px-6 lg:px-8">


                <div class="overflow-hidden  sm:rounded-lg">

                    <div class="flex items-center mb-3">
                        <svg class="h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path
                                d="M3 10C3 10.5523 3.44772 11 4 11L12 11C12.5523 11 13 10.5523 13 10V4C13 3.44772 12.5523 3 12 3H4C3.44772 3 3 3.44772 3 4V10ZM11 20C11 20.5523 11.4477 21 12 21H20C20.5523 21 21 20.5523 21 20V14C21 13.4477 20.5523 13 20 13H12C11.4477 13 11 13.4477 11 14V20ZM13 15H19V19H13V15ZM3 20C3 20.5523 3.44772 21 4 21H8C8.55229 21 9 20.5523 9 20V14C9 13.4477 8.55229 13 8 13H4C3.44772 13 3 13.4477 3 14V20ZM5 19V15H7V19H5ZM5 9V5L11 5L11 9L5 9ZM20 11C20.5523 11 21 10.5523 21 10V4C21 3.44772 20.5523 3 20 3H16C15.4477 3 15 3.44772 15 4V10C15 10.5523 15.4477 11 16 11H20ZM19 9H17V5H19V9Z">
                            </path>
                        </svg>
                        <span class="ml-1 text-lg poppins-light">
                            Overview
                        </span>
                    </div>
                    <div class="border mb-8 p-6 lg:p-8 bg-white border-b border-gray-200">

                        <apexchart height="350" type="bar" :options="options" :series="series"></apexchart>

                    </div>


                    <div v-if="registersCompound.active.length > 0">
                        <div class="flex items-center mb-3">
                            <svg class="h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                <path
                                    d="M19 22H5C3.34315 22 2 20.6569 2 19V3C2 2.44772 2.44772 2 3 2H17C17.5523 2 18 2.44772 18 3V15H22V19C22 20.6569 20.6569 22 19 22ZM18 17V19C18 19.5523 18.4477 20 19 20C19.5523 20 20 19.5523 20 19V17H18ZM16 20V4H4V19C4 19.5523 4.44772 20 5 20H16ZM6 7H14V9H6V7ZM6 11H14V13H6V11ZM6 15H11V17H6V15Z">
                                </path>
                            </svg>
                            <span class="ml-1 text-lg poppins-light">
                                Active
                            </span>
                        </div>

                        <div class="mb-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3  gap-4">

                            <Link :href="route('registers.show', { 'code': register.code })"
                                v-for="(register, index) in registersCompound.active" :key="index"
                                class="mb-4 p-4 lg:p-6 rounded sm:rounded-lg yellow-card border border-yellow-100 flex justify-between">

                            <!-- <div>
                                <span class="date rounded py-1 px-2 bg-gray-200 text-gray-600 text-xs font-bold ">
                                    {{ register.ministry.name }}
                                </span>

                            </div> -->
                            <div>
                                <div class="text-lg ">
                                    {{ register.name }}
                                </div>


                                <div class="flex items-center">
                                    <div class="text-xs text-yellow-900">{{ useDate(register.date) }}</div>
                                    <div class="mx-2 h-1 w-1 bg-yellow-900 rounded-full"></div>
                                    <div class="text-xs text-yellow-900">{{ register.ministry.name }}</div>
                                    <!-- <div class="text-xs text-gray-500">{{ register.attendees.length }} {{ register.attendees.length
                                == 1 ?
                                "Attendee" : "Attendees"}}</div> -->
                                </div>
                            </div>
                            <div
                                class="bg_yellow_500 h-12 w-12 poppins-bold font-bold flex items-center justify-center rounded">
                                {{ register.attendees.length }}

                            </div>
                            </Link>

                        </div>
                    </div>

                    <div class="flex items-center mb-3">
                        <svg class="h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path
                                d="M21 4H7C5.89543 4 5 4.89543 5 6C5 7.10457 5.89543 8 7 8H21V21C21 21.5523 20.5523 22 20 22H7C4.79086 22 3 20.2091 3 18V6C3 3.79086 4.79086 2 7 2H20C20.5523 2 21 2.44772 21 3V4ZM5 18C5 19.1046 5.89543 20 7 20H19V10H7C6.27143 10 5.58835 9.80521 5 9.46487V18ZM20 7H7C6.44772 7 6 6.55228 6 6C6 5.44772 6.44772 5 7 5H20V7Z">
                            </path>
                        </svg>
                        <span class="ml-1 text-lg poppins-light">
                            Attendance Registers
                        </span>
                    </div>
                    <div class="relative overflow-x-auto shadow-md sm:rounded-lg border border-blue-100">
                        <table class="w-full text-sm text-left rtl:text-right text-gray-500 ">
                            <thead
                                class="text-xs text-gray-700 uppercase bg-blue-100  ">
                                <tr>
                                    <th scope="col" class="px-6 py-3">
                                        Date
                                    </th>
                                    <th scope="col" class="px-6 py-3">
                                        Service Name
                                    </th>
                                    <th scope="col" class="px-6 py-3">
                                        Ministry
                                    </th>
                                    <th scope="col" class="px-6 py-3">
                                        Attendance
                                    </th>
                                    <th scope="col" class="px-6 py-3">
                                        Visitors
                                    </th>
                                    <th scope="col" class="px-6 py-3">
                                        Converts
                                    </th>

                                </tr>
                            </thead>
                            <tbody>
                                <tr @click="navigateToRegister(register.code)"
                                    v-for="(register, index) in registersCompound.all" :key="index"
                                    class="bg-white hover:bg-blue-50 cursor-pointer border-b   border-blue-100">
                                    <td class="px-6 py-4">
                                        {{ useDate(register.date) }}
                                    </td>
                                    <th scope="row"
                                        class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap ">
                                        {{ register.name }}
                                    </th>
                                    <td class="px-6 py-4">
                                        {{ register.ministry.name }}
                                    </td>

                                    <td class="px-6 py-4">
                                        {{ register.attendees.length }}
                                    </td>

                                    <td class="px-6 py-4">
                                        {{ }}
                                    </td>

                                    <td class="px-6 py-4">
                                        {{ }}
                                    </td>

                                </tr>

                            </tbody>
                        </table>
                    </div>


                </div>
            </div>
        </div>


    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import DialogModal from '@/Components/DialogModal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

import { computed, ref } from 'vue';
import { useForm, Link, router } from "@inertiajs/vue3";
import InputLabel from "@/Components/InputLabel.vue";
import InputError from "@/Components/InputError.vue";
import TextInput from "@/Components/TextInput.vue";
import { useDate } from '@/Plugins/composables';

const props = defineProps({
    ministries: {
        type: Array,
        default: null,
    },
    registers: {
        type: Object,
        default: null,
    },
});

// const selectedMinistry = ref(1);
// const date = ref(null);
const registerDialog = ref(false);

const form = useForm({
    name: "",
    ministryId: 1,
    date: null,
});

const options = {
    chart: {
        type: 'area',
        stacked: false,
        // height: 350,
        zoom: {
            type: 'x',
            enabled: true,
            autoScaleYaxis: true
        },
        toolbar: {
            autoSelected: 'zoom'
        }
    },
    dataLabels: {
        enabled: false
    },
    markers: {
        size: 0,
    },

    fill: {
        colors: ['#4ec3c3', '#8fa3ee', "#ffe346", "#4285F4"],
        opacity: 0.9,
        type: 'solid',
        // type: 'gradient',
        // gradient: {
        //     shadeIntensity: 1,
        //     inverseColors: false,
        //     opacityFrom: 0.5,
        //     opacityTo: 0,
        //     stops: [0, 90, 100]
        // },

    },
    yaxis: {
        labels: {
            formatter: function (val) {
                // return (val / 1000000).toFixed(0);
                return val;
            },
        },

    },
    xaxis: {
        type: 'datetime',
    },
    tooltip: {
        shared: false,
        y: {
            formatter: function (val) {
                // console.log(val)
                // return (val / 1000000).toFixed(0)
                return val
            }
        }
    },
    plotOptions: {
        bar: {

            columnWidth: '100%',


        }
    }
};

const series = computed(() => {
    let series = [{
        name: 'Attendance',
        data: []
    }];

    for (let x in props.registers.data) {
        series[0].data.push({
            x: props.registers.data[x].date * 1000,
            y: props.registers.data[x].attendees.length
        })
    }

    return series;
})

const submit = () => {
    form
        .transform(data => ({
            ...data,
            ministry_id: form.ministryId,
        }))
        .post(route('registers.store'), {
            errorBag: 'register',
            preserveScroll: true,
            onSuccess: () => {
                form.reset()
                registerDialog.value = false
            },
            onError: () => {
                if (form.errors.ministry_id) {
                    // form.reset('password', 'password_confirmation');
                    // passwordInput.value.focus();
                }

                if (form.errors.date) {
                    // form.reset('current_password');
                    // currentPasswordInput.value.focus();
                }
            },
        });
};

const navigateToRegister = (code) => {
    router.get(route('registers.show', { 'code': code }))
};

// const proxyChecked = computed({
//     get() {
//         return props.checked;
//     },
//
//     set(val) {
//         emit('update:checked', val);
//     },
// });

const registersCompound = computed(() => {
    const active = props.registers.data.filter((register) => {
        return register.active == true
    })
    const all = props.registers.data.filter((register) => {
        return register.active == false
    })

    return {
        "active": active,
        "all": all,
    }
})

const googleLogin = () => {
    form.transform(data => ({
        ...data,

    })).get(route('auth.google'), {
        // onFinish: () => form.reset('password'),
    });
};
</script>