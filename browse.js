// ===== BROWSE PAGE FUNCTIONALITY =====

let activeLocationFilter = null;

function normalizeMatchText(value) {
    return String(value || "")
        .toLowerCase()
        .replace(/[^a-z0-9,\s]/g, " ")
        .replace(/\s+/g, " ")
        .trim();
}

function tokenizeAreaParts(value) {
    const stopWords = new Set(["city", "municipality", "province", "region", "area", "barangay"]);
    return normalizeMatchText(value)
        .split(/[,\s]+/)
        .map((part) => part.trim())
        .filter((part) => part.length >= 4 && !stopWords.has(part));
}

function isGuideNearLocation(guide, location) {
    const guideText = normalizeMatchText([
        guide.service_areas,
        guide.specialization,
        guide.description
    ].filter(Boolean).join(" "));

    if (!guideText) return false;

    const locationName = normalizeMatchText(location.name || "");
    if (locationName && guideText.includes(locationName)) return true;

    const locationAddress = normalizeMatchText(location.address || "");
    if (locationAddress && guideText.includes(locationAddress)) return true;

    const areaParts = [
        ...tokenizeAreaParts(location.name || ""),
        ...tokenizeAreaParts(location.address || "")
    ];

    return areaParts.some((part) => guideText.includes(part));
}

function updateGuideSectionHeading(filterLabel, count) {
    const titleEl = document.getElementById("guidesSectionTitle");
    const subtitleEl = document.getElementById("guidesSectionSubtitle");
    const clearBtn = document.getElementById("clearGuideFilterBtn");

    if (filterLabel) {
        if (titleEl) titleEl.textContent = `Tour Guides Near ${filterLabel}`;
        if (subtitleEl) subtitleEl.textContent = count > 0
            ? `${count} guide${count === 1 ? "" : "s"} available near ${filterLabel}`
            : `No guides found near ${filterLabel}`;
        if (clearBtn) clearBtn.hidden = false;
    } else {
        if (titleEl) titleEl.textContent = "Top Tour Guides";
        if (subtitleEl) subtitleEl.textContent = "Meet our experienced and professional tour guides";
        if (clearBtn) clearBtn.hidden = true;
    }
}

function scrollToGuidesSection() {
    const section = document.getElementById("guidesSection");
    if (section) {
        section.scrollIntoView({ behavior: "smooth", block: "start" });
    }
}

/**
 * Display locations in the locations container
 */
function displayLocations() {
    const locationsContainer = document.getElementById('locationsContainer');
    if (!locationsContainer) return;
    locationsContainer.innerHTML = '';

    (locationData || []).forEach((location, index) => {
        const card = document.createElement('div');
        card.classList.add('item-card');
        card.dataset.index = String(index);
        if (activeLocationFilter && activeLocationFilter.index === index) {
            card.classList.add("is-active");
        }

        function selectLocation() {
            const filteredGuides = (guideData || []).filter((guide) => isGuideNearLocation(guide, location));
            activeLocationFilter = {
                index,
                label: location.name || "selected location",
                guides: filteredGuides
            };

            displayLocations();
            displayGuides(filteredGuides, activeLocationFilter.label);
            scrollToGuidesSection();
        }

        card.innerHTML = `
            <img src="${location.image}" alt="${location.name}" class="item-image" onerror="this.src='https://via.placeholder.com/300x200?text=No+Image'">
            <div class="item-content">
                <h4>${location.name}</h4>
                <p>${location.description}</p>
                <button type="button" class="book-now-btn">Book Now</button>
            </div>
        `;
        card.addEventListener("click", selectLocation);

        const button = card.querySelector('.book-now-btn');
        if (button) {
            button.addEventListener('click', function(event) {
                event.stopPropagation();
                selectLocation();
            });
        }

        locationsContainer.appendChild(card);
    });
}

/**
 * Display tour guides in the guides container
 */
