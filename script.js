// --- 1. DATA SOURCES ---
const defaultLocationData = [
    { name: "Kawasan Falls", description: "Famous turquoise waterfalls in Badian.", image: "photos/kaws.jpg", rating: 4.8, reviewCount: 80 },
    { name: "Osmeña Peak", description: "The highest point in Cebu with 360-degree views.", image: "photos/osm.jpg", rating: 4.5, reviewCount: 62 },
    { name: "Magellan's Cross", description: "A historical landmark from the Spanish era.", image: "photos/mag.jpeg", rating: 4.6, reviewCount: 120 },
    { name: "Temple of Leah", description: "A grand symbol of undying love.", image: "photos/temp.jpeg", rating: 4.7, reviewCount: 95 },
    { name: "Sirao Garden", description: "The Little Amsterdam of Cebu.", image: "photos/Sirao.jpeg", rating: 4.4, reviewCount: 74 },
    { name: "Taoist Temple", description: "A colorful ritual center for devotees.", image: "photos/taoist.jpeg", rating: 4.5, reviewCount: 88 }
];

let locationData = [...defaultLocationData];
let guideData = []; // Will be populated from the database
let displayedItems = [];
let activeGuideAreaFilter = null;
let favoriteDestinationIds = new Set();

// --- 2. SELECTORS ---
const container = document.getElementById("cardsContainer");
const tabButtons = document.querySelectorAll(".tab-btn");
const sectionTitle = document.getElementById("sectionTitle");
const searchInput = document.getElementById("searchInput");
const reviewContainer = document.getElementById("reviewContainer");
const spotGuidesSection = document.getElementById("spotGuidesSection");
const spotGuidesTitle = document.getElementById("spotGuidesTitle");
const spotGuidesSubtitle = document.getElementById("spotGuidesSubtitle");
const spotGuidesContainer = document.getElementById("spotGuidesContainer");
const spotDetailImage = document.getElementById("spotDetailImage");
const spotDetailName = document.getElementById("spotDetailName");
const spotDetailRating = document.getElementById("spotDetailRating");
const spotDetailAddress = document.getElementById("spotDetailAddress");
const spotDetailDescription = document.getElementById("spotDetailDescription");
const spotGuidesOpenBtn = document.getElementById("spotGuidesOpenBtn");
const locationDetailModal = document.getElementById("locationDetailModal");
const locationDetailBackdrop = document.getElementById("locationDetailBackdrop");
const locationDetailCloseBtn = document.getElementById("locationDetailCloseBtn");
const locationDetailModalImage = document.getElementById("locationDetailModalImage");
const locationDetailModalName = document.getElementById("locationDetailModalName");
const locationDetailModalRating = document.getElementById("locationDetailModalRating");
const locationDetailModalAddress = document.getElementById("locationDetailModalAddress");
const locationDetailModalDescription = document.getElementById("locationDetailModalDescription");
const locationDetailModalGuidesBtn = document.getElementById("locationDetailModalGuidesBtn");

let currentType = "locations"; 
let selectedSpotForModal = null;

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"]+/g, function(match) {
        return {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;'
        }[match] || match;
    });
}

function getCurrentDataPool() {
    if (currentType === "locations") return locationData;
    return activeGuideAreaFilter ? activeGuideAreaFilter.guides : guideData;
}

async function loadFavoriteDestinationIds() {
    try {
        const response = await fetch('get_my_favorite_destinations.php', { credentials: 'same-origin' });
        let data = {};
        try {
            data = await response.json();
        } catch (_) {}

        if (!response.ok || !data.success || !Array.isArray(data.favorites)) {
            favoriteDestinationIds = new Set();
            return;
        }

        favoriteDestinationIds = new Set(
            data.favorites
                .map((item) => parseInt(item.destination_id, 10))
                .filter((id) => Number.isInteger(id) && id > 0)
        );
    } catch (_) {
        favoriteDestinationIds = new Set();
    }
}

function updateSectionCopy(type) {
    if (sectionTitle) {
        sectionTitle.textContent = type === "locations"
            ? "Explore experiences near Cebu City"
            : "Available Tour Guides";
    }
    const subtitle = document.querySelector(".explore-subtitle");
    if (subtitle) {
        subtitle.textContent = type === "locations"
            ? "Can't-miss picks near you"
            : "Book a guide for your trip";
    }
}

function setActiveTab(type) {
    tabButtons.forEach((btn) => {
        btn.classList.toggle("active", btn.dataset.type === type);
    });
}

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

function escapeRegex(value) {
    return String(value || "").replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
}

function hasWordBoundaryMatch(text, keyword) {
    const normalizedText = normalizeMatchText(text);
    const normalizedKeyword = normalizeMatchText(keyword);
    if (!normalizedText || !normalizedKeyword) return false;
    const pattern = new RegExp(`\\b${escapeRegex(normalizedKeyword)}\\b`, "i");
    return pattern.test(normalizedText);
}

