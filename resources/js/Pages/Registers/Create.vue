<template>
    <AppLayout title="Register - Create">

        <template #heading>{{ register.data.name }}</template>
        <template #breadcrumbs>
            <li>
                <div class="flex items-center">
                    <svg class="rtl:rotate-180 block w-3 h-3 mx-1 text-gray-400 " aria-hidden="true"
                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="m1 9 4-4-4-4" />
                    </svg>
                    <a href="#"
                        class="ms-1 text-xs font-medium text-gray-700 hover:text-blue-600 md:ms-2 dark:text-gray-400 dark:hover:text-white">Registers</a>
                </div>
            </li>
            <li aria-current="page">
                <div class="flex items-center">
                    <svg class="rtl:rotate-180  w-3 h-3 mx-1 text-gray-400" aria-hidden="true"
                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="m1 9 4-4-4-4" />
                    </svg>
                    <span class="ms-1 text-xs font-medium text-gray-500 md:ms-2 dark:text-gray-400">{{
                        useDate(register.data.date) }}</span>
                </div>
            </li>
        </template>
        <template #actions>

            <label class="inline-flex items-center cursor-pointer mx-6">
                <input type="checkbox" v-model="active" class="sr-only peer">
                <div
                    class="relative w-16 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600 dark:peer-checked:bg-blue-600">
                </div>
                <span class="ms-3 text-sm font-medium text-gray-900 dark:text-gray-300">Active</span>
            </label>

            <div class="flex justify-center">
                <button @click="newMemberDialog = true"
                    class="block text-white bg_green_500 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800"
                    type="button">
                    New Member +
                </button>
            </div>

            <DialogModal :show="newMemberDialog" @close="newMemberDialog = false">
                <template #title @close="newMemberDialog = false">
                    Member Details
                </template>

                <template #content>
                  
                </template>

                <template #footer>

                    <button @click="submit" type="button" v-show="newMemberValidation"
                        class="text-white inline-flex items-center bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                        <svg class="me-1 -ms-1 w-5 h-5" fill="currentColor" viewBox="0 0 20 20"
                            xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd"
                                d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z"
                                clip-rule="evenodd"></path>
                        </svg>
                        Add new member
                    </button>
                </template>
            </DialogModal>
        </template>

        <div class="px-6 sm:px-8 md:px-10 lg:px-12">
            <div class="">

                <div class="py-1.5 px-3 border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">

                                        <AutoComplete v-model="selectedCell" option-label="name" :suggestions="filteredCells" @complete="selectCell" @item-select="select"   />
                                        
                                    </div>
                 

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div class="lg:col-span-2 bg-white  mb-4 px-6 lg:px-8 border-b border-gray-200">


                        <form class="flex items-center mb-4">
                            <label for="simple-search" class="sr-only">Search</label>
                            <div class="relative w-full">
                                <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true"
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                            stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z" />
                                    </svg>
                                </div>
                                <input type="text" id="simple-search" v-model="form.search"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                    placeholder="Search" required />
                            </div>
                            <!-- <button type="submit" disabled
                                class="p-2.5 ms-2 text-sm font-medium text-white bg_green_500 rounded-lg border  focus:ring-4 focus:outline-none focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                                <svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 20 20">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z" />
                                </svg>
                                <span class="sr-only">Search</span>
                            </button> -->
                        </form>



                        <div class="relative overflow-x-auto">
                            <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                                <thead class="text-xs text-gray-700 uppercase  dark:bg-gray-700 dark:text-gray-400">
                                    <tr>
                                        <th v-show="active" scope="col" class="px-6 py-3">

                                        </th>
                                        <th scope="col" class="px-6 py-3">
                                            First Name
                                        </th>
                                        <th scope="col" class="px-6 py-3">
                                            Last Name
                                        </th>
                                        <th scope="col" class="px-6 py-3">
                                            Phone Number
                                        </th>
                                        <th scope="col" class="px-6 py-3">
                                            Cell
                                        </th>
                                        <th scope="col" class="px-6 py-3">
                                            Zone
                                        </th>

                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(member, index) in filteredMembers" :key="index"
                                        class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 border-gray-200">
                                        <td v-show="active" class="px-6 py-4" @click="submit(member.id)">
                                            <span v-if="form.processing && memberId == member.id" role="status">
                                                <svg aria-hidden="true"
                                                    class="inline w-8 h-8 text-gray-200 animate-spin dark:text-gray-600 fill-blue-600"
                                                    viewBox="0 0 100 101" fill="none"
                                                    xmlns="http://www.w3.org/2000/svg">
                                                    <path
                                                        d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z"
                                                        fill="currentColor" />
                                                    <path
                                                        d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z"
                                                        fill="currentFill" />
                                                </svg>
                                                <span class="sr-only">Loading...</span>
                                            </span>
                                            <label v-else class="inline-flex items-center cursor-pointer">
                                                <input :checked="checkAttendance(member)" disabled type="checkbox"
                                                    value="" class="sr-only peer">
                                                <div
                                                    class="relative w-16 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600 dark:peer-checked:bg-blue-600">
                                                </div>
                                            </label>
                                        </td>
                                        <td class="px-6 py-4">
                                            {{ member.firstName }}
                                        </td>
                                        <th scope="row"
                                            class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                            {{ member.lastName }}
                                        </th>

                                        <td class="px-6 py-4">
                                            {{ getPhoneNumber(member) }}
                                        </td>

                                        <td class="px-6 py-4">
                                            {{ member.cell != null ? member.cell.name : "-" }}
                                        </td>

                                        <td class="px-6 py-4">
                                            {{ member.cell != null ? member.cell.zone.name : "-" }}
                                        </td>

                                    </tr>

                                </tbody>
                            </table>
                        </div>



                    </div>
                    <div>
                        <div class=" blue-card mb-4 p-6 lg:p-8 border-gray-200 rounded-lg">
                            <div class="mb-4">Summary Details</div>

                            <div class="flex items-end mb-2">
                                <div class="font-bold text-3xl">{{ register.data.attendees.length }}</div>
                                <!-- <div class="text-gray-500 ml-1">{{ register.data.attendees.length == 1 ? "Person"  : "People"}}</div> -->
                            </div>
                            <div class="mb-1 text-base font-medium text-green-700 dark:text-green-500">Male ({{
                                metrics.males.length }})</div>
                            <div class="w-full bg-white rounded-full h-2.5 mb-4 dark:bg-gray-700">
                                <div class="bg-green-600 h-2.5 rounded-full dark:bg-green-500"
                                    :style="{ 'width': metrics.mPercentage + '%' }"></div>
                            </div>
                            <div class="mb-1 text-base font-medium text-yellow-700 dark:text-yellow-500">Female ({{
                                metrics.females.length }})</div>
                            <div class="w-full bg-white rounded-full h-2.5 mb-4 dark:bg-gray-700">
                                <div class="bg-yellow-400 h-2.5 rounded-full"
                                    :style="{ 'width': metrics.fPercentage + '%' }">
                                </div>
                            </div>



                        </div>
                    </div>


                </div>

            </div>
        </div>


    </AppLayout>
