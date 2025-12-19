<?php
// Simple Router
$page = isset($_GET['page']) ? $_GET['page'] : 'home';
$valid_pages = ['home', 'about', 'services', 'volunteer', 'locations', 'contact'];

if (!in_array($page, $valid_pages)) {
    $page = 'home';
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lions District 2-E2 Eyeglass Recycling Center</title>
    
    <!-- Modern Stack -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <!-- Maps -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/maplibre-gl@4.0.0/dist/maplibre-gl.css" />
    <script src="https://cdn.jsdelivr.net/npm/maplibre-gl@4.0.0/dist/maplibre-gl.js"></script>
    
    <!-- Custom Config for Tailwind -->
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                fontFamily: {
                    sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                    body: ['Inter', 'sans-serif'],
                },
                extend: {
                    colors: {
                        primary: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                            900: '#312e81',
                        },
                        glass: {
                            100: 'rgba(255, 255, 255, 0.1)',
                            200: 'rgba(255, 255, 255, 0.2)',
                            dark: 'rgba(15, 23, 42, 0.6)',
                        }
                    },
                    backdropBlur: {
                        xs: '2px',
                    }
                }
            }
        }
    </script>

    <!-- Custom Futuristic Styles -->
    <style>
        .glass-panel {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .dark .glass-panel {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .bento-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        /* Animated Background */
        .animated-bg {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            z-index: -1;
            background: radial-gradient(circle at 0% 0%, #4f46e5 0%, transparent 50%), 
                        radial-gradient(circle at 100% 0%, #ec4899 0%, transparent 50%), 
                        radial-gradient(circle at 100% 100%, #06b6d4 0%, transparent 50%), 
                        radial-gradient(circle at 0% 100%, #8b5cf6 0%, transparent 50%);
            opacity: 0.15;
            filter: blur(100px);
        }
        
        .text-gradient {
            background: linear-gradient(135deg, #4f46e5, #ec4899);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Hide scrollbar */
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        /* Custom Scrollbar */
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: rgba(0, 0, 0, 0.05); }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(99, 102, 241, 0.5); border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(99, 102, 241, 0.8); }
        .dark .custom-scrollbar::-webkit-scrollbar-track { background: rgba(255, 255, 255, 0.05); }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(129, 140, 248, 0.5); }
    </style>
</head>
<body class="bg-gray-50 text-slate-800 dark:bg-slate-900 dark:text-gray-100 transition-colors duration-300 flex flex-col min-h-screen">

    <!-- Background -->
    <div class="animated-bg"></div>

    <!-- Navigation -->
    <?php include 'templates/nav.php'; ?>

    <!-- Main Content -->
    <main class="pt-24 flex-grow">
        <?php 
        $file = "views/{$page}.php";
        if (file_exists($file)) {
            include $file;
        } else {
            echo "<div class='text-center py-20'>Page not found</div>";
        }
        ?>
    </main>

    <!-- Footer -->
    <?php include 'templates/footer.php'; ?>

    <!-- Scripts -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 800,
            once: true,
            offset: 50
        });
        
        // Mobile Menu Logic
        const mobileBtn = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        if(mobileBtn) {
            mobileBtn.addEventListener('click', () => {
                mobileMenu.classList.toggle('hidden');
            });
        }
    </script>
    <!-- Include existing app logic if needed, but we are rewriting most of it -->
    <!-- Core Logic -->
    <script src="js/locations.js"></script>
    <script src="js/calendar.js"></script>
    <script src="js/app.js"></script> 
</body>
</html>
