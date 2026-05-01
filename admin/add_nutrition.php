<?php
echo "<!DOCTYPE html>";
echo "<html lang='en'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Nutrition Mapping</title>";
echo "<link rel='stylesheet' href='admin.css'>";
echo "</head>";
echo "<body>";

echo "<div class='layout'>";

echo "<aside class='sidebar'>
        <a class='logo' href='admin.php'>PantryAdmin</a>
        <a class='nav' href='admin.php'>Dashboard</a>
        <a class='nav secondary' href='../index.php' target='_blank'>Main Website ↗</a>
        <a class='nav active' href='nutrition.php'>Nutrition Mapping</a>
      </aside>";

echo "<main class='main'>";
echo "<div class='page-title'>Nutrition Mapping</div>";

echo "<div class='card'>";
echo "<div class='section'>1. Select Ingredient</div>";
echo "<select id='ingredient_id' class='input'>";
    echo "<option value='1'>Eggs</option>";
    echo "<option value='2'>Chicken Breast</option>";
echo "</select>";

echo "<div class='section'>2. Enter Nutrition Data (per 100g)</div>";
echo "<div class='preview-list'>";

    $sample_nutrients = [
        ['id' => 1, 'name' => 'Calories', 'unit' => 'kcal'],
        ['id' => 2, 'name' => 'Protein', 'unit' => 'g'],
        ['id' => 3, 'name' => 'Fat', 'unit' => 'g']
    ];

    foreach ($sample_nutrients as $nutr) {
        echo "<div class='preview-row'>
                <div class='preview-row-left'>{$nutr['name']} ({$nutr['unit']})</div>
                <div class='preview-row-right'>
                    <input type='number' step='0.01' class='input nutrient-input' 
                           data-nutrient-id='{$nutr['id']}' placeholder='0.00' style='width: 120px;'>
                </div>
              </div>";
    }

echo "</div>";
echo "<button class='btn primary' onclick='saveNutrition()' style='width:100%; margin-top:20px;'>Save All Nutrition Data</button>";
echo "</div>";

echo "</main></div>";

?>
<script>
function saveNutrition() {
    const ingId = document.getElementById('ingredient_id').value;
    const inputs = document.querySelectorAll('.nutrient-input');
    let data = [];

    inputs.forEach(input => {
        if(input.value !== "") {
            data.push({
                nutrient_id: input.getAttribute('data-nutrient-id'),
                amount: input.value
            });
        }
    });

    console.log("Siunčiame duomenis:", { ingredient_id: ingId, nutrition: data });
  // Nuoroda į DB
    alert('Duomenys paruošti siuntimui į DB!');
}
</script>
</body></html>
