document.addEventListener("DOMContentLoaded", function () {
    function getJsonPath(fileName) {
        const isPagesDir = window.location.pathname.includes("/pages/");
        return isPagesDir ? "../assets/data/" + fileName : "assets/data/" + fileName;
    }

    function getFallbackJsonPath(fileName) {
        const isPagesDir = window.location.pathname.includes("/pages/");
        return isPagesDir ? "../" + fileName : fileName;
    }

    async function loadJsonData(fileName) {
        try {
            let primaryUrl = getJsonPath(fileName);
            let response = await fetch(primaryUrl);
            if (!response.ok) {
                let fallbackUrl = getFallbackJsonPath(fileName);
                response = await fetch(fallbackUrl);
            }
            if (!response.ok) {
                throw new Error(`Failed to load ${fileName} (HTTP ${response.status})`);
            }
            return await response.json();
        } catch (err) {
            try {
                let directResponse = await fetch(fileName);
                if (directResponse.ok) return await directResponse.json();
            } catch (e) {
                console.error(`Final Fetch Failure for ${fileName}:`, e);
            }
            return null;
        }
    }

    const eventList = document.getElementById("eventList") || document.getElementById("upcoming-events");
    const eventSearchBox = document.getElementById("searchBox") || document.getElementById("event-keyword");
    const statusFilter = document.getElementById("statusFilter") || document.getElementById("event-status");
    const categoryFilter = document.getElementById("categoryFilter") || document.getElementById("event-category");
    const sortButton = document.getElementById("sortButton") || document.getElementById("sortDateBtn");
    const paginationContainer = document.getElementById("pagination") || document.getElementById("eventPagination");

    if (eventList) {
        let events = [];
        let currentPage = 1;
        const recordsPerPage = 5;
        let isSortedAsc = true;

        eventList.innerHTML = "<p class='loading-state'>⏳ Loading upcoming campus events from server...</p>";

        loadJsonData("events.json").then(function (data) {
            if (!data || !data.length) {
                eventList.innerHTML = "<p class='error-state'>⚠️ Unable to load events dataset. Please check server connection.</p>";
                return;
            }
            events = data;
            renderEvents();
        });

        function renderEvents() {
            let filtered = events.slice();

            const query = eventSearchBox ? eventSearchBox.value.trim().toLowerCase() : "";
            if (query) {
                filtered = filtered.filter(function (evt) {
                    return (
                        (evt.title && evt.title.toLowerCase().includes(query)) ||
                        (evt.category && evt.category.toLowerCase().includes(query)) ||
                        (evt.venue && evt.venue.toLowerCase().includes(query)) ||
                        (evt.description && evt.description.toLowerCase().includes(query))
                    );
                });
            }

            const selectedCat = categoryFilter ? categoryFilter.value.trim().toLowerCase() : "";
            if (selectedCat) {
                filtered = filtered.filter(function (evt) {
                    return evt.category && evt.category.toLowerCase().includes(selectedCat);
                });
            }

            const selectedStatus = statusFilter ? statusFilter.value.trim().toLowerCase() : "";
            if (selectedStatus) {
                filtered = filtered.filter(function (evt) {
                    return evt.status && evt.status.toLowerCase() === selectedStatus;
                });
            }

            const totalRecords = filtered.length;
            const totalPages = Math.ceil(totalRecords / recordsPerPage) || 1;
            if (currentPage > totalPages) currentPage = totalPages;

            const startIndex = (currentPage - 1) * recordsPerPage;
            const pageData = filtered.slice(startIndex, startIndex + recordsPerPage);

            eventList.innerHTML = "";
            if (pageData.length === 0) {
                eventList.innerHTML = "<p class='empty-state'>ℹ️ No matching campus events found for your search criteria.</p>";
            } else {
                pageData.forEach(function (evt) {
                    const card = document.createElement("article");
                    card.className = "event-card";
                    const badgeClass = evt.status === "Open" ? "badge-open" : evt.status === "Limited Seats" ? "badge-limited" : "badge-soon";
                    
                    card.innerHTML = `
                        <div class="card-header">
                            <h3>${evt.title}</h3>
                            <span class="status-badge ${badgeClass}">${evt.status}</span>
                        </div>
                        <p class="meta-line">
                            📅 <strong>Date:</strong> ${evt.date} | 📍 <strong>Venue:</strong> ${evt.venue}
                        </p>
                        <p class="category-tag">🏷️ <strong>Category:</strong> ${evt.category}</p>
                        <p class="event-desc">${evt.description || "Join this exciting campus activity."}</p>
                        <div class="card-actions">
                            <button type="button" class="btn-primary" onclick="openEventModal('${evt.title.replace(/'/g, "\\'")}', '${evt.date}', '${evt.venue}', '${evt.category}', '${(evt.description || "").replace(/'/g, "\\'")}')">View Details &amp; Modal</button>
                            <a href="register.html?event=${encodeURIComponent(evt.title)}" class="btn-secondary">Register Now</a>
                        </div>
                    `;
                    eventList.appendChild(card);
                });
            }

            renderPaginationControls(totalPages, totalRecords);
        }

        function renderPaginationControls(totalPages, totalRecords) {
            if (!paginationContainer) return;
            paginationContainer.innerHTML = "";
            if (totalPages <= 1) return;

            const info = document.createElement("span");
            info.className = "pagination-info";
            info.textContent = `Page ${currentPage} of ${totalPages} (${totalRecords} events)`;
            paginationContainer.appendChild(info);

            for (let i = 1; i <= totalPages; i++) {
                const pageBtn = document.createElement("button");
                pageBtn.type = "button";
                pageBtn.className = "page-btn" + (i === currentPage ? " active" : "");
                pageBtn.textContent = i;
                pageBtn.addEventListener("click", function () {
                    currentPage = i;
                    renderEvents();
                });
                paginationContainer.appendChild(pageBtn);
            }
        }

        if (eventSearchBox) {
            eventSearchBox.addEventListener("input", function () {
                currentPage = 1;
                renderEvents();
            });
        }

        if (statusFilter) {
            statusFilter.addEventListener("change", function () {
                currentPage = 1;
                renderEvents();
            });
        }

        if (categoryFilter) {
            categoryFilter.addEventListener("change", function () {
                currentPage = 1;
                renderEvents();
            });
        }

        if (sortButton) {
            sortButton.addEventListener("click", function () {
                events.sort(function (a, b) {
                    return isSortedAsc ? a.date.localeCompare(b.date) : b.date.localeCompare(a.date);
                });
                isSortedAsc = !isSortedAsc;
                sortButton.textContent = isSortedAsc ? "Sort by Date ⬆" : "Sort by Date ⬇";
                currentPage = 1;
                renderEvents();
            });
        }
    }

    const studentList = document.getElementById("studentList");
    const studentSearchInput = document.getElementById("studentSearch");

    if (studentList) {
        let students = [];
        studentList.innerHTML = "<p class='loading-state'>⏳ Loading student records from database...</p>";

        loadJsonData("students.json").then(function (data) {
            if (!data || !data.length) {
                studentList.innerHTML = "<p class='error-state'>⚠️ Unable to load student records.</p>";
                return;
            }
            students = data;
            renderStudents();
        });

        function renderStudents() {
            const query = studentSearchInput ? studentSearchInput.value.trim().toLowerCase() : "";
            const cgpaSearch = parseFloat(query);

            const filtered = students.filter(function (st) {
                const textMatch = (
                    st.name.toLowerCase().includes(query) ||
                    st.enrollment.toLowerCase().includes(query) ||
                    st.course.toLowerCase().includes(query) ||
                    st.year.toLowerCase().includes(query) ||
                    st.email.toLowerCase().includes(query)
                );
                const cgpaMatch = !isNaN(cgpaSearch) && parseFloat(st.cgpa) >= cgpaSearch;
                return textMatch || cgpaMatch;
            });

            studentList.innerHTML = "";
            if (filtered.length === 0) {
                studentList.innerHTML = "<p class='empty-state'>No student records match your search query.</p>";
                return;
            }

            filtered.forEach(function (st) {
                const card = document.createElement("div");
                card.className = "student-card";
                card.innerHTML = `
                    <h3>${st.name} <small>(${st.enrollment})</small></h3>
                    <p><strong>Course:</strong> ${st.course} | <strong>Year:</strong> ${st.year}</p>
                    <p><strong>Email:</strong> ${st.email} | <strong>Mobile:</strong> ${st.mobile}</p>
                    <p><strong>CGPA:</strong> <mark>${st.cgpa}</mark> | <strong>Status:</strong> ${st.status}</p>
                `;
                studentList.appendChild(card);
            });
        }

        if (studentSearchInput) {
            studentSearchInput.addEventListener("input", renderStudents);
        }
    }

    const faqList = document.getElementById("faqList");
    const faqSearchInput = document.getElementById("faqSearch") || document.getElementById("faq-query");

    if (faqList) {
        let faqs = [];
        faqList.innerHTML = "<p class='loading-state'>⏳ Loading FAQs...</p>";

        loadJsonData("faqs.json").then(function (data) {
            if (!data || !data.length) {
                faqs = [
                    { question: "What is StudentHub?", answer: "StudentHub is a campus portal for CHARUSAT students.", category: "General" },
                    { question: "How do I register for events?", answer: "Go to Campus Events page and click Register.", category: "Events" }
                ];
            } else {
                faqs = data;
            }
            renderFaqs();
        });

        function renderFaqs() {
            const query = faqSearchInput ? faqSearchInput.value.trim().toLowerCase() : "";
            const filtered = faqs.filter(function (item) {
                return (
                    item.question.toLowerCase().includes(query) ||
                    item.answer.toLowerCase().includes(query) ||
                    (item.category && item.category.toLowerCase().includes(query))
                );
            });

            faqList.innerHTML = "";
            if (filtered.length === 0) {
                faqList.innerHTML = "<p class='empty-state'>No FAQ items found matching your question.</p>";
                return;
            }

            filtered.forEach(function (item, index) {
                const itemDiv = document.createElement("div");
                itemDiv.className = "accordion-item";
                
                const questionHeader = document.createElement("button");
                questionHeader.type = "button";
                questionHeader.className = "accordion-header";
                questionHeader.setAttribute("aria-expanded", "false");
                questionHeader.innerHTML = `<span>Q${index + 1}: ${item.question}</span> <span class="accordion-icon">+</span>`;

                const answerBody = document.createElement("div");
                answerBody.className = "accordion-body";
                answerBody.style.display = "none";
                answerBody.innerHTML = `<p>${item.answer}</p>`;

                questionHeader.addEventListener("click", function () {
                    const isOpen = answerBody.style.display === "block";
                    answerBody.style.display = isOpen ? "none" : "block";
                    questionHeader.setAttribute("aria-expanded", !isOpen);
                    questionHeader.classList.toggle("active", !isOpen);
                    questionHeader.querySelector(".accordion-icon").textContent = isOpen ? "+" : "−";
                });

                itemDiv.appendChild(questionHeader);
                itemDiv.appendChild(answerBody);
                faqList.appendChild(itemDiv);
            });
        }

        if (faqSearchInput) {
            faqSearchInput.addEventListener("input", renderFaqs);
        }
    }
});
