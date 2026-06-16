<?php

/**
 * heygen_recipe_prompt.php
 * Kitchen2MyTable 1-Click Automated HeyGen Video Generator (V3 API)
 * Optimized with a premium Tailwind CSS interface, dynamic database recipe closet,
 * live prompt step-by-step preview/customization, and a robust Fetch API status loop.
 */

session_start();

// Enable Error Reporting for secure validation and debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Secure db config loading
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
} else {
    $conn = null;
}

// XSS mitigation helper
function h(?string $value): string
{
    return htmlspecialchars(($value !== null && $value !== '') ? (string)$value : '', ENT_QUOTES, 'UTF-8');
}

function public_site_base_url(): string
{
    return 'https://kitchen2mytable.com';
}

function absolute_url(?string $path): string
{
    $path = trim((string)$path);
    if ($path === '') return '';
    if (preg_match('/^https?:\/\//i', $path)) return $path;
    return public_site_base_url() . '/' . ltrim($path, '/');
}

// Global Variables
$recipeId = null;
$recipe = null;
$error = '';
$sessionId = null;
$recipePrompt = '';
$previewMode = false;
$allRecipes = [];

// Selected variables to persist between steps
$selectedRatio = '9:16';
$selectedVoice = 'Chef Sophia (Upbeat)';
$targetDuration = 60;
$customImageUrl = '';

// AJAX ENDPOINTS CONTAINER: Handles asynchronous polling endpoints securely
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    $apiKey = 'sk_V2_hgu_kYG8xXTCkbg_yIgNTLY5Qk30GG1nAgrGzEspeDB9rWle';
    $sessId = $_GET['session_id'] ?? '';
    $vidId = $_GET['video_id'] ?? '';

    if ($_GET['action'] === 'get_video_id' && !empty($sessId)) {
        $ch = curl_init("https://api.heygen.com/v3/video-agents/$sessId");
        $ch = curl_init("https://api.heygen.com/v3/video-agents/$sessId");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Api-Key: ' . $apiKey]);
        $rawRes = curl_exec($ch);
        $resCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $response = json_decode($rawRes, true);
        if ($resCode === 200 && isset($response['data']['video_id'])) {
            echo json_encode(['success' => true, 'video_id' => $response['data']['video_id']]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => $response['error']['message'] ?? 'Pending assignment or synthesis initialized'
            ]);
        }
        exit;
    }

    if ($_GET['action'] === 'check_status' && !empty($vidId)) {
        $ch = curl_init("https://api.heygen.com/v3/videos/$vidId");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Api-Key: ' . $apiKey]);
        $rawRes = curl_exec($ch);
        $resCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $response = json_decode($rawRes, true);
        $status = $response['data']['status'] ?? 'pending';
        $videoUrl = $response['data']['video_url'] ?? '';
        echo json_encode(['success' => true, 'status' => $status, 'video_url' => $videoUrl]);
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Invalid action']);
    exit;
}