function getSpotAreaCandidates(spot) {
    const phraseCandidates = [];
    const tokenCandidates = [];

    const rawAddress = String((spot && spot.address) || "").trim();
    const rawName = String((spot && spot.name) || "").trim();
    const addressParts = rawAddress
        .split(",")
        .map((part) => part.trim())
        .filter(Boolean);

    const primaryArea = addressParts[0] || rawName;
    const secondaryArea = rawName;
    [primaryArea, secondaryArea].forEach((part) => {
        const normalized = normalizeMatchText(part);
        if (normalized && !phraseCandidates.includes(normalized)) {
            phraseCandidates.push(normalized);
        }
    });

    [
        ...tokenizeAreaParts(primaryArea),
        ...tokenizeAreaParts(secondaryArea),
        ...tokenizeAreaParts(rawAddress)
    ].forEach((token) => {
        if (!tokenCandidates.includes(token)) {
            tokenCandidates.push(token);
        }
    });

    // Avoid broad province-level matches (e.g., "cebu") when a more specific locality exists.
    const filteredTokens = tokenCandidates.filter((token) => {
        if (token !== "cebu") return true;
        return tokenCandidates.length <= 1;
    });

    return {
        phrases: phraseCandidates,
        tokens: filteredTokens
    };
}

function isGuideInSpotArea(guide, spot) {
    const guideAreaText = normalizeMatchText(guide.service_areas || "");

    if (!guideAreaText) return false;

    const candidates = getSpotAreaCandidates(spot);
    if (!candidates.phrases.length && !candidates.tokens.length) return false;

    const hasPhraseMatch = candidates.phrases.some((phrase) => hasWordBoundaryMatch(guideAreaText, phrase));
    if (hasPhraseMatch) return true;

    return candidates.tokens.some((token) => hasWordBoundaryMatch(guideAreaText, token));
}

async function showGuidesForSpot(spot) {
    if (!Array.isArray(guideData) || guideData.length === 0) {
        await loadGuideData();
    }

    const matchingGuides = guideData.filter((guide) => !guide.is_booked && isGuideInSpotArea(guide, spot));
    activeGuideAreaFilter = {
        spotName: spot.name || "selected area",
        guides: matchingGuides
    };

    renderGuidesForSelectedSpot(spot, matchingGuides);

    if (currentType === "guides") {
        updateSectionCopy("guides");
        if (sectionTitle) {
            sectionTitle.textContent = `Available Tour Guides in ${spot.name || "this area"}`;
        }
        const subtitle = document.querySelector(".explore-subtitle");
        if (subtitle) {
            subtitle.textContent = matchingGuides.length > 0
                ? "Guides serving this area"
                : "No guides currently available in this area";
        }
        displayCards(matchingGuides);
    }
}

// #region agent log
fetch('http://127.0.0.1:7921/ingest/b62290be-ce23-4956-ad93-971fb215c8cf',{method:'POST',headers:{'Content-Type':'application/json','X-Debug-Session-Id':'e8c76a'},body:JSON.stringify({sessionId:'e8c76a',runId:'pre-fix',hypothesisId:'A,B',location:'script.js:top',message:'script.js loaded',data:{scriptVersion:'2026-03-06_price_removed_v2',href:(typeof window!=='undefined'&&window.location?window.location.href:null)},timestamp:Date.now()})}).catch(()=>{});
// #endregion

// --- 3. PROFILE SYNC LOGIC ---
function getActiveProfileState() {
    const role = localStorage.getItem("role") || "";
    const userId = localStorage.getItem("userId") || "";
    const scopedNameKey = (role && userId) ? `profileName:${role}:${userId}` : "";
    const scopedImageKey = (role && userId) ? `profileImage:${role}:${userId}` : "";

    return {
        role,
        userId,
        name: (scopedNameKey ? localStorage.getItem(scopedNameKey) : null) || localStorage.getItem("fullName") || "Guest Traveler",
        image: (scopedImageKey ? localStorage.getItem(scopedImageKey) : null) || localStorage.getItem("profileImage") || ""
    };
}

function syncProfileData() {
    const profileState = getActiveProfileState();
    const savedName = profileState.name;
    const savedPic = profileState.image;

    const nameEl = document.getElementById("profileName");
    if (nameEl) nameEl.textContent = savedName;

    const handleEl = document.getElementById("profileHandle");
    if (handleEl) handleEl.textContent = `@${savedName.split(" ")[0].toLowerCase()}`;

    const profileElements = document.querySelectorAll('.profile-pic, .large-avatar, #profilePic, #profileDropdownBtn');
    if (savedPic) {
        profileElements.forEach(img => { img.src = savedPic; });
    }
}

