function addIngredientRow() {
  const container = document.getElementById("ingredients");

  const row = document.createElement("div");
  row.className = "row";

  row.innerHTML = `
    <input placeholder="Ingredient name" class="ing-name">
    <input placeholder="Amount" type="number" class="ing-amount">
    <select class="ing-unit">
      <option value="g">g</option>
      <option value="ml">ml</option>
      <option value="pcs">pcs</option>
    </select>
    <button onclick="this.parentElement.remove()">✕</button>
  `;

  container.appendChild(row);
}

function addStepRow() {
  const container = document.getElementById("steps");

  const row = document.createElement("div");
  row.className = "row";

  row.innerHTML = `
    <input type="number" placeholder="Step #" class="step-number">
    <select class="step-type">
      <option value="prep">prep</option>
      <option value="cook">cook</option>
    </select>
    <input type="number" placeholder="Time (min)" class="step-time">
    <input placeholder="Instructions" class="step-text">
    <button onclick="this.parentElement.remove()">✕</button>
  `;

  container.appendChild(row);
}

async function submitRecipe() {
  const payload = {
    title: document.getElementById("title").value,
    description: document.getElementById("description").value,

    ingredients: [...document.querySelectorAll("#ingredients .row")].map(r => ({
      name: r.querySelector(".ing-name").value,
      amount: parseFloat(r.querySelector(".ing-amount").value),
      unit: r.querySelector(".ing-unit").value
    })),

    steps: [...document.querySelectorAll("#steps .row")].map(r => ({
      step_number: parseInt(r.querySelector(".step-number").value),
      step_type: r.querySelector(".step-type").value,
      time_minutes: parseInt(r.querySelector(".step-time").value),
      instructions: r.querySelector(".step-text").value
    }))
  };

  const res = await fetch("add_recipe_handler.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload)
  });

  const data = await res.json();
  alert(data.success ? "Recipe added" : "Error");
}
