document.addEventListener("DOMContentLoaded", () => {
    // 1. INITIAL SYNC & SELECTORS
    let role = localStorage.getItem("role") || "";
    let userId = localStorage.getItem("userId") || "";
    let scopedNameKey = (role && userId) ? `profileName:${role}:${userId}` : "";
    let scopedImageKey = (role && userId) ? `profileImage:${role}:${userId}` : "";
    let fullName = (scopedNameKey ? localStorage.getItem(scopedNameKey) : null) || localStorage.getItem("fullName") || "Guest Traveler";
    let firstName = (role && userId ? localStorage.getItem(`firstName:${role}:${userId}`) : null) || localStorage.getItem("firstName") || "";
    let lastName = (role && userId ? localStorage.getItem(`lastName:${role}:${userId}`) : null) || localStorage.getItem("lastName") || "";
    let canChangeProfileImage = true;
    let nextProfileImageChangeAt = "";
    let profileImageCooldownDays = 15;

    const profileName = document.getElementById("profileName");
    const profileHandle = document.getElementById("profileHandle");
    const profilePics = document.querySelectorAll(".profile-pic, .large-avatar, #profilePic, #profileDropdownBtn");
    const profileNotice = document.getElementById("profileNotice");
    const editProfileToggle = document.getElementById("editProfileToggle");
    const profileEditPanel = document.getElementById("profileEditPanel");
    const touristProfileForm = document.getElementById("touristProfileForm");
    const editFirstName = document.getElementById("editFirstName");
    const editLastName = document.getElementById("editLastName");
    const editProfileImage = document.getElementById("editProfileImage");
    const cancelProfileEdit = document.getElementById("cancelProfileEdit");
    const saveProfileChangesBtn = document.getElementById("saveProfileChangesBtn");
    const profilePicCooldownText = document.getElementById("profilePicCooldownText");
    const favoriteDestinationsList = document.getElementById("favoriteDestinationsList");

    function refreshScopedKeys() {
        scopedNameKey = (role && userId) ? `profileName:${role}:${userId}` : "";
        scopedImageKey = (role && userId) ? `profileImage:${role}:${userId}` : "";
    }

    function showProfileNotice(message, type = "success") {
        if (!profileNotice) return;
        profileNotice.textContent = message;
        profileNotice.className = "profile-notice";
        profileNotice.classList.add(type, "show");
    }

    function updateDisplay(name) {
        if (profileName) profileName.textContent = name;
        if (profileHandle) {
            const handle = (name.split(" ")[0] || "traveler").toLowerCase();
            profileHandle.textContent = `@${handle}`;
        }
    }

    function syncImages() {
        const savedImage = (scopedImageKey ? localStorage.getItem(scopedImageKey) : null) || localStorage.getItem("profileImage");
        if (savedImage && profilePics) {
            profilePics.forEach((img) => {
                img.src = savedImage;
            });
        }
    }

    function fillProfileForm() {
        if (editFirstName) editFirstName.value = firstName;
        if (editLastName) editLastName.value = lastName;
        if (editProfileImage) editProfileImage.value = "";
    }

    function formatDateLabel(rawDate) {
        if (!rawDate) return "";
        const parsed = new Date(rawDate);
        if (Number.isNaN(parsed.getTime())) return rawDate;
        return parsed.toLocaleDateString(undefined, {
            year: "numeric",
            month: "long",
            day: "numeric"
        });
    }

    function getRemainingDays(rawDate) {
        if (!rawDate) return 0;
        const parsed = new Date(rawDate);
        if (Number.isNaN(parsed.getTime())) return 0;
        const diffMs = parsed.getTime() - Date.now();
        if (diffMs <= 0) return 0;
        return Math.ceil(diffMs / 86400000);
    }

    function updateProfileImageAvailability() {
        if (editProfileImage) {
            editProfileImage.disabled = !canChangeProfileImage;
            if (!canChangeProfileImage) editProfileImage.value = "";
        }

        if (!profilePicCooldownText) return;

        if (canChangeProfileImage) {
            profilePicCooldownText.textContent = `You can change your photo now. After uploading a new one, you must wait ${profileImageCooldownDays} days before changing it again.`;
            return;
        }

        const daysLeft = getRemainingDays(nextProfileImageChangeAt);
        const nextDate = formatDateLabel(nextProfileImageChangeAt);
        const dayLabel = daysLeft === 1 ? "1 day" : `${daysLeft} days`;
        profilePicCooldownText.textContent = nextDate
            ? `Profile photo changes are limited to once every ${profileImageCooldownDays} days. You can upload again in ${dayLabel}, on ${nextDate}.`
            : `Profile photo changes are limited to once every ${profileImageCooldownDays} days.`;
    }

    function toggleEditPanel(forceState) {
        if (!profileEditPanel) return;
        const shouldOpen = typeof forceState === "boolean" ? forceState : profileEditPanel.hidden;
        profileEditPanel.hidden = !shouldOpen;
        if (editProfileToggle) {
            editProfileToggle.textContent = shouldOpen ? "Close editor" : "Edit profile";
        }
        if (shouldOpen) fillProfileForm();
    }

    function applyProfileData(data) {
        const sessionRole = String(data.role || role);
        const sessionUserId = String(data.user_id || userId);
        const sessionFirstName = String(data.first_name || "").trim();
        const sessionLastName = String(data.last_name || "").trim();
        const sessionFullName = String(data.full_name || "").trim() || "Guest Traveler";
        const sessionImage = String(data.profile_image || "");

        role = sessionRole;
        userId = sessionUserId;
        refreshScopedKeys();

        firstName = sessionFirstName;
        lastName = sessionLastName;
        fullName = sessionFullName;
        canChangeProfileImage = data.can_change_profile_image !== false;
        nextProfileImageChangeAt = String(data.next_profile_image_change_at || "");

        const cooldownValue = Number(data.profile_image_cooldown_days);
        if (!Number.isNaN(cooldownValue) && cooldownValue > 0) {
            profileImageCooldownDays = cooldownValue;
        } else if (sessionRole === "guide") {
            profileImageCooldownDays = 30;
        } else {
            profileImageCooldownDays = 15;
        }

        localStorage.setItem("role", sessionRole);
        localStorage.setItem("userId", sessionUserId);
        localStorage.setItem("firstName", sessionFirstName);
        localStorage.setItem("lastName", sessionLastName);
        localStorage.setItem("fullName", sessionFullName);
        localStorage.setItem(`firstName:${sessionRole}:${sessionUserId}`, sessionFirstName);
        localStorage.setItem(`lastName:${sessionRole}:${sessionUserId}`, sessionLastName);
        localStorage.setItem(`profileName:${sessionRole}:${sessionUserId}`, sessionFullName);
        if (sessionImage) {
            localStorage.setItem(`profileImage:${sessionRole}:${sessionUserId}`, sessionImage);
            localStorage.setItem("profileImage", sessionImage);
        }

        updateDisplay(sessionFullName);
        syncImages();
        fillProfileForm();
        updateProfileImageAvailability();
        if (typeof syncProfileData === "function") syncProfileData();
    }

    async function hydrateProfileFromSession() {
        try {
            const response = await fetch("get_user.php", { credentials: "same-origin" });
            if (!response.ok) return;

            const data = await response.json();
            if (!data || !data.success) return;
            applyProfileData(data);
        } catch (_) {}
    }

    function formatFavoriteDate(rawDate) {
        if (!rawDate) return "";
        const parsed = new Date(rawDate);
        if (Number.isNaN(parsed.getTime())) return "";
        return parsed.toLocaleDateString(undefined, {
            year: "numeric",
            month: "short",
            day: "numeric"
        });
    }

    async function loadFavoriteDestinations() {
        if (!favoriteDestinationsList) return;
        favoriteDestinationsList.innerHTML = "<p>Loading your favorite destinations...</p>";

        try {
            const response = await fetch("get_my_favorite_destinations.php", { credentials: "same-origin" });
            let data = {};
            try {
                data = await response.json();
            } catch (_) {}

            if (!response.ok || !data.success) {
                favoriteDestinationsList.innerHTML = `<p>${data.message || "Could not load favorite destinations."}</p>`;
                return;
            }

            const favorites = Array.isArray(data.favorites) ? data.favorites : [];
            if (favorites.length === 0) {
                favoriteDestinationsList.innerHTML = "<p>You have no favorite destinations yet. Tap the heart on a location card to save one.</p>";
                return;
            }

            favoriteDestinationsList.innerHTML = "";
            favorites.forEach((item) => {
                const card = document.createElement("article");
                card.className = "favorite-destination-item";

                const image = document.createElement("img");
                image.className = "favorite-destination-thumb";
                image.src = item.image || "photos/default.jpg";
                image.alt = item.name || "Favorite destination";
                image.loading = "lazy";

                const body = document.createElement("div");
                body.className = "favorite-destination-body";

                const title = document.createElement("h3");
                title.textContent = item.name || "Unnamed destination";

                const address = document.createElement("p");
                address.textContent = item.address ? `Location: ${item.address}` : "Location details unavailable";

                const meta = document.createElement("div");
                meta.className = "favorite-destination-meta";
                const favoriteDate = formatFavoriteDate(item.favorited_at);
                meta.textContent = favoriteDate ? `Saved on ${favoriteDate}` : "Saved destination";

                body.appendChild(title);
                body.appendChild(address);
                body.appendChild(meta);
                card.appendChild(image);
                card.appendChild(body);
                favoriteDestinationsList.appendChild(card);
            });
        } catch (_) {
            favoriteDestinationsList.innerHTML = "<p>Could not load favorite destinations right now.</p>";
        }
    }

    async function saveTouristProfile(e) {
        e.preventDefault();
        if (!touristProfileForm || !editFirstName || !editLastName) return;

        const trimmedFirstName = editFirstName.value.trim();
        const trimmedLastName = editLastName.value.trim();
        if (!trimmedFirstName || !trimmedLastName) {
            showProfileNotice("First name and last name are required.", "error");
            return;
        }

        const formData = new FormData();
        formData.append("first_name", trimmedFirstName);
        formData.append("last_name", trimmedLastName);

        if (editProfileImage && editProfileImage.files && editProfileImage.files[0] && !editProfileImage.disabled) {
            formData.append("profile_image", editProfileImage.files[0]);
        }

        if (saveProfileChangesBtn) {
            saveProfileChangesBtn.disabled = true;
            saveProfileChangesBtn.textContent = "Saving...";
        }

        try {
            const response = await fetch("update_tourist_profile.php", {
                method: "POST",
                credentials: "same-origin",
                body: formData
            });
            let data = {};
            try {
                data = await response.json();
            } catch (_) {}

            if (!response.ok || !data.success) {
                if (data && Object.prototype.hasOwnProperty.call(data, "can_change_profile_image")) {
                    canChangeProfileImage = data.can_change_profile_image !== false;
                    nextProfileImageChangeAt = String(data.next_profile_image_change_at || "");
                    const cooldownValue = Number(data.profile_image_cooldown_days);
                    if (!Number.isNaN(cooldownValue) && cooldownValue > 0) {
                        profileImageCooldownDays = cooldownValue;
                    }
                    updateProfileImageAvailability();
                }
                showProfileNotice(data.message || "Could not update profile.", "error");
                return;
            }

            applyProfileData(data);
            toggleEditPanel(false);
            showProfileNotice(data.message || "Profile updated successfully.", "success");
        } catch (_) {
            showProfileNotice("Could not update profile right now. Please try again.", "error");
        } finally {
            if (saveProfileChangesBtn) {
                saveProfileChangesBtn.disabled = false;
                saveProfileChangesBtn.textContent = "Save changes";
            }
        }
    }

    // Run initial display sync
    updateDisplay(fullName);
    syncImages();
    fillProfileForm();
    updateProfileImageAvailability();
    hydrateProfileFromSession();
    loadFavoriteDestinations();

    if (editProfileToggle) {
        editProfileToggle.addEventListener("click", () => toggleEditPanel());
    }

    if (cancelProfileEdit) {
        cancelProfileEdit.addEventListener("click", () => {
            fillProfileForm();
            toggleEditPanel(false);
        });
    }

    if (touristProfileForm) {
        touristProfileForm.addEventListener("submit", saveTouristProfile);
    }

    // 2. TAB SWITCHING LOGIC
    const profileTabs = document.querySelectorAll('.profile-tabs a');
    const activityContent = document.getElementById('activityContent');
    const reviewsContent = document.getElementById('reviewsContent');

    profileTabs.forEach(tab => {
        tab.addEventListener('click', (e) => {
            e.preventDefault();
            profileTabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');

            if (tab.textContent.trim() === 'Reviews') {
                if (activityContent) activityContent.style.display = 'none';
                if (reviewsContent) {
                    reviewsContent.style.display = 'block';
                    loadUserReviews();
                }
            } else {
                if (activityContent) activityContent.style.display = 'block';
                if (reviewsContent) reviewsContent.style.display = 'none';
                loadFavoriteDestinations();
            }
        });
    });

    // 3. Logout (Sign out)
    const logoutBtn = document.getElementById("logoutBtn");
    if (logoutBtn) {
        logoutBtn.addEventListener("click", (e) => {
            e.preventDefault();
            const performLogout = () => {
                const activeRole = localStorage.getItem("role") || "";
                const activeUserId = localStorage.getItem("userId") || "";

                localStorage.removeItem("userLoggedIn");
                localStorage.removeItem("role");
                localStorage.removeItem("userId");
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

                window.location.href = "logout.php";
            };

            if (typeof showLogoutConfirm === 'function') {
                showLogoutConfirm(performLogout, 'Sign out? Yes or No');
            } else {
                performLogout();
            }
        });
    }

    // 4. INITIALIZE REVIEW SYSTEM
    initializeReviewDropdowns();
    initializeProfileReviewForm();
});

