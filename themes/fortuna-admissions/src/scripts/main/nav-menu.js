const NavMenu = {
  init() {
    document.addEventListener('DOMContentLoaded', function () {
      const dropdowns = document.querySelectorAll('.dropdown-content');
    
      dropdowns.forEach(dropdown => {
        const parent = dropdown.parentElement;
    
        parent.addEventListener('mouseenter', () => {
          // Reset transforms before measuring
          dropdown.style.left = '50%';
          dropdown.style.transform = 'translateX(-50%)';
    
          const rect = dropdown.getBoundingClientRect();
          const overflowRight = rect.right > window.innerWidth;
          const overflowLeft = rect.left < 0;
    
          if (overflowRight) {
            const overflowAmount = rect.right - window.innerWidth;
            dropdown.style.transform = `translateX(calc(-50% - ${overflowAmount}px))`;
          } else if (overflowLeft) {
            dropdown.style.transform = `translateX(calc(-50% + ${Math.abs(rect.left)}px))`;
          }
        });
      });
    });
  }
};

export default NavMenu;