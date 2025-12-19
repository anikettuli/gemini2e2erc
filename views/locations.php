<section class="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8 pt-6 pb-16">
    <div class="text-center mb-16">
        <h1 class="text-4xl font-bold mb-4 text-slate-900 dark:text-white">Drop-Off Locations</h1>
        <p class="text-xl text-slate-600 dark:text-slate-400">Find convenient donation drop-off locations across the Dallas-Fort Worth area.</p>
    </div>

    <!-- Main Center Info -->
    <div class="glass-panel p-10 rounded-3xl mb-12 flex flex-col md:flex-row items-center gap-12 border-l-8 border-indigo-500 shadow-lg">
        <div class="flex-1">
            <span class="text-indigo-600 dark:text-indigo-400 font-bold tracking-wider text-sm uppercase mb-2 block">Main Processing Center</span>
            <h2 class="text-3xl font-bold mb-4 text-slate-900 dark:text-white">Watauga Center</h2>
            
             <a href="https://www.google.com/maps/search/?api=1&query=5621+Bunker+Blvd,+Watauga,+TX+76148" target="_blank" class="flex items-center gap-3 text-lg mb-4 hover:text-indigo-500 transition-colors group">
                <span class="p-2 bg-indigo-50 dark:bg-slate-700 rounded-lg text-indigo-600 dark:text-indigo-400 group-hover:scale-110 transition-transform">📍</span>
                <span>5621 Bunker Blvd, Watauga, TX 76148</span>
             </a>
            
             <div class="flex items-center gap-3 text-lg mb-6 hover:text-indigo-500 transition-colors group">
                 <span class="p-2 bg-indigo-50 dark:bg-slate-700 rounded-lg text-indigo-600 dark:text-indigo-400">📞</span>
                <a href="tel:+18177105403" class="show-text">(817) 710-5403</a>
             </div>

            <div class="bg-indigo-50 dark:bg-slate-700/50 p-6 rounded-xl border border-indigo-100 dark:border-indigo-800/50 mb-6">
                <h3 class="font-bold mb-3 text-slate-900 dark:text-white flex items-center gap-2">
                    <span>🅿️</span> Parking & Access
                </h3>
                <p class="text-slate-600 dark:text-slate-300">
                    Free parking available. Entrance at <strong>rear of building</strong> via wooden walkway on East side.
                </p>
            </div>
            
             <div class="bg-indigo-50 dark:bg-slate-700/50 p-6 rounded-xl border border-indigo-100 dark:border-indigo-800/50">
                <h3 class="font-bold mb-3 text-slate-900 dark:text-white flex items-center gap-2">
                    <span>📮</span> Mailing Address
                </h3>
                 <p class="text-slate-600 dark:text-slate-300 font-mono">
                    Lions District 2-E2 ERC<br>
                    P.O. Box 1854<br>
                    Keller, TX 76244
                </p>
            </div>
        </div>
        
        <div class="w-full md:w-1/3">
             <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700">
                <h3 class="font-bold text-lg mb-4 text-center border-b border-slate-100 dark:border-slate-700 pb-3">Hours of Operation</h3>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-slate-500">Mon-Fri</span>
                        <span class="font-medium bg-slate-100 dark:bg-slate-700 px-3 py-1 rounded text-sm">By Appointment</span>
                    </div>
                     <div class="flex justify-between items-center">
                        <span class="font-bold text-indigo-600 dark:text-indigo-400">Saturday</span>
                        <span class="font-bold bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 px-3 py-1 rounded text-sm">9:00 AM - 3:00 PM</span>
                    </div>
                     <div class="flex justify-between items-center">
                        <span class="text-slate-500">Sunday</span>
                        <span class="text-slate-400 text-sm">Closed</span>
                    </div>
                </div>
                <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-700 text-center">
                    <p class="text-xs text-slate-500 mb-2">Need a weekday appointment?</p>
                     <a href="index.php?page=contact" class="text-indigo-600 font-bold text-sm hover:underline">Contact Us</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Map Section -->
    <div class="bg-white dark:bg-slate-800 p-2 rounded-3xl shadow-lg mb-12">
        <div id="mapContainer" class="w-full h-[500px] rounded-2xl overflow-hidden relative z-0">
            <div id="allLocationsMap" class="w-full h-full"></div>
        </div>
    </div>


    <!-- Partner List -->
    <div class="glass-panel p-8 rounded-3xl" id="locations-list-container">
        <h2 class="text-3xl font-bold mb-4">Partner Drop-Off Locations</h2>
        <p class="text-slate-500 mb-8">We have 150+ convenient donation drop-off locations at partner organizations throughout the DFW area.</p>
        
        <div id="locations-list" class="space-y-4">
            <div class="text-center pt-6 pb-16 text-slate-400">
                <svg class="animate-spin h-8 w-8 text-indigo-500 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Loading locations map data...
            </div>
        </div>
    </div>
</section>