// --- 5. REVIEW SYSTEM FUNCTIONS ---

async function fetchMyReviewData() {
    try {
        const response = await fetch('get_my_reviews.php', { credentials: 'same-origin' });
        let data = {};
        try {
            data = await response.json();
        } catch (_) {}
        if (!response.ok && !data.error) {
            data.error = 'Could not load your review data.';
        }
        return data;
    } catch (_) {
        return { reviews: [], eligible_guides: [], error: 'Could not load your review data.' };
    }
}

function updateReviewFormAvailability(canSubmit, customMessage) {
    const form = document.getElementById('profileReviewForm');
    const messageDiv = document.getElementById('profileReviewMessage');
    if (!form) return;

    form.querySelectorAll('select, textarea, input[type="radio"], button[type="submit"]').forEach((field) => {
        field.disabled = !canSubmit;
    });

    if (!canSubmit) {
        showProfileMessage(
            customMessage || 'You can only submit a review for guides that you have booked.',
            'error',
            messageDiv
        );
        return;
    }

    if (messageDiv && messageDiv.classList.contains('error')) {
        messageDiv.textContent = '';
        messageDiv.classList.remove('success', 'error');
    }
}

async function initializeReviewDropdowns() {
    const locationSelect = document.getElementById('reviewLocation');
    const guideSelect = document.getElementById('reviewGuide');
    if (!locationSelect || !guideSelect) return;

    let locations = Array.isArray(window.locationData) ? window.locationData : [];
    if (locations.length === 0) {
        try {
            const res = await fetch('get_spots.php', { credentials: 'same-origin' });
            const data = await res.json();
            if (Array.isArray(data)) locations = data;
        } catch (_) {}
    }

    populateDropdown(locationSelect, locations, 'Select a location');

    const reviewData = await fetchMyReviewData();
    const eligibleGuides = Array.isArray(reviewData.eligible_guides) ? reviewData.eligible_guides : [];
    populateDropdown(
        guideSelect,
        eligibleGuides,
        eligibleGuides.length > 0 ? 'Select a tour guide' : 'No booked guides yet'
    );
    if (eligibleGuides.length === 1) {
        guideSelect.value = String(eligibleGuides[0].guide_id);
    }
    updateReviewFormAvailability(eligibleGuides.length > 0, reviewData.error);
}

