<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | UserAlfresco</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
</head>
<body class="auth-page">
    <main class="login-shell">
        <section class="login-hero">
            <div class="login-kicker">Alfresco Document Gateway</div>
            <h1>ค้นหาและเปิดเอกสารตามสิทธิ์ของผู้ใช้งาน</h1>
            <p>หน้าเว็บ CI4 นี้เชื่อมต่อกับ UserAlfresco-api แล้วให้ backend เป็นคนจัดการ token/session ให้เรียบร้อย</p>
        </section>

        <section class="login-panel">
            <div class="brand-block">
                <div class="brand-mark">A</div>
                <div>
                    <h2>UserAlfresco</h2>
                    <p>เข้าสู่ระบบด้วย user ของ Alfresco</p>
                </div>
            </div>

            <?php if (! empty($error)): ?>
                <div class="alert alert-error"><?= esc($error) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= site_url('login') ?>" class="login-form">
                <?= csrf_field() ?>

                <label>
                    <span>Username</span>
                    <input type="text" name="username" value="<?= old('username') ?>" autocomplete="username" required autofocus>
                </label>

                <label>
                    <span>Password</span>
                    <input type="password" name="password" autocomplete="current-password" required>
                </label>

                <button type="submit">เข้าสู่ระบบ</button>
            </form>

            <div class="login-note">
                CI4 ส่งข้อมูล login ไปที่ <code>UserAlfresco-api</code> แล้วเก็บ access token ไว้ใน PHP session
            </div>
        </section>
    </main>
</body>
</html>
