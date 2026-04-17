feather.replace();

    var activeBookingConversation = null;
    var guideMessageRefreshTimer = null;

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function formatMessageDateTime(value) {
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

    function getGuideMessageElements() {
        return {
            modal: document.getElementById('guideMessageModal'),
            title: document.getElementById('guideMessageModalTitle'),
            subtitle: document.getElementById('guideMessageModalSubtitle'),
            thread: document.getElementById('guideMessageThread'),
            input: document.getElementById('bookingMessageInput'),
            status: document.getElementById('bookingMessageStatus'),
            sendBtn: document.getElementById('sendBookingMessageBtn')
        };
    }

    function setGuideMessageStatus(text, type) {
        var els = getGuideMessageElements();
        if (!els.status) return;
        els.status.textContent = text || '';
        els.status.className = 'message-status' + (type ? (' ' + type) : '');
    }

    function stopGuideMessageRefresh() {
        if (guideMessageRefreshTimer) {
            window.clearInterval(guideMessageRefreshTimer);
            guideMessageRefreshTimer = null;
        }
    }

    function startGuideMessageRefresh() {
        stopGuideMessageRefresh();
        guideMessageRefreshTimer = window.setInterval(function() {
            var els = getGuideMessageElements();
            if (!els.modal || els.modal.hidden || !activeBookingConversation || !activeBookingConversation.booking_id) {
                stopGuideMessageRefresh();
                return;
            }

            loadGuideMessages(activeBookingConversation.booking_id, {
                silent: true,
                keepStatus: true
            });
        }, 5000);
    }

    function renderGuideMessageThread(messages, touristName) {
        var els = getGuideMessageElements();
        if (!els.thread) return;
        if (!Array.isArray(messages) || messages.length === 0) {
            els.thread.innerHTML = '<div class="message-empty">No messages yet. Send your first update to ' + escapeHtml(touristName || 'your tourist') + '.</div>';
            return;
        }

        els.thread.innerHTML = messages.map(function(message) {
            var senderRole = message.sender_role === 'guide' ? 'guide' : 'tourist';
            var senderLabel = senderRole === 'guide' ? 'You' : (touristName || 'Tourist');
            return '<div class="message-bubble ' + senderRole + '">' +
                '<strong>' + escapeHtml(senderLabel) + '</strong>' +
                '<p>' + escapeHtml(message.message_text || '') + '</p>' +
                '<small>' + escapeHtml(formatMessageDateTime(message.created_at)) + '</small>' +
                '</div>';
        }).join('');
        els.thread.scrollTop = els.thread.scrollHeight;
    }

    function updateGuideMessageButtons(enabled) {
        var heroBtn = document.getElementById('heroMessageTouristBtn');
        var taskBtn = document.getElementById('bookingMessageBtn');
        if (heroBtn) heroBtn.disabled = !enabled;
        if (taskBtn) taskBtn.disabled = !enabled;
    }

    function openGuideMessageModal() {
        var els = getGuideMessageElements();
        if (!els.modal || !activeBookingConversation || !activeBookingConversation.booking_id) {
            setGuideMessageStatus('No approved booking is available for messaging.', 'error');
            return;
        }
        els.modal.hidden = false;
        setGuideMessageStatus('', '');
        loadGuideMessages(activeBookingConversation.booking_id);
    }

    function closeGuideMessageModal() {
        var els = getGuideMessageElements();
        if (!els.modal) return;
        els.modal.hidden = true;
        stopGuideMessageRefresh();
        if (els.input) els.input.value = '';
        setGuideMessageStatus('', '');
    }

    function loadGuideMessages(bookingId, options) {
        var els = getGuideMessageElements();
        if (!els.thread || !bookingId) return;
        var isSilentRefresh = !!(options && options.silent);
        var keepStatus = !!(options && options.keepStatus);
        if (!isSilentRefresh) {
            els.thread.innerHTML = '<div class="message-empty">Loading messages…</div>';
        }
        if (!keepStatus) {
            setGuideMessageStatus('', '');
        }
        fetch('get_booking_messages.php?booking_id=' + encodeURIComponent(bookingId), { credentials: 'same-origin' })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.ok) {
                    throw new Error(data.error || 'Could not load messages.');
                }
                activeBookingConversation = {
                    booking_id: data.booking_id,
                    tourist_name: data.tourist_name || 'Tourist',
                    guide_name: data.guide_name || 'Guide',
                    status: data.status || 'Approved'
                };
                if (els.title) els.title.textContent = 'Message ' + (activeBookingConversation.tourist_name || 'Tourist');
                if (els.subtitle) els.subtitle.textContent = 'Use this chat to coordinate the meetup and reply to your tourist.';
                renderGuideMessageThread(data.messages || [], activeBookingConversation.tourist_name);
                if (els.modal && !els.modal.hidden) {
                    startGuideMessageRefresh();
                }
            })
            .catch(function(err) {
                if (!isSilentRefresh) {
                    els.thread.innerHTML = '<div class="message-empty">' + escapeHtml((err && err.message) || 'Could not load messages.') + '</div>';
                    stopGuideMessageRefresh();
                }
            });
    }

    function sendGuideMessage() {
        var els = getGuideMessageElements();
        if (!activeBookingConversation || !activeBookingConversation.booking_id || !els.input || !els.sendBtn) {
            setGuideMessageStatus('No booking selected for messaging.', 'error');
            return;
        }

        var message = (els.input.value || '').trim();
        if (!message) {
            setGuideMessageStatus('Enter a message first.', 'error');
            return;
        }

        var form = new FormData();
        form.append('booking_id', String(activeBookingConversation.booking_id));
        form.append('message_text', message);

        els.sendBtn.disabled = true;
        els.sendBtn.textContent = 'Sending...';
        setGuideMessageStatus('', '');
        stopGuideMessageRefresh();

        fetch('send_booking_message.php', {
            method: 'POST',
            credentials: 'same-origin',
            body: form
        })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.ok) {
                    throw new Error(data.error || 'Could not send message.');
                }
                if (els.input) els.input.value = '';
                setGuideMessageStatus(data.message || 'Message sent.', 'success');
                loadGuideMessages(activeBookingConversation.booking_id, {
                    silent: true,
                    keepStatus: true
                });
            })
            .catch(function(err) {
                setGuideMessageStatus((err && err.message) || 'Could not send message.', 'error');
            })
            .finally(function() {
                els.sendBtn.disabled = false;
                els.sendBtn.textContent = 'Send message';
            });
    }

    function clearStoredSessionState() {
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

    function handleLogout() {
        if (typeof showLogoutConfirm === 'function') {
            showLogoutConfirm(function() {
                clearStoredSessionState();
                window.location.href = "logout.php";
            });
        } else {
            clearStoredSessionState();
            window.location.href = "logout.php";
        }
    }

    function setDashboardStats(avgRating, reviewCount) {
        var avgDisplay = document.getElementById('avgRating');
        var countDisplay = document.getElementById('totalReviews');
        var starContainer = document.getElementById('starContainer');
        var ratingText = document.getElementById('ratingText');
        var ratingCountText = document.getElementById('ratingCountText');
        var avg = avgRating != null ? Number(avgRating) : 0;
        var count = reviewCount != null ? parseInt(reviewCount, 10) : 0;
        if (avgDisplay) avgDisplay.textContent = avg > 0 ? avg.toFixed(1) : '0.0';
        if (countDisplay) countDisplay.textContent = count;
        if (starContainer) starContainer.innerHTML = avg > 0 ? '★'.repeat(Math.round(avg)) : '';
        if (ratingText) ratingText.textContent = avg > 0 ? avg.toFixed(1) : '—';
        if (ratingCountText) ratingCountText.textContent = count === 1 ? '(1 review)' : '(' + count + ' reviews)';
    }

    function applyGuideDisplayName(displayName) {
        var safeName = displayName || 'Guide';
        var navWelcomeEl = document.getElementById('guideNavWelcome');
        if (navWelcomeEl) navWelcomeEl.textContent = 'Welcome back Guide, ' + safeName;
    }

    function getStoredGuideDisplayName() {
        var fullName = (localStorage.getItem('fullName') || '').trim();
        if (fullName) return fullName;
        var first = (localStorage.getItem('firstName') || '').trim();
        var last = (localStorage.getItem('lastName') || '').trim();
        return [first, last].filter(Boolean).join(' ').trim() || 'Guide';
    }

    function loadGuideProfile() {
        var guideId = localStorage.getItem('guideId');
        var url = 'get_guide_profile.php';
        if (guideId) url += '?guide_id=' + encodeURIComponent(guideId);
        fetch(url, { credentials: 'same-origin' })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.error) return;
                var yearsEl = document.getElementById('experienceYears');
                var areasEl = document.getElementById('serviceAreas');
                var specEl = document.getElementById('specialization');
                var guideDisplayName = [data.first_name, data.last_name].filter(Boolean).join(' ') || 'Guide';
                if (yearsEl && data.experience_years !== undefined) yearsEl.value = data.experience_years;
                if (areasEl && data.service_areas !== undefined) areasEl.value = data.service_areas || '';
                if (specEl && data.specialization !== undefined) specEl.value = data.specialization || '';
                applyGuideDisplayName(guideDisplayName);
                setDashboardStats(data.avg_rating, data.review_count);
            })
            .catch(function() {});
    }

    function loadTouristReviews() {
        var container = document.getElementById('recentFeedbackList');
        if (!container) return;
        fetch('get_guide_reviews.php', { credentials: 'same-origin' })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                var reviews = data.reviews || [];
                if (reviews.length === 0) {
                    container.innerHTML = '<p style="color: #888; font-size: 0.85rem;">No reviews yet.</p>';
                    return;
                }
                container.innerHTML = reviews.slice(0, 3).map(function(rev) {
                    var name = rev.tourist_name || 'Tourist';
                    var stars = '★'.repeat(rev.rating || 0);
                    var type = (rev.review_type || 'location').toUpperCase();
                    var location = (rev.location_name || '').replace(/</g, '&lt;');
                    var locationMeta = location ? (' · ' + location) : '';
                    return '<div class="review-item"><p class="review-quote">"' + (rev.comment || '').replace(/</g, '&lt;') + '"</p><small class="review-meta">— ' + name.replace(/</g, '&lt;') + ' · ' + stars + ' · ' + type + locationMeta + '</small></div>';
                }).join('');
            })
            .catch(function() {
                container.innerHTML = '<p style="color: #888; font-size: 0.85rem;">No reviews yet.</p>';
            });
    }

    function setupOverviewCardSelection() {
        var cards = Array.prototype.slice.call(document.querySelectorAll('.overview-card-plain'));
        if (!cards.length) return;

        function selectCard(card) {
            cards.forEach(function(item) { item.classList.remove('is-selected'); });
            card.classList.add('is-selected');
        }

        cards.forEach(function(card) {
            card.addEventListener('click', function() {
                selectCard(card);
            });
            card.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    selectCard(card);
                }
            });
        });
    }

