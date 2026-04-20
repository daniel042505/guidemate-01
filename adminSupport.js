function escapeHtml(str) {
    return String(str || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function formatMessageDateTime(value) {
    if (!value) return '';
    var date = new Date(value.replace(' ', 'T'));
    if (Number.isNaN(date.getTime())) return value;
    return date.toLocaleString([], { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}

var adminSupportState = {
    isAdmin: false,
    selectedRole: null,
    selectedUserId: null,
    isLoaded: false
};

function setAdminStatus(text, type) {
    var el = document.getElementById('adminChatStatus');
    if (!el) return;
    el.textContent = String(text || '');
    el.style.color = type === 'error' ? '#b91c1c' : '#2563eb';
}

function renderAdminThread(messages) {
    var container = document.getElementById('adminChatThread');
    if (!container) return;
    if (!Array.isArray(messages) || messages.length === 0) {
        container.innerHTML = '<div class="empty-thread">No messages yet. Send the first message below.</div>';
        return;
    }
    container.innerHTML = messages.map(function(message) {
        var senderRole = message.sender_role === 'admin' ? 'admin' : 'user';
        var senderLabel = message.sender_role === 'admin' ? 'Admin' : 'You';
        var bubbleClass = senderRole === 'admin' ? 'admin' : 'user';
        return '<div class="message-row ' + bubbleClass + '">' +
            '<div class="message-bubble">' +
            '<strong>' + escapeHtml(senderLabel) + '</strong>' +
            '<p>' + escapeHtml(message.message_text || '') + '</p>' +
            '<div class="message-meta">' + escapeHtml(formatMessageDateTime(message.created_at)) + '</div>' +
            '</div>' +
            '</div>';
    }).join('');
    container.scrollTop = container.scrollHeight;
}

function renderAdminConversations(conversations) {
    var list = document.getElementById('adminConversationList');
    if (!list) return;
    if (!Array.isArray(conversations) || conversations.length === 0) {
        list.innerHTML = '<li class="conversation-item">No active admin conversations yet.</li>';
        return;
    }
    list.innerHTML = conversations.map(function(item) {
        var active = (item.other_role === adminSupportState.selectedRole && item.other_user_id === adminSupportState.selectedUserId) ? ' active' : '';
        return '<li class="conversation-item' + active + '" data-role="' + escapeHtml(item.other_role) + '" data-userid="' + item.other_user_id + '">' +
            '<strong>' + escapeHtml(item.display_name) + '</strong>' +
            '<small>' + escapeHtml(item.last_at) + ' · ' + item.message_count + ' messages</small>' +
            '</li>';
    }).join('');
    Array.from(list.querySelectorAll('.conversation-item')).forEach(function(item) {
        item.addEventListener('click', function() {
            var role = this.getAttribute('data-role');
            var userId = parseInt(this.getAttribute('data-userid'), 10);
            if (!role || !userId) return;
            adminSupportState.selectedRole = role;
            adminSupportState.selectedUserId = userId;
            loadAdminMessages(role, userId);
            renderAdminConversations(conversations);
        });
    });
}

function setChatHeader(title, subtitle) {
    var titleEl = document.getElementById('chatTitle');
    var subtitleEl = document.getElementById('chatSubtitle');
    if (titleEl) titleEl.textContent = title;
    if (subtitleEl) subtitleEl.textContent = subtitle;
}

function loadAdminMessages(role, userId) {
    var url = 'get_admin_messages.php';
    if (role && userId) {
        url += '?user_role=' + encodeURIComponent(role) + '&user_id=' + encodeURIComponent(userId);
    }
    fetch(url, { credentials: 'same-origin' })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (!data.ok) {
                throw new Error(data.error || 'Could not load messages.');
            }
            renderAdminThread(data.messages || []);
            if (adminSupportState.isAdmin) {
                var title = 'Admin support: ' + (role ? role.charAt(0).toUpperCase() + role.slice(1) : 'Conversation');
                var subtitle = role && userId ? ('Viewing conversation with ' + title.toLowerCase() + ' #' + userId) : 'Select a conversation from the list.';
                setChatHeader(title, subtitle);
            } else {
                setChatHeader('Support Chat', 'Use this chat to message GuideMate admin directly.');
            }
            setAdminStatus('', '');
        })
        .catch(function(err) {
            renderAdminThread([]);
            setChatHeader('Support Chat', 'Could not load support conversation.');
            setAdminStatus(err.message || 'Could not load admin messages.', 'error');
        });
}

function loadAdminConversations() {
    fetch('get_admin_conversations.php', { credentials: 'same-origin' })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (!data.ok) {
                throw new Error(data.error || 'Could not load conversations.');
            }
            document.getElementById('adminConversationsSection').hidden = false;
            document.getElementById('guideNote').hidden = true;
            if (!adminSupportState.selectedRole || !adminSupportState.selectedUserId) {
                if (data.conversations.length > 0) {
                    adminSupportState.selectedRole = data.conversations[0].other_role;
                    adminSupportState.selectedUserId = data.conversations[0].other_user_id;
                }
            }
            renderAdminConversations(data.conversations);
            if (adminSupportState.selectedRole && adminSupportState.selectedUserId) {
                loadAdminMessages(adminSupportState.selectedRole, adminSupportState.selectedUserId);
            }
        })
        .catch(function(err) {
            setAdminStatus(err.message || 'Could not load admin conversations.', 'error');
        });
}

