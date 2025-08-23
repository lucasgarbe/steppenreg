<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Successful</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full mx-4">
        <div class="bg-white rounded-lg shadow-md p-8 text-center">
            <!-- Success Icon -->
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-6">
                <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>

            <!-- Success Message -->
            <h1 class="text-2xl font-bold text-gray-900 mb-4">Registration Successful!</h1>
            <p class="text-gray-600 mb-8">
                Thank you for registering! Your registration has been submitted successfully. 
                You will receive a confirmation email shortly with further details about the event.
            </p>

            <!-- Next Steps -->
            <div class="bg-blue-50 rounded-lg p-4 mb-6">
                <h2 class="text-lg font-semibold text-blue-800 mb-2">What's Next?</h2>
                <ul class="text-sm text-blue-700 text-left space-y-1">
                    <li>• You'll receive an email confirmation within 24 hours</li>
                    <li>• Payment instructions will be included in the confirmation email</li>
                    <li>• The draw for event participation will be conducted soon</li>
                    <li>• Keep an eye on your email for updates</li>
                </ul>
            </div>

            <!-- Actions -->
            <div class="space-y-3">
                <a href="{{ route('registration.create') }}" 
                   class="w-full bg-blue-600 text-white px-4 py-2 rounded-md font-medium hover:bg-blue-700 transition duration-200 inline-block">
                    Register Another Participant
                </a>
                <a href="/" 
                   class="w-full bg-gray-100 text-gray-700 px-4 py-2 rounded-md font-medium hover:bg-gray-200 transition duration-200 inline-block">
                    Return to Homepage
                </a>
            </div>
        </div>
    </div>
</body>
</html>