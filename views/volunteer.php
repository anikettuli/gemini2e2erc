<section class="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8 pt-6 pb-8">
    <div class="text-center mb-10">
        <h1 class="text-4xl font-bold mb-4 text-slate-900 dark:text-white">Get Involved</h1>
        <p class="text-xl text-slate-600 dark:text-slate-400">Join us in making a global impact through volunteering, donating, or support.</p>
    </div>

    <!-- Ways to Help Grid (Restored) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-16">
        <div class="glass-panel p-8 rounded-2xl hover:scale-105 transition-transform" data-aos="fade-up" data-aos-delay="0">
            <div class="text-4xl mb-4">🤝</div>
            <h3 class="font-bold text-lg mb-2">Volunteer</h3>
            <p class="text-slate-600 dark:text-slate-400 text-sm">Join our sorting, cleaning, and packing efforts. Help process items that will change lives.</p>
        </div>
        <div class="glass-panel p-8 rounded-2xl hover:scale-105 transition-transform" data-aos="fade-up" data-aos-delay="100">
            <div class="text-4xl mb-4">🎁</div>
            <h3 class="font-bold text-lg mb-2">Donate Items</h3>
            <p class="text-slate-600 dark:text-slate-400 text-sm">Bring your used eyeglasses, laptops, phones, and hearing aids.</p>
        </div>
        <div class="glass-panel p-8 rounded-2xl hover:scale-105 transition-transform" data-aos="fade-up" data-aos-delay="200">
            <div class="text-4xl mb-4">💰</div>
            <h3 class="font-bold text-lg mb-2">Donate Money</h3>
            <p class="text-slate-600 dark:text-slate-400 text-sm">Financial contributions help us with shipping and supplies. 100% goes to our mission.</p>
        </div>
        <div class="glass-panel p-8 rounded-2xl hover:scale-105 transition-transform" data-aos="fade-up" data-aos-delay="300">
             <div class="text-4xl mb-4">📢</div>
            <h3 class="font-bold text-lg mb-2">Spread Word</h3>
            <p class="text-slate-600 dark:text-slate-400 text-sm">Tell friends and family about our mission. Help us reach more people.</p>
        </div>
    </div>

    <!-- Calendar Section -->
    <h2 class="text-3xl font-bold mb-6 text-center" id="volunteer-calendar">Volunteer Calendar</h2>
    <div class="glass-panel rounded-3xl p-8 mb-12 relative overflow-hidden shadow-lg">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 h-[500px] lg:h-[400px]">
            <!-- Calendar Grid -->
            <div class="lg:col-span-1 border-r border-slate-200 dark:border-slate-700/50 pr-8" id="volunteerCalendar">
                <div class="flex items-center justify-center h-full text-slate-400 animate-pulse">Loading Calendar...</div>
            </div>
            
            <!-- Events Display -->
            <div class="lg:col-span-2 bg-slate-50 dark:bg-slate-800/50 rounded-2xl p-6 overflow-y-auto custom-scrollbar" id="eventsDisplay">
                <div class="flex flex-col items-center justify-center h-full text-slate-400">
                    <span class="text-4xl mb-2">📅</span>
                    <p>Select a date to view available slots</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- All Events List -->
    <div id="upcomingEvents" class="mb-16 px-4 bg-white dark:bg-slate-800 rounded-2xl p-6 shadow-sm"></div>

    <!-- Interactive Sections -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-16">
        <!-- Individual Volunteers -->
        <div class="glass-panel p-8 rounded-3xl" data-aos="fade-right">
            <h3 class="text-2xl font-bold mb-4">Individual Volunteers</h3>
            <p class="mb-6 text-slate-600 dark:text-slate-300">We welcome individual volunteers during our open hours to help with:</p>
            <ul class="space-y-3 mb-8">
                <li class="flex items-center gap-3">
                    <div class="w-6 h-6 rounded-full bg-green-100 text-green-600 flex items-center justify-center text-xs">✓</div>
                    <span>Sorting and organizing donations</span>
                </li>
                 <li class="flex items-center gap-3">
                    <div class="w-6 h-6 rounded-full bg-green-100 text-green-600 flex items-center justify-center text-xs">✓</div>
                    <span>Cleaning eyeglasses</span>
                </li>
                 <li class="flex items-center gap-3">
                    <div class="w-6 h-6 rounded-full bg-green-100 text-green-600 flex items-center justify-center text-xs">✓</div>
                    <span>Packing shipments</span>
                </li>
                 <li class="flex items-center gap-3">
                    <div class="w-6 h-6 rounded-full bg-green-100 text-green-600 flex items-center justify-center text-xs">✓</div>
                    <span>Administrative support</span>
                </li>
            </ul>
        </div>

        <!-- Group Volunteer Info -->
        <div class="bg-indigo-600 text-white rounded-3xl p-8 relative overflow-hidden flex flex-col justify-center" data-aos="fade-left">
            <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full blur-3xl -mr-16 -mt-16"></div>
            <div class="relative z-10">
                <h3 class="text-2xl font-bold mb-4">Group Volunteer Days</h3>
                <p class="text-indigo-100 mb-6 font-light">
                    Perfect for corporate teams, schools, and community organizations (up to 20 people).
                </p>
                <div class="flex gap-4">
                     <a href="index.php?page=contact" class="inline-block bg-white text-indigo-600 px-6 py-3 rounded-full font-bold hover:bg-indigo-50 transition-colors shadow-lg">Schedule Group</a>
                     <a href="forms/group-volunteer-form.pdf" target="_blank" class="inline-block border border-white/30 text-white px-6 py-3 rounded-full font-bold hover:bg-white/10 transition-colors">Download Form</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Gallery -->
    <div class="mb-12">
        <h3 class="text-2xl font-bold mb-6">Volunteers in Action</h3>
        <div class="gallery-wrapper relative group">
            <div class="gallery-track whitespace-nowrap overflow-x-auto no-scrollbar scroll-smooth pb-4">
               <!-- JS loads images here -->
               <div class="animate-pulse flex gap-4">
                   <div class="w-64 h-48 bg-slate-200 rounded-xl"></div>
                   <div class="w-64 h-48 bg-slate-200 rounded-xl"></div>
                   <div class="w-64 h-48 bg-slate-200 rounded-xl"></div>
               </div>
            </div>
            
            <!-- Fade overlay for indication of scroll -->
            <div class="absolute inset-y-0 right-0 w-24 bg-gradient-to-l from-gray-50 dark:from-slate-900 to-transparent pointer-events-none"></div>
            
            <!-- Controls (simplified) -->
            <!-- Controls (simplified) -->
            <button class="gallery-arrow left absolute top-1/2 left-4 transform -translate-y-1/2 w-12 h-12 bg-white rounded-full shadow-xl flex items-center justify-center cursor-pointer hover:scale-110 transition-transform z-20 text-slate-900 border border-slate-100 hidden md:flex font-bold text-xl">‹</button>
            <button class="gallery-arrow right absolute top-1/2 right-4 transform -translate-y-1/2 w-12 h-12 bg-white rounded-full shadow-xl flex items-center justify-center cursor-pointer hover:scale-110 transition-transform z-20 text-slate-900 border border-slate-100 hidden md:flex font-bold text-xl">›</button>
        </div>
    </div>
    
    <!-- Financial Donations -->
    <div class="max-w-4xl mx-auto" data-aos="fade-up">
        <div class="glass-panel p-8 rounded-3xl text-center relative overflow-hidden">
            <div class="absolute top-0 right-0 w-64 h-64 bg-green-400/10 rounded-full blur-3xl -mr-16 -mt-16 pointer-events-none"></div>
            
            <h3 class="text-2xl font-bold mb-4 flex items-center justify-center gap-3">
                <span class="text-3xl">💝</span> Financial Support
            </h3>
            
            <p class="text-slate-600 dark:text-slate-300 mb-8 max-w-2xl mx-auto text-lg">
                Your tax-deductible donations directly fund our shipping costs and supplies, ensuring thousands receive the gift of sight.
            </p>

            <div class="inline-flex flex-col md:flex-row items-center gap-4 bg-white dark:bg-slate-800 p-2 pr-6 rounded-2xl border border-indigo-100 dark:border-indigo-900 shadow-sm">
                <div class="p-4 bg-indigo-50 dark:bg-indigo-900/30 rounded-xl text-indigo-600 dark:text-indigo-400 font-bold tracking-wide uppercase text-sm">
                    Mail Checks To
                </div>
                <div class="text-left py-2 px-4">
                    <p class="text-slate-900 dark:text-white font-mono font-medium text-lg">Lions District 2-E2 ERC</p>
                    <p class="text-slate-600 dark:text-slate-400">P.O. Box 1854, Keller, TX 76244</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Partners Section -->
    <div class="mt-16" data-aos="fade-up">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold mb-4">Our Partners & Friends</h2>
            <p class="text-slate-500 max-w-2xl mx-auto">
                We are grateful for the support of our many friends and partners. Special thanks to local businesses like <strong>Westlake Ace Hardware</strong> and <strong>Market Street</strong> for hosting our collection boxes.
            </p>
        </div>
        
        <div class="glass-panel p-8 rounded-3xl">
            <ul id="partners-list" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-2">
                <li class="col-span-full text-center text-slate-400 py-4 italic">Loading partners list...</li>
            </ul>
            <div class="mt-10 pt-8 border-t border-slate-100 dark:border-slate-800 text-center">
                <h3 class="text-xl font-bold mb-4">Partner with Us</h3>
                <p class="text-slate-600 dark:text-slate-400 mb-6">Want to host a collection box or support our mission through your organization?</p>
                <a href="index.php?page=contact" class="text-indigo-600 font-bold hover:underline inline-flex items-center gap-2">
                    Contact us to learn more <span>→</span>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Signup Modal (Hidden) -->
<div id="signupModal" class="fixed inset-0 z-[100] hidden">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm close-modal-bg"></div>
    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-md bg-white dark:bg-slate-800 rounded-2xl shadow-2xl p-8 animation-fade-in-up">
        <button class="close-modal absolute top-4 right-4 text-slate-400 hover:text-slate-600 text-2xl">&times;</button>
        <h2 id="modalEventTitle" class="text-2xl font-bold mb-2 text-slate-900 dark:text-white">Sign Up</h2>
        <p id="modalEventDetails" class="text-slate-500 mb-6"></p>
        
        <form id="volunteerSignupForm" class="space-y-4">
            <input type="hidden" id="signupEventId" name="eventId">
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Full Name</label>
                <input type="text" id="signupName" name="name" required class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Email</label>
                <input type="email" id="signupEmail" name="email" required class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Phone</label>
                <input type="tel" id="signupPhone" name="phone" class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <button type="submit" class="w-full py-3 bg-indigo-600 text-white rounded-lg font-bold hover:bg-indigo-700 transition-colors shadow-lg">Confirm Registration</button>
        </form>
    </div>
</div>
