document.addEventListener("DOMContentLoaded", async () => {
    const navName = document.getElementById("navProfileName");
    const navAvatar = document.querySelector(".nav-avatar");
    const role = localStorage.getItem("role") || "";
    const userId = localStorage.getItem("userId") || "";
    if (navName) {
        const scopedFirstName = (role && userId) ? localStorage.getItem(`firstName:${role}:${userId}`) : "";
        const scopedLastName = (role && userId) ? localStorage.getItem(`lastName:${role}:${userId}`) : "";
        const scopedFullName = (role && userId) ? localStorage.getItem(`profileName:${role}:${userId}`) : "";
        const displayName = scopedFullName || [scopedFirstName, scopedLastName].filter(Boolean).join(" ").trim() || localStorage.getItem("fullName") || "Guest Traveler";
        navName.textContent = displayName;
    }
    if (navAvatar) {
        const scopedProfileImage = (role && userId) ? localStorage.getItem(`profileImage:${role}:${userId}`) : "";
        if (scopedProfileImage || localStorage.getItem("profileImage")) {
            navAvatar.src = scopedProfileImage || localStorage.getItem("profileImage");
        }
    }

    try {
        const response = await fetch("get_user.php", { credentials: "same-origin" });
        if (response.ok) {
            const data = await response.json();
            if (data && data.success && data.role && data.user_id) {
                const sessionRole = String(data.role);
                const sessionUserId = String(data.user_id);
                const sessionFirstName = String(data.first_name || "");
                const sessionLastName = String(data.last_name || "");
                const sessionFullName = String(data.full_name || "").trim() || "Guest Traveler";
                const sessionProfileImage = String(data.profile_image || "");

                localStorage.setItem("role", sessionRole);
                localStorage.setItem("userId", sessionUserId);
                localStorage.setItem("firstName", sessionFirstName);
                localStorage.setItem("lastName", sessionLastName);
                localStorage.setItem("fullName", sessionFullName);
                localStorage.setItem(`firstName:${sessionRole}:${sessionUserId}`, sessionFirstName);
                localStorage.setItem(`lastName:${sessionRole}:${sessionUserId}`, sessionLastName);
                localStorage.setItem(`profileName:${sessionRole}:${sessionUserId}`, sessionFullName);

                if (navName) {
                    navName.textContent = sessionFullName;
                }
                if (sessionProfileImage) {
                    localStorage.setItem(`profileImage:${sessionRole}:${sessionUserId}`, sessionProfileImage);
                    localStorage.setItem("profileImage", sessionProfileImage);
                    if (navAvatar) {
                        navAvatar.src = sessionProfileImage;
                    }
                }
            }
        }
    } catch (_) {}

    const map = L.map("map").setView([10.2936, 123.9019], 13);
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        attribution: "© OpenStreetMap"
    }).addTo(map);

    const routingControl = L.Routing.control({
        waypoints: [],
        lineOptions: {
            styles: [{ color: "#2d76f9", weight: 6 }]
        },
        routeWhileDragging: false,
        addWaypoints: false,
        show: false
    }).addTo(map);

    const searchInput = document.getElementById("searchInput");
    const searchBtn = document.getElementById("searchBtn");
    const historyDropdown = document.getElementById("searchHistory");
    const quickNavChips = document.getElementById("quickNavChips");
    const nearbyAttractionsList = document.getElementById("nearbyAttractionsList");

    const isFile = typeof window !== "undefined" && window.location && window.location.protocol === "file:";
    const spotsEndpoint = isFile ? "http://localhost/guidemate1/get_spots.php" : "get_spots.php";

    let spots = [];
    let history = JSON.parse(localStorage.getItem("searchHistory")) || [];
    const markers = {};
    let currentLocationMarker = null;
    let currentCoords = null;

    const escapeHtml = (value) => String(value ?? "").replace(/[&<>"']/g, (char) => ({
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        "\"": "&quot;",
        "'": "&#39;"
    }[char]));

    const clearMarkers = () => {
        Object.keys(markers).forEach((key) => {
            map.removeLayer(markers[key]);
            delete markers[key];
        });
    };

    const hasCoordinates = (spot) => Number.isFinite(Number(spot.latitude)) && Number.isFinite(Number(spot.longitude));
    const getMarkerKey = (spot) => spot.destinationId
        ? `destination-${spot.destinationId}`
        : `${String(spot.name || "").toLowerCase()}-${spot.latitude}-${spot.longitude}`;

    const formatDistance = (distanceKm) => {
        if (distanceKm < 1) {
            return `${Math.round(distanceKm * 1000)} m away`;
        }
        return `${distanceKm.toFixed(1)} km away`;
    };

    const calculateDistanceKm = (lat1, lon1, lat2, lon2) => {
        const toRadians = (value) => value * (Math.PI / 180);
        const earthRadiusKm = 6371;
        const dLat = toRadians(lat2 - lat1);
        const dLon = toRadians(lon2 - lon1);
        const a = Math.sin(dLat / 2) * Math.sin(dLat / 2)
            + Math.cos(toRadians(lat1)) * Math.cos(toRadians(lat2))
            * Math.sin(dLon / 2) * Math.sin(dLon / 2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return earthRadiusKm * c;
    };

    const buildPopupContent = (spot) => `
        <div style="text-align: center; width: 170px;">
            <b style="font-size: 14px;">${escapeHtml(spot.name)}</b><br>
            <img src="${escapeHtml(spot.image || "photos/default.jpg")}"
                 alt="${escapeHtml(spot.name)}"
                 onerror="this.src='https://via.placeholder.com/150?text=No+Image'"
                 style="width: 100%; border-radius: 8px; margin-top: 5px;">
            ${spot.address ? `<div style="font-size: 12px; color: #5a6a85; margin-top: 6px;">${escapeHtml(spot.address)}</div>` : ""}
            <div style="font-size: 12px; color: #5a6a85; margin-top: 6px;">Search or tap quick nav to route here.</div>
        </div>
    `;

    const openSpot = (spot) => {
        map.flyTo(spot.coords, 15);
        const marker = markers[spot.markerKey];
        if (marker) {
            setTimeout(() => marker.openPopup(), 300);
        }
    };

    const renderNearbyAttractions = () => {
        if (!nearbyAttractionsList) {
            return;
        }

        if (!spots.length) {
            nearbyAttractionsList.innerHTML = '<div class="nearby-empty-state">No attractions with saved coordinates are available yet.</div>';
            return;
        }

        if (!currentCoords) {
            nearbyAttractionsList.innerHTML = '<div class="nearby-empty-state">Tap My Current Location to view the nearest attractions.</div>';
            return;
        }

        const rankedSpots = spots
            .map((spot) => ({
                ...spot,
                distanceKm: calculateDistanceKm(
                    currentCoords[0],
                    currentCoords[1],
                    spot.coords[0],
                    spot.coords[1]
                )
            }))
            .sort((a, b) => a.distanceKm - b.distanceKm)
            .slice(0, 6);

        nearbyAttractionsList.innerHTML = rankedSpots.map((spot) => `
            <article class="nearby-attraction-card">
                <h4>${escapeHtml(spot.name)}</h4>
                <p class="nearby-attraction-meta">${formatDistance(spot.distanceKm)}</p>
                <p class="nearby-attraction-address">${escapeHtml(spot.address || "Address not available")}</p>
                <p class="nearby-attraction-description">${escapeHtml(spot.description || "Explore this nearby attraction on the map.")}</p>
                <div class="nearby-attraction-actions">
                    <button type="button" class="nearby-attraction-btn secondary" data-action="view" data-marker-key="${escapeHtml(spot.markerKey)}">View</button>
                    <button type="button" class="nearby-attraction-btn primary" data-action="route" data-marker-key="${escapeHtml(spot.markerKey)}">Route</button>
                </div>
            </article>
        `).join("");

        nearbyAttractionsList.querySelectorAll(".nearby-attraction-btn").forEach((button) => {
            button.addEventListener("click", () => {
                const markerKey = button.getAttribute("data-marker-key");
                const selectedSpot = spots.find((spot) => spot.markerKey === markerKey);
                if (!selectedSpot) {
                    return;
                }
                if (button.getAttribute("data-action") === "route") {
                    selectSpot(selectedSpot, true);
                } else {
                    selectSpot(selectedSpot, false);
                }
            });
        });
    };

    const getMatchingSpots = (query) => {
        const lowerQuery = query.toLowerCase().trim();
        if (!lowerQuery) {
            return [];
        }

        return spots
            .filter((spot) => spot.name.toLowerCase().includes(lowerQuery))
            .slice(0, 6);
    };

    const renderDropdown = (items, mode) => {
        historyDropdown.innerHTML = "";

        if (!items.length) {
            historyDropdown.style.display = "none";
            return;
        }

        items.forEach((item) => {
            const row = document.createElement("div");
            row.className = "history-item";

            const icon = document.createElement("i");
            icon.className = mode === "history" ? "bx bx-history" : "bx bx-map";

            const label = document.createElement("span");
            label.textContent = mode === "history" ? item : item.name;

            row.appendChild(icon);
            row.appendChild(label);

            row.addEventListener("click", () => {
                if (mode === "history") {
                    performSearch(item);
                } else {
                    selectSpot(item, true);
                }
            });

            historyDropdown.appendChild(row);
        });

        historyDropdown.style.display = "block";
    };

    const renderHistory = () => {
        renderDropdown(history, "history");
    };

    const renderSuggestions = (query) => {
        const matches = getMatchingSpots(query);
        renderDropdown(matches, "suggestions");
    };

    const updateHistory = (name) => {
        history = [name, ...history.filter((item) => item !== name)].slice(0, 5);
        localStorage.setItem("searchHistory", JSON.stringify(history));
    };

    const selectSpot = (spot, shouldRoute) => {
        searchInput.value = spot.name;
        updateHistory(spot.name);
        openSpot(spot);
        historyDropdown.style.display = "none";

        if (shouldRoute) {
            window.getRouteTo(spot.coords[0], spot.coords[1]);
        }
    };

    const performSearch = (query = searchInput.value) => {
        if (!query.trim()) {
            return;
        }

        if (!spots.length) {
            alert("Spots are still loading. Please try again in a moment.");
            return;
        }

        const lowerQuery = query.toLowerCase().trim();
        const found = spots.find((spot) => spot.name.toLowerCase() === lowerQuery)
            || spots.find((spot) => spot.name.toLowerCase().includes(lowerQuery));

        if (found) {
            selectSpot(found, true);
        } else {
            alert("Location not found. Try searching the exact spot name.");
        }
    };

    const renderQuickNav = () => {
        quickNavChips.innerHTML = "";

        if (!spots.length) {
            const emptyState = document.createElement("span");
            emptyState.className = "quick-nav-empty";
            emptyState.textContent = "No routable spots available yet.";
            quickNavChips.appendChild(emptyState);
            return;
        }

        spots.slice(0, 8).forEach((spot) => {
            const chip = document.createElement("button");
            chip.type = "button";
            chip.className = "chip";
            chip.textContent = `📍 ${spot.name}`;
            chip.addEventListener("click", () => selectSpot(spot, true));
            quickNavChips.appendChild(chip);
        });
    };

    const loadSpots = async () => {
        try {
            const response = await fetch(spotsEndpoint, { credentials: "same-origin" });
            if (!response.ok) {
                throw new Error(`get_spots failed: ${response.status}`);
            }

            const data = await response.json();
            spots = Array.isArray(data)
                ? data
                    .filter(hasCoordinates)
                    .map((spot) => ({
                        ...spot,
                        markerKey: getMarkerKey(spot),
                        latitude: Number(spot.latitude),
                        longitude: Number(spot.longitude),
                        coords: [Number(spot.latitude), Number(spot.longitude)]
                    }))
                : [];

            clearMarkers();
            spots.forEach((spot) => {
                const marker = L.marker(spot.coords).addTo(map).bindPopup(buildPopupContent(spot));
                markers[spot.markerKey] = marker;
            });

            renderQuickNav();
            renderNearbyAttractions();
        } catch (error) {
            console.error("Failed to load navigation spots:", error);
            if (quickNavChips) {
                quickNavChips.innerHTML = '<span class="quick-nav-empty">Could not load spots right now.</span>';
            }
            if (nearbyAttractionsList) {
                nearbyAttractionsList.innerHTML = '<div class="nearby-empty-state">Could not load nearby attractions right now.</div>';
            }
        }
    };

    if (searchBtn) {
        searchBtn.addEventListener("click", () => performSearch());
    }

    if (searchInput) {
        searchInput.addEventListener("keypress", (e) => {
            if (e.key === "Enter") {
                performSearch();
            }
        });

        searchInput.addEventListener("focus", () => {
            if (searchInput.value.trim()) {
                renderSuggestions(searchInput.value);
            } else if (history.length > 0) {
                renderHistory();
            }
        });

        searchInput.addEventListener("input", () => {
            const query = searchInput.value.trim();
            if (query) {
                renderSuggestions(query);
            } else if (history.length > 0) {
                renderHistory();
            } else {
                historyDropdown.style.display = "none";
            }
        });
    }

    document.addEventListener("click", (e) => {
        const container = document.querySelector(".search-box-container");
        if (container && !container.contains(e.target)) {
            historyDropdown.style.display = "none";
        }
    });

    window.getLocation = function() {
        if (!navigator.geolocation) {
            alert("Geolocation is not supported on this device.");
            return;
        }

        navigator.geolocation.getCurrentPosition((pos) => {
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;
            currentCoords = [lat, lng];

            if (currentLocationMarker) {
                map.removeLayer(currentLocationMarker);
            }

            currentLocationMarker = L.marker([lat, lng]).addTo(map).bindPopup("You are here");
            map.flyTo([lat, lng], 16);
            currentLocationMarker.openPopup();
            renderNearbyAttractions();
        }, () => alert("Please enable GPS to find your location."));
    };

    window.getRouteTo = function(destLat, destLng) {
        if (!navigator.geolocation) {
            alert("Geolocation is not supported on this device.");
            return;
        }

        navigator.geolocation.getCurrentPosition((pos) => {
            const destination = [Number(destLat), Number(destLng)];
            routingControl.setWaypoints([
                L.latLng(pos.coords.latitude, pos.coords.longitude),
                L.latLng(destination[0], destination[1])
            ]);

            map.flyTo(destination, 14);

            setTimeout(() => {
                const targetSpot = spots.find((spot) =>
                    Math.abs(spot.coords[0] - destination[0]) < 0.000001
                    && Math.abs(spot.coords[1] - destination[1]) < 0.000001
                );

                if (targetSpot && markers[targetSpot.markerKey]) {
                    markers[targetSpot.markerKey].openPopup();
                }
            }, 700);
        }, () => alert("Please enable GPS for routing."));
    };

    const logoutBtn = document.getElementById("logoutBtn");
    if (logoutBtn) {
        logoutBtn.addEventListener("click", (e) => {
            e.preventDefault();
            if (typeof showLogoutConfirm === "function") {
                showLogoutConfirm(function() {
                    localStorage.clear();
                    window.location.href = "logout.php";
                }, "Sign out? Yes or No");
            } else {
                localStorage.clear();
                window.location.href = "logout.php";
            }
        });
    }

    await loadSpots();
});