</template>

<script setup>

import AppLayout from '@/Layouts/AppLayout.vue';
import DialogModal from '@/Components/DialogModal.vue';

import { computed, ref } from 'vue';
import { useForm } from "@inertiajs/vue3";
import InputLabel from "@/Components/InputLabel.vue";
import InputError from "@/Components/InputError.vue";
import TextInput from "@/Components/TextInput.vue";
import { useDate, fileUrl } from '@/Plugins/composables';

import AutoComplete from 'primevue/autocomplete';


const props = defineProps({
    register: {
        type: Object,
        default: null,
    },
    members: {
        type: Object,
        default: null,
    },
    cells: {
        type: Array,
        default: null,
    },
    publicPath: {
        type: String,
        default: null,
    },


});

const memberId = ref(0)
const errorMessage = ref("")
const filteredCells = ref([]);
const selectedCell = ref("");

const showDateOfBirth = ref(false);
const showLinkToCell = ref(false);
const showEmailAddress = ref(false);
const newMemberDialog = ref(false);
const active = ref(props.register.data.active);

const form = useForm({
    phoneNumberTnm: "",
    phoneNumberAirtel: "",
    phoneNumberInternational: "",
    firstName: "",
    lastName: "",
    gender: "",
    dateOfBirth: "",
    email: "",
    cellId: "",
});

