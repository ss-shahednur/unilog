<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Prize Bond API Demo</title>
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 { font-size: 2.5em; margin-bottom: 10px; }
        .header p { opacity: 0.9; }
        .content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
            padding: 30px;
        }
        .card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 25px;
            border: 1px solid #dee2e6;
        }
        .card h2 {
            color: #667eea;
            margin-bottom: 20px;
            font-size: 1.5em;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
            color: #495057;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 14px;
        }
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        button:hover { transform: translateY(-2px); }
        button:active { transform: translateY(0); }
        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .response {
            margin-top: 15px;
            padding: 15px;
            border-radius: 4px;
            font-size: 14px;
            white-space: pre-wrap;
            word-break: break-all;
        }
        .response.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .response.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .token-display {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
            font-family: monospace;
            font-size: 12px;
            word-break: break-all;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Prize Bond API Demo</h1>
            <p>Test all authentication and profile endpoints with Alpine.js</p>
        </div>

        <div class="content" x-data="apiDemo()">
            <!-- Register -->
            <div class="card">
                <h2>1️⃣ Register</h2>
                <form @submit.prevent="register">
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" x-model="registerForm.name" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" x-model="registerForm.email" required>
                    </div>
                    <div class="form-group">
                        <label>Phone (optional)</label>
                        <input type="text" x-model="registerForm.phone">
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" x-model="registerForm.password" required>
                    </div>
                    <div class="form-group">
                        <label>Confirm Password</label>
                        <input type="password" x-model="registerForm.password_confirmation" required>
                    </div>
                    <button type="submit" :disabled="loading.register">
                        <span x-show="!loading.register">Register</span>
                        <span x-show="loading.register">Registering...</span>
                    </button>
                </form>
                <div x-show="responses.register" 
                     :class="responses.register?.includes('success') ? 'response success' : 'response error'" 
                     x-text="responses.register"></div>
            </div>

            <!-- Verify OTP -->
            <div class="card">
                <h2>2️⃣ Verify OTP</h2>
                <form @submit.prevent="verifyOtp">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" x-model="verifyForm.email" required>
                    </div>
                    <div class="form-group">
                        <label>6-Digit OTP</label>
                        <input type="text" x-model="verifyForm.otp" maxlength="6" required>
                    </div>
                    <button type="submit" :disabled="loading.verify">
                        <span x-show="!loading.verify">Verify</span>
                        <span x-show="loading.verify">Verifying...</span>
                    </button>
                </form>
                <div x-show="responses.verify" 
                     :class="responses.verify?.includes('success') ? 'response success' : 'response error'" 
                     x-text="responses.verify"></div>
            </div>

            <!-- Resend OTP -->
            <div class="card">
                <h2>3️⃣ Resend OTP</h2>
                <form @submit.prevent="resendOtp">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" x-model="resendForm.email" required>
                    </div>
                    <button type="submit" :disabled="loading.resend">
                        <span x-show="!loading.resend">Resend OTP</span>
                        <span x-show="loading.resend">Sending...</span>
                    </button>
                </form>
                <div x-show="responses.resend" 
                     :class="responses.resend?.includes('resent') ? 'response success' : 'response error'" 
                     x-text="responses.resend"></div>
            </div>

            <!-- Login -->
            <div class="card">
                <h2>4️⃣ Login</h2>
                <form @submit.prevent="login">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" x-model="loginForm.email" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" x-model="loginForm.password" required>
                    </div>
                    <button type="submit" :disabled="loading.login">
                        <span x-show="!loading.login">Login</span>
                        <span x-show="loading.login">Logging in...</span>
                    </button>
                </form>
                <div x-show="token" class="token-display">
                    <strong>🔑 Token:</strong><br><span x-text="token"></span>
                </div>
                <div x-show="responses.login" 
                     :class="responses.login?.includes('successful') ? 'response success' : 'response error'" 
                     x-text="responses.login"></div>
            </div>

            <!-- Forgot Password -->
            <div class="card">
                <h2>5️⃣ Forgot Password</h2>
                <form @submit.prevent="forgotPassword">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" x-model="forgotForm.email" required>
                    </div>
                    <button type="submit" :disabled="loading.forgot">
                        <span x-show="!loading.forgot">Send Reset OTP</span>
                        <span x-show="loading.forgot">Sending...</span>
                    </button>
                </form>
                <div x-show="responses.forgot" 
                     class="response success" 
                     x-text="responses.forgot"></div>
            </div>

            <!-- Reset Password -->
            <div class="card">
                <h2>6️⃣ Reset Password</h2>
                <form @submit.prevent="resetPassword">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" x-model="resetForm.email" required>
                    </div>
                    <div class="form-group">
                        <label>6-Digit OTP</label>
                        <input type="text" x-model="resetForm.otp" maxlength="6" required>
                    </div>
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" x-model="resetForm.new_password" required>
                    </div>
                    <div class="form-group">
                        <label>Confirm Password</label>
                        <input type="password" x-model="resetForm.new_password_confirmation" required>
                    </div>
                    <button type="submit" :disabled="loading.reset">
                        <span x-show="!loading.reset">Reset Password</span>
                        <span x-show="loading.reset">Resetting...</span>
                    </button>
                </form>
                <div x-show="responses.reset" 
                     :class="responses.reset?.includes('success') ? 'response success' : 'response error'" 
                     x-text="responses.reset"></div>
            </div>

            <!-- Change Password (Authenticated) -->
            <div class="card">
                <h2>7️⃣ Change Password</h2>
                <p style="font-size: 12px; color: #6c757d; margin-bottom: 15px;">⚠️ Requires login token</p>
                <form @submit.prevent="changePassword">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" x-model="changeForm.email" required>
                    </div>
                    <div class="form-group">
                        <label>Current Password</label>
                        <input type="password" x-model="changeForm.old_password" required>
                    </div>
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" x-model="changeForm.new_password" required>
                    </div>
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" x-model="changeForm.new_password_confirmation" required>
                    </div>
                    <button type="submit" :disabled="loading.change || !token">
                        <span x-show="!loading.change">Change Password</span>
                        <span x-show="loading.change">Changing...</span>
                    </button>
                </form>
                <div x-show="responses.change" 
                     :class="responses.change?.includes('success') ? 'response success' : 'response error'" 
                     x-text="responses.change"></div>
            </div>

            <!-- Update Profile (Authenticated) -->
            <div class="card">
                <h2>8️⃣ Update Profile</h2>
                <p style="font-size: 12px; color: #6c757d; margin-bottom: 15px;">⚠️ Requires login token</p>
                <form @submit.prevent="updateProfile">
                    <div class="form-group">
                        <label>User ID</label>
                        <input type="number" x-model="profileForm.id" required>
                    </div>
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" x-model="profileForm.name" required>
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" x-model="profileForm.phone">
                    </div>
                    <div class="form-group">
                        <label>NID</label>
                        <input type="text" x-model="profileForm.nid">
                    </div>
                    <div class="form-group">
                        <label>Profile Image</label>
                        <input type="file" @change="profileForm.image = $event.target.files[0]" accept="image/*">
                    </div>
                    <button type="submit" :disabled="loading.profile || !token">
                        <span x-show="!loading.profile">Update Profile</span>
                        <span x-show="loading.profile">Updating...</span>
                    </button>
                </form>
                <div x-show="responses.profile" 
                     :class="responses.profile?.includes('success') ? 'response success' : 'response error'" 
                     x-text="responses.profile"></div>
            </div>
        </div>
    </div>

    <script>
        function apiDemo() {
            return {
                baseUrl: '{{ url("/") }}',
                token: '',
                loading: {
                    register: false,
                    verify: false,
                    resend: false,
                    login: false,
                    forgot: false,
                    reset: false,
                    change: false,
                    profile: false
                },
                responses: {},
                registerForm: {
                    name: '',
                    email: '',
                    phone: '',
                    password: '',
                    password_confirmation: ''
                },
                verifyForm: { email: '', otp: '' },
                resendForm: { email: '' },
                loginForm: { email: '', password: '' },
                forgotForm: { email: '' },
                resetForm: {
                    email: '',
                    otp: '',
                    new_password: '',
                    new_password_confirmation: ''
                },
                changeForm: {
                    email: '',
                    old_password: '',
                    new_password: '',
                    new_password_confirmation: ''
                },
                profileForm: {
                    id: '',
                    name: '',
                    phone: '',
                    nid: '',
                    image: null
                },

                async register() {
                    this.loading.register = true;
                    try {
                        const res = await fetch(`${this.baseUrl}/api/customers/register`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                            body: JSON.stringify(this.registerForm)
                        });
                        const data = await res.json();
                        this.responses.register = JSON.stringify(data, null, 2);
                    } catch (e) {
                        this.responses.register = 'Error: ' + e.message;
                    }
                    this.loading.register = false;
                },

                async verifyOtp() {
                    this.loading.verify = true;
                    try {
                        const res = await fetch(`${this.baseUrl}/api/customers/verify-register-otp`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                            body: JSON.stringify(this.verifyForm)
                        });
                        const data = await res.json();
                        this.responses.verify = JSON.stringify(data, null, 2);
                    } catch (e) {
                        this.responses.verify = 'Error: ' + e.message;
                    }
                    this.loading.verify = false;
                },

                async resendOtp() {
                    this.loading.resend = true;
                    try {
                        const res = await fetch(`${this.baseUrl}/api/customers/resend-otp`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                            body: JSON.stringify(this.resendForm)
                        });
                        const data = await res.json();
                        this.responses.resend = JSON.stringify(data, null, 2);
                    } catch (e) {
                        this.responses.resend = 'Error: ' + e.message;
                    }
                    this.loading.resend = false;
                },

                async login() {
                    this.loading.login = true;
                    try {
                        const res = await fetch(`${this.baseUrl}/api/customers/login`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                            body: JSON.stringify(this.loginForm)
                        });
                        const data = await res.json();
                        if (data.token) {
                            this.token = data.token;
                        }
                        this.responses.login = JSON.stringify(data, null, 2);
                    } catch (e) {
                        this.responses.login = 'Error: ' + e.message;
                    }
                    this.loading.login = false;
                },

                async forgotPassword() {
                    this.loading.forgot = true;
                    try {
                        const res = await fetch(`${this.baseUrl}/api/customers/forgot-password`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                            body: JSON.stringify(this.forgotForm)
                        });
                        const data = await res.json();
                        this.responses.forgot = JSON.stringify(data, null, 2);
                    } catch (e) {
                        this.responses.forgot = 'Error: ' + e.message;
                    }
                    this.loading.forgot = false;
                },

                async resetPassword() {
                    this.loading.reset = true;
                    try {
                        const res = await fetch(`${this.baseUrl}/api/customers/reset-password`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                            body: JSON.stringify(this.resetForm)
                        });
                        const data = await res.json();
                        this.responses.reset = JSON.stringify(data, null, 2);
                    } catch (e) {
                        this.responses.reset = 'Error: ' + e.message;
                    }
                    this.loading.reset = false;
                },

                async changePassword() {
                    if (!this.token) {
                        alert('Please login first to get a token');
                        return;
                    }
                    this.loading.change = true;
                    try {
                        const res = await fetch(`${this.baseUrl}/api/customers/change-password`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'Authorization': `Bearer ${this.token}`
                            },
                            body: JSON.stringify(this.changeForm)
                        });
                        const data = await res.json();
                        this.responses.change = JSON.stringify(data, null, 2);
                    } catch (e) {
                        this.responses.change = 'Error: ' + e.message;
                    }
                    this.loading.change = false;
                },

                async updateProfile() {
                    if (!this.token) {
                        alert('Please login first to get a token');
                        return;
                    }
                    this.loading.profile = true;
                    try {
                        const formData = new FormData();
                        formData.append('name', this.profileForm.name);
                        if (this.profileForm.phone) formData.append('phone', this.profileForm.phone);
                        if (this.profileForm.nid) formData.append('nid', this.profileForm.nid);
                        if (this.profileForm.image) formData.append('image', this.profileForm.image);

                        const res = await fetch(`${this.baseUrl}/api/customers/update-profile/${this.profileForm.id}`, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Authorization': `Bearer ${this.token}`
                            },
                            body: formData
                        });
                        const data = await res.json();
                        this.responses.profile = JSON.stringify(data, null, 2);
                    } catch (e) {
                        this.responses.profile = 'Error: ' + e.message;
                    }
                    this.loading.profile = false;
                }
            }
        }
    </script>
</body>
</html>