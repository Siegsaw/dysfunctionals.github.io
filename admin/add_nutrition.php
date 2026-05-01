<?php
require_once __DIR__ . '/auth.php';
require_admin();
header('Content-Type: text/html; charset=utf-8');
require '/var/www/private/db.php'; 

$ingResult = $conn->query("SELECT ingredient_id, name_ing FROM ingredients ORDER BY name_ing ASC");

$nutrResult = $conn->query("SELECT nutrient_id, name_nutr, unit FROM nutrients ORDER BY nutrient_id ASC");
$nutrients = [];
while ($row = $nutrResult->fetch_assoc()) {
    $nutrients[] = $row;
}

echo "<!DOCTYPE html>";
echo "<html lang='en'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<title>Nutrition Mapping</title>";
echo "<link rel='stylesheet' href='admin.css'>";
echo "</head>";
echo "<body>";

echo "<div class='layout'>";

echo "<aside class='sidebar'>";
echo "<a class='logo' href='admin.php'>PantryAdmin</a>";
echo "<a class='nav' href='admin.php'>Dashboard</a>";
echo "<a class='nav secondary' href='/web/index.php' target='_blank'>Main Website ↗</a>";
echo "<a class='nav' href='add_recipe.php'>Add Recipe</a>";
echo "<a class='nav active' href='add_nutrition.php'>Nutrition Mapping</a>";
echo "<a class='nav secondary' href='logout.php'>Log out</a>";
echo "</aside>";

echo "<main class='main'>";
echo "<div class='page-title'>Nutrition Mapping</div>";
echo "<div class='page-sub'>Assign values per 100g for each ingredient</div>";

echo "<div class='card'>";
echo "<div class='section'>1. Select Ingredient</div>";
echo "<select id='ingredient_id' class='input' onchange='loadNutrition()'>";
echo "<option value=''>-- Choose an ingredient --</option>";
while ($ing = $ingResult->fetch_assoc()) {
    echo "<option value='{$ing['ingredient_id']}'>{$ing['name_ing']}</option>";
}
echo "</select>";

echo "<div class='section'>2. Nutrient Values (per 100g)</div>";
echo "<div class='preview-list'>";

foreach ($nutrients as $nutr) {
    $nutrientName = strtolower(trim($nutr['name_nutr']));

    $isRequiredMacro = in_array($nutrientName, [
        'calories',
        'calorie',
        'kcal',
        'fat',
        'carbs',
        'carbohydrates',
        'protein'
    ]);

    $requiredAttr = $isRequiredMacro ? "required" : "";
    $requiredMark = $isRequiredMacro ? " <span style='color:#ef4444;'>*</span>" : "";

    echo "<div class='preview-row'>";
    echo "<div class='preview-row-left'>{$nutr['name_nutr']} ({$nutr['unit']}){$requiredMark}</div>";
    echo "<div class='preview-row-right'>";
    echo "<input 
            type='number' 
            step='0.01' 
            min='0'
            class='input nutrient-input' 
            data-nutrient-id='{$nutr['nutrient_id']}'
            data-nutrient-name='" . htmlspecialchars($nutrientName, ENT_QUOTES, 'UTF-8') . "'
            placeholder='0.00'
            {$requiredAttr}
            style='width: 100px; margin: 0;'
          >";
    echo "</div>";
    echo "</div>";
}

echo "</div>";
echo "<button class='btn primary' onclick='saveNutrition()' style='width: 100%; margin-top: 20px;'>Save All Nutrition Data</button>";
echo "</div>";

echo "</main>";
echo "</div>";

?>

<script>
function isRequiredMacro(name) {
    name = String(name || '').toLowerCase().trim();

    return [
        'calories',
        'calorie',
        'kcal',
        'fat',
        'carbs',
        'carbohydrates',
        'protein'
    ].includes(name);
}

function clearNutritionInputs() {
    document.querySelectorAll('.nutrient-input').forEach(input => {
        input.value = "";
    });
}

function loadNutrition() {
    const ingId = document.getElementById('ingredient_id').value;

    clearNutritionInputs();

    if (!ingId) {
        return;
    }

    fetch('get_nutrition_mapping.php?ingredient_id=' + encodeURIComponent(ingId))
        .then(response => response.json())
        .then(res => {
            if (!res.success || !res.nutrition) {
                return;
            }

            Object.keys(res.nutrition).forEach(nutrientId => {
                const input = document.querySelector(
                    `.nutrient-input[data-nutrient-id="${nutrientId}"]`
                );

                if (input) {
                    input.value = res.nutrition[nutrientId];
                }
            });
        })
        .catch(err => {
            console.error('Error loading nutrition:', err);
            alert("Could not load existing nutrition data.");
        });
}

function saveNutrition() {
    const ingId = document.getElementById('ingredient_id').value;

    if (!ingId) {
        alert("Please select an ingredient first!");
        return;
    }

    const inputs = document.querySelectorAll('.nutrient-input');
    let nutritionData = [];
    let missingRequired = [];

    inputs.forEach(input => {
        const nutrientName = input.getAttribute('data-nutrient-name');
        const value = input.value.trim();

        if (isRequiredMacro(nutrientName) && value === "") {
            missingRequired.push(nutrientName);
        }

        if (value !== "") {
            nutritionData.push({
                nutrient_id: input.getAttribute('data-nutrient-id'),
                amount: value
            });
        }
    });

    if (missingRequired.length > 0) {
        alert("Please enter required macronutrients: calories, fat, carbs and protein.");
        return;
    }

    if (nutritionData.length === 0) {
        alert("Please enter at least one value.");
        return;
    }

    fetch('add_nutrition_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            ingredient_id: ingId,
            nutrition: nutritionData
        })
    })
    .then(response => response.json())
    .then(res => {
        alert(res.message);

        if (res.success) {
            loadNutrition();
        }
    })
    .catch(err => {
        console.error('Error:', err);
        alert("System error. Check console.");
    });
}
</script>
</body>
</html>
