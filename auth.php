<?php
session_start();
require_once 'config/db_connect.php';

// If an admin is already logged in, redirect them
if (isset($_SESSION["admin_loggedin"]) && $_SESSION["admin_loggedin"] === true) {
    header("location: admin/dashboard.php");
    exit;
}
// If a user is already logged in, redirect them
if (isset($_SESSION["user_loggedin"]) && $_SESSION["user_loggedin"] === true) {
    if ($_SESSION["user_type"] === 'coordinator') {
        header("location: coordinator/dashboard.php");
    } else {
        header("location: users/dashboard.php");
    }
    exit;
}

$login_error = '';
$register_feedback = '';
$register_type = ''; // 'success' or 'error'
$active_form = isset($_GET['action']) && $_GET['action'] === 'register' ? 'register' : 'login';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['form_type']) && $_POST['form_type'] === 'login') {
        $active_form = 'login';
        $email_or_username = trim($_POST['email']);
        $password = $_POST['password'];
        $remember_me = isset($_POST['remember_me']);

        if (!empty($email_or_username) && !empty($password)) {
            // 1. Check USERS table first
            $sql = "SELECT student_id, full_name, email, password, user_type FROM users WHERE email = ?";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("s", $email_or_username);
                if ($stmt->execute()) {
                    $stmt->store_result();
                    if ($stmt->num_rows == 1) {
                        $stmt->bind_result($id, $name, $email_from_db, $hashed_password, $user_type);
                        if ($stmt->fetch()) {
                            if (password_verify($password, $hashed_password)) {
                                session_regenerate_id();
                                $_SESSION["user_loggedin"] = true;
                                $_SESSION["user_id"] = $id;
                                $_SESSION["user_name"] = $name;
                                $_SESSION["user_type"] = $user_type;

                                if ($remember_me) {
                                    $token = bin2hex(random_bytes(16));
                                    $token_hash = hash('sha256', $token);
                                    $expires_at = date('Y-m-d H:i:s', time() + 86400 * 30);
                                    $update_sql = "UPDATE users SET remember_token_hash = ?, remember_token_expires_at = ? WHERE student_id = ?";
                                    if ($update_stmt = $conn->prepare($update_sql)) {
                                        $update_stmt->bind_param("ssi", $token_hash, $expires_at, $id);
                                        $update_stmt->execute();
                                        $update_stmt->close();
                                        setcookie('remember_me_token', $token, time() + 86400 * 30, "/");
                                    }
                                }

                                if ($user_type === 'coordinator') {
                                    header("location: coordinator/dashboard.php");
                                } else {
                                    header("location: users/dashboard.php");
                                }
                                exit;
                            } else {
                                $login_error = "Invalid password. Please try again.";
                            }
                        }
                    } else {
                        // 2. Check ADMINS table
                        $sql_admin = "SELECT admin_id, username, password FROM admins WHERE username = ?";
                        if ($stmt_admin = $conn->prepare($sql_admin)) {
                            $stmt_admin->bind_param("s", $email_or_username);
                            if ($stmt_admin->execute()) {
                                $stmt_admin->store_result();
                                if ($stmt_admin->num_rows == 1) {
                                    $stmt_admin->bind_result($admin_id, $db_username, $admin_hashed_password);
                                    if ($stmt_admin->fetch()) {
                                        if (password_verify($password, $admin_hashed_password)) {
                                            session_regenerate_id();
                                            $_SESSION["admin_loggedin"] = true;
                                            $_SESSION["admin_id"] = $admin_id;
                                            $_SESSION["admin_username"] = $db_username;

                                            // Handle remember me for admin
                                            if ($remember_me) {
                                                $token = bin2hex(random_bytes(16));
                                                $token_hash = hash('sha256', $token);
                                                $expires_at = date('Y-m-d H:i:s', time() + 86400 * 30);

                                                $update_sql = "UPDATE admins SET remember_token_hash = ?, remember_token_expires_at = ? WHERE admin_id = ?";
                                                if ($update_stmt = $conn->prepare($update_sql)) {
                                                    $update_stmt->bind_param("ssi", $token_hash, $expires_at, $admin_id);
                                                    $update_stmt->execute();
                                                    $update_stmt->close();
                                                    setcookie('remember_me_admin_token', $token, time() + 86400 * 30, "/");
                                                }
                                            }

                                            header("location: admin/dashboard.php");
                                            exit;
                                        } else {
                                            $login_error = "Invalid password. Please try again.";
                                        }
                                    }
                                } else {
                                    $login_error = "No account found with that email or username.";
                                }
                            } else {
                                $login_error = "Oops! Something went wrong.";
                            }
                            $stmt_admin->close();
                        }
                    }
                } else {
                    $login_error = "Oops! Something went wrong.";
                }
                $stmt->close();
            }
        } else {
            $login_error = "Please fill in all fields.";
        }
    } elseif (isset($_POST['form_type']) && $_POST['form_type'] === 'register') {
        $active_form = 'register';
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        $full_name = trim($_POST['full_name']);
        $college_name = trim($_POST['college_name']);
        $user_type = $_POST['user_type'];
        $roll_number = trim($_POST['roll_number']);

        if (empty($email) || empty($password) || empty($full_name) || empty($college_name) || empty($user_type)) {
            $register_feedback = "Please fill all required fields.";
            $register_type = 'error';
        } elseif (strlen($password) < 6) {
            $register_feedback = "Password must be at least 6 characters long.";
            $register_type = 'error';
        } else {
            $sql_check = "SELECT student_id FROM users WHERE email = ?";
            if ($stmt_check = $conn->prepare($sql_check)) {
                $stmt_check->bind_param("s", $email);
                $stmt_check->execute();
                $stmt_check->store_result();

                if ($stmt_check->num_rows > 0) {
                    $register_feedback = "This email is already registered.";
                    $register_type = 'error';
                } else {
                    $sql_insert = "INSERT INTO users (full_name, email, phone, college_name, user_type, roll_number, password) VALUES (?, ?, ?, ?, ?, ?, ?)";
                    if ($stmt_insert = $conn->prepare($sql_insert)) {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $phone = trim($_POST['phone'] ?? '');

                        $stmt_insert->bind_param("sssssss", $full_name, $email, $phone, $college_name, $user_type, $roll_number, $hashed_password);

                        if ($stmt_insert->execute()) {
                            $register_type = 'success';
                            $register_feedback = "Registration successful! You can now login.";
                            $active_form = 'login'; // Switch back to login on success
                        } else {
                            $register_feedback = "Something went wrong. Please try again later.";
                            $register_type = 'error';
                        }
                        $stmt_insert->close();
                    }
                }
                $stmt_check->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authentication - Inter-College Meet Hub</title>
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

<body class="bg-slate-50 relative overflow-x-hidden text-slate-800 min-h-screen flex items-center justify-center p-6"
    x-data="{ activeTab: '<?php echo $active_form; ?>' }">

    <!-- Background Animated Gradients -->
    <div class="fixed inset-0 z-0 overflow-hidden pointer-events-none">
        <div class="absolute top-[-20%] left-[-10%] w-[50%] h-[50%] rounded-full bg-indigo-300/30 blur-[120px]"></div>
        <div class="absolute bottom-[-20%] right-[-10%] w-[60%] h-[60%] rounded-full bg-purple-300/30 blur-[150px]">
        </div>
        <div class="absolute top-[40%] left-[50%] w-[40%] h-[40%] rounded-full bg-pink-300/30 blur-[120px]"></div>
    </div>

    <!-- Navigation Back Button -->
    <a href="index.php"
        class="absolute top-8 left-8 z-50 flex items-center gap-2 text-slate-500 hover:text-slate-900 transition-colors">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18">
            </path>
        </svg>
        <span class="font-medium text-sm">Back to Home</span>
    </a>

    <!-- Auth Container -->
    <div class="w-full max-w-md mx-auto relative group animate-reveal z-10">
        <div
            class="absolute -inset-1 bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 rounded-3xl blur opacity-25 group-hover:opacity-40 transition duration-1000">
        </div>

        <div class="glass-panel rounded-3xl p-8 relative">
            <div class="text-center mb-6">
                <a href="index.php" class="inline-block mb-4">
                    <div
                        class="w-12 h-12 mx-auto rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg">
                        <svg class="w-7 h-7 text-slate-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                </a>
                <h2 class="text-2xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-slate-900 to-slate-600">
                    Welcome Back</h2>
                <p class="text-xs text-slate-500 mt-1">Single Portal for Students, Coordinators & Admins</p>
            </div>

            <!-- Tabs -->
            <div class="flex relative mb-8 rounded-xl bg-white shadow-md border-r border-slate-200 p-1 border border-slate-100">
                <button @click="activeTab = 'login'"
                    class="flex-1 py-3 text-sm font-medium rounded-lg transition-all duration-300 z-10"
                    :class="activeTab === 'login' ? 'text-slate-900' : 'text-slate-500 hover:text-slate-900'">
                    Sign In
                </button>
                <button @click="activeTab = 'register'"
                    class="flex-1 py-3 text-sm font-medium rounded-lg transition-all duration-300 z-10"
                    :class="activeTab === 'register' ? 'text-slate-900' : 'text-slate-500 hover:text-slate-900'">
                    Create Account
                </button>
                <!-- Active Tab indicator -->
                <div class="absolute top-1 bottom-1 w-[calc(50%-4px)] bg-white shadow-sm border border-slate-200 rounded-lg transition-all duration-300 ease-out z-0 shadow-lg border border-slate-200"
                    :class="activeTab === 'login' ? 'left-1' : 'left-[calc(50%+2px)]'">
                </div>
            </div>

            <!-- Login Form -->
            <div x-show="activeTab === 'login'" x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
                style="display: <?php echo $active_form === 'login' ? 'block' : 'none'; ?>;">

                <?php if (!empty($login_error)): ?>
                    <div
                        class="bg-red-500/10 border border-red-500/30 text-red-400 p-4 rounded-xl mb-6 text-sm text-center font-medium">
                        <?php echo $login_error; ?>
                    </div>
                <?php endif; ?>
                <?php if ($register_type === 'success' && !empty($register_feedback)): ?>
                    <div
                        class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 p-4 rounded-xl mb-6 text-sm text-center font-medium">
                        <?php echo $register_feedback; ?>
                    </div>
                <?php endif; ?>

                <form action="auth.php" method="POST" class="space-y-5">
                    <input type="hidden" name="form_type" value="login">

                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1.5 uppercase tracking-wider">Email
                            Address / Admin Username</label>
                        <input type="text" name="email" required placeholder="User Email or Admin Username"
                            class="form-input w-full px-5 py-3.5 rounded-xl text-sm"
                            value="<?php echo isset($_POST['email']) && $active_form == 'login' ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                    <div>
                        <label
                            class="block text-xs font-medium text-slate-500 mb-1.5 uppercase tracking-wider">Password</label>
                        <input type="password" name="password" required
                            class="form-input w-full px-5 py-3.5 rounded-xl text-sm">
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <label class="flex items-center space-x-3 cursor-pointer group">
                            <input type="checkbox" name="remember_me"
                                class="w-4 h-4 rounded border-gray-600 bg-black/30 text-indigo-500 focus:ring-0 focus:ring-offset-0 transition-colors cursor-pointer">
                            <span class="text-sm text-slate-500 group-hover:text-gray-200 transition-colors">Keep me
                                signed in</span>
                        </label>
                    </div>

                    <button type="submit"
                        class="w-full bg-gradient-to-r from-indigo-500 to-purple-500 hover:from-indigo-400 hover:to-purple-400 text-white font-semibold py-4 px-4 rounded-xl shadow-[0_0_20px_rgba(99,102,241,0.4)] transition-all duration-300 transform hover:-translate-y-1 mt-6">
                        Access Portal
                    </button>
                </form>
            </div>

            <!-- Register Form -->
            <div x-show="activeTab === 'register'" x-cloak x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
                style="display: <?php echo $active_form === 'register' ? 'block' : 'none'; ?>;">

                <?php if (!empty($register_feedback) && $register_type === 'error'): ?>
                    <div
                        class="bg-red-500/10 border border-red-500/30 text-red-400 p-4 rounded-xl mb-6 text-sm text-center font-medium">
                        <?php echo $register_feedback; ?>
                    </div>
                <?php endif; ?>

                <form action="auth.php" method="POST" class="space-y-4">
                    <input type="hidden" name="form_type" value="register">

                    <div>
                        <input type="text" name="full_name" placeholder="Full Name *" required
                            class="form-input w-full px-5 py-3.5 rounded-xl text-sm">
                    </div>
                    <div>
                        <input type="email" name="email" placeholder="Email Address *" required
                            class="form-input w-full px-5 py-3.5 rounded-xl text-sm">
                    </div>
                    <div>
                        <input type="text" name="college_name" placeholder="College / Institution *" required
                            class="form-input w-full px-5 py-3.5 rounded-xl text-sm">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <input type="text" name="roll_number" placeholder="Roll No"
                            class="form-input px-5 py-3.5 rounded-xl text-sm">
                        <input type="text" name="phone" placeholder="Phone Number"
                            class="form-input px-5 py-3.5 rounded-xl text-sm">
                    </div>
                    <div>
                        <select name="user_type" required
                            class="form-input w-full px-5 py-3.5 rounded-xl text-sm appearance-none cursor-pointer">
                            <option value="student">Student / Participant</option>
                            <option value="coordinator">Event Coordinator / Faculty</option>
                        </select>
                    </div>
                    <div>
                        <input type="password" name="password" placeholder="Password (min. 6 chars) *" required
                            class="form-input w-full px-5 py-3.5 rounded-xl text-sm">
                    </div>

                    <button type="submit"
                        class="w-full bg-gradient-to-r from-purple-500 to-pink-500 hover:from-purple-400 hover:to-pink-400 text-white font-semibold py-4 px-4 rounded-xl shadow-[0_0_20px_rgba(236,72,153,0.4)] transition-all duration-300 transform hover:-translate-y-1 mt-4">
                        Create Account
                    </button>
                </form>
            </div>

        </div>
    </div>

</body>

</html>