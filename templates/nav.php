<?php
$active = isset($_GET['page']) ? $_GET['page'] : 'home';
function isActive($pageName, $currentPage) {
    return $pageName === $currentPage ? 'text-indigo-600 dark:text-indigo-400 font-semibold bg-indigo-50 dark:bg-indigo-900/20' : 'text-slate-600 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-50 dark:hover:bg-white/5';
}
?>
<nav class="fixed top-0 w-full z-50 transition-all duration-300">
    <div class="absolute inset-0 glass-panel border-b border-white/20 shadow-sm"></div>
    <div class="relative max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-20">
            <!-- Logo -->
            <div class="flex-shrink-0 flex items-center gap-3">
                <a href="index.php?page=home" class="flex items-center gap-2 group">
                    <img class="h-10 w-auto transition-transform group-hover:scale-110 group-hover:-rotate-3" src="e2e2rc_LOGO.png" alt="Logo">
                    <div class="hidden sm:block">
                        <h1 class="text-lg font-bold bg-clip-text text-transparent bg-gradient-to-r from-indigo-600 to-violet-600 dark:from-indigo-400 dark:to-violet-400 leading-tight">
                            District 2-E2 ERC
                        </h1>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Eyeglass Recycling Center</p>
                    </div>
                </a>
            </div>

            <!-- Desktop Menu -->
            <div class="hidden md:flex space-x-1">
                <a href="index.php?page=home" class="px-4 py-2 rounded-full text-sm transition-all <?= isActive('home', $active) ?>">Home</a>
                <a href="index.php?page=about" class="px-4 py-2 rounded-full text-sm transition-all <?= isActive('about', $active) ?>">About</a>
                <a href="index.php?page=services" class="px-4 py-2 rounded-full text-sm transition-all <?= isActive('services', $active) ?>">Services</a>
                <a href="index.php?page=volunteer" class="px-4 py-2 rounded-full text-sm transition-all <?= isActive('volunteer', $active) ?>">Volunteer</a>
                <a href="index.php?page=locations" class="px-4 py-2 rounded-full text-sm transition-all <?= isActive('locations', $active) ?>">Locations</a>
                <a href="index.php?page=contact" class="px-4 py-2 rounded-full text-sm transition-all <?= isActive('contact', $active) ?>">Contact</a>
            </div>

            <!-- Actions -->
            <div class="hidden md:flex items-center gap-3">
                <button id="themeToggleDesktop" class="p-2 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300 transition-colors">
                    <span class="text-xl">☀️</span>
                </button>
                <a href="index.php?page=volunteer" class="px-5 py-2.5 rounded-full bg-gradient-to-r from-indigo-600 to-violet-600 text-white text-sm font-medium shadow-lg shadow-indigo-500/30 hover:shadow-indigo-500/50 hover:scale-105 transition-all">
                    Get Involved
                </a>
            </div>

            <!-- Mobile Button -->
            <div class="md:hidden flex items-center gap-2">
                <button id="themeToggleMobile" class="p-2 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300 transition-colors">
                    <span class="text-xl">☀️</span>
                </button>
                <button id="mobile-menu-btn" class="p-2 rounded-md text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div id="mobile-menu" class="hidden md:hidden absolute top-20 left-0 w-full glass-panel border-b border-white/20 shadow-lg">
        <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
            <a href="index.php?page=home" class="block px-3 py-2 rounded-md text-base font-medium <?= isActive('home', $active) ?>">Home</a>
            <a href="index.php?page=about" class="block px-3 py-2 rounded-md text-base font-medium <?= isActive('about', $active) ?>">About</a>
            <a href="index.php?page=services" class="block px-3 py-2 rounded-md text-base font-medium <?= isActive('services', $active) ?>">Services</a>
            <a href="index.php?page=volunteer" class="block px-3 py-2 rounded-md text-base font-medium <?= isActive('volunteer', $active) ?>">Volunteer</a>
            <a href="index.php?page=locations" class="block px-3 py-2 rounded-md text-base font-medium <?= isActive('locations', $active) ?>">Locations</a>
            <a href="index.php?page=contact" class="block px-3 py-2 rounded-md text-base font-medium <?= isActive('contact', $active) ?>">Contact</a>
        </div>
    </div>
</nav>
