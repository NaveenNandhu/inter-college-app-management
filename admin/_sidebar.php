<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="w-64 bg-white shadow-md border-r border-slate-200 text-slate-900 flex flex-col h-full">
    <div class="p-6">
        <h2 class="text-2xl font-bold text-slate-900">Admin Menu</h2>
        <span class="text-sm text-slate-500">Welcome,
            <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
    </div>
    <nav class="flex-1 px-4 space-y-2">
        <a href="dashboard.php"
            class="block py-2.5 px-4 rounded transition duration-200 hover:bg-white shadow-sm border border-slate-200 <?php echo ($current_page == 'dashboard.php') ? 'bg-white shadow-sm border border-slate-200' : ''; ?>">
            Dashboard
        </a>
        <a href="manage_meets.php"
            class="block py-2.5 px-4 rounded transition duration-200 hover:bg-white shadow-sm border border-slate-200 <?php echo ($current_page == 'manage_meets.php') ? 'bg-white shadow-sm border border-slate-200' : ''; ?>">
            Meets
        </a>
        <a href="manage_events.php"
            class="block py-2.5 px-4 rounded transition duration-200 hover:bg-white shadow-sm border border-slate-200 <?php echo ($current_page == 'manage_events.php') ? 'bg-white shadow-sm border border-slate-200' : ''; ?>">
            Events
        </a>
        <a href="assign_coordinators.php"
            class="block py-2.5 px-4 rounded transition duration-200 hover:bg-white shadow-sm border border-slate-200 <?php echo ($current_page == 'assign_coordinators.php') ? 'bg-white shadow-sm border border-slate-200' : ''; ?>">
            Assign Coordinators
        </a>
        <a href="participant_status.php"
            class="block py-2.5 px-4 rounded transition duration-200 hover:bg-white shadow-sm border border-slate-200 <?php echo ($current_page == 'participant_status.php') ? 'bg-white shadow-sm border border-slate-200' : ''; ?>">
            Participant Status
        </a>
        <a href="manage_users.php"
            class="block py-2.5 px-4 rounded transition duration-200 hover:bg-white shadow-sm border border-slate-200 <?php echo ($current_page == 'manage_users.php') ? 'bg-white shadow-sm border border-slate-200' : ''; ?>">
            All Users
        </a>
        <!-- New Link Here -->
        <a href="release_results.php"
            class="block py-2.5 px-4 rounded transition duration-200 hover:bg-white shadow-sm border border-slate-200 <?php echo ($current_page == 'release_results.php') ? 'bg-white shadow-sm border border-slate-200' : ''; ?>">
            Publish Results
        </a>
        <a href="reports.php"
            class="block py-2.5 px-4 rounded transition duration-200 hover:bg-white shadow-sm border border-slate-200 <?php echo ($current_page == 'reports.php') ? 'bg-white shadow-sm border border-slate-200' : ''; ?>">
            Reports
        </a>
    </nav>
    <div class="p-4 mt-auto">
        <a href="logout.php"
            class="block text-center py-2.5 px-4 rounded transition duration-200 bg-red-600 hover:bg-red-700 text-white font-bold">
            Logout
        </a>
    </div>
</div>