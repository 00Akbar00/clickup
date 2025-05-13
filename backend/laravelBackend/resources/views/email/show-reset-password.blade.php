<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
</head>
<body>
    <h2>Reset Your Password</h2>

    @if(session('error'))
        <p style="color:red">{{ session('error') }}</p>
    @endif

    <form method="POST" action="{{ url('api/reset-password/' . $token) }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <label for="password">New Password:</label><br>
        <input type="password" name="password" required><br><br>

        <label for="password_confirmation">Confirm Password:</label><br>
        <input type="password" name="password_confirmation" required><br><br>

        <button type="submit">Reset Password</button>
    </form>
</body>
</html>
