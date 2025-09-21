<template>
    <AppLayout title="Register">

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
                        class="ms-1 text-xs font-medium text-gray-700 hover:text-blue-600 md:ms-2 ">Registers</a>
                </div>
            </li>
            <li aria-current="page">
                <div class="flex items-center">
                    <svg class="rtl:rotate-180  w-3 h-3 mx-1 text-gray-400" aria-hidden="true"
                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="m1 9 4-4-4-4" />
                    </svg>
                    <span class="ms-1 text-xs font-medium text-gray-500 md:ms-2 ">{{
                        useDate(register.data.date) }}</span>
                </div>
            </li>
        </template>
        <template #actions>

            <label class="inline-flex items-center cursor-pointer mx-6">
                <input type="checkbox" v-model="active" class="sr-only peer">
                <div
                    class="relative w-16 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all  peer-checked:bg-blue-600 ">
                </div>
                <span class="ms-3 text-sm font-medium text-gray-900 ">Active</span>
            </label>

            <div class="text-center">
                <button @click="newMemberDialog = true"
                    class="block text-white bg_green_500 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center   "
                    type="button">
                    + Add Member
                </button>
            </div>

            <AppDialogModal :show="newMemberDialog" @close="newMemberDialog = false">
                <template #title @close="newMemberDialog = false">
                    New Member
                </template>


                <template #content>
                    <form class="">
                        <div class="">

                            <div v-show="!newMemberValidation || !phoneValidation"
                                class="flex items-center w-full text-red-500 md:col-span-2 mb-4">
                                <div class="text-sm text-red-500">
                                    <i class="mdi mdi-alert-circle text-red-500"></i> {{
                                        errorMessage }}
                                </div>
                            </div>

                            <div class="mb-4">

                                <div class="flex">
                                    <div class="flex items-center me-4">
                                        <input v-model="form.gender" id="inline-radio" type="radio" value="Male"
                                            name="inline-radio-group"
                                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 ">
                                        <label for="inline-radio"
                                            class="ms-2 text-sm font-medium text-gray-900 ">Male</label>
                                    </div>
                                    <div class="flex items-center me-4">
                                        <input v-model="form.gender" id="inline-2-radio" type="radio" value="Female"
                                            name="inline-radio-group"
                                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 ">
                                        <label for="inline-2-radio"
                                            class="ms-2 text-sm font-medium text-gray-900 ">Female</label>
                                    </div>
                                </div>

                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-1 md:grid-cols-2 gap-2">
                                <div class="mb-4">
                                    <label for="category" class="block mb-2 text-sm font-medium text-gray-900 ">
                                        First Name
                                    </label>
                                    <TextInput id="name" ref="textInput" v-model="form.firstName" type="text"
                                        class="mt-1 block w-full" autocomplete="new-first-name" />
                                    <InputError :message="form.errors.firstName" class="mt-2" />
                                </div>
                                <div class="mb-4">
                                    <label for="category" class="block mb-2 text-sm font-medium text-gray-900 ">
                                        Last Name
                                    </label>
                                    <TextInput id="name" ref="textInput" v-model="form.lastName" type="text"
                                        class="mt-1 block w-full" autocomplete="new-last-name" />
                                    <InputError :message="form.errors.lastName" class="mt-2" />
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="date" class="block mb-2 text-sm font-medium text-gray-900 ">Phone
                                    Numbers</label>

                                <div class="grid grid-cols-1 sm:grid-cols-1 md:grid-cols-2 gap-2 ">
                                    <div class=" mb-2">
                                        <div class="text-xs text-gray-500">TNM Network</div>
                                        <div class="relative w-full">
                                            <div class="input-suffix">
                                                +265 8
                                            </div>
                                            <div class="absolute right-2 top-2">
                                                <img class="h-7 rounded" :src="fileUrl(publicPath, 'images/tnm.png')"
                                                    alt="">
                                            </div>
                                            <MaskInput
                                                class="block pl-14 p-2.5 w-full z-20 text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                                v-model="form.phoneNumberTnm" mask="########" />
                                            <!-- <input 
                                             type="text" id="phone-input" maxlength="8"
                                                class="block pl-14 p-2.5 w-full z-20 text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                               placeholder="88-888-888"
                                                v-model="form.phoneNumberTnm" /> -->

                                        </div>
                                    </div>
                                    <div class="mb-2">
                                        <div class="text-xs text-gray-500">Airtel Network</div>
                                        <div class="relative w-full">
                                            <div class="input-suffix">
                                                +265 9
                                            </div>
                                            <div class="absolute right-2 top-2">
                                                <img class="h-7 rounded" :src="fileUrl(publicPath, 'images/airtel.png')"
                                                    alt="">
                                            </div>
                                            <MaskInput
                                                class="block pl-14 p-2.5 w-full z-20 text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                                v-model="form.phoneNumberAirtel" mask="########" />


                                        </div>
                                    </div>
                                    <div class="mb-2">
                                        <div class="text-xs text-gray-500">International</div>
                                        <div class="relative w-full">
                                            <div class="input-suffix">
                                                +
                                            </div>
                                            <div class="absolute right-2 top-2">
                                                <img class="h-7 rounded" :src="fileUrl(publicPath, 'images/int.png')"
                                                    alt="">
                                            </div>
                                            <input type="text" id="phone-input" 
                                                class="block pl-5 p-2.5 w-full z-20 text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                                placeholder="" v-model="form.phoneNumberInternational" />


                                        </div>
                                    </div>


                                </div>
                            </div>

                            <div class="mb-4">
                                <!-- date of birth -->
                                <div v-show="showDateOfBirth" class="mb-2 ">
                                    <div @click="showDateOfBirth = false"
                                        class="flex cursor-pointer items-center hover:text-red-600">
                                        <svg width="12" height="12" fill="#000000" viewBox="0 0 24 24"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                            <g id="SVGRepo_tracerCarrier" stroke-linecap="round"
                                                stroke-linejoin="round">
                                            </g>
                                            <g fill="#ff0000" id="SVGRepo_iconCarrier">
                                                <path
                                                    d="M12,2A10,10,0,1,0,22,12,10,10,0,0,0,12,2Zm3.707,12.293a1,1,0,1,1-1.414,1.414L12,13.414,9.707,15.707a1,1,0,0,1-1.414-1.414L10.586,12,8.293,9.707A1,1,0,0,1,9.707,8.293L12,10.586l2.293-2.293a1,1,0,0,1,1.414,1.414L13.414,12Z">
                                                </path>
                                            </g>
                                        </svg>
                                        <label for="category"
                                            class="ml-1 block  text-sm font-medium text-gray-900  hover:text-red-600 cursor-pointer ">
                                            Date of birth
                                        </label>

                                    </div>

                                    <div
                                        class="py-2 px-3 border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">

                                        <DatePicker v-model="dateOfBirth" dateFormat="dd/mm/yy" />

                                    </div>

                                    <InputError :message="form.errors.dateOfBirth" class="mt-2" />
                                </div>

                                <!-- Link to cell -->
                                <div v-show="showLinkToCell" class="mb-2 ">
                                    <div @click="closeCell" class="flex cursor-pointer items-center hover:text-red-600">
                                        <svg width="12" height="12" fill="#000000" viewBox="0 0 24 24"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                            <g id="SVGRepo_tracerCarrier" stroke-linecap="round"
                                                stroke-linejoin="round">
                                            </g>
                                            <g fill="#ff0000" id="SVGRepo_iconCarrier">
                                                <path
                                                    d="M12,2A10,10,0,1,0,22,12,10,10,0,0,0,12,2Zm3.707,12.293a1,1,0,1,1-1.414,1.414L12,13.414,9.707,15.707a1,1,0,0,1-1.414-1.414L10.586,12,8.293,9.707A1,1,0,0,1,9.707,8.293L12,10.586l2.293-2.293a1,1,0,0,1,1.414,1.414L13.414,12Z">
                                                </path>
                                            </g>
                                        </svg>
                                        <label for="category"
                                            class="ml-1 block  text-sm font-medium text-gray-900  hover:text-red-600 cursor-pointer ">
                                            Link To Cell
                                        </label>

                                    </div>

                                    <SelectInput :items="cells" v-model="selectedCell" />
                                    <!-- <div
                                        class="py-2 px-3 border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">

                                        <AutoComplete v-model="selectedCell" option-label="name"
                                            :suggestions="filteredCells" @complete="selectCell" @item-select="select" />

                                    </div> -->
                                    <InputError :message="form.errors.cellId" class="mt-2" />

                                </div>

                                <!-- Email -->
                                <div v-show="showEmailAddress" class="mb-2 ">
                                    <div @click="showEmailAddress = false"
                                        class="flex cursor-pointer items-center hover:text-red-600">
                                        <svg width="12" height="12" fill="#000000" viewBox="0 0 24 24"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                            <g id="SVGRepo_tracerCarrier" stroke-linecap="round"
                                                stroke-linejoin="round">
                                            </g>
                                            <g fill="#ff0000" id="SVGRepo_iconCarrier">
                                                <path
                                                    d="M12,2A10,10,0,1,0,22,12,10,10,0,0,0,12,2Zm3.707,12.293a1,1,0,1,1-1.414,1.414L12,13.414,9.707,15.707a1,1,0,0,1-1.414-1.414L10.586,12,8.293,9.707A1,1,0,0,1,9.707,8.293L12,10.586l2.293-2.293a1,1,0,0,1,1.414,1.414L13.414,12Z">
                                                </path>
                                            </g>
                                        </svg>
                                        <label for="category"
                                            class="ml-1 block  text-sm font-medium text-gray-900  hover:text-red-600 cursor-pointer ">
                                            Email Address
                                        </label>

                                    </div>
                                    <TextInput id="name" ref="textInput" v-model="form.email" type="email"
                                        class="mt-1 block w-full" autocomplete="new-first-name" />
                                    <InputError :message="form.errors.email" class="mt-2" />
                                </div>
                            </div>
                        </div>

                    </form>

                    <div class="flex items-center mb-4">
                        <div v-show="!showDateOfBirth" @click="showDateOfBirth = true"
                            class="flex items-center mr-3 cursor-pointer mb-2">
                            <div>
                                <svg fill="4f4f4f" xmlns="http://www.w3.org/2000/svg" width="12" height="12"
                                    viewBox="0 0 24 24">
                                    <g fill="#1a7dff">
                                        <path
                                            d="M12 0c-6.627 0-12 5.373-12 12s5.373 12 12 12 12-5.373 12-12-5.373-12-12-12zm6 13h-5v5h-2v-5h-5v-2h5v-5h2v5h5v2z" />
                                    </g>
                                </svg>
                            </div>
                            <div class="ml-1 text-blue-600 text-sm">
                                Date of birth
                            </div>
                        </div>
                        <div v-show="!showLinkToCell" @click="showLinkToCell = true"
                            class="flex items-center mr-3 cursor-pointer mb-2">
                            <div>
                                <svg fill="4f4f4f" xmlns="http://www.w3.org/2000/svg" width="12" height="12"
                                    viewBox="0 0 24 24">
                                    <g fill="#1a7dff">
                                        <path
                                            d="M12 0c-6.627 0-12 5.373-12 12s5.373 12 12 12 12-5.373 12-12-5.373-12-12-12zm6 13h-5v5h-2v-5h-5v-2h5v-5h2v5h5v2z" />
                                    </g>
                                </svg>
                            </div>
                            <div class="ml-1 text-blue-600 text-sm">
                                Link to cell
                            </div>
                        </div>
                        <div v-show="!showEmailAddress" @click="showEmailAddress = true"
                            class="flex items-center mr-3 cursor-pointer mb-2">
                            <div>
                                <svg fill="4f4f4f" xmlns="http://www.w3.org/2000/svg" width="12" height="12"
                                    viewBox="0 0 24 24">
                                    <g fill="#1a7dff">
                                        <path
                                            d="M12 0c-6.627 0-12 5.373-12 12s5.373 12 12 12 12-5.373 12-12-5.373-12-12-12zm6 13h-5v5h-2v-5h-5v-2h5v-5h2v5h5v2z" />
                                    </g>
                                </svg>
                            </div>
                            <div class="ml-1 text-blue-600 text-sm">
                                Email Address
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center mb-4">
                        <input id="default-checkbox" type="checkbox" v-model="form.checkAttendance"
                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded-sm focus:ring-blue-500  focus:ring-2 ">
                        <label for="default-checkbox" class="ms-2 text-sm font-medium text-gray-900 ">Check
                            Attendance</label>
                    </div>


                </template>

                <template #footer>
                    <!-- <SecondaryButton @click="registerDialog = false">
                        Cancel
                    </SecondaryButton> -->

                    <!-- <PrimaryButton class="ms-3" :class="{ 'opacity-25': form.processing }" :disabled="form.processing"
                        @click="deleteUser">
                        Delete Account
                    </PrimaryButton> -->
                    <button @click="newMember" type="button" v-show="newMemberValidation"
                        class="text-white inline-flex items-center bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center ">
                        <svg class="me-1 -ms-1 w-5 h-5" fill="currentColor" viewBox="0 0 20 20"
                            xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd"
                                d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z"
                                clip-rule="evenodd"></path>
                        </svg>
                        Add new member
                    </button>
                </template>
            </AppDialogModal>



        </template>

        <div class="px-6 sm:px-8 md:px-10 lg:px-12">
            <div class="">


                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div class="lg:col-span-2 bg-white  mb-4 px-6 lg:px-8 border-b border-gray-200">


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
                                        class="bg-white border-b   border-gray-200">
                                        <td v-show="active" class="px-6 py-4" @click="submit(member.id)">
                                            <span v-if="form.processing && memberId == member.id" role="status">
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
                                                <input :checked="checkAttendance(member)" disabled type="checkbox"
                                                    value="" class="sr-only peer">
                                                <div
                                                    class="relative w-16 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300  rounded-full peer  peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all  peer-checked:bg-blue-600 ">
                                                </div>
                                            </label>
                                        </td>
                                        <td class="px-6 py-4">
                                            {{ member.firstName }}
                                        </td>
                                        <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap ">
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
                        <div class="bg-gray-50 mb-4 p-6 lg:p-8 border border-gray-300 rounded-lg">
                            <div class="mb-4">Summary Details</div>

                            <div class="flex items-end mb-2">
                                <div class="font-bold text-3xl">{{ register.data.attendees.length }}</div>
                                <!-- <div class="text-gray-500 ml-1">{{ register.data.attendees.length == 1 ? "Person"  : "People"}}</div> -->
                            </div>
                            <div class="mb-1 text-base font-medium text-green-700 ">Male ({{
                                metrics.males.length }})</div>
                            <div class="w-full bg-white rounded-full h-2.5 mb-4 ">
                                <div class="bg-green-600 h-2.5 rounded-full "
                                    :style="{ 'width': metrics.mPercentage + '%' }">
                                </div>
                            </div>
                            <div class="mb-1 text-base font-medium text-yellow-700 ">Female ({{
                                metrics.females.length }})</div>
                            <div class="w-full bg-white rounded-full h-2.5 mb-4 ">
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
import { useDate, fileUrl, getTimestampFromDate } from '@/Plugins/composables';

