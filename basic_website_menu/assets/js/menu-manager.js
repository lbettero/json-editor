// assets/js/menu-manager.js

document.addEventListener("DOMContentLoaded", () => {

    const scrollUp = document.getElementById("scrollUp");
    const scrollDown = document.getElementById("scrollDown");

    // Scroll to the top of the page
    if (scrollUp) {
        scrollUp.addEventListener("click", () => {
            window.scrollTo({ top: 0, behavior: "smooth" });
        });
    }

    // Scroll to the bottom of the page
    if (scrollDown) {
        scrollDown.addEventListener("click", () => {
            window.scrollTo({ top: document.body.scrollHeight, behavior: "smooth" });
        });
    }
});