const submit = (id) => {
    if (!form.processing) {
        memberId.value = id
        form
            .transform(data => ({
                ...data,
                member_id: id,
                register_id: props.register.data.id
            }))
            .post(route('registers.record-attendance'), {
                errorBag: 'register',
                preserveScroll: true,
                onSuccess: () => form.reset(),
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
    }
};

const getPhoneNumber = (member) => {
    if (member.phoneNumberAirtel) {
        return "+2659" + member.phoneNumberAirtel
    } else if (member.phoneNumberTnm) {
        return "+2658" + member.phoneNumberTnm
    } else if (member.phoneNumberInternational) {
        return "+" + member.phoneNumberInternational

    }
};
const checkAttendance = (member) => {
    const attendee = props.register.data.attendees.find(({ id }) => id === member.id);
    return attendee != null

};


const newMemberValidation = computed(() => {
    if (form.gender == "") {
        errorMessage.value = "Select Gender"
        return false
    } else if (form.firstName.length == 0 || form.firstName == "") {
        errorMessage.value = "Enter First Name"
        return false
    } else if (form.lastName.length == 0 || form.lastName == "") {
        errorMessage.value = "Enter Last Name"
        return false
    } else if (form.phoneNumberTnm.length == 0 && form.phoneNumberAirtel.length == 0 && form.phoneNumberInternational.length == 0) {
        errorMessage.value = "Enter at atleast one phone number"
        return false
    } else if (form.phoneNumberTnm.length > 0 && form.phoneNumberTnm.length < 11) {
        errorMessage.value = "Complete TNM number"
        return false
    } else if (form.phoneNumberAirtel.length > 0 && form.phoneNumberAirtel.length < 11) {
        errorMessage.value = "Complete Airtel number"
        return false
    } else if (form.phoneNumberInternational.length > 0 && form.phoneNumberInternational.length < 9) {
        errorMessage.value = "Complete International number"
        return false
    }
    return true
})

const filteredMembers = computed(() => {
    let filtered = []
    if (active.value) {
        filtered = props.members.data

        /* Filter Members By Code*/
        if (form.search.length !== 0) {
            filtered = (filtered).filter((member) => {
                return member.firstName.toLowerCase().includes(form.search.toLowerCase()) || member.lastName.toLowerCase().includes(form.search.toLowerCase())
            })
        }
    } else {
        filtered = props.register.data.attendees
    }

    return filtered
})

const metrics = computed(() => {
    const males = props.register.data.attendees.filter((attendee) => {
        return attendee.gender.toLowerCase() == "male"
    })
    const females = props.register.data.attendees.filter((attendee) => {
        return attendee.gender.toLowerCase() == "female"
    })

    return {
        "males": males,
        "mPercentage": (males.length / props.register.data.attendees.length) * 100,
        "females": females,
        "fPercentage": (females.length / props.register.data.attendees.length) * 100,
    }
})

const selectCell = (event) => {
    console.log(event)
    console.log(event.query)
        setTimeout(() => {
        if (!event.query.trim().length) {
            filteredCells.value = [...props.cells];
        } else {
            filteredCells.value = props.cells.filter((cell) => {
                return cell.name.toLowerCase().startsWith(event.query.toLowerCase());
            });
        }
    }, 250);
}
const select = (event) => {
    console.log(event.value.id)
    form.cellId = event.value.id
}


</script>
