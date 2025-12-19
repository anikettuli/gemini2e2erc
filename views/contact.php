<section class="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8 pt-8 pb-8">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
        <!-- Info Side -->
        <div data-aos="fade-right">
            <h1 class="text-4xl font-bold mb-6 text-slate-900 dark:text-white">Contact Us</h1>
            <p class="text-xl text-slate-600 dark:text-slate-400 mb-8">
                Have questions? We'd love to hear from you.
            </p>

            <div class="space-y-6">
                <div class="glass-panel p-6 rounded-2xl flex items-center gap-6">
                    <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/50 rounded-full flex items-center justify-center text-2xl">📞</div>
                    <div>
                        <h3 class="font-bold text-slate-900 dark:text-white">Phone</h3>
                        <a href="tel:+18177105403" class="text-indigo-600 dark:text-indigo-400 hover:underline">(817) 710-5403</a>
                    </div>
                </div>
                <div class="glass-panel p-6 rounded-2xl flex items-center gap-6">
                    <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/50 rounded-full flex items-center justify-center text-2xl">✉️</div>
                    <div>
                        <h3 class="font-bold text-slate-900 dark:text-white">Email</h3>
                        <a href="mailto:2e2erc1854@gmail.com" class="text-indigo-600 dark:text-indigo-400 hover:underline">2e2erc1854@gmail.com</a>
                    </div>
                </div>
                <div class="glass-panel p-6 rounded-2xl flex items-center gap-6">
                    <div class="w-12 h-12 bg-pink-100 dark:bg-pink-900/50 rounded-full flex items-center justify-center text-2xl">📍</div>
                    <div>
                        <h3 class="font-bold text-slate-900 dark:text-white">Visit Us</h3>
                        <p class="text-slate-600 dark:text-slate-400">5621 Bunker Blvd, Watauga, TX 76148</p>
                    </div>
                </div>
                <div class="glass-panel p-6 rounded-2xl flex items-center gap-6">
                    <div class="w-12 h-12 bg-teal-100 dark:bg-teal-900/50 rounded-full flex items-center justify-center text-2xl">📮</div>
                    <div>
                        <h3 class="font-bold text-slate-900 dark:text-white">Mail Us</h3>
                        <p class="text-slate-600 dark:text-slate-400">P.O. Box 1854, Keller, TX 76244</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Side -->
        <div data-aos="fade-left">
            <div class="glass-panel p-8 rounded-3xl border-t-8 border-indigo-600">
                <h2 class="text-2xl font-bold mb-8">Send a Message</h2>
                <form action="send-email.php" method="POST" class="space-y-6" id="contactForm">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium mb-2 text-slate-700 dark:text-slate-300">Name</label>
                            <input type="text" name="name" required class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white/50 dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2 text-slate-700 dark:text-slate-300">Email</label>
                            <input type="email" name="email" required class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white/50 dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2 text-slate-700 dark:text-slate-300">Subject</label>
                        <select name="subject" required class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white/50 dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all">
                            <option value="">Select a topic...</option>
                            <option value="volunteer">Volunteer</option>
                            <option value="group-volunteer">Group volunteer</option>
                            <option value="donation-items">Donate items</option>
                            <option value="donation-money">Donate money</option>
                            <option value="need-collection-boxes">Collection Boxes</option>
                            <option value="partnership">Partnership</option>
                            <option value="question">Question</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2 text-slate-700 dark:text-slate-300">Message</label>
                        <textarea name="message" style="height: 160px;" required class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white/50 dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all"></textarea>
                    </div>
                    <button type="submit" class="w-full py-4 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-bold rounded-xl shadow-lg shadow-indigo-500/30 hover:shadow-indigo-500/50 hover:scale-[1.02] transition-all">
                        Send Message
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- FAQ Section -->
    <div class="mt-12">
        <h2 class="text-3xl font-bold mb-8 text-center text-slate-900 dark:text-white">Frequently Asked Questions</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="glass-panel p-8 rounded-2xl" data-aos="fade-up">
                <h3 class="text-xl font-bold mb-3 text-indigo-600 dark:text-indigo-400">Can I drop off items other than Saturday?</h3>
                <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
                    Yes! Weekday drop-offs are available by appointment. Call or email us to schedule.
                </p>
            </div>
            <div class="glass-panel p-8 rounded-2xl" data-aos="fade-up" data-aos-delay="100">
                <h3 class="text-xl font-bold mb-3 text-purple-600 dark:text-purple-400">Do you accept damaged eyeglasses?</h3>
                <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
                    Absolutely! We accept glasses in any condition. Our team can often repair or repurpose them.
                </p>
            </div>
            <div class="glass-panel p-8 rounded-2xl" data-aos="fade-up" data-aos-delay="200">
                <h3 class="text-xl font-bold mb-3 text-pink-600 dark:text-pink-400">Are donations tax-deductible?</h3>
                <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
                    Yes! We're a registered 501(c)(3) nonprofit. Keep your receipt for tax purposes.
                </p>
            </div>
            <div class="glass-panel p-8 rounded-2xl" data-aos="fade-up" data-aos-delay="300">
                <h3 class="text-xl font-bold mb-3 text-teal-600 dark:text-teal-400">How are you involved in missions?</h3>
                <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
                    We partner with missionaries and nonprofits in 40+ countries to distribute eyeglasses.
                </p>
            </div>
        </div>
    </div>
</section>
