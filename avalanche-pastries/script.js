// Small extras. The searching, sorting, and alerts stay in PHP.

// ---- "+ Add product" / "+ Add ingredient" and "Remove" on forms with a list of rows ----
// addRow copies the hidden row template into the list; removeRow deletes a row (one row always stays).
function addRow() {
  var rows = document.getElementById("rows");
  var template = document.getElementById("rowTemplate");
  if (rows && template) {
    rows.appendChild(template.content.cloneNode(true));
  }
}
function removeRow(btn) {
  if (document.querySelectorAll("#rows .item-row").length > 1) {
    btn.closest(".item-row").remove();
  }
}

// ---- Number boxes accept numbers only: no letters, and no e, + or - ----
//  <input type="number">   digits (and one "." when the box allows decimals, e.g. step="any")
//  <input data-digits>     digits only (used for mobile numbers)
function isNumberBox(el) {
  return el instanceof HTMLInputElement && (el.type === "number" || el.hasAttribute("data-digits"));
}
function wholeNumbersOnly(el) {
  var step = el.getAttribute("step");
  return el.hasAttribute("data-digits") || step === null || step === "1";
}
function charAllowed(el, ch) {
  if (/^[0-9]$/.test(ch)) { return true; }
  return ch === "." && !wholeNumbersOnly(el);
}

// Desktop keyboards: block the key before it is typed
document.addEventListener("keydown", function (e) {
  if (!isNumberBox(e.target)) { return; }
  if (e.ctrlKey || e.metaKey || e.altKey || e.key.length !== 1) { return; }   // shortcuts, Backspace, arrows, Tab
  if (!charAllowed(e.target, e.key)) { e.preventDefault(); }
});

// Phone keyboards: block what is about to be inserted
document.addEventListener("beforeinput", function (e) {
  if (!isNumberBox(e.target) || e.inputType !== "insertText" || !e.data) { return; }
  for (var i = 0; i < e.data.length; i++) {
    if (!charAllowed(e.target, e.data.charAt(i))) { e.preventDefault(); return; }
  }
});

// Pasting: keep only the allowed characters
document.addEventListener("paste", function (e) {
  if (!isNumberBox(e.target)) { return; }
  var text = (e.clipboardData || window.clipboardData).getData("text");
  var clean = wholeNumbersOnly(e.target) ? text.replace(/[^0-9]/g, "") : text.replace(/[^0-9.]/g, "");
  if (clean !== text) {
    e.preventDefault();
    if (clean !== "") { document.execCommand("insertText", false, clean); }
  }
});

// Text boxes marked data-digits: remove anything else that gets in (drag and drop, autofill)
document.addEventListener("input", function (e) {
  var el = e.target;
  if (el instanceof HTMLInputElement && el.hasAttribute("data-digits")) {
    var clean = el.value.replace(/[^0-9]/g, "");
    if (clean !== el.value) { el.value = clean; }
  }
});

document.addEventListener("DOMContentLoaded", function () {
  // Hide the green success message after 4 seconds
  var ok = document.querySelector(".success");
  if (ok) {
    setTimeout(function () { ok.style.display = "none"; }, 4000);
  }

  // Phone menu: the "Menu" button opens and closes the links
  var toggle = document.querySelector(".nav-toggle");
  var links = document.getElementById("nav-links");
  if (toggle && links) {
    toggle.addEventListener("click", function () {
      var open = links.classList.toggle("open");
      toggle.setAttribute("aria-expanded", open ? "true" : "false");
    });
  }

  // Phone tables: copy each column heading into its cells so a row can be shown as a small card.
  // (The CSS only turns tables into cards on small screens.)
  document.querySelectorAll("table").forEach(function (table) {
    var headRow = null;
    table.querySelectorAll("tr").forEach(function (row) {
      var heads = row.querySelectorAll("th");
      if (heads.length && !headRow) { headRow = row; }
    });
    if (!headRow) { return; }
    var labels = Array.prototype.map.call(headRow.querySelectorAll("th"), function (th) {
      return th.textContent.trim();
    });
    headRow.classList.add("stack-head");
    table.querySelectorAll("tr").forEach(function (row) {
      if (row === headRow) { return; }
      var cells = row.querySelectorAll("td");
      cells.forEach(function (td, i) {
        if (!td.hasAttribute("colspan") && labels[i] !== undefined) {
          td.setAttribute("data-label", labels[i]);
        }
      });
    });
    table.classList.add("stack");
  });
});
