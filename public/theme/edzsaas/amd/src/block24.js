define([], function () {
    return {
        init: function () {
            const filterBtns = document.querySelectorAll("#block24 .filter-btn"),
                courseCards = document.querySelectorAll("#block24 .course-card"),
                emptyMessage = document.getElementById("empty-message"),
                prevBtn = document.getElementById("prev-page"),
                nextBtn = document.getElementById("next-page");

            if (!filterBtns.length || !courseCards.length) return;

            let activeFilter = "all",
                currentPage = 1;
            const perPage = 4;

            // Filter handler
            function applyFilter(filter) {
                activeFilter = filter;
                currentPage = 1;
                render();
            }

            // Render courses + pagination
            function render() {
                const filtered = Array.from(courseCards).filter(c =>
                    activeFilter === "all" || c.classList.contains(activeFilter)
                );

                const totalPages = Math.ceil(filtered.length / perPage);
                const start = (currentPage - 1) * perPage;
                const end = start + perPage;

                // Show / hide cards
                courseCards.forEach(c => (c.style.display = "none"));
                filtered.slice(start, end).forEach(c => (c.style.display = ""));

                // Empty message
                emptyMessage.style.display = filtered.length === 0 ? "block" : "none";

                // Show arrows only if 4+ courses
                if (filtered.length > perPage) {
                    prevBtn.style.display = "block";
                    nextBtn.style.display = "block";
                } else {
                    prevBtn.style.display = "none";
                    nextBtn.style.display = "none";
                }
            }

            // Event listeners
            filterBtns.forEach(btn => {
                btn.addEventListener("click", () => {
                    filterBtns.forEach(b => b.classList.remove("active"));
                    btn.classList.add("active");
                    applyFilter(btn.getAttribute("data-filter"));
                });
            });

            // Infinite prev
            prevBtn.addEventListener("click", () => {
                const filtered = Array.from(courseCards).filter(c =>
                    activeFilter === "all" || c.classList.contains(activeFilter)
                );
                const totalPages = Math.ceil(filtered.length / perPage);
                currentPage = currentPage > 1 ? currentPage - 1 : totalPages;
                render();
            });

            // Infinite next
            nextBtn.addEventListener("click", () => {
                const filtered = Array.from(courseCards).filter(c =>
                    activeFilter === "all" || c.classList.contains(activeFilter)
                );
                const totalPages = Math.ceil(filtered.length / perPage);
                currentPage = currentPage < totalPages ? currentPage + 1 : 1;
                render();
            });

            // Initial load
            applyFilter("all");
        }
    };
});
