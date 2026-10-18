document.addEventListener("DOMContentLoaded", function () {
    const themeButton = document.getElementById("themeButton");
    
    const savedTheme = localStorage.getItem("theme");
    if (savedTheme === "dark") {
        document.body.classList.add("dark");
        if (themeButton) themeButton.textContent = "☀️ Light Mode";
    } else {
        document.body.classList.remove("dark");
        if (themeButton) themeButton.textContent = "🌙 Dark Mode";
    }

    if (themeButton) {
        themeButton.addEventListener("click", function () {
            document.body.classList.toggle("dark");
            const isDark = document.body.classList.contains("dark");
            localStorage.setItem("theme", isDark ? "dark" : "light");
            themeButton.textContent = isDark ? "☀️ Light Mode" : "🌙 Dark Mode";
        });
    }

    const menuButton = document.getElementById("menuButton");
    const mobileMenu = document.getElementById("mobileMenu") || document.querySelector("nav[aria-label='Main Navigation Menu'] ul");
    
    if (menuButton && mobileMenu) {
        menuButton.addEventListener("click", function () {
            mobileMenu.classList.toggle("show");
            const expanded = mobileMenu.classList.contains("show");
            menuButton.setAttribute("aria-expanded", expanded ? "true" : "false");
        });
    }

    const notificationBanner = document.getElementById("notificationBanner");
    const closeNotification = document.getElementById("closeNotification");
    
    if (notificationBanner && closeNotification) {
        if (sessionStorage.getItem("notification_closed") === "true") {
            notificationBanner.style.display = "none";
        }
        closeNotification.addEventListener("click", function () {
            notificationBanner.style.display = "none";
            sessionStorage.setItem("notification_closed", "true");
        });
    }

    const modal = document.getElementById("eventModal");
    const openModalBtn = document.getElementById("openModal");
    const closeModalBtn = document.getElementById("closeModal");

    window.openEventModal = function (title, date, venue, category, description) {
        if (!modal) return;
        const modalTitle = document.getElementById("modalTitle");
        const modalDate = document.getElementById("modalDate");
        const modalVenue = document.getElementById("modalVenue");
        const modalCat = document.getElementById("modalCategory");
        const modalDesc = document.getElementById("modalDescription");

        if (modalTitle) modalTitle.textContent = title || "Event Details";
        if (modalDate) modalDate.textContent = date || "";
        if (modalVenue) modalVenue.textContent = venue || "";
        if (modalCat) modalCat.textContent = category || "";
        if (modalDesc) modalDesc.textContent = description || "";

        modal.classList.add("show");
        modal.setAttribute("aria-hidden", "false");
    };

    window.closeEventModal = function () {
        if (!modal) return;
        modal.classList.remove("show");
        modal.setAttribute("aria-hidden", "true");
    };

    if (openModalBtn) {
        openModalBtn.addEventListener("click", function () {
            openEventModal(
                "Web Innovation Hackathon 2026",
                "2026-08-15",
                "Seminar Hall A",
                "Technical",
                "Join CHARUSAT's flagship 24-hour web hackathon and present your frontend applications."
            );
        });
    }

    if (closeModalBtn) {
        closeModalBtn.addEventListener("click", closeEventModal);
    }

    if (modal) {
        modal.addEventListener("click", function (e) {
            if (e.target === modal) {
                closeEventModal();
            }
        });

        document.addEventListener("keydown", function (e) {
            if (e.key === "Escape" && modal.classList.contains("show")) {
                closeEventModal();
            }
        });
    }

    const slides = document.querySelectorAll(".slide-item");
    const prevBtn = document.getElementById("prevSlide");
    const nextBtn = document.getElementById("nextSlide");
    const dots = document.querySelectorAll(".slider-dot");
    let currentSlide = 0;
    let autoSlideInterval = null;

    function showSlide(index) {
        if (!slides.length) return;
        if (index >= slides.length) currentSlide = 0;
        else if (index < 0) currentSlide = slides.length - 1;
        else currentSlide = index;

        slides.forEach((slide, i) => {
            slide.classList.toggle("active", i === currentSlide);
        });

        dots.forEach((dot, i) => {
            dot.classList.toggle("active", i === currentSlide);
        });
    }

    if (prevBtn) {
        prevBtn.addEventListener("click", function () {
            showSlide(currentSlide - 1);
            resetAutoSlide();
        });
    }

    if (nextBtn) {
        nextBtn.addEventListener("click", function () {
            showSlide(currentSlide + 1);
            resetAutoSlide();
        });
    }

    dots.forEach((dot, index) => {
        dot.addEventListener("click", function () {
            showSlide(index);
            resetAutoSlide();
        });
    });

    function resetAutoSlide() {
        if (autoSlideInterval) clearInterval(autoSlideInterval);
        if (slides.length > 1) {
            autoSlideInterval = setInterval(function () {
                showSlide(currentSlide + 1);
            }, 5000);
        }
    }

    if (slides.length > 1) {
        showSlide(0);
        resetAutoSlide();
    }
});
