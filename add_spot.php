<?php
/**
 * Admin: add a new tourist spot (destination). Session admin required.
 */
session_start();
require_once 'dbconnect.php';

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: signinTouristAdmin.html');
    exit;
}
$adminName = $_SESSION['username'] ?? 'Admin';

$message = '';
$error   = '';
$philippinesBounds = [
    'minLat' => 4.5,
    'maxLat' => 21.5,
    'minLng' => 116.0,
    'maxLng' => 127.0,
];
$defaultLatitude = isset($_POST['latitude']) && is_numeric($_POST['latitude']) ? (float) $_POST['latitude'] : 10.3156992;
$defaultLongitude = isset($_POST['longitude']) && is_numeric($_POST['longitude']) ? (float) $_POST['longitude'] : 123.8854366;
$defaultAddress = trim($_POST['address'] ?? '');
$defaultFacilitiesServices = trim($_POST['facilities_services'] ?? '');
$defaultContactInformation = trim($_POST['contact_information'] ?? '');
$defaultCategorization = trim($_POST['categorization'] ?? '');
$defaultIsMostVisited = !empty($_POST['is_most_visited']) ? 1 : 0;

// Ensure destinations has the columns needed by the tourist cards and navigation map.
$requiredColumns = [
    'address' => "ALTER TABLE destinations ADD COLUMN address VARCHAR(255) DEFAULT NULL",
    'image' => "ALTER TABLE destinations ADD COLUMN image VARCHAR(255) DEFAULT NULL",
    'rating' => "ALTER TABLE destinations ADD COLUMN rating DECIMAL(2,1) DEFAULT 4.5",
    'review_count' => "ALTER TABLE destinations ADD COLUMN review_count INT DEFAULT 0",
    'price' => "ALTER TABLE destinations ADD COLUMN price VARCHAR(30) DEFAULT NULL",
    'latitude' => "ALTER TABLE destinations ADD COLUMN latitude DECIMAL(10,7) DEFAULT NULL",
    'longitude' => "ALTER TABLE destinations ADD COLUMN longitude DECIMAL(10,7) DEFAULT NULL",
    'facilities_services' => "ALTER TABLE destinations ADD COLUMN facilities_services TEXT DEFAULT NULL",
    'contact_information' => "ALTER TABLE destinations ADD COLUMN contact_information VARCHAR(255) DEFAULT NULL",
    'categorization' => "ALTER TABLE destinations ADD COLUMN categorization VARCHAR(100) DEFAULT NULL",
    'is_most_visited' => "ALTER TABLE destinations ADD COLUMN is_most_visited TINYINT(1) NOT NULL DEFAULT 0",
    'is_available' => "ALTER TABLE destinations ADD COLUMN is_available TINYINT(1) NOT NULL DEFAULT 1",
];
foreach ($requiredColumns as $col => $sql) {
    $c = $mysqli->query("SHOW COLUMNS FROM destinations LIKE '$col'");
    if (!$c || $c->num_rows === 0) {
        $mysqli->query($sql);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $rating = isset($_POST['rating']) ? (float) $_POST['rating'] : 4.5;
    $review_count = isset($_POST['review_count']) ? (int) $_POST['review_count'] : 0;
    $price = trim($_POST['price'] ?? '');
    $latitude = isset($_POST['latitude']) ? trim($_POST['latitude']) : '';
    $longitude = isset($_POST['longitude']) ? trim($_POST['longitude']) : '';
    $facilitiesServices = trim($_POST['facilities_services'] ?? '');
    $contactInformation = trim($_POST['contact_information'] ?? '');
    $categorization = trim($_POST['categorization'] ?? '');
    $isMostVisited = !empty($_POST['is_most_visited']) ? 1 : 0;

    if (empty($name)) {
        $error = 'Please enter the spot name.';
    } elseif ($latitude === '' || $longitude === '') {
        $error = 'Please enter the spot latitude and longitude so tourists can route to it.';
    } elseif (!is_numeric($latitude) || !is_numeric($longitude)) {
        $error = 'Latitude and longitude must be valid numbers.';
    } elseif ((float) $latitude < -90 || (float) $latitude > 90 || (float) $longitude < -180 || (float) $longitude > 180) {
        $error = 'Latitude or longitude is outside the valid map range.';
    } elseif (
        (float) $latitude < $philippinesBounds['minLat']
        || (float) $latitude > $philippinesBounds['maxLat']
        || (float) $longitude < $philippinesBounds['minLng']
        || (float) $longitude > $philippinesBounds['maxLng']
    ) {
        $error = 'Please pin the location inside the Philippines so tourists get the correct route.';
    } else {
        $latitude = (float) $latitude;
        $longitude = (float) $longitude;
        $imagePath = null;
        if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $dir = __DIR__ . '/photos/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION) ?: 'jpg';
            $filename = 'spot_' . time() . '_' . preg_replace('/[^a-z0-9]/i', '', $name) . '.' . $ext;
            $target = $dir . $filename;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                $imagePath = 'photos/' . $filename;
            }
        }
        if (isset($_POST['image_url']) && trim($_POST['image_url']) !== '') {
            $imagePath = trim($_POST['image_url']);
        }

        $stmt = $mysqli->prepare("INSERT INTO destinations (name, description, address, image, rating, review_count, price, latitude, longitude, facilities_services, contact_information, categorization, is_most_visited, is_available) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
        if ($stmt) {
            $stmt->bind_param('ssssdisddsssi', $name, $description, $address, $imagePath, $rating, $review_count, $price, $latitude, $longitude, $facilitiesServices, $contactInformation, $categorization, $isMostVisited);
            if ($stmt->execute()) {
                $stmt->close();
                $message = "Tourist spot \"{$name}\" added. It will appear on the landing page and in navigation routes.";
            } else {
                $stmt->close();
                $stmt2 = $mysqli->prepare("INSERT INTO destinations (name, description, address, facilities_services, contact_information, categorization, is_most_visited, is_available) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
                if ($stmt2) {
                    $stmt2->bind_param('ssssssi', $name, $description, $address, $facilitiesServices, $contactInformation, $categorization, $isMostVisited);
                    if ($stmt2->execute()) {
                        $stmt2->close();
                        $message = "Tourist spot \"{$name}\" added, but map coordinates could not be saved. Add coordinates to make it routable.";
                    } else {
                        $stmt2->close();
                        $error = 'Could not save: ' . $mysqli->error;
                    }
                } else {
                    $error = 'Could not save: ' . $mysqli->error;
                }
            }
        } else {
            $error = 'Database error. Try running add_spot again to add required columns.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Tourist Spot | GuideMate</title>
    <script src="https://kit.fontawesome.com/ed5caa5a8f.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="adminDashboard.css">
    <link rel="stylesheet" href="add_admin.css">
</head>
<body>
    <nav class="glass-nav">
        <div class="logo">GuideMate Admin</div>
        <div class="nav-links">
            <a href="adminDashboard.php#real-time-map" style="color:inherit;text-decoration:none;">REAL-TIME MAP</a>
            <a href="adminDashboard.php#fleet-status" style="color:inherit;text-decoration:none;">FLEET STATUS</a>
            <a href="adminDashboard.php#revenue" style="color:inherit;text-decoration:none;">REVENUE</a>
            <a href="add_admin.php" style="color:inherit;text-decoration:none;">ADD ADMIN</a>
            <span class="nav-active">ADD SPOT</span>
            <a href="logout.php" class="logout-link">LOGOUT</a>
        </div>
        <div class="user-id"><i data-feather="shield"></i> Super_Admin: <b><?= htmlspecialchars($adminName) ?></b></div>
    </nav>
    <header class="dashboard-header">
        <p>TOURIST SPOTS</p>
        <h1>Add new tourist spot</h1>
    </header>
    <section class="add-admin-section">
        <div class="panel add-admin-panel">
            <div class="panel-head">
                <h3><i data-feather="map-pin"></i> New tourist spot</h3>
                <span class="panel-subtitle">The spot will appear on the landing page and in tourist navigation.</span>
            </div>
            <?php if ($message): ?>
                <div class="alert-msg ok"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert-msg err"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="POST" action="add_spot.php" enctype="multipart/form-data" class="add-admin-form">
                <div class="form-row">
                    <label for="name"><i data-feather="type"></i> Name</label>
                    <input type="text" id="name" name="name" placeholder="e.g. Kawasan Falls" required value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>">
                </div>
                <div class="form-row">
                    <label for="description"><i data-feather="file-text"></i> Description</label>
                    <textarea id="description" name="description" rows="3" placeholder="Short description for the card"><?= isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '' ?></textarea>
                </div>
                <div class="form-row">
                    <label for="address"><i data-feather="navigation"></i> Search exact place or address</label>
                    <div class="location-search-box">
                        <input type="text" id="address" name="address" placeholder="Search a place in the Philippines" value="<?= htmlspecialchars($defaultAddress) ?>" autocomplete="off">
                        <button type="button" id="searchPlaceBtn" class="cmd-btn btn-search-location">Find on map</button>
                    </div>
                    <div id="placeSearchStatus" class="location-search-status" aria-live="polite"></div>
                    <div id="placeSearchResults" class="location-search-results" style="display:none;"></div>
                </div>
                <div class="form-row">
                    <label for="image"><i data-feather="image"></i> Image (upload)</label>
                    <input type="file" id="image" name="image" accept="image/*">
                </div>
                <div class="form-row">
                    <label for="image_url">Or image URL</label>
                    <input type="text" id="image_url" name="image_url" placeholder="photos/spot.jpg">
                </div>
                <div class="form-row two-cols">
                    <div>
                        <label for="rating">Rating (1–5)</label>
                        <input type="number" id="rating" name="rating" min="1" max="5" step="0.1" value="<?= isset($_POST['rating']) ? htmlspecialchars($_POST['rating']) : '4.5' ?>">
                    </div>
                    <div>
                        <label for="review_count">Review count</label>
                        <input type="number" id="review_count" name="review_count" min="0" value="<?= isset($_POST['review_count']) ? (int)$_POST['review_count'] : '0' ?>">
                    </div>
                </div>
                <div class="form-row">
                    <label for="price">Price (e.g. 2,500)</label>
                    <input type="text" id="price" name="price" placeholder="2,500" value="<?= isset($_POST['price']) ? htmlspecialchars($_POST['price']) : '' ?>">
                </div>
                <div class="form-row">
                    <label for="facilities_services"><i data-feather="tool"></i> Facilities and services available</label>
                    <textarea id="facilities_services" name="facilities_services" rows="3" placeholder="Parking, restrooms, guides, food stalls, cottages, shuttle service"><?= htmlspecialchars($defaultFacilitiesServices) ?></textarea>
                </div>
                <div class="form-row">
                    <label for="contact_information"><i data-feather="phone"></i> Contact information (if applicable)</label>
                    <input type="text" id="contact_information" name="contact_information" placeholder="Phone number, email, Facebook page, or office contact" value="<?= htmlspecialchars($defaultContactInformation) ?>">
                </div>
                <div class="form-row two-cols">
                    <div>
                        <label for="categorization"><i data-feather="tag"></i> Categorization</label>
                        <select id="categorization" name="categorization">
                            <option value="">Select category</option>
                            <option value="Beach" <?= $defaultCategorization === 'Beach' ? 'selected' : '' ?>>Beach</option>
                            <option value="Park" <?= $defaultCategorization === 'Park' ? 'selected' : '' ?>>Park</option>
                            <option value="Museum" <?= $defaultCategorization === 'Museum' ? 'selected' : '' ?>>Museum</option>
                            <option value="Historical Site" <?= $defaultCategorization === 'Historical Site' ? 'selected' : '' ?>>Historical Site</option>
                            <option value="Mountain" <?= $defaultCategorization === 'Mountain' ? 'selected' : '' ?>>Mountain</option>
                            <option value="Falls" <?= $defaultCategorization === 'Falls' ? 'selected' : '' ?>>Falls</option>
                            <option value="Island" <?= $defaultCategorization === 'Island' ? 'selected' : '' ?>>Island</option>
                            <option value="Temple / Church" <?= $defaultCategorization === 'Temple / Church' ? 'selected' : '' ?>>Temple / Church</option>
                            <option value="Adventure Spot" <?= $defaultCategorization === 'Adventure Spot' ? 'selected' : '' ?>>Adventure Spot</option>
                            <option value="Other" <?= $defaultCategorization === 'Other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                    <div>
                        <label for="is_most_visited"><i data-feather="star"></i> Most Visited Destinations Section</label>
                        <select id="is_most_visited" name="is_most_visited">
                            <option value="0" <?= !$defaultIsMostVisited ? 'selected' : '' ?>>No</option>
                            <option value="1" <?= $defaultIsMostVisited ? 'selected' : '' ?>>Yes</option>
                        </select>
                    </div>
                </div>
                <div class="form-row two-cols">
                    <div>
                        <label for="latitude">Latitude</label>
                        <input type="number" id="latitude" name="latitude" placeholder="10.3156992" step="any" required value="<?= isset($_POST['latitude']) ? htmlspecialchars($_POST['latitude']) : '' ?>">
                    </div>
                    <div>
                        <label for="longitude">Longitude</label>
                        <input type="number" id="longitude" name="longitude" placeholder="123.8854366" step="any" required value="<?= isset($_POST['longitude']) ? htmlspecialchars($_POST['longitude']) : '' ?>">
                    </div>
                </div>
                <div class="form-row">
                    <label for="spotLocationMap"><i data-feather="crosshair"></i> Pin exact spot location</label>
                    <p class="location-picker-help">Search for the place first, then click the map or drag the marker to fine-tune the exact tourist spot. Only locations inside the Philippines are allowed.</p>
                    <div id="spotLocationMap" class="location-picker-map" aria-label="Spot location map"></div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="cmd-btn btn-primary"><i data-feather="plus"></i> Add spot</button>
                    <a href="adminDashboard.php" class="cmd-btn btn-back"><i data-feather="arrow-left"></i> Back to dashboard</a>
                </div>
            </form>
        </div>
    </section>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="logout_modal.js"></script>
    <script>feather.replace();</script>
    <script>
    (function() {
        var logoutLink = document.querySelector('a.logout-link');
        if (logoutLink && typeof showLogoutConfirm === 'function') {
            logoutLink.addEventListener('click', function(e) {
                e.preventDefault();
                showLogoutConfirm(function() { window.location.href = 'logout.php'; });
            });
        }
    })();

    (function() {
        var addressInput = document.getElementById('address');
        var searchPlaceBtn = document.getElementById('searchPlaceBtn');
        var searchResults = document.getElementById('placeSearchResults');
        var searchStatus = document.getElementById('placeSearchStatus');
        var latitudeInput = document.getElementById('latitude');
        var longitudeInput = document.getElementById('longitude');
        var mapElement = document.getElementById('spotLocationMap');
        if (!latitudeInput || !longitudeInput || !mapElement || typeof L === 'undefined') {
            return;
        }

        var philippinesBounds = L.latLngBounds(
            [<?= json_encode($philippinesBounds['minLat']) ?>, <?= json_encode($philippinesBounds['minLng']) ?>],
            [<?= json_encode($philippinesBounds['maxLat']) ?>, <?= json_encode($philippinesBounds['maxLng']) ?>]
        );
        var initialLat = parseFloat(latitudeInput.value || <?= json_encode($defaultLatitude) ?>);
        var initialLng = parseFloat(longitudeInput.value || <?= json_encode($defaultLongitude) ?>);
        var initialLatLng = philippinesBounds.contains([initialLat, initialLng])
            ? [initialLat, initialLng]
            : [<?= json_encode($defaultLatitude) ?>, <?= json_encode($defaultLongitude) ?>];

        var map = L.map('spotLocationMap', {
            center: initialLatLng,
            zoom: 11,
            minZoom: 5,
            maxBounds: philippinesBounds,
            maxBoundsViscosity: 1.0
        });

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        var marker = L.marker(initialLatLng, { draggable: true }).addTo(map);

        function updateInputs(latlng) {
            latitudeInput.value = Number(latlng.lat).toFixed(7);
            longitudeInput.value = Number(latlng.lng).toFixed(7);
        }

        function setSearchStatus(message, isError) {
            if (!searchStatus) {
                return;
            }
            searchStatus.textContent = message || '';
            searchStatus.classList.toggle('is-error', !!isError);
        }

        function hideResults() {
            if (!searchResults) {
                return;
            }
            searchResults.innerHTML = '';
            searchResults.style.display = 'none';
        }

        function clampToPhilippines(latlng) {
            var lat = Math.min(Math.max(latlng.lat, philippinesBounds.getSouth()), philippinesBounds.getNorth());
            var lng = Math.min(Math.max(latlng.lng, philippinesBounds.getWest()), philippinesBounds.getEast());
            return L.latLng(lat, lng);
        }

        function moveMarker(latlng, keepMapCentered) {
            var bounded = clampToPhilippines(latlng);
            marker.setLatLng(bounded);
            updateInputs(bounded);
            if (keepMapCentered) {
                map.setView(bounded, Math.max(map.getZoom(), 16));
            }
        }

        updateInputs(marker.getLatLng());

        map.on('click', function(event) {
            moveMarker(event.latlng, false);
            setSearchStatus('Marker updated. You can still drag it for a more exact pin.', false);
        });

        marker.on('dragend', function() {
            moveMarker(marker.getLatLng(), false);
            setSearchStatus('Marker adjusted. The saved route will use this exact pin.', false);
        });

        function syncFromInputs() {
            var lat = parseFloat(latitudeInput.value);
            var lng = parseFloat(longitudeInput.value);
            if (Number.isNaN(lat) || Number.isNaN(lng)) {
                return;
            }
            moveMarker(L.latLng(lat, lng), true);
        }

        latitudeInput.addEventListener('change', syncFromInputs);
        longitudeInput.addEventListener('change', syncFromInputs);

        async function searchPlace() {
            if (!addressInput) {
                return;
            }

            var query = addressInput.value.trim();
            if (!query) {
                hideResults();
                setSearchStatus('Enter a place name or address to locate the spot.', true);
                return;
            }

            setSearchStatus('Searching for the exact place...', false);
            hideResults();

            try {
                var params = new URLSearchParams({
                    q: query,
                    format: 'jsonv2',
                    addressdetails: '1',
                    limit: '5',
                    countrycodes: 'ph',
                    bounded: '1',
                    viewbox: '116.0,21.5,127.0,4.5'
                });
                var response = await fetch('https://nominatim.openstreetmap.org/search?' + params.toString(), {
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) {
                    throw new Error('Geocoder request failed with status ' + response.status);
                }

                var results = await response.json();
                results = Array.isArray(results) ? results.filter(function(result) {
                    var lat = parseFloat(result.lat);
                    var lng = parseFloat(result.lon);
                    return !Number.isNaN(lat) && !Number.isNaN(lng) && philippinesBounds.contains([lat, lng]);
                }) : [];

                if (!results.length) {
                    setSearchStatus('No matching place found in the Philippines. Try a more specific address.', true);
                    return;
                }

                if (!searchResults) {
                    var firstResult = results[0];
                    addressInput.value = firstResult.display_name || query;
                    moveMarker(L.latLng(parseFloat(firstResult.lat), parseFloat(firstResult.lon)), true);
                    setSearchStatus('Location found. Fine-tune the marker if needed before saving.', false);
                    return;
                }

                searchResults.innerHTML = '';
                results.forEach(function(result, index) {
                    var item = document.createElement('button');
                    item.type = 'button';
                    item.className = 'location-result-item';
                    item.textContent = result.display_name;
                    item.addEventListener('click', function() {
                        var lat = parseFloat(result.lat);
                        var lng = parseFloat(result.lon);
                        addressInput.value = result.display_name || query;
                        moveMarker(L.latLng(lat, lng), true);
                        setSearchStatus('Location found. Fine-tune the marker if needed before saving.', false);
                        hideResults();
                    });
                    searchResults.appendChild(item);

                    if (index === 0) {
                        addressInput.value = result.display_name || query;
                        moveMarker(L.latLng(parseFloat(result.lat), parseFloat(result.lon)), true);
                    }
                });

                searchResults.style.display = 'block';
                setSearchStatus('Choose the best match below or drag the marker to the exact spot.', false);
            } catch (error) {
                console.error('Place search failed:', error);
                setSearchStatus('Could not search the place right now. You can still pin the spot manually on the map.', true);
            }
        }

        if (searchPlaceBtn) {
            searchPlaceBtn.addEventListener('click', function() {
                searchPlace();
            });
        }

        if (addressInput) {
            addressInput.addEventListener('keydown', function(event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    searchPlace();
                }
            });

            addressInput.addEventListener('input', function() {
                if (!addressInput.value.trim()) {
                    hideResults();
                    setSearchStatus('', false);
                }
            });
        }

        document.addEventListener('click', function(event) {
            if (!searchResults || !addressInput || !searchPlaceBtn) {
                return;
            }
            if (!searchResults.contains(event.target) && event.target !== addressInput && event.target !== searchPlaceBtn) {
                hideResults();
            }
        });

        setTimeout(function() {
            map.invalidateSize();
        }, 150);
    })();
    </script>
</body>
</html>