async function hydrateProfileDataFromSession() {
    try {
        const response = await fetch("get_user.php", { credentials: "same-origin" });
        if (!response.ok) return;

        const data = await response.json();
        if (!data || !data.success || !data.role || !data.user_id) return;

        const role = String(data.role);
        const userId = String(data.user_id);
        const firstName = String(data.first_name || "");
        const lastName = String(data.last_name || "");
        const fullName = String(data.full_name || "").trim() || "Guest Traveler";
        const profileImage = String(data.profile_image || "");

        localStorage.setItem("userId", userId);
        localStorage.setItem("role", role);
        localStorage.setItem("firstName", firstName);
        localStorage.setItem("lastName", lastName);
        localStorage.setItem("fullName", fullName);
        localStorage.setItem(`firstName:${role}:${userId}`, firstName);
        localStorage.setItem(`lastName:${role}:${userId}`, lastName);
        localStorage.setItem(`profileName:${role}:${userId}`, fullName);
        if (profileImage) {
            localStorage.setItem(`profileImage:${role}:${userId}`, profileImage);
            localStorage.setItem("profileImage", profileImage);
        }

        syncProfileData();
    } catch (_) {}
}

// --- 4. UI RENDERING FUNCTIONS ---

function renderRating(rating, reviewCount) {
    const full = Math.floor(rating);
    const hasHalf = rating % 1 >= 0.5;
    let stars = '';
    for (let i = 0; i < full; i++) stars += '<span class="star filled">●</span>';
    if (hasHalf) stars += '<span class="star half">○</span>';
    for (let i = full + (hasHalf ? 1 : 0); i < 5; i++) stars += '<span class="star">○</span>';
    return `<span class="card-rating">${stars} <span class="review-count">(${reviewCount || 0})</span></span>`;
}

async function toggleFavoriteDestination(button) {
    if (!button) return;
    const activeRole = String(localStorage.getItem("role") || "").toLowerCase();
    const activeUserId = String(localStorage.getItem("userId") || "").trim();
    if (activeRole !== "tourist" || !activeUserId) {
        alert("Please login first to save favorite destinations.");
        return;
    }

    const destinationId = parseInt(button.getAttribute('data-destination-id') || '0', 10);
    if (!Number.isInteger(destinationId) || destinationId <= 0) {
        alert("This destination cannot be favorited right now.");
        return;
    }

    button.disabled = true;
    try {
        const response = await fetch('toggle_favorite_destination.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ destination_id: destinationId })
        });

        let data = {};
        try {
            data = await response.json();
        } catch (_) {}

        if (response.status === 401 || response.status === 403) {
            alert("Please login first to save favorite destinations.");
            return;
        }

        if (!response.ok || !data.success) {
            alert(data.message || "Could not update favorite destination.");
            return;
        }

        const isFavorited = !!data.is_favorited;
        if (isFavorited) {
            favoriteDestinationIds.add(destinationId);
        } else {
            favoriteDestinationIds.delete(destinationId);
        }

        button.classList.toggle('is-favorited', isFavorited);
        button.textContent = isFavorited ? '♥' : '♡';
        button.setAttribute('aria-label', isFavorited ? 'Remove from favorites' : 'Save');
    } catch (_) {
        alert("Could not update favorite destination right now.");
    } finally {
        button.disabled = false;
    }
}