function sendAdminMessage() {
    var input = document.getElementById('adminChatInput');
    if (!input) return;
    var text = input.value.trim();
    if (text === '') {
        setAdminStatus('Type a message before sending.', 'error');
        return;
    }
    var payload = new FormData();
    payload.append('message_text', text);
    if (adminSupportState.isAdmin) {
        if (!adminSupportState.selectedRole || !adminSupportState.selectedUserId) {
            setAdminStatus('Select a user conversation first.', 'error');
            return;
        }
        payload.append('recipient_role', adminSupportState.selectedRole);
        payload.append('recipient_user_id', String(adminSupportState.selectedUserId));
    }
    var button = document.getElementById('adminChatSendBtn');
    if (button) {
        button.disabled = true;
        button.textContent = 'Sending...';
    }
    setAdminStatus('', '');
    fetch('send_admin_message.php', {
        method: 'POST',
        credentials: 'same-origin',
        body: payload
    })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (!data.ok) {
                throw new Error(data.error || 'Could not send message.');
            }
            input.value = '';
            setAdminStatus('Message sent.', 'success');
            if (adminSupportState.isAdmin) {
                loadAdminConversations();
            } else {
                loadAdminMessages();
            }
        })
        .catch(function(err) {
            setAdminStatus(err.message || 'Could not send message.', 'error');
        })
        .finally(function() {
            if (button) {
                button.disabled = false;
                button.textContent = 'Send message';
            }
        });
}

function initAdminSupport() {
    var sendBtn = document.getElementById('adminChatSendBtn');
    if (sendBtn) {
        sendBtn.addEventListener('click', sendAdminMessage);
    }
    var status = document.getElementById('adminChatStatus');
    var note = document.getElementById('guideNote');
    if (note) note.hidden = true;
    fetch('get_admin_messages.php', { credentials: 'same-origin' })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.ok && data.admin === true) {
                adminSupportState.isAdmin = true;
                document.getElementById('guideNote').hidden = true;
                setChatHeader('Admin Support Inbox', 'Select a conversation from the list to reply.');
                loadAdminConversations();
                return;
            }
            if (data.ok) {
                adminSupportState.isAdmin = false;
                setChatHeader('Support Chat', 'Use this chat to message GuideMate admin directly.');
                renderAdminThread(data.messages || []);
                setAdminStatus('', '');
                return;
            }
            throw new Error(data.error || 'Could not load messages.');
        })
        .catch(function(err) {
            setChatHeader('Support Chat', 'Could not load support conversation.');
            setAdminStatus(err.message || 'Unable to load conversation.', 'error');
        });
}

window.addEventListener('DOMContentLoaded', initAdminSupport);
