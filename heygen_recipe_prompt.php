<?php

/**
 * heygen_recipe_prompt.php
 * Kitchen2MyTable HeyGen Recipe Prompt Builder
 *
 * Purpose:
 * - Enter a paid_recipes recipe ID
 * - Pull title, ingredients, steps, and image_url
 * - Build one copy/paste-ready prompt for HeyGen Script-to-Video / Agent
 */

session_start();

require_once __DIR__ . DIRECTORY_SEPARATOR . 'config.php'; // Must provide $conn as PDO

// Optional admin-only protection. Uncomment when ready.
// if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
//     http_response_code(403);
//     die('Access denied.');
// }

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function public_site_base_url(): string
{
    return 'https://kitchen2mytable.com';
}

function absolute_url(?string $path): string
{
    $path = trim((string)$path);
    if ($path === '') {
        return '';
    }

    if (preg_match('/^https?:\/\//i', $path)) {
        $parsed = parse_url($path);
        $cleanPath = $parsed['path'] ?? '';
        $query = isset($parsed['query']) ? ('?' . $parsed['query']) : '';
        return public_site_base_url() . $cleanPath . $query;
    }

    return public_site_base_url() . '/' . ltrim($path, '/');
}

function clean_recipe_title(string $title): string
{
    $title = trim($title);
    $title = preg_replace('/^\s*Entr[ée]e:\s*/iu', '', $title);
    return trim($title);
}

function short_title_for_overlay(string $title): string
{
    $clean = clean_recipe_title($title);

    if (strpos($clean, '–') !== false) {
        $parts = explode('–', $clean, 2);
        if (!empty($parts[1])) {
            $clean = trim($parts[1]);
        }
    }

    if (strpos($clean, '▶') !== false) {
        $parts = explode('▶', $clean, 2);
        if (!empty($parts[1])) {
            $clean = trim($parts[1]);
        }
    }

    return $clean ?: $title;
}

$recipeId = null;
$recipe = null;
$error = '';
$prompt = '';