function displayCards(items) {
    if (!container) return;
    container.innerHTML = "";
    displayedItems = Array.isArray(items) ? items : [];
    // #region agent log
    fetch('http://127.0.0.1:7921/ingest/b62290be-ce23-4956-ad93-971fb215c8cf',{method:'POST',headers:{'Content-Type':'application/json','X-Debug-Session-Id':'e8c76a'},body:JSON.stringify({sessionId:'e8c76a',runId:'pre-fix',hypothesisId:'A,B,C',location:'script.js:displayCards',message:'displayCards called',data:{currentType,currentItemsCount:Array.isArray(items)?items.length:null,hasContainer:!!container},timestamp:Date.now()})}).catch(()=>{});
    // #endregion
    
    if (items.length === 0) {
        container.innerHTML = `<p class="no-results">No ${currentType} found matching your search.</p>`;
        return;
    }

    items.forEach((item, index) => {
        const card = document.createElement("div");
        card.classList.add("card");
        card.dataset.index = String(index);
        
        const title = item.name || `${item.first_name} ${item.last_name}`;
        let desc = item.description || `@${item.username}`;
        const imgUrl = item.image || "photos/default-avatar.png";
        const rating = item.rating != null ? item.rating : 4.5;
        const reviewCount = (item.reviewCount != null) ? item.reviewCount : (item.review_count != null ? item.review_count : 0);
        const isBooked = !!item.is_booked;

        // For locations: never show price/payment text; show only description (or fallback)
        if (currentType === "locations") {
            const looksLikePrice = /from\s*P?\s*[\d,]+(\s*per\s*adult)?/i.test(desc) || (item.price && String(desc).trim() === String(item.price).trim());
            if (looksLikePrice || !desc || desc === "—") {
                desc = item.description && !/from\s*P?\s*[\d,]+/i.test(item.description) ? item.description : `Discover ${title}.`;
            }
        }

        if (currentType === "locations") {
            const destinationId = parseInt(item.destinationId || item.destination_id || 0, 10);
            const isFavorited = Number.isInteger(destinationId) && destinationId > 0 && favoriteDestinationIds.has(destinationId);
            const priceLabel = item.price ? String(item.price).trim() : '';
            // #region agent log
            fetch('http://127.0.0.1:7921/ingest/b62290be-ce23-4956-ad93-971fb215c8cf',{method:'POST',headers:{'Content-Type':'application/json','X-Debug-Session-Id':'e8c76a'},body:JSON.stringify({sessionId:'e8c76a',runId:'pre-fix',hypothesisId:'B,C',location:'script.js:displayCards:locations',message:'rendering location card',data:{title:(title||'').slice(0,40),hasPriceField:item&&('price'in item),descPreview:(String(desc||'').slice(0,40))},timestamp:Date.now()})}).catch(()=>{});
            // #endregion
            card.innerHTML = `
                <div class="card-image-wrap">
                    <img src="${imgUrl}" alt="${title}" onerror="this.src='https://via.placeholder.com/300x200?text=No+Image'">
                    <button type="button" class="card-fav${isFavorited ? ' is-favorited' : ''}" data-destination-id="${destinationId > 0 ? destinationId : ''}" aria-label="${isFavorited ? 'Remove from favorites' : 'Save'}">${isFavorited ? '♥' : '♡'}</button>
                </div>
                <div class="card-content">
                    <div class="card-title-row">
                        <h3>${title}</h3>
                        <button type="button" class="card-next-btn" aria-label="Next">→</button>
                    </div>
                    ${renderRating(rating, reviewCount)}
                    ${priceLabel ? `<p class="card-price">Price: ${escapeHtml(priceLabel)}</p>` : ''}
                    <p class="card-desc">${desc}</p>
                </div>
            `;
        } else {
            card.innerHTML = `
                <div class="card-image-wrap">
                    <img src="${imgUrl}" alt="${title}" onerror="this.src='https://via.placeholder.com/300x200?text=No+Image'">
                </div>
                <div class="card-content">
                    <div class="card-title-row">
                        <h3>${title}</h3>
                    </div>
                    ${renderRating(rating, reviewCount)}
                    <p class="card-desc">${desc}</p>
                    <button class="book-btn${isBooked ? ' is-booked' : ''}" data-guide-id="${item.guide_id || ''}" data-is-booked="${isBooked ? '1' : '0'}" ${isBooked ? 'disabled' : ''}>${isBooked ? 'Already Booked' : 'Book Now'}</button>
                </div>
            `;
        }
        container.appendChild(card);
    });
}