function displayGuides(sourceGuides = guideData, filterLabel = "") {
    const guidesContainer = document.getElementById('guidesContainer');
    if (!guidesContainer) return;
    guidesContainer.innerHTML = '';
    const guides = Array.isArray(sourceGuides) ? sourceGuides : [];
    updateGuideSectionHeading(filterLabel, guides.length);

    if (guides.length === 0) {
        guidesContainer.innerHTML = `
            <div class="item-card">
                <div class="item-content">
                    <h4>No guides available</h4>
                    <p>Try another location to find nearby tour guides.</p>
                </div>
            </div>
        `;
        return;
    }

    guides.forEach(guide => {
        const name = (guide.first_name || guide.last_name) ? `${guide.first_name || ''} ${guide.last_name || ''}`.trim() : (guide.name || 'Tour Guide');
        const desc = guide.specialization || guide.service_areas || guide.description || '';
        const img = guide.image || guide.profile_image || 'photos/default.jpg';
        const guideId = guide.user_id || guide.guide_id || '';
        const card = document.createElement('div');
        card.classList.add('item-card');
        card.innerHTML = `
            <img src="${img}" alt="${name}" class="item-image" onerror="this.src='https://via.placeholder.com/300x200?text=No+Image'">
            <div class="item-content">
                <h4>${name}</h4>
                <p>${desc}</p>
                <button type="button" class="book-now-btn" data-guide-id="${guideId}">Book Now</button>
            </div>
        `;
        const button = card.querySelector('.book-now-btn');
        if (button && guideId) {
            button.addEventListener('click', function(event) {
                event.stopPropagation();
                handleBrowseBooking(button, guideId);
            });
        }
        guidesContainer.appendChild(card);
    });
}

async function handleBrowseBooking(button, guideId) {
    const card = button.closest('.item-card');
    const guideName = card && card.querySelector('h4') ? card.querySelector('h4').textContent.trim() : 'this guide';
    const isLoggedIn = localStorage.getItem('userLoggedIn') === 'true';
    const role = localStorage.getItem('role') || '';

    if (!guideId) {
        alert('This guide is not available for booking right now.');
        return;
    }

    if (!isLoggedIn || role !== 'tourist') {
        alert('Please sign in as a tourist to book a guide.');
        window.location.href = 'signinTouristAdmin.html';
        return;
    }

    if (!window.confirm(`Send a booking request to ${guideName}?`)) {
        return;
    }

    const originalText = button.textContent;
    button.disabled = true;
    button.textContent = 'Sending...';

    const form = new FormData();
    form.append('guide_id', guideId);

    try {
        const response = await fetch('book_guide.php', { method: 'POST', credentials: 'same-origin', body: form });
        const data = await response.json();

        if (response.status === 403) {
            alert(data.error || 'Please sign in as a tourist first.');
            window.location.href = 'signinTouristAdmin.html';
            return;
        }

        if (!data.ok) {
            throw new Error(data.error || 'Booking request failed.');
        }

        button.textContent = 'Requested';
        button.disabled = true;
        alert(data.message || 'Booking request sent. Waiting for the guide to accept.');
    } catch (error) {
        alert(error.message || 'Could not send booking request.');
        button.disabled = false;
        button.textContent = originalText;
    }
}

async function loadLocationsFromDb() {
    try {
        const isFile = typeof window !== 'undefined' && window.location && window.location.protocol === 'file:';
        const endpoint = isFile ? 'http://localhost/guidemate1/get_spots.php' : 'get_spots.php';

        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 5000);
        const res = await fetch(endpoint, { credentials: 'same-origin', signal: controller.signal });
        clearTimeout(timeout);
        const data = await res.json();
        if (Array.isArray(data)) {
            locationData = data;
        }
    } catch (e) {
        // Fallback: keep whatever locationData is (script.js defaults)
    }
}

/**
 * Initialize the browse page: load guides (and rely on script.js locationData) then display
 */
document.addEventListener('DOMContentLoaded', async () => {
    try {
        const res = await fetch('get_guides.php');
        const data = await res.json();
        if (Array.isArray(data)) guideData = data;
    } catch (e) {}
    displayLocations();
    displayGuides();

    // Replace locations with DB version when loaded
    loadLocationsFromDb().then(displayLocations);

    const clearBtn = document.getElementById("clearGuideFilterBtn");
    if (clearBtn) {
        clearBtn.addEventListener("click", () => {
            activeLocationFilter = null;
            displayLocations();
            displayGuides();
        });
    }
});
