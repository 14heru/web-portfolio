// ==========================================================================
// Web Portfolio - Client-Side JavaScript
// ==========================================================================

document.addEventListener("DOMContentLoaded", () => {
  // 1. Filter Kategori Portofolio Interaktif
  const filterButtons = document.querySelectorAll(".btn-filter");
  const projectItems = document.querySelectorAll(".project-item");

  if (filterButtons.length > 0 && projectItems.length > 0) {
    filterButtons.forEach((button) => {
      button.addEventListener("click", () => {
        // Hapus status aktif dari semua tombol filter
        filterButtons.forEach((btn) => btn.classList.remove("active"));
        // Tambahkan status aktif pada tombol yang diklik
        button.classList.add("active");

        const filterValue = button.getAttribute("data-filter");

        projectItems.forEach((item) => {
          const itemCategory = item.getAttribute("data-category");
          if (filterValue === "all" || itemCategory === filterValue) {
            item.style.display = "block";
            // Efek fade-in halus
            item.classList.add("fade-in");
          } else {
            item.style.display = "none";
            item.classList.remove("fade-in");
          }
        });
      });
    });
  }

  // 2. Tutup Mobile Navbar otomatis saat item navigasi diklik
  const navLinks = document.querySelectorAll(
    ".navbar-nav .nav-link:not(.dropdown-toggle)",
  );
  const navbarCollapse = document.querySelector(".navbar-collapse");
  if (navbarCollapse && typeof bootstrap !== "undefined") {
    navLinks.forEach((link) => {
      link.addEventListener("click", () => {
        const bsCollapse = bootstrap.Collapse.getInstance(navbarCollapse);
        if (bsCollapse && navbarCollapse.classList.contains("show")) {
          bsCollapse.hide();
        }
      });
    });
  }

  // 3. Highlight navigasi + state navbar saat scroll
  const sections = document.querySelectorAll("section[id]");
  const mainNavbar = document.querySelector(".navbar-glass");
  const updateNavbarState = () => {
    if (mainNavbar) {
      mainNavbar.classList.toggle("is-scrolled", window.pageYOffset > 24);
    }
  };
  updateNavbarState();
  window.addEventListener("scroll", updateNavbarState, { passive: true });
  window.addEventListener("scroll", () => {
    const scrollY = window.pageYOffset;
    sections.forEach((current) => {
      const sectionHeight = current.offsetHeight;
      const sectionTop = current.offsetTop - 120;
      const sectionId = current.getAttribute("id");

      const activeLink = document.querySelector(
        `.navbar-nav a[href*='${sectionId}']`,
      );
      if (activeLink) {
        if (scrollY > sectionTop && scrollY <= sectionTop + sectionHeight) {
          activeLink.classList.add("text-primary", "active");
          activeLink.classList.remove("text-secondary");
        } else {
          activeLink.classList.remove("text-primary", "active");
          activeLink.classList.add("text-secondary");
        }
      }
    });
  });
});
