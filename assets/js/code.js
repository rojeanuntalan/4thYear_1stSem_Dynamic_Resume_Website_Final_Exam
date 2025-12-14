$(function () {

  // Navbar toggle (since navbar is hard-coded)
  $("#mobile-toggle").on("click", function () {
    $("#nav-links").toggleClass("show");
  });

  // Load footer
  $("#footer").load("footer.html", function () {
    console.log("Footer loaded");
  });

});
