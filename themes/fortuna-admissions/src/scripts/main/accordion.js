const Accordion = {
  init() {
    document.querySelectorAll(".accordion-item__toggle").forEach(function (toggle) {
      toggle.addEventListener("click", function (e) {
        e.preventDefault();

        const parent = this.parentElement;
        const isActive = parent.classList.contains("active");

        document.querySelectorAll(".accordion-item").forEach(function (item) {
          item.classList.remove("active");
        });

        if (!isActive) {
          parent.classList.add("active");
        }
      });
    });
  }
};

export default Accordion;
