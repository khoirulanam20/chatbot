<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Permintaan Demo</title>
</head>
<body style="font-family: sans-serif; line-height: 1.6; color: #111;">
    <h2>Permintaan Demo Baru</h2>
    <p><strong>Nama:</strong> {{ $inquiry['name'] }}</p>
    <p><strong>Email:</strong> {{ $inquiry['email'] }}</p>
    <p><strong>Perusahaan:</strong> {{ $inquiry['company'] }}</p>
    <p><strong>Pesan:</strong></p>
    <p style="white-space: pre-wrap;">{{ $inquiry['message'] }}</p>
</body>
</html>
