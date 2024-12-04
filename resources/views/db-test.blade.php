
<!DOCTYPE html>
<html>
<head>
    <title>Database Connection Test</title>
    <style>
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <h1>Database Connection Status</h1>
    
    <div class="{{ $status === 'Connected' ? 'success' : 'error' }}">
        <h2>Status: {{ $status }}</h2>
        <p>{{ $message }}</p>
        @if($error)
            <p>Error details: {{ $error }}</p>
        @endif
    </div>
</body>
</html>