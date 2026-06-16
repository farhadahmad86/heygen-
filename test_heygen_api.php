<?php
// 1. Configuration (Updated to the V3 Endpoint from your documentation)
$apiKey = 'sk_V2_hgu_kYG8xXTCkbg_yIgNTLY5Qk30GG1nAgrGzEspeDB9rWle';
$apiUrl = 'https://api.heygen.com/v3/video-agents';

// 2. Mock Recipe Data (Simulating the output of your client's script)
$title = "Creamy Garlic Chicken";
$imageUrl = "https://images.unsplash.com/photo-1604908176997-125f25cc6f3d?q=80&w=600";

// 3. Build the text prompt exactly how your client's tool expects it
$recipePrompt = "Create a 9:16 vertical recipe marketing video for Kitchen2MyTable. "
    . "Recipe Title: {$title}. "
    . "Exact Kitchen2MyTable Recipe Image URL: {$imageUrl}. "
    . "Instructions: Cook chicken until golden brown, add cream, garlic, and spinach. "
    . "Scene 4 Reveal: Scale the recipe image to full width with a blurred background copy. "
    . "Scene 5 CTA: Background #80336b, Gold text #f7b948 saying: What do you want to cook today? Let Kitchen2MyTable help. Sign up before July 5th and become a Founding Member. Kitchen2MyTable.com";

// 4. Constructing the Payload matching V3 Documentation
$payload = [
    "prompt" => $recipePrompt
];

// 5. Send the Request via cURL
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-Api-Key: ' . $apiKey
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// 6. Output Result
echo "HTTP Status Code: " . $httpCode . "\n";
echo "Response:\n" . $response;