function formatBookingDateTimeShort(value) {
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

function loadPendingGuideBookings() {
    var loading = document.getElementById('guidePendingBookingsLoading');
    var table = document.getElementById('guidePendingBookingsTable');
    var body = document.getElementById('guidePendingBookingsBody');
    var empty = document.getElementById('guidePendingBookingsEmpty');
    if (!loading) return;

    loading.style.display = 'block';
    if (table) table.style.display = 'none';
    if (empty) empty.style.display = 'none';

    fetch('get_pending_bookings_guide.php', { credentials: 'same-origin' })
        .then(function(r) {
            if (r.status === 403) {
                window.location.href = 'signinTouristAdmin.html';
                return [];
            }
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
                body.innerHTML = data.map(function(booking) {
                    var details = [
                        booking.meeting_location ? ('Suggested location: ' + escapeHtml(booking.meeting_location)) : '',
                        booking.tourist_message ? ('Tourist note: ' + escapeHtml(booking.tourist_message)) : ''
                    ].filter(Boolean).join('<br>');
                    return '<tr data-booking-id="' + booking.booking_id + '">' +
                        '<td style="padding: 10px 8px;"><b>' + escapeHtml(booking.tourist_name || 'Tourist') + '</b></td>' +
                        '<td style="padding: 10px 8px;">' + escapeHtml(formatBookingDateTimeShort(booking.created_at || '')) + '</td>' +
                        '<td style="padding: 10px 8px;">' + (details ? details : '—') + '</td>' +
                        '<td style="padding: 10px 8px;"><button type="button" class="btn btn-primary accept-booking-btn" data-booking-id="' + booking.booking_id + '">Accept booking</button></td>' +
                        '</tr>';
                }).join('');
            }

            document.querySelectorAll('.accept-booking-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var bookingId = this.getAttribute('data-booking-id');
                    if (!bookingId) return;
                    if (!window.confirm('Accept this booking request?')) return;

                    this.disabled = true;
                    this.textContent = 'Accepting...';
                    var form = new FormData();
                    form.append('booking_id', bookingId);

                    fetch('approve_booking.php', { method: 'POST', credentials: 'same-origin', body: form })
                        .then(function(r) { return r.json(); })
                        .then(function(res) {
                            if (!res.ok) {
                                throw new Error(res.error || 'Could not accept booking.');
                            }
                            loadPendingGuideBookings();
                            loadApprovedBooking();
                        })
                        .catch(function(err) {
                            alert((err && err.message) || 'Could not accept booking.');
                            btn.disabled = false;
                            btn.textContent = 'Accept booking';
                        });
                });
            });
        })
        .catch(function() {
            loading.style.display = 'none';
            if (empty) {
                empty.textContent = 'Could not load pending bookings.';
                empty.style.display = 'block';
            }
        });
}

