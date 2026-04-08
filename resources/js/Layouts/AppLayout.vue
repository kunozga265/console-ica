<script setup>
import { nextTick, onMounted, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import ApplicationMark from '@/Components/ApplicationMark.vue';
import Banner from '@/Components/Banner.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import Toast from '@/Components/Toast.vue';
import { fileUrl } from '@/Plugins/composables';
import { Icon } from '@iconify/vue';

defineProps({
	title: String,
});

const greeting = ref('')

onMounted(() => {
	// We need to wait for the DOM to render before initializing legacy scripts
	nextTick(() => {
		loadLegacyScripts();
	});

	// // Wait for your legacy script logic to finish (800ms for preloader + script load)
	// setTimeout(() => {
	//     showMessages(); // Show messages present on initial load

	//     // Then start watching for subsequent Inertia navigation flashes
	//     watch(() => page.props.flash, () => {
	//         showMessages();
	//     }, { deep: true });
	// }, 500); 

	// Get the current hour (0 - 23)
	const currentHour = new Date().getHours()

	// Determine the greeting based on the hour
	if (currentHour < 12) {
		greeting.value = 'Good Morning'
	} else if (currentHour < 18) {
		greeting.value = 'Good Afternoon'
	} else {
		greeting.value = 'Good Evening'
	}
});

function loadLegacyScripts() {
	// 1. Remove Preloader
	const preloader = document.getElementById('preload');
	if (preloader) {
		setTimeout(() => {
			preloader.style.opacity = 0;
			preloader.style.visibility = 'hidden';
		}, 800);
	}

	// 2. Load UI scripts dynamically (main.js, carousel.js)
	// This ensures they run AFTER Vue has rendered the HTML
	const scripts = [
		'/vendor/global/global.min.js',
		'/vendor/bootstrap-select/dist/js/bootstrap-select.min.js',
		'/js/custom.min.js',
		'/js/deznav-init.js',

	];

	scripts.forEach(src => {
		// Check if script already exists to prevent duplicate loading
		let script = document.querySelector(`script[src="${src}"]`);
		if (!script) {
			script = document.createElement('script');
			script.src = src;
			script.async = false; // Execute in order
			document.body.appendChild(script);
		} else {
			// If main.js exposes an init function, call it here.
			// Otherwise, we might need to reload the file:
			const newScript = document.createElement('script');
			newScript.src = src + '?t=' + new Date().getTime(); // Bust cache to force re-run
			newScript.async = false;
			document.body.appendChild(newScript);
		}
	});

	// 3. Re-init WOW.js
	if (window.WOW) new window.WOW().init();
}



const showingNavigationDropdown = ref(false);

const switchToTeam = (team) => {
	router.put(route('current-team.update'), {
		team_id: team.id,
	}, {
		preserveState: false,
	});
};

const logout = () => {
	router.post(route('logout'));
};
</script>

<template>
	<div>
		<Toast />

		<Head :title="title" />


		<!--**********************************
        Main wrapper start
    ***********************************-->
		<div id="main-wrapper">

			<!--**********************************
            Nav header start
        ***********************************-->
			<div class="nav-header">
				<a :href="route('home')" class="brand-logo items-center gap-x-2">
					<img class="logo-abbr" :src="fileUrl('assets/images/ica_logo.jpg')" alt="">
					<div class="brand-title">
						<div class="title poppins-medium text-sm">ICA APP</div>
						<div class="subtitle poppins-regular text-xs">Online Church Portal</div>
					</div>
					<!-- <img class="logo-compact" src="images/logo-text.png" alt=""> -->
					<!-- <img class="brand-title" src="images/logo-text.png" alt=""> -->
				</a>

				<div class="nav-control">
					<div class="hamburger">
						<span class="line"></span><span class="line"></span><span class="line"></span>
					</div>
				</div>
			</div>
			<!--**********************************
            Nav header end
        ***********************************-->



			<!--**********************************
            Header start
        ***********************************-->
			<div class="header">
				<div class="header-content">
					<nav class="navbar navbar-expand">
						<div class="navbar-collapse justify-content-between">
							<div class="header-left">
								<div class="dashboard_bar">
									<slot name="heading" />
								</div>
							</div>
							<ul class="navbar-nav header-right">
								<!-- <li class="nav-item">
									<div class="input-group search-area d-xl-inline-flex d-none">
										<input type="text" class="form-control" placeholder="Search here"
											aria-label="Username" aria-describedby="header-search">
										<span class="input-group-text" id="header-search">
											<a href="javascript:void(0);">
												<i class="flaticon-381-search-2"></i>
											</a>
										</span>
									</div>
								</li> -->


								<li class="nav-item dropdown header-profile">
									<a v-if="$page.props.auth.user != null" class="nav-link" href="javascript:void(0)"
										role="button" data-bs-toggle="dropdown">
										<div class="flex items-center gap-x-4">
											<div>
												<div class="flex gap-x-1">
													<Icon icon="material-symbols:person-2-outline-rounded" />
													<div class="text-xxs poppins-medium">{{ greeting }}</div>
												</div>
												<div class="poppins-medium text-xl">
													<div class="">{{ $page.props.auth?.user?.first_name }}!</div>
												</div>
											</div>
											<img :src="$page.props.auth?.user?.avatar" width="20" alt="">

										</div>
									</a>
									<a v-else class="nav-link" href="javascript:void(0)" role="button">
										<div class="flex items-center gap-x-4">
											<div>
												<div class="flex gap-x-1">
													<Icon icon="material-symbols:person-2-outline-rounded" />
													<div class="text-xxs poppins-medium">{{ greeting }}</div>
												</div>
												<div class="poppins-medium text-xl">
													<div class="">Welcome!</div>
												</div>
											</div>

											<div>

												<a :href="route('login')"
													class="btn btn-primary cursor-pointer light flex gap-x-2 items-center">
													<Icon width="20" icon="material-symbols:person" />
													<span>Login</span>
												</a>
											</div>

										</div>
									</a>

									<div class="dropdown-menu dropdown-menu-end">
										<a href="#" class="dropdown-item ai-icon">

											<span class="ms-2">Profile </span>
										</a>
										<a @click="logout" class="dropdown-item ai-icon cursor-pointer">
											<span class="ms-2">Logout </span>
										</a>

									</div>


								</li>

								<li class="nav-item">
									<a href="https://play.google.com/store/apps/details?id=com.icaapp.app"  target="_blank">
										<Icon width="30" icon="logos:google-play-icon" />
									</a>
								</li>

								<li class="nav-item">
									<a href="https://apps.apple.com/gb/app/ica-app-online-church-portal/id6466726690" target="_blank">
										<Icon width="30" icon="logos:apple-app-store" />
									</a>
								</li>
							</ul>
						</div>
					</nav>
				</div>
			</div>
			<!--**********************************
            Header end ti-comment-alt
        ***********************************-->

			<!--**********************************
            Sidebar start
        ***********************************-->
			<div class="deznav">
				<div class="deznav-scroll">
					<!-- <a href="javascript:void(0)" class="add-menu-sidebar" data-bs-toggle="modal"
						data-bs-target="#addOrderModalside">+ New Event</a> -->
					<ul class="metismenu" id="menu">
						<li><a class="has-arrow ai-icon flex gap-x-2" :href="route('home')" aria-expanded="false">
								<!-- <i class="flaticon-381-networking"></i> -->
								<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
									<path
										d="M14 21C13.4477 21 13 20.5523 13 20V12C13 11.4477 13.4477 11 14 11H20C20.5523 11 21 11.4477 21 12V20C21 20.5523 20.5523 21 20 21H14ZM4 13C3.44772 13 3 12.5523 3 12V4C3 3.44772 3.44772 3 4 3H10C10.5523 3 11 3.44772 11 4V12C11 12.5523 10.5523 13 10 13H4ZM9 11V5H5V11H9ZM4 21C3.44772 21 3 20.5523 3 20V16C3 15.4477 3.44772 15 4 15H10C10.5523 15 11 15.4477 11 16V20C11 20.5523 10.5523 21 10 21H4ZM5 19H9V17H5V19ZM15 19H19V13H15V19ZM13 4C13 3.44772 13.4477 3 14 3H20C20.5523 3 21 3.44772 21 4V8C21 8.55228 20.5523 9 20 9H14C13.4477 9 13 8.55228 13 8V4ZM15 5V7H19V5H15Z">
									</path>
								</svg>
								<span class="nav-text">Home</span>
							</a>

						</li>
						<li><a class="has-arrow ai-icon flex gap-x-2" :href="route('dashboard')" aria-expanded="false">
								<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
									<path
										d="M21 4H7C5.89543 4 5 4.89543 5 6C5 7.10457 5.89543 8 7 8H21V21C21 21.5523 20.5523 22 20 22H7C4.79086 22 3 20.2091 3 18V6C3 3.79086 4.79086 2 7 2H20C20.5523 2 21 2.44772 21 3V4ZM5 18C5 19.1046 5.89543 20 7 20H19V10H7C6.27143 10 5.58835 9.80521 5 9.46487V18ZM20 7H7C6.44772 7 6 6.55228 6 6C6 5.44772 6.44772 5 7 5H20V7Z">
									</path>
								</svg>
								<span class="nav-text">Sermons</span>
							</a>

						</li>
						<li><a class="has-arrow ai-icon flex gap-x-2" :href="route('dashboard')" aria-expanded="false">
								<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
									<path
										d="M12 11C14.7614 11 17 13.2386 17 16V22H15V16C15 14.4023 13.7511 13.0963 12.1763 13.0051L12 13C10.4023 13 9.09634 14.2489 9.00509 15.8237L9 16V22H7V16C7 13.2386 9.23858 11 12 11ZM5.5 14C5.77885 14 6.05009 14.0326 6.3101 14.0942C6.14202 14.594 6.03873 15.122 6.00896 15.6693L6 16L6.0007 16.0856C5.88757 16.0456 5.76821 16.0187 5.64446 16.0069L5.5 16C4.7203 16 4.07955 16.5949 4.00687 17.3555L4 17.5V22H2V17.5C2 15.567 3.567 14 5.5 14ZM18.5 14C20.433 14 22 15.567 22 17.5V22H20V17.5C20 16.7203 19.4051 16.0796 18.6445 16.0069L18.5 16C18.3248 16 18.1566 16.03 18.0003 16.0852L18 16C18 15.3343 17.8916 14.694 17.6915 14.0956C17.9499 14.0326 18.2211 14 18.5 14ZM5.5 8C6.88071 8 8 9.11929 8 10.5C8 11.8807 6.88071 13 5.5 13C4.11929 13 3 11.8807 3 10.5C3 9.11929 4.11929 8 5.5 8ZM18.5 8C19.8807 8 21 9.11929 21 10.5C21 11.8807 19.8807 13 18.5 13C17.1193 13 16 11.8807 16 10.5C16 9.11929 17.1193 8 18.5 8ZM5.5 10C5.22386 10 5 10.2239 5 10.5C5 10.7761 5.22386 11 5.5 11C5.77614 11 6 10.7761 6 10.5C6 10.2239 5.77614 10 5.5 10ZM18.5 10C18.2239 10 18 10.2239 18 10.5C18 10.7761 18.2239 11 18.5 11C18.7761 11 19 10.7761 19 10.5C19 10.2239 18.7761 10 18.5 10ZM12 2C14.2091 2 16 3.79086 16 6C16 8.20914 14.2091 10 12 10C9.79086 10 8 8.20914 8 6C8 3.79086 9.79086 2 12 2ZM12 4C10.8954 4 10 4.89543 10 6C10 7.10457 10.8954 8 12 8C13.1046 8 14 7.10457 14 6C14 4.89543 13.1046 4 12 4Z">
									</path>
								</svg>
								<span class="nav-text">Cell</span>
							</a>

						</li>
						<li><a class="has-arrow ai-icon flex gap-x-2" :href="route('dashboard')" aria-expanded="false">
								<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"
									viewBox="0 0 24 24"><!-- Icon from Material Symbols by Google - https://github.com/google/material-design-icons/blob/master/LICENSE -->
									<path fill="currentColor"
										d="M16 21q-.825 0-1.412-.587T14 19v-4q0-.825.588-1.412T16 13h4q.825 0 1.413.588T22 15v4q0 .825-.587 1.413T20 21zm0-2h4v-4h-4zM3 18q-.425 0-.712-.288T2 17t.288-.712T3 16h7q.425 0 .713.288T11 17t-.288.713T10 18zm13-7q-.825 0-1.412-.587T14 9V5q0-.825.588-1.412T16 3h4q.825 0 1.413.588T22 5v4q0 .825-.587 1.413T20 11zm0-2h4V5h-4zM3 8q-.425 0-.712-.288T2 7t.288-.712T3 6h7q.425 0 .713.288T11 7t-.288.713T10 8zm15-1" />
								</svg>
								<span class="nav-text">Register Sheets</span>
							</a>

						</li>
						<li><a class="has-arrow ai-icon flex gap-x-2" :href="route('dashboard')" aria-expanded="false">
								<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"
									viewBox="0 0 12 12"><!-- Icon from Fluent UI System Icons by Microsoft Corporation - https://github.com/microsoft/fluentui-system-icons/blob/main/LICENSE -->
									<path fill="currentColor"
										d="M6.153 7.008A1.5 1.5 0 0 1 7.5 8.5c0 .771-.47 1.409-1.102 1.83c-.635.424-1.485.67-2.398.67s-1.763-.246-2.398-.67C.969 9.91.5 9.271.5 8.5A1.5 1.5 0 0 1 2 7h4zM9.998 7a1.5 1.5 0 0 1 1.5 1.5c0 .695-.431 1.211-.983 1.528c-.548.315-1.265.472-2.017.472q-.379-.001-.74-.055c.234-.276.43-.593.561-.95q.089.005.179.005c.629 0 1.162-.133 1.519-.338c.353-.203.481-.437.481-.662a.5.5 0 0 0-.5-.5h-1.55a2.5 2.5 0 0 0-.454-1zM2 8a.5.5 0 0 0-.5.5c0 .333.203.696.656.998c.45.3 1.1.502 1.844.502c.743 0 1.394-.202 1.844-.502c.453-.302.656-.665.656-.998a.5.5 0 0 0-.4-.49L6 8zm2.231-6.491a2.253 2.253 0 0 1 2.023 2.24l-.012.23A2.253 2.253 0 0 1 4.002 6l-.23-.012a2.25 2.25 0 0 1-2.01-2.01l-.012-.23a2.253 2.253 0 0 1 2.252-2.253zm4.519.988a1.75 1.75 0 1 1 0 3.5a1.75 1.75 0 0 1 0-3.5m-4.748 0a1.252 1.252 0 1 0 0 2.504a1.252 1.252 0 0 0 0-2.504m4.748 1a.75.75 0 1 0 0 1.5a.75.75 0 0 0 0-1.5" />
								</svg>
								<span class="nav-text">Members</span>
							</a>

						</li>
						<li><a class="has-arrow ai-icon flex gap-x-2" :href="route('dashboard')" aria-expanded="false">
								<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"
									viewBox="0 0 24 24"><!-- Icon from Google Material Icons by Material Design Authors - https://github.com/material-icons/material-icons/blob/master/LICENSE -->
									<path fill="currentColor"
										d="M19.66 3.99c-2.64-1.8-5.9-.96-7.66 1.1c-1.76-2.06-5.02-2.91-7.66-1.1c-1.4.96-2.28 2.58-2.34 4.29c-.14 3.88 3.3 6.99 8.55 11.76l.1.09c.76.69 1.93.69 2.69-.01l.11-.1c5.25-4.76 8.68-7.87 8.55-11.75c-.06-1.7-.94-3.32-2.34-4.28M12.1 18.55l-.1.1l-.1-.1C7.14 14.24 4 11.39 4 8.5C4 6.5 5.5 5 7.5 5c1.54 0 3.04.99 3.57 2.36h1.87C13.46 5.99 14.96 5 16.5 5c2 0 3.5 1.5 3.5 3.5c0 2.89-3.14 5.74-7.9 10.05" />
								</svg>
								<span class="nav-text">Give</span>
							</a>

						</li>
						<li><a class="has-arrow ai-icon flex gap-x-2" href="javascript:void()" aria-expanded="false">
								<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"
									viewBox="0 0 24 24"><!-- Icon from Material Symbols by Google - https://github.com/google/material-design-icons/blob/master/LICENSE -->
									<path fill="currentColor" d="M17 16V7H8V5h11v11zm-5 5v-9H3v-2h11v11z" />
								</svg>
								<span class="nav-text">More</span>
							</a>
							<ul aria-expanded="false">
								<li><a :href="route('dashboard')">Series</a></li>
								<li><a :href="route('dashboard')">Authors</a></li>
								<li><a :href="route('dashboard')">Resources</a></li>
								<li><a :href="route('dashboard')">About</a></li>
							</ul>
						</li>

					</ul>
					<!-- <div class="copyright">
						<p><strong>ICA APP</strong> © 2026 All Rights Reserved</p>
						<p>Made with <span class="heart"></span> by Gaya Tech</p>
					</div> -->
				</div>
			</div>
			<!--**********************************
            Sidebar end
        ***********************************-->

			<slot />

			<!--**********************************
            Footer start
        ***********************************-->
			<div class="footer">
				<div class="copyright">
					<p>Copyright © Designed &amp; Developed by <a href="https://gayatech.net/"
							target="_blank">Gaya Tech</a> 2026</p>
				</div>
			</div>
			<!--**********************************
            Footer end
        ***********************************-->

			<!--**********************************
           Support ticket button start
        ***********************************-->

			<!--**********************************
           Support ticket button end
        ***********************************-->


		</div>
		<!--**********************************
        Main wrapper end
    ***********************************-->


	</div>
</template>
