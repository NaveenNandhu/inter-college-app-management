<?php
session_start();
// Determine dashboard URL if user/admin is logged in
$dashboard_url = null;
if (isset($_SESSION["admin_loggedin"]) && $_SESSION["admin_loggedin"] === true) {
    $dashboard_url = 'admin/dashboard.php';
} elseif (isset($_SESSION["user_loggedin"]) && $_SESSION["user_loggedin"] === true) {
    if (isset($_SESSION["user_type"]) && $_SESSION["user_type"] === 'coordinator') {
        $dashboard_url = 'coordinator/dashboard.php';
    } else {
        $dashboard_url = 'users/dashboard.php';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inter-College Meet Hub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.13.3/cdn.min.js" defer></script>
    <style>
        body { font-family: 'Outfit', sans-serif; background-color: #f8fafc; color: #1e293b; }
        
        /* Reveal Animations */
        @keyframes reveal { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        .animate-reveal { animation: reveal 0.8s cubic-bezier(0.4, 0, 0.2, 1) forwards; }
        @keyframes float { 0% { transform: translateY(0px) rotate(0deg); } 50% { transform: translateY(-20px) rotate(5deg); } 100% { transform: translateY(0px) rotate(0deg); } }
        .animate-float { animation: float 6s ease-in-out infinite; }
        .animate-float-delayed { animation: float 8s ease-in-out infinite; animation-delay: 2s; }

        .glass-panel {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(0, 0, 0, 0.05);
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.08);
            color: #1e293b;
        }
        .glass-nav {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
        }
        .glass-card {
            background: linear-gradient(145deg, #ffffff 0%, #f1f5f9 100%);
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
            color: #334155;
        }
        .glass-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px -10px rgba(0, 0, 0, 0.1);
            border-color: rgba(99, 102, 241, 0.4);
        }
    
        /* Global Style for Forms - Glass Light Effect with Hover */
        input:not([type="checkbox"]):not([type="submit"]):not([type="hidden"]), select, textarea {
            background: #ffffff !important;
            border: 1px solid #cbd5e1 !important;
            color: #1e293b !important;
            transition: all 0.3s ease !important;
            box-shadow: inset 0 2px 4px 0 rgba(0,0,0,0.02) !important;
        }
        input:not([type="checkbox"]):not([type="submit"]):not([type="hidden"]):hover, select:hover, textarea:hover {
            border-color: #6366f1 !important;
            box-shadow: 0 2px 4px rgba(99,102,241,0.05) !important;
        }
        input:not([type="checkbox"]):not([type="submit"]):not([type="hidden"]):focus, select:focus, textarea:focus {
            outline: none !important;
            background: #ffffff !important;
            border-color: #4f46e5 !important;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2) !important;
        }
        input::placeholder, textarea::placeholder {
            color: #94a3b8 !important;
        }

        /* Advanced Table UI */
        table {
            border-collapse: separate !important;
            border-spacing: 0 !important;
            width: 100% !important;
            border-radius: 12px !important;
            overflow: hidden !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03) !important;
            border: 1px solid #e2e8f0 !important;
            background: #ffffff !important;
        }
        thead {
            background: linear-gradient(90deg, #f8fafc 0%, #f1f5f9 100%) !important;
        }
        th {
            padding: 1rem 1.25rem !important;
            text-align: left !important;
            font-size: 0.875rem !important;
            font-weight: 600 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.05em !important;
            color: #475569 !important;
            border-bottom: 2px solid #e2e8f0 !important;
        }
        tbody tr {
            background-color: #ffffff !important;
            transition: all 0.2s ease-in-out !important;
        }
        tbody tr:hover {
            background-color: #f8fafc !important;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05) !important;
            transform: scale(1.001) !important;
        }
        td {
            padding: 1rem 1.25rem !important;
            font-size: 0.95rem !important;
            color: #334155 !important;
            border-bottom: 1px solid #f1f5f9 !important;
            vertical-align: middle !important;
        }
        tbody tr:last-child td {
            border-bottom: none !important;
        }
    </style>
</head>

<body class="bg-slate-50 relative overflow-x-hidden text-slate-800" x-data="{ scrolled: false }"
    @scroll.window="scrolled = (window.pageYOffset > 20)">

    <!-- Background Animated Gradients -->
    <div class="fixed inset-0 z-0 overflow-hidden pointer-events-none">
        <div class="absolute top-[-20%] left-[-10%] w-[50%] h-[50%] rounded-full bg-indigo-300/30 blur-[120px]"></div>
        <div class="absolute bottom-[-20%] right-[-10%] w-[60%] h-[60%] rounded-full bg-purple-300/30 blur-[150px]">
        </div>
        <div class="absolute top-[40%] left-[50%] w-[40%] h-[40%] rounded-full bg-pink-300/30 blur-[120px]"></div>
    </div>

    <!-- Navigation Bar -->
    <nav class="fixed top-0 w-full z-50 transition-all duration-300"
        :class="{'glass-nav py-4': scrolled, 'py-6': !scrolled}">
        <div class="max-w-7xl mx-auto px-6 flex items-center justify-between">
            <a href="#" class="flex items-center gap-2 group">
                <div
                    class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg group-hover:shadow-[0_0_20px_rgba(99,102,241,0.5)] transition-all">
                    <svg class="w-6 h-6 text-slate-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <span
                    class="font-bold text-xl tracking-wide bg-clip-text text-transparent bg-gradient-to-r from-slate-900 to-slate-600">Meet
                    Hub</span>
            </a>
            <div class="hidden md:flex items-center gap-8">
                <a href="#hero" class="text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">Home</a>
                <a href="#about" class="text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">About
                    Project</a>
                <a href="users/view_results.php"
                    class="text-sm font-medium text-pink-400 hover:text-pink-300 transition-colors flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Live Results
                </a>

                <?php if ($dashboard_url): ?>
                    <a href="<?php echo htmlspecialchars($dashboard_url); ?>"
                        class="px-5 py-2.5 rounded-lg bg-indigo-500/10 border border-indigo-500/30 text-indigo-300 text-sm font-semibold hover:bg-indigo-200/40 transition-colors">
                        Go to Dashboard
                    </a>
                <?php else: ?>
                    <div class="flex items-center gap-3">
                        <a href="auth.php?action=login"
                            class="text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">Sign In</a>
                        <a href="auth.php?action=register"
                            class="px-5 py-2.5 rounded-lg bg-gradient-to-r from-indigo-500 to-purple-500 hover:from-indigo-400 hover:to-purple-400 text-white text-sm font-semibold transition-all shadow-[0_0_15px_rgba(99,102,241,0.3)]">
                            Sign Up
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Mobile Menu Placeholder -->
            <div class="md:hidden flex items-center">
                <button class="text-slate-600 hover:text-slate-900 p-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </div>
        </div>
    </nav>

    <!-- ======== HERO SECTION ======== -->
    <section id="hero" class="relative z-10 w-full min-h-screen pt-20 flex items-center justify-center px-6">
        <div class="max-w-7xl w-full flex flex-col items-center text-center gap-8 py-12">

            <div class="animate-reveal mt-10">
                <div
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 text-sm font-medium mb-8 mx-auto">
                    <span class="w-2 h-2 rounded-full bg-indigo-400 animate-pulse"></span>
                    Registration is Live for 2024 Meets
                </div>
                <h1
                    class="text-5xl md:text-7xl lg:text-8xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-slate-900 via-indigo-600 to-indigo-800 tracking-tight leading-[1.1] mb-8 max-w-5xl mx-auto">
                    Elevate Your <br> <span
                        class="bg-clip-text text-transparent bg-gradient-to-r from-indigo-400 via-purple-400 to-pink-400">College
                        Events.</span>
                </h1>
                <p
                    class="text-slate-500 text-lg md:text-xl md:text-2xl font-light tracking-wide max-w-3xl mx-auto mb-12 leading-relaxed">
                    The advanced, centralized platform for discovering symposiums, registering for hackathons, and
                    scanning QR-based attendance effortlessly.
                </p>

                <div class="flex flex-wrap gap-5 justify-center">
                    <?php if ($dashboard_url): ?>
                        <a href="<?php echo htmlspecialchars($dashboard_url); ?>"
                            class="flex items-center gap-2 px-8 py-4 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white font-medium transition-all duration-300 shadow-[0_0_20px_rgba(99,102,241,0.3)] hover:shadow-[0_0_30px_rgba(99,102,241,0.5)] transform hover:-translate-y-1">
                            Access Your Space
                            <svg class="w-5 h-5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                            </svg>
                        </a>
                    <?php else: ?>
                        <a href="auth.php?action=register"
                            class="flex items-center gap-2 px-8 py-4 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white font-medium transition-all duration-300 shadow-[0_0_20px_rgba(99,102,241,0.3)] hover:shadow-[0_0_30px_rgba(99,102,241,0.5)] transform hover:-translate-y-1">
                            Join Now
                            <svg class="w-5 h-5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                            </svg>
                        </a>
                    <?php endif; ?>
                    <a href="#about"
                        class="flex items-center gap-2 px-8 py-4 rounded-xl bg-slate-50 border border-slate-200 hover:bg-white shadow-sm border border-slate-200 text-slate-900 font-medium transition-all duration-300 hover:shadow-lg">
                        Learn More
                    </a>
                </div>

                <!-- Stats Footer in Hero -->
                <div
                    class="grid grid-cols-2 md:grid-cols-4 gap-8 mt-20 pt-10 border-t border-slate-200 max-w-4xl mx-auto">
                    <div>
                        <div class="text-4xl font-bold text-slate-900 mb-2">50+</div>
                        <div class="text-sm text-slate-500 font-medium">Events Hosted</div>
                    </div>
                    <div>
                        <div
                            class="text-4xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-purple-400 to-pink-400 mb-2">
                            10k+</div>
                        <div class="text-sm text-slate-500 font-medium">Participants</div>
                    </div>
                    <div>
                        <div class="text-4xl font-bold text-slate-900 mb-2">100%</div>
                        <div class="text-sm text-slate-500 font-medium">Digital Pass</div>
                    </div>
                    <div>
                        <div
                            class="text-4xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-blue-400 mb-2">
                            24/7</div>
                        <div class="text-sm text-slate-500 font-medium">Live Support</div>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- ======== ABOUT SECTION ======== -->
    <section id="about" class="relative z-10 w-full py-32 bg-white shadow-md border-r border-slate-200 border-t border-slate-100">
        <div class="max-w-7xl mx-auto px-6">

            <div class="text-center max-w-3xl mx-auto mb-20 animate-reveal">
                <h2 class="text-4xl md:text-5xl font-bold mb-6 text-slate-900">About the Project</h2>
                <div class="w-20 h-1 bg-gradient-to-r from-indigo-500 to-pink-500 mx-auto rounded-full mb-8"></div>
                <p class="text-slate-500 text-lg leading-relaxed">
                    Inter-College Meet Hub is a comprehensive web platform designed to streamline the entire lifecycle
                    of college symposiums, hackathons, and cultural meets. It acts as a bridge between organizers and
                    participants.
                </p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="glass-card rounded-2xl p-8 animate-reveal" style="animation-delay: 0.1s;">
                    <div
                        class="w-14 h-14 rounded-xl bg-indigo-200/40 flex items-center justify-center mb-6 text-indigo-400">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Participant Portal</h3>
                    <p class="text-slate-500 text-sm leading-relaxed mb-4">
                        A dedicated hub where students from various colleges can browse upcoming events, register
                        instantly, and manage their participation profiles seamlessly.
                    </p>
                </div>

                <!-- Feature 2 -->
                <div class="glass-card rounded-2xl p-8 animate-reveal" style="animation-delay: 0.2s;">
                    <div
                        class="w-14 h-14 rounded-xl bg-purple-200/40 flex items-center justify-center mb-6 text-purple-400">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">QR Smart Ticketing</h3>
                    <p class="text-slate-500 text-sm leading-relaxed mb-4">
                        Say goodbye to paper tickets. Our integrated QR code generation allows for instant check-ins at
                        the venue, preventing unauthorized access and speeding up entry lines.
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="glass-card rounded-2xl p-8 animate-reveal" style="animation-delay: 0.3s;">
                    <div
                        class="w-14 h-14 rounded-xl bg-pink-200/40 flex items-center justify-center mb-6 text-pink-400">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Coordinator Dashboards</h3>
                    <p class="text-slate-500 text-sm leading-relaxed mb-4">
                        Faculty and assigned student coordinators have isolated access to manage only their assigned
                        events, mark live attendance via mobile QR scanning, and submit final winner lists.
                    </p>
                </div>

                <!-- Feature 4 -->
                <div class="glass-card rounded-2xl p-8 animate-reveal" style="animation-delay: 0.4s;">
                    <div
                        class="w-14 h-14 rounded-xl bg-blue-200/40 flex items-center justify-center mb-6 text-blue-400">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Live Public Results</h3>
                    <p class="text-slate-500 text-sm leading-relaxed mb-4">
                        Maintain transparency. Once competitions end and admins verify the results, they are broadcasted
                        via a public portal allowing everyone to celebrate the winners instantly.
                    </p>
                </div>

                <!-- Feature 5 -->
                <div class="glass-card rounded-2xl p-8 animate-reveal" style="animation-delay: 0.5s;">
                    <div
                        class="w-14 h-14 rounded-xl bg-emerald-200/40 flex items-center justify-center mb-6 text-emerald-400">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Admin Overlord</h3>
                    <p class="text-slate-500 text-sm leading-relaxed mb-4">
                        A master control panel for Head of Departments (HODs) and higher management. Create college
                        meets, generate certificates, moderate results, and export critical analytics in PDF.
                    </p>
                </div>

                <!-- Feature 6 -->
                <div class="glass-card rounded-2xl p-8 animate-reveal flex flex-col justify-center items-center text-center bg-gradient-to-br from-indigo-500/10 to-purple-500/10 border-indigo-500/30"
                    style="animation-delay: 0.6s;">
                    <h3 class="text-2xl font-bold text-slate-900 mb-4">Ready to Join?</h3>
                    <p class="text-slate-500 text-sm leading-relaxed mb-6">
                        Create an account or log in to explore ongoing meets or manage operations.
                    </p>
                    <?php if (!$dashboard_url): ?>
                        <a href="auth.php?action=login"
                            class="px-6 py-3 rounded-lg bg-indigo-500 hover:bg-indigo-600 text-white font-semibold transition-colors shadow-lg">
                            Get Started
                        </a>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 py-8 border-t border-slate-200 text-center text-gray-500 z-10 relative">
        <p class="text-sm">© <?php echo date('Y'); ?> Inter-College Meet Hub. All rights reserved.</p>
    </footer>

</body>

</html>