function renderGuidesForSelectedSpot(spot, guides) {
    if (!spotGuidesSection || !spotGuidesContainer) return;

    const spotName = spot && spot.name ? spot.name : "this area";
    const count = Array.isArray(guides) ? guides.length : 0;
    const plural = count === 1 ? "" : "s";

    if (spotGuidesTitle) {
        spotGuidesTitle.textContent = `Available Tour Guides in ${spotName}`;
    }
    if (spotGuidesSubtitle) {
        spotGuidesSubtitle.textContent = count > 0
            ? `${count} guide${plural} currently serving this area.`
            : "No guides currently available in this area.";
    }

    if (spotDetailName) {
        spotDetailName.textContent = spotName;
    }
    if (spotDetailRating) {
        const detailRating = spot && spot.rating != null ? spot.rating : 4.5;
        const detailReviews = spot && spot.reviewCount != null ? spot.reviewCount : (spot && spot.review_count != null ? spot.review_count : 0);
        spotDetailRating.innerHTML = renderRating(detailRating, detailReviews);
    }
    if (spotDetailAddress) {
        const address = String((spot && spot.address) || "").trim();
        spotDetailAddress.textContent = address ? `Location: ${address}` : "Location: Cebu, Philippines";
    }
    if (spotDetailDescription) {
        const description = String((spot && spot.description) || "").trim();
        spotDetailDescription.textContent = description || `Discover ${spotName} and find local guides ready to assist your trip.`;
    }
    if (spotDetailImage) {
        const image = (spot && spot.image) ? spot.image : "photos/default.jpg";
        spotDetailImage.src = image;
        spotDetailImage.alt = spotName;
    }

    spotGuidesContainer.innerHTML = "";

    if (count === 0) {
        spotGuidesContainer.innerHTML = `<p class="no-results">No guides found for this location right now.</p>`;
    } else {
        guides.forEach((item) => {
            const card = document.createElement("div");
            card.classList.add("card");

            const title = item.name || `${item.first_name || ""} ${item.last_name || ""}`.trim();
            const desc = item.description || item.specialization || "Experienced tour guide.";
            const imgUrl = item.image || "photos/default-avatar.png";
            const rating = item.rating != null ? item.rating : 4.5;
            const reviewCount = item.reviewCount != null
                ? item.reviewCount
                : (item.review_count != null ? item.review_count : 0);
            const isBooked = !!item.is_booked;

            card.innerHTML = `
                <div class="card-image-wrap">
                    <img src="${imgUrl}" alt="${title}" onerror="this.src='https://via.placeholder.com/300x200?text=No+Image'">
                </div>
                <div class="card-content">
                    <div class="card-title-row">
                        <h3>${title}</h3>
                    </div>
                    ${renderRating(rating, reviewCount)}
                    <p class="card-desc">${desc}</p>
                    <button class="book-btn${isBooked ? ' is-booked' : ''}" data-guide-id="${item.guide_id || ''}" data-is-booked="${isBooked ? '1' : '0'}" ${isBooked ? 'disabled' : ''}>${isBooked ? 'Already Booked' : 'Book Now'}</button>
                </div>
            `;

            spotGuidesContainer.appendChild(card);
        });
    }

    spotGuidesSection.hidden = false;
    spotGuidesSection.scrollIntoView({ behavior: "smooth", block: "start" });
}

function closeLocationDetailsModal() {
    if (!locationDetailModal) return;
    locationDetailModal.hidden = true;
    document.body.classList.remove("location-modal-open");
}

function openLocationDetailsModal(spot) {
    if (!locationDetailModal || !spot) return;

    selectedSpotForModal = spot;
    const spotName = String(spot.name || "Selected location").trim() || "Selected location";
    const address = String(spot.address || "").trim();
    const description = String(spot.description || "").trim();
    const image = String(spot.image || "").trim() || "photos/default.jpg";
    const detailRating = spot.rating != null ? spot.rating : 4.5;
    const detailReviews = spot.reviewCount != null ? spot.reviewCount : (spot.review_count != null ? spot.review_count : 0);

    if (locationDetailModalName) locationDetailModalName.textContent = spotName;
    if (locationDetailModalRating) locationDetailModalRating.innerHTML = renderRating(detailRating, detailReviews);
    if (locationDetailModalAddress) {
        locationDetailModalAddress.textContent = address ? `Location: ${address}` : "Location: Cebu, Philippines";
    }
    if (locationDetailModalDescription) {
        locationDetailModalDescription.textContent = description || `Discover ${spotName} and explore what this destination offers.`;
    }
    if (locationDetailModalImage) {
        locationDetailModalImage.src = image;
        locationDetailModalImage.alt = spotName;
    }

    locationDetailModal.hidden = false;
    document.body.classList.add("location-modal-open");
}

function switchToGuidesForSelectedSpot() {
    if (!activeGuideAreaFilter) return;

    currentType = "guides";
    setActiveTab("guides");
    updateSectionCopy("guides");

    if (sectionTitle) {
        sectionTitle.textContent = `Available Tour Guides in ${activeGuideAreaFilter.spotName || "this area"}`;
    }
    const subtitle = document.querySelector(".explore-subtitle");
    if (subtitle) {
        subtitle.textContent = activeGuideAreaFilter.guides.length > 0
            ? "Guides serving this area"
            : "No guides currently available in this area";
    }
    if (searchInput) {
        searchInput.placeholder = "Search guides...";
        searchInput.value = "";
    }

    displayCards(activeGuideAreaFilter.guides);
}