function loadApprovedBooking() {
    var labelEl = document.getElementById('bookingHeroLabel');
    var titleEl = document.getElementById('bookingHeroTitle');
    var textEl = document.getElementById('bookingHeroText');
    var metaEl = document.getElementById('bookingHeroMeta');
    var actionsEl = document.getElementById('bookingHeroActions');
    var taskTimeEl = document.getElementById('bookingTaskTime');
    var taskStatusEl = document.getElementById('bookingTaskStatus');
    var taskTitleEl = document.getElementById('bookingTaskTitle');
    var taskPrimaryLineEl = document.getElementById('bookingTaskPrimaryLine');
    var taskSecondaryLineEl = document.getElementById('bookingTaskSecondaryLine');
    var scheduleCardTitleEl = document.getElementById('scheduleCardTitle');
    var scheduleCardTextEl = document.getElementById('scheduleCardText');
    var scheduleCardButtonEl = document.getElementById('scheduleCardButton');
    var heroMessageBtn = document.getElementById('heroMessageTouristBtn');
    var taskMessageBtn = document.getElementById('bookingMessageBtn');

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

    function formatBookingTime(value) {
        if (!value) return '--:--';
        var normalized = String(value).replace(' ', 'T');
        var date = new Date(normalized);
        if (isNaN(date.getTime())) return '--:--';
        return date.toLocaleTimeString(undefined, {
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function setTaskCardEmpty() {
        if (taskTimeEl) taskTimeEl.textContent = '--:--';
        if (taskStatusEl) taskStatusEl.textContent = 'OPEN';
        if (taskTitleEl) taskTitleEl.textContent = 'No active booking';
        if (taskPrimaryLineEl) taskPrimaryLineEl.innerHTML = '<i data-feather="users" style="width: 16px;"></i> Waiting for a tourist booking.';
        if (taskSecondaryLineEl) taskSecondaryLineEl.innerHTML = '<i data-feather="calendar" style="width: 16px;"></i> Approved booking details will appear here.';
        activeBookingConversation = null;
        updateGuideMessageButtons(false);
        feather.replace();
    }

    function setScheduleCardEmpty() {
        if (scheduleCardTitleEl) scheduleCardTitleEl.textContent = 'My Schedule';
        if (scheduleCardTextEl) scheduleCardTextEl.textContent = 'No accepted tourist booking yet. Your current guide assignment will appear here once you accept a request.';
        if (scheduleCardButtonEl) scheduleCardButtonEl.textContent = 'VIEW STATUS';
    }

    function setTaskCardBooked(data) {
        var touristName = data.tourist_name || 'Tourist';
        var meetTimeText = formatBookingDateTime(data.meet_time);
        var approvedText = formatBookingDateTime(data.approved_at || data.created_at);
        var locationText = data.meeting_location ? ('Location: ' + data.meeting_location) : '';
        if (taskTimeEl) taskTimeEl.textContent = data.meet_time ? formatBookingTime(data.meet_time) : '--:--';
        if (taskStatusEl) taskStatusEl.textContent = 'BOOKED';
        if (taskTitleEl) taskTitleEl.textContent = touristName;
        if (taskPrimaryLineEl) taskPrimaryLineEl.innerHTML = '<i data-feather="users" style="width: 16px;"></i> Tourist assigned to this guide.';
        if (taskSecondaryLineEl) taskSecondaryLineEl.innerHTML = '<i data-feather="calendar" style="width: 16px;"></i> ' + (meetTimeText ? ('Meet on ' + meetTimeText) : (approvedText ? ('Booking accepted on ' + approvedText + '. Message the tourist to confirm the meeting time and place.') : 'Booking accepted. Message the tourist to confirm the meeting time and place.')) + (locationText ? ('<br><span style="display:inline-block; margin-top:4px;">' + escapeHtml(locationText) + '</span>') : '');
        activeBookingConversation = {
            booking_id: data.booking_id,
            tourist_name: touristName,
            status: data.status || 'Approved'
        };
        updateGuideMessageButtons(!!data.booking_id);
        feather.replace();
    }

    function setScheduleCardBooked(data) {
        var touristName = data.tourist_name || 'Tourist';
        var meetTimeText = formatBookingDateTime(data.meet_time);
        var approvedText = formatBookingDateTime(data.approved_at || data.created_at);
        var locationText = data.meeting_location ? (' Meeting point: ' + data.meeting_location + '.') : '';
        if (scheduleCardTitleEl) scheduleCardTitleEl.textContent = 'Booked Tourist';
        if (scheduleCardTextEl) {
            scheduleCardTextEl.textContent = meetTimeText
                ? (touristName + ' is currently assigned to you. Meet the tourist on ' + meetTimeText + '.' + locationText)
                : approvedText
                ? (touristName + ' is currently assigned to you. Booking approved on ' + approvedText + '. Send a message to confirm the meeting time and place.' + locationText)
                : (touristName + ' is currently assigned to you. Booking accepted by you. Send a message to confirm the meeting time and place.');
        }
        if (scheduleCardButtonEl) scheduleCardButtonEl.textContent = 'CURRENT BOOKING';
    }

    fetch('get_guide_booking_status.php', { credentials: 'same-origin' })
        .then(function(r) {
            if (r.status === 403) {
                throw new Error('Unauthorized');
            }
            return r.json();
        })
        .then(function(data) {
            if (!data.booked) {
                if (labelEl) labelEl.textContent = 'Current Assignment';
                if (titleEl) titleEl.textContent = 'No approved booking yet';
                if (textEl) textEl.textContent = "When you accept a tourist's booking request, the tourist name will appear here.";
                if (metaEl) metaEl.textContent = '';
                if (actionsEl) actionsEl.style.display = 'none';
                setTaskCardEmpty();
                setScheduleCardEmpty();
                return;
            }

            if (labelEl) labelEl.textContent = 'Approved Booking';
            if (titleEl) titleEl.textContent = 'Booked by ' + (data.tourist_name || 'Tourist');
            if (textEl) textEl.textContent = data.meet_time
                ? ('A tourist booking has been approved for you. Meet your tourist on ' + formatBookingDateTime(data.meet_time) + '.' + (data.meeting_location ? (' Meeting point: ' + data.meeting_location + '.') : ''))
                : 'A tourist booking has been approved for you. Coordinate with the assigned tourist and prepare for the trip.';
            if (metaEl) {
                metaEl.textContent = data.meet_time
                    ? ('Meet time: ' + formatBookingDateTime(data.meet_time))
                    : data.approved_at
                    ? ('Accepted on ' + data.approved_at)
                    : 'Booking accepted by guide.';
            }
            if (actionsEl) actionsEl.style.display = 'flex';
            if (heroMessageBtn) heroMessageBtn.textContent = 'MESSAGE ' + String(data.tourist_name || 'TOURIST').toUpperCase();
            if (taskMessageBtn) taskMessageBtn.textContent = 'Contact ' + (data.tourist_name || 'Tourist');
            setTaskCardBooked(data);
            setScheduleCardBooked(data);
        })
        .catch(function() {
            if (titleEl) titleEl.textContent = 'No approved booking yet';
            if (textEl) textEl.textContent = 'Booking status could not be loaded right now.';
            if (metaEl) metaEl.textContent = '';
            if (actionsEl) actionsEl.style.display = 'none';
            setTaskCardEmpty();
            setScheduleCardEmpty();
        });
}

    function saveGuideProfile() {
        var yearsEl = document.getElementById('experienceYears');
        var areasEl = document.getElementById('serviceAreas');
        var specEl = document.getElementById('specialization');
        var btn = document.getElementById('saveProfileBtn');
        var msgEl = document.getElementById('profileSaveMessage');
        if (!yearsEl || !areasEl) return;
        var years = parseInt(yearsEl.value, 10) || 0;
        var areas = (areasEl.value || '').trim();
        var spec = (specEl && specEl.value) ? specEl.value.trim() : '';
        if (years < 0) years = 0;
        if (years > 70) years = 70;
        if (btn) btn.disabled = true;
        if (msgEl) { msgEl.style.display = 'none'; msgEl.textContent = ''; }
        var form = new FormData();
        form.append('experience_years', years);
        form.append('service_areas', areas);
        form.append('specialization', spec);
        fetch('update_guide_profile.php', { method: 'POST', credentials: 'same-origin', body: form })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (msgEl) {
                    msgEl.style.display = 'block';
                    msgEl.style.color = data.ok ? '#40c057' : '#e03131';
                    msgEl.textContent = data.ok ? 'Saved.' : (data.error || 'Save failed.');
                }
                if (data.ok) {
                    yearsEl.value = years;
                    areasEl.value = areas;
                    if (specEl) specEl.value = spec;
                }
            })
            .catch(function() {
                if (msgEl) { msgEl.style.display = 'block'; msgEl.style.color = '#e03131'; msgEl.textContent = 'Save failed.'; }
            })
            .finally(function() { if (btn) btn.disabled = false; });
    }

    window.addEventListener('load', function() {
        applyGuideDisplayName(getStoredGuideDisplayName());
        setupOverviewCardSelection();
        loadGuideProfile();
        loadTouristReviews();
        loadPendingGuideBookings();
        loadApprovedBooking();
        var heroMessageBtn = document.getElementById('heroMessageTouristBtn');
        var taskMessageBtn = document.getElementById('bookingMessageBtn');
        var modalCloseBtn = document.getElementById('guideMessageModalClose');
        var modal = document.getElementById('guideMessageModal');
        var sendMessageBtn = document.getElementById('sendBookingMessageBtn');
        var saveBtn = document.getElementById('saveProfileBtn');
        if (saveBtn) saveBtn.addEventListener('click', saveGuideProfile);
        if (heroMessageBtn) heroMessageBtn.addEventListener('click', openGuideMessageModal);
        if (taskMessageBtn) taskMessageBtn.addEventListener('click', openGuideMessageModal);
        if (modalCloseBtn) modalCloseBtn.addEventListener('click', closeGuideMessageModal);
        if (sendMessageBtn) sendMessageBtn.addEventListener('click', sendGuideMessage);
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeGuideMessageModal();
                }
            });
        }
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeGuideMessageModal();
            }
        });
    });