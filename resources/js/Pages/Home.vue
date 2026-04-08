<template>
    <AppLayout title="Home">
        <template #heading></template>
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
                                <label for="category" class="block mb-2 text-sm font-medium text-gray-900 ">
                                    Service Name
                                </label>
                                <TextInput id="name" ref="textInput" v-model="form.name" type="text"
                                    class="mt-1 block w-full" autocomplete="new-service-name" />
                                <InputError :message="form.errors.name" class="mt-2" />
                            </div>
                            <div class="mb-4">
                                <label for="category" class="block mb-2 text-sm font-medium text-gray-900 ">Register
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
                                <label for="date" class="block mb-2 text-sm font-medium text-gray-900 ">Service
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

        <!--**********************************
            Content body start
        ***********************************-->
        <div class="content-body">
            <!-- row -->
            <div class="container-fluid">
                <!-- Add Order -->
                <div class="modal fade" id="addOrderModalside">
                    <div class="modal-dialog modal-dialog-centered" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Add Event</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <form>
                                    <div class="form-group">
                                        <label class="text-black font-w500">Event Name</label>
                                        <input type="text" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label class="text-black font-w500">Event Date</label>
                                        <input type="date" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label class="text-black font-w500">Description</label>
                                        <input type="text" class="form-control">
                                    </div>

                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-danger light"
                                    data-bs-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-primary">Save changes</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-xl-9 col-xxl-8">
                        <div class="row">
                            <div class="col-xl-12">
                                <div v-if="sermon != null" class="card event-detail-bx overflow-hidden">
                                    <!-- <div class="card-media">
                                        <img src="images/hill.jpg" alt="" class="w-100">
                                    </div> -->
                                    <div class="card-body">
                                        <div class="flex justify-between mb-4">
                                            <div class="grow">

                                                <div
                                                    class="playfair-display-regular text-4xl text-black col-xl-6 p-0 col-xxl-12 me-auto title">
                                                    {{ sermon.title }}
                                                </div>
                                                <div class="text- text-black">{{ sermon.subtitle }}</div>
                                                <div class="text-">{{ sermon.series?.title }}</div>
                                                <div class="flex gap-x-2 items-center mt-3">
                                                    <Avatar :image="fileUrl(sermon.author.avatar)" class="mr-2"
                                                        size="large" shape="circle" />

                                                    <div>
                                                        <div class="text-sm text-black">{{ sermon.author.suffix }} {{
                                                            sermon.author.name }}</div>
                                                        <div class="text-xs">{{ sermon.author.title }}</div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div>

                                                <div class="d-flex ">

                                                    <a href="javascript:void(0)" class="share-icon me-3">
                                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                            xmlns="http://www.w3.org/2000/svg">
                                                            <path fill-rule="evenodd" clip-rule="evenodd"
                                                                d="M11 2H6C3.791 2 2 3.791 2 6V18C2 20.209 3.791 22 6 22H18C20.209 22 22 20.209 22 18C22 15.729 22 13 22 13C22 12.448 21.552 12 21 12C20.448 12 20 12.448 20 13V18C20 19.104 19.104 20 18 20C14.67 20 9.329 20 6 20C4.895 20 4 19.104 4 18C4 14.67 4 9.329 4 6C4 4.895 4.895 4 6 4H11C11.552 4 12 3.552 12 3C12 2.448 11.552 2 11 2ZM18.586 4H15C14.448 4 14 3.552 14 3C14 2.448 14.448 2 15 2H21C21.552 2 22 2.448 22 3V9C22 9.552 21.552 10 21 10C20.448 10 20 9.552 20 9V5.414L12.707 12.707C12.317 13.097 11.683 13.097 11.293 12.707C10.902 12.317 10.902 11.683 11.293 11.293L18.586 4Z"
                                                                fill="#FE634E" />
                                                        </svg>
                                                    </a>
                                                    <div class="dropdown">
                                                        <div class="share-icon" role="button" data-bs-toggle="dropdown"
                                                            aria-expanded="false">
                                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                                xmlns="http://www.w3.org/2000/svg">
                                                                <path
                                                                    d="M12 13C12.5523 13 13 12.5523 13 12C13 11.4477 12.5523 11 12 11C11.4477 11 11 11.4477 11 12C11 12.5523 11.4477 13 12 13Z"
                                                                    stroke="#FE634E" stroke-width="2"
                                                                    stroke-linecap="round" stroke-linejoin="round" />
                                                                <path
                                                                    d="M12 6C12.5523 6 13 5.55228 13 5C13 4.44772 12.5523 4 12 4C11.4477 4 11 4.44772 11 5C11 5.55228 11.4477 6 12 6Z"
                                                                    stroke="#FE634E" stroke-width="2"
                                                                    stroke-linecap="round" stroke-linejoin="round" />
                                                                <path
                                                                    d="M12 20C12.5523 20 13 19.5523 13 19C13 18.4477 12.5523 18 12 18C11.4477 18 11 18.4477 11 19C11 19.5523 11.4477 20 12 20Z"
                                                                    stroke="#FE634E" stroke-width="2"
                                                                    stroke-linecap="round" stroke-linejoin="round" />
                                                            </svg>
                                                        </div>
                                                        <div class="dropdown-menu dropdown-menu-end">
                                                            <a class="dropdown-item" href="javascript:void(0);">View
                                                                Detail</a>
                                                            <a class="dropdown-item" href="javascript:void(0);">Edit</a>
                                                            <a class="dropdown-item"
                                                                href="javascript:void(0);">Delete</a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="body-html short mb-4 text-black inter-regular fade-bottom"
                                            v-html="sermon.body"></div>
                                        <!-- <div class="w-full h-12"></div> -->

                                        <div class="flex">
                                            <a :href="route('login')"
                                                class="btn btn-primary cursor-pointer light flex gap-x-2 items-center">
                                                <span>Read More</span>
                                                <Icon width="15" icon="material-symbols:arrow-forward-ios-rounded" />
                                            </a>
                                        </div>

                                    </div>
                                </div>


                            </div>
                            <div class="col-xl-12">
                                <div class="card">
                                    <div class="card-header border-0 pb-sm-0 pb-5">
                                        <div class="flex items-center gap-x-1 text-muted">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                                fill="currentColor" width="20" height="20">
                                                <path
                                                    d="M21 4H7C5.89543 4 5 4.89543 5 6C5 7.10457 5.89543 8 7 8H21V21C21 21.5523 20.5523 22 20 22H7C4.79086 22 3 20.2091 3 18V6C3 3.79086 4.79086 2 7 2H20C20.5523 2 21 2.44772 21 3V4ZM5 18C5 19.1046 5.89543 20 7 20H19V10H7C6.27143 10 5.58835 9.80521 5 9.46487V18ZM20 7H7C6.44772 7 6 6.55228 6 6C6 5.44772 6.44772 5 7 5H20V7Z">
                                                </path>
                                            </svg>
                                            <h4 class="fs-16 poppins-regular text-muted">Lastest Sermons</h4>
                                        </div>
                                        <div class="dropdown custom-dropdown c-pointer mb-0">
                                            <div data-bs-toggle="dropdown">
                                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                    xmlns="http://www.w3.org/2000/svg">
                                                    <path
                                                        d="M12 12.9999C12.5523 12.9999 13 12.5522 13 11.9999C13 11.4477 12.5523 10.9999 12 10.9999C11.4477 10.9999 11 11.4477 11 11.9999C11 12.5522 11.4477 12.9999 12 12.9999Z"
                                                        stroke="#7E7E7E" stroke-width="2" stroke-linecap="round"
                                                        stroke-linejoin="round" />
                                                    <path
                                                        d="M12 5.99994C12.5523 5.99994 13 5.55222 13 4.99994C13 4.44765 12.5523 3.99994 12 3.99994C11.4477 3.99994 11 4.44765 11 4.99994C11 5.55222 11.4477 5.99994 12 5.99994Z"
                                                        stroke="#7E7E7E" stroke-width="2" stroke-linecap="round"
                                                        stroke-linejoin="round" />
                                                    <path
                                                        d="M12 19.9999C12.5523 19.9999 13 19.5522 13 18.9999C13 18.4477 12.5523 17.9999 12 17.9999C11.4477 17.9999 11 18.4477 11 18.9999C11 19.5522 11.4477 19.9999 12 19.9999Z"
                                                        stroke="#7E7E7E" stroke-width="2" stroke-linecap="round"
                                                        stroke-linejoin="round" />
                                                </svg>
                                            </div>
                                            <div class="dropdown-menu dropdown-menu-end">
                                                <a class="dropdown-item" href="javascript:void(0);">Details</a>
                                                <a class="dropdown-item text-danger"
                                                    href="javascript:void(0);">Cancel</a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <Carousel v-bind="carouselConfig">
                                            <Slide v-for="(sermon, index) in latestSermons" :key="index">
                                                <div class="carousel__item">
                                                    <div class="sermon-card">
                                                        <div class="flex items-center justify-between gap-x-2  text-xs">
                                                            <div class="flex gap-x-1">
                                                                <Icon width="15"
                                                                    icon="material-symbols:folder-outline-rounded" />
                                                                <div>Main Church</div>
                                                            </div>

                                                            <div class="text-black">{{ useDate(sermon.published_at) }}
                                                            </div>


                                                        </div>
                                                        <div class="inter-bold text-lg text-black mb-1 truncate">{{
                                                            sermon.title
                                                            }}
                                                        </div>
                                                        <div class="flex gap-x-2 items-center">
                                                            <Avatar :image="fileUrl(sermon.author.avatar)" class="mr-2"
                                                                shape="circle" />

                                                            <div>
                                                                <div class="text-xs text-black">{{ sermon.author.suffix
                                                                }} {{
                                                                        sermon.author.name }}</div>
                                                                <div class="text-xxs">{{ sermon.author.title }}</div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                </div>
                                            </Slide>

                                            <template #addons>
                                                <Navigation />
                                                <!-- <Pagination /> -->
                                            </template>
                                        </Carousel>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="col-xl-3 col-xxl-4">
                        <div class="row">
                            <!-- <div class="col-xl-12 col-sm-6">
                                <div class="card">
                                    <div class="card-header border-0 pb-0">
                                        <h4 class="fs-20 text-black">Events</h4>
                                    </div>
                                    <div class="card-body pt-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="fs-14">Tuesday</span>
                                            <span class="text-black fs-18 font-w500">215,523 pcs</span>
                                        </div>
                                        <div id="radialBar"></div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <p class="fs-28 text-black font-w600 mb-1">25 Left</p>
                                                <span>Available Ticket</span>
                                            </div>
                                            <div class="d-inline-block ms-auto position-relative donut-chart-sale">
                                                <span class="donut2"
                                                    data-peity='{ "fill": ["rgb(254, 99, 78)", "rgba(244, 244, 244, 1)"],   "innerRadius": 26, "radius": 10}'>5/8</span>
                                                <small class="text-primary">65%</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div> -->

                            <div v-if="prayerPoints != null" class="col-xl-12 col-sm-6">
                                <div class="card secondary-container">
                                    <div class="card-body">
                                        <div class="space-y-2">
                                            <div class="flex justify-start items-center gap-x-1">
                                                <p class="fs-14 poppins-regular ">Prayer Points </p>
                                                <div class=" text-xs">
                                                    - {{ getPrayerPointDate(prayerPoints.data.date) }}
                                                </div>

                                            </div>

                                            <div>
                                                <div class="text-sm text-black inter-medium">{{ prayerPoints.data.title
                                                    }}</div>
                                                <div class="text-xs inter-regular">{{ prayerPoints.data.verses }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-if="events.data.length > 0" class="col-xl-12 col-sm-6">
                                <div class="card">
                                    <div class="card-body">
                                        <div class=" flex justify-start items-center gap-x-1 mb-2">
                                            <p class="fs-14 poppins-regular ">Upcoming Events</p>
                                        </div>

                                        <div class=" divide-y">
                                            <div v-for="event in events.data" class=" flex items-center gap-x-2 py-2.5">
                                                <div
                                                    class="rounded tertiary-container text-center h-12 w-12 flex justify-center items-center">
                                                    <div>
                                                        <div class="text-xxs font-bold">{{ getMonth(event.start_date) }}
                                                        </div>
                                                        <div class="text-base font-bold">{{ getDay(event.start_date) }}
                                                        </div>
                                                    </div>
                                                    <!-- <div class="text-xs">{{ getYear(event.start_date) }}</div> -->
                                                </div>

                                                <div>
                                                    <div class="text-sm text-black">{{ event.title }}</div>
                                                    <div class="text-xs">{{ event.venue }}</div>
                                                    <!-- <div class="text-xs">{{ getDuration(event.start_date, event.end_date) }}</div> -->
                                                    <div class="text-xs">{{ event.time }}</div>

                                                </div>
                                            </div>
                                        </div>




                                    </div>
                                </div>
                            </div>

                            <div v-if="birthdays?.data.length > 0" class="col-xl-12 col-sm-6">
                                <div class="card">
                                    <div class="card-body">
                                        <div class=" flex justify-start items-center gap-x-1 mb-2">
                                            <!-- <svg width="12" height="12" version="1.1" id="_x36_" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 512 512" xml:space="preserve" fill="#000000"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <g> <path style="fill:none;" d="M284.549,127.723h0.814v0.321h79.817l6.322-6.322l-87.471,4.701 C284.223,126.851,284.383,127.288,284.549,127.723z"></path> <path style="opacity:0.07;fill:#040000;" d="M383.655,121.069c0.062-0.004,0.111-0.022,0.173-0.026 c2.141-0.125,4.124-0.447,5.983-0.905c0.638-0.158,1.19-0.397,1.795-0.592c1.216-0.39,2.394-0.81,3.464-1.341 c0.61-0.302,1.154-0.652,1.715-0.997c0.922-0.567,1.785-1.171,2.572-1.845c0.489-0.42,0.949-0.85,1.384-1.308 c0.706-0.744,1.316-1.541,1.876-2.379c0.33-0.494,0.676-0.97,0.953-1.495c0.519-0.979,0.894-2.028,1.225-3.104 c0.145-0.471,0.352-0.908,0.46-1.396c0.353-1.598,0.536-3.269,0.479-5.024c-0.006-0.199-0.072-0.413-0.084-0.614 c-0.082-1.398-0.234-2.82-0.585-4.293c-0.02,0.045-0.059,0.078-0.079,0.123c-0.431-1.857-1.09-3.762-1.937-5.7l-31.549,31.549 L383.655,121.069z"></path> <g> <g> <path style="fill:#FDEAD0;" d="M448.424,198.784v293.948c0,10.577-8.609,19.268-19.268,19.268H45.261 c-10.659,0-19.268-8.691-19.268-19.268V198.784c0-10.577,8.609-19.269,19.268-19.269h383.895 C439.815,179.515,448.424,188.206,448.424,198.784z"></path> <path style="opacity:0.1;fill:#191919;" d="M448.424,198.784v98.885H25.992v-98.885c0-10.577,8.609-19.269,19.268-19.269h383.895 C439.815,179.515,448.424,188.206,448.424,198.784z"></path> <path style="fill:#FDEAD0;" d="M474.416,157.623v73.221c0,2.788-0.41,5.493-1.147,8.117 c-3.198,11.233-12.955,19.761-24.844,21.237c-1.148,0.164-2.378,0.246-3.608,0.246H29.6c-1.23,0-2.46-0.082-3.607-0.246 C11.397,258.393,0,245.848,0,230.843v-73.221c0-16.235,13.283-29.6,29.6-29.6h415.217 C461.133,128.023,474.416,141.388,474.416,157.623z"></path> <linearGradient id="SVGID_1_" gradientUnits="userSpaceOnUse" x1="188.4222" y1="135.2383" x2="285.9946" y2="135.2383"> <stop offset="0.0338" style="stop-color:#B6274B"></stop> <stop offset="0.9903" style="stop-color:#B52323"></stop> </linearGradient> <path style="fill:url(#SVGID_1_);" d="M285.995,135.239c0,1.721-0.164,3.361-0.656,4.919 c-3.608,15.087-23.778,26.566-48.13,26.566c-24.352,0-44.523-11.479-48.13-26.566c-0.492-1.558-0.656-3.198-0.656-4.919 c0-1.722,0.164-3.362,0.656-4.92c0.164-0.82,0.328-1.558,0.656-2.296c0-0.164,0.082-0.246,0.164-0.328 c0.082-0.41,0.246-0.82,0.492-1.23c2.132-4.838,5.985-9.183,11.151-12.627c5.985-4.181,13.611-7.297,22.302-8.855 c4.182-0.82,8.691-1.23,13.365-1.23c4.674,0,9.183,0.41,13.365,1.23c8.691,1.558,16.317,4.674,22.303,8.855 c5.166,3.444,9.019,7.789,11.151,12.627c0.246,0.41,0.41,0.82,0.492,1.23c0.082,0.082,0.164,0.164,0.164,0.328 c0.328,0.738,0.492,1.476,0.656,2.296C285.831,131.877,285.995,133.516,285.995,135.239z"></path> <rect x="189.054" y="259.145" style="fill:#B82E4B;" width="96.309" height="252.643"></rect> <rect x="189.078" y="127.695" style="fill:#D63F62;" width="96.261" height="132.748"></rect> <g> <g> <linearGradient id="SVGID_2_" gradientUnits="userSpaceOnUse" x1="68.6858" y1="63.7493" x2="228.107" y2="63.7493"> <stop offset="0.0338" style="stop-color:#C43459"></stop> <stop offset="0.9903" style="stop-color:#C3302B"></stop> </linearGradient> <path style="fill:url(#SVGID_2_);" d="M228.107,116.298c0,2.542-0.82,4.755-2.46,6.559c-0.246,0.328-0.656,0.656-0.984,0.902 c-3.198,2.706-8.446,4.018-15.579,3.69l-18.694-0.984l-99.623-5.412c-7.79-0.41-13.775-3.116-17.547-7.379 c-0.82-0.984-1.558-1.968-2.213-3.034c-1.968-3.444-2.706-7.544-2.132-12.053c0-0.41,0.082-0.82,0.246-1.312 c0-0.492,0.082-0.984,0.328-1.476c0.492-2.378,1.394-4.756,2.624-7.215l38.291-74.697c8.609-16.891,25.418-18.612,37.307-3.853 l74.286,92.407c0.738,0.902,1.312,1.722,1.886,2.542c0.82,1.148,1.476,2.213,1.968,3.279c0.41,0.738,0.738,1.476,0.984,2.132 c0.328,0.738,0.574,1.476,0.738,2.132c0.164,0.656,0.328,1.312,0.41,1.968C228.025,115.068,228.107,115.724,228.107,116.298z"></path> <path style="opacity:0.07;fill:#191919;" d="M228.107,116.298c0,2.542-0.82,4.755-2.46,6.559 c-0.246,0.328-0.656,0.656-0.984,0.902c-3.198,2.706-8.446,4.018-15.579,3.69l-18.694-0.984l-99.623-5.412 c-7.79-0.41-13.775-3.116-17.547-7.379c-0.82-0.984-1.558-1.968-2.213-3.034c-1.968-3.444-2.706-7.544-2.132-12.053 c0-0.41,0.082-0.82,0.246-1.312c0-0.492,0.082-0.984,0.246-1.558l0.082,0.082c2.952,6.805,10.167,11.479,20.498,11.971 l111.594,6.067l6.723,0.328c8.937,0.492,15.005-1.804,17.547-5.904c0.41,0.738,0.738,1.476,0.984,2.132 c0.328,0.738,0.574,1.476,0.738,2.132c0.164,0.656,0.328,1.312,0.41,1.968C228.025,115.068,228.107,115.724,228.107,116.298z"></path> </g> <g> <linearGradient id="SVGID_3_" gradientUnits="userSpaceOnUse" x1="246.3099" y1="63.7815" x2="405.731" y2="63.7815"> <stop offset="0.0338" style="stop-color:#C43459"></stop> <stop offset="0.9903" style="stop-color:#C3302B"></stop> </linearGradient> <path style="fill:url(#SVGID_3_);" d="M403.41,110.64c-0.656,1.066-1.394,2.05-2.214,3.034 c-3.771,4.263-9.757,6.969-17.546,7.379l-99.623,5.412l-18.695,0.984c-7.133,0.328-12.381-0.984-15.579-3.69 c-0.328-0.246-0.738-0.574-0.984-0.902c-1.64-1.804-2.46-4.018-2.46-6.559c0-0.574,0.082-1.23,0.164-1.804 c0.082-0.656,0.246-1.312,0.41-1.968c0.164-0.656,0.41-1.394,0.738-2.132c0.246-0.656,0.574-1.394,0.984-2.132 c0.492-1.066,1.148-2.132,1.968-3.279c0.574-0.82,1.148-1.64,1.886-2.542l74.286-92.407c6.641-8.281,14.841-11.315,22.302-9.43 c5.822,1.394,11.233,5.822,15.005,13.283l1.804,3.526l36.487,71.171c1.23,2.46,2.132,4.837,2.624,7.215 c0.246,0.492,0.328,0.984,0.328,1.476c0.164,0.492,0.246,0.902,0.246,1.312C406.116,103.097,405.378,107.196,403.41,110.64z"></path> <path style="opacity:0.07;fill:#191919;" d="M265.291,127.429l118.363-6.363c16.167-0.867,24.668-11.666,21.413-25.316 c-2.986,6.851-10.196,11.493-20.559,12.051l-118.357,6.356c-8.969,0.481-14.985-1.791-17.567-5.888 C242.421,120.045,248.957,128.309,265.291,127.429z"></path> </g> </g> </g> <path style="opacity:0.03;fill:#191919;" d="M474.416,157.623v73.221c0,2.788-0.41,5.493-1.147,8.117 c-3.198,11.151-12.955,19.597-24.844,21.072v232.699c0,10.577-8.609,19.268-19.268,19.268H45.261 c-10.659,0-19.268-8.691-19.268-19.268v-25.5l163.085-163.086l6.478-6.477l37.225-37.225l80.928-80.928l51.492-51.492h79.616 C461.133,128.023,474.416,141.388,474.416,157.623z"></path> </g> </g> </g></svg> -->

                                            <Icon
                                                icon="streamline:shopping-gift-reward-box-social-present-gift-media-rating-bow" />
                                            <p class="fs-14 poppins-regular ">Birthdays</p>
                                        </div>

                                        <div class=" text-sm">
                                            <!-- <div v-if="birthdays.data.length < 6"> -->
                                            <span v-show="birthdays.data.length <= 5"
                                                v-for="(member, index) in birthdays.data">

                                                <a class="text-black font-medium" href="#">
                                                    {{ member.fullName
                                                    }}
                                                </a>

                                                <span class="mx-1"
                                                    v-if="(index < birthdays.data.length - 2) || birthdays.data.length > 5">,</span>
                                                <span class="mx-1"
                                                    v-else-if="index == birthdays.data.length - 2">&</span>
                                            </span>
                                            <span class="mx-1" v-if="birthdays.data.length > 5">& {{
                                                birthdays.data.length - 5
                                            }} more.</span>

                                            <!-- </div> -->

                                        </div>

                                    </div>
                                </div>
                            </div>

                            <!-- <div class="col-xl-12 col-sm-6">
                                <div class="card">
                                    <div class="card-header align-items-start pb-0 border-0">
                                        <div>
                                            <h4 class="fs-16 mb-0 text-black font-w600">Increase 25%</h4>
                                            <span class="fs-12">Comparisson</span>
                                        </div>
                                        <select class="form-control style-1 default-select ">
                                            <option>Daily</option>
                                            <option>Monthly</option>
                                            <option>Weekly</option>
                                        </select>
                                    </div>
                                    <div class="card-body pt-0">
                                        <canvas id="widgetChart1" height="50"></canvas>
                                    </div>
                                </div>
                            </div> -->

                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--**********************************
            Content body end
        ***********************************-->




    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import DialogModal from '@/Components/DialogModal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

import { computed, onMounted, ref } from 'vue';
import { useForm, Link, router } from "@inertiajs/vue3";
import InputLabel from "@/Components/InputLabel.vue";
import InputError from "@/Components/InputError.vue";
import TextInput from "@/Components/TextInput.vue";
import { fileUrl, getDay, getMonth, getPrayerPointDate, getYear, useDate } from '@/Plugins/composables';
import Avatar from 'primevue/avatar';
import Image from 'primevue/image';
import { Icon } from '@iconify/vue';
import 'vue3-carousel/carousel.css'
import { Carousel, Slide, Pagination, Navigation } from 'vue3-carousel'




const props = defineProps({
    ministries: {
        type: Array,
        default: [],
    },
    registers: {
        type: Array,
        default: [],
    },
    sermons: {
        type: Object,
    },
    prayerPoints: {
        type: Object,
    },
    events: {
        type: Object,
    },
    birthdays: {
        type: Object,
    },
});

// const selectedMinistry = ref(1);
// const date = ref(null);
const registerDialog = ref(false);
// const sermon = ref(null);

const form = useForm({
    name: "",
    ministryId: 1,
    date: null,
});

const carouselConfig = {
    itemsToShow: 1,
    breakpoints: {
        // 300px and up
        300: {
            itemsToShow: 1,
            snapAlign: 'center',
        },
        // 400px and up
        600: {
            itemsToShow: 1.5,
            snapAlign: 'start',
        },
        // 500px and up
        800: {
            itemsToShow: 2,
            snapAlign: 'start',
        },
        1000: {
            itemsToShow: 2.5,
            snapAlign: 'start',
        },
        1200: {
            itemsToShow: 2,
            snapAlign: 'start',
        },
        1600: {
            itemsToShow: 2.5,
            snapAlign: 'start',
        },
        // 1100: {
        //     itemsToShow: 3,
        //     snapAlign: 'start',
        // },
    },
    // wrapAround: true
}
const eventCarouselConfig = {
    itemsToShow: 2,
    // height: "260px",
    // wrapAround: true

    breakpoints: {
        // 300px and up
        300: {
            itemsToShow: 2,
            snapAlign: 'center',
        },
        // 400px and up
        400: {
            itemsToShow: 3,
            snapAlign: 'start',
        },
        // 500px and up
        500: {
            itemsToShow: 4,
            snapAlign: 'start',
        },
    },
}

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

onMounted(() => {
    // console(props.sermons.data.splice(0,1))
    // sermon.value = props.sermons.data.splice(0,1)

})

const sermon = computed(() => {
    return props.sermons.data[0];
})
const latestSermons = computed(() => {
    return props.sermons.data.filter(s => s.id != sermon.value.id);
})
const posters = computed(() => {
    return props.events.data.filter(e => e.image != null);
})

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
    const active = props.registers.filter((register) => {
        return register.active == true
    })
    const all = props.registers.filter((register) => {
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