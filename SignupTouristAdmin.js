document.addEventListener("DOMContentLoaded", () => {
  const form = document.querySelector("#signup-form");
  const roleButtons = document.querySelectorAll(".role-btn");
  const roleInput = document.querySelector("#user-role");

  // Handle Role Selection (Tourist vs Admin)
  roleButtons.forEach(btn => {
    btn.addEventListener("click", () => {
      // Reset all buttons
      roleButtons.forEach(b => {
          b.classList.remove("active");
          b.setAttribute("aria-pressed", "false");
      });
      // Activate clicked button
      btn.classList.add("active");
      btn.setAttribute("aria-pressed", "true");
      roleInput.value = btn.dataset.role;
    });
  });

  form.addEventListener("submit", function (e) {
    const password = document.getElementById("password").value;
    const confirmPassword = document.getElementById("confirm_password").value;
    const email = document.getElementById("email").value;

    // 1. Check if passwords match
    if (password !== confirmPassword) {
      e.preventDefault();
      alert("Passwords do not match!");
      return;
    }

    // 2. Minimum length validation
    if (password.length < 8) {
        e.preventDefault();
        alert("Password must be at least 8 characters long.");
        return;
    }

    // 3. Email format validation
    if (!email.includes("@")) {
        e.preventDefault();
        alert("Please enter a valid email address.");
        return;
    }
    
    // If validations pass, the form submits to SignupTouristAdmin.php
  });
});