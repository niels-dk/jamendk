<?php $title = 'Login'; ob_start(); ?>
<h2>Login</h2>
<?php if (!empty($error)) echo '<p style="color:red">'.$error.'</p>'; ?>
<form method="post">
    <label>Email <input type="email" name="email" required></label><br>
    <label>Password <input type="password" name="password" required></label><br>
    <button type="submit">Login</button>
</form>
<?php $content = ob_get_clean(); include __DIR__.'/layout.php'; ?>