function handleGuideBooking(button) {
    function toDateTimeLocalValue(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        return year + '-' + month + '-' + day + 'T' + hours + ':' + minutes;
    }

    function getDefaultMeetTimeInput() {
        const now = new Date(Date.now() + (60 * 60 * 1000));
        return toDateTimeLocalValue(now);
    }

    function normalizeMeetTimeInput(value) {
        const raw = String(value || '').trim();
        if (!raw) {
            return '';
        }

        const normalized = raw
            .replace(/\//g, '-')
            .replace(/\s+/g, ' ')
            .replace(/^(\d{4}-\d{2}-\d{2})\s+(\d{1,2}:\d{2}(?::\d{2})?)$/, '$1T$2');

        const directDate = new Date(normalized);
        if (!isNaN(directDate.getTime())) {
            return toDateTimeLocalValue(directDate);
        }

        const match = raw.match(/^(\d{4})-(\d{2})-(\d{2})[\sT]+(\d{1,2}):(\d{2})(?:\s*([AaPp][Mm]))?$/);
        if (!match) {
            return '';
        }

        let hours = parseInt(match[4], 10);
        const minutes = parseInt(match[5], 10);
        const meridiem = (match[6] || '').toUpperCase();

        if (minutes > 59 || hours > 23) {
            return '';
        }

        if (meridiem) {
            if (hours < 1 || hours > 12) {
                return '';
            }
            if (meridiem === 'PM' && hours !== 12) hours += 12;
            if (meridiem === 'AM' && hours === 12) hours = 0;
        }

        const date = new Date(
            parseInt(match[1], 10),
            parseInt(match[2], 10) - 1,
            parseInt(match[3], 10),
            hours,
            minutes,
            0,
            0
        );

        return isNaN(date.getTime()) ? '' : toDateTimeLocalValue(date);
    }

    const guideId = parseInt(button.getAttribute("data-guide-id") || "0", 10);
    const card = button.closest(".card");
    const guideName = card && card.querySelector("h3") ? card.querySelector("h3").textContent.trim() : "this guide";
    const isBooked = button.getAttribute("data-is-booked") === "1";
    const isLoggedIn = localStorage.getItem("userLoggedIn") === "true";
    const role = localStorage.getItem("role") || "";

    if (!guideId) {
        alert("This guide is not available for booking right now.");
        return;
    }

    if (isBooked) {
        alert("This guide has already been booked. Please choose another guide.");
        return;
    }

    if (!isLoggedIn || role !== "tourist") {
        alert("Please sign in as a tourist to book a guide.");
        window.location.href = "signinTouristAdmin.html";
        return;
    }

    if (!window.confirm(`Send a booking request to ${guideName}?`)) {
        return;
    }

    const originalText = button.textContent;
    button.disabled = true;
    button.textContent = "Sending...";

    const form = new FormData();
    form.append("guide_id", guideId);

    fetch("book_guide.php", { method: "POST", credentials: "same-origin", body: form })
        .then(async (response) => {
            let data = {};
            try {
                data = await response.json();
            } catch (err) {
                data = {};
            }
            return { status: response.status, data };
        })
        .then(({ status, data }) => {
            if (status === 403) {
                alert(data.error || "Please sign in as a tourist first.");
                window.location.href = "signinTouristAdmin.html";
                return;
            }

            if (!data.ok) {
                throw new Error(data.error || "Booking request failed.");
            }

            button.textContent = "Requested";
            alert(data.message || "Booking request sent. Waiting for the guide to accept.");
        })
        .catch((error) => {
            alert(error.message || "Could not send booking request.");
            button.disabled = false;
            button.textContent = originalText;
        });
}

function normalizeReviewRating(rating) {
    const parsed = parseInt(rating, 10);
    if (Number.isNaN(parsed)) return 5;
    return Math.max(1, Math.min(5, parsed));
}

async function getPublicReviews() {
    try {
        const response = await fetch("get_public_reviews.php", { credentials: "same-origin" });
        if (!response.ok) return [];

        const data = await response.json();
        const submittedReviews = Array.isArray(data.reviews) ? data.reviews : [];
        return submittedReviews.map((review) => ({
            name: String(review.tourist_name || "Traveler").trim() || "Traveler",
            rating: normalizeReviewRating(review.rating),
            comment: String(review.comment || "").trim() || "Had a great trip with GuideMate."
        }));
    } catch (_) {
        return [];
    }
}

async function loadReviews() {
    if (!reviewContainer) return;
    reviewContainer.innerHTML = "";

    const allReviews = await getPublicReviews();

    if (allReviews.length === 0) {
        const emptyCard = document.createElement("div");
        emptyCard.classList.add("review-card");
        emptyCard.innerHTML = `
            <h3>No reviews yet</h3>
            <p>Traveler reviews will appear here once users submit them.</p>
        `;
        reviewContainer.appendChild(emptyCard);
        return;
    }

    allReviews.forEach(review => {
        const displayName = review.name || "Traveler";
        const card = document.createElement("div");
        card.classList.add("review-card");
        card.innerHTML = `
            <h3>${displayName}</h3>
            <div class="stars">${"★".repeat(normalizeReviewRating(review.rating))}</div>
            <p>${review.comment}</p>
        `;
        reviewContainer.appendChild(card);
    });
}

// --- 5. AUTHENTICATION & NAVIGATION ---

function initializeAuthButtons() {
    const loginLink = document.getElementById("loginLink");
    const profileContainer = document.getElementById("profileContainer");
    const isLoggedIn = localStorage.getItem("userLoggedIn");
    
    if (isLoggedIn === "true") {
        if (loginLink) loginLink.style.display = "none";
        if (profileContainer) profileContainer.style.display = "block";
    } else {
        if (loginLink) loginLink.style.display = "block";
        if (profileContainer) profileContainer.style.display = "none";
    }
}

function clearStoredSessionState() {
    const activeRole = localStorage.getItem("role") || "";
    const activeUserId = localStorage.getItem("userId") || "";

    localStorage.removeItem("userLoggedIn");
    localStorage.removeItem("userId");
    localStorage.removeItem("role");
    localStorage.removeItem("touristId");
    localStorage.removeItem("guideId");
    localStorage.removeItem("firstName");
    localStorage.removeItem("lastName");
    localStorage.removeItem("fullName");
    localStorage.removeItem("profileImage");
    localStorage.removeItem("userReviews");

    if (activeRole && activeUserId) {
        localStorage.removeItem(`firstName:${activeRole}:${activeUserId}`);
        localStorage.removeItem(`lastName:${activeRole}:${activeUserId}`);
        localStorage.removeItem(`profileName:${activeRole}:${activeUserId}`);
        localStorage.removeItem(`profileImage:${activeRole}:${activeUserId}`);
    }
}

function handleLogout() {
    if (typeof showLogoutConfirm === 'function') {
        showLogoutConfirm(function() {
            clearStoredSessionState();
            window.location.href = "logout.php";
        }, 'Sign out? Yes or No');
    } else {
        clearStoredSessionState();
        window.location.href = "logout.php";
    }
}

// --- 6. EVENT LISTENERS ---

// Tab Switching logic
tabButtons.forEach(button => {
    button.addEventListener("click", () => {
        setActiveTab(button.dataset.type);
        currentType = button.dataset.type;

        if (currentType === "guides" && activeGuideAreaFilter && activeGuideAreaFilter.spotName) {
            if (sectionTitle) {
                sectionTitle.textContent = `Available Tour Guides in ${activeGuideAreaFilter.spotName}`;
            }
            const subtitle = document.querySelector(".explore-subtitle");
            if (subtitle) {
                subtitle.textContent = activeGuideAreaFilter.guides.length > 0
                    ? "Guides serving this area"
                    : "No guides currently available in this area";
            }
        } else {
            updateSectionCopy(currentType);
        }

        if (searchInput) {
            searchInput.placeholder = `Search ${currentType}...`;
            searchInput.value = ""; 
        }
        
        // Show correct data
        displayCards(getCurrentDataPool());
    });
});

if (searchInput) {
    searchInput.addEventListener('input', (e) => {
        const term = e.target.value.toLowerCase().trim();
        const dataPool = getCurrentDataPool();
        
        const filtered = dataPool.filter(item => {
            const name = (item.name || `${item.first_name} ${item.last_name}`).toLowerCase();
            const desc = (item.description || item.username || "").toLowerCase();
            return name.includes(term) || desc.includes(term);
        });
        
        displayCards(filtered);
    });
}

// Profile Dropdown Toggle
const profilePicBtn = document.getElementById("profilePic");
if (profilePicBtn) {
    profilePicBtn.addEventListener("click", (e) => {
        const menu = document.getElementById("dropdownMenu");
        if (menu) menu.classList.toggle("show");
        e.stopPropagation();
    });
}

window.onclick = (event) => {
    if (!event.target.matches('.profile-pic')) {
        const menu = document.getElementById("dropdownMenu");
        if (menu && menu.classList.contains('show')) {
            menu.classList.remove('show');
        }
    }
};

// --- 7. INITIALIZATION ---

async function loadGuideData() {
    try {
        const response = await fetch('get_guides.php');
        const data = await response.json();
        // Set the global guideData variable
        guideData = Array.isArray(data)
            ? data.map((item) => ({ ...item, is_booked: !!item.is_booked }))
            : [];
    } catch (error) {
        console.error('Error loading guides from DB, using defaults:', error);
        guideData = [
            { first_name: "Vince", last_name: "", username: "vince_trek", image: "photos/vince.jfif", is_booked: false },
            { first_name: "Christian", last_name: "", username: "xtian_eats", image: "photos/christian.avif", is_booked: false }
        ];
    }
}

async function loadSpotData() {
    try {
        const isFile = typeof window !== 'undefined' && window.location && window.location.protocol === 'file:';
        const endpoint = isFile ? 'http://localhost/guidemate1/get_spots.php' : 'get_spots.php';

        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 5000);

        const response = await fetch(endpoint, { credentials: 'same-origin', signal: controller.signal });
        clearTimeout(timeout);

        if (!response.ok) throw new Error(`get_spots failed: ${response.status}`);
        const data = await response.json();
        if (Array.isArray(data)) {
            locationData = data;
        } else {
            locationData = [...defaultLocationData];
        }
    } catch (error) {
        console.error('Error loading spots from DB, using defaults:', error);
        locationData = [...defaultLocationData];
    }
}