if (isset($_GET['id']) && $_GET['id'] !== '') {
    if (!ctype_digit((string)$_GET['id'])) {
        $error = 'Recipe ID must be a number.';
    } else {
        $recipeId = (int)$_GET['id'];

        try {
            $stmt = $conn->prepare("
                SELECT id, title, ingredients, steps, image_url, type, country_of_origin, region_of_origin, dish_type_primary
                FROM paid_recipes
                WHERE id = :id
                LIMIT 1
            ");
            $stmt->execute([':id' => $recipeId]);
            $recipe = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$recipe) {
                $error = "No recipe found for ID {$recipeId}.";
            }
        } catch (Throwable $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

if ($recipe) {
    $title = trim((string)$recipe['title']);
    $overlayTitle = short_title_for_overlay($title);
    $imageUrl = absolute_url($recipe['image_url'] ?? '');
    $ingredients = trim((string)$recipe['ingredients']);
    $steps = trim((string)$recipe['steps']);
    $country = trim((string)($recipe['country_of_origin'] ?? ''));
    $region = trim((string)($recipe['region_of_origin'] ?? ''));
    $dishType = trim((string)($recipe['dish_type_primary'] ?? ''));

    $prompt = <<<PROMPT
Create a 9:16 vertical recipe marketing video for Kitchen2MyTable.

VIDEO STYLE:
Food-first recipe video.
No avatar.
Use narration, captions, light background music, AI-generated visuals, Getty/stock cooking footage, and one exact Kitchen2MyTable recipe image in the final reveal scene.
Total length: 30 to 40 seconds.
Format: 9:16 vertical MP4 for YouTube Shorts, TikTok, Facebook Reels, Instagram Reels, Pinterest Video Pins, and Snapchat Spotlight.

VOICE REQUIREMENTS:
Use a warm, natural adult female narrator.
The voice should be calm, friendly, professional, conversational, and slightly lower pitched.
Avoid a high-pitched voice, cartoon voice, influencer voice, overly excited delivery, or salesperson tone.
The narration should sound like a trusted cooking guide helping someone prepare dinner.

BRAND REQUIREMENTS:
Display the website exactly as:
Kitchen2MyTable.com

Do not hyphenate the website name.
Do not insert spaces into the website name.
Do not rewrite the domain name.
Do not create new slogans or alternate brand names.
Use Kitchen2MyTable.com exactly wherever the website is shown.

Brand colors:
Purple: #80336b
Gold: #f7b948

CTA REQUIREMENTS:
Use the exact CTA shown in Scene 5.
Do not rewrite it.
Do not shorten it.
Do not summarize it.
Do not replace it with different promotional language.

Recipe ID:
{$recipe['id']}

Recipe Title:
{$title}

Short Display Title:
{$overlayTitle}

Exact Kitchen2MyTable Recipe Image URL:
{$imageUrl}

Country / Region / Type:
{$country} {$region} {$dishType}

Ingredients:
{$ingredients}

Instructions:
{$steps}

Build this as a 5-scene food-focused recipe video:

Scene 1 — Hook / Food Hero
Duration: 3–5 seconds
Use AI-generated food visuals or Getty/stock media inspired by the recipe title.
Do not use the exact Kitchen2MyTable recipe image in this opening scene.
Text overlay:
{$overlayTitle}
Narration:
Looking for an easy dinner idea? Today we're making {$overlayTitle}.
Visual style:
Close-up food hero shot, warm lighting, appetizing, social-media-ready.

Scene 2 — Ingredients
Duration: 6–8 seconds
Create an ingredient visual from the ingredient list using AI-generated imagery or stock media.
Text overlay:
Simple 
Ingredients
Narration:
Start with a few simple ingredients and let Kitchen2MyTable guide the way.
Visual style:
Overhead ingredient layout, clean kitchen counter, realistic food photography.

Scene 3 — Cooking / Preparation
Duration: 10–14 seconds
Use stock video or AI-generated visuals based on the recipe instructions.
Text overlay:
Prep-Cook-Enjoy.
Narration:
Follow the steps, bring the flavors together, and cook until everything is tender, flavorful, and ready to serve.
Visual style:
Hands preparing food, realistic cooking action, sauce, heat, steam, and natural kitchen movement.

Scene 4 — Final Recipe Reveal
Duration: 6–8 seconds
This scene must use the exact Kitchen2MyTable recipe image URL below as the full-size final reveal image:
{$imageUrl}

IMAGE PLACEMENT REQUIREMENTS FOR SCENE 4:
The Kitchen2MyTable.com/uploads image URL must be the main full-screen final reveal visual.

Use this exact image URL:
{$imageUrl}

Full-screen centered final reveal. Scale the recipe image literally to the full width of the 9:16 vertical canvas (edge-to-edge). Use a blurred, enlarged copy of the image as the background to fill the frame. Place the sharp original on top, centered vertically and horizontally. Do not shrink, do not use a box or sticker effect, and do not leave empty space. The food must be large and prominent. Slow zoom effect.

Do not generate a similar image for this scene.
Do not recreate the image.
Do not restyle the image.
Do not enhance or reinterpret the image.
Do not use a Getty image in this scene.
Do not use AI-generated food imagery in this scene.
Use the exact provided Kitchen2MyTable recipe image.

The exact Kitchen2MyTable recipe image is required for Scene 4.

Do not substitute text.
Do not create a placeholder.
Do not display instructions.
Do not display an empty frame.
Do not display the image URL as text.
Do not display the words INSERT IMAGE, RECIPE IMAGE, FULL-SCREEN HERE, or any similar placeholder wording.

If the exact image cannot be rendered, return an error instead of generating a substitute scene.

Text overlay:
Delicious. Simple. Ready to Serve.
Narration:
The result is a satisfying meal you can feel good about putting on the table.
Visual style:
Full-screen centered final recipe image, large food presentation, gentle slow zoom on the exact provided image only.

Scene 5 — Kitchen2MyTable Founding Member CTA
Duration: 7–9 seconds
Use a branded CTA screen.
Background color: #80336b
Accent color: #f7b948

Text overlay exactly:
What do you want to cook today?
Let Kitchen2MyTable help.
Sign up before July 5th and become a Founding Member.
Receive complimentary Executive Chef access through 12/31/2026.
Kitchen2MyTable.com

Narration exactly:
What do you want to cook today? Let Kitchen2MyTable help. Sign up before July 5th and become a Founding Member. Receive complimentary Executive Chef access through 12/31/2026.
Visit Kitchen2MyTable.com.

Music:
Light grocery-store style instrumental music.
Warm, relaxed, upbeat, no vocals.
Keep the music under the narration.

Captions:
Turn captions on for all narration.
Use large, readable mobile-first captions.
Do not change the website spelling in captions.
PROMPT;
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Kitchen2MyTable HeyGen Prompt Builder</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
    :root {
        --purple: #80336b;
        --gold: #f7b948;
        --bg: #f7f7f9;
        --card: #ffffff;
        --text: #222222;
        --muted: #666666;
        --border: #dddddd;
    }

    body {
        margin: 0;
        padding: 24px;
        background: var(--bg);
        color: var(--text);
        font-family: Arial, Helvetica, sans-serif;
    }

    .wrap {
        max-width: 1100px;
        margin: 0 auto;
    }

    .box {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 18px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    }

    h1,
    h2 {
        margin-top: 0;
        color: var(--purple);
    }

    label {
        font-weight: bold;
    }

    input[type="number"] {
        width: 140px;
        padding: 10px;
        border: 1px solid var(--border);
        border-radius: 8px;
        font-size: 16px;
    }

    button {
        padding: 10px 16px;
        border: 0;
        border-radius: 8px;
        background: var(--purple);
        color: white;
        font-size: 16px;
        cursor: pointer;
        margin-left: 8px;
    }

    button:hover {
        opacity: 0.92;
    }

    .copy-btn {
        background: var(--gold);
        color: #222;
        margin-left: 0;
        margin-bottom: 10px;
    }

    .error {
        color: #b00020;
        font-weight: bold;
    }

    .meta {
        display: grid;
        grid-template-columns: 180px 1fr;
        gap: 8px 14px;
        word-break: break-word;
    }

    .recipe-img {
        max-width: 360px;
        width: 100%;
        border-radius: 10px;
        margin-top: 14px;
        border: 1px solid var(--border);
    }

    textarea {
        width: 100%;
        min-height: 720px;
        box-sizing: border-box;
        padding: 14px;
        border: 1px solid var(--border);
        border-radius: 10px;
        font-family: Consolas, Monaco, monospace;
        font-size: 14px;
        line-height: 1.45;
        white-space: pre-wrap;
    }

    .hint {
        color: var(--muted);
        font-size: 14px;
        margin-top: 8px;
    }

    .top-row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;
    }

    @media (max-width: 700px) {
        body {
            padding: 14px;
        }

        .meta {
            grid-template-columns: 1fr;
        }

        button {
            margin-left: 0;
        }
    }
    </style>
</head>

<body>
    <div class="wrap">
        <div class="box">
            <h1>Kitchen2MyTable HeyGen Recipe Prompt Builder</h1>
            <div class="hint"><strong>Version:</strong> recipe ID selector + required exact final image reveal + no
                placeholder fallback + Founding Member CTA</div>
            <form method="get" class="top-row">
                <label for="id">Recipe ID:</label>
                <input id="id" type="number" name="id" min="1" value="<?= h($_GET['id'] ?? '') ?>">
                <button type="submit">Build Prompt</button>
            </form>
            <div class="hint">Example: heygen_recipe_prompt.php?id=44</div>
        </div>

        <?php if ($error): ?>
        <div class="box error"><?= h($error) ?></div>
        <?php endif; ?>

        <?php if ($recipe): ?>
        <div class="box">
            <h2>Recipe Loaded</h2>
            <div class="meta">
                <strong>ID</strong><span><?= h($recipe['id']) ?></span>
                <strong>Title</strong><span><?= h($title) ?></span>
                <strong>Display Title</strong><span><?= h($overlayTitle) ?></span>
                <strong>Raw DB Image</strong><span><?= h($recipe['image_url'] ?? '') ?></span>
                <strong>Public Image URL</strong><span><?= h($imageUrl) ?></span>
                <strong>Country</strong><span><?= h($country ?: 'Not set') ?></span>
                <strong>Region</strong><span><?= h($region ?: 'Not set') ?></span>
                <strong>Dish Type</strong><span><?= h($dishType ?: 'Not set') ?></span>
            </div>
            <?php if ($imageUrl): ?>
            <img class="recipe-img" src="<?= h($imageUrl) ?>" alt="Recipe image">
            <?php endif; ?>
        </div>

        <div class="box">
            <h2>Copy/Paste Prompt for HeyGen Script-to-Video</h2>
            <button type="button" class="copy-btn" onclick="copyPrompt()">Copy Prompt</button>
            <div class="hint">Paste this into HeyGen Script-to-Video / Agent and ask it to build the video.</div>
            <br>
            <textarea id="promptBox" onclick="this.select();" readonly><?= h($prompt) ?></textarea>
        </div>
        <?php endif; ?>
    </div>

    <script>
    function copyPrompt() {
        const box = document.getElementById('promptBox');
        if (!box) return;
        box.select();
        box.setSelectionRange(0, 999999);
        navigator.clipboard.writeText(box.value).then(function() {
            alert('Prompt copied.');
        }).catch(function() {
            document.execCommand('copy');
            alert('Prompt copied.');
        });
    }
    </script>
</body>

</html>