import AutoComplete from 'primevue/autocomplete';
import DatePicker from 'primevue/datepicker';
import AppDialogModal from '@/Components/AppDialogModal.vue';
import SelectInput from '@/Components/SelectInput.vue';



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
const search = ref("")
const errorMessage = ref("")
const filteredCells = ref([]);
const selectedCell = ref(null);

const phoneValidation = ref(true);
const showDateOfBirth = ref(false);
const showLinkToCell = ref(false);
const showEmailAddress = ref(false);
const newMemberDialog = ref(false);
const active = ref(props.register.data.active);
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
        if(tnm){
            errorMessage.value = "TNM number already exists"
            phoneValidation.value = false
            return 0
        }
        
        if(airtel){
            errorMessage.value = "Airtel number already exists"
            phoneValidation.value = false
            return 0
        }
        
        if(international){
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

const filteredMembers = computed(() => {
    let filtered = []
    if (active.value) {
        filtered = props.members.data

        /* Filter Members By Code*/
        if (search.length !== 0) {
            filtered = (filtered).filter((member) => {
                return member.firstName.toLowerCase().includes(search.value.toLowerCase()) || member.lastName.toLowerCase().includes(search.value.toLowerCase())
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


const checkPhoneNumber = (key, value) => {
    if (value != null && value.length > 0) {
        const exists = props.members.data.some(item => item[key] === value.replace(" ",""));

        console.log(key);
        console.log(value.toString().replace(" ",""));
        console.log(exists);
        return exists
    }else{
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