document.addEventListener("DOMContentLoaded", async () => {
    // #region agent log
    fetch('http://127.0.0.1:7921/ingest/b62290be-ce23-4956-ad93-971fb215c8cf',{method:'POST',headers:{'Content-Type':'application/json','X-Debug-Session-Id':'e8c76a'},body:JSON.stringify({sessionId:'e8c76a',runId:'pre-fix',hypothesisId:'A,B',location:'script.js:DOMContentLoaded',message:'DOMContentLoaded fired',data:{hasCardsContainer:!!document.getElementById('cardsContainer')},timestamp:Date.now()})}).catch(()=>{});
    // #endregion
    syncProfileData(); 
    hydrateProfileDataFromSession();
    initializeAuthButtons();

    if (container) {
        // Render immediately (fast), then replace with DB spots when loaded
        displayCards(locationData);

        loadSpotData().then(() => {
            if (currentType === 'locations') displayCards(locationData);
        });

        await loadGuideData();
        await loadFavoriteDestinationIds();
        if (currentType === 'locations') {
            displayCards(locationData);
        }
    }
    
    var scrollAmount = 300;
    var scrollLeftBtn = document.getElementById("scrollLeft");
    var scrollRightBtn = document.getElementById("scrollRight");
    if (scrollLeftBtn && container) {
        scrollLeftBtn.addEventListener("click", function() {
            container.scrollBy({ left: -scrollAmount, behavior: "smooth" });
        });
    }
    if (scrollRightBtn && container) {
        scrollRightBtn.addEventListener("click", function() {
            container.scrollBy({ left: scrollAmount, behavior: "smooth" });
        });
    }

    if (container) {
        container.addEventListener("click", function(e) {
            var bookBtn = e.target.closest(".book-btn");
            if (bookBtn) {
                handleGuideBooking(bookBtn);
                return;
            }
            var favoriteBtn = e.target.closest(".card-fav");
            if (favoriteBtn) {
                e.preventDefault();
                e.stopPropagation();
                toggleFavoriteDestination(favoriteBtn);
                return;
            }
            var nextBtn = e.target.closest(".card-next-btn");
            if (nextBtn) {
                var card = nextBtn.closest(".card");
                if (currentType === "locations" && card) {
                    var spotIndex = parseInt(card.dataset.index || "-1", 10);
                    if (Number.isInteger(spotIndex) && spotIndex >= 0 && spotIndex < displayedItems.length) {
                        var spotFromArrow = displayedItems[spotIndex];
                        if (spotFromArrow) {
                            openLocationDetailsModal(spotFromArrow);
                            return;
                        }
                    }
                }
                if (card && card.nextElementSibling) {
                    card.nextElementSibling.scrollIntoView({ behavior: "smooth", block: "nearest", inline: "start" });
                } else {
                    container.scrollBy({ left: scrollAmount, behavior: "smooth" });
                }
                return;
            }

            if (currentType === "locations") {
                var cardEl = e.target.closest(".card");
                if (!cardEl) return;

                var cardIndex = parseInt(cardEl.dataset.index || "-1", 10);
                if (!Number.isInteger(cardIndex) || cardIndex < 0 || cardIndex >= displayedItems.length) return;

                var selectedSpot = displayedItems[cardIndex];
                if (!selectedSpot) return;

                openLocationDetailsModal(selectedSpot);
            }
        });
    }

    if (spotGuidesContainer) {
        spotGuidesContainer.addEventListener("click", function(e) {
            var bookBtn = e.target.closest(".book-btn");
            if (bookBtn) {
                handleGuideBooking(bookBtn);
            }
        });
    }

    if (spotGuidesOpenBtn) {
        spotGuidesOpenBtn.addEventListener("click", function() {
            switchToGuidesForSelectedSpot();
        });
    }

    if (locationDetailBackdrop) {
        locationDetailBackdrop.addEventListener("click", function() {
            closeLocationDetailsModal();
        });
    }

    if (locationDetailCloseBtn) {
        locationDetailCloseBtn.addEventListener("click", function() {
            closeLocationDetailsModal();
        });
    }

    if (locationDetailModalGuidesBtn) {
        locationDetailModalGuidesBtn.addEventListener("click", function() {
            if (!selectedSpotForModal) return;
            closeLocationDetailsModal();
            showGuidesForSpot(selectedSpotForModal);
        });
    }

    document.addEventListener("keydown", function(event) {
        if (event.key === "Escape" && locationDetailModal && !locationDetailModal.hidden) {
            closeLocationDetailsModal();
        }
    });
    
    if (reviewContainer) loadReviews();
});