function populateDropdown(selectElement, dataPool, placeholderText) {
    selectElement.innerHTML = `<option value="">${placeholderText}</option>`;
    (dataPool || []).forEach(item => {
        const name = (item && item.name) ? item.name : [item?.first_name, item?.last_name].filter(Boolean).join(' ');
        if (!name) return;
        const option = document.createElement('option');
        if (Object.prototype.hasOwnProperty.call(item || {}, 'guide_id') && item.guide_id) {
            option.value = String(item.guide_id);
            option.dataset.name = name;
        } else {
            option.value = name;
        }
        option.textContent = name;
        selectElement.appendChild(option);
    });
}

function initializeProfileReviewForm() {
    const form = document.getElementById('profileReviewForm');
    const messageDiv = document.getElementById('profileReviewMessage');
    const submitBtn = form ? form.querySelector('button[type="submit"]') : null;
    if (!form) return;

    form.addEventListener('submit', (e) => {
        e.preventDefault();

        const reviewType = document.getElementById('reviewType').value.trim();
        const locationName = document.getElementById('reviewLocation').value.trim();
        const guideSelect = document.getElementById('reviewGuide');
        const guideId = guideSelect ? parseInt(guideSelect.value, 10) || 0 : 0;
        const guideName = guideSelect
            ? ((guideSelect.options[guideSelect.selectedIndex]?.dataset.name || guideSelect.options[guideSelect.selectedIndex]?.textContent || '').trim())
            : '';
        const rating = document.querySelector('input[name="tourRating"]:checked')?.value;
        const comment = document.getElementById('tourReviewComment').value.trim();

        if (!reviewType || !locationName || !guideName || !rating || !comment) {
            showProfileMessage('Please fill in all fields.', 'error', messageDiv);
            return;
        }

        if (submitBtn) submitBtn.disabled = true;

        fetch('submit_review.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                review_type: reviewType,
                location_name: locationName,
                guide_id: guideId,
                guide_name: guideName,
                rating: parseInt(rating, 10),
                comment: comment
            })
        })
            .then((res) => res.json())
            .then((data) => {
                if (!data.success) {
                    showProfileMessage(data.message || 'Could not submit review.', 'error', messageDiv);
                    if (submitBtn) submitBtn.disabled = false;
                    return;
                }

                showProfileMessage('Review submitted successfully! It is now visible to the guide and admin.', 'success', messageDiv);
                form.reset();
                initializeReviewDropdowns();
                loadUserReviews();

                setTimeout(() => {
                    if (messageDiv) {
                        messageDiv.textContent = '';
                        messageDiv.classList.remove('success', 'error');
                    }
                }, 3000);
                if (submitBtn) submitBtn.disabled = false;
            })
            .catch(() => {
                showProfileMessage('Request failed. Please try again.', 'error', messageDiv);
                if (submitBtn) submitBtn.disabled = false;
            });
    });
}

