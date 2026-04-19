<?php
session_start();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: landingpage.html');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cebu Admin Command | AdventureSync</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
    <link rel="stylesheet" href="adminDashboard.css">
</head>
<body>

    <nav class="glass-nav">
        <div class="logo">GuideMate Admin </div>
        <div class="nav-links">
            <span class="active">REAL-TIME MAP</span>
            <a href="add_admin.php" style="color:inherit;text-decoration:none;font-size:0.9rem;" class="nav-link">ADD ADMIN</a>
            <a href="add_spot.php" style="color:inherit;text-decoration:none;font-size:0.9rem;" class="nav-link">ADD SPOT</a>
            <a href="logout.php" class="logout-link">LOGOUT</a>
        </div>
        <div class="user-id"><i data-feather="shield"></i> Admin Panel</div>
    </nav>

    <header class="dashboard-header">
        <p id="dashboardCurrentDate"><?= strtoupper(date('F j, Y')) ?></p>
        <h1>Admin Dashboard</h1>
    </header>

    <div class="stats-row" id="statsRow">
        <div class="stat-card">
            <label>Total users</label>
            <div class="value" id="statTotalUsers">—</div>
        </div>
        <div class="stat-card">
            <label>Total guides</label>
            <div class="value blue-glow" id="statTotalGuides">—</div>
        </div>
        <div class="stat-card">
            <label>Total destinations</label>
            <div class="value" id="statTotalDestinations">—</div>
        </div>
    </div>

    <!-- User management panel -->
    <section class="pending-guides-section">
        <h2 class="panel-section-title">User management panel</h2>
    </section>
    <div class="guide-management-grid guide-management-grid--stacked">
        <section class="pending-guides-section guide-management-grid-item">
            <div class="panel pending-panel">
                <div class="panel-head">
                    <h3>All tour guides</h3>
                    <span class="pending-subtitle">Complete list of all registered tour guides and their status (Pending, Active on landing page, or Suspended).</span>
                </div>
                <div id="allGuidesContainer">
                    <p class="pending-loading" id="allGuidesLoading">Loading…</p>
                    <table class="pending-table" id="allGuidesTable" style="display: none;">
                        <thead>
                            <tr>
                                <th>Guide</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="allGuidesBody"></tbody>
                    </table>
                    <p class="pending-empty" id="allGuidesEmpty" style="display: none;">No tour guides registered yet.</p>
                </div>
            </div>
        </section>
        <div class="guide-management-side-stack">
            <section class="pending-guides-section guide-management-grid-item">
                <div class="panel pending-panel">
                    <div class="panel-head">
                        <h3>Manage tour guides – Add to landing page</h3>
                        <span class="pending-subtitle">Newly registered guides appear below. Approve each one to add them to the landing page so tourists can search and book them.</span>
                    </div>
                    <div id="pendingGuidesContainer">
                        <p class="pending-loading" id="pendingLoading">Loading…</p>
                        <table class="pending-table" id="pendingGuidesTable" style="display: none;">
                            <thead>
                                <tr>
                                    <th>Guide</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="pendingGuidesBody"></tbody>
                        </table>
                        <p class="pending-empty" id="pendingEmpty" style="display: none;">No pending guides. When a guide registers, they will appear here for you to add to the landing page.</p>
                    </div>
                </div>
            </section>

            <div class="guide-management-side-stack">
                <section class="pending-guides-section guide-management-grid-item">
                    <div class="panel pending-panel">
                        <div class="panel-head">
                            <h3>Pending guide bookings</h3>
                            <span class="pending-subtitle">Tourist booking requests appear here with their requested meet time. Only the assigned guide can accept a request.</span>
                        </div>
                        <div id="pendingBookingsContainer">
                            <p class="pending-loading" id="pendingBookingsLoading">Loading…</p>
                            <table class="pending-table" id="pendingBookingsTable" style="display: none;">
                                <thead>
                                    <tr>
                                        <th>Tourist</th>
                                        <th>Guide</th>
                                        <th>Requested</th>
                                        <th>Meet time</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="pendingBookingsBody"></tbody>
                            </table>
                            <p class="pending-empty" id="pendingBookingsEmpty" style="display: none;">No pending booking requests.</p>
                        </div>
                    </div>
                </section>

                <section class="pending-guides-section guide-management-grid-item">
                    <div class="panel pending-panel">
                        <div class="panel-head">
                            <h3>Approved guide bookings</h3>
                            <span class="pending-subtitle">Approved bookings show the confirmed meet time. After the booked day is finished, click the button below to make that guide available for new tourists again.</span>
                        </div>
                        <div id="approvedBookingsContainer">
                            <p class="pending-loading" id="approvedBookingsLoading">Loading…</p>
                            <table class="pending-table" id="approvedBookingsTable" style="display: none;">
                                <thead>
                                    <tr>
                                        <th>Tourist</th>
                                        <th>Guide</th>
                                        <th>Approved</th>
                                        <th>Meet time</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="approvedBookingsBody"></tbody>
                            </table>
                            <p class="pending-empty" id="approvedBookingsEmpty" style="display: none;">No approved guide bookings right now.</p>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>

    <section class="pending-guides-section">
        <div class="panel pending-panel">
            <div class="panel-head">
                <h3>Manage tour guides – On landing page</h3>
                <span class="pending-subtitle">Remove a guide from the landing page for 1–3 days if they did not appear on the exact time. They will automatically reappear after that, or you can re-add them earlier from the section below.</span>
            </div>
            <div id="activeGuidesContainer">
                <p class="pending-loading" id="activeLoading">Loading…</p>
                <table class="pending-table" id="activeGuidesTable" style="display: none;">
                    <thead>
                        <tr>
                            <th>Guide</th>
                            <th>Email</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="activeGuidesBody"></tbody>
                </table>
                <p class="pending-empty" id="activeEmpty" style="display: none;">No guides on the landing page right now. Add pending guides above first.</p>
            </div>
        </div>
    </section>

    <section class="pending-guides-section">
        <div class="panel pending-panel">
            <div class="panel-head">
                <h3>Manage tour guides – Suspended</h3>
                <span class="pending-subtitle">Guides removed from the landing page (e.g. did not appear on exact time). They will be visible to tourists again after the return date, or you can re-add them now.</span>
            </div>
            <div id="suspendedGuidesContainer">
                <p class="pending-loading" id="suspendedLoading">Loading…</p>
                <table class="pending-table" id="suspendedGuidesTable" style="display: none;">
                    <thead>
                        <tr>
                            <th>Guide</th>
                            <th>Return to landing page</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="suspendedGuidesBody"></tbody>
                </table>
                <p class="pending-empty" id="suspendedEmpty" style="display: none;">No suspended guides.</p>
            </div>
        </div>
    </section>

    <!-- Destination management panel -->
    <section class="pending-guides-section">
        <h2 class="panel-section-title">Destination management panel</h2>
    </section>
    <section class="pending-guides-section">
        <div class="panel pending-panel">
            <div class="panel-head">
                <h3>Manage tourist spots</h3>
                <span class="pending-subtitle">Every tourist spot is listed below. Change the price, mark a spot unavailable so it is hidden from the landing page, or switch it back to available at any time.</span>
            </div>
            <div id="spotsPriceContainer">
                <p class="pending-loading" id="spotsPriceLoading">Loading…</p>
                <div id="spotsBulkActions" style="display: none; margin-bottom: 0.5rem;">
                    <button type="button" class="delete-spots-bulk-btn" id="deleteSpotsBulkBtn">Mark selected unavailable</button>
                </div>
                <table class="pending-table" id="spotsPriceTable" style="display: none;">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="spotsSelectAll" title="Select all"></th>
                            <th>Spot</th>
                            <th>Price (e.g. 2,500)</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="spotsPriceBody"></tbody>
                </table>
                <p class="pending-empty" id="spotsPriceEmpty" style="display: none;">No tourist spots yet. <a href="add_spot.php">Add a spot</a> first.</p>
            </div>
        </div>
    </section>

    <!-- Review moderation section -->
    <section class="pending-guides-section">
        <h2 class="panel-section-title">Review moderation section</h2>
    </section>
    <section class="pending-guides-section">
        <div class="review-panels-grid">
            <div class="panel pending-panel review-summary-panel">
                <div class="panel-head">
                    <h3>Rating overview</h3>
                    <span class="pending-subtitle">Track average ratings, review types, and the current star distribution across submitted reviews.</span>
                </div>
                <p class="pending-loading" id="reviewSummaryLoading">Loading…</p>
                <div id="reviewSummaryContent" style="display: none;">
                    <div class="review-summary-stats">
                        <div class="review-summary-card">
                            <span class="review-summary-label">Total reviews</span>
                            <strong id="reviewTotalCount">0</strong>
                        </div>
                        <div class="review-summary-card">
                            <span class="review-summary-label">Average rating</span>
                            <strong id="reviewAverageRating">0.0</strong>
                        </div>
                        <div class="review-summary-card">
                            <span class="review-summary-label">Reported</span>
                            <strong id="reviewReportedCount">0</strong>
                        </div>
                        <div class="review-summary-card">
                            <span class="review-summary-label">Guide reviews</span>
                            <strong id="reviewGuideCount">0</strong>
                        </div>
                        <div class="review-summary-card">
                            <span class="review-summary-label">Location reviews</span>
                            <strong id="reviewLocationCount">0</strong>
                        </div>
                    </div>
                    <div class="review-distribution" id="reviewDistribution"></div>
                    <p class="review-summary-updated" id="reviewSummaryUpdated"></p>
                </div>
                <p class="pending-empty" id="reviewSummaryEmpty" style="display: none;">No rating data available yet.</p>
            </div>

            <div class="panel pending-panel">
                <div class="panel-head">
                    <h3>Top-rated guides</h3>
                    <span class="pending-subtitle">Sort guides by average rating, review count, or latest review activity.</span>
                </div>
                <div class="review-toolbar">
                    <label class="review-control" for="topGuidesSort">
                        <span>Sort by</span>
                        <select id="topGuidesSort">
                            <option value="highest">Highest rated</option>
                            <option value="most_reviews">Most reviews</option>
                            <option value="recent">Most recent review</option>
                        </select>
                    </label>
                    <label class="review-control" for="topGuidesMinReviews">
                        <span>Minimum reviews</span>
                        <select id="topGuidesMinReviews">
                            <option value="1">1+</option>
                            <option value="2">2+</option>
                            <option value="3">3+</option>
                            <option value="5">5+</option>
                        </select>
                    </label>
                </div>
                <p class="pending-loading" id="topGuidesLoading">Loading…</p>
                <div class="top-guides-list" id="topGuidesList" style="display: none;"></div>
                <p class="pending-empty" id="topGuidesEmpty" style="display: none;">No guide ratings yet.</p>
            </div>
        </div>
    </section>
    <section class="pending-guides-section">
        <div class="panel pending-panel">
            <div class="panel-head">
                <h3>View previous reviews</h3>
                <span class="pending-subtitle">Browse all active reviews from tourists, filter them by type or rating, and remove inappropriate entries.</span>
            </div>
            <div class="review-toolbar review-toolbar-inline">
                <label class="review-control" for="reviewsAdminSearch">
                    <span>Search</span>
                    <input type="search" id="reviewsAdminSearch" placeholder="Search tourist, guide, location, or comment">
                </label>
                <label class="review-control" for="reviewsAdminTypeFilter">
                    <span>Type</span>
                    <select id="reviewsAdminTypeFilter">
                        <option value="all">All reviews</option>
                        <option value="guide">Guide</option>
                        <option value="location">Location</option>
                        <option value="reported">Reported only</option>
                    </select>
                </label>
                <label class="review-control" for="reviewsAdminRatingFilter">
                    <span>Rating</span>
                    <select id="reviewsAdminRatingFilter">
                        <option value="all">All ratings</option>
                        <option value="5">5 stars</option>
                        <option value="4">4 stars</option>
                        <option value="3">3 stars</option>
                        <option value="2">2 stars</option>
                        <option value="1">1 star</option>
                    </select>
                </label>
                <label class="review-control" for="reviewsAdminSort">
                    <span>Sort</span>
                    <select id="reviewsAdminSort">
                        <option value="latest">Latest</option>
                        <option value="highest">Highest rating</option>
                        <option value="lowest">Lowest rating</option>
                    </select>
                </label>
            </div>
            <div id="reviewsAdminContainer">
                <p class="pending-loading" id="reviewsAdminLoading">Loading…</p>
                <table class="pending-table" id="reviewsAdminTable" style="display: none;">
                    <thead>
                        <tr>
                            <th>Tourist</th>
                            <th>Guide / Location</th>
                            <th>Rating</th>
                            <th>Comment</th>
                            <th>Reply</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="reviewsAdminBody"></tbody>
                </table>
                <p class="pending-empty" id="reviewsAdminEmpty" style="display: none;">No tourist reviews yet.</p>
            </div>
        </div>
    </section>

    <!-- Handle reported reviews -->
    <section class="pending-guides-section">
        <h2 class="panel-section-title">Handle reported reviews</h2>
    </section>
    <section class="pending-guides-section">
        <div class="panel pending-panel">
            <div class="panel-head">
                <h3>Reported reviews</h3>
                <span class="pending-subtitle">Reviews that have been flagged by guides or users appear here. Dismiss the report to keep it visible or delete the review.</span>
            </div>
            <div id="reportedReviewsContainer">
                <p class="pending-loading" id="reportedReviewsLoading">Loading…</p>
                <table class="pending-table" id="reportedReviewsTable" style="display: none;">
                    <thead>
                        <tr>
                            <th>Tourist</th>
                            <th>Guide / Location</th>
                            <th>Rating</th>
                            <th>Comment</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="reportedReviewsBody"></tbody>
                </table>
                <p class="pending-empty" id="reportedReviewsEmpty" style="display: none;">No reported reviews. When users report a review, it will appear here for moderation.</p>
            </div>
        </div>
    </section>

    <!-- Security and accessibility -->
    <section class="pending-guides-section">
        <h2 class="panel-section-title">Security and accessibility</h2>
    </section>

    <main class="admin-grid security-grid">
        <div class="panel pending-panel table-panel">
            <div class="panel-head">
                <h3>Implementation status</h3>
                <i data-feather="refresh-cw"></i>
                <span class="pending-subtitle">Track the current state of the rollout directly from the admin dashboard.</span>
            </div>
            <div id="securityStatusContainer">
                <p class="pending-loading" id="securityStatusLoading">Loading...</p>
                <table class="pending-table" id="securityStatusTable" style="display: none;">
                    <thead>
                        <tr>
                            <th>Area</th>
                            <th>Status</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody id="securityStatusBody"></tbody>
                </table>
                <p class="pending-empty" id="securityStatusEmpty" style="display: none;">Could not load the security and accessibility status.</p>
            </div>
        </div>

        <div class="panel feed-panel security-summary-panel">
            <div class="panel-head">
                <h3>Priority overview</h3>
                <span class="pending-subtitle">A quick summary of what is complete and what still needs follow-up.</span>
            </div>
            <div id="securitySummaryContainer">
                <p class="pending-loading" id="securitySummaryLoading">Loading...</p>
                <div id="securitySummaryContent" style="display: none;">
                    <div class="security-summary-stats">
                        <div class="security-summary-card">
                            <span class="security-summary-label">Implemented</span>
                            <strong id="securityImplementedCount">0</strong>
                        </div>
                        <div class="security-summary-card">
                            <span class="security-summary-label">Partial</span>
                            <strong id="securityPartialCount">0</strong>
                        </div>
                        <div class="security-summary-card">
                            <span class="security-summary-label">Needs attention</span>
                            <strong id="securityAttentionCount">0</strong>
                        </div>
                    </div>
                    <p class="security-summary-updated" id="securitySummaryUpdated"></p>
                    <div class="feed-list security-recommendations" id="securityRecommendations"></div>
                </div>
                <p class="pending-empty" id="securitySummaryEmpty" style="display: none;">Could not load the summary.</p>
            </div>
        </div>
    </main>

    <section class="pending-guides-section">
        <h2 class="panel-section-title">System maintenance tools</h2>
    </section>

    <main class="admin-grid security-grid">
        <div class="panel pending-panel table-panel">
            <div class="panel-head">
                <h3>Maintenance status</h3>
                <i data-feather="tool"></i>
                <span class="pending-subtitle">Check debug-log readiness, booking table health, and project maintenance availability.</span>
            </div>
            <div id="maintenanceStatusContainer">
                <p class="pending-loading" id="maintenanceStatusLoading">Loading...</p>
                <table class="pending-table" id="maintenanceStatusTable" style="display: none;">
                    <thead>
                        <tr>
                            <th>Area</th>
                            <th>Status</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody id="maintenanceStatusBody"></tbody>
                </table>
                <p class="pending-empty" id="maintenanceStatusEmpty" style="display: none;">Could not load the maintenance status.</p>
            </div>
        </div>

        <div class="panel feed-panel security-summary-panel">
            <div class="panel-head">
                <h3>Maintenance actions</h3>
                <span class="pending-subtitle">Refresh system checks, inspect current booking counts, and clear the project debug log when needed.</span>
            </div>
            <div id="maintenanceActionsContainer">
                <p class="pending-loading" id="maintenanceActionsLoading">Loading...</p>
                <div id="maintenanceActionsContent" style="display: none;">
                    <div class="security-summary-stats">
                        <div class="security-summary-card">
                            <span class="security-summary-label">Debug log size</span>
                            <strong id="maintenanceLogSize">0 B</strong>
                        </div>
                        <div class="security-summary-card">
                            <span class="security-summary-label">Pending bookings</span>
                            <strong id="maintenancePendingBookings">0</strong>
                        </div>
                        <div class="security-summary-card">
                            <span class="security-summary-label">Approved bookings</span>
                            <strong id="maintenanceApprovedBookings">0</strong>
                        </div>
                        <div class="security-summary-card">
                            <span class="security-summary-label">Completed bookings</span>
                            <strong id="maintenanceCompletedBookings">0</strong>
                        </div>
                    </div>
                    <p class="security-summary-updated" id="maintenanceUpdated"></p>
                    <p class="security-summary-updated" id="maintenanceLogMeta"></p>
                    <p class="security-summary-updated" id="maintenanceBookingMeta"></p>
                    <div style="display:flex; gap:0.75rem; flex-wrap:wrap; margin:1rem 0;">
                        <button type="button" class="cmd-btn" id="refreshMaintenanceBtn">Refresh status</button>
                        <button type="button" class="cmd-btn btn-danger" id="clearDebugLogBtn">Clear debug log</button>
                    </div>
                    <div class="feed-list security-recommendations" id="maintenanceRecommendations"></div>
                </div>
                <p class="pending-empty" id="maintenanceActionsEmpty" style="display: none;">Could not load maintenance actions.</p>
            </div>
        </div>
    </main>

    <div class="admin-notice" id="adminNotice" aria-live="polite" aria-atomic="true">
        <span id="adminNoticeMessage"></span>
        <button type="button" class="admin-notice-close" id="adminNoticeClose" aria-label="Close message">OK</button>
    </div>

    <div class="admin-confirm-backdrop" id="adminConfirmBackdrop" hidden>
        <div class="admin-confirm-modal" role="dialog" aria-modal="true" aria-labelledby="adminConfirmTitle" aria-describedby="adminConfirmMessage">
            <h3 id="adminConfirmTitle">Please confirm</h3>
            <p id="adminConfirmMessage"></p>
            <div class="admin-confirm-actions">
                <button type="button" class="admin-confirm-btn admin-confirm-cancel" id="adminConfirmCancel">Cancel</button>
                <button type="button" class="admin-confirm-btn admin-confirm-ok" id="adminConfirmOk">Continue</button>
            </div>
        </div>
    </div>

    <script src="logout_modal.js"></script>
    <script>feather.replace();</script>
    <script>
    (function() {
        var logoutLink = document.querySelector('a.logout-link');
        if (!logoutLink) return;

        function clearClientSession() {
            var activeRole = localStorage.getItem('role') || '';
            var activeUserId = localStorage.getItem('userId') || '';
            localStorage.removeItem('userLoggedIn');
            localStorage.removeItem('userId');
            localStorage.removeItem('role');
            localStorage.removeItem('touristId');
            localStorage.removeItem('guideId');
            localStorage.removeItem('firstName');
            localStorage.removeItem('lastName');
            localStorage.removeItem('fullName');
            localStorage.removeItem('profileImage');
            localStorage.removeItem('userReviews');

            if (activeRole && activeUserId) {
                localStorage.removeItem('firstName:' + activeRole + ':' + activeUserId);
                localStorage.removeItem('lastName:' + activeRole + ':' + activeUserId);
                localStorage.removeItem('profileName:' + activeRole + ':' + activeUserId);
                localStorage.removeItem('profileImage:' + activeRole + ':' + activeUserId);
            }
        }

        logoutLink.addEventListener('click', function(e) {
            e.preventDefault();
            var proceedLogout = function() {
                clearClientSession();
                window.location.href = 'logout.php';
            };

            if (typeof showLogoutConfirm === 'function') {
                showLogoutConfirm(proceedLogout);
            } else {
                proceedLogout();
            }
        });
    })();
    (function() {
        const body = document.getElementById('pendingGuidesBody');
        const table = document.getElementById('pendingGuidesTable');
        const loading = document.getElementById('pendingLoading');
        const empty = document.getElementById('pendingEmpty');

        var FETCH_TIMEOUT_MS = 12000;
        function fetchWithTimeout(url, options, timeoutMs) {
            timeoutMs = timeoutMs || FETCH_TIMEOUT_MS;
            var ctrl = new AbortController();
            var id = setTimeout(function() { ctrl.abort(); }, timeoutMs);
            options = options || {};
            options.signal = ctrl.signal;
            return fetch(url, options).then(function(r) { clearTimeout(id); return r; }, function(err) { clearTimeout(id); throw err; });
        }

        var adminNotice = document.getElementById('adminNotice');
        var adminNoticeMessage = document.getElementById('adminNoticeMessage');
        var adminNoticeClose = document.getElementById('adminNoticeClose');
        var adminNoticeTimer = null;
        var adminConfirmBackdrop = document.getElementById('adminConfirmBackdrop');
        var adminConfirmMessage = document.getElementById('adminConfirmMessage');
        var adminConfirmCancel = document.getElementById('adminConfirmCancel');
        var adminConfirmOk = document.getElementById('adminConfirmOk');
        var adminConfirmResolver = null;

        function showAdminNotice(message) {
            if (!adminNotice || !adminNoticeMessage) return;
            adminNoticeMessage.textContent = message || 'Something went wrong.';
            adminNotice.hidden = false;
            adminNotice.classList.add('is-visible');
            if (adminNoticeTimer) clearTimeout(adminNoticeTimer);
            adminNoticeTimer = setTimeout(function() {
                hideAdminNotice();
            }, 3200);
        }

        function hideAdminNotice() {
            if (!adminNotice) return;
            adminNotice.classList.remove('is-visible');
            adminNoticeTimer = setTimeout(function() {
                adminNotice.hidden = true;
            }, 180);
        }

        function closeAdminConfirm(confirmed) {
            if (!adminConfirmBackdrop) return;
            adminConfirmBackdrop.hidden = true;
            if (adminConfirmResolver) {
                var resolve = adminConfirmResolver;
                adminConfirmResolver = null;
                resolve(confirmed);
            }
        }

        function showAdminConfirm(message) {
            if (!adminConfirmBackdrop || !adminConfirmMessage) {
                showAdminNotice(message || 'Please confirm this action.');
                return Promise.resolve(false);
            }
            adminConfirmMessage.textContent = message || 'Are you sure you want to continue?';
            adminConfirmBackdrop.hidden = false;
            return new Promise(function(resolve) {
                adminConfirmResolver = resolve;
            });
        }

        if (adminNoticeClose) {
            adminNoticeClose.addEventListener('click', hideAdminNotice);
        }

        if (adminConfirmCancel) {
            adminConfirmCancel.addEventListener('click', function() { closeAdminConfirm(false); });
        }

        if (adminConfirmOk) {
            adminConfirmOk.addEventListener('click', function() { closeAdminConfirm(true); });
        }

        if (adminConfirmBackdrop) {
            adminConfirmBackdrop.addEventListener('click', function(e) {
                if (e.target === adminConfirmBackdrop) closeAdminConfirm(false);
            });
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                if (adminConfirmBackdrop && !adminConfirmBackdrop.hidden) closeAdminConfirm(false);
                hideAdminNotice();
            }
        });

        function loadPending() {
            if (!loading) return;
            loading.style.display = 'block';
            if (table) table.style.display = 'none';
            if (empty) empty.style.display = 'none';

            fetchWithTimeout('get_pending_guides.php', { credentials: 'same-origin' })
                .then(function(r) {
                    if (r.status === 403) { window.location.href = 'signinTouristAdmin.html'; return []; }
                    return r.json();
                })
                .then(function(data) {
                    loading.style.display = 'none';
                    if (!Array.isArray(data) || data.length === 0) {
                        if (empty) empty.style.display = 'block';
                        return;
                    }
                    if (table) table.style.display = 'table';
                    if (body) {
                        body.innerHTML = data.map(function(g) {
                            return '<tr data-guide-id="' + g.guide_id + '">' +
                                '<td><b>' + escapeHtml(g.name) + '</b></td>' +
                                '<td><button type="button" class="approve-guide-btn" data-guide-id="' + g.guide_id + '">Add to landing page</button></td>' +
                                '</tr>';
                        }).join('');
                    }
                    document.querySelectorAll('.approve-guide-btn').forEach(function(btn) {
                        btn.addEventListener('click', function() {
                            var id = this.getAttribute('data-guide-id');
                            if (!id) return;
                            this.disabled = true;
                            this.textContent = 'Adding…';
                            var form = new FormData();
                            form.append('guide_id', id);
                            fetch('approve_guide.php', { method: 'POST', credentials: 'same-origin', body: form })
                                .then(function(r) { return r.json(); })
                                .then(function(res) {
                                    if (res.ok) {
                                        var row = document.querySelector('tr[data-guide-id="' + id + '"]');
                                        if (row) row.remove();
                                        if (body && body.rows.length === 0) {
                                            if (table) table.style.display = 'none';
                                            if (empty) { empty.style.display = 'block'; empty.textContent = 'No pending guides. When a guide registers, they will appear here for you to add to the landing page.'; }
                                        }
                                    } else {
                                        showAdminNotice(res.error || 'Could not approve.');
                                        btn.disabled = false;
                                        btn.textContent = 'Add to landing page';
                                    }
                                })
                                .catch(function() {
                                    showAdminNotice('Request failed.');
                                    btn.disabled = false;
                                    btn.textContent = 'Add to landing page';
                                });
                        });
                    });
                })
                .catch(function() {
                    loading.style.display = 'none';
                    if (empty) { empty.innerHTML = 'Could not load. Request failed or timed out. Open this page via your server (e.g. <code>http://localhost/guidemate1/adminDashboard.php</code>) and ensure Apache and MySQL are running.'; empty.style.display = 'block'; }
                });
        }

        function loadPendingBookings() {
            var loading = document.getElementById('pendingBookingsLoading');
            var table = document.getElementById('pendingBookingsTable');
            var body = document.getElementById('pendingBookingsBody');
            var empty = document.getElementById('pendingBookingsEmpty');
            if (!loading) return;
            loading.style.display = 'block';
            if (table) table.style.display = 'none';
            if (empty) empty.style.display = 'none';

            fetchWithTimeout('get_pending_bookings.php', { credentials: 'same-origin' })
                .then(function(r) {
                    if (r.status === 403) { window.location.href = 'signinTouristAdmin.html'; return []; }
                    return r.json();
                })
                .then(function(data) {
                    loading.style.display = 'none';
                    if (!Array.isArray(data) || data.length === 0) {
                        if (empty) empty.style.display = 'block';
                        return;
                    }
                    if (table) table.style.display = 'table';
                    if (body) {
                        body.innerHTML = data.map(function(b) {
                            var requestDetails = [
                                'Waiting for guide acceptance',
                                b.tourist_message ? ('Tourist note: ' + b.tourist_message) : '',
                                b.meeting_location ? ('Suggested location: ' + b.meeting_location) : ''
                            ].filter(Boolean).join('\n');
                            return '<tr data-booking-id="' + b.booking_id + '">' +
                                '<td><b>' + escapeHtml(b.tourist_name) + '</b></td>' +
                                '<td>' + escapeHtml(b.guide_name) + '</td>' +
                                '<td>' + escapeHtml(b.created_at || '') + '</td>' +
                                '<td style="white-space: pre-line;">' + escapeHtml(requestDetails) + '</td>' +
                                '<td>Waiting for guide</td>' +
                                '</tr>';
                        }).join('');
                    }
                })
                .catch(function() {
                    loading.style.display = 'none';
                    if (empty) { empty.innerHTML = 'Could not load booking requests.'; empty.style.display = 'block'; }
                });
        }

        function loadApprovedBookings() {
            var loading = document.getElementById('approvedBookingsLoading');
            var table = document.getElementById('approvedBookingsTable');
            var body = document.getElementById('approvedBookingsBody');
            var empty = document.getElementById('approvedBookingsEmpty');
            if (!loading) return;
            loading.style.display = 'block';
            if (table) table.style.display = 'none';
            if (empty) empty.style.display = 'none';

            fetchWithTimeout('get_approved_bookings_admin.php', { credentials: 'same-origin' })
                .then(function(r) {
                    if (r.status === 403) { window.location.href = 'signinTouristAdmin.html'; return []; }
                    return r.json();
                })
                .then(function(data) {
                    loading.style.display = 'none';
                    if (!Array.isArray(data) || data.length === 0) {
                        if (empty) empty.style.display = 'block';
                        return;
                    }
                    if (table) table.style.display = 'table';
                    if (body) {
                        body.innerHTML = data.map(function(b) {
                            var approvedMeetDetails = b.meet_time
                                ? formatBookingDateTime(b.meet_time)
                                : 'To be confirmed in guide-tourist messages';
                            return '<tr data-approved-booking-id="' + b.booking_id + '">' +
                                '<td><b>' + escapeHtml(b.tourist_name) + '</b></td>' +
                                '<td>' + escapeHtml(b.guide_name) + '</td>' +
                                '<td>' + escapeHtml(b.approved_at || b.created_at || '') + '</td>' +
                                '<td>' + escapeHtml(approvedMeetDetails) + '</td>' +
                                '<td><button type="button" class="release-booking-btn" data-booking-id="' + b.booking_id + '">Make available again</button></td>' +
                                '</tr>';
                        }).join('');
                    }
                    document.querySelectorAll('.release-booking-btn').forEach(function(btn) {
                        btn.addEventListener('click', function() {
                            var id = this.getAttribute('data-booking-id');
                            if (!id) return;
                            this.disabled = true;
                            this.textContent = 'Updating…';
                            var form = new FormData();
                            form.append('booking_id', id);
                            fetch('release_booking.php', { method: 'POST', credentials: 'same-origin', body: form })
                                .then(function(r) { return r.json(); })
                                .then(function(res) {
                                    if (res.ok) {
                                        var row = document.querySelector('tr[data-approved-booking-id="' + id + '"]');
                                        if (row) row.remove();
                                        if (body && body.rows.length === 0) {
                                            if (table) table.style.display = 'none';
                                            if (empty) empty.style.display = 'block';
                                        }
                                    } else {
                                        showAdminNotice(res.error || 'Could not update booking.');
                                        btn.disabled = false;
                                        btn.textContent = 'Make available again';
                                    }
                                })
                                .catch(function() {
                                    showAdminNotice('Request failed.');
                                    btn.disabled = false;
                                    btn.textContent = 'Make available again';
                                });
                        });
                    });
                })
                .catch(function() {
                    loading.style.display = 'none';
                    if (empty) { empty.innerHTML = 'Could not load approved bookings.'; empty.style.display = 'block'; }
                });
        }

        function escapeHtml(s) {
            if (s == null) return '';
            var div = document.createElement('div');
            div.textContent = s;
            return div.innerHTML;
        }

        function escapeAttribute(value) {
            return escapeHtml(value).replace(/"/g, '&quot;');
        }

        function formatBookingDateTime(value) {
            if (!value) return '';
            var normalized = String(value).replace(' ', 'T');
            var date = new Date(normalized);
            if (isNaN(date.getTime())) return String(value);
            return date.toLocaleString(undefined, {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: 'numeric',
                minute: '2-digit'
            });
        }

        function getDefaultMeetTimeInput(value) {
            var date = value ? new Date(String(value).replace(' ', 'T')) : new Date(Date.now() + (60 * 60 * 1000));
            if (isNaN(date.getTime())) {
                date = new Date(Date.now() + (60 * 60 * 1000));
            }
            var year = date.getFullYear();
            var month = String(date.getMonth() + 1).padStart(2, '0');
            var day = String(date.getDate()).padStart(2, '0');
            var hours = String(date.getHours()).padStart(2, '0');
            var minutes = String(date.getMinutes()).padStart(2, '0');
            return year + '-' + month + '-' + day + 'T' + hours + ':' + minutes;
        }

        function normalizeMeetTimeInput(value) {
            var raw = String(value || '').trim();
            if (!raw) {
                return '';
            }

            var normalized = raw
                .replace(/\//g, '-')
                .replace(/\s+/g, ' ')
                .replace(/^(\d{4}-\d{2}-\d{2})\s+(\d{1,2}:\d{2}(?::\d{2})?)$/, '$1T$2');

            var directDate = new Date(normalized);
            if (!isNaN(directDate.getTime())) {
                return getDefaultMeetTimeInput(toDateTimeDatabaseValue(directDate));
            }

            var match = raw.match(/^(\d{4})-(\d{2})-(\d{2})[\sT]+(\d{1,2}):(\d{2})(?:\s*([AaPp][Mm]))?$/);
            if (!match) {
                return '';
            }

            var hours = parseInt(match[4], 10);
            var minutes = parseInt(match[5], 10);
            var meridiem = (match[6] || '').toUpperCase();

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

            var parsedDate = new Date(
                parseInt(match[1], 10),
                parseInt(match[2], 10) - 1,
                parseInt(match[3], 10),
                hours,
                minutes,
                0,
                0
            );

            return isNaN(parsedDate.getTime()) ? '' : getDefaultMeetTimeInput(toDateTimeDatabaseValue(parsedDate));
        }

        function toDateTimeDatabaseValue(date) {
            var year = date.getFullYear();
            var month = String(date.getMonth() + 1).padStart(2, '0');
            var day = String(date.getDate()).padStart(2, '0');
            var hours = String(date.getHours()).padStart(2, '0');
            var minutes = String(date.getMinutes()).padStart(2, '0');
            var seconds = String(date.getSeconds()).padStart(2, '0');
            return year + '-' + month + '-' + day + ' ' + hours + ':' + minutes + ':' + seconds;
        }

        function loadAllGuides() {
            var loading = document.getElementById('allGuidesLoading');
            var table = document.getElementById('allGuidesTable');
            var body = document.getElementById('allGuidesBody');
            var empty = document.getElementById('allGuidesEmpty');
            if (!loading) return;
            loading.style.display = 'block';
            if (table) table.style.display = 'none';
            if (empty) empty.style.display = 'none';
            fetchWithTimeout('get_all_guides_admin.php', { credentials: 'same-origin' })
                .then(function(r) { if (r.status === 403) { window.location.href = 'signinTouristAdmin.html'; return []; } return r.json(); })
                .then(function(data) {
                    loading.style.display = 'none';
                    if (!Array.isArray(data) || data.length === 0) {
                        if (empty) empty.style.display = 'block';
                        return;
                    }
                    if (table) table.style.display = 'table';
                    if (body) {
                        body.innerHTML = data.map(function(g) {
                            var canDelete = !!g.can_delete;
                            var statusText = String(g.status || 'Pending');
                            var normalizedStatus = statusText.toLowerCase();
                            var statusClass = 'guide-status-pending';
                            if (normalizedStatus === 'active') {
                                statusClass = 'guide-status-active';
                            } else if (normalizedStatus === 'suspended') {
                                statusClass = 'guide-status-suspended';
                            }
                            var deleteAction = canDelete
                                ? '<button type="button" class="delete-guide-btn" data-guide-id="' + g.guide_id + '" data-guide-name="' + escapeAttribute(g.name || 'this guide') + '">Delete guide</button>'
                                : '<span class="guide-action-muted">Active guide</span>';
                            return '<tr data-guide-id="' + g.guide_id + '">' +
                                '<td><b>' + escapeHtml(g.name) + '</b></td>' +
                                '<td>' + escapeHtml(g.email) + '</td>' +
                                '<td><span class="guide-status-badge ' + statusClass + '">' + escapeHtml(statusText) + '</span></td>' +
                                '<td class="guide-action-cell">' + deleteAction + '</td></tr>';
                        }).join('');
                    }
                    document.querySelectorAll('#allGuidesBody .delete-guide-btn').forEach(function(btn) {
                        btn.addEventListener('click', function() {
                            var id = this.getAttribute('data-guide-id');
                            var guideName = this.getAttribute('data-guide-name') || 'this guide';
                            if (!id) return;
                            this.disabled = true;

                            showAdminConfirm('Delete ' + guideName + '? This permanently removes the guide account and cannot be undone.')
                                .then(function(confirmed) {
                                    if (!confirmed) {
                                        btn.disabled = false;
                                        return;
                                    }
                                    var form = new FormData();
                                    form.append('guide_id', id);
                                    return fetch('delete_guide_admin.php', { method: 'POST', credentials: 'same-origin', body: form })
                                        .then(function(r) { return r.json(); })
                                        .then(function(res) {
                                            if (!res.ok) {
                                                showAdminNotice(res.error || 'Could not delete guide.');
                                                btn.disabled = false;
                                                return;
                                            }
                                            showAdminNotice('Guide deleted successfully.');
                                            loadAllGuides();
                                            loadPending();
                                            loadActiveGuides();
                                            loadSuspendedGuides();
                                        })
                                        .catch(function() {
                                            showAdminNotice('Request failed while deleting guide.');
                                            btn.disabled = false;
                                        });
                                });
                        });
                    });
                })
                .catch(function() { loading.style.display = 'none'; if (empty) { empty.innerHTML = 'Could not load all guides.'; empty.style.display = 'block'; } });
        }

        function loadActiveGuides() {
            var loading = document.getElementById('activeLoading');
            var table = document.getElementById('activeGuidesTable');
            var body = document.getElementById('activeGuidesBody');
            var empty = document.getElementById('activeEmpty');
            if (!loading) return;
            loading.style.display = 'block';
            if (table) table.style.display = 'none';
            if (empty) empty.style.display = 'none';
            fetchWithTimeout('get_active_guides.php', { credentials: 'same-origin' })
                .then(function(r) { if (r.status === 403) { window.location.href = 'signinTouristAdmin.html'; return []; } return r.json(); })
                .then(function(data) {
                    loading.style.display = 'none';
                    if (!Array.isArray(data) || data.length === 0) {
                        if (empty) empty.style.display = 'block';
                        return;
                    }
                    if (table) table.style.display = 'table';
                    if (body) {
                        body.innerHTML = data.map(function(g) {
                            return '<tr data-guide-id="' + g.guide_id + '">' +
                                '<td><b>' + escapeHtml(g.name) + '</b></td>' +
                                '<td>' + escapeHtml(g.email) + '</td>' +
                                '<td><span class="punish-actions"><button type="button" class="punish-btn" data-guide-id="' + g.guide_id + '" data-days="1">1 day</button> <button type="button" class="punish-btn" data-guide-id="' + g.guide_id + '" data-days="2">2 days</button> <button type="button" class="punish-btn" data-guide-id="' + g.guide_id + '" data-days="3">3 days</button></span></td></tr>';
                        }).join('');
                    }
                    document.querySelectorAll('#activeGuidesBody .punish-btn').forEach(function(btn) {
                        btn.addEventListener('click', function() {
                            var id = this.getAttribute('data-guide-id');
                            var days = this.getAttribute('data-days');
                            if (!id || !days) return;
                            this.disabled = true;
                            var form = new FormData();
                            form.append('guide_id', id);
                            form.append('days', days);
                            fetch('suspend_guide.php', { method: 'POST', credentials: 'same-origin', body: form })
                                .then(function(r) { return r.json(); })
                                .then(function(res) {
                                    if (res.ok) { loadAllGuides(); loadActiveGuides(); loadSuspendedGuides(); } else { showAdminNotice(res.error || 'Failed'); this.disabled = false; }
                                }.bind(this))
                                .catch(function() { showAdminNotice('Request failed.'); this.disabled = false; }.bind(this));
                        });
                    });
                })
                .catch(function() { loading.style.display = 'none'; if (empty) { empty.innerHTML = 'Could not load. Request failed or timed out. Use the site via your server and ensure Apache and MySQL are running.'; empty.style.display = 'block'; } });
        }

        function loadSuspendedGuides() {
            var loading = document.getElementById('suspendedLoading');
            var table = document.getElementById('suspendedGuidesTable');
            var body = document.getElementById('suspendedGuidesBody');
            var empty = document.getElementById('suspendedEmpty');
            if (!loading) return;
            loading.style.display = 'block';
            if (table) table.style.display = 'none';
            if (empty) empty.style.display = 'none';
            fetchWithTimeout('get_suspended_guides.php', { credentials: 'same-origin' })
                .then(function(r) { if (r.status === 403) return []; return r.json(); })
                .then(function(data) {
                    loading.style.display = 'none';
                    if (!Array.isArray(data) || data.length === 0) {
                        if (empty) empty.style.display = 'block';
                        return;
                    }
                    if (table) table.style.display = 'table';
                    if (body) {
                        body.innerHTML = data.map(function(g) {
                            var suspensionCount = parseInt(g.suspension_count || 0, 10);
                            if (!Number.isFinite(suspensionCount)) suspensionCount = 0;
                            var canDeleteForMultipleSuspension = suspensionCount > 1;
                            var deleteAction = canDeleteForMultipleSuspension
                                ? '<button type="button" class="delete-guide-btn suspended-delete-btn" data-guide-id="' + g.guide_id + '" data-guide-name="' + escapeAttribute(g.name || 'this guide') + '">Delete guide</button>'
                                : '<span class="guide-action-muted">Delete allowed after multiple suspensions</span>';
                            return '<tr data-guide-id="' + g.guide_id + '">' +
                                '<td><b>' + escapeHtml(g.name) + '</b></td>' +
                                '<td>' + escapeHtml(g.suspended_until || '—') + '</td>' +
                                '<td><span class="punish-actions"><button type="button" class="pardon-btn" data-guide-id="' + g.guide_id + '">Re-add to landing page</button> ' + deleteAction + '</span></td></tr>';
                        }).join('');
                    }
                    document.querySelectorAll('#suspendedGuidesBody .pardon-btn').forEach(function(btn) {
                        btn.addEventListener('click', function() {
                            var id = this.getAttribute('data-guide-id');
                            if (!id) return;
                            this.disabled = true;
                            var form = new FormData();
                            form.append('guide_id', id);
                            fetch('pardon_guide.php', { method: 'POST', credentials: 'same-origin', body: form })
                                .then(function(r) { return r.json(); })
                                .then(function(res) {
                                    if (res.ok) { loadAllGuides(); loadActiveGuides(); loadSuspendedGuides(); } else { this.disabled = false; }
                                }.bind(this))
                                .catch(function() { this.disabled = false; }.bind(this));
                        });
                    });
                    document.querySelectorAll('#suspendedGuidesBody .suspended-delete-btn').forEach(function(btn) {
                        btn.addEventListener('click', function() {
                            var id = this.getAttribute('data-guide-id');
                            var guideName = this.getAttribute('data-guide-name') || 'this guide';
                            if (!id) return;
                            this.disabled = true;

                            showAdminConfirm('Delete ' + guideName + '? This permanently removes the guide account and cannot be undone.')
                                .then(function(confirmed) {
                                    if (!confirmed) {
                                        btn.disabled = false;
                                        return;
                                    }
                                    var form = new FormData();
                                    form.append('guide_id', id);
                                    return fetch('delete_guide_admin.php', { method: 'POST', credentials: 'same-origin', body: form })
                                        .then(function(r) { return r.json(); })
                                        .then(function(res) {
                                            if (!res.ok) {
                                                showAdminNotice(res.error || 'Could not delete guide.');
                                                btn.disabled = false;
                                                return;
                                            }
                                            showAdminNotice('Guide deleted successfully.');
                                            loadAllGuides();
                                            loadPending();
                                            loadActiveGuides();
                                            loadSuspendedGuides();
                                        })
                                        .catch(function() {
                                            showAdminNotice('Request failed while deleting guide.');
                                            btn.disabled = false;
                                        });
                                });
                        });
                    });
                })
                .catch(function() { loading.style.display = 'none'; if (empty) { empty.innerHTML = 'Could not load. Request failed or timed out.'; empty.style.display = 'block'; } });
        }

        function loadSpotsPrice() {
            var loading = document.getElementById('spotsPriceLoading');
            var table = document.getElementById('spotsPriceTable');
            var body = document.getElementById('spotsPriceBody');
            var empty = document.getElementById('spotsPriceEmpty');
            if (!loading) return;
            loading.style.display = 'block';
            if (table) table.style.display = 'none';
            if (empty) empty.style.display = 'none';
            fetchWithTimeout('get_spots_admin.php', { credentials: 'same-origin' })
                .then(function(r) {
                    if (r.status === 403) { window.location.href = 'signinTouristAdmin.html'; return []; }
                    return r.json();
                })
                .then(function(data) {
                    loading.style.display = 'none';
                    if (!Array.isArray(data)) {
                        if (empty) { empty.innerHTML = 'Could not load spots. Try again or <a href="add_spot.php">add a spot</a> first.'; empty.style.display = 'block'; }
                        return;
                    }
                    if (data.length === 0) {
                        if (empty) empty.style.display = 'block';
                        return;
                    }
                    if (table) table.style.display = 'table';
                    var bulkActions = document.getElementById('spotsBulkActions');
                    if (bulkActions && data.length > 0) bulkActions.style.display = 'block';
                    if (body) {
                        body.innerHTML = data.map(function(s) {
                            var isAvailable = parseInt(s.is_available, 10) !== 0;
                            return '<tr data-destination-id="' + s.destination_id + '" data-is-available="' + (isAvailable ? '1' : '0') + '">' +
                                '<td><input type="checkbox" class="spot-row-checkbox" data-destination-id="' + s.destination_id + '"></td>' +
                                '<td><b>' + escapeHtml(s.name) + '</b></td>' +
                                '<td><input type="text" class="spot-price-input" data-destination-id="' + s.destination_id + '" value="' + escapeHtml(s.price || '') + '" placeholder="e.g. 2,500"></td>' +
                                '<td><span class="spot-actions"><span class="spot-status-badge ' + (isAvailable ? 'spot-status-available' : 'spot-status-unavailable') + '">' + (isAvailable ? 'Available' : 'Unavailable') + '</span> <button type="button" class="save-spot-price-btn" data-destination-id="' + s.destination_id + '">Save</button> <button type="button" class="delete-spot-btn ' + (isAvailable ? '' : 'spot-toggle-available-btn') + '" data-destination-id="' + s.destination_id + '" data-is-available="' + (isAvailable ? '1' : '0') + '">' + (isAvailable ? 'Unavailable' : 'Available') + '</button></span></td></tr>';
                        }).join('');
                    }
                    var selectAll = document.getElementById('spotsSelectAll');
                    if (selectAll) {
                        selectAll.checked = false;
                        selectAll.onclick = function() {
                            document.querySelectorAll('.spot-row-checkbox').forEach(function(cb) { cb.checked = selectAll.checked; });
                        };
                    }
                    var bulkBtn = document.getElementById('deleteSpotsBulkBtn');
                    if (bulkBtn) {
                        bulkBtn.onclick = function() {
                            var ids = [];
                            document.querySelectorAll('.spot-row-checkbox:checked').forEach(function(cb) {
                                var id = cb.getAttribute('data-destination-id');
                                if (id) ids.push(id);
                            });
                            if (ids.length === 0) { showAdminNotice('Select at least one spot to mark unavailable.'); return; }
                            showAdminConfirm('Mark ' + ids.length + ' selected spot(s) as unavailable? They will no longer appear on the landing page.').then(function(confirmed) {
                                if (!confirmed) return;
                                bulkBtn.disabled = true;
                                bulkBtn.textContent = 'Marking unavailable…';
                                var form = new FormData();
                                ids.forEach(function(id) { form.append('destination_ids[]', id); });
                                fetch('delete_spots_bulk.php', { method: 'POST', credentials: 'same-origin', body: form })
                                    .then(function(r) { return r.json(); })
                                    .then(function(res) {
                                        if (res.ok) {
                                            ids.forEach(function(id) {
                                                var row = document.querySelector('tr[data-destination-id="' + id + '"]');
                                                if (row) {
                                                    row.setAttribute('data-is-available', '0');
                                                    var badge = row.querySelector('.spot-status-badge');
                                                    if (badge) {
                                                        badge.textContent = 'Unavailable';
                                                        badge.classList.remove('spot-status-available');
                                                        badge.classList.add('spot-status-unavailable');
                                                    }
                                                    var toggleBtn = row.querySelector('.delete-spot-btn');
                                                    if (toggleBtn) {
                                                        toggleBtn.disabled = false;
                                                        toggleBtn.textContent = 'Available';
                                                        toggleBtn.setAttribute('data-is-available', '0');
                                                        toggleBtn.classList.add('spot-toggle-available-btn');
                                                    }
                                                    var checkbox = row.querySelector('.spot-row-checkbox');
                                                    if (checkbox) checkbox.checked = false;
                                                }
                                            });
                                            if (selectAll) selectAll.checked = false;
                                        } else {
                                            showAdminNotice(res.error || 'Could not mark spots as unavailable.');
                                        }
                                        bulkBtn.disabled = false;
                                        bulkBtn.textContent = 'Mark selected unavailable';
                                    })
                                    .catch(function() {
                                        showAdminNotice('Request failed.');
                                        bulkBtn.disabled = false;
                                        bulkBtn.textContent = 'Mark selected unavailable';
                                    });
                            });
                        };
                    }
                    document.querySelectorAll('.save-spot-price-btn').forEach(function(btn) {
                        btn.addEventListener('click', function() {
                            var id = this.getAttribute('data-destination-id');
                            if (!id) return;
                            var row = document.querySelector('tr[data-destination-id="' + id + '"]');
                            var input = row ? row.querySelector('.spot-price-input') : null;
                            var price = input ? input.value.trim() : '';
                            this.disabled = true;
                            this.textContent = 'Saving…';
                            var form = new FormData();
                            form.append('destination_id', id);
                            form.append('price', price);
                            fetch('update_spot_price.php', { method: 'POST', credentials: 'same-origin', body: form })
                                .then(function(r) { return r.json(); })
                                .then(function(res) {
                                    if (res.ok) {
                                        this.textContent = 'Saved';
                                        var t = this;
                                        setTimeout(function() { t.textContent = 'Save'; t.disabled = false; }, 1500);
                                    } else {
                                        showAdminNotice(res.error || 'Could not save.');
                                        this.disabled = false;
                                        this.textContent = 'Save';
                                    }
                                }.bind(this))
                                .catch(function() {
                                    showAdminNotice('Request failed.');
                                    this.disabled = false;
                                    this.textContent = 'Save';
                                }.bind(this));
                        });
                    });
                    document.querySelectorAll('.delete-spot-btn').forEach(function(btn) {
                        btn.addEventListener('click', function() {
                            var id = this.getAttribute('data-destination-id');
                            if (!id) return;
                            var row = document.querySelector('tr[data-destination-id="' + id + '"]');
                            var spotName = row && row.cells[1] ? row.cells[1].textContent.trim() : 'This spot';
                            var isCurrentlyAvailable = this.getAttribute('data-is-available') !== '0';
                            var nextAvailability = isCurrentlyAvailable ? '0' : '1';
                            var actionLabel = isCurrentlyAvailable ? 'unavailable' : 'available';
                            var landingPageText = isCurrentlyAvailable
                                ? 'It will be hidden from the landing page until you make it available again.'
                                : 'It will appear on the landing page again.';
                            showAdminConfirm('Mark "' + spotName + '" as ' + actionLabel + '? ' + landingPageText).then(function(confirmed) {
                                if (!confirmed) return;
                                this.disabled = true;
                                this.textContent = 'Updating…';
                                var form = new FormData();
                                form.append('destination_id', id);
                                form.append('is_available', nextAvailability);
                                fetch('delete_spot.php', { method: 'POST', credentials: 'same-origin', body: form })
                                    .then(function(r) { return r.json(); })
                                    .then(function(res) {
                                        if (res.ok && row) {
                                            var isAvailableNow = String(res.is_available) !== '0';
                                            row.setAttribute('data-is-available', isAvailableNow ? '1' : '0');
                                            this.setAttribute('data-is-available', isAvailableNow ? '1' : '0');
                                            this.disabled = false;
                                            this.textContent = isAvailableNow ? 'Unavailable' : 'Available';
                                            this.classList.toggle('spot-toggle-available-btn', !isAvailableNow);
                                            var badge = row.querySelector('.spot-status-badge');
                                            if (badge) {
                                                badge.textContent = isAvailableNow ? 'Available' : 'Unavailable';
                                                badge.classList.toggle('spot-status-available', isAvailableNow);
                                                badge.classList.toggle('spot-status-unavailable', !isAvailableNow);
                                            }
                                        } else {
                                            showAdminNotice(res.error || 'Could not update this spot.');
                                            this.disabled = false;
                                            this.textContent = isCurrentlyAvailable ? 'Unavailable' : 'Available';
                                        }
                                    }.bind(this))
                                    .catch(function() {
                                        showAdminNotice('Request failed.');
                                        this.disabled = false;
                                        this.textContent = isCurrentlyAvailable ? 'Unavailable' : 'Available';
                                    }.bind(this));
                            }.bind(this));
                        });
                    });
                })
                .catch(function() {
                    loading.style.display = 'none';
                    if (empty) { empty.innerHTML = 'Could not load spots. Request failed or timed out. Open this page through your local server and ensure Apache and MySQL are running.'; empty.style.display = 'block'; }
                });
        }

        var reviewsAdminData = [];

        function getReviewStatusBadgeClass(status) {
            switch (String(status || 'visible').toLowerCase()) {
                case 'reported':
                    return 'badge-review-reported';
                case 'hidden':
                    return 'badge-review-hidden';
                default:
                    return 'badge-review-visible';
            }
        }

        function formatReviewStars(rating) {
            rating = Number(rating || 0);
            return (rating >= 1 && rating <= 5) ? ('★'.repeat(rating) + ' ' + rating) : '—';
        }

        function truncateReviewText(value, maxLength) {
            value = value || '';
            return value.length > maxLength ? (value.substring(0, maxLength - 1) + '…') : value;
        }

        function refreshReviewDashboard() {
            loadReviewsAdmin();
            loadReviewSummary();
            loadTopRatedGuides();
            loadReportedReviews();
        }

        function bindReviewDashboardControls() {
            var search = document.getElementById('reviewsAdminSearch');
            var typeFilter = document.getElementById('reviewsAdminTypeFilter');
            var ratingFilter = document.getElementById('reviewsAdminRatingFilter');
            var sort = document.getElementById('reviewsAdminSort');
            var topGuidesSort = document.getElementById('topGuidesSort');
            var topGuidesMinReviews = document.getElementById('topGuidesMinReviews');

            if (search && !search.dataset.bound) {
                search.addEventListener('input', renderReviewsAdminTable);
                search.dataset.bound = '1';
            }
            if (typeFilter && !typeFilter.dataset.bound) {
                typeFilter.addEventListener('change', renderReviewsAdminTable);
                typeFilter.dataset.bound = '1';
            }
            if (ratingFilter && !ratingFilter.dataset.bound) {
                ratingFilter.addEventListener('change', renderReviewsAdminTable);
                ratingFilter.dataset.bound = '1';
            }
            if (sort && !sort.dataset.bound) {
                sort.addEventListener('change', renderReviewsAdminTable);
                sort.dataset.bound = '1';
            }
            if (topGuidesSort && !topGuidesSort.dataset.bound) {
                topGuidesSort.addEventListener('change', loadTopRatedGuides);
                topGuidesSort.dataset.bound = '1';
            }
            if (topGuidesMinReviews && !topGuidesMinReviews.dataset.bound) {
                topGuidesMinReviews.addEventListener('change', loadTopRatedGuides);
                topGuidesMinReviews.dataset.bound = '1';
            }
        }

        function deleteReviewAdmin(reviewId, button) {
            if (!reviewId) return;
            showAdminConfirm('Remove this review? It will be permanently deleted.').then(function(confirmed) {
                if (!confirmed) return;
                if (button) {
                    button.disabled = true;
                    button.textContent = 'Deleting…';
                }
                var form = new FormData();
                form.append('review_id', reviewId);
                fetch('delete_review_admin.php', { method: 'POST', credentials: 'same-origin', body: form })
                    .then(function(r) {
                        if (r.status === 403) {
                            window.location.href = 'signinTouristAdmin.html';
                            return null;
                        }
                        return r.json();
                    })
                    .then(function(res) {
                        if (!res) return;
                        if (res.ok) {
                            refreshReviewDashboard();
                            showAdminNotice('Review deleted.');
                            return;
                        }
                        showAdminNotice(res.error || 'Could not delete.');
                        if (button) {
                            button.disabled = false;
                            button.textContent = 'Delete';
                        }
                    })
                    .catch(function() {
                        showAdminNotice('Request failed.');
                        if (button) {
                            button.disabled = false;
                            button.textContent = 'Delete';
                        }
                    });
            });
        }

        function dismissReportedReview(reviewId, button) {
            if (!reviewId) return;
            if (button) {
                button.disabled = true;
                button.textContent = 'Dismissing…';
            }
            var form = new FormData();
            form.append('review_id', reviewId);
            form.append('action', 'dismiss');
            fetch('resolve_review_report_admin.php', { method: 'POST', credentials: 'same-origin', body: form })
                .then(function(r) {
                    if (r.status === 403) {
                        window.location.href = 'signinTouristAdmin.html';
                        return null;
                    }
                    return r.json();
                })
                .then(function(res) {
                    if (!res) return;
                    if (res.ok) {
                        refreshReviewDashboard();
                        showAdminNotice('Report dismissed.');
                        return;
                    }
                    showAdminNotice(res.error || 'Could not dismiss report.');
                    if (button) {
                        button.disabled = false;
                        button.textContent = 'Dismiss';
                    }
                })
                .catch(function() {
                    showAdminNotice('Request failed.');
                    if (button) {
                        button.disabled = false;
                        button.textContent = 'Dismiss';
                    }
                });
        }

        function suspendGuideFromReview(guideId, button) {
            guideId = Number(guideId || 0);
            if (!guideId) {
                showAdminNotice('This reported review is not linked to a tour guide.');
                return;
            }

            var daysInput = window.prompt('Suspend this tour guide for how many days? Enter 1, 2, or 3.', '3');
            if (daysInput === null) return;

            var days = Number(String(daysInput).trim());
            if ([1, 2, 3].indexOf(days) === -1) {
                showAdminNotice('Enter only 1, 2, or 3 days.');
                return;
            }

            showAdminConfirm('Suspend this tour guide for ' + days + ' day' + (days === 1 ? '' : 's') + '?').then(function(confirmed) {
                if (!confirmed) return;

                if (button) {
                    button.disabled = true;
                    button.textContent = 'Suspending…';
                }

                var form = new FormData();
                form.append('guide_id', guideId);
                form.append('days', days);

                fetch('suspend_guide.php', { method: 'POST', credentials: 'same-origin', body: form })
                    .then(function(r) {
                        if (r.status === 403) {
                            window.location.href = 'signinTouristAdmin.html';
                            return null;
                        }
                        return r.json();
                    })
                    .then(function(res) {
                        if (!res) return;
                        if (res.ok) {
                            loadAllGuides();
                            loadActiveGuides();
                            loadSuspendedGuides();
                            showAdminNotice('Tour guide suspended for ' + days + ' day' + (days === 1 ? '' : 's') + '.');
                            return;
                        }
                        showAdminNotice(res.error || 'Could not suspend this tour guide.');
                        if (button) {
                            button.disabled = false;
                            button.textContent = 'Suspend guide';
                        }
                    })
                    .catch(function() {
                        showAdminNotice('Request failed.');
                        if (button) {
                            button.disabled = false;
                            button.textContent = 'Suspend guide';
                        }
                    });
            });
        }

        function renderReviewsAdminTable() {
            var table = document.getElementById('reviewsAdminTable');
            var body = document.getElementById('reviewsAdminBody');
            var empty = document.getElementById('reviewsAdminEmpty');
            var search = document.getElementById('reviewsAdminSearch');
            var typeFilter = document.getElementById('reviewsAdminTypeFilter');
            var ratingFilter = document.getElementById('reviewsAdminRatingFilter');
            var sort = document.getElementById('reviewsAdminSort');

            if (!body || !table || !empty) return;

            var query = search ? String(search.value || '').trim().toLowerCase() : '';
            var typeValue = typeFilter ? typeFilter.value : 'all';
            var ratingValue = ratingFilter ? ratingFilter.value : 'all';
            var sortValue = sort ? sort.value : 'latest';

            var filtered = reviewsAdminData.slice().filter(function(review) {
                var reviewType = String(review.review_type || 'location').toLowerCase();
                var status = String(review.status || 'visible').toLowerCase();
                var textBlob = [
                    review.tourist_name || '',
                    review.guide_name || '',
                    review.location_name || '',
                    review.subject || '',
                    review.comment || ''
                ].join(' ').toLowerCase();

                if (typeValue === 'reported' && status !== 'reported') return false;
                if (typeValue !== 'all' && typeValue !== 'reported' && reviewType !== typeValue) return false;
                if (ratingValue !== 'all' && Number(review.rating || 0) !== Number(ratingValue)) return false;
                if (query && textBlob.indexOf(query) === -1) return false;
                return true;
            });

            filtered.sort(function(a, b) {
                if (sortValue === 'highest') {
                    if (Number(b.rating || 0) !== Number(a.rating || 0)) {
                        return Number(b.rating || 0) - Number(a.rating || 0);
                    }
                } else if (sortValue === 'lowest') {
                    if (Number(a.rating || 0) !== Number(b.rating || 0)) {
                        return Number(a.rating || 0) - Number(b.rating || 0);
                    }
                }
                return new Date(b.created_at || 0).getTime() - new Date(a.created_at || 0).getTime();
            });

            if (filtered.length === 0) {
                table.style.display = 'none';
                empty.textContent = reviewsAdminData.length === 0 ? 'No tourist reviews yet.' : 'No reviews match the selected filters.';
                empty.style.display = 'block';
                body.innerHTML = '';
                return;
            }

            empty.style.display = 'none';
            table.style.display = 'table';
            body.innerHTML = filtered.map(function(review) {
                var commentShort = truncateReviewText(review.comment || '—', 80);
                var replyShort = truncateReviewText(review.reply_text || '—', 50);
                var subject = [review.guide_name || '', review.location_name || ''].filter(Boolean).join(' @ ');
                if (!subject) subject = review.subject || '—';
                var status = String(review.status || 'visible');
                return '<tr data-review-id="' + review.review_id + '">' +
                    '<td>' + escapeHtml(review.tourist_name || '—') + '</td>' +
                    '<td>' + escapeHtml(subject) + ' <small>(' + escapeHtml(String(review.review_type || 'location').toUpperCase()) + ')</small></td>' +
                    '<td>' + escapeHtml(formatReviewStars(review.rating)) + '</td>' +
                    '<td title="' + escapeAttr(review.comment || '') + '">' + escapeHtml(commentShort) + '</td>' +
                    '<td title="' + escapeAttr(review.reply_text || '') + '">' + escapeHtml(replyShort) + '</td>' +
                    '<td>' + escapeHtml(review.created_at || '') + '</td>' +
                    '<td><span class="badge ' + getReviewStatusBadgeClass(status) + '">' + escapeHtml(status.charAt(0).toUpperCase() + status.slice(1)) + '</span></td>' +
                    '<td><button type="button" class="delete-review-admin-btn" data-review-id="' + review.review_id + '">Delete</button></td>' +
                    '</tr>';
            }).join('');

            body.querySelectorAll('.delete-review-admin-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    deleteReviewAdmin(this.getAttribute('data-review-id'), this);
                });
            });
        }

        function loadReviewsAdmin() {
            var loading = document.getElementById('reviewsAdminLoading');
            var table = document.getElementById('reviewsAdminTable');
            var empty = document.getElementById('reviewsAdminEmpty');
            if (!loading) return;
            loading.style.display = 'block';
            if (table) table.style.display = 'none';
            if (empty) empty.style.display = 'none';
            fetchWithTimeout('get_reviews_admin.php', { credentials: 'same-origin' })
                .then(function(r) {
                    if (r.status === 403) {
                        window.location.href = 'signinTouristAdmin.html';
                        return [];
                    }
                    return r.json();
                })
                .then(function(data) {
                    loading.style.display = 'none';
                    if (!Array.isArray(data)) {
                        reviewsAdminData = [];
                        if (empty) {
                            empty.textContent = 'Could not load reviews.';
                            empty.style.display = 'block';
                        }
                        return;
                    }
                    reviewsAdminData = data;
                    renderReviewsAdminTable();
                })
                .catch(function() {
                    loading.style.display = 'none';
                    reviewsAdminData = [];
                    if (empty) {
                        empty.innerHTML = 'Could not load reviews. Request failed or timed out.';
                        empty.style.display = 'block';
                    }
                });
        }

        function loadReviewSummary() {
            var loading = document.getElementById('reviewSummaryLoading');
            var content = document.getElementById('reviewSummaryContent');
            var empty = document.getElementById('reviewSummaryEmpty');
            var distribution = document.getElementById('reviewDistribution');
            if (!loading || !content || !empty) return;

            loading.style.display = 'block';
            content.style.display = 'none';
            empty.style.display = 'none';

            fetchWithTimeout('get_review_summary_admin.php', { credentials: 'same-origin' })
                .then(function(r) {
                    if (r.status === 403) {
                        window.location.href = 'signinTouristAdmin.html';
                        return null;
                    }
                    return r.json();
                })
                .then(function(data) {
                    loading.style.display = 'none';
                    if (!data || Number(data.total_reviews || 0) === 0) {
                        empty.style.display = 'block';
                        return;
                    }

                    document.getElementById('reviewTotalCount').textContent = Number(data.total_reviews || 0);
                    document.getElementById('reviewAverageRating').textContent = Number(data.average_rating || 0).toFixed(1);
                    document.getElementById('reviewReportedCount').textContent = Number(data.reported_reviews || 0);
                    document.getElementById('reviewGuideCount').textContent = Number(data.guide_reviews || 0);
                    document.getElementById('reviewLocationCount').textContent = Number(data.location_reviews || 0);
                    document.getElementById('reviewSummaryUpdated').textContent = data.latest_review_at ? ('Latest review: ' + data.latest_review_at) : '';

                    var total = Number(data.total_reviews || 0);
                    var dist = data.distribution || {};
                    if (distribution) {
                        distribution.innerHTML = [5, 4, 3, 2, 1].map(function(star) {
                            var count = Number(dist[String(star)] || 0);
                            var percent = total > 0 ? Math.round((count / total) * 100) : 0;
                            return '<div class="review-distribution-row">' +
                                '<span class="review-distribution-label">' + star + ' star</span>' +
                                '<span class="review-distribution-bar"><span class="review-distribution-fill" style="width:' + percent + '%;"></span></span>' +
                                '<span class="review-distribution-count">' + count + '</span>' +
                                '</div>';
                        }).join('');
                    }

                    content.style.display = 'block';
                })
                .catch(function() {
                    loading.style.display = 'none';
                    empty.textContent = 'Could not load rating summary.';
                    empty.style.display = 'block';
                });
        }

        function loadTopRatedGuides() {
            var loading = document.getElementById('topGuidesLoading');
            var list = document.getElementById('topGuidesList');
            var empty = document.getElementById('topGuidesEmpty');
            var sort = document.getElementById('topGuidesSort');
            var minReviews = document.getElementById('topGuidesMinReviews');
            if (!loading || !list || !empty) return;

            loading.style.display = 'block';
            list.style.display = 'none';
            empty.style.display = 'none';

            var query = '?sort=' + encodeURIComponent(sort ? sort.value : 'highest') +
                '&min_reviews=' + encodeURIComponent(minReviews ? minReviews.value : '1') +
                '&limit=8';

            fetchWithTimeout('get_top_rated_guides.php' + query, { credentials: 'same-origin' })
                .then(function(r) {
                    if (r.status === 403) {
                        window.location.href = 'signinTouristAdmin.html';
                        return null;
                    }
                    return r.json();
                })
                .then(function(data) {
                    loading.style.display = 'none';
                    var guides = data && Array.isArray(data.guides) ? data.guides : [];
                    if (!guides.length) {
                        empty.style.display = 'block';
                        return;
                    }

                    list.innerHTML = guides.map(function(guide, index) {
                        var avg = Number(guide.avg_rating || 0);
                        var stars = avg > 0 ? '★'.repeat(Math.max(1, Math.round(avg))) : '—';
                        var reviewCount = Number(guide.review_count || 0);
                        var reviewsLabel = reviewCount === 1 ? '1 review' : (reviewCount + ' reviews');
                        return '<div class="top-guide-card">' +
                            '<span class="top-guide-rank">#' + (index + 1) + '</span>' +
                            '<div>' +
                            '<strong>' + escapeHtml(guide.guide_name || 'Guide') + '</strong>' +
                            '<div class="top-guide-stars">' + escapeHtml(stars) + ' <span>' + escapeHtml(avg.toFixed(1)) + '</span></div>' +
                            '<div class="top-guide-meta">' + escapeHtml(reviewsLabel) + (guide.last_review_at ? (' · last review ' + escapeHtml(guide.last_review_at)) : '') + '</div>' +
                            '</div>' +
                            '</div>';
                    }).join('');
                    list.style.display = 'grid';
                })
                .catch(function() {
                    loading.style.display = 'none';
                    empty.textContent = 'Could not load top-rated guides.';
                    empty.style.display = 'block';
                });
        }

        function loadReportedReviews() {
            var loading = document.getElementById('reportedReviewsLoading');
            var table = document.getElementById('reportedReviewsTable');
            var body = document.getElementById('reportedReviewsBody');
            var empty = document.getElementById('reportedReviewsEmpty');
            if (!loading || !table || !body || !empty) return;

            loading.style.display = 'block';
            table.style.display = 'none';
            empty.style.display = 'none';

            fetchWithTimeout('get_reported_reviews_admin.php', { credentials: 'same-origin' })
                .then(function(r) {
                    if (r.status === 403) {
                        window.location.href = 'signinTouristAdmin.html';
                        return null;
                    }
                    return r.json();
                })
                .then(function(data) {
                    loading.style.display = 'none';
                    var reviews = data && Array.isArray(data.reviews) ? data.reviews : [];
                    if (!reviews.length) {
                        empty.style.display = 'block';
                        return;
                    }

                    table.style.display = 'table';
                    body.innerHTML = reviews.map(function(review) {
                        var guideId = Number(review.guide_id || 0);
                        var suspendButton = guideId > 0
                            ? '<button type="button" class="suspend-guide-review-btn" data-guide-id="' + guideId + '">Suspend guide</button>'
                            : '';
                        return '<tr data-review-id="' + review.review_id + '">' +
                            '<td>' + escapeHtml(review.tourist_name || '—') + '</td>' +
                            '<td>' + escapeHtml(review.subject || '—') + ' <small>(' + escapeHtml(String(review.review_type || 'location').toUpperCase()) + ')</small></td>' +
                            '<td>' + escapeHtml(formatReviewStars(review.rating)) + '</td>' +
                            '<td title="' + escapeAttr(review.comment || '') + '">' + escapeHtml(truncateReviewText(review.comment || '—', 90)) + '</td>' +
                            '<td>' + escapeHtml(review.created_at || '') + '</td>' +
                            '<td><span class="review-actions-inline">' +
                            '<button type="button" class="dismiss-report-btn" data-review-id="' + review.review_id + '">Dismiss</button>' +
                            suspendButton +
                            '<button type="button" class="delete-review-admin-btn" data-review-id="' + review.review_id + '">Delete</button>' +
                            '</span></td>' +
                            '</tr>';
                    }).join('');

                    body.querySelectorAll('.dismiss-report-btn').forEach(function(btn) {
                        btn.addEventListener('click', function() {
                            dismissReportedReview(this.getAttribute('data-review-id'), this);
                        });
                    });
                    body.querySelectorAll('.delete-review-admin-btn').forEach(function(btn) {
                        btn.addEventListener('click', function() {
                            deleteReviewAdmin(this.getAttribute('data-review-id'), this);
                        });
                    });
                    body.querySelectorAll('.suspend-guide-review-btn').forEach(function(btn) {
                        btn.addEventListener('click', function() {
                            suspendGuideFromReview(this.getAttribute('data-guide-id'), this);
                        });
                    });
                })
                .catch(function() {
                    loading.style.display = 'none';
                    empty.textContent = 'Could not load reported reviews.';
                    empty.style.display = 'block';
                });
        }

        function escapeAttr(s) {
            if (s == null) return '';
            var div = document.createElement('div');
            div.textContent = s;
            return div.innerHTML.replace(/"/g, '&quot;');
        }

        function updateDashboardDate() {
            var dateEl = document.getElementById('dashboardCurrentDate');
            if (!dateEl) return;
            dateEl.textContent = new Date().toLocaleDateString('en-US', {
                month: 'long',
                day: 'numeric',
                year: 'numeric'
            }).toUpperCase();
        }

        function loadAdminStats() {
            var totalUsers = document.getElementById('statTotalUsers');
            var totalGuides = document.getElementById('statTotalGuides');
            var totalDest = document.getElementById('statTotalDestinations');
            fetchWithTimeout('get_admin_stats.php', { credentials: 'same-origin' })
                .then(function(r) {
                    if (r.status === 403) return null;
                    return r.json();
                })
                .then(function(data) {
                    if (!data) return;
                    if (totalUsers) totalUsers.textContent = (data.total_users != null) ? Number(data.total_users).toLocaleString() : '—';
                    if (totalGuides) totalGuides.textContent = (data.total_guides != null) ? Number(data.total_guides).toLocaleString() : '—';
                    if (totalDest) totalDest.textContent = (data.total_destinations != null) ? Number(data.total_destinations).toLocaleString() : '—';
                })
                .catch(function() {});
        }

        function getSecurityBadgeClass(status) {
            switch (status) {
                case 'Implemented':
                    return 'badge-security-ok';
                case 'Needs attention':
                    return 'badge-security-alert';
                default:
                    return 'badge-security-partial';
            }
        }

        function getMaintenanceBadgeClass(status) {
            switch (status) {
                case 'Implemented':
                    return 'badge-security-ok';
                case 'Needs attention':
                    return 'badge-security-alert';
                default:
                    return 'badge-security-partial';
            }
        }

        function loadSecurityStatus() {
            var loading = document.getElementById('securityStatusLoading');
            var table = document.getElementById('securityStatusTable');
            var body = document.getElementById('securityStatusBody');
            var empty = document.getElementById('securityStatusEmpty');
            var summaryLoading = document.getElementById('securitySummaryLoading');
            var summaryContent = document.getElementById('securitySummaryContent');
            var summaryEmpty = document.getElementById('securitySummaryEmpty');

            if (loading) loading.style.display = 'block';
            if (table) table.style.display = 'none';
            if (empty) empty.style.display = 'none';
            if (summaryLoading) summaryLoading.style.display = 'block';
            if (summaryContent) summaryContent.style.display = 'none';
            if (summaryEmpty) summaryEmpty.style.display = 'none';

            fetchWithTimeout('get_security_accessibility_status.php', { credentials: 'same-origin' })
                .then(function(r) {
                    if (r.status === 403) {
                        window.location.href = 'signinTouristAdmin.html';
                        return null;
                    }
                    return r.json();
                })
                .then(function(data) {
                    if (!data) return;

                    var items = Array.isArray(data.items) ? data.items : [];
                    var summary = data.summary || {};

                    if (loading) loading.style.display = 'none';
                    if (summaryLoading) summaryLoading.style.display = 'none';

                    if (!items.length) {
                        if (empty) empty.style.display = 'block';
                        if (summaryEmpty) summaryEmpty.style.display = 'block';
                        return;
                    }

                    if (table) table.style.display = 'table';
                    if (body) {
                        body.innerHTML = items.map(function(item) {
                            return '<tr>' +
                                '<td><b>' + escapeHtml(item.label || '') + '</b></td>' +
                                '<td><span class="badge ' + getSecurityBadgeClass(item.status) + '">' + escapeHtml(item.status || 'Partial') + '</span></td>' +
                                '<td>' + escapeHtml(item.detail || '') + '</td>' +
                                '</tr>';
                        }).join('');
                    }

                    if (summaryContent) summaryContent.style.display = 'block';
                    var implementedCount = document.getElementById('securityImplementedCount');
                    var partialCount = document.getElementById('securityPartialCount');
                    var attentionCount = document.getElementById('securityAttentionCount');
                    var updated = document.getElementById('securitySummaryUpdated');
                    var recommendations = document.getElementById('securityRecommendations');

                    if (implementedCount) implementedCount.textContent = Number(summary.implemented || 0);
                    if (partialCount) partialCount.textContent = Number(summary.partial || 0);
                    if (attentionCount) attentionCount.textContent = Number(summary.needs_attention || 0);
                    if (updated) updated.textContent = summary.updated_at ? ('Last checked: ' + summary.updated_at) : '';

                    if (recommendations) {
                        var recs = Array.isArray(summary.recommendations) ? summary.recommendations : [];
                        recommendations.innerHTML = recs.map(function(item) {
                            return '<div class="feed-item ' + (item.variant ? escapeHtml(item.variant) : '') + '">' +
                                '<p><b>' + escapeHtml(item.title || '') + '</b><br>' + escapeHtml(item.detail || '') + '</p>' +
                                '</div>';
                        }).join('');
                    }
                })
                .catch(function() {
                    if (loading) loading.style.display = 'none';
                    if (summaryLoading) summaryLoading.style.display = 'none';
                    if (empty) empty.style.display = 'block';
                    if (summaryEmpty) summaryEmpty.style.display = 'block';
                });
        }

        function loadSystemMaintenanceStatus() {
            var loading = document.getElementById('maintenanceStatusLoading');
            var table = document.getElementById('maintenanceStatusTable');
            var body = document.getElementById('maintenanceStatusBody');
            var empty = document.getElementById('maintenanceStatusEmpty');
            var actionsLoading = document.getElementById('maintenanceActionsLoading');
            var actionsContent = document.getElementById('maintenanceActionsContent');
            var actionsEmpty = document.getElementById('maintenanceActionsEmpty');

            if (loading) loading.style.display = 'block';
            if (table) table.style.display = 'none';
            if (empty) empty.style.display = 'none';
            if (actionsLoading) actionsLoading.style.display = 'block';
            if (actionsContent) actionsContent.style.display = 'none';
            if (actionsEmpty) actionsEmpty.style.display = 'none';

            fetchWithTimeout('get_system_maintenance_status.php', { credentials: 'same-origin' })
                .then(function(r) {
                    if (r.status === 403) {
                        window.location.href = 'signinTouristAdmin.html';
                        return null;
                    }
                    return r.json();
                })
                .then(function(data) {
                    if (!data) return;

                    var items = Array.isArray(data.items) ? data.items : [];
                    var summary = data.summary || {};
                    var log = data.log || {};
                    var bookingCounts = data.booking_counts || {};

                    if (loading) loading.style.display = 'none';
                    if (actionsLoading) actionsLoading.style.display = 'none';

                    if (!items.length) {
                        if (empty) empty.style.display = 'block';
                        if (actionsEmpty) actionsEmpty.style.display = 'block';
                        return;
                    }

                    if (table) table.style.display = 'table';
                    if (body) {
                        body.innerHTML = items.map(function(item) {
                            return '<tr>' +
                                '<td><b>' + escapeHtml(item.label || '') + '</b></td>' +
                                '<td><span class="badge ' + getMaintenanceBadgeClass(item.status) + '">' + escapeHtml(item.status || 'Partial') + '</span></td>' +
                                '<td>' + escapeHtml(item.detail || '') + '</td>' +
                                '</tr>';
                        }).join('');
                    }

                    if (actionsContent) actionsContent.style.display = 'block';

                    var logSize = document.getElementById('maintenanceLogSize');
                    var pending = document.getElementById('maintenancePendingBookings');
                    var approved = document.getElementById('maintenanceApprovedBookings');
                    var completed = document.getElementById('maintenanceCompletedBookings');
                    var updated = document.getElementById('maintenanceUpdated');
                    var logMeta = document.getElementById('maintenanceLogMeta');
                    var bookingMeta = document.getElementById('maintenanceBookingMeta');
                    var recommendations = document.getElementById('maintenanceRecommendations');
                    var refreshBtn = document.getElementById('refreshMaintenanceBtn');
                    var clearBtn = document.getElementById('clearDebugLogBtn');

                    if (logSize) logSize.textContent = log.size_human || '0 B';
                    if (pending) pending.textContent = Number(bookingCounts.pending || 0);
                    if (approved) approved.textContent = Number(bookingCounts.approved || 0);
                    if (completed) completed.textContent = Number(bookingCounts.completed || 0);
                    if (updated) updated.textContent = summary.updated_at ? ('Last checked: ' + summary.updated_at) : '';
                    if (logMeta) {
                        logMeta.textContent = log.exists
                            ? ('Debug log ready: ' + (log.modified_at ? ('last updated ' + log.modified_at) : 'timestamp unavailable'))
                            : 'Debug log file has not been created yet.';
                    }
                    if (bookingMeta) {
                        var otherCount = Number(bookingCounts.other || 0);
                        bookingMeta.textContent = 'Booking states tracked: Pending ' + Number(bookingCounts.pending || 0) +
                            ', Approved ' + Number(bookingCounts.approved || 0) +
                            ', Completed ' + Number(bookingCounts.completed || 0) +
                            (otherCount > 0 ? (', Other ' + otherCount) : '');
                    }
                    if (recommendations) {
                        var recs = Array.isArray(summary.recommendations) ? summary.recommendations : [];
                        recommendations.innerHTML = recs.map(function(item) {
                            return '<div class="feed-item ' + (item.variant ? escapeHtml(item.variant) : '') + '">' +
                                '<p><b>' + escapeHtml(item.title || '') + '</b><br>' + escapeHtml(item.detail || '') + '</p>' +
                                '</div>';
                        }).join('');
                    }

                    if (refreshBtn) {
                        refreshBtn.disabled = false;
                        refreshBtn.onclick = function() {
                            refreshBtn.disabled = true;
                            loadSystemMaintenanceStatus();
                        };
                    }

                    if (clearBtn) {
                        clearBtn.disabled = !log.can_clear;
                        clearBtn.textContent = log.exists ? 'Clear debug log' : 'Create empty debug log';
                        clearBtn.onclick = function() {
                            showAdminConfirm('Clear the project debug log? This only removes the current log contents.').then(function(confirmed) {
                                if (!confirmed) return;
                                clearBtn.disabled = true;
                                clearBtn.textContent = 'Clearing…';
                                fetch('clear_debug_log.php', { method: 'POST', credentials: 'same-origin' })
                                    .then(function(r) {
                                        if (r.status === 403) {
                                            window.location.href = 'signinTouristAdmin.html';
                                            return null;
                                        }
                                        return r.json();
                                    })
                                    .then(function(res) {
                                        if (!res) return;
                                        if (res.ok) {
                                            showAdminNotice(res.message || 'Debug log cleared.');
                                            loadSystemMaintenanceStatus();
                                        } else {
                                            showAdminNotice(res.error || 'Could not clear debug log.');
                                            clearBtn.disabled = false;
                                            clearBtn.textContent = 'Clear debug log';
                                        }
                                    })
                                    .catch(function() {
                                        showAdminNotice('Request failed.');
                                        clearBtn.disabled = false;
                                        clearBtn.textContent = 'Clear debug log';
                                    });
                            });
                        };
                    }
                })
                .catch(function() {
                    if (loading) loading.style.display = 'none';
                    if (actionsLoading) actionsLoading.style.display = 'none';
                    if (empty) empty.style.display = 'block';
                    if (actionsEmpty) actionsEmpty.style.display = 'block';
                });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                updateDashboardDate();
                setInterval(updateDashboardDate, 60000);
                bindReviewDashboardControls();
                loadAdminStats();
                loadAllGuides();
                loadPending();
                loadPendingBookings();
                loadApprovedBookings();
                loadActiveGuides();
                loadSuspendedGuides();
                loadSpotsPrice();
                loadReviewsAdmin();
                loadReviewSummary();
                loadTopRatedGuides();
                loadReportedReviews();
                loadSecurityStatus();
                loadSystemMaintenanceStatus();
            });
        } else {
            updateDashboardDate();
            setInterval(updateDashboardDate, 60000);
            bindReviewDashboardControls();
            loadAdminStats();
            loadAllGuides();
            loadPending();
            loadPendingBookings();
            loadApprovedBookings();
            loadActiveGuides();
            loadSuspendedGuides();
            loadSpotsPrice();
            loadReviewsAdmin();
            loadReviewSummary();
            loadTopRatedGuides();
            loadReportedReviews();
            loadSecurityStatus();
            loadSystemMaintenanceStatus();
        }
    })();
    </script>
</body>
</html>
