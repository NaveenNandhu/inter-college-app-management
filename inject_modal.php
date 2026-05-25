<?php
$file = 'c:\\xampp\\htdocs\\Inter-College_Meet\\coordinator\\dashboard.php';
$content = file_get_contents($file);

$btn_regex = '/<a href="scan\.php" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded transition duration-300">\s*Scan QR Codes\s*<\/a>/s';

$btn_new = '<button type="button" onclick="openScanner()" class="bg-blue-600 hover:bg-blue-500 text-white font-semibold py-2 px-4 rounded transition duration-300 shadow-[0_0_15px_rgba(37,99,235,0.3)] hover:shadow-[0_0_25px_rgba(37,99,235,0.5)]">
                                    Scan QR Codes
                                </button>';

$footer_regex = '/<\/main>\s*<\/div><\/body>\s*<\/html>/s';

$footer_new = '</main>

    <!-- Scanner Modal (Hidden by Default) -->
    <div id="scannerModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center bg-black/80 backdrop-blur-sm p-4 transition-opacity duration-300">
        <div class="glass-panel w-full max-w-2xl p-6 rounded-3xl shadow-[0_0_50px_rgba(0,0,0,0.8)] relative border border-white/10">
            
            <!-- Close Button -->
            <button type="button" onclick="closeScanner()" class="absolute top-4 right-4 text-gray-400 hover:text-white transition-colors bg-white/5 hover:bg-red-500/80 p-2 rounded-full shadow-lg z-50">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
            
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-xl bg-blue-500/20 border border-blue-500/30 flex items-center justify-center text-blue-400 shadow-[0_0_15px_rgba(59,130,246,0.5)]">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                </div>
                <h2 class="text-2xl font-bold text-white tracking-wide">Live Scanner Portal</h2>
            </div>
            
            <p class="text-gray-400 mb-6 text-sm ml-14">Verify participant entry passes and lunch tokens instantly using your device camera.</p>
            
            <!-- UI Overrides for html5-qrcode -->
            <style>
                #qr-reader { width: 100%; max-width: 500px; border: 2px solid rgba(255,255,255,0.1); border-radius: 12px; overflow: hidden; margin: 0 auto; background: rgba(0,0,0,0.5); }
                #qr-reader__scan_region { background: #000; display: flex; align-items: center; justify-content: center; min-height: 250px;}
                #qr-reader__dashboard_section_csr span, #qr-reader__dashboard_section_swaplink { color: #818cf8 !important; }
                #qr-reader button { margin: 10px 5px; padding: 6px 16px; background: rgba(59,130,246,0.6); color: white; border: 1px solid rgba(59,130,246,0.8); border-radius: 8px; cursor: pointer; transition: 0.3s; font-weight: 500; display: inline-block;}
                #qr-reader button:hover { background: rgba(59,130,246,0.9); }
                #qr-reader select { background: rgba(0,0,0,0.5) !important; color: white !important; border-radius: 6px; padding: 6px; border: 1px solid rgba(255,255,255,0.2); outline: none;}
            </style>
            
            <div id="qr-reader" class="mx-auto shadow-[0_0_40px_rgba(0,0,0,0.4)]"></div>
            
            <div id="qr-reader-results" class="mt-6 mx-auto max-w-lg p-4 rounded-xl text-center font-semibold text-sm tracking-wide text-gray-300 border border-white/5 backdrop-blur-md">
                Awaiting camera feed. Position the QR code within the frame above.
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode/html5-qrcode.min.js"></script>
<script>
    let html5QrcodeScanner = null;

    function openScanner() {
        const modal = document.getElementById("scannerModal");
        modal.classList.remove("hidden");
        
        const resultDiv = document.getElementById("qr-reader-results");
        resultDiv.textContent = "Awaiting camera feed. Position the QR code within the frame above.";
        resultDiv.className = "mt-6 mx-auto max-w-lg p-4 rounded-xl text-center font-semibold text-sm tracking-wide text-gray-300 border border-white/5 backdrop-blur-md";

        if (!html5QrcodeScanner) {
            html5QrcodeScanner = new Html5QrcodeScanner(
                "qr-reader",
                { fps: 10, qrbox: { width: 250, height: 250 }, aspectRatio: 1.0 },
                /* verbose= */ false
            );
            html5QrcodeScanner.render(onScanSuccess, onScanFailure);
        }
    }

    function closeScanner() {
        document.getElementById("scannerModal").classList.add("hidden");
        if (html5QrcodeScanner) {
            html5QrcodeScanner.clear().then(() => {
                html5QrcodeScanner = null;
                document.getElementById("qr-reader").innerHTML = ""; 
            }).catch(e => console.error("Could not stop scanner.", e));
        }
    }

    function onScanSuccess(decodedText, decodedResult) {
        const resultDiv = document.getElementById("qr-reader-results");
        resultDiv.textContent = "Processing verification...";
        resultDiv.className = "mt-6 mx-auto max-w-lg p-4 rounded-xl text-center font-semibold text-sm bg-yellow-400/10 border border-yellow-400/30 text-yellow-300 backdrop-blur-md shadow-[0_0_15px_rgba(250,204,21,0.2)] animate-pulse";

        // Optional: Pause scanner briefly
        if (html5QrcodeScanner) html5QrcodeScanner.pause();

        fetch("../api/verify_qr.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ qr_data: decodedText })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                resultDiv.textContent = `SUCCESS: ${data.message}`;
                resultDiv.className = "mt-6 mx-auto max-w-lg p-4 rounded-xl text-center font-bold text-sm bg-green-500/10 border border-green-500/40 text-green-400 backdrop-blur-md shadow-[0_0_20px_rgba(34,197,94,0.3)]";
                let audio = new Audio("https://cdn.pixabay.com/audio/2022/03/15/audio_2c64e9a052.mp3");
                audio.volume = 0.5; audio.play().catch(e => console.log(e));
                
                setTimeout(() => { if(html5QrcodeScanner) html5QrcodeScanner.resume(); }, 2000);
            } else {
                resultDiv.textContent = `FAILED: ${data.message}`;
                resultDiv.className = "mt-6 mx-auto max-w-lg p-4 rounded-xl text-center font-bold text-sm bg-red-500/10 border border-red-500/50 text-red-500 backdrop-blur-md shadow-[0_0_20px_rgba(239,68,68,0.4)]";
                let audio = new Audio("https://cdn.pixabay.com/audio/2022/03/10/audio_c81c107383.mp3");
                audio.volume = 0.5; audio.play().catch(e => console.log(e));
                
                setTimeout(() => { if(html5QrcodeScanner) html5QrcodeScanner.resume(); }, 2000);
            }
        })
        .catch(error => {
            console.error("Error:", error);
            resultDiv.textContent = "Network Error: Could not verify with the server.";
            resultDiv.className = "mt-6 mx-auto max-w-lg p-4 rounded-xl text-center font-bold text-sm bg-red-500/10 border border-red-500/50 text-red-500 backdrop-blur-md shadow-[0_0_20px_rgba(239,68,68,0.4)]";
            setTimeout(() => { if(html5QrcodeScanner) html5QrcodeScanner.resume(); }, 3000);
        });
    }

    function onScanFailure(error) {
        // Handle quietly
    }
</script>
</body>
</html>';

$new_content_1 = preg_replace($btn_regex, $btn_new, $content);
$new_content_2 = preg_replace($footer_regex, $footer_new, $new_content_1);

if ($new_content_2 !== $content) {
    file_put_contents($file, $new_content_2);
    echo "Success";
} else {
    echo "Fail replacing strings";
}
?>