// PHASE 1: Form POST Processing
if (isset($_POST['id']) && $_POST['id'] !== '' && !isset($_POST['confirmed_prompt'])) {
    $recipeId = (int)$_POST['id'];
    $selectedRatio = $_POST['aspect_ratio'] ?? '9:16';
    $selectedVoice = $_POST['voice'] ?? 'Chef Sophia (Upbeat)';
    $targetDuration = isset($_POST['duration']) ? (int)$_POST['duration'] : 60;
    $customImageUrl = trim($_POST['custom_image_url'] ?? '');

    if ($conn) {
        try {
            $stmt = $conn->prepare("SELECT id, title, ingredients, steps, image_url FROM paid_recipes WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $recipeId]);
            $recipe = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$recipe) {
                $mockTable = [
                    1 => [
                        'id' => 1,
                        'title' => 'Entrée: United States – Grilled Sirloin Steak with Garlic Herb Butter',
                        'ingredients' => "- 2 sirloin steaks (about 8 oz / 225 g each)\n- Salt and pepper to taste\n- 2 tbsp (30 ml) olive oil\n- 4 tbsp (60 g) unsalted butter, softened",
                        'steps' => "1. Season sirloin steaks generously.\n2. Sear and top with delicious butter.",
                        'image_url' => '/uploads/grilled_sirloin_steak.jpg'
                    ]
                ];
                $recipe = $mockTable[$recipeId] ?? $mockTable[1];
            }
        } catch (Throwable $e) {
            $error = 'Database row query failed: ' . $e->getMessage();
        }
    } else {
        $recipe = [
            'id' => $recipeId,
            'title' => "Simulated Recipe (Database Offline)",
            'ingredients' => "1 premium steak ribeye, coarse sea salt, crushed peppercorns, organic butter blocks.",
            'steps' => "1. Dry steak.\n2. Sear over high heat.\n3. Baste with crushed herbs.",
            'image_url' => "https://images.unsplash.com/photo-1544025162-d76694265947",
        ];
    }

    if ($recipe) {
        $title = trim((string)$recipe['title']);
        $ingredients = trim((string)$recipe['ingredients']);
        $steps = trim((string)$recipe['steps']);

        // Image logic determination: Explicit custom URL configuration override takes precedence
        if (!empty($customImageUrl)) {
            $imageUrl = $customImageUrl;
        } else {
            $imageUrl = absolute_url($recipe['image_url'] ?? '');
        }

        // Scene timeline budgeting logic based on user input duration values
        $scene1Duration = round($targetDuration * 0.10);
        $scene2Duration = round($targetDuration * 0.18);
        $scene3Duration = round($targetDuration * 0.30);
        $scene4Duration = round($targetDuration * 0.17);
        $scene5Duration = $targetDuration - ($scene1Duration + $scene2Duration + $scene3Duration + $scene4Duration);

        $recipePrompt = "Create a premium meal advertising short-form recipe video for Kitchen2MyTable.\n\n"
            . "=== KEY TECHNICAL SPECIFICATIONS ===\n"
            . "* VIDEO ASPECT RATIO MODEL: {$selectedRatio} (" . ($selectedRatio === '9:16' ? 'Vertical Portrait layout' : 'Landscape Cinema layout') . ").\n"
            . "* VOICE PROFILE NARRATION ONLY (NO AVATAR): {$selectedVoice}\n"
            . "  - Voice settings: Use a warm, natural, mature female voice that sounds calm, professional, and confident (like an expert culinary guide). Strictly avoid over-excited delivery, loud hyping, or casual influencer-style tone.\n"
            . "  - Background Music: Light, pleasant instrumental background music with absolutely NO vocals, playing softly and continuously under the voiceover narration.\n"
            . "  - Captions: Enable automatic readable on-screen subtitles/captions that capture the voiceover verbatim for mobile viewers.\n"
            . "* NO HUMAN OR CHEF VISUALS: Strictly DO NOT show any person, talking avatar, or chef in the video track. No talking human models, faces, or human hands should be visible. The visual track must solely consist of high-quality food visuals, readable text overlays, beautiful plated reveals, and brand backdrops.\n"
            . "* STRICT DURATION CONSTRAINT: The overall video length MUST be exactly around {$targetDuration} seconds.\n\n"
            . "=== THE RECIPE INSTRUCTIONS ===\n"
            . "Recipe Title: {$title}\n"
            . "Ingredients: {$ingredients}\n"
            . "Source Image Asset URL: {$imageUrl}\n\n"
            . "Cooking steps:\n"
            . "{$steps}\n\n"
            . "=== SCENIC TIMELINE DIRECTIVE ===\n"
            . "* Scene 1 - Hook / Food Hero (Duration: {$scene1Duration} seconds):\n"
            . "  - Visual: High-quality close-up cooking or stock food footage matching the recipe category (e.g. if steak is cooking/sizzling on a grill). The primary image URL ({$imageUrl}) must NOT be shown yet in this scene.\n"
            . "  - Screen Text: Display the recipe title \"{$title}\" prominently with elegant, clear, high-contrast overlay text.\n"
            . "  - Voiceover: \"Looking for an easy dinner idea? Today we're making {$title}.\"\n\n"
            . "* Scene 2 - Ingredients Showcase & Overlay (Duration: {$scene2Duration} seconds):\n"
            . "  - Visual: Overhead-style culinary layout of fresh kitchen counter ingredients.\n"
            . "  - Screen Text: High-contrast, dynamic, clearly animated list displaying the recipe ingredients: \"{$ingredients}\" on screen.\n"
            . "  - Voiceover: \"Start with a few simple ingredients: {$ingredients}. Let Kitchen2MyTable guide the way.\"\n\n"
            . "* Scene 3 - Cooking Steps & Overlay (Duration: {$scene3Duration} seconds):\n"
            . "  - Visual: Real kitchen motion shots: hands preparating/cooking, sauce simmering in a pan, beautiful steam and heat effects.\n"
            . "  - Screen Text: High-contrast sequential step-by-step instructions showing the recipe steps: \"{$steps}\" written on screen.\n"
            . "  - Voiceover: \"Here is how to make it: {$steps}. Bring the flavors together, and cook until everything is flavorful and ready to serve.\"\n\n"
            . "* Scene 4 - Final Recipe Reveal (Duration: {$scene4Duration} seconds):\n"
            . "  - Visual: STRICTLY AND EXCLUSIVELY show the exact recipe picture: \"{$imageUrl}\". No AI or other stock imagery allowed. Display the image centered and edge-to-edge on the canvas. Use a blurred, enlarged copy of this same recipe image in the background to fill empty side spaces beautifully, with a gentle slow cinematic zoom panning across the image.\n"
            . "  - Screen Text: \"Delicious. Simple. Ready to Serve.\"\n"
            . "  - Voiceover: \"The result is a satisfying meal you can feel good about putting on the table.\"\n\n"
            . "* Scene 5 - Branded Founding Member CTA (Duration: {$scene5Duration} seconds):\n"
            . "  - Visual: Clean closing brand screen on the signature Purple background (#80336b) with elegant Gold (#f7b948) highlight text overlays.\n"
            . "  - Screen Text & Voiceover (VERBATIM SAME TEXT FOR BOTH OVERLAY AND NARRATION):\n"
            . "    \"What do you want to cook today? Let Kitchen2MyTable help. Sign up before July 5th and become a Founding Member. Receive complimentary Executive Chef access through 12/31/2026. Visit Kitchen2MyTable.com\"";

        $previewMode = true;
    }
}

