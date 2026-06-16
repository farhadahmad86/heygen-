<?php

/**
 * config.php
 * Premium Mock Sandbox Configuration for Local Testing
 * Holds 5 real data records matching the Kitchen2MyTable structural definitions.
 */

class MockPDO
{
    public function prepare($sql)
    {
        return new MockPDOStatement();
    }
}

class MockPDOStatement
{
    private $recipeId = 1;

    public function execute($params)
    {
        // Capture the incoming ID passed by the controller binding array
        if (isset($params[':id'])) {
            $this->recipeId = (int)$params[':id'];
        }
        return true;
    }

    public function fetch()
    {
        // 11,000 Recipe Mock Relational Index Table
        $recipesTable = [
            1 => [
                'id' => 1,
                'title' => 'Entrée: United States – Grilled Sirloin Steak with Garlic Herb Butter',
                'ingredients' => "- 2 sirloin steaks (about 8 oz / 225 g each)\n- Salt and pepper to taste\n- 2 tbsp (30 ml) olive oil\n- 4 tbsp (60 g) unsalted butter, softened\n- 3 cloves garlic, minced\n- 1 tbsp chopped fresh parsley\n- 1 tbsp chopped fresh thyme\n- 1 tsp lemon juice",
                'steps' => "1. Season the sirloin steaks generously with salt and pepper. Drizzle with olive oil.\n2. Combine butter, minced garlic, parsley, thyme, and lemon juice in a small bowl.\n3. Place the steaks on a medium-high grill and cook for 4-5 minutes per side.\n4. Rest steaks for 5 minutes, then top each with a generous scoop of the garlic herb butter.\n5. Slice the steaks against the grain and serve immediately.",
                'image_url' => '/uploads/grilled_sirloin_steak.jpg'
            ],
            2 => [
                'id' => 2,
                'title' => 'Entrée: Japan – 鶏肉の照り焼き ▶ Toriniku no Teriyaki (Teriyaki Chicken Wings)',
                'ingredients' => "- Whole chicken wings, about 2 lb (900 g)\n- 1/2 cup (120 ml) soy sauce\n- 1/4 cup (50 g) brown sugar\n- 2 tbsp (30 ml) honey\n- 2 cloves garlic, minced\n- 1 tsp (5 ml) grated ginger\n- Sesame seeds, to taste (optional)",
                'steps' => "1. In a mixing bowl, combine soy sauce, brown sugar, honey, garlic, and ginger to form a marinade.\n2. Place chicken wings in a large zip-top bag with marinade. Refrigerate for at least 1 hour.\n3. Preheat oven to 375°F (190°C) and place wings on a baking sheet. Bake for 25-30 minutes, turning once.\n4. Broil wings for an additional 3-5 minutes for extra caramelization. Sprinkle with sesame seeds.",
                'image_url' => '/uploads/toriniku_no_teriyaki.jpg'
            ],
            3 => [
                'id' => 3,
                'title' => 'Entrée: United States – Grilled Pork Chops',
                'ingredients' => "- 4 bone-in pork chops — about 28 oz total (794 g total)\n- 2 tbsp (30 ml) olive oil\n- 1 tsp salt\n- 1 tsp black pepper\n- 1 tsp garlic powder\n- 1 tsp paprika\n- 1 tbsp (15 ml) fresh lemon juice",
                'steps' => "1. Pat the pork chops dry. In a small bowl, mix the olive oil, salt, black pepper, garlic powder, paprika, and lemon juice.\n2. Rub the seasoning mixture evenly over both sides of the pork chops.\n3. Preheat your grill to medium-high heat. Grill chops for about 4-5 minutes on each side until charred and cooked through.\n4. Remove from the grill and let rest for a few minutes before serving.",
                'image_url' => '/uploads/grilled_pork_chops.jpg'
            ],
            4 => [
                'id' => 4,
                'title' => 'Entrée: United States – Pork Chop with Apple Sauce',
                'ingredients' => "- 4 boneless pork chops, about 1.5 lb total (680 g total)\n- 1 tbsp (15 ml) vegetable oil\n- 1 tsp salt\n- 1 tsp black pepper\n- 2 apples, peeled and diced\n- 1/2 cup (120 ml) apple cider\n- 2 tbsp (30 ml) brown sugar\n- 1/2 tsp cinnamon",
                'steps' => "1. Season pork chops with salt and pepper. Heat vegetable oil in a skillet over medium-high heat.\n2. Cook pork chops for 4-5 minutes on each side until golden brown and cooked through. Set aside to rest.\n3. In the same skillet, add diced apples, apple cider, brown sugar, and cinnamon.\n4. Cook until apples are soft and sauce has thickened (5-7 minutes). Serve over the pork chops.",
                'image_url' => '/uploads/pork_chop_apple_sauce.jpg'
            ],
            5 => [
                'id' => 5,
                'title' => 'Entrée: United States – Herb-Crusted Pork Chops',
                'ingredients' => "- 4 pork chops, about 1.5 lb total (680 g total)\n- 2 tbsp (30 ml) Dijon mustard\n- 1 tbsp (15 ml) olive oil\n- 1/2 tsp salt\n- 1/2 tsp black pepper\n- 1/2 cup (60 g) breadcrumbs\n- 2 tbsp (30 ml) chopped fresh parsley\n- 1 tbsp (15 ml) chopped fresh rosemary",
                'steps' => "1. Preheat oven to 375°F (190°C). Mix Dijon mustard, olive oil, salt, and pepper in a small bowl. Rub over chops.\n2. In another bowl, combine breadcrumbs, parsley, and rosemary.\n3. Press the breadcrumb mixture firmly onto both sides of the pork chops ensuring an even coating.\n4. Bake on a sheet for 20-25 minutes until the crust is golden and pork is cooked through. Rest before serving.",
                'image_url' => '/uploads/herb_crusted_pork_chops.jpg'
            ]
        ];

        // Return the target array row, fallback to recipe 1 if the index isn't found
        return $recipesTable[$this->recipeId] ?? $recipesTable[1];
    }
}

// Instantiate the expected global variable framework connection pointer
$conn = new MockPDO();
