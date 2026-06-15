<?php
// 1. Configuration
$apiKey = 'sk_V2_hgu_kb8Tm9lHwum_LHdCTsvowJ0OlsOACkJ8UsTbeCM7cSFL'; // Replace with your actual key
$apiUrl = 'https://api.heygen.com/v2/video/generate';

// 2. Mock Recipe Data (Simulating the output of your client's script)
$title = "Creamy Garlic Chicken";
$imageUrl = "https://images.unsplash.com/photo-1604908176997-125f25cc6f3d?q=80&w=600"; // Free stock food image

// 3. Constructing the Payload for HeyGen Video Translate / Script-to-Video
$payload = [
    "title" => "Kitchen2MyTable Demo - " . $title,
    "callback_url" => "",
    "dimension" => "9:16", // Force Vertical aspect ratio
    "video_setting" => [
        "background_color" => "#80336b" // Client's purple
    ],
    "clips" => [
        [
            "script" => [
                "type" => "text",
                "text" => "Looking for an easy dinner idea? Today we're making " . $title . ". Start with a few simple ingredients and let Kitchen2MyTable guide the way.",
                "voice_id" => "2d5a4a5643ac44da8aee78229b7d667a" // Example code for a warm female voice
            ]
        ],
        [
            "script" => [
                "type" => "text",
                "text" => "What do you want to cook today? Let Kitchen2MyTable help. Sign up before July 5th and become a Founding Member. Visit Kitchen2MyTable.com",
                "voice_id" => "2d5a4a5643ac44da8aee78229b7d667a"
            ]
        ]
    ]
];

// 4. Send the Request via cURL
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

// 5. Output Result
echo "HTTP Status Code: " . $httpCode . "\n";
echo "Response:\n" . $response;