// PHASE 2: Form POST Processing
if (isset($_POST['confirmed_prompt']) && $_POST['confirmed_prompt'] !== '') {
    $recipeId = (int)$_POST['id'];
    $recipePrompt = $_POST['confirmed_prompt'];
    $selectedRatio = $_POST['aspect_ratio_confirmed'] ?? '9:16';
    $selectedVoice = $_POST['voice_confirmed'] ?? 'Chef Sophia';

    $apiKey = 'sk_V2_hgu_kYG8xXTCkbg_yIgNTLY5Qk30GG1nAgrGzEspeDB9rWle';
    $apiUrl = 'https://api.heygen.com/v3/video-agents';

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-Api-Key: ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["prompt" => $recipePrompt]));

    $apiResponse = curl_exec($ch);
    $resCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $resData = json_decode($apiResponse, true);
    curl_close($ch);

    if ($resCode === 200 && isset($resData['data']['session_id'])) {
        $sessionId = $resData['data']['session_id'];
    } else {
        $error = "HeyGen Handshake aborted: " . ($resData['error']['message'] ?? "HTTP Status code {$resCode}");
    }
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Kitchen2MyTable • Master Video Control Suite</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap"
        rel="stylesheet font-sans">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    tailwind.config = {
        theme: {
            extend: {
                fontFamily: {
                    sans: ['Outfit', 'sans-serif'],
                    mono: ['JetBrains Mono', 'monospace'],
                },
                colors: {
                    brand: {
                        purple: '#80336b',
                        purpleLight: '#993f80',
                        purpleBg: '#fdf1fa',
                        gold: '#f7b948',
                        goldDark: '#e0a334',
                    }
                }
            }
        }
    }
    </script>
    <style>
    body {
        background-color: #030712;
        background-image: radial-gradient(#80336b 1px, transparent 0);
        background-size: 24px 24px;
    }

    .luxury-card {
        background: rgba(15, 23, 42, 0.7);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(128, 51, 107, 0.4);
    }
    </style>
</head>

<body class="text-slate-100 min-h-screen py-10 px-4 md:px-6 flex items-center justify-center font-sans tracking-tight">
    <div class="fixed top-0 left-1/4 w-96 h-96 bg-brand-purple/10 rounded-full blur-[140px] pointer-events-none"></div>
    <div
        class="fixed bottom-0 right-1/4 w-[30rem] h-[30rem] bg-brand-gold/5 rounded-full blur-[160px] pointer-events-none">
    </div>

    <div class="w-full max-w-2xl luxury-card rounded-3xl p-6 md:p-8 shadow-2xl relative overflow-hidden transition-all">
        <div class="flex items-center gap-4 mb-6">
            <div
                class="w-14 h-14 bg-slate-900 border border-brand-purple/40 rounded-2xl flex items-center justify-center shadow-lg p-1.5 overflow-hidden">
                <img src="logoo.png" alt="Kitchen2MyTable Logo" class="w-full h-full object-contain error-fallback">
                <!-- <div
                    class="hidden w-full h-full bg-gradient-to-tr from-brand-purple to-brand-gold rounded-xl items-center justify-center font-bold text-slate-950 text-sm">
                    K2M</div> -->
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-bold text-white tracking-tight">Kitchen2MyTable</h1>
                    <span
                        class="text-[10px] font-mono tracking-wider text-brand-gold uppercase bg-brand-purple/50 px-2 py-0.5 rounded-full font-bold border border-brand-purple/30">Suite
                        V3.5</span>
                </div>
                <!-- <p class="text-xs text-slate-400 font-sans">Database-aligned script compiler configured dynamically for
                    timeline specifications & visual layers</p> -->
            </div>
        </div>

        <?php if ($error): ?>
        <div
            class="mb-6 p-4 rounded-xl bg-red-950/40 border border-red-500/35 text-red-200 text-xs flex items-start gap-2">
            <span><strong>Pipeline Error:</strong> <?= h($error) ?></span>
        </div>
        <?php endif; ?>

        <form method="post" id="genForm" class="space-y-6 <?= ($previewMode || $sessionId) ? 'hidden' : ''; ?>">
            <div class="bg-slate-900/50 rounded-2xl p-5 border border-slate-800/60 text-left space-y-4">
                <label for="recipeIdInput"
                    class="block text-xs font-bold font-mono tracking-wider text-brand-gold uppercase">1. Input Recipe
                    ID Target</label>
                <input type="number" id="recipeIdInput" name="id" min="1" value="<?= h($recipeId ?? '1') ?>" required
                    class="w-full bg-slate-950 border border-slate-800 focus:border-brand-purple rounded-xl py-3.5 px-4 text-xs font-medium text-slate-300 focus:outline-none placeholder-slate-600"
                    placeholder="e.g. 1">
            </div>

            <div class="bg-slate-900/50 rounded-2xl p-5 border border-slate-800/60 text-left space-y-5">
                <h3 class="text-xs font-bold font-mono tracking-wider text-brand-gold uppercase">2. Media &amp; Timeline
                    Specifications</h3>

                <div class="space-y-2">
                    <div class="flex justify-between items-center">
                        <label for="durationSlider" class="block text-xs font-semibold text-slate-300">Target Video
                            Timeline Duration</label>
                        <span id="durationBadge"
                            class="text-xs font-mono font-bold text-brand-purple px-2 py-0.5 bg-brand-purple/20 border border-brand-purple/40 rounded-md">60
                            seconds</span>
                    </div>
                    <input type="range" id="durationSlider" name="duration" min="15" max="90"
                        value="<?= h($targetDuration) ?>"
                        class="w-full h-1.5 bg-slate-950 rounded-lg appearance-none cursor-pointer accent-brand-purple border border-slate-800"
                        oninput="document.getElementById('durationBadge').innerText = this.value + ' seconds';">
                    <div class="flex justify-between text-[10px] text-slate-500 font-mono">
                        <span>15s (Shorts)</span>
                        <span>45s</span>
                        <span>60s (Standard)</span>
                        <span>90s (Longform)</span>
                    </div>
                </div>

                <div class="space-y-2">
                    <label for="customImageUrlInput" class="block text-xs font-semibold text-slate-300">Custom Scene
                        Image Asset URL (Optional Override)</label>
                    <input type="url" id="customImageUrlInput" name="custom_image_url" value="<?= h($customImageUrl) ?>"
                        class="w-full bg-slate-950 border border-slate-800 focus:border-brand-purple rounded-xl py-3 px-4 text-xs font-medium text-slate-300 focus:outline-none placeholder-slate-600"
                        placeholder="https://domain.com/path/to/recipe-image.jpg">
                    <p class="text-[10px] text-slate-500 font-mono">Leave blank to inherit primary image assets
                        dynamically indexed within database records.</p>
                </div>
            </div>

            <div class="bg-slate-900/50 rounded-2xl p-5 border border-slate-800/60 text-left space-y-5">
                <h3 class="text-xs font-bold font-mono tracking-wider text-brand-gold uppercase">3. Configuration &amp;
                    Directing Parameters</h3>

                <div class="space-y-2">
                    <label class="block text-xs font-semibold text-slate-300">Format Layout Orientation</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label
                            class="flex items-center justify-between p-3 rounded-xl border border-slate-800 bg-slate-950/60 cursor-pointer hover:border-brand-purple transition-all">
                            <div class="flex items-center gap-2">
                                <input type="radio" name="aspect_ratio" value="9:16" checked
                                    class="accent-brand-purple">
                                <span class="text-xs font-bold text-white">Portrait 9:16</span>
                            </div>
                        </label>
                        <label
                            class="flex items-center justify-between p-3 rounded-xl border border-slate-800 bg-slate-950/60 cursor-pointer hover:border-brand-purple transition-all">
                            <div class="flex items-center gap-2">
                                <input type="radio" name="aspect_ratio" value="16:9" class="accent-brand-purple">
                                <span class="text-xs font-bold text-white">Landscape 16:9</span>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="space-y-2">
                    <label for="voiceSelect" class="block text-xs font-semibold text-slate-300">Voice Profile Narrator
                        Agent</label>
                    <select id="voiceSelect" name="voice"
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl py-3 px-4 text-xs font-medium text-slate-300 focus:outline-none focus:border-brand-purple">
                        <option value="Chef Sophia (Upbeat Narrator)">Chef Sophia — Energetic, warm influencer tone
                        </option>
                        <option value="Gourmet Michael (Elegant Accent)">Gourmet Michael — Deep, sophisticated narration
                        </option>
                    </select>
                </div>
            </div>

            <button type="submit"
                class="w-full bg-brand-purple hover:bg-brand-purpleLight text-white rounded-xl py-4 font-bold text-xs uppercase tracking-wider transition-all shadow-lg shadow-brand-purple/20">
                Generate Recipe Script First
            </button>
        </form>

        <?php if ($previewMode && !$sessionId): ?>
        <div id="previewBlock" class="space-y-4 text-left animate-fadeIn">
            <h3 class="text-sm font-bold uppercase tracking-wider text-brand-gold">Recipe Script &amp; Directing Overlay
                Matrix</h3>
            <form method="post" id="confirmForm" class="space-y-4">
                <input type="hidden" name="id" value="<?= h($recipeId) ?>">
                <input type="hidden" name="aspect_ratio_confirmed" value="<?= h($selectedRatio) ?>">
                <input type="hidden" name="voice_confirmed" value="<?= h($selectedVoice) ?>">
                <textarea name="confirmed_prompt"
                    class="w-full h-80 bg-slate-950/90 border border-slate-800 focus:border-brand-purple rounded-2xl p-4 text-xs font-mono text-slate-300 leading-relaxed outline-none resize-none shadow-inner"><?= h($recipePrompt) ?></textarea>

                <div class="flex gap-3">
                    <button type="submit"
                        class="flex-[2] bg-brand-purple hover:bg-brand-purpleLight text-white text-xs font-bold uppercase py-3.5 rounded-xl tracking-wider transition-all shadow-md">Launch
                        Handshake &amp; Start Compilation</button>
                    <a href="?"
                        class="flex-1 text-center bg-slate-900 text-slate-400 text-xs font-bold uppercase py-3.5 rounded-xl border border-slate-800 block hover:text-white transition-colors">Back</a>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <div id="loadingStatus" class="hidden space-y-4 text-left">
            <div class="flex items-center justify-between border-b border-slate-800/60 pb-3">
                <div class="flex items-center gap-3">
                    <div class="relative w-8 h-8 flex items-center justify-center">
                        <div
                            class="absolute inset-0 rounded-full border-2 border-slate-900 border-t-brand-purple animate-spin">
                        </div>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-white text-mono animate-pulse" id="statusTitle">Connecting
                            Rendering Engines...</h4>
                        <p class="text-[10px] text-slate-500 font-mono" id="statusBriefing">Broadcasting prompt payload
                            to HeyGen v3 queue</p>
                    </div>
                </div>
                <span class="text-xs font-mono text-brand-gold font-bold" id="progressPercentage">5%</span>
            </div>

            <div class="h-2 w-full bg-slate-950 rounded-full overflow-hidden border border-slate-900">
                <div id="progressBar"
                    class="h-full bg-gradient-to-r from-brand-purple to-brand-gold rounded-full w-[5%] transition-all duration-700">
                </div>
            </div>
            <div class="space-y-1.5 bg-slate-950/80 p-4 border border-slate-800 rounded-xl h-40 overflow-y-auto font-mono text-[10px] text-slate-400"
                id="logTerminal"></div>
        </div>

        <div id="videoDisplay" class="hidden space-y-5 text-center">
            <div id="videoFrameWrapper"
                class="bg-black p-3.5 rounded-3xl border-4 border-brand-purple/60 shadow-2xl relative overflow-hidden mx-auto">
                <div class="bg-slate-950 rounded-xl overflow-hidden mt-2 relative animate-pulse" id="videoContainer">
                    <video id="player" controls class="w-full h-full object-cover"></video>
                </div>
            </div>
            <a id="downloadLink" href="" download
                class="w-full bg-brand-gold hover:bg-brand-goldDark text-slate-950 rounded-xl py-3.5 font-bold text-xs uppercase block text-center transition-transform shadow-lg transform hover:scale-[1.02]"
                target="_blank">Download Production Asset</a>
            <a href="?"
                class="text-xs text-slate-500 font-semibold uppercase hover:text-slate-400 block transition-colors mt-2">Back
                to closet</a>
        </div>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const sessionId = "<?= $sessionId ?? '' ?>";
        const selectedRatio = "<?= h($selectedRatio ?? '9:16') ?>";

        const wrapper = document.getElementById('videoFrameWrapper');
        const container = document.getElementById('videoContainer');
        if (wrapper && container) {
            if (selectedRatio === '16:9') {
                wrapper.className =
                    "bg-black p-3.5 rounded-2xl border-4 border-brand-purple/60 shadow-2xl relative w-[100%] max-w-xl overflow-hidden mx-auto";
                container.className = "bg-slate-950 h-64 rounded-lg overflow-hidden mt-2 relative";
            } else {
                wrapper.className =
                    "bg-black p-3.5 rounded-3xl border-4 border-brand-purple/60 shadow-2xl relative w-64 md:w-72 overflow-hidden mx-auto";
                container.className = "bg-slate-950 h-96 rounded-xl overflow-hidden mt-2 relative";
            }
        }

        if (sessionId) {
            if (document.getElementById('genForm')) document.getElementById('genForm').style.display = 'none';
            if (document.getElementById('previewBlock')) document.getElementById('previewBlock').style.display =
                'none';
            document.getElementById('loadingStatus').classList.remove('hidden');

            const statusTitle = document.getElementById('statusTitle');
            const statusBriefing = document.getElementById('statusBriefing');
            const progressPercentage = document.getElementById('progressPercentage');
            const progressBar = document.getElementById('progressBar');
            const terminal = document.getElementById('logTerminal');

            let videoId = null;
            let currentProgress = 5;

            function addLog(msg, isSuccess = false) {
                const row = document.createElement('div');
                row.className = isSuccess ? 'text-emerald-400' : 'text-slate-400';
                row.innerText = '[' + new Date().toLocaleTimeString() + '] ' + msg;
                terminal.appendChild(row);
                terminal.scrollTop = terminal.scrollHeight;
            }

            addLog("Executing server-to-server handshake validation script...", true);

            const progressInterval = setInterval(() => {
                if (currentProgress < 92) {
                    currentProgress += (92 - currentProgress) * 0.08;
                    const val = Math.round(currentProgress);
                    progressBar.style.width = val + '%';
                    progressPercentage.innerText = val + '%';
                }
            }, 3000);

            const pollInterval = setInterval(() => {
                if (!videoId) {
                    fetch('?action=get_video_id&session_id=' + sessionId)
                        .then(res => res.json())
                        .then(data => {
                            if (data.success && data.video_id) {
                                videoId = data.video_id;
                                statusTitle.innerText = "Synthesis Engine Initialized";
                                statusBriefing.innerText =
                                    "Script parsed and visual layers compiled";
                                addLog("HeyGen active Video ID successfully assigned: " + videoId,
                                    true);
                            }
                        });
                } else {
                    fetch('?action=check_status&video_id=' + videoId)
                        .then(res => res.json())
                        .then(data => {
                            if (data.status === 'completed') {
                                clearInterval(pollInterval);
                                clearInterval(progressInterval);
                                progressBar.style.width = '100%';
                                progressPercentage.innerText = '100%';
                                setTimeout(() => {
                                    document.getElementById('loadingStatus').classList.add(
                                        'hidden');
                                    document.getElementById('player').src = data.video_url;
                                    document.getElementById('downloadLink').href = data
                                        .video_url;
                                    document.getElementById('videoDisplay').classList
                                        .remove('hidden');
                                }, 1000);
                            }
                        });
                }
            }, 12000);
        }
    });
    </script>
</body>

</html>