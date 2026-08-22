const MobileMenu = {
  init() {
    // Mobile navigation

    document.querySelector(".mobile-menu-toggle")?.addEventListener("click", function () {
      this.classList.add("hide");
      document.querySelector(".mobile-menu-close")?.classList.remove("hide");
      document.body.classList.add("mobile-menu-open");
    });

    document.querySelector(".mobile-menu-close")?.addEventListener("click", function () {
      this.classList.add("hide");
      document.querySelector(".mobile-menu-toggle")?.classList.remove("hide");
      if (typeof MicroModal !== "undefined") {
        MicroModal.close("mobile-menu-modal");
      }
      document.body.classList.remove("mobile-menu-open");
    });

    document.querySelectorAll(".mobile-menu__item-toggle").forEach(function (toggle) {
      toggle.addEventListener("click", function (e) {
        e.preventDefault();
        const parent = this.parentElement;
        parent.classList.toggle("active");
      });
    });

  }
};

export default MobileMenu;