<?php
$file = 'c:\\xampp\\htdocs\\Inter-College_Meet\\users\\dashboard.php';
$content = file_get_contents($file);

$lunch_new = '        <div class="relative overflow-hidden bg-emerald-500/10 border border-emerald-500/30 p-8 rounded-3xl shadow-lg mb-10 group transition-all duration-300 hover:border-emerald-400/50 hover:shadow-[0_0_40px_rgba(16,185,129,0.15)]">
            <div class="absolute top-0 right-0 p-6 opacity-5 pointer-events-none transition-transform duration-700 group-hover:scale-110 group-hover:rotate-12">
                <svg class="w-48 h-48 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 2a4 4 0 00-4 4v1H5a1 1 0 00-.994.89l-1 9A1 1 0 004 18h12a1 1 0 00.994-1.11l-1-9A1 1 0 0015 7h-1V6a4 4 0 00-4-4zm2 5V6a2 2 0 10-4 0v1h4zm-6 3a1 1 0 112 0 1 1 0 01-2 0zm7-1a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd"></path></svg>
            </div>
            
            <div class="flex flex-col md:flex-row items-center gap-8 relative z-10">
                <div class="bg-white p-3 rounded-2xl shadow-[0_0_30px_rgba(16,185,129,0.3)] group-hover:shadow-[0_0_50px_rgba(16,185,129,0.5)] transition-shadow duration-500 shrink-0">
                     <img src="<?php echo $lunch_qr_file; ?>?t=<?php echo time(); ?>" alt="Lunch Token QR Code" class="rounded-xl w-32 h-32 object-cover">
                </div>
                <div>
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-400 text-xs font-bold uppercase tracking-widest mb-3 border border-emerald-500/30">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span> Valid Once
                    </div>
                    <h2 class="text-3xl md:text-3xl font-extrabold mb-2 text-transparent bg-clip-text bg-gradient-to-r from-white to-emerald-400">Universal Lunch Token</h2>
                    <p class="text-emerald-100/70 text-base max-w-xl leading-relaxed">Present this unique QR code at the food counter to redeem your lunch securely.</p>
                </div>
            </div>
        </div>';

$events_new = '        <div class="mb-10">
            <h2 class="text-2xl font-bold text-gray-100 mb-6 flex items-center gap-3">
                <svg class="w-6 h-6 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                Your Registered Events
            </h2>
            <?php if(empty($registrations)): ?>
                <div class="glass-panel text-center py-16 rounded-3xl border border-white/5">
                    <div class="w-20 h-20 bg-gray-800/50 rounded-full flex items-center justify-center mx-auto mb-6 text-gray-500 border border-white/10">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                    </div>
                    <p class="text-gray-400 text-lg mb-6">You haven\'t registered for any events yet.</p>
                    <a href="browse_events.php" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-indigo-500 to-purple-500 hover:from-indigo-400 hover:to-purple-400 text-white font-semibold transition-all shadow-[0_0_20px_rgba(99,102,241,0.3)]">
                        Browse & Register
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </a>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <?php foreach($registrations as $reg): 
                        $qr_filename = \'event_\' . preg_replace(\'/[^a-zA-Z0-9_-]/\', \'_\', $reg[\'qr_code_data\']) . \'.png\';
                        $qr_code_file_path = $qr_temp_dir . $qr_filename;
                        QRcode::png($reg[\'qr_code_data\'], $qr_code_file_path, QR_ECLEVEL_L, 4);
                    ?>
                    <div class="bg-gradient-to-b from-white/5 to-white/[0.01] border border-white/10 p-6 rounded-3xl flex flex-col items-center text-center shadow-lg transition-all duration-300 hover:-translate-y-2 hover:shadow-[0_15px_40px_rgba(99,102,241,0.2)] hover:border-indigo-500/40 group relative overflow-hidden backdrop-blur-xl">
                        
                        <!-- Top Accent Bar -->
                        <div class="absolute top-0 inset-x-0 h-1 bg-gradient-to-r from-indigo-500 to-purple-500 opacity-50 group-hover:opacity-100 transition-opacity"></div>
                        
                        <div class="w-12 h-12 rounded-2xl bg-indigo-500/10 border border-indigo-500/30 text-indigo-400 flex items-center justify-center mb-4 group-hover:scale-110 group-hover:bg-indigo-500/20 transition-all duration-300">
                             <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </div>
                        
                        <h3 class="font-bold text-xl text-white mb-2 line-clamp-1"><?php echo htmlspecialchars($reg[\'event_name\']); ?></h3>
                        <p class="text-sm text-indigo-300 font-medium mb-3"><?php echo htmlspecialchars($reg[\'venue\']); ?></p>
                        <p class="text-xs text-gray-300 mb-6 bg-black/40 border border-white/10 px-4 py-2 rounded-full font-medium tracking-wide">
                            <?php echo date("D, M j, Y - g:i A", strtotime($reg[\'event_date\'])); ?>
                        </p>
                        
                        <div class="bg-white p-2 rounded-2xl shadow-[0_0_20px_rgba(0,0,0,0.5)] group-hover:shadow-[0_0_25px_rgba(99,102,241,0.5)] transition-shadow duration-300">
                            <img src="<?php echo $qr_code_file_path; ?>?t=<?php echo time(); ?>" alt="Event Entry QR Code" class="rounded-xl w-32 h-32 object-cover">
                        </div>
                        
                        <div class="mt-6 w-full border-t border-white/10 pt-4">
                             <p class="text-xs font-bold text-gray-400 uppercase tracking-widest group-hover:text-indigo-400 transition-colors">Event Entry Pass</p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>';

// Replace using regex for robustness
$new_content = preg_replace('/<div\s+class="bg-green-100.*?<\/div>\s*<\/div>/s', $lunch_new, $content, 1);
$new_content = preg_replace('/<div\s+class="glass-panel p-6 rounded-lg shadow-md".*?<\/div>\s*<\/div>/s', $events_new, $new_content, 1);

file_put_contents($file, $new_content);
echo "Replaced Cards.\n";
?>