/**
 * My Trips Dashboard Controller
 * Renders the signed-in tourist's actual guide bookings.
 */
(function() {
    let tripsData = [];
    let currentFilter = 'all';

    const grid = document.getElementById('tripsGridContainer');
    const filterTabs = document.querySelectorAll('.filter-tab');
    const toast = document.getElementById('toastMessage');
    const profileNameEl = document.getElementById('navProfileName');
    const profileAvatarEl = document.querySelector('.nav-avatar');
    const addNewTripBtn = document.getElementById('addNewTripBtn');
    const tripMessagesModal = document.getElementById('tripMessagesModal');
    const tripMessagesTitle = document.getElementById('tripMessagesTitle');
    const tripMessagesSubtitle = document.getElementById('tripMessagesSubtitle');
    const tripMessagesThread = document.getElementById('tripMessagesThread');
    const tripMessagesClose = document.getElementById('tripMessagesClose');
    const tripMessageInput = document.getElementById('tripMessageInput');
    const tripMessageStatus = document.getElementById('tripMessageStatus');
    const tripMessageSendBtn = document.getElementById('tripMessageSendBtn');
    let activeTripConversation = null;
    let tripMessageRefreshTimer = null;

    function initUserData() {
        if (!profileNameEl && !profileAvatarEl) {
            return;
        }

        const role = localStorage.getItem('role') || '';
        const userId = localStorage.getItem('userId') || '';
        const scopedFullName = (role && userId) ? localStorage.getItem(`profileName:${role}:${userId}`) : '';
        const scopedFirstName = (role && userId) ? localStorage.getItem(`firstName:${role}:${userId}`) : '';
        const scopedLastName = (role && userId) ? localStorage.getItem(`lastName:${role}:${userId}`) : '';
        profileNameEl.textContent = scopedFullName || [scopedFirstName, scopedLastName].filter(Boolean).join(' ').trim() || localStorage.getItem('fullName') || 'Guest Traveler';

        const scopedProfileImage = (role && userId) ? localStorage.getItem(`profileImage:${role}:${userId}`) : '';
        if (profileAvatarEl && (scopedProfileImage || localStorage.getItem('profileImage'))) {
            profileAvatarEl.src = scopedProfileImage || localStorage.getItem('profileImage');
        }
    }

    async function hydrateUserDataFromSession() {
        try {
            const response = await fetch('get_user.php', { credentials: 'same-origin' });
            if (!response.ok) {
                return;
            }

            const data = await response.json();
            if (!data || !data.success || !data.role || !data.user_id) {
                return;
            }

            const role = String(data.role);
            const userId = String(data.user_id);
            const firstName = String(data.first_name || '');
            const lastName = String(data.last_name || '');
            const fullName = String(data.full_name || '').trim() || 'Guest Traveler';
            const profileImage = String(data.profile_image || '');

            localStorage.setItem('role', role);
            localStorage.setItem('userId', userId);
            localStorage.setItem('firstName', firstName);
            localStorage.setItem('lastName', lastName);
            localStorage.setItem('fullName', fullName);
            localStorage.setItem(`firstName:${role}:${userId}`, firstName);
            localStorage.setItem(`lastName:${role}:${userId}`, lastName);
            localStorage.setItem(`profileName:${role}:${userId}`, fullName);
            if (profileImage) {
                localStorage.setItem(`profileImage:${role}:${userId}`, profileImage);
                localStorage.setItem('profileImage', profileImage);
            }

            initUserData();
        } catch (_) {}
    }

    function showToast(text) {
        if (!toast) return;
        toast.textContent = text || 'Trip updated';
        toast.classList.add('show');
        setTimeout(function() {
            toast.classList.remove('show');
        }, 3000);
    }

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML;
    }

    function formatDateTime(value) {
        if (!value) return 'Not set yet';
        const normalized = String(value).replace(' ', 'T');
        const date = new Date(normalized);
        if (isNaN(date.getTime())) return String(value);
        return date.toLocaleString(undefined, {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: 'numeric',
            minute: '2-digit'
        });
    }

    function formatMessageDateTime(value) {
        if (!value) return '';
        const normalized = String(value).replace(' ', 'T');
        const date = new Date(normalized);
        if (isNaN(date.getTime())) return String(value);
        return date.toLocaleString(undefined, {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: 'numeric',
            minute: '2-digit'
        });
    }

    function getTripImageStyle(status) {
        if (status === 'Approved') {
            return 'linear-gradient(145deg, #1f7a4d, #2aa86b)';
        }
        if (status === 'Completed') {
            return 'linear-gradient(145deg, #17678c, #39a0ca)';
        }
        return 'linear-gradient(145deg, #7a5d3c, #ab8b67)';
    }

    function getUiStatus(status) {
        if (status === 'Approved') {
            return {
                filterStatus: 'planned',
                badgeClass: 'planned',
                badgeText: 'Guide Available',
                titlePrefix: 'Meet with',
                summary: 'Your guide is available. Open Messages to confirm your meeting time and place.'
            };
        }
        if (status === 'Completed') {
            return {
                filterStatus: 'completed',
                badgeClass: 'completed',
                badgeText: 'Completed',
                titlePrefix: 'Trip with',
                summary: 'This guide booking has already been completed.'
            };
        }
        return {
            filterStatus: 'planned',
            badgeClass: 'planned',
            badgeText: 'Pending Approval',
            titlePrefix: 'Request for',
            summary: 'Waiting for admin approval. Your guide will appear as available here once approved.'
        };
    }

    function renderEmptyState(message) {
        if (!grid) return;
        grid.innerHTML = `
            <div class="empty-state">
                <i class='bx bx-compass' style="font-size:3rem;"></i>
                <p>${message}</p>
            </div>`;
    }

    function renderTrips() {
        if (!grid) return;

        const filtered = tripsData.filter(function(trip) {
            return currentFilter === 'all' || trip.filterStatus === currentFilter;
        });

        if (filtered.length === 0) {
            const emptyMessage = currentFilter === 'completed'
                ? 'No completed guide trips yet.'
                : currentFilter === 'planned'
                ? 'No active guide bookings yet. Book a guide to see your schedule here.'
                : 'No guide bookings yet. Book a guide to start your trip.';
            renderEmptyState(emptyMessage);
            return;
        }

        grid.innerHTML = filtered.map(function(trip) {
            return `
                <div class="trip-card" data-id="${trip.id}">
                    <div class="trip-img" style="background-image: ${trip.imageStyle};">
                        <span class="trip-badge ${trip.badgeClass}">${escapeHtml(trip.badgeText)}</span>
                    </div>
                    <div class="trip-info">
                        <div class="trip-title">
                            <i class='bx bx-map-pin'></i>
                            <h3>${escapeHtml(trip.name)}</h3>
                        </div>
                        <p style="color:#315879; line-height:1.6;">${escapeHtml(trip.summary)}</p>
                        <div class="trip-details">
                            <span><i class='bx bx-user'></i> ${escapeHtml(trip.guideName)}</span>
                            <span><i class='bx bx-calendar'></i> ${escapeHtml(trip.meetTime)}</span>
                            ${trip.meetingLocation ? `<span><i class='bx bx-current-location'></i> ${escapeHtml(trip.meetingLocation)}</span>` : ''}
                            <span><i class='bx bx-time-five'></i> ${escapeHtml(trip.metaTime)}</span>
                        </div>
                        <div class="trip-actions">
                            <button class="btn-outline view-trip"><i class='bx bx-show'></i> Details</button>
                            ${trip.canViewMessages ? "<button class=\"btn-outline view-messages\"><i class='bx bx-message-dots'></i> Messages</button>" : ''}
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    function normalizeTrips(bookings) {
        return bookings.map(function(booking) {
            const ui = getUiStatus(booking.status);
            const meetTimeText = booking.meet_time ? formatDateTime(booking.meet_time) : 'To be confirmed with guide';
            const metaTime = booking.status === 'Approved'
                ? 'Approved: ' + formatDateTime(booking.approved_at || booking.created_at)
                : booking.status === 'Completed'
                ? 'Completed booking'
                : 'Requested: ' + formatDateTime(booking.created_at);

            return {
                id: String(booking.booking_id),
                bookingId: Number(booking.booking_id),
                status: booking.status,
                filterStatus: ui.filterStatus,
                badgeClass: ui.badgeClass,
                badgeText: ui.badgeText,
                guideName: booking.guide_name || 'Guide',
                name: ui.titlePrefix + ' ' + (booking.guide_name || 'Guide'),
                summary: ui.summary,
                meetTime: meetTimeText,
                meetingLocation: booking.meeting_location || '',
                touristMessage: booking.tourist_message || '',
                metaTime: metaTime,
                imageStyle: getTripImageStyle(booking.status),
                canViewMessages: booking.status === 'Approved' || booking.status === 'Completed'
            };
        });
    }

    function openTripMessagesModal() {
        if (!tripMessagesModal) return;
        tripMessagesModal.hidden = false;
    }

    function closeTripMessagesModal() {
        if (!tripMessagesModal) return;
        tripMessagesModal.hidden = true;
        stopTripMessageRefresh();
        activeTripConversation = null;
        if (tripMessageInput) tripMessageInput.value = '';
        setTripMessageStatus('');
    }

    function setTripMessageStatus(text, type) {
        if (!tripMessageStatus) return;
        tripMessageStatus.textContent = text || '';
        tripMessageStatus.className = 'trip-message-status' + (type ? (' ' + type) : '');
    }

    function updateTripMessageComposer(canSend) {
        if (tripMessageInput) {
            tripMessageInput.disabled = !canSend;
            if (!canSend) {
                tripMessageInput.value = '';
            }
        }
        if (tripMessageSendBtn) {
            tripMessageSendBtn.disabled = !canSend;
            tripMessageSendBtn.textContent = 'Send message';
        }
    }

    function stopTripMessageRefresh() {
        if (tripMessageRefreshTimer) {
            window.clearInterval(tripMessageRefreshTimer);
            tripMessageRefreshTimer = null;
        }
    }

    function startTripMessageRefresh() {
        stopTripMessageRefresh();
        tripMessageRefreshTimer = window.setInterval(function() {
            if (!tripMessagesModal || tripMessagesModal.hidden || !activeTripConversation || !activeTripConversation.bookingId) {
                stopTripMessageRefresh();
                return;
            }

            const trip = tripsData.find(function(item) {
                return Number(item.bookingId) === Number(activeTripConversation.bookingId);
            });

            if (trip) {
                loadTripMessages(trip, { silent: true, keepStatus: true });
            }
        }, 5000);
    }

    function renderTripMessages(messages, guideName) {
        if (!tripMessagesThread) return;
        if (!Array.isArray(messages) || messages.length === 0) {
            tripMessagesThread.innerHTML = '<div class="trip-message-empty">No messages yet. Send your first message to ' + escapeHtml(guideName || 'your guide') + '.</div>';
            return;
        }

        tripMessagesThread.innerHTML = messages.map(function(message) {
            const senderRole = message.sender_role === 'tourist' ? 'tourist' : 'guide';
            const senderLabel = senderRole === 'guide' ? (guideName || 'Guide') : 'You';
            return `
                <div class="trip-message-bubble ${senderRole}">
                    <strong>${escapeHtml(senderLabel)}</strong>
                    <p>${escapeHtml(message.message_text || '')}</p>
                    <small>${escapeHtml(formatMessageDateTime(message.created_at))}</small>
                </div>
            `;
        }).join('');
        tripMessagesThread.scrollTop = tripMessagesThread.scrollHeight;
    }

    async function loadTripMessages(trip, options) {
        if (!trip || !trip.bookingId || !tripMessagesThread) return;
        const isSilentRefresh = !!(options && options.silent);
        const keepStatus = !!(options && options.keepStatus);

        openTripMessagesModal();
        if (tripMessagesTitle) tripMessagesTitle.textContent = 'Messages with ' + (trip.guideName || 'Guide');
        if (tripMessagesSubtitle) tripMessagesSubtitle.textContent = 'Use this chat to coordinate your trip details and reply to your guide.';
        if (!isSilentRefresh) {
            tripMessagesThread.innerHTML = '<div class="trip-message-empty">Loading messages…</div>';
            updateTripMessageComposer(false);
        }
        if (!keepStatus) {
            setTripMessageStatus('');
        }

        try {
            const response = await fetch('get_booking_messages.php?booking_id=' + encodeURIComponent(trip.bookingId), { credentials: 'same-origin' });
            const data = await response.json();
            if (!response.ok || !data.ok) {
                throw new Error((data && data.error) || 'Could not load messages.');
            }
            activeTripConversation = {
                bookingId: Number(data.booking_id || trip.bookingId),
                guideName: data.guide_name || trip.guideName || 'Guide',
                canSend: !!data.can_send
            };
            updateTripMessageComposer(!!data.can_send);
            renderTripMessages(data.messages || [], data.guide_name || trip.guideName);
            if (tripMessagesModal && !tripMessagesModal.hidden) {
                startTripMessageRefresh();
            }
        } catch (error) {
            if (!isSilentRefresh) {
                tripMessagesThread.innerHTML = '<div class="trip-message-empty">' + escapeHtml(error.message || 'Could not load messages.') + '</div>';
                activeTripConversation = null;
                stopTripMessageRefresh();
            }
        }
    }

    async function sendTripMessage() {
        if (!activeTripConversation || !activeTripConversation.bookingId || !tripMessageInput || !tripMessageSendBtn) {
            setTripMessageStatus('Open a booking conversation first.', 'error');
            return;
        }

        const message = (tripMessageInput.value || '').trim();
        if (!message) {
            setTripMessageStatus('Enter a message first.', 'error');
            return;
        }

        const form = new FormData();
        form.append('booking_id', String(activeTripConversation.bookingId));
        form.append('message_text', message);

        tripMessageSendBtn.disabled = true;
        tripMessageSendBtn.textContent = 'Sending...';
        setTripMessageStatus('');
        stopTripMessageRefresh();

        try {
            const response = await fetch('send_booking_message.php', {
                method: 'POST',
                credentials: 'same-origin',
                body: form
            });
            const data = await response.json();
            if (!response.ok || !data.ok) {
                throw new Error((data && data.error) || 'Could not send message.');
            }
            tripMessageInput.value = '';
            setTripMessageStatus(data.message || 'Message sent.', 'success');
            const trip = tripsData.find(function(item) {
                return Number(item.bookingId) === Number(activeTripConversation.bookingId);
            });
            if (trip) {
                await loadTripMessages(trip, { silent: true, keepStatus: true });
                setTripMessageStatus(data.message || 'Message sent.', 'success');
            }
        } catch (error) {
            setTripMessageStatus(error.message || 'Could not send message.', 'error');
        } finally {
            if (tripMessageSendBtn) {
                tripMessageSendBtn.disabled = !activeTripConversation || !activeTripConversation.canSend;
                tripMessageSendBtn.textContent = 'Send message';
            }
        }
    }

    function notifyApprovedBooking(bookings) {
        const approved = bookings.find(function(booking) {
            return booking.status === 'Approved';
        });
        const userId = localStorage.getItem('userId') || 'guest';
        if (!approved || !approved.booking_id) {
            return;
        }

        const key = 'seenApprovedBooking:' + userId + ':' + approved.booking_id;
        if (localStorage.getItem(key) === '1') {
            return;
        }

        localStorage.setItem(key, '1');
        showToast(
            approved.meet_time
                ? ('Your guide is now available. Meet on ' + formatDateTime(approved.meet_time) + (approved.meeting_location ? (' at ' + approved.meeting_location) : '') + '.')
                : 'Your guide is now available. Open Messages to confirm your meeting time and place.'
        );
    }

    async function loadTrips() {
        try {
            const response = await fetch('get_tourist_bookings.php', { credentials: 'same-origin' });
            if (response.status === 403) {
                renderEmptyState('Sign in as a tourist to view your guide bookings.');
                return;
            }

            const data = await response.json();
            const bookings = Array.isArray(data) ? data : [];
            notifyApprovedBooking(bookings);
            tripsData = normalizeTrips(bookings);
            renderTrips();
        } catch (_) {
            renderEmptyState('Could not load your guide bookings right now.');
        }
    }

    function setupEventListeners() {
        if (grid) {
            grid.addEventListener('click', function(e) {
                const card = e.target.closest('.trip-card');
                if (!card) return;
                const tripId = card.dataset.id;
                const trip = tripsData.find(function(item) {
                    return item.id === tripId;
                });
                if (!trip) return;

                if (e.target.closest('.view-trip')) {
                    showToast(trip.name + ' • ' + trip.meetTime + (trip.meetingLocation ? (' • ' + trip.meetingLocation) : ''));
                }

                if (e.target.closest('.view-messages')) {
                    loadTripMessages(trip);
                }
            });
        }

        filterTabs.forEach(function(tab) {
            tab.addEventListener('click', function() {
                filterTabs.forEach(function(item) {
                    item.classList.remove('active');
                });
                tab.classList.add('active');
                currentFilter = tab.getAttribute('data-filter') || 'all';
                renderTrips();
            });
        });

        if (addNewTripBtn) {
            addNewTripBtn.addEventListener('click', function() {
                window.location.href = 'landingpage.html';
            });
        }

        if (tripMessagesClose) {
            tripMessagesClose.addEventListener('click', closeTripMessagesModal);
        }

        if (tripMessageSendBtn) {
            tripMessageSendBtn.addEventListener('click', sendTripMessage);
        }

        if (tripMessageInput) {
            tripMessageInput.addEventListener('keydown', function(e) {
                if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                    e.preventDefault();
                    sendTripMessage();
                }
            });
        }

        if (tripMessagesModal) {
            tripMessagesModal.addEventListener('click', function(e) {
                if (e.target === tripMessagesModal) {
                    closeTripMessagesModal();
                }
            });
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeTripMessagesModal();
            }
        });
    }

    initUserData();
    hydrateUserDataFromSession();
    renderEmptyState('Loading your guide bookings...');
    setupEventListeners();
    loadTrips();
})();