<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | UserAlfresco</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
</head>
<body class="auth-page">
    <main class="login-shell">
        <section class="login-hero">
        </section>

        <section class="login-panel">
            <div class="brand-block">
                <!-- <div class="brand-mark">A</div> -->
                <div>
                    <h2>ระบบ E-Documents Alfresco</h2>
                    <!-- <p>เข้าสู่ระบบด้วย user ของ Alfresco</p> -->
                </div>
            </div>

            <form id="loginForm" method="post" action="<?= site_url('login') ?>" class="login-form" novalidate>
                <?= csrf_field() ?>

                <label>
                    <span>ชื่อผู้ใช้งาน</span>
                    <div class="login-input">
                        <svg class="login-input-icon" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                            <path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H3Zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/>
                        </svg>
                        <input type="text" name="username" value="<?= old('username') ?>" autocomplete="username" required autofocus>
                    </div>
                </label>

                <label>
                    <span>รหัสผ่าน</span>
                    <div class="login-input">
                        <svg class="login-input-icon" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                            <path d="M8 1a3 3 0 0 0-3 3v2H4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-1V4a3 3 0 0 0-3-3Zm2 5H6V4a2 2 0 1 1 4 0v2Z"/>
                        </svg>
                        <input type="password" name="password" autocomplete="current-password" required>
                    </div>
                </label>

                <button type="submit">เข้าสู่ระบบ</button>
            </form>

            <div class="login-note">
                <!-- CI4 ส่งข้อมูล login ไปที่ <code>UserAlfresco-api</code> แล้วเก็บ access token ไว้ใน PHP session -->
            </div>
        </section>
    </main>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
        toastr.options = {
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-top-center',
            timeOut: 3500
        };

        const serverError = <?= json_encode($error ?? null, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

        if (serverError) {
            toastr.error(serverError, 'แจ้งเตือน');
        }

        document.getElementById('loginForm').addEventListener('submit', function (event) {
            const usernameInput = this.elements.username;
            const passwordInput = this.elements.password;

            if (usernameInput.value.trim() === '') {
                event.preventDefault();
                toastr.warning('กรุณากรอกชื่อผู้ใช้งาน', 'แจ้งเตือน');
                usernameInput.focus();
                return;
            }

            if (passwordInput.value === '') {
                event.preventDefault();
                toastr.warning('กรุณากรอกรหัสผ่าน', 'แจ้งเตือน');
                passwordInput.focus();
            }
        });
    </script>
</body>
</html>
