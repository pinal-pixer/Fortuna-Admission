const PostGrid = {
  init() {
    document.addEventListener("DOMContentLoaded", function () {
      const paginationLinks = document.querySelectorAll('.post-grid-pagination a');

      // When a pagination link is clicked, set the flag
      paginationLinks.forEach(function (link) {
        link.addEventListener('click', function () {
          localStorage.setItem('scrollToPostGridWrapper', 'true');
        });
      });

      // On page load, scroll if flag is set
      if (localStorage.getItem('scrollToPostGridWrapper') === 'true') {
        const pagination = document.querySelector('.post-grid-pagination');

        if (pagination) {
          const wrapper = pagination.closest('.post-grid-wrapper');

          if (wrapper) {
            setTimeout(() => {
              const offset = 129; // Adjust for sticky header
              const scrollTarget = wrapper.getBoundingClientRect().top + window.scrollY - offset;

              window.scrollTo({
                top: scrollTarget
              });
            }, 50);
          }
        }

        localStorage.removeItem('scrollToPostGridWrapper');
      }
    });
  }
};

export default PostGrid;
