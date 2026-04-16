let ingredientIndex = 0;
let stepIndex = 0;

function addIngredientRow() {
  const container = document.getElementById("ingredients");

  const row = document.createElement("div");
  row.className = "row";

  row.innerHTML = `
    <input placeholder="Ingredient name" class="ing-name">
    <input placeholder="Amount" class="ing-amount small" type="number">
    <input placeholder="Unit" class="ing-unit small">
    <button onclick="this.parentElement.remove()">X</button>
  `;

  container.appendChild(row);
}

function addStepRow() {
  const container = document.getElementById("steps");

  const row = document.createElement("div");
  row.className = "row";

  row.innerHTML = `
    <input placeholder="Step number" class="step-num small" type="number">
    <input placeholder="Type (prep/cook)" class="step-type small">
    <input placeholder="Time (min)" class="step-time small" type="number">
    <input placeholder="Instructions" class="step-text">
    <button onclick="this.parentElement.remove()">X</button>
  `;

  container.appendChild(row);
}

async function submitRecipe() {
  const title = document.getElementById("title").value;
  const description = document.getElementById("description").value;

  const ingredients = [...document.querySelectorAll("#ingredients .row")].map(r => ({
    name: r.querySelector(".ing-name").value,
    amount: r.querySelector(".ing-amount").value,
    unit: r.querySelector(".ing-unit").value
  }));

  const steps = [...document.querySelectorAll("#steps .row")].map(r => ({
    step_number: r.querySelector(".step-num").value,
    step_type: r.querySelector(".step-type").value,
    time_minutes: r.querySelector(".step-time").value,
    instructions: r.querySelector(".step-text").value
  }));

  const payload = {
    title,
    description,
    ingredients,
    steps
  };

  const res = await fetch("add_recipe_handler.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload)
  });

  const data = await res.json();
  alert(data.success ? "Recipe added!" : "Error");
}
