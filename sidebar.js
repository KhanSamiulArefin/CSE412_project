// sidebar.js
document.getElementById("profilePic").addEventListener("click", function () {
  const dropdown = document.getElementById("profileDropdown");
  dropdown.style.display = dropdown.style.display === "flex" ? "none" : "flex";
});
