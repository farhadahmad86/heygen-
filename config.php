<?php

/**
 * Mock config.php for local testing
 */

// Create a fake PDO class so the script doesn't crash
class MockPDO
{
    public function prepare($sql)
    {
        return new MockPDOStatement();
    }
}

class MockPDOStatement
{
    public function execute($params)
    {
        return true;
    }

    public function fetch($mode)
    {
        // This simulates a recipe row matching what the main script expects!
        return [
            'id' => isset($_GET['id']) ? (int)$_GET['id'] : 44,
            'title' => 'Entrée: Creamy Garlic Tuscan Chicken ▶ Video Guide',
            'ingredients' => "- 2 lbs Chicken Breasts\n- 4 cloves Garlic, minced\n- 1 cup Heavy Cream\n- 1/2 cup Sun-dried Tomatoes\n- 2 cups Fresh Spinach",
            'steps' => "1. Sear the chicken breasts in a hot skillet until golden brown.\n2. Add minced garlic and sun-dried tomatoes, cooking until fragrant.\n3. Pour in heavy cream and bring to a simmer.\n4. Stir in fresh spinach until wilted.\n5. Slice chicken and serve hot.",
            'image_url' => '/uploads/creamy_chicken.jpg',
            'type' => 'Dinner',
            'country_of_origin' => 'Italy',
            'region_of_origin' => 'Tuscany',
            'dish_type_primary' => 'Main Course'
        ];
    }
}

// Instantiate the expected $conn variable
$conn = new MockPDO();