function formatReviewDate(rawDate) {
    if (!rawDate) return 'Unknown date';
    const parsedDate = new Date(rawDate);
    if (Number.isNaN(parsedDate.getTime())) return String(rawDate);
    return parsedDate.toLocaleDateString();
}

async function loadUserReviews() {
    const reviewsList = document.getElementById('yourReviewsList');
    if (!reviewsList) return;

    const reviewData = await fetchMyReviewData();
    const userReviews = Array.isArray(reviewData.reviews) ? reviewData.reviews : [];
    if (userReviews.length === 0) {
        reviewsList.innerHTML = '<p>You haven\'t submitted any reviews yet.</p>';
        return;
    }

    reviewsList.innerHTML = '';
    userReviews.forEach(review => {
        const reviewElement = document.createElement('div');
        reviewElement.classList.add('review-item');
        const reviewType = review.review_type || 'location';
        const locationName = review.location_name || review.subject || 'Unknown location';
        const guideName = review.guide_name || 'Unknown guide';
        reviewElement.innerHTML = `
            <div class="review-item-header">
                <div class="review-item-title">${locationName}</div>
            </div>
            <div style="font-size: 0.8rem; color: #666; margin-bottom: 4px;">Type: ${reviewType.toUpperCase()} · Guide: ${guideName}</div>
            <div class="review-item-rating" style="color: #fcc419; margin: 4px 0;">${'★'.repeat(review.rating)}</div>
            <div class="review-item-text" style="margin-bottom: 5px;">${review.comment}</div>
            <small style="color: #999;">Submitted on ${formatReviewDate(review.created_at)}</small>
        `;
        reviewsList.appendChild(reviewElement);
    });
}

function showProfileMessage(message, type, element) {
    if (!element) return;
    element.textContent = message;
    element.className = ''; // Reset classes
    element.classList.add(type);
}