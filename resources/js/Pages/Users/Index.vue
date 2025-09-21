<template>
    <AppLayout title="Users">

        <template #heading>Users</template>
        <template #breadcrumbs>

            <li aria-current="page">
                <div class="flex items-center">
                    <svg class="rtl:rotate-180  w-3 h-3 mx-1 text-gray-400" aria-hidden="true"
                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="m1 9 4-4-4-4" />
                    </svg>
                    <span class="ms-1 text-xs font-medium text-gray-500 md:ms-2 ">
                        Users</span>
                </div>
            </li>
        </template>
        <template #actions>



        </template>

        <div class="px-6 sm:px-8 md:px-10 lg:px-12">
            <div class="">


                <div class="">
                    <div class=" bg-white  mb-4 px-6 lg:px-8 border-b border-gray-200">


                        <form class="flex items-center mb-4">
                            <label for="simple-search" class="sr-only">Search</label>
                            <div class="relative w-full">
                                <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                                    <svg class="w-4 h-4 text-gray-500 " aria-hidden="true"
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                            stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z" />
                                    </svg>
                                </div>
                                <input type="text" id="simple-search" v-model="search"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5     "
                                    placeholder="Search" required />
                            </div>
                            <!-- <button type="submit" disabled
                                class="p-2.5 ms-2 text-sm font-medium text-white bg_green_500 rounded-lg border  focus:ring-4 focus:outline-none focus:ring-blue-300 ">
                                <svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 20 20">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z" />
                                </svg>
                                <span class="sr-only">Search</span>
                            </button> -->
                        </form>



                        <div class="relative overflow-x-auto">
                            <table class="w-full text-sm text-left rtl:text-right text-gray-500 ">
                                <thead class="text-xs text-gray-700 uppercase   ">
                                    <tr>
                                        <th scope="col" class="px-6 py-3">
                                            Admin Role
                                        </th>
                                        <th scope="col" class="px-6 py-3">
                                            First Name
                                        </th>
                                        <th scope="col" class="px-6 py-3">
                                            Last Name
                                        </th>
                                        <th scope="col" class="px-6 py-3">
                                            Email
                                        </th>


                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(user, index) in filteredUsers" :key="index"
                                        class="bg-white border-b   border-gray-200">
                                        <td v-if="user.role.name != 'super'"  class="px-6 py-4" @click="submit(user.id)">
                                            <span  v-if="form.processing && userId == user.id " role="status">
                                                <svg aria-hidden="true"
                                                    class="inline w-8 h-8 text-gray-200 animate-spin  fill-blue-600"
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
                                                <input :checked="user.isAdmin" disabled type="checkbox"
                                                    value="" class="sr-only peer">
                                                <div
                                                    class="relative w-16 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300  rounded-full peer  peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all  peer-checked:bg-blue-600 ">
                                                </div>
                                            </label>
                                        </td>
                                      <td v-else class="px-6 py-4">
                                            System Admin
                                        </td>
                                        <td class="px-6 py-4">
                                            {{ user.firstName }}
                                        </td>
                                        <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap ">
                                            {{ user.lastName }}
                                        </th>


                                        <td class="px-6 py-4">
                                            {{ user.email }}
                                        </td>


                                    </tr>

                                </tbody>
                            </table>
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
import { useDate, fileUrl, getTimestampFromDate } from '@/Plugins/composables';

import AutoComplete from 'primevue/autocomplete';
import DatePicker from 'primevue/datepicker';
import AppDialogModal from '@/Components/AppDialogModal.vue';
import SelectInput from '@/Components/SelectInput.vue';



const props = defineProps(["users"]);

const userId = ref(0)
const search = ref("")
const errorMessage = ref("")
const filteredCells = ref([]);
const selectedCell = ref(null);

const phoneValidation = ref(true);
const showDateOfBirth = ref(false);
const showLinkToCell = ref(false);
const showEmailAddress = ref(false);
const newMemberDialog = ref(false);

const dateOfBirth = ref(null);

const form = useForm({
    phoneNumberTnm: "",
    phoneNumberAirtel: "",
    phoneNumberInternational: "",
    firstName: "",
    lastName: "",
    gender: "",
    email: "",
    cellId: "",
    checkAttendance: false,
});

const submit = (id) => {
    if (!form.processing) {
        userId.value = id
        form
            .transform(data => ({
                ...data,
                user_id: id,
                
            }))
            .post(route('users.change-role'), {
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
const newMember = () => {

    if (!form.processing) {
        var dateOfBirth = null;
        if (dateOfBirth != null) {
            dateOfBirth = getTimestampFromDate(dateOfBirth)
        }

        const tnm = checkPhoneNumber("phoneNumberTnm", form.phoneNumberTnm)
        const airtel = checkPhoneNumber("phoneNumberAirtel", form.phoneNumberAirtel)
        const international = checkPhoneNumber("phoneNumberInternational", form.phoneNumberInternational)

        errorMessage.value = "";
        phoneValidation.value = true
        if (tnm) {
            errorMessage.value = "TNM number already exists"
            phoneValidation.value = false
            return 0
        }

        if (airtel) {
            errorMessage.value = "Airtel number already exists"
            phoneValidation.value = false
            return 0
        }

        if (international) {
            errorMessage.value = "International number already exists"
            phoneValidation.value = false
            return 0
        }

        form
            .transform(data => ({
                ...data,
                dateOfBirth: dateOfBirth,
                registerId: props.register.data.id,
                cellId: selectedCell.value != null ? selectedCell.value.id : null
            }))
            .post(route('members.store'), {
                errorBag: 'member',
                preserveScroll: true,
                onSuccess: () => {
                    form.reset()
                    showDateOfBirth.value = false
                    showEmailAddress.value = false
                    showLinkToCell.value = false
                    selectedCell.value = false
                    newMemberDialog.value = false
                },
                onError: () => {

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
    } else if (form.phoneNumberTnm.length > 0 && form.phoneNumberTnm.length < 8) {
        errorMessage.value = "Complete TNM number"
        return false
    } else if (form.phoneNumberAirtel.length > 0 && form.phoneNumberAirtel.length < 8) {
        errorMessage.value = "Complete Airtel number"
        return false
    } else if (form.phoneNumberInternational.length > 0 && form.phoneNumberInternational.length < 9) {
        errorMessage.value = "Complete International number"
        return false
    }
    return true
})

const filteredUsers = computed(() => {
    let filtered = []

    filtered = props.users.data

    /* Filter Members By Code*/
    if (search.length !== 0) {
        filtered = (filtered).filter((user) => {
            if(user.lastName != null){
                return user.firstName.toLowerCase().includes(search.value.toLowerCase()) || user.lastName.toLowerCase().includes(search.value.toLowerCase()) || user.email.toLowerCase().includes(search.value.toLowerCase())
            }
            return user.firstName.toLowerCase().includes(search.value.toLowerCase()) || user.email.toLowerCase().includes(search.value.toLowerCase())
        })
    }

   


    return filtered
})




const checkPhoneNumber = (key, value) => {
    if (value != null && value.length > 0) {
        const exists = props.members.data.some(item => item[key] === value.replace(" ", ""));

        console.log(key);
        console.log(value.toString().replace(" ", ""));
        console.log(exists);
        return exists
    } else {
        return false
    }
}
const closeCell = () => {
    showLinkToCell.value = false
    selectedCell.value = null
}
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
