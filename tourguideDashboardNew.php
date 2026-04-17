// Add this inside the <script> tag of tourGuideDashboardNew.html
document.addEventListener("DOMContentLoaded", () => {
    const role = localStorage.getItem('role');
    if (role !== 'guide') {
        alert("Access Denied: Guides only.");
        window.location.href = 'signinTouristAdmin.html'; // Kick them out if